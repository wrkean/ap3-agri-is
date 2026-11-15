<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/crud.php';
requireAuth();

$db = new Database();
$harvestModel = new Harvest($db);
$plantingModel = new Planting($db);
$cropModel = new Crop($db);
$action = $_GET['action'] ?? 'list';
$id = (int) ($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) {
        flash('error', 'Invalid security token. Please try again.');
        header('Location: /harvest/index.php' . ($action === 'edit' ? '?action=edit&id=' . $id : '?action=create'));
        exit();
    }

    $data = [
        'planting_id' => (int) ($_POST['planting_id'] ?? 0),
        'harvest_date' => trim($_POST['harvest_date'] ?? ''),
        'quantity_kg' => trim($_POST['quantity_kg'] ?? ''),
        'selling_price_per_kg' => trim($_POST['selling_price_per_kg'] ?? ''),
        'quality_grade_id' => (int) ($_POST['quality_grade_id'] ?? 0),
        'remark_id' => (int) ($_POST['remark_id'] ?? 0) ?: null,
    ];
    $errors = [];
    if (!$data['planting_id']) {
        $errors[] = 'Planting record is required.';
    }
    if (!$data['harvest_date'] || !DateTime::createFromFormat('Y-m-d', $data['harvest_date'])) {
        $errors[] = 'Valid harvest date is required.';
    }
    if (!is_numeric($data['quantity_kg']) || $data['quantity_kg'] <= 0) {
        $errors[] = 'Quantity must be positive.';
    }
    if (!is_numeric($data['selling_price_per_kg']) || $data['selling_price_per_kg'] < 0) {
        $errors[] = 'Selling price must be valid.';
    }
    if (!$data['quality_grade_id']) {
        $errors[] = 'Quality grade is required.';
    }
    if ($errors) {
        storeFormInput($data);
        flash('error', implode(' ', $errors));
        header('Location: /harvest/index.php' . ($action === 'edit' ? '?action=edit&id=' . $id : '?action=create'));
        exit();
    }
    if ($action === 'create') {
        $harvestModel->create($data);
        flash('success', 'Harvest record created.');
        header('Location: /harvest/index.php');
        exit();
    }
    if ($action === 'edit' && $id > 0) {
        $harvestModel->update($id, $data);
        flash('success', 'Harvest updated.');
        header('Location: /harvest/index.php');
        exit();
    }
}

if ($action === 'delete' && $id > 0) {
    $harvestModel->delete($id);
    flash('success', 'Harvest deleted.');
    header('Location: /harvest/index.php');
    exit();
}

$pageTitle = 'Harvest Records';

$plantings = $plantingModel->all([], ['limit' => 100]);
$remarks = $db->select('harvest_remarks', 'remark_id,remark_description');
$gradeOptions = $db->select('quality_grade', 'quality_grade_id,grade_name', [], ['order' => 'quality_grade_id.asc']);
$cropLabels = $cropModel->labels();

$search = trim($_GET['search'] ?? '');
$gradeFilter = (int) ($_GET['grade'] ?? 0);
$sort = $_GET['sort'] ?? 'harvest_id';
$dir = $_GET['dir'] ?? 'asc';
$page = (int) ($_GET['page'] ?? 1);

$filters = [];
if ($search !== '') {
    $grades = $db->select('quality_grade', 'quality_grade_id', [
        'grade_name=ilike.%25' . rawurlencode($search) . '%25',
    ]);
    $ids = array_map('intval', array_column($grades, 'quality_grade_id'));
    $filters[] = 'quality_grade_id=in.(' . implode(',', $ids ?: [0]) . ')';
}
if ($gradeFilter > 0) {
    $filters[] = 'quality_grade_id=eq.' . $gradeFilter;
}

$countRows = $db->select('harvest', 'harvest_id', $filters);
$total = count($countRows);
$p = pagination($page, $total);
$rows = $harvestModel->all(
    $filters,
    ['order' => $sort . '.' . $dir, 'limit' => $p['limit'], 'offset' => $p['offset']],
);

$editRow = null;
if ($action === 'edit' && $id > 0) {
    $editRow = $harvestModel->find($id);
}

$formInput = getFormInput();
if (!empty($formInput)) {
    $editRow = array_merge($editRow ?? [], $formInput);
}
?>
<?php if ($action === 'create' || $action === 'edit'): ?>
    <div class="max-w-xl fade-in">
        <div class="bg-white p-6 rounded-lg border border-gray-200 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4"><?php echo $action === 'create'
                ? 'Add Harvest Record'
                : 'Edit Harvest Record'; ?></h2>
            <form method="POST" action="index.php?action=<?php
            echo $action;
            echo $id > 0 ? '&id=' . $id : '';
            ?>" class="space-y-4">
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Planting Record <span class="text-red-500">*</span></label>
                    <select name="planting_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                        <option value="">Select planting</option>
                        <?php foreach ($plantings as $pl):
                            $plLabel = ($pl['crop']['species']['crop_name'] ?? '?') . ' — ' . ($pl['crop']['variety'] ?? '');
                            ?>
                            <option value="<?php echo $pl['planting_id']; ?>"<?php echo ($editRow['planting_id'] ??
    0) ==
$pl['planting_id']
    ? ' selected'
    : ''; ?>>
                                #<?php echo $pl['planting_id']; ?> — <?php echo e(
     $pl['farm']['farm_name'] ?? '?',
 ); ?> / <?php echo e($plLabel); ?> (<?php echo e($pl['planting_date']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Harvest Date <span class="text-red-500">*</span></label>
                        <input type="date" name="harvest_date" required value="<?php echo e(
                            $editRow['harvest_date'] ?? ($_POST['harvest_date'] ?? ''),
                        ); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantity (kg) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" name="quantity_kg" required value="<?php echo e(
                            $editRow['quantity_kg'] ?? ($_POST['quantity_kg'] ?? ''),
                        ); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Selling Price (per kg) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" name="selling_price_per_kg" required value="<?php echo e(
                            $editRow['selling_price_per_kg'] ?? ($_POST['selling_price_per_kg'] ?? ''),
                        ); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quality Grade <span class="text-red-500">*</span></label>
                        <select name="quality_grade_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                            <option value="">Select grade</option>
                            <?php foreach ($gradeOptions as $o): ?><option value="<?php echo $o[
    'quality_grade_id'
]; ?>"<?php echo ($editRow['quality_grade_id'] ?? 0) == $o['quality_grade_id'] ? ' selected' : ''; ?>><?php echo e(
    $o['grade_name'],
); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Harvest Remark (lookup)</label>
                    <select name="remark_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                        <option value="">— None —</option>
                        <?php foreach ($remarks as $rm): ?><option value="<?php echo $rm[
    'remark_id'
]; ?>"<?php echo ($editRow['remark_id'] ?? 0) == $rm['remark_id'] ? ' selected' : ''; ?>><?php echo e(
    $rm['remark_description'],
); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition"><?php echo $action ===
                    'create'
                        ? 'Create Record'
                        : 'Update Record'; ?></button>
                    <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-100 transition">Cancel</a>
                </div>
            </form>
        </div>
        <a href="index.php" class="text-blue-600 hover:underline">&larr; Back to list</a>
    </div>
<?php else: ?>
    <div class="flex justify-between items-end mb-4 fade-in">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Harvest Records</h1>
            <p class="text-gray-500 text-sm">Track harvests and quality</p>
        </div>
        <a href="index.php?action=create" class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition flex items-center gap-2"><i class="fa-solid fa-plus"></i> Add Record</a>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <form method="GET" action="index.php" class="px-6 py-4 border-b border-gray-100 flex flex-col md:flex-row gap-4 items-end">
            <div class="relative w-full md:w-1/3">
                <i class="fa-solid fa-search absolute left-3 top-3 text-gray-400"></i>
                <input type="text" id="searchInput" name="search" value="<?php echo e(
                    $search,
                ); ?>" placeholder="Search by quality grade..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
            </div>
            <div class="relative w-full md:w-1/3">
                <label class="block text-xs font-medium text-gray-500 mb-1">Quality Grade</label>
                <select id="gradeFilter" name="grade" onchange="this.form.submit()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                    <option value="">All</option>
                    <?php foreach ($gradeOptions as $o): ?><option value="<?php echo $o[
    'quality_grade_id'
]; ?>"<?php echo $gradeFilter === (int) $o['quality_grade_id']
    ? ' selected'
    : ''; ?>><?php echo e($o['grade_name']); ?></option><?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition flex items-center gap-2"><i class="fa-solid fa-search"></i> Search</button>
            <div class="flex gap-2">
                <a href="index.php?search=<?php echo rawurlencode($search); ?>&grade=<?php echo $gradeFilter
    ?: ''; ?>&sort=harvest_id&dir=asc" class="px-4 py-2 text-sm font-medium rounded transition <?php echo $sort === 'harvest_id'
    ? 'bg-blue-50 text-blue-600'
    : 'border border-gray-200 text-gray-700 hover:bg-gray-50'; ?>">Sort by ID</a>
                <a href="index.php?search=<?php echo rawurlencode($search); ?>&grade=<?php echo $gradeFilter
    ?: ''; ?>&sort=harvest_date&dir=desc" class="px-4 py-2 text-sm font-medium rounded transition <?php echo $sort === 'harvest_date'
    ? 'bg-blue-50 text-blue-600'
    : 'border border-gray-200 text-gray-700 hover:bg-gray-50'; ?>">Sort by Date</a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-gray-500 uppercase text-xs">
                        <th class="px-6 py-3">ID</th>
                        <th class="px-6 py-3">Farm / Crop</th>
                        <th class="px-6 py-3">Harvest Date</th>
                        <th class="px-6 py-3">Quantity (kg)</th>
                        <th class="px-6 py-3">Price/kg</th>
                        <th class="px-6 py-3">Grade</th>
                        <th class="px-6 py-3">Value</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($rows as $r): ?>
                        <?php
                        $gradeName = $r['quality_grade']['grade_name'] ?? '—';
                        $cropId = (int) ($r['planting']['crop']['crop_id'] ?? 0);
                        $cropLabel = $cropLabels[$cropId] ?? ($r['planting']['crop']['variety'] ?? '—');
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-medium text-gray-900">#<?php echo $r['harvest_id']; ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e(
                                ($r['planting']['farm']['farm_name'] ?? '—') . ' / ' . $cropLabel,
                            ); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e($r['harvest_date']); ?></td>
                            <td class="px-6 py-3 text-gray-700 font-medium"><?php echo number_format(
                                $r['quantity_kg'],
                                2,
                            ); ?></td>
                            <td class="px-6 py-3 text-gray-700">₱<?php echo number_format(
                                $r['selling_price_per_kg'],
                                2,
                            ); ?></td>
                            <td class="px-6 py-3">
                                <span class="status-badge <?php echo $gradeName === 'Grade A'
                                    ? 'bg-green-100 text-green-700'
                                    : ($gradeName === 'Grade B'
                                        ? 'bg-blue-100 text-blue-700'
                                        : 'bg-gray-100 text-gray-700'); ?>">
                                    <?php echo e($gradeName); ?>
                                </span>
                            </td>
                            <td class="px-6 py-3 text-gray-700 font-medium">₱<?php echo number_format(
                                $r['quantity_kg'] * $r['selling_price_per_kg'],
                                2,
                            ); ?></td>
                            <td class="px-6 py-3 text-right">
                                <a href="index.php?action=edit&id=<?php echo $r[
                                    'harvest_id'
                                ]; ?>" class="text-blue-600 hover:underline text-sm">Edit</a>
                                <a href="index.php?action=delete&id=<?php echo $r[
                                    'harvest_id'
                                ]; ?>" class="text-red-600 hover:underline text-sm ml-4" data-confirm="Delete this harvest record?">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total > 0): ?>
            <div class="px-6 py-4 border-t border-gray-100">
                <?php echo renderPager(
                    $_SERVER['PHP_SELF'] .
                        '?' .
                        http_build_query(
                            array_filter([
                                'search' => $search,
                                'grade' => $gradeFilter ?: '',
                                'sort' => $sort,
                                'dir' => $dir,
                            ]),
                        ),
                    $p['page'],
                    $p['pages'],
                ); ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($total === 0): ?>
        <div class="text-center py-12 text-gray-500">
            <i class="fa-solid fa-wheat-awn text-4xl mb-3 opacity-30"></i>
            <p>No harvest records found.</p>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
