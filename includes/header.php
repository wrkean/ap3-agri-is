<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/Supabase.php';
$sb = new Supabase();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Agriculture Information System'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.min.css" rel="stylesheet">
    <style>
        .fade-in { animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }
        .status-badge { display: inline-flex; align-items: center; gap: 1px; padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 600; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">

<?php
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

$navClass = function(string $key) use ($activeNav): string {
    return $activeNav === $key
        ? 'px-3 py-2 text-sm font-medium rounded-md bg-blue-50 text-blue-600'
        : 'px-3 py-2 text-sm font-medium rounded-md text-gray-700 hover:bg-gray-100';
};
?>
<?php if (isLoggedIn()): ?>
    <nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-leaf text-green-600 text-xl"></i>
                    <span class="font-bold text-xl tracking-tight text-gray-800">AgriInfo<span class="text-blue-600">System</span></span>
                </div>
                <div class="hidden md:flex items-center gap-6">
                    <a href="/index.php" class="<?php echo $navClass('dashboard'); ?>">Dashboard</a>
                    <a href="/farmers/index.php" class="<?php echo $navClass('farmers'); ?>">Farmers</a>
                    <a href="/farms/index.php" class="<?php echo $navClass('farms'); ?>">Farms</a>
                    <a href="/crops/index.php" class="<?php echo $navClass('crops'); ?>">Crops</a>
                    <a href="/planting/index.php" class="<?php echo $navClass('planting'); ?>">Planting</a>
                    <a href="/harvest/index.php" class="<?php echo $navClass('harvest'); ?>">Harvest</a>
                    <?php if (isAdmin()): ?>
                        <a href="/reports/index.php" class="<?php echo $navClass('reports'); ?>">Reports</a>
                    <?php endif; ?>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-600 hidden md:block">
                        <span class="bg-gray-100 px-2 py-0.5 rounded text-xs font-semibold uppercase"><?php echo strtoupper(currentUser()['role']); ?></span>
                    </span>
                    <a href="/logout.php" class="px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-md"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>
<?php endif; ?>

<?php
foreach (['success','error','warning','info'] as $type) {
    $msg = getFlash($type);
    if ($msg) {
        $cls = $type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : ($type === 'error' ? 'bg-red-50 border-red-200 text-red-800' : ($type === 'warning' ? 'bg-amber-50 border-amber-200 text-amber-800' : ''));
        $icon = $type === 'success' ? 'fa-check-circle' : ($type === 'error' ? 'fa-circle-exclamation' : ($type === 'warning' ? 'fa-triangle-exclamation' : 'fa-info-circle'));
?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-4 fade-in <?php echo $cls; ?> border rounded-lg px-4 py-3 flex items-center gap-2" role="alert">
            <i class="fa-solid <?php echo $icon; ?>"></i>
            <?php echo htmlspecialchars($msg); ?>
        </div>
<?php
    }
}
?>

<?php if (isLoggedIn()): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
<?php endif; ?>
