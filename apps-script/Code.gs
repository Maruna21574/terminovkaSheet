/**
 * Google Apps Script — prijíma registrácie bežcov z formulára a zapisuje ich
 * do listu "Prihlasenia" v Google Sheete, ktorého ID príde v požiadavke
 * (payload.spreadsheet_id). Vďaka tomu jeden Apps Script vie obsluhovať
 * ľubovoľný počet podujatí, každé s vlastným Sheetom.
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

var SHEET_NAME = 'Prihlasenia';
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
    var lock = LockService.getScriptLock();
    var gotLock = false;
    try {
        gotLock = lock.tryLock(30000);
        if (!gotLock) {
            return jsonResponse({ success: false, error: 'Server je momentálne vyťažený, skúste znova.' });
        }

        var data = JSON.parse(e.postData.contents);

        var expectedSecret = PropertiesService.getScriptProperties().getProperty('SHARED_SECRET');
        if (expectedSecret && data.secret !== expectedSecret) {
            return jsonResponse({ success: false, error: 'Neplatný prístup.' });
        }

        var spreadsheetId = sanitize(data.spreadsheet_id);
        if (!spreadsheetId) {
            return jsonResponse({ success: false, error: 'Chýba ID cieľového Google Sheetu.' });
        }

        var sheet = getOrCreateSheet(spreadsheetId);
        var submissionId = sanitize(data.submission_id);

        if (submissionId && isDuplicate(sheet, submissionId)) {
            return jsonResponse({ success: true, duplicate: true });
        }

        var narodenie = sanitize(data.narodenie);
        var rok = extractYear(narodenie);

        sheet.appendRow([
            new Date(),
            sanitize(data.event_name || data.event_slug),
            sanitize(data.priezvisko),
            sanitize(data.meno),
            sanitize(data.pohlavie),
            narodenie,
            rok,
            sanitize(data.klub),
            sanitize(data.obec),
            sanitize(data.trat),
            submissionId
        ]);

        return jsonResponse({ success: true });
    } catch (err) {
        return jsonResponse({ success: false, error: String(err) });
    } finally {
        if (gotLock) {
            lock.releaseLock();
        }
    }
}

function doGet() {
    return jsonResponse({ success: true, info: 'Terminovka registrácia — Apps Script beží.' });
}

function getOrCreateSheet(spreadsheetId) {
    var ss = SpreadsheetApp.openById(spreadsheetId);
    var sheet = ss.getSheetByName(SHEET_NAME);
    if (!sheet) {
        sheet = ss.insertSheet(SHEET_NAME);
    }
    if (sheet.getLastRow() === 0) {
        sheet.appendRow(HEADERS);
        sheet.setFrozenRows(1);
    }
    return sheet;
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
