<?php
require_once __DIR__ . '/includes/header.php';
requireAuth();

$db = new Database();
$farmerModel = new Farmer($db);
$farmModel = new Farm($db);
$cropModel = new Crop($db);
$plantingModel = new Planting($db);
$harvestModel = new Harvest($db);

// Summary counts
$counts = [
    'farmers' => $farmerModel->count(),
    'farms' => $farmModel->count(),
    'crops' => $cropModel->count(),
    'planting' => $plantingModel->count(),
    'harvest' => $harvestModel->count(),
];

// Recent records
$recentPlanting = $plantingModel->all(
    [],
    ['limit' => 5, 'order' => 'planting_id.desc'],
);
$recentHarvest = $harvestModel->all(
    [],
    ['limit' => 5, 'order' => 'harvest_id.desc'],
);

// Plantings by status for chart (map lookup IDs to labels)
$plantingStatusRows = $plantingModel->byStatus();
$statusNames = [];
foreach ($db->select('planting_status', 'planting_status_id,status_name') as $s) {
    $statusNames[(int) $s['planting_status_id']] = $s['status_name'];
}
$plantingStatus = array_map(
    fn(array $r) => [
        'status' => $statusNames[(int) $r['planting_status_id']] ?? 'Unknown',
        'count' => $r['count'],
    ],
    $plantingStatusRows,
);

// Harvest totals and quantity-weighted average price
$harvestStats = $harvestModel->stats();
$totalHarvest = $harvestStats['quantity'];

$pageTitle = 'Dashboard';
?>
<h1 class="text-2xl font-bold text-gray-800 mb-6 fade-in">Dashboard</h1>

<!-- Summary strip (compact) -->
<div class="bg-white rounded-lg border border-gray-200 mb-6 fade-in overflow-x-auto">
    <div class="flex divide-x divide-gray-100 min-w-max">
        <?php
        $stats = [
            ['label' => 'Farmers', 'value' => $counts['farmers'], 'icon' => 'fa-users', 'color' => 'text-blue-600'],
            ['label' => 'Farms', 'value' => $counts['farms'], 'icon' => 'fa-seedling', 'color' => 'text-purple-600'],
            ['label' => 'Crops', 'value' => $counts['crops'], 'icon' => 'fa-carrot', 'color' => 'text-green-600'],
            ['label' => 'Plantings', 'value' => $counts['planting'], 'icon' => 'fa-calendar-check', 'color' => 'text-orange-600'],
            ['label' => 'Harvests', 'value' => $counts['harvest'], 'icon' => 'fa-wheat-awn', 'color' => 'text-red-600'],
        ];
        foreach ($stats as $s): ?>
            <div class="flex-1 min-w-[120px] px-4 py-3 flex items-center gap-3">
                <i class="fa-solid <?php echo $s['icon']; ?> <?php echo $s['color']; ?> text-lg"></i>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-semibold leading-tight"><?php echo $s['label']; ?></p>
                    <p class="text-xl font-bold text-gray-900 leading-tight"><?php echo number_format($s['value']); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Getting started (shown when no data exists yet) -->
<?php if ($counts['farmers'] === 0): ?>
<div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6 fade-in">
    <h3 class="font-semibold text-blue-900 mb-2 flex items-center gap-2">
        <i class="fa-solid fa-rocket text-blue-600"></i> Getting Started
    </h3>
    <p class="text-sm text-blue-800 mb-3">Welcome! Follow these steps to set up your agriculture data:</p>
    <ol class="list-decimal list-inside text-sm text-blue-800 space-y-1 mb-4">
        <li><strong>Add Farmers</strong> — Register the farmers in your area</li>
        <li><strong>Add Farms</strong> — Create farm records linked to farmers</li>
        <li><strong>Add Crops</strong> — Define crop varieties and growing periods</li>
        <li><strong>Record Plantings</strong> — Track what was planted, where, and when</li>
        <li><strong>Record Harvests</strong> — Log harvest quantities and selling prices</li>
    </ol>
    <a href="/farmers/index.php?action=create" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">
        <i class="fa-solid fa-plus"></i> Add Your First Farmer
    </a>
</div>
<?php endif; ?>

<!-- Total Harvested Quantity + Status Chart -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8 fade-in">
    <div class="bg-white p-6 rounded-lg border border-gray-200">
        <p class="text-sm text-gray-500 uppercase font-semibold mb-2">Total Harvested Quantity</p>
        <h2 class="text-3xl font-bold text-gray-900 mb-1"><?php echo number_format($totalHarvest, 2); ?> kg</h2>
        <p class="text-xs text-gray-500">Estimated value: <span class="font-semibold text-gray-700"><?php echo number_format(
            $harvestStats['value'],
            2,
        ); ?> PHP</span> (avg ₱<?php echo number_format($harvestStats['average_price'], 2); ?>/kg)</p>
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
                                    $p['crop']['species']['crop_name'] ?? '—',
                                ); ?></td>
                                <td class="py-2 text-gray-700"><?php echo htmlspecialchars(
                                    $p['planting_date'] ?? '',
                                ); ?></td>
                                <td class="py-2">
                                    <?php $pStatus = $p['planting_status']['status_name'] ?? '—'; ?>
                                    <span class="status-badge <?php echo $pStatus === 'Harvested'
                                        ? 'bg-green-100 text-green-700'
                                        : 'bg-blue-100 text-blue-700'; ?>">
                                        <?php echo htmlspecialchars($pStatus); ?>
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
                                    <?php $hGrade = $h['quality_grade']['grade_name'] ?? '—'; ?>
                                    <span class="status-badge <?php echo $hGrade === 'Grade A'
                                        ? 'bg-green-100 text-green-700'
                                        : ($hGrade === 'Grade B'
                                            ? 'bg-blue-100 text-blue-700'
                                            : 'bg-gray-100 text-gray-700'); ?>">
                                        <?php echo htmlspecialchars($hGrade); ?>
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

<!-- Quick actions: create new records -->
<div class="mb-8 fade-in">
    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Quick Actions</h2>
    <div class="flex flex-wrap gap-2">
        <a href="/farmers/index.php?action=create" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-blue-50 hover:border-blue-300 hover:text-blue-700 transition">
            <i class="fa-solid fa-plus text-blue-600"></i> New Farmer
        </a>
        <a href="/farms/index.php?action=create" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-purple-50 hover:border-purple-300 hover:text-purple-700 transition">
            <i class="fa-solid fa-plus text-purple-600"></i> New Farm
        </a>
        <a href="/crops/index.php?action=create" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-green-50 hover:border-green-300 hover:text-green-700 transition">
            <i class="fa-solid fa-plus text-green-600"></i> New Crop
        </a>
        <a href="/planting/index.php?action=create" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-orange-50 hover:border-orange-300 hover:text-orange-700 transition">
            <i class="fa-solid fa-plus text-orange-600"></i> New Planting
        </a>
        <a href="/harvest/index.php?action=create" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-red-50 hover:border-red-300 hover:text-red-700 transition">
            <i class="fa-solid fa-plus text-red-600"></i> New Harvest
        </a>
        <?php if (isAdmin()): ?>
        <a href="/reports/index.php" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition">
            <i class="fa-solid fa-chart-bar text-indigo-600"></i> Reports
        </a>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
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