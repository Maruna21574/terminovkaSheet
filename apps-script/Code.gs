/**
 * Google Apps Script — prijíma registrácie bežcov z formulára a zapisuje ich
 * do Google Sheetu, ktorého ID príde v požiadavke (payload.spreadsheet_id).
 * Každé podujatie dostane vlastný HÁROK (tab) v tomto Sheete, pomenovaný podľa
 * URL adresy (slug) podujatia - takže pokojne môžeš pre všetky podujatia v admine
 * použiť ten istý Google Sheet dokument a appka si sama vytvorí nový hárok pre
 * každé nové podujatie. (Ak chceš pre niektoré podujatie úplne samostatný
 * dokument, stačí mu v admine zadať iné spreadsheet_id/URL.)
 *
 * DÔLEŽITÉ: Google účet, pod ktorým je tento skript nasadený ("Execute as: Me"),
 * musí mať editovacie práva na KAŽDÝ Sheet, ktorého ID sem príde (typicky preto,
 * lebo ho vytvoril rovnaký Google účet ako vlastní tento skript).
 *
 * NASTAVENIE:
 * 1. V ktoromkoľvek Google Sheete choď na Rozšírenia > Apps Script (stačí raz,
 *    skript nemusí byť naviazaný práve na ten Sheet, do ktorého sa má zapisovať).
 * 2. Vlož tento súbor ako Code.gs (nahraď predvolený obsah).
 * 3. Nastavenia projektu > Vlastnosti skriptu > Pridať vlastnosť skriptu:
 *      Názov:  SHARED_SECRET
 *      Hodnota: rovnaké tajné heslo ako v config.php (SHARED_SECRET)
 * 4. Nasadenie > Nová nasadená verzia > Typ: Web App.
 *      - Spustiť ako: Ja (vlastník)
 *      - Kto má prístup: Ktokoľvek
 * 5. Skopíruj URL (končí na /exec) do config.php ako APPS_SCRIPT_URL.
 * 6. Pri prvom spustení Google požiada o povolenie prístupu k Sheetom — schváľ.
 */

var HEADERS = [
    'Čas odoslania',
    'Podujatie',
    'Priezvisko',
    'Meno',
    'Pohlavie',
    'Dátum narodenia',
    'Rok narodenia',
    'Klub',
    'Obec',
    'Trať',
    'ID záznamu (dedup)'
];

function doPost(e) {
    try {
        // Parsovanie, kontrola hesla a príprava dát nepotrebujú zámok - nesiahajú
        // na zdieľaný Sheet, takže nemá zmysel kvôli nim blokovať ostatných.
        var data = JSON.parse(e.postData.contents);

        var expectedSecret = PropertiesService.getScriptProperties().getProperty('SHARED_SECRET');
        if (expectedSecret && data.secret !== expectedSecret) {
            return jsonResponse({ success: false, error: 'Neplatný prístup.' });
        }

        var spreadsheetId = sanitize(data.spreadsheet_id);
        if (!spreadsheetId) {
            return jsonResponse({ success: false, error: 'Chýba ID cieľového Google Sheetu.' });
        }

        var submissionId = sanitize(data.submission_id);
        var narodenie = sanitize(data.narodenie);
        var row = [
            new Date(),
            sanitize(data.event_name || data.event_slug),
            sanitize(data.priezvisko),
            sanitize(data.meno),
            sanitize(data.pohlavie),
            narodenie,
            extractYear(narodenie),
            sanitize(data.klub),
            sanitize(data.obec),
            sanitize(data.trat),
            submissionId
        ];

        // Zámok drží len tú časť, ktorá sa naozaj musí vykonať postupne
        // (kontrola duplicity + zápis riadka) - ostatní tak čakajú čo najkratšie.
        var lock = LockService.getScriptLock();
        var gotLock = false;
        try {
            gotLock = lock.tryLock(30000);
            if (!gotLock) {
                return jsonResponse({ success: false, error: 'Server je momentálne vyťažený, skúste znova.' });
            }

            var sheet = getOrCreateSheet(spreadsheetId, sheetNameFor(data));

            if (submissionId && isDuplicate(sheet, submissionId)) {
                return jsonResponse({ success: true, duplicate: true });
            }

            sheet.getRange(sheet.getLastRow() + 1, 1, 1, row.length).setValues([row]);

            return jsonResponse({ success: true });
        } finally {
            if (gotLock) {
                lock.releaseLock();
            }
        }
    } catch (err) {
        return jsonResponse({ success: false, error: String(err) });
    }
}

function doGet() {
    return jsonResponse({ success: true, info: 'Terminovka registrácia — Apps Script beží.' });
}

function getOrCreateSheet(spreadsheetId, sheetName) {
    var ss = SpreadsheetApp.openById(spreadsheetId);
    var sheet = ss.getSheetByName(sheetName);
    if (!sheet) {
        sheet = ss.insertSheet(sheetName);
    }
    if (sheet.getLastRow() === 0) {
        sheet.appendRow(HEADERS);
        sheet.setFrozenRows(1);
        // Posledný stĺpec (ID záznamu) slúži len na ochranu pred duplicitami,
        // v tabuľke nie je potrebný - skryjeme ho, aby neprekážal.
        sheet.hideColumns(HEADERS.length);
    }
    return sheet;
}

/**
 * Názov hárka (tab) pre dané podujatie - podľa názvu podujatia zadaného
 * v administrácii, očistený od znakov, ktoré Google Sheets v názve hárka
 * nepovoľuje.
 */
function sheetNameFor(data) {
    var raw = sanitize(data.event_name) || sanitize(data.event_slug) || 'podujatie';
    var name = raw.replace(/[\[\]\*\?\/\\:]/g, '-').substring(0, 100);
    return name || 'podujatie';
}

function isDuplicate(sheet, submissionId) {
    var idColumn = HEADERS.length; // posledný stĺpec = ID záznamu
    var lastRow = sheet.getLastRow();
    if (lastRow < 2) return false;
    var values = sheet.getRange(2, idColumn, lastRow - 1, 1).getValues();
    for (var i = 0; i < values.length; i++) {
        if (String(values[i][0]) === submissionId) {
            return true;
        }
    }
    return false;
}

function sanitize(value) {
    var v = (value === undefined || value === null) ? '' : String(value).trim();
    // Ochrana pred formula injection v Google Sheets
    if (v !== '' && /^[=+\-@]/.test(v)) {
        v = "'" + v;
    }
    return v;
}

function extractYear(narodenie) {
    var m = /^(\d{1,2})\.(\d{1,2})\.(\d{4})$/.exec(narodenie);
    return m ? m[3] : '';
}

function jsonResponse(obj) {
    return ContentService
        .createTextOutput(JSON.stringify(obj))
        .setMimeType(ContentService.MimeType.JSON);
}
