<?php
declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/events.php';
require __DIR__ . '/../includes/auth.php';

admin_require_login();

$notice = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Neplatný formulár, skúste to znova.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        $events = load_events();

        if ($action === 'create') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $slugInput = trim((string) ($_POST['slug'] ?? ''));
            $sheetInput = trim((string) ($_POST['sheet'] ?? ''));
            $slug = slugify($slugInput !== '' ? $slugInput : $name);
            $spreadsheetId = extract_spreadsheet_id($sheetInput);

            if ($name === '') {
                $error = 'Zadajte názov podujatia.';
            } elseif ($slug === '' || !is_valid_slug_format($slug)) {
                $error = 'Neplatná URL adresa (slug). Použite len malé písmená, čísla a pomlčky.';
            } elseif (isset($events[$slug])) {
                $error = 'Podujatie s touto URL adresou už existuje.';
            } elseif ($spreadsheetId === '') {
                $error = 'Zadajte platnú URL alebo ID Google Sheetu.';
            } else {
                $events[$slug] = [
                    'name' => $name,
                    'spreadsheet_id' => $spreadsheetId,
                    'created_at' => date('c'),
                ];
                save_events($events);
                $notice = 'Podujatie „' . $name . '" bolo vytvorené na adrese /' . $slug;
            }
        } elseif ($action === 'update') {
            $slug = (string) ($_POST['slug'] ?? '');
            $name = trim((string) ($_POST['name'] ?? ''));
            $sheetInput = trim((string) ($_POST['sheet'] ?? ''));
            $spreadsheetId = extract_spreadsheet_id($sheetInput);

            if (!isset($events[$slug])) {
                $error = 'Podujatie sa nenašlo.';
            } elseif ($name === '') {
                $error = 'Zadajte názov podujatia.';
            } elseif ($spreadsheetId === '') {
                $error = 'Zadajte platnú URL alebo ID Google Sheetu.';
            } else {
                $events[$slug]['name'] = $name;
                $events[$slug]['spreadsheet_id'] = $spreadsheetId;
                save_events($events);
                $notice = 'Podujatie „' . $name . '" bolo upravené.';
            }
        } elseif ($action === 'delete') {
            $slug = (string) ($_POST['slug'] ?? '');
            if (isset($events[$slug])) {
                $deletedName = $events[$slug]['name'];
                unset($events[$slug]);
                save_events($events);
                $notice = 'Podujatie „' . $deletedName . '" bolo zmazané.';
            }
        }
    }
}

$events = load_events();
uasort($events, function ($a, $b) {
    return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
});

$editSlug = (string) ($_GET['edit'] ?? '');
$editEvent = ($editSlug !== '' && isset($events[$editSlug])) ? $events[$editSlug] : null;
?>
<!doctype html>
<html lang="sk">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Administrácia — <?= h(SITE_NAME) ?></title>
<link rel="stylesheet" href="<?= h(asset_url('assets/css/style.css')) ?>">
</head>
<body>
<header class="site-header">
    <div class="site-header__inner admin-header-bar">
        <a href="/admin/" class="admin-header-bar__logo">
            <img src="/assets/img/logo-full-white.svg" alt="Terminovka" class="site-header__logo-img">
            <span>admin</span>
        </a>
        <a href="/admin/logout.php" class="admin-logout-link">Odhlásiť sa</a>
    </div>
</header>
<main class="site-main">
<div class="wrap wrap--wide">

    <?php if ($notice !== ''): ?>
        <div class="form-message is-success"><?= h($notice) ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="form-message is-error"><?= h($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <h1 class="card__title"><?= $editEvent ? 'Upraviť podujatie' : 'Nové podujatie' ?></h1>
        <?php if ($editEvent): ?>
            <form method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="slug" value="<?= h($editSlug) ?>">
                <div class="field">
                    <label>URL adresa</label>
                    <input type="text" value="/<?= h($editSlug) ?>" disabled>
                </div>
                <div class="field">
                    <label for="name">Názov podujatia</label>
                    <input type="text" id="name" name="name" value="<?= h($editEvent['name']) ?>" required>
                </div>
                <div class="field">
                    <label for="sheet">Google Sheet (URL alebo ID)</label>
                    <input type="text" id="sheet" name="sheet" value="<?= h($editEvent['spreadsheet_id']) ?>" required>
                </div>
                <button type="submit" class="btn btn--primary">Uložiť zmeny</button>
                <a href="/admin/" class="btn btn--secondary">Zrušiť</a>
            </form>
        <?php else: ?>
            <form method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="create">
                <div class="field">
                    <label for="name">Názov podujatia</label>
                    <input type="text" id="name" name="name" placeholder="napr. Beh popri Hrone 2026" required>
                </div>
                <div class="field">
                    <label for="slug">URL adresa (slug)</label>
                    <input type="text" id="slug" name="slug" placeholder="napr. podujatie-hron">
                    <small class="field__hint">Nechajte prázdne a vygeneruje sa automaticky z názvu. Bude dostupné na adrese vašadomena.sk/<em>slug</em></small>
                </div>
                <div class="field">
                    <label for="sheet">Google Sheet (URL alebo ID)</label>
                    <input type="text" id="sheet" name="sheet" placeholder="https://docs.google.com/spreadsheets/d/..." required>
                    <small class="field__hint">Google účet, pod ktorým je nasadený Apps Script, musí mať k tomuto Sheetu editovacie práva.</small>
                </div>
                <button type="submit" class="btn btn--primary">Vytvoriť podujatie</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="card">
        <h1 class="card__title">Podujatia</h1>
        <?php if (empty($events)): ?>
            <p class="card__subtitle">Zatiaľ žiadne podujatia.</p>
        <?php else: ?>
            <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Názov</th>
                        <th>URL</th>
                        <th>Sheet</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($events as $slug => $ev): ?>
                    <tr>
                        <td><?= h($ev['name']) ?></td>
                        <td>
                            <a href="/<?= h($slug) ?>" target="_blank" rel="noopener">/<?= h($slug) ?></a>
                            &middot;
                            <a href="/qr/<?= h($slug) ?>" target="_blank" rel="noopener">QR</a>
                        </td>
                        <td>
                            <a href="https://docs.google.com/spreadsheets/d/<?= h($ev['spreadsheet_id']) ?>/edit" target="_blank" rel="noopener">otvoriť sheet</a>
                        </td>
                        <td class="admin-table__actions">
                            <a href="/admin/?edit=<?= h($slug) ?>" class="link-btn">upraviť</a>
                            <form method="post" onsubmit="return confirm('Naozaj zmazať podujatie \'<?= h(addslashes($ev['name'])) ?>\'? Dáta v Google Sheete zostanú zachované.');">
                                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="slug" value="<?= h($slug) ?>">
                                <button type="submit" class="link-btn link-btn--danger">zmazať</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </div>

</div>
</main>
</body>
</html>
