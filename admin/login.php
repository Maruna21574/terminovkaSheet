<?php
declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/auth.php';

admin_start_session();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = (string) ($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $remember = !empty($_POST['remember']);
    if (admin_login($username, $password, $remember)) {
        header('Location: /admin/');
        exit;
    }
    $error = 'Nesprávne meno alebo heslo.';
}

if (admin_is_logged_in()) {
    header('Location: /admin/');
    exit;
}
?>
<!doctype html>
<html lang="sk">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Prihlásenie — <?= h(SITE_NAME) ?></title>
<link rel="stylesheet" href="<?= h(asset_url('assets/css/style.css')) ?>">
</head>
<body>
<div class="wrap wrap--narrow">
    <div class="card">
        <h1 class="card__title">Administrácia</h1>
        <?php if ($error !== ''): ?>
            <div class="form-message is-error"><?= h($error) ?></div>
        <?php endif; ?>
        <form method="post" novalidate>
            <div class="field">
                <label for="username">Meno</label>
                <input type="text" id="username" name="username" autocomplete="username" required autofocus>
            </div>
            <div class="field">
                <label for="password">Heslo</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required>
            </div>
            <div class="field field--checkbox">
                <label>
                    <input type="checkbox" id="remember" name="remember" value="1">
                    <span>Zapamätať prihlásenie na tomto zariadení (30 dní)</span>
                </label>
            </div>
            <button type="submit" class="btn btn--primary btn--block">Prihlásiť sa</button>
        </form>
    </div>
</div>
</body>
</html>
