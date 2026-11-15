<?php
/**
 * Simple session-based authentication for the Agriculture Information System.
 *
 * Users are validated against the `users` table in Supabase
 * (password_hash()/password_verify()). Falls back to demo accounts if the
 * table is unavailable.
 *
 * Roles: administrator, staff
 */

session_start();

function getUsers(): array
{
    static $users = null;
    if ($users === null) {
        $users = [];
        // Validate against the `users` table in Supabase when available.
        try {
            if (!class_exists('Database')) {
                require_once __DIR__ . '/Database.php';
            }
            if (class_exists('Database')) {
                $db = new Database();
                $rows = $db->select('users', '*');
                if (is_array($rows)) {
                    foreach ($rows as $r) {
                        $users[] = [
                            'id' => $r['user_id'] ?? ($r['id'] ?? null),
                            'email' => $r['email'] ?? '',
                            'password' => $r['password_hash'] ?? ($r['password'] ?? ''),
                            'role' => $r['role'] ?? '',
                            'name' => $r['full_name'] ?? ($r['name'] ?? ''),
                        ];
                    }
                }
            }
        } catch (Throwable $e) {
            $users = [];
        }
        // Fallback demo accounts if the database is unavailable or empty.
        if (empty($users)) {
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
        header('Location: /index.php');
        exit();
    }
}

function flash(string $key, string $message): void
{
    $_SESSION['flash_' . $key] = $message;
}

/**
 * Generate (or reuse) a CSRF token for the current session.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a CSRF token from POST data. Returns true on success.
 */
function validateCsrf(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Store form input in session so it can be repopulated after a validation error.
 */
function storeFormInput(array $data): void
{
    $_SESSION['form_input'] = $data;
}

/**
 * Retrieve and clear stored form input (for repopulation after validation error).
 */
function getFormInput(): array
{
    $data = $_SESSION['form_input'] ?? [];
    unset($_SESSION['form_input']);
    return is_array($data) ? $data : [];
}

function getFlash(string $key): ?string
{
    $val = $_SESSION['flash_' . $key] ?? null;
    unset($_SESSION['flash_' . $key]);
    return $val;
}
