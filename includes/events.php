<?php
/**
 * Správa registra podujatí (data/events.json).
 * Každé podujatie má vlastný URL slug a vlastný cieľový Google Sheet.
 */

define('EVENTS_FILE', __DIR__ . '/../data/events.json');

function load_events(): array
{
    if (!file_exists(EVENTS_FILE)) {
        return [];
    }
    $fh = fopen(EVENTS_FILE, 'r');
    if (!$fh) {
        return [];
    }
    flock($fh, LOCK_SH);
    $raw = stream_get_contents($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    $data = json_decode((string) $raw, true);
    return is_array($data) ? $data : [];
}

function save_events(array $events): bool
{
    $dir = dirname(EVENTS_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $fh = fopen(EVENTS_FILE, 'c+');
    if (!$fh) {
        return false;
    }
    flock($fh, LOCK_EX);
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($events, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return true;
}

function get_event(string $slug): ?array
{
    $events = load_events();
    return $events[$slug] ?? null;
}

function is_valid_slug_format(string $slug): bool
{
    return (bool) preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug) && strlen($slug) <= 80;
}

/**
 * Prevedie ľubovoľný text (aj so slovenskou diakritikou) na URL-bezpečný slug.
 */
function slugify(string $text): string
{
    $text = trim($text);
    $transliteration = [
        'á'=>'a','ä'=>'a','č'=>'c','ď'=>'d','é'=>'e','í'=>'i','ľ'=>'l','ĺ'=>'l',
        'ň'=>'n','ó'=>'o','ô'=>'o','ŕ'=>'r','š'=>'s','ť'=>'t','ú'=>'u','ý'=>'y','ž'=>'z',
        'Á'=>'a','Ä'=>'a','Č'=>'c','Ď'=>'d','É'=>'e','Í'=>'i','Ľ'=>'l','Ĺ'=>'l',
        'Ň'=>'n','Ó'=>'o','Ô'=>'o','Ŕ'=>'r','Š'=>'s','Ť'=>'t','Ú'=>'u','Ý'=>'y','Ž'=>'z',
    ];
    $text = strtr($text, $transliteration);
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim((string) $text, '-');
    return (string) $text;
}

/**
 * Vytiahne ID Google Sheetu zo zdieľanej URL alebo vráti hodnotu, ak je to už len ID.
 */
function extract_spreadsheet_id(string $urlOrId): string
{
    $urlOrId = trim($urlOrId);
    if (preg_match('#/spreadsheets/d/([a-zA-Z0-9_-]+)#', $urlOrId, $m)) {
        return $m[1];
    }
    if (preg_match('/^[a-zA-Z0-9_-]{20,}$/', $urlOrId)) {
        return $urlOrId;
    }
    return '';
}
