<?php
/**
 * Jednoduchá session autentifikácia pre /admin, s voliteľným "Zapamätať prihlásenie".
 */

define('ADMIN_REMEMBER_COOKIE', 'terminovka_admin_remember');
define('ADMIN_REMEMBER_DAYS', 30);

function admin_start_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        session_start();
    }
}

function admin_cookie_options(int $expires): array
{
    return [
        'expires' => $expires,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ];
}

/**
 * Bezstavový "remember me" token (dátum expirácie + HMAC podpis) - nepotrebuje
 * žiadne úložisko na serveri, overuje sa len podpisom pomocou ADMIN_PASSWORD_HASH
 * (tajná hodnota známa iba serveru).
 */
function admin_remember_token_create(): string
{
    $expiry = time() + ADMIN_REMEMBER_DAYS * 86400;
    $signature = hash_hmac('sha256', ADMIN_USERNAME . '|' . $expiry, ADMIN_PASSWORD_HASH);
    return $expiry . '.' . $signature;
}

function admin_remember_token_valid(string $token): bool
{
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2) {
        return false;
    }
    [$expiry, $signature] = $parts;
    if (!ctype_digit($expiry) || (int) $expiry < time()) {
        return false;
    }
    $expected = hash_hmac('sha256', ADMIN_USERNAME . '|' . $expiry, ADMIN_PASSWORD_HASH);
    return hash_equals($expected, $signature);
}

function admin_set_remember_cookie(): void
{
    $expires = time() + ADMIN_REMEMBER_DAYS * 86400;
    setcookie(ADMIN_REMEMBER_COOKIE, admin_remember_token_create(), admin_cookie_options($expires));
}

function admin_clear_remember_cookie(): void
{
    setcookie(ADMIN_REMEMBER_COOKIE, '', admin_cookie_options(time() - 3600));
}

function admin_is_logged_in(): bool
{
    admin_start_session();

    if (!empty($_SESSION['admin_logged_in'])) {
        return true;
    }

    if (!empty($_COOKIE[ADMIN_REMEMBER_COOKIE]) && admin_remember_token_valid((string) $_COOKIE[ADMIN_REMEMBER_COOKIE])) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        return true;
    }

    return false;
}

function admin_require_login(): void
{
    if (!admin_is_logged_in()) {
        header('Location: /admin/login.php');
        exit;
    }
}

function admin_login(string $username, string $password, bool $remember = false): bool
{
    admin_start_session();
    if ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD_HASH)) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        if ($remember) {
            admin_set_remember_cookie();
        }
        return true;
    }
    return false;
}

function admin_logout(): void
{
    admin_start_session();
    $_SESSION = [];
    session_destroy();
    admin_clear_remember_cookie();
}

function csrf_token(): string
{
    admin_start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(?string $token): bool
{
    admin_start_session();
    return !empty($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}
