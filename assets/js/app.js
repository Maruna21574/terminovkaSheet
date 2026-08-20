(function () {
    'use strict';

    var QUEUE_KEY = 'terminovka_queue_v1';
    var RETRY_INTERVAL_MS = 7000;
    var SENDING = false;
    var lastSubmittedId = null;

    var form = document.getElementById('runner-form');
    var submitBtn = document.getElementById('submit-btn');
    var narodenieDen = document.getElementById('narodenie_den');
    var narodenieMesiac = document.getElementById('narodenie_mesiac');
    var narodenieRok = document.getElementById('narodenie_rok');
    var formMessage = document.getElementById('form-message');
    var queueStatus = document.getElementById('queue-status');
    var queueStatusText = document.getElementById('queue-status-text');
    var pickupModal = document.getElementById('pickup-modal');

    var FIELD_IDS = ['meno', 'priezvisko', 'pohlavie', 'narodenie', 'obec', 'trat', 'suhlas_udaje', 'suhlas_podmienky'];

    function setButtonLoading(loading) {
        submitBtn.disabled = loading;
        submitBtn.classList.toggle('is-loading', loading);
    }

    // ---------- Popup po úspešnom odoslaní ----------
    function showPickupModal() {
        pickupModal.hidden = false;
        document.body.classList.add('modal-open');
    }

    function hidePickupModal() {
        pickupModal.hidden = true;
        document.body.classList.remove('modal-open');

        // Formulár zámerne zostáva vyplnený až do zatvorenia popupu (potvrdenie
        // toho, čo sa odoslalo) - vyčistí sa a scrollne na začiatok až teraz.
        form.reset();
        clearAllFieldErrors();
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        var firstField = document.getElementById('meno');
        if (firstField) {
            firstField.focus({ preventScroll: true });
        }
    }

    pickupModal.querySelectorAll('[data-modal-close]').forEach(function (el) {
        el.addEventListener('click', hidePickupModal);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !pickupModal.hidden) {
            hidePickupModal();
        }
    });

    // ---------- Consent text toggles ----------
    document.querySelectorAll('[data-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = document.getElementById(btn.getAttribute('data-toggle'));
            if (target) {
                target.hidden = !target.hidden;
            }
        });
    });

    // ---------- Chybové hlášky pri jednotlivých poliach ----------
    function fieldInputs(id) {
        if (id === 'narodenie') {
            return [narodenieDen, narodenieMesiac, narodenieRok];
        }
        var el = document.getElementById(id);
        return el ? [el] : [];
    }

    function setFieldError(id, message) {
        var errorEl = document.getElementById(id + '-error');
        var inputs = fieldInputs(id);
        if (message) {
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.hidden = false;
            }
            inputs.forEach(function (el) { el.classList.add('error'); });
        } else {
            if (errorEl) {
                errorEl.hidden = true;
                errorEl.textContent = '';
            }
            inputs.forEach(function (el) { el.classList.remove('error'); });
        }
    }

    function clearAllFieldErrors() {
        FIELD_IDS.forEach(function (id) { setFieldError(id, null); });
    }

    FIELD_IDS.forEach(function (id) {
        fieldInputs(id).forEach(function (el) {
            el.addEventListener('input', function () { setFieldError(id, null); });
            el.addEventListener('change', function () { setFieldError(id, null); });
        });
    });

    function isValidBirthdate(value) {
        var m = /^(\d{1,2})\.(\d{1,2})\.(\d{4})$/.exec(value);
        if (!m) return false;
        var day = parseInt(m[1], 10);
        var month = parseInt(m[2], 10);
        var year = parseInt(m[3], 10);
        var currentYear = new Date().getFullYear();
        if (year < 1900 || year > currentYear) return false;
        if (month < 1 || month > 12) return false;
        var daysInMonth = new Date(year, month, 0).getDate();
        return day >= 1 && day <= daysInMonth;
    }

    function uuid() {
        if (window.crypto && window.crypto.randomUUID) {
            return window.crypto.randomUUID();
        }
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = (Math.random() * 16) | 0;
            var v = c === 'x' ? r : (r & 0x3) | 0x8;
            return v.toString(16);
        });
    }

    // ---------- Queue persistence ----------
    function loadQueue() {
        try {
            var raw = localStorage.getItem(QUEUE_KEY);
            return raw ? JSON.parse(raw) : [];
        } catch (e) {
            return [];
        }
    }

    function saveQueue(queue) {
        try {
            localStorage.setItem(QUEUE_KEY, JSON.stringify(queue));
        } catch (e) {
            // localStorage nedostupné (napr. súkromný režim) - odošleme aspoň priamo, bez fronty
        }
    }

    function updateQueueStatus() {
        var queue = loadQueue();
        if (queue.length === 0) {
            queueStatus.hidden = true;
            queueStatus.classList.remove('is-ok');
            return;
        }
        queueStatus.hidden = false;
        queueStatus.classList.remove('is-ok');
        queueStatusText.textContent = queue.length === 1
            ? 'Čaká na odoslanie 1 záznam...'
            : 'Čaká na odoslanie ' + queue.length + ' záznamov...';
    }

    function showMessage(text, type) {
        formMessage.hidden = false;
        formMessage.textContent = text;
        formMessage.className = 'form-message ' + (type === 'error' ? 'is-error' : 'is-success');
        window.clearTimeout(showMessage._t);
        showMessage._t = window.setTimeout(function () {
            formMessage.hidden = true;
        }, 4000);
    }

    // Stavy odpovede zo servera:
    // - 'success': záznam bol zapísaný, vyhoď z frontu
    // - 'invalid': dáta sú chybné (4xx) - opakovanie by nikdy neuspelo, vyhoď z frontu a nahlás chybu
    // - 'retry': dočasný problém (výpadok siete, 5xx) - nechaj vo fronte, skúsi sa znova
    function sendItem(item, callback) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/submit.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.timeout = 12000;
        xhr.onload = function () {
            if (xhr.status >= 200 && xhr.status < 300) {
                callback('success');
                return;
            }
            if (xhr.status >= 400 && xhr.status < 500) {
                var error = 'Záznam bol odmietnutý serverom.';
                try {
                    var parsed = JSON.parse(xhr.responseText);
                    if (parsed && parsed.error) error = parsed.error;
                } catch (e) { /* ignoruj, použi predvolenú hlášku */ }
                callback('invalid', error);
                return;
            }
            callback('retry');
        };
        xhr.onerror = function () { callback('retry'); };
        xhr.ontimeout = function () { callback('retry'); };
        xhr.send(JSON.stringify(item));
    }

    function flushQueue() {
        if (SENDING) return;
        var queue = loadQueue();
        if (queue.length === 0) {
            updateQueueStatus();
            return;
        }
        SENDING = true;
        var current = queue[0];

        sendItem(current, function (result, error) {
            if (result === 'success' || result === 'invalid') {
                var latest = loadQueue().filter(function (i) { return i.submission_id !== current.submission_id; });
                saveQueue(latest);

                if (result === 'success' && current.submission_id === lastSubmittedId) {
                    showMessage('Registrácia bola úspešne odoslaná.', 'success');
                    showPickupModal();
                } else if (result === 'invalid') {
                    showMessage('Záznam sa nepodarilo odoslať: ' + error, 'error');
                }

                SENDING = false;
                updateQueueStatus();
                if (latest.length > 0) {
                    flushQueue();
                }
            } else {
                SENDING = false;
                updateQueueStatus();
            }
        });
    }

    // ---------- Form submit ----------
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        clearAllFieldErrors();

        var meno = document.getElementById('meno').value.trim();
        var priezvisko = document.getElementById('priezvisko').value.trim();
        var pohlavie = document.getElementById('pohlavie').value;
        var den = narodenieDen.value;
        var mesiac = narodenieMesiac.value;
        var rok = narodenieRok.value.trim();
        var narodenie = (den && mesiac && rok) ? (den + '.' + mesiac + '.' + rok) : '';
        var klub = document.getElementById('klub').value.trim();
        var obec = document.getElementById('obec').value.trim();
        var trat = document.getElementById('trat').value.trim();
        var suhlasUdaje = document.getElementById('suhlas_udaje').checked;
        var suhlasPodmienky = document.getElementById('suhlas_podmienky').checked;
        var eventSlug = form.querySelector('[name="event_slug"]').value;
        var website = document.getElementById('website').value;

        var firstInvalid = null;
        function fail(id, message) {
            setFieldError(id, message);
            if (!firstInvalid) {
                firstInvalid = fieldInputs(id)[0];
            }
        }

        if (!meno) fail('meno', 'Vyplňte meno.');
        if (!priezvisko) fail('priezvisko', 'Vyplňte priezvisko.');
        if (!pohlavie) fail('pohlavie', 'Vyberte pohlavie.');
        if (!isValidBirthdate(narodenie)) fail('narodenie', 'Zadajte platný dátum narodenia.');
        if (!trat) fail('trat', 'Vyplňte trať.');
        if (!suhlasUdaje) fail('suhlas_udaje', 'Potvrďte súhlas so spracovaním osobných údajov.');
        if (!suhlasPodmienky) fail('suhlas_podmienky', 'Potvrďte súhlas s podmienkami podujatia.');

        if (firstInvalid) {
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstInvalid.focus({ preventScroll: true });
            return;
        }

        setButtonLoading(true);

        var item = {
            event_slug: eventSlug,
            website: website,
            meno: meno,
            priezvisko: priezvisko,
            pohlavie: pohlavie,
            narodenie: narodenie,
            klub: klub,
            obec: obec,
            trat: trat,
            suhlas_udaje: true,
            suhlas_podmienky: true,
            submission_id: uuid()
        };

        var queue = loadQueue();
        queue.push(item);
        saveQueue(queue);
        updateQueueStatus();

        lastSubmittedId = item.submission_id;

        showMessage('Registrácia sa odosiela. Nezatvárajte okno!', 'success');

        window.setTimeout(function () { setButtonLoading(false); }, 700);

        flushQueue();
    });

    // ---------- Retry triggers ----------
    window.addEventListener('online', flushQueue);
    window.addEventListener('load', flushQueue);
    setInterval(flushQueue, RETRY_INTERVAL_MS);

    // Varovanie pred zatvorením karty, kým čaká neodoslaný záznam vo fronte.
    window.addEventListener('beforeunload', function (e) {
        if (loadQueue().length > 0) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    updateQueueStatus();
})();
