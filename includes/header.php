<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/Database.php';
$sb = new Database();

// Determine active nav item based on current request path
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$activeNav = 'dashboard';
if (strpos($currentPath, '/farmers/') !== false) $activeNav = 'farmers';
elseif (strpos($currentPath, '/farms/') !== false) $activeNav = 'farms';
elseif (strpos($currentPath, '/crops/') !== false) $activeNav = 'crops';
elseif (strpos($currentPath, '/planting/') !== false) $activeNav = 'planting';
elseif (strpos($currentPath, '/harvest/') !== false) $activeNav = 'harvest';
elseif (strpos($currentPath, '/reports/') !== false) $activeNav = 'reports';
elseif ($currentPath === '/' || strpos($currentPath, 'index.php') !== false) $activeNav = 'dashboard';

$navClass = function (string $key) use ($activeNav): string {
    $base = 'px-3 py-2 text-sm font-medium rounded-md transition-colors';
    return $activeNav === $key
        ? $base . ' bg-blue-50 text-blue-700'
        : $base . ' text-gray-600 hover:bg-gray-100 hover:text-gray-900';
};

// Flash message config
$flashConfig = [
    'success' => ['bg-green-50 border-green-200 text-green-800', 'fa-check-circle text-green-600'],
    'error'   => ['bg-red-50 border-red-200 text-red-800', 'fa-circle-exclamation text-red-600'],
    'warning' => ['bg-amber-50 border-amber-200 text-amber-800', 'fa-triangle-exclamation text-amber-600'],
    'info'    => ['bg-blue-50 border-blue-200 text-blue-800', 'fa-info-circle text-blue-600'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Agriculture Information System'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .fade-in { animation: fadeIn 0.2s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }
        .status-badge { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 600; }
        /* Consistent focus ring for all interactive elements */
        a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible, textarea:focus-visible {
            outline: none;
            box-shadow: 0 0 0 2px #fff, 0 0 0 4px #3b82f6;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 antialiased">

<?php if (isLoggedIn()): ?>
    <header class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="/index.php" class="flex items-center gap-2 text-gray-900">
                    <i class="fa-solid fa-leaf text-green-600 text-lg"></i>
                    <span class="font-semibold text-lg tracking-tight">AgriInfo<span class="text-blue-600">System</span></span>
                </a>
                <nav class="hidden md:flex items-center gap-1" aria-label="Main navigation">
                    <a href="/index.php" class="<?php echo $navClass('dashboard'); ?>">Dashboard</a>
                    <a href="/farmers/index.php" class="<?php echo $navClass('farmers'); ?>">Farmers</a>
                    <a href="/farms/index.php" class="<?php echo $navClass('farms'); ?>">Farms</a>
                    <a href="/crops/index.php" class="<?php echo $navClass('crops'); ?>">Crops</a>
                    <a href="/planting/index.php" class="<?php echo $navClass('planting'); ?>">Planting</a>
                    <a href="/harvest/index.php" class="<?php echo $navClass('harvest'); ?>">Harvest</a>
                    <?php if (isAdmin()): ?>
                        <a href="/reports/index.php" class="<?php echo $navClass('reports'); ?>">Reports</a>
                    <?php endif; ?>
                </nav>
                <div class="flex items-center gap-3">
                    <span class="hidden md:inline-flex items-center gap-2 text-sm text-gray-600">
                        <span class="w-7 h-7 rounded-full bg-gray-100 flex items-center justify-center text-xs font-semibold text-gray-700">
                            <?php echo strtoupper(substr(currentUser()['name'] ?? 'U', 0, 1)); ?>
                        </span>
                        <span class="hidden lg:inline"><?php echo htmlspecialchars(currentUser()['name'] ?? ''); ?></span>
                        <span class="text-xs font-medium uppercase text-gray-400"><?php echo htmlspecialchars(currentUser()['role'] ?? ''); ?></span>
                    </span>
                    <a href="/logout.php" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span class="hidden sm:inline">Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </header>
<?php endif; ?>

<?php
foreach (['success', 'error', 'warning', 'info'] as $type) {
    $msg = getFlash($type);
    if ($msg) {
        [$cls, $iconCls] = $flashConfig[$type];
?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 fade-in" role="alert">
            <div class="flex items-start gap-3 px-4 py-3 rounded-lg border <?php echo $cls; ?>">
                <i class="fa-solid <?php echo $iconCls; ?> mt-0.5"></i>
                <p class="text-sm font-medium"><?php echo htmlspecialchars($msg); ?></p>
            </div>
        </div>
<?php
    }
}
?>

<?php if (isLoggedIn()): ?>
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
<?php else: ?>
    <div class="min-h-screen flex items-center justify-center bg-gray-100 p-4">
<?php endif; ?>