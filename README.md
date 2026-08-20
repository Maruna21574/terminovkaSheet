# Terminovka registrácia

Prezenčná registrácia bežcov cez QR kód. Bežec naskenuje QR kód na mieste podujatia,
otvorí sa mu formulár na adrese `/{slug-podujatia}` (napr. `/podujatie-hron`), vyplní
údaje na svojom telefóne a dáta sa uložia do Google Sheets. Funguje aj pri výpadku
internetu — záznamy sa ukladajú lokálne v prehliadači a automaticky sa doodosielajú,
keď sa spojenie obnoví.

Podujatia sa vytvárajú cez **administráciu** (`/admin`) — každé podujatie má vlastnú
URL adresu aj vlastný cieľový Google Sheet, takže môžeš mať naraz spustených viacero
podujatí (napr. cez víkend), každé zapisujúce do iného Sheetu.

## Ako to funguje

```
bežec (telefón) --/podujatie-hron--> index.php --submit.php (PHP, cURL)--> Google Apps Script --> Google Sheet A
bežec (telefón) --/podujatie-sliac--> index.php --submit.php (PHP, cURL)--> Google Apps Script --> Google Sheet B
```

- **admin/** — prihlásenie a správa podujatí (vytvorenie, úprava, zmazanie). Register
  podujatí sa ukladá do `data/events.json` (slug → názov + ID cieľového Google Sheetu).
- **index.php** — router, podľa `slug` z URL nájde podujatie v `data/events.json`
  a zobrazí formulár. Neznámy slug → 404.
- **submit.php** — prijme JSON z formulára, validuje, dohľadá `spreadsheet_id` podľa
  slugu (klient sám spreadsheet_id nepozná ani neposiela) a pošle dáta na Google Apps Script.
- **Google Apps Script** (`apps-script/Code.gs`) — jeden spoločný skript pre všetky
  podujatia. Podľa `spreadsheet_id`, ktoré príde v požiadavke, otvorí príslušný Sheet
  a zapíše riadok do hárka (tab) pomenovaného podľa slugu podujatia — vytvorí ho, ak
  ešte neexistuje. Takže pokojne môžeš pre viacero podujatí použiť ten istý Sheet
  dokument, každé dostane vlastný hárok automaticky. Chráni pred duplicitami aj
  súbežnými zápismi (viacero bežcov naraz).
- **assets/js/app.js** — offline fronta: pri odoslaní sa záznam okamžite uloží do
  `localStorage` a formulár sa vyčistí (obsluha/bežec môže hneď pokračovať ďalším).
  Na pozadí sa opakovane skúša odoslať (každých 7 s, pri návrate pripojenia, pri
  načítaní stránky), kým sa neuloží do Sheetu. Prežije aj zavretie prehliadača/refresh.

## 1. Nastavenie Google Apps Script (raz, spoločné pre všetky podujatia)

Netreba robiť pre každé podujatie zvlášť — jeden Apps Script vie zapisovať do
ľubovoľného počtu rôznych Sheetov.

1. Otvor **ktorýkoľvek** svoj Google Sheet (môže to byť ten úplne prvý, čo si zdieľal —
   https://docs.google.com/spreadsheets/d/1Cnf6TKf27ekezebHvbrbRbRDG0HO-K4AwqrpnNcbEIM/edit).
2. **Rozšírenia > Apps Script.**
3. Obsah predvoleného `Code.gs` nahraď obsahom súboru [`apps-script/Code.gs`](apps-script/Code.gs) z tohto projektu.
4. **Nastavenia projektu** (ikona ozubeného kolieska vľavo) **> Vlastnosti skriptu > Pridať vlastnosť skriptu:**
   - Názov: `SHARED_SECRET`
   - Hodnota: silné náhodné heslo (rovnaké potom musí byť v `config.php`)
5. **Nasadenie > Nová nasadená verzia:**
   - Typ: **Web App**
   - Spustiť ako: **Ja**
   - Kto má prístup: **Ktokoľvek** ⚠️ (ak tu necháš "Only myself", appka dostane HTTP 401)
6. Skopíruj vygenerovanú URL (končí na `/exec`).
7. Pri prvom spustení ťa Google požiada o schválenie prístupu — potvrď (je to tvoj vlastný skript).

**Dôležité:** Google účet, pod ktorým je skript nasadený, musí mať editovacie práva na
**každý** Sheet, ktorý neskôr priradíš niektorému podujatiu v administrácii (v praxi to
znamená: vytváraj si nové Sheety pod tým istým Google účtom).

Keď neskôr pridáš nové podujatie s novým Sheetom, Apps Script sa **nemení** — stačí
v administrácii zadať URL nového Sheetu.

## 2. Konfigurácia appky

V [`config.php`](config.php) uprav:

```php
define('APPS_SCRIPT_URL', 'https://script.google.com/macros/s/XXXXX/exec'); // URL z kroku 1.6
define('SHARED_SECRET', '...');                                             // rovnaké heslo ako v kroku 1.4
define('SITE_NAME', 'Terminovka registrácia');

define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', '...'); // pozri nižšie ako zmeniť heslo
```

### Zmena admin hesla

Predvolené heslo je vygenerované len na prvé prihlásenie — **zmeň si ho**. Na počítači,
kde máš nainštalované PHP, spusti:

```bash
php -r "echo password_hash('tvoje-nove-heslo', PASSWORD_DEFAULT);"
```

Výsledný reťazec (začína `$2y$...`) vlož do `config.php` ako `ADMIN_PASSWORD_HASH`.
Heslo v čitateľnej podobe sa nikde neukladá.

## 3. Nahratie na Websupport hosting

1. Nahraj **celý obsah** tohto priečinka (okrem `apps-script/`, ktorý sa nikam nenahráva —
   ten ide len do Google Apps Script editora) do koreňa webu (zvyčajne `public_html/` alebo
   podľa toho, ktorý priečinok má Websupport nastavený ako webroot pre tvoju doménu).
2. Over, že `.htaccess` sa nahral (niektoré FTP klienty skrývajú súbory začínajúce bodkou —
   zapni zobrazenie skrytých súborov). Over aj priečinok `data/` a jeho `.htaccess`.
3. Websupport shared hosting má Apache s `mod_rewrite` a podporou `.htaccess` zapnutú
   predvolene — pretty URL (napr. `/podujatie-hron`) by mali fungovať hneď.
4. Priečinok `data/` musí byť pre PHP **zapisovateľný** (aby admin mohol ukladať
   podujatia) — na Websupport hostingu to zvyčajne funguje bez úprav práv, ak nie,
   nastav práva `755`/`775` na priečinok `data/`.
5. Over, že PHP má povolené rozšírenie **cURL** (bežne áno na všetkých PHP verziách
   vo Websupport hostingu).
6. Over, že appka beží cez **HTTPS** (Websupport ponúka zdarma Let's Encrypt certifikát) —
   formulár aj administrácia obsahujú citlivé údaje, mali by ísť len cez zabezpečené spojenie.

## 4. Vytvorenie podujatia

1. Choď na `https://tvojadomena.sk/admin/` a prihlás sa.
2. Vyplň **Názov podujatia** (napr. „Beh popri Hrone 2026").
3. Voliteľne uprav **URL adresu (slug)** — ak necháš prázdne, vygeneruje sa automaticky
   z názvu. Podujatie bude dostupné na `https://tvojadomena.sk/{slug}`.
4. Vlož **URL alebo ID Google Sheetu**, do ktorého má toto podujatie zapisovať (môžeš si
   vopred vytvoriť/skopírovať prázdny Sheet pre každé podujatie — hlavičky stĺpcov si
   appka vytvorí sama pri prvom zápise).
5. Klikni **Vytvoriť podujatie**.

V zozname podujatí potom nájdeš odkaz na formulár (`/{slug}`), na QR stránku (`/qr/{slug}`)
aj priamy odkaz na príslušný Google Sheet. Podujatie vieš kedykoľvek upraviť (názov,
cieľový Sheet) alebo zmazať — zmazanie odstráni len záznam v administrácii, dáta
v Google Sheete zostanú zachované.

## 5. QR kód pre podujatie

Pre podujatie s adresou `/podujatie-hron` appka automaticky vygeneruje tlačiteľnú
stránku s QR kódom na:

```
https://tvojadomena.sk/qr/podujatie-hron
```

QR kód vedie na `https://tvojadomena.sk/podujatie-hron` — formulár pre dané podujatie.
Stránku si vytlač a vylep na mieste prezentácie.

## 6. Pred ostrým nasadením — DOPLŇ

V [`templates/form.php`](templates/form.php) sú dve miesta označené `TODO pre organizátora`:

- text súhlasu so spracovaním osobných údajov (GDPR),
- text podmienok podujatia.

Predvolený text je len zástupný (placeholder) — nahraď ho skutočným znením, ideálne
po konzultácii s organizátorom podujatia / právnikom.

## 7. Úprava vzhľadu (SCSS)

Zdrojové štýly sú v `assets/scss/`. Do appky sa načítava skompilovaný
`assets/css/style.css`, ktorý treba po úprave `.scss` súborov prekompilovať:

```bash
npm install -g sass
sass assets/scss/style.scss assets/css/style.css --style=compressed --no-source-map
```

Websupport shared hosting nemá Node.js pre bežné PHP hostingové plány — SCSS sa preto
kompiluje **lokálne** pred nahratím na server, appka na hostingu používa už hotový
`assets/css/style.css`.

## Stĺpce v Google Sheete (hárok pomenovaný podľa slugu podujatia)

| Stĺpec | Popis |
|---|---|
| Čas odoslania | časová pečiatka zápisu (generuje Apps Script) |
| Podujatie | názov podujatia priradený v administrácii |
| Priezvisko | |
| Meno | |
| Pohlavie | M / Ž |
| Dátum narodenia | DD.MM.RRRR ako zadal bežec |
| Rok narodenia | automaticky vypočítané z dátumu narodenia |
| Klub | nepovinné |
| Obec | nepovinné |
| Trať | vyplní si bežec sám (voľný text, napr. „10 km") |
| ID záznamu (dedup) | interné UUID, slúži na ochranu pred duplicitným zápisom pri opakovanom odoslaní (napr. pri výpadku internetu) — môžeš stĺpec skryť |

## Bezpečnostné poznámky

- `config.php` a priečinok `data/` (register podujatí) sú cez `.htaccess` chránené
  pred priamym HTTP prístupom.
- `/admin` je chránené prihlásením (meno + heslo, hashované bcryptom), CSRF tokenom
  na formulároch a session cookie s `HttpOnly`/`SameSite=Lax` (na HTTPS aj `Secure`).
- Všetky textové polia sa pred zápisom do Sheetu čistia proti tzv. formula injection
  (bunky začínajúce na `=`, `+`, `-`, `@` sa escapujú) — na strane PHP aj Apps Scriptu.
- `SHARED_SECRET` je jednoduchá ochrana Apps Script Web App URL pred zápismi mimo tejto
  appky — nie je to silné zabezpečenie (Apps Script Web App s prístupom „Ktokoľvek" je
  vždy verejne dostupný endpoint), ale zabráni náhodným/automatizovaným zápisom.
- `spreadsheet_id` sa nikdy neposiela z prehliadača bežca — klient pozná len `slug`
  podujatia, server si `spreadsheet_id` dohľadá sám z `data/events.json`. Zabraňuje to
  tomu, aby niekto z formulára mohol zapisovať do cudzieho/iného Sheetu.
