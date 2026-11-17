<?php
/**
 * Simple session-based authentication for the Agriculture Information System.
 *
 * Users are validated against the `users` table in Supabase
 * (password_hash()/password_verify()). Fails closed: if the lookup
 * fails or returns nothing, login fails. No fallback accounts.
 *
 * Roles: administrator, staff
 */

session_start();

/**
 * Look up a single user by email via the server-only service key.
 * Returns the normalized user row, or null when not found / on failure.
 */
function getUserByEmail(string $email): ?array
{
    try {
        if (!class_exists('Database')) {
            require_once __DIR__ . '/Database.php';
        }
        if (!class_exists('User')) {
            require_once __DIR__ . '/../models/User.php';
        }
        $db = new Database(true); // service key; server-only users lookup
        $userModel = new User($db);
        $row = $userModel->findByEmail($email);
        if (!is_array($row)) {
            return null;
        }
        return $userModel->normalize($row);
    } catch (Throwable $e) {
        error_log('auth: users lookup failed: ' . $e->getMessage());
        return null;
    }
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
    $u = getUserByEmail(trim($email));
    if ($u === null || empty($u['password']) || !password_verify($password, $u['password'])) {
        return false;
    }
    // Only known roles may log in; anything else is rejected.
    if (!in_array($u['role'], ['administrator', 'staff'], true)) {
        return false;
    }
    // Regenerate the session ID so a fixed session cannot be reused.
    session_regenerate_id(true);
    $_SESSION['user_id'] = $u['id'];
    $_SESSION['user_email'] = $u['email'];
    $_SESSION['user_role'] = $u['role'];
    $_SESSION['user_name'] = $u['name'];
    $_SESSION['logged_in_at'] = time();
    return true;
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
        flash('error', 'Administrator access required.');
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
