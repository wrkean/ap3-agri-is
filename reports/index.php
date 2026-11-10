<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/crud.php';
requireAuth();
requireAdmin();

$sb = new Database();
$pageTitle = 'Reports';

// Report filters
$reportType = $_GET['report'] ?? 'farmers_farms';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Fetch data based on report type
$reportData = [];
switch ($reportType) {
    case 'farmers_farms':
        $farmers = $sb->select('farmer', '*,farm(*)');
        $reportData['farmers'] = $farmers;
        break;
    case 'crops_per_farm':
        $plantings = $sb->select('planting', '*,farm(farm_name),crop(crop_name)', [], ['limit' => 500]);
        $reportData['plantings'] = $plantings;
        break;
    case 'planting_status':
        $plantings = $sb->select('planting', '*,farm(farm_name),crop(crop_name)', [], ['limit' => 500]);
        $reportData['plantings'] = $plantings;
        break;
    case 'harvest_records':
        $harvests = $sb->select(
            'harvest',
            '*,planting(planting_id,farm(farm_name),crop(crop_name))',
            [],
            ['limit' => 500],
        );
        $reportData['harvests'] = $harvests;
        break;
    case 'total_harvested':
        $harvests = $sb->select('harvest', 'quantity_kg', [], ['limit' => 500]);
        $reportData['harvests'] = $harvests;
        break;
    case 'harvest_value':
        $harvests = $sb->select(
            'harvest',
            '*,planting(planting_id,farm(farm_name),crop(crop_name))',
            [],
            ['limit' => 500],
        );
        $reportData['harvests'] = $harvests;
        break;
}
?>
<h1 class="text-2xl font-bold text-gray-800 mb-6 fade-in">Reports</h1>

<!-- Report selector -->
<div class="bg-white p-4 rounded-lg border border-gray-200 mb-6 fade-in">
    <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
        <div class="w-full md:w-1/3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
            <select name="report" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                <option value="farmers_farms"<?php echo $reportType === 'farmers_farms'
                    ? ' selected'
                    : ''; ?>>Farmers and Their Farms</option>
                <option value="crops_per_farm"<?php echo $reportType === 'crops_per_farm'
                    ? ' selected'
                    : ''; ?>>Crops Planted Per Farm</option>
                <option value="planting_status"<?php echo $reportType === 'planting_status'
                    ? ' selected'
                    : ''; ?>>Planting Records by Status</option>
                <option value="harvest_records"<?php echo $reportType === 'harvest_records'
                    ? ' selected'
                    : ''; ?>>Harvest Records</option>
                <option value="total_harvested"<?php echo $reportType === 'total_harvested'
                    ? ' selected'
                    : ''; ?>>Total Harvested Quantity</option>
                <option value="harvest_value"<?php echo $reportType === 'harvest_value'
                    ? ' selected'
                    : ''; ?>>Estimated Harvest Value</option>
            </select>
        </div>
        <div class="w-full md:w-1/4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
            <input type="date" name="date_from" value="<?php echo e(
                $dateFrom,
            ); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
        </div>
        <div class="w-full md:w-1/4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
            <input type="date" name="date_to" value="<?php echo e(
                $dateTo,
            ); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
        </div>
        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition">Generate</button>
    </form>
</div>

<!-- Report output -->
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden fade-in">
    <?php if ($reportType === 'farmers_farms'): ?>
        <div class="px-6 py-4 border-b border-gray-100"><h3 class="font-semibold text-gray-800">Farmers and Their Farms</h3></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="bg-gray-50 text-left text-gray-500 uppercase text-xs"><th class="px-6 py-3">Farmer</th><th class="px-6 py-3">Contact</th><th class="px-6 py-3">Address</th><th class="px-6 py-3">Registered</th><th class="px-6 py-3">Farms</th></tr></thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($reportData['farmers'] as $f): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-gray-700 font-medium"><?php echo e(
                                $f['first_name'] . ' ' . $f['last_name'],
                            ); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e($f['contact_number'] ?? '—'); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e($f['address'] ?? '—'); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e($f['date_registered'] ?? '—'); ?></td>
                            <td class="px-6 py-3 text-gray-700">
                                <?php if (!empty($f['farm'])): ?>
                                    <ul class="list-disc pl-4 text-xs">
                                        <?php foreach ($f['farm'] as $fm): ?>
                                            <li><?php echo e($fm['farm_name']); ?> — <?php echo e(
     $fm['location'],
 ); ?> (<?php echo number_format($fm['area_hectares'], 2); ?> ha)</li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <span class="text-gray-400">No farms</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($reportType === 'crops_per_farm'): ?>
        <div class="px-6 py-4 border-b border-gray-100"><h3 class="font-semibold text-gray-800">Crops Planted Per Farm</h3></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="bg-gray-50 text-left text-gray-500 uppercase text-xs"><th class="px-6 py-3">Farm</th><th class="px-6 py-3">Crop</th><th class="px-6 py-3">Planting Date</th><th class="px-6 py-3">Area Planted (ha)</th><th class="px-6 py-3">Status</th></tr></thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($reportData['plantings'] as $p): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-gray-700"><?php echo e($p['farm']['farm_name'] ?? '—'); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e($p['crop']['crop_name'] ?? '—'); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e($p['planting_date']); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo number_format($p['area_planted'], 2); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e($p['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($reportType === 'planting_status'): ?>
        <div class="px-6 py-4 border-b border-gray-100"><h3 class="font-semibold text-gray-800">Planting Records by Status</h3></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="bg-gray-50 text-left text-gray-500 uppercase text-xs"><th class="px-6 py-3">Status</th><th class="px-6 py-3">Count</th></tr></thead>
                <tbody class="divide-y divide-gray-50">
                    <?php
                    $statusCounts = [];
                    foreach ($reportData['plantings'] as $p) {
                        $statusCounts[$p['status']] = ($statusCounts[$p['status']] ?? 0) + 1;
                    }
                    foreach ($statusCounts as $st => $cnt): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-gray-700 font-medium"><?php echo e($st); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo number_format($cnt); ?></td>
                        </tr>
                    <?php endforeach;
                    ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($reportType === 'harvest_records'): ?>
        <div class="px-6 py-4 border-b border-gray-100"><h3 class="font-semibold text-gray-800">Harvest Records</h3></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="bg-gray-50 text-left text-gray-500 uppercase text-xs"><th class="px-6 py-3">Harvest ID</th><th class="px-6 py-3">Farm</th><th class="px-6 py-3">Crop</th><th class="px-6 py-3">Date</th><th class="px-6 py-3">Quantity (kg)</th><th class="px-6 py-3">Price/kg</th><th class="px-6 py-3">Grade</th><th class="px-6 py-3">Value</th></tr></thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($reportData['harvests'] as $h): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-gray-700">#<?php echo $h['harvest_id']; ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e(
                                $h['planting']['farm']['farm_name'] ?? '—',
                            ); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e(
                                $h['planting']['crop']['crop_name'] ?? '—',
                            ); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e($h['harvest_date']); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo number_format($h['quantity_kg'], 2); ?></td>
                            <td class="px-6 py-3 text-gray-700">₱<?php echo number_format(
                                $h['selling_price_per_kg'],
                                2,
                            ); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e($h['quality_grade']); ?></td>
                            <td class="px-6 py-3 text-gray-700 font-medium">₱<?php echo number_format(
                                $h['quantity_kg'] * $h['selling_price_per_kg'],
                                2,
                            ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($reportType === 'total_harvested'): ?>
        <div class="px-6 py-4 border-b border-gray-100"><h3 class="font-semibold text-gray-800">Total Harvested Quantity</h3></div>
        <div class="p-6">
            <?php
            $totalQty = 0;
            foreach ($reportData['harvests'] as $h) {
                $totalQty += (float) $h['quantity_kg'];
            }
            ?>
            <div class="text-center">
                <p class="text-5xl font-bold text-green-600"><?php echo number_format($totalQty, 2); ?></p>
                <p class="text-gray-500 mt-2">kilograms harvested across all records</p>
            </div>
        </div>
    <?php elseif ($reportType === 'harvest_value'): ?>
        <div class="px-6 py-4 border-b border-gray-100"><h3 class="font-semibold text-gray-800">Estimated Harvest Value</h3></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="bg-gray-50 text-left text-gray-500 uppercase text-xs"><th class="px-6 py-3">Harvest ID</th><th class="px-6 py-3">Farm</th><th class="px-6 py-3">Crop</th><th class="px-6 py-3">Quantity (kg)</th><th class="px-6 py-3">Price/kg</th><th class="px-6 py-3">Estimated Value</th></tr></thead>
                <tbody class="divide-y divide-gray-50">
                    <?php
                    $grandTotal = 0;
                    foreach ($reportData['harvests'] as $h):

                        $val = (float) $h['quantity_kg'] * (float) $h['selling_price_per_kg'];
                        $grandTotal += $val;
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-gray-700">#<?php echo $h['harvest_id']; ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e(
                                $h['planting']['farm']['farm_name'] ?? '—',
                            ); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e(
                                $h['planting']['crop']['crop_name'] ?? '—',
                            ); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo number_format($h['quantity_kg'], 2); ?></td>
                            <td class="px-6 py-3 text-gray-700">₱<?php echo number_format(
                                $h['selling_price_per_kg'],
                                2,
                            ); ?></td>
                            <td class="px-6 py-3 text-gray-700 font-medium">₱<?php echo number_format($val, 2); ?></td>
                        </tr>
                    <?php
                    endforeach;
                    ?>
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50 font-bold">
                        <td colspan="5" class="px-6 py-3 text-right">Grand Total:</td>
                        <td class="px-6 py-3 text-green-600">₱<?php echo number_format($grandTotal, 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
