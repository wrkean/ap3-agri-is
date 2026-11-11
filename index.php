<?php
require_once __DIR__ . '/includes/header.php';
requireAuth();

$sb = new Database();

// Summary counts
$counts = [
    'farmers' => count($sb->select('farmer', 'farmer_id')),
    'farms' => count($sb->select('farm', 'farm_id')),
    'crops' => count($sb->select('crop', 'crop_id')),
    'planting' => count($sb->select('planting', 'planting_id')),
    'harvest' => count($sb->select('harvest', 'harvest_id')),
];

// Recent records
$recentPlanting = $sb->select(
    'planting',
    '*,farm(farm_name),crop(crop_name)',
    [],
    ['limit' => 5, 'order' => 'planting_id.desc'],
);
$recentHarvest = $sb->select(
    'harvest',
    '*,planting(planting_id,farm_id)',
    [],
    ['limit' => 5, 'order' => 'harvest_id.desc'],
);

// Plantings by status for chart
$plantingStatus = $sb->select('planting', 'status,count', [], ['order' => 'status.asc']);

// Total harvested quantity
$harvestRows = $sb->select('harvest', 'quantity_kg');
$totalHarvest = 0;
foreach ($harvestRows as $r) {
    $totalHarvest += (float) ($r['quantity_kg'] ?? 0);
}

$pageTitle = 'Dashboard';
?>
<h1 class="text-2xl font-bold text-gray-800 mb-6 fade-in">Dashboard</h1>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8 fade-in">
    <div class="bg-white p-5 rounded-lg border border-gray-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 uppercase font-semibold">Total Farmers</p>
                <h3 class="text-2xl font-bold text-gray-900 mt-1"><?php echo number_format($counts['farmers']); ?></h3>
            </div>
            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>
    </div>
    <div class="bg-white p-5 rounded-lg border border-gray-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 uppercase font-semibold">Total Farms</p>
                <h3 class="text-2xl font-bold text-gray-900 mt-1"><?php echo number_format($counts['farms']); ?></h3>
            </div>
            <div class="w-10 h-10 rounded-full bg-purple-50 flex items-center justify-center text-purple-600">
                <i class="fa-solid fa-seedling"></i>
            </div>
        </div>
    </div>
    <div class="bg-white p-5 rounded-lg border border-gray-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 uppercase font-semibold">Total Crops</p>
                <h3 class="text-2xl font-bold text-gray-900 mt-1"><?php echo number_format($counts['crops']); ?></h3>
            </div>
            <div class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center text-green-600">
                <i class="fa-solid fa-carrot"></i>
            </div>
        </div>
    </div>
    <div class="bg-white p-5 rounded-lg border border-gray-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 uppercase font-semibold">Planting Records</p>
                <h3 class="text-2xl font-bold text-gray-900 mt-1"><?php echo number_format($counts['planting']); ?></h3>
            </div>
            <div class="w-10 h-10 rounded-full bg-orange-50 flex items-center justify-center text-orange-600">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
        </div>
    </div>
    <div class="bg-white p-5 rounded-lg border border-gray-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 uppercase font-semibold">Harvest Records</p>
                <h3 class="text-2xl font-bold text-gray-900 mt-1"><?php echo number_format($counts['harvest']); ?></h3>
            </div>
            <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-red-600">
                <i class="fa-solid fa-wheat-awn"></i>
            </div>
        </div>
    </div>
</div>

<!-- Total Harvested Quantity + Status Chart -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8 fade-in">
    <div class="bg-white p-6 rounded-lg border border-gray-200">
        <p class="text-sm text-gray-500 uppercase font-semibold mb-2">Total Harvested Quantity</p>
        <h2 class="text-3xl font-bold text-gray-900 mb-1"><?php echo number_format($totalHarvest, 2); ?> kg</h2>
        <p class="text-xs text-gray-500">Estimated value: <span class="font-semibold text-gray-700"><?php echo number_format(
            $totalHarvest * 25,
            2,
        ); ?> PHP</span> (avg ₱25/kg)</p>
    </div>

    <div class="bg-white p-6 rounded-lg border border-gray-200">
        <div class="flex justify-between items-start mb-4">
            <h3 class="font-semibold text-gray-800">Plantings by Status</h3>
        </div>
        <canvas id="statusChart" width="300" height="200"></canvas>
    </div>
</div>

<!-- Recent Records -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8 fade-in">
    <div class="bg-white p-6 rounded-lg border border-gray-200">
        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-calendar-plus text-green-600"></i> Recent Plantings
        </h3>
        <?php if (empty($recentPlanting)): ?>
            <p class="text-gray-500 text-sm">No planting records yet.</p>
        <?php else: ?>
            <div class="overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left border-b border-gray-100">
                            <th class="pb-2 font-medium text-gray-500">Farm</th>
                            <th class="pb-2 font-medium text-gray-500">Crop</th>
                            <th class="pb-2 font-medium text-gray-500">Date</th>
                            <th class="pb-2 font-medium text-gray-500">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentPlanting as $p): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="py-2 text-gray-700"><?php echo htmlspecialchars(
                                    $p['farm']['farm_name'] ?? '—',
                                ); ?></td>
                                <td class="py-2 text-gray-700"><?php echo htmlspecialchars(
                                    $p['crop']['crop_name'] ?? '—',
                                ); ?></td>
                                <td class="py-2 text-gray-700"><?php echo htmlspecialchars(
                                    $p['planting_date'] ?? '',
                                ); ?></td>
                                <td class="py-2">
                                    <span class="status-badge <?php echo $p['status'] === 'Harvested'
                                        ? 'bg-green-100 text-green-700'
                                        : 'bg-blue-100 text-blue-700'; ?>">
                                        <?php echo htmlspecialchars($p['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="bg-white p-6 rounded-lg border border-gray-200">
        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-wheat-awn text-amber-600"></i> Recent Harvests
        </h3>
        <?php if (empty($recentHarvest)): ?>
            <p class="text-gray-500 text-sm">No harvest records yet.</p>
        <?php else: ?>
            <div class="overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left border-b border-gray-100">
                            <th class="pb-2 font-medium text-gray-500">Date</th>
                            <th class="pb-2 font-medium text-gray-500">Quantity</th>
                            <th class="pb-2 font-medium text-gray-500">Price/kg</th>
                            <th class="pb-2 font-medium text-gray-500">Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentHarvest as $h): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="py-2 text-gray-700"><?php echo htmlspecialchars(
                                    $h['harvest_date'] ?? '',
                                ); ?></td>
                                <td class="py-2 text-gray-700 font-medium"><?php echo number_format(
                                    $h['quantity_kg'] ?? 0,
                                    2,
                                ); ?> kg</td>
                                <td class="py-2 text-gray-700">₱<?php echo number_format(
                                    $h['selling_price_per_kg'] ?? 0,
                                    2,
                                ); ?></td>
                                <td class="py-2">
                                    <span class="status-badge <?php echo $h['quality_grade'] === 'Grade A'
                                        ? 'bg-green-100 text-green-700'
                                        : ($h['quality_grade'] === 'Grade B'
                                            ? 'bg-blue-100 text-blue-700'
                                            : 'bg-gray-100 text-gray-700'); ?>">
                                        <?php echo htmlspecialchars($h['quality_grade']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Navigation shortcuts -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 fade-in">
    <?php
    $shortcuts = [
        [
            'href' => '/farmers/index.php',
            'icon' => 'fa-users',
            'color' => 'bg-blue-100 text-blue-600',
            'label' => 'Manage Farmers',
            'desc' => 'Add, edit, view farmers',
        ],
        [
            'href' => '/farms/index.php',
            'icon' => 'fa-seedling',
            'color' => 'bg-purple-100 text-purple-600',
            'label' => 'Manage Farms',
            'desc' => 'Farm locations & details',
        ],
        [
            'href' => '/crops/index.php',
            'icon' => 'fa-carrot',
            'color' => 'bg-green-100 text-green-600',
            'label' => 'Manage Crops',
            'desc' => 'Crop catalog & varieties',
        ],
        [
            'href' => '/planting/index.php',
            'icon' => 'fa-calendar-check',
            'color' => 'bg-orange-100 text-orange-600',
            'label' => 'Planting Records',
            'desc' => 'Track planting cycles',
        ],
        [
            'href' => '/harvest/index.php',
            'icon' => 'fa-wheat-awn',
            'color' => 'bg-red-100 text-red-600',
            'label' => 'Harvest Records',
            'desc' => 'Record harvests & quality',
        ],
    ];
    if (isAdmin()):
        $shortcuts[] = [
            'href' => '/reports/index.php',
            'icon' => 'fa-chart-bar',
            'color' => 'bg-indigo-100 text-indigo-600',
            'label' => 'Reports',
            'desc' => 'View agricultural reports',
        ];
    endif;
    foreach ($shortcuts as $s): ?>
        <a href="<?php echo $s[
            'href'
        ]; ?>" class="group flex items-center gap-3 p-4 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 transition">
            <div class="<?php echo $s[
                'color'
            ]; ?> w-10 h-10 rounded-lg flex items-center justify-center group-hover:scale-110 transition">
                <i class="fa-solid <?php echo $s['icon']; ?>"></i>
            </div>
            <div>
                <div class="font-medium text-gray-800 group-hover:text-blue-600 transition"><?php echo $s[
                    'label'
                ]; ?></div>
                <div class="text-xs text-gray-500"><?php echo $s['desc']; ?></div>
            </div>
        </a>
    <?php endforeach;
    ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
    const statusData = <?php echo json_encode($plantingStatus); ?>;
    const ctx = document.getElementById('statusChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: statusData.map(r => r.status),
            datasets: [{
                data: statusData.map(r => parseInt(r.count)),
                backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { font: { size: 12 } } } }
        }
    });
</script>