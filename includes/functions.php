<?php
/**
 * Pomocné funkcie.
 */

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * URL statického súboru s cache-busting parametrom podľa času poslednej úpravy,
 * aby prehliadač po nasadení zmien nezobrazoval starú (vycachovanú) verziu.
 */
function asset_url(string $path): string
{
    $fullPath = __DIR__ . '/../' . ltrim($path, '/');
    $version = file_exists($fullPath) ? filemtime($fullPath) : time();
    return '/' . ltrim($path, '/') . '?v=' . $version;
}

/**
 * Overí formát dátumu narodenia DD.MM.YYYY vrátane rozumného rozsahu.
 */
function is_valid_birthdate(string $value): bool
{
    if (!preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $value, $m)) {
        return false;
    }
    $day = (int) $m[1];
    $month = (int) $m[2];
    $year = (int) $m[3];
    if ($year < 1900 || $year > (int) date('Y')) {
        return false;
    }
    return checkdate($month, $day, $year);
}

function extract_birth_year(string $birthdate): string
{
    if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $birthdate, $m)) {
        return $m[3];
    }
    return '';
}

/**
 * Ochrana pred formula injection v Google Sheets (bunky začínajúce na = + - @).
 */
function sanitize_field(?string $value): string
{
    $value = trim((string) $value);
    $value = strip_tags($value);
    if ($value !== '' && preg_match('/^[=+\-@]/', $value)) {
        $value = "'" . $value;
    }
    return $value;
}

/**
 * Odošle JSON dáta na Google Apps Script Web App cez cURL.
 * Vracia pole ['success' => bool, 'data' => mixed, 'error' => string|null].
 */
function send_to_apps_script(array $payload): array
{
    if (!function_exists('curl_init')) {
        return ['success' => false, 'data' => null, 'error' => 'Server nemá dostupné rozšírenie cURL.'];
    }

    $ch = curl_init(APPS_SCRIPT_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => APPS_SCRIPT_TIMEOUT,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($response === false) {
        return ['success' => false, 'data' => null, 'error' => 'Spojenie s Google Sheets zlyhalo: ' . $curlError];
    }

    $decoded = json_decode($response, true);
    if ($httpCode >= 200 && $httpCode < 300 && is_array($decoded) && !empty($decoded['success'])) {
        return ['success' => true, 'data' => $decoded, 'error' => null];
    }

    $errorMessage = is_array($decoded) && !empty($decoded['error'])
        ? $decoded['error']
        : 'Neočakávaná odpoveď zo servera (HTTP ' . $httpCode . ').';

    return ['success' => false, 'data' => $decoded, 'error' => $errorMessage];
}
