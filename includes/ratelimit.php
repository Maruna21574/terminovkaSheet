<?php
/**
 * Jednoduchá ochrana submit.php pred spamom/zneužitím - bez databázy.
 */

define('RATE_LIMIT_FILE', __DIR__ . '/../data/rate_limit.json');

// Benevolentné limity: aj zdieľaný telefón/tablet na prezentácii môže odosielať
// desiatky registrácií rýchlo za sebou, to nechceme blokovať. Cieľom je zastaviť
// len skutočnú záplavu (bot, opakované automatizované volania).
define('RATE_LIMIT_MAX_REQUESTS', 60);
define('RATE_LIMIT_WINDOW_SECONDS', 900); // 15 minút

/**
 * Najlepšia dostupná IP adresa klienta. X-Forwarded-For sa dá sfalšovať,
 * ale keďže ide len o ochranu pred spamom (nie o autentifikáciu), je to
 * dostatočné - v najhoršom prípade sa limit vyhodnotí zhovievavejšie.
 */
function client_ip(): string
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($parts[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Vráti true, ak tento kľúč (zvyčajne IP adresa, prípadne "login:" + IP pre
 * samostatný, prísnejší limit na admin login) ešte môže odoslať ďalšiu
 * požiadavku. Zároveň si túto požiadavku "zaznamená" (počíta sa do limitu).
 */
function rate_limit_allow(string $key, int $maxRequests = RATE_LIMIT_MAX_REQUESTS, int $windowSeconds = RATE_LIMIT_WINDOW_SECONDS): bool
{
    $dir = dirname(RATE_LIMIT_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $fh = fopen(RATE_LIMIT_FILE, 'c+');
    if (!$fh) {
        return true; // radšej nezablokovať legitímne registrácie ako zlyhať naostro
    }

    flock($fh, LOCK_EX);
    $raw = stream_get_contents($fh);
    $data = json_decode((string) $raw, true);
    if (!is_array($data)) {
        $data = [];
    }

    $now = time();
    $cutoff = $now - $windowSeconds;

    foreach ($data as $existingKey => $timestamps) {
        $fresh = array_values(array_filter((array) $timestamps, function ($t) use ($cutoff) {
            return is_int($t) && $t >= $cutoff;
        }));
        if (empty($fresh)) {
            unset($data[$existingKey]);
        } else {
            $data[$existingKey] = $fresh;
        }
    }

    $timestamps = $data[$key] ?? [];
    $allowed = count($timestamps) < $maxRequests;

    if ($allowed) {
        $timestamps[] = $now;
        $data[$key] = $timestamps;
    }

    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($data));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);

    return $allowed;
}
