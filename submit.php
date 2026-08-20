<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/events.php';
require __DIR__ . '/includes/ratelimit.php';

header('Content-Type: application/json; charset=utf-8');

function respond(int $httpCode, array $body): void
{
    http_response_code($httpCode);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'error' => 'Metóda nie je povolená.']);
}

if (!rate_limit_allow(client_ip())) {
    respond(429, ['success' => false, 'error' => 'Príliš veľa požiadaviek, skúste to o chvíľu.']);
}

$raw = file_get_contents('php://input');
$input = json_decode((string) $raw, true);

if (!is_array($input)) {
    respond(400, ['success' => false, 'error' => 'Neplatné dáta.']);
}

// Honeypot: skryté pole, ktoré skutočný používateľ nikdy nevyplní. Bot, ktorý
// automaticky vypĺňa všetky polia formulára, sa tu chytí. Odpovieme mu falošným
// úspechom (aby nespozoroval blokovanie a neskúšal iné spôsoby), ale dáta nikam
// nezapíšeme.
if (trim((string) ($input['website'] ?? '')) !== '') {
    respond(200, ['success' => true]);
}

$slug = (string) ($input['event_slug'] ?? '');
$meno = (string) ($input['meno'] ?? '');
$priezvisko = (string) ($input['priezvisko'] ?? '');
$pohlavie = (string) ($input['pohlavie'] ?? '');
$narodenie = (string) ($input['narodenie'] ?? '');
$klub = (string) ($input['klub'] ?? '');
$obec = (string) ($input['obec'] ?? '');
$trat = (string) ($input['trat'] ?? '');
$submissionId = (string) ($input['submission_id'] ?? '');
$suhlasUdaje = !empty($input['suhlas_udaje']);
$suhlasPodmienky = !empty($input['suhlas_podmienky']);

$errors = [];

$event = ($slug !== '' && is_valid_slug_format($slug)) ? get_event($slug) : null;
if ($event === null) {
    $errors[] = 'Neplatné podujatie.';
}
if (trim($meno) === '') {
    $errors[] = 'Meno je povinné.';
}
if (trim($priezvisko) === '') {
    $errors[] = 'Priezvisko je povinné.';
}
if (!in_array($pohlavie, ['muz', 'zena'], true)) {
    $errors[] = 'Pohlavie musí byť muž alebo žena.';
}
if (!is_valid_birthdate($narodenie)) {
    $errors[] = 'Dátum narodenia musí byť v tvare DD.MM.RRRR.';
}
if (trim($trat) === '') {
    $errors[] = 'Trať je povinná.';
}
if (!$suhlasUdaje) {
    $errors[] = 'Musíte súhlasiť so spracovaním osobných údajov.';
}
if (!$suhlasPodmienky) {
    $errors[] = 'Musíte súhlasiť s podmienkami podujatia.';
}
if ($submissionId === '' || !preg_match('/^[A-Za-z0-9-]{8,64}$/', $submissionId)) {
    $errors[] = 'Neplatný identifikátor záznamu.';
}

if (!empty($errors)) {
    respond(422, ['success' => false, 'error' => implode(' ', $errors)]);
}

$payload = [
    'secret' => SHARED_SECRET,
    'spreadsheet_id' => $event['spreadsheet_id'],
    'event_slug' => sanitize_field($slug),
    'event_name' => sanitize_field((string) $event['name']),
    'meno' => sanitize_field($meno),
    'priezvisko' => sanitize_field($priezvisko),
    'pohlavie' => $pohlavie === 'muz' ? 'M' : 'Ž',
    'narodenie' => sanitize_field($narodenie),
    'rok_narodenia' => extract_birth_year($narodenie),
    'klub' => sanitize_field($klub),
    'obec' => sanitize_field($obec),
    'trat' => sanitize_field($trat),
    'submission_id' => $submissionId,
];

$result = send_to_apps_script($payload);

if ($result['success']) {
    respond(200, ['success' => true, 'duplicate' => !empty($result['data']['duplicate'])]);
}

respond(502, ['success' => false, 'error' => $result['error']]);
