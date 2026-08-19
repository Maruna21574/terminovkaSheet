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

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? '';
$formUrl = $scheme . '://' . $host . '/' . $slug;
?>
<!doctype html>
<html lang="sk">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>QR kód — <?= h($event['name']) ?></title>
<link rel="stylesheet" href="<?= h(asset_url('assets/css/style.css')) ?>">
<style>
  .qr-page { text-align: center; padding: 48px 16px; }
  .qr-page canvas, .qr-page img { margin: 24px auto; }
  .qr-page .url { font-size: 1.1rem; word-break: break-all; color: #757b8c; }

  /* Stránka sa tlačí a lepí na mieste podujatia - na tlač prepneme na biele
     pozadie s čiernym textom, aby sa zbytočne nemíňal čierny toner. */
  @media print {
    .no-print { display: none; }
    body { background: #fff !important; }
    .qr-page, .qr-page h1, .qr-page p, .qr-page .url { color: #000 !important; }
  }
</style>
</head>
<body>
<div class="qr-page">
    <h1><?= h($event['name']) ?></h1>
    <p>Naskenujte QR kód a vyplňte prezenčnú registráciu na svojom telefóne.</p>
    <div id="qrcode"></div>
    <p class="url"><?= h($formUrl) ?></p>
    <button class="btn btn--primary no-print" onclick="window.print()">Vytlačiť</button>
</div>
<script src="/assets/js/qrcode.min.js"></script>
<script>
    new QRCode(document.getElementById('qrcode'), {
        text: <?= json_encode($formUrl) ?>,
        width: 320,
        height: 320,
        correctLevel: QRCode.CorrectLevel.M
    });
</script>
</body>
</html>
