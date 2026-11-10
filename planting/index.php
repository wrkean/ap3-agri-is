<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/crud.php';
requireAuth();

$sb = new Database();
$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'farm_id'              => (int)($_POST['farm_id'] ?? 0),
        'crop_id'              => (int)($_POST['crop_id'] ?? 0),
        'planting_date'        => trim($_POST['planting_date'] ?? ''),
        'area_planted'         => trim($_POST['area_planted'] ?? ''),
        'seed_quantity'        => trim($_POST['seed_quantity'] ?? ''),
        'expected_harvest_date'=> trim($_POST['expected_harvest_date'] ?? ''),
        'status'               => trim($_POST['status'] ?? 'Growing'),
    ];
    $errors = [];
    if (!$data['farm_id']) $errors[] = 'Farm is required.';
    if (!$data['crop_id']) $errors[] = 'Crop is required.';
    if (!$data['planting_date'] || !DateTime::createFromFormat('Y-m-d', $data['planting_date'])) $errors[] = 'Valid planting date is required.';
    if (!is_numeric($data['area_planted']) || $data['area_planted'] <= 0) $errors[] = 'Area planted must be positive.';
    if (!is_numeric($data['seed_quantity']) || $data['seed_quantity'] < 0) $errors[] = 'Seed quantity must be valid.';
    if (!$data['expected_harvest_date'] || !DateTime::createFromFormat('Y-m-d', $data['expected_harvest_date'])) $errors[] = 'Valid expected harvest date is required.';
    if ($data['expected_harvest_date'] < $data['planting_date']) $errors[] = 'Expected harvest must be after planting date.';
    if ($errors) { flash('error', implode(' ', $errors)); header('Location: planting/index.php' . ($action === 'edit' ? '?action=edit&id=' . $id : '?action=create')); exit; }
    if ($action === 'create') { $sb->insert('planting', $data); flash('success', 'Planting record created.'); header('Location: planting/index.php'); exit; }
    if ($action === 'edit' && $id > 0) { $sb->update('planting', $data, ['planting_id=eq.' . $id]); flash('success', 'Planting updated.'); header('Location: planting/index.php'); exit; }
}

if ($action === 'delete' && $id > 0) {
    $harvests = $sb->select('harvest', 'harvest_id', ['planting_id=eq.' . $id]);
    if (!empty($harvests)) flash('error', 'Cannot delete planting: has harvest records.');
    else { $sb->delete('planting', ['planting_id=eq.' . $id]); flash('success', 'Planting deleted.'); }
    header('Location: planting/index.php'); exit;
}

$pageTitle = 'Planting Records';

$farms = $sb->select('farm', 'farm_id,farm_name');
$crops = $sb->select('crop', 'crop_id,crop_name');

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$farmFilter = $_GET['farm'] ?? '';
$sort = $_GET['sort'] ?? 'planting_id';
$dir = $_GET['dir'] ?? 'asc';
$page = (int)($_GET['page'] ?? 1);

$filters = [];
if ($search !== '') { $filters[] = 'planting_date=ilike.%' . rawurlencode($search) . '%'; }
if ($statusFilter !== '') { $filters[] = 'status=eq.' . rawurlencode($statusFilter); }
if ($farmFilter !== '') { $filters[] = 'farm_id=eq.' . (int)$farmFilter; }

$countRows = $sb->select('planting', 'planting_id', $filters);
$total = count($countRows);
$p = pagination($page, $total);
$rows = $sb->select('planting', '*,farm(farm_name),crop(crop_name)', array_merge($filters, ['order' => $sort . '.' . $dir, 'limit' => $p['limit'], 'offset' => $p['offset']]));

$editRow = null;
if ($action === 'edit' && $id > 0) { $editRow = $sb->find('planting', $id, 'planting_id'); }

$statusOptions = ['Growing', 'Harvested', 'Failed'];
?>
<?php if ($action === 'create' || $action === 'edit'): ?>
    <div class="max-w-xl fade-in">
        <div class="bg-white p-6 rounded-lg border border-gray-200 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4"><?php echo $action === 'create' ? 'Add Planting Record' : 'Edit Planting Record'; ?></h2>
            <form method="POST" action="index.php?action=<?php echo $action; ?><?php echo $id > 0 ? '&id=' . $id : ''; ?>" class="space-y-4">
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Farm <span class="text-red-500">*</span></label>
                        <select name="farm_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                            <option value="">Select farm</option>
                            <?php foreach ($farms as $f): ?><option value="<?php echo $f['farm_id']; ?>"<?php echo ($editRow['farm_id'] ?? 0) == $f['farm_id'] ? ' selected' : ''; ?>><?php echo e($f['farm_name']); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Crop <span class="text-red-500">*</span></label>
                        <select name="crop_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                            <option value="">Select crop</option>
                            <?php foreach ($crops as $c): ?><option value="<?php echo $c['crop_id']; ?>"<?php echo ($editRow['crop_id'] ?? 0) == $c['crop_id'] ? ' selected' : ''; ?>><?php echo e($c['crop_name']); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Planting Date <span class="text-red-500">*</span></label>
                        <input type="date" name="planting_date" required value="<?php echo e($editRow['planting_date'] ?? ($_POST['planting_date'] ?? '')); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Expected Harvest Date <span class="text-red-500">*</span></label>
                        <input type="date" name="expected_harvest_date" required value="<?php echo e($editRow['expected_harvest_date'] ?? ($_POST['expected_harvest_date'] ?? '')); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Area Planted (ha) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" name="area_planted" required value="<?php echo e($editRow['area_planted'] ?? ($_POST['area_planted'] ?? '')); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Seed Quantity (kg) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" name="seed_quantity" required value="<?php echo e($editRow['seed_quantity'] ?? ($_POST['seed_quantity'] ?? '')); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                    <select name="status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                        <?php foreach ($statusOptions as $o): ?><option value="<?php echo e($o); ?>"<?php echo ($editRow['status'] ?? '') == $o ? ' selected' : ''; ?>><?php echo e($o); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition"><?php echo $action === 'create' ? 'Create Record' : 'Update Record'; ?></button>
                    <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-100 transition">Cancel</a>
                </div>
            </form>
        </div>
        <a href="index.php" class="text-blue-600 hover:underline">&larr; Back to list</a>
    </div>
<?php else: ?>
    <div class="flex justify-between items-end mb-4 fade-in">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Planting Records</h1>
            <p class="text-gray-500 text-sm">Track crop planting cycles</p>
        </div>
        <a href="index.php?action=create" class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition flex items-center gap-2"><i class="fa-solid fa-plus"></i> Add Record</a>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex flex-col md:flex-row gap-4 items-end">
            <div class="relative w-full md:w-1/3">
                <i class="fa-solid fa-search absolute left-3 top-3 text-gray-400"></i>
                <input type="text" id="searchInput" value="<?php echo e($search); ?>" placeholder="Search by date..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
            </div>
            <div class="relative w-full md:w-1/3">
                <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                <select id="statusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                    <option value="">All</option>
                    <?php foreach ($statusOptions as $o): ?><option value="<?php echo e($o); ?>"<?php echo $statusFilter === $o ? ' selected' : ''; ?>><?php echo e($o); ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="relative w-full md:w-1/3">
                <label class="block text-xs font-medium text-gray-500 mb-1">Farm</label>
                <select id="farmFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                    <option value="">All</option>
                    <?php foreach ($farms as $f): ?><option value="<?php echo $f['farm_id']; ?>"<?php echo $farmFilter == $f['farm_id'] ? ' selected' : ''; ?>><?php echo e($f['farm_name']); ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="flex gap-2">
                <a href="index.php?search=<?php echo rawurlencode($search); ?>&status=<?php echo rawurlencode($statusFilter); ?>&farm=<?php echo rawurlencode($farmFilter); ?>&sort=planting_id&dir=asc" class="px-4 py-2 text-sm font-medium rounded transition <?php echo $sort === 'planting_id' ? 'bg-blue-50 text-blue-600' : 'border border-gray-200 text-gray-700 hover:bg-gray-50'; ?>">Sort by ID</a>
                <a href="index.php?search=<?php echo rawurlencode($search); ?>&status=<?php echo rawurlencode($statusFilter); ?>&farm=<?php echo rawurlencode($farmFilter); ?>&sort=planting_date&dir=asc" class="px-4 py-2 text-sm font-medium rounded transition <?php echo $sort === 'planting_date' ? 'bg-blue-50 text-blue-600' : 'border border-gray-200 text-gray-700 hover:bg-gray-50'; ?>">Sort by Date</a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-gray-500 uppercase text-xs">
                        <th class="px-6 py-3">ID</th>
                        <th class="px-6 py-3">Farm</th>
                        <th class="px-6 py-3">Crop</th>
                        <th class="px-6 py-3">Planting Date</th>
                        <th class="px-6 py-3">Exp. Harvest</th>
                        <th class="px-6 py-3">Area (ha)</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($rows as $r): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-medium text-gray-900">#<?php echo $r['planting_id']; ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e($r['farm']['farm_name'] ?? '—'); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e($r['crop']['crop_name'] ?? '—'); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e($r['planting_date']); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e($r['expected_harvest_date']); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo number_format($r['area_planted'], 2); ?></td>
                            <td class="px-6 py-3">
                                <span class="status-badge <?php echo $r['status'] === 'Harvested' ? 'bg-green-100 text-green-700' : ($r['status'] === 'Growing' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700'); ?>">
                                    <?php echo e($r['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-3 text-right">
                                <a href="index.php?action=edit&id=<?php echo $r['planting_id']; ?>" class="text-blue-600 hover:underline text-sm">Edit</a>
                                <a href="index.php?action=delete&id=<?php echo $r['planting_id']; ?>" class="text-red-600 hover:underline text-sm ml-4" data-confirm="Delete this planting? Related harvest records will be affected.">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total > 0): ?>
            <div class="px-6 py-4 border-t border-gray-100">
                <?php echo renderPager($_SERVER['PHP_SELF'] . '?' . http_build_query(array_filter(['search' => $search, 'status' => $statusFilter, 'farm' => $farmFilter, 'sort' => $sort, 'dir' => $dir])), $p['page'], $p['pages']); ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($total === 0): ?>
        <div class="text-center py-12 text-gray-500">
            <i class="fa-solid fa-calendar-check text-4xl mb-3 opacity-30"></i>
            <p>No planting records found.</p>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>