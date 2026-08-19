<?php
/**
 * Jednoduchá session autentifikácia pre /admin.
 */

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

function admin_is_logged_in(): bool
{
    admin_start_session();
    return !empty($_SESSION['admin_logged_in']);
}

function admin_require_login(): void
{
    if (!admin_is_logged_in()) {
        header('Location: /admin/login.php');
        exit;
    }
}

function admin_login(string $username, string $password): bool
{
    admin_start_session();
    if ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD_HASH)) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        return true;
    }
    return false;
}

function admin_logout(): void
{
    admin_start_session();
    $_SESSION = [];
    session_destroy();
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
