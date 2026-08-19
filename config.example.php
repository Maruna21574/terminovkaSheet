<?php
/**
 * Konfigurácia aplikácie.
 * Skopíruj tento súbor ako config.php a uprav hodnoty nižšie.
 * config.php sa NEVERZUJE v Gite (obsahuje tajné údaje) - pozri .gitignore.
 */

// Na produkcii nezobrazovať PHP chyby/varovania v odpovedi (mohli by rozbiť JSON
// vracaný z submit.php) — chyby sa namiesto toho logujú na serveri.
ini_set('display_errors', '0');
error_reporting(E_ALL);

// URL nasadenej Google Apps Script Web App (Nasadenie > Nová nasadená verzia > Web App).
// Musí končiť na /exec
define('APPS_SCRIPT_URL', 'https://script.google.com/macros/s/AKfycb.../exec');

// Zdieľané heslo medzi týmto serverom a Apps Scriptom (musí byť rovnaké ako
// hodnota vlastnosti SHARED_SECRET nastavená v Apps Script > Nastavenia projektu > Vlastnosti skriptu).
// Slúži ako jednoduchá ochrana proti tomu, aby niekto posielal dáta priamo na Apps Script URL.
define('SHARED_SECRET', 'zmen-toto-tajne-heslo');

// Názov stránky / organizátora zobrazený v hlavičke.
define('SITE_NAME', 'Terminovka registrácia');

// Timeout (v sekundách) pre spojenie s Apps Script.
define('APPS_SCRIPT_TIMEOUT', 15);

// Prihlasovacie meno do administrácie (/admin) - tu sa vytvárajú podujatia
// a priraďujú im vlastné Google Sheety.
define('ADMIN_USERNAME', 'admin');

// Hash hesla do administrácie (NIKDY sem nedávaj heslo v čitateľnej podobe).
// Vygeneruješ príkazom:
//   php -r "echo password_hash('tvoje-heslo', PASSWORD_DEFAULT);"
define('ADMIN_PASSWORD_HASH', '$2y$12$zmen.toto.na.vlastny.hash.z.prikazu.vyssie');
