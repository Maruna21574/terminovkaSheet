<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/events.php';

$slug = isset($_GET['slug']) ? (string) $_GET['slug'] : '';
$event = ($slug !== '' && is_valid_slug_format($slug)) ? get_event($slug) : null;

if ($event === null) {
    http_response_code(404);
    require __DIR__ . '/templates/error.php';
    exit;
}

require __DIR__ . '/templates/form.php';
