<?php
/**
 * Simple session-based authentication for the Agriculture Information System.
 *
 * Demo users are defined below. In production, validate against a `users` table
 * in Supabase with password_hash()/password_verify().
 *
 * Roles: administrator, staff
 */

session_start();

function getUsers(): array
{
    static $users = null;
    if ($users === null) {
        $users = [
            [
                'id' => 1,
                'email' => 'admin@example.com',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'role' => 'administrator',
                'name' => 'System Administrator',
            ],
            [
                'id' => 2,
                'email' => 'staff@example.com',
                'password' => password_hash('staff123', PASSWORD_DEFAULT),
                'role' => 'staff',
                'name' => 'Staff User',
            ],
        ];
    }
    return $users;
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function currentUser(): ?array
{
    return isLoggedIn()
        ? [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'] ?? '',
            'role' => $_SESSION['user_role'] ?? '',
            'name' => $_SESSION['user_name'] ?? '',
        ]
        : null;
}

function isAdmin(): bool
{
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'administrator';
}

function login(string $email, string $password): bool
{
    foreach (getUsers() as $u) {
        if ($u['email'] === $email && password_verify($password, $u['password'])) {
            $_SESSION['user_id'] = $u['id'];
            $_SESSION['user_email'] = $u['email'];
            $_SESSION['user_role'] = $u['role'];
            $_SESSION['user_name'] = $u['name'];
            $_SESSION['logged_in_at'] = time();
            return true;
        }
    }
    return false;
}

function logout(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly'],
            );
        }
        @session_destroy();
    } else {
        $_SESSION = [];
    }
}

function requireAuth(): void
{
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: /login.php');
        exit();
    }
}

function requireAdmin(): void
{
    requireAuth();
    if (!isAdmin()) {
        $_SESSION['error'] = 'Administrator access required.';
        header('Location: index.php');
        exit();
    }
}

function flash(string $key, string $message): void
{
    $_SESSION['flash_' . $key] = $message;
}

function getFlash(string $key): ?string
{
    $val = $_SESSION['flash_' . $key] ?? null;
    unset($_SESSION['flash_' . $key]);
    return $val;
}
