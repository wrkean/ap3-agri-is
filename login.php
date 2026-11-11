<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } elseif (login($email, $password)) {
        $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
        unset($_SESSION['redirect_after_login']);
        $redirect = strpos($redirect, '/') === 0 ? $redirect : '/' . ltrim($redirect, '/');
        header('Location: ' . $redirect);
        exit();
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Agriculture Information System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-green-50 to-blue-50">
    <div class="w-full max-w-md bg-white rounded-lg border border-gray-200 p-8">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                <i class="fa-solid fa-leaf text-green-600 text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Agriculture Information System</h1>
            <p class="text-sm text-gray-500 mt-1">Cebu Technological University — Cebu City</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-center gap-2 text-sm">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <div class="relative">
                    <i class="fa-solid fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="email" id="email" name="email" required
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition"
                           placeholder="you@example.com">
                </div>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <div class="relative">
                    <i class="fa-solid fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="password" id="password" name="password" required
                           class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition"
                           placeholder="••••••••">
                </div>
            </div>

            <button type="submit"
                    class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2.5 rounded-lg transition flex items-center justify-center gap-2">
                <i class="fa-solid fa-right-to-bracket"></i> Sign In
            </button>
        </form>

        <div class="mt-6 p-3 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-800">
            <strong class="block mb-1">Demo credentials:</strong>
            Admin: <code>admin@example.com</code> / <code>admin123</code><br>
            Staff: <code>staff@example.com</code> / <code>staff123</code>
        </div>
    </div>
</body>
</html>