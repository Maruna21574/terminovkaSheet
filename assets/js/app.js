(function () {
    'use strict';

    var QUEUE_KEY = 'terminovka_queue_v1';
    var RETRY_INTERVAL_MS = 7000;
    var SENDING = false;
    var lastSubmittedId = null;

    var form = document.getElementById('runner-form');
    var submitBtn = document.getElementById('submit-btn');
    var narodenieInput = document.getElementById('narodenie');
    var formMessage = document.getElementById('form-message');
    var queueStatus = document.getElementById('queue-status');
    var queueStatusText = document.getElementById('queue-status-text');

    // ---------- Consent text toggles ----------
    document.querySelectorAll('[data-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = document.getElementById(btn.getAttribute('data-toggle'));
            if (target) {
                target.hidden = !target.hidden;
            }
        });
    });

    // ---------- Birth date auto-formatting DD.MM.RRRR ----------
    narodenieInput.addEventListener('input', function () {
        var digits = narodenieInput.value.replace(/\D/g, '').slice(0, 8);
        var out = '';
        if (digits.length <= 2) {
            out = digits;
        } else if (digits.length <= 4) {
            out = digits.slice(0, 2) + '.' + digits.slice(2);
        } else {
            out = digits.slice(0, 2) + '.' + digits.slice(2, 4) + '.' + digits.slice(4);
        }
        narodenieInput.value = out;
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

        var meno = document.getElementById('meno').value.trim();
        var priezvisko = document.getElementById('priezvisko').value.trim();
        var pohlavie = document.getElementById('pohlavie').value;
        var narodenie = narodenieInput.value.trim();
        var klub = document.getElementById('klub').value.trim();
        var obec = document.getElementById('obec').value.trim();
        var trat = document.getElementById('trat').value.trim();
        var suhlasUdaje = document.getElementById('suhlas_udaje').checked;
        var suhlasPodmienky = document.getElementById('suhlas_podmienky').checked;
        var eventSlug = form.querySelector('[name="event_slug"]').value;

        var errors = [];
        if (!meno) errors.push('Vyplňte meno.');
        if (!priezvisko) errors.push('Vyplňte priezvisko.');
        if (!pohlavie) errors.push('Vyberte pohlavie.');
        if (!isValidBirthdate(narodenie)) errors.push('Zadajte platný dátum narodenia (DD.MM.RRRR).');
        if (!obec) errors.push('Vyplňte obec.');
        if (!trat) errors.push('Vyplňte trať.');
        if (!suhlasUdaje) errors.push('Potvrďte súhlas so spracovaním osobných údajov.');
        if (!suhlasPodmienky) errors.push('Potvrďte súhlas s podmienkami podujatia.');

        if (errors.length > 0) {
            showMessage(errors.join(' '), 'error');
            return;
        }

        var item = {
            event_slug: eventSlug,
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

        form.reset();
        narodenieInput.value = '';
        showMessage('Záznam sa odosiela...', 'success');

        flushQueue();
    });

    // ---------- Retry triggers ----------
    window.addEventListener('online', flushQueue);
    window.addEventListener('load', flushQueue);
    setInterval(flushQueue, RETRY_INTERVAL_MS);

    updateQueueStatus();
})();
