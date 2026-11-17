<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/crud.php';
requireAuth();

$db = new Database();
$farmModel = new Farm($db);
$farmerModel = new Farmer($db);
$action = $_GET['action'] ?? 'list';
$id = (int) ($_GET['id'] ?? 0);

// Handle delete (POST + CSRF only; GET links are not allowed)
if ($action === 'delete') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        flash('error', 'Delete must be submitted from the delete button.');
    } elseif (!validateCsrf()) {
        flash('error', 'Invalid security token. Please try again.');
    } elseif ($id <= 0) {
        flash('error', 'Invalid record ID.');
    } else {
        $plantings = $db->select('planting', 'planting_id', ['farm_id=eq.' . $id]);
        if (!empty($plantings)) {
            flash('error', 'Cannot delete farm: has planting records.');
        } else {
            $result = $farmModel->delete($id);
            if ($result === null) {
                flash('error', 'Could not save. Please check your input.');
            } else {
                flash('success', 'Farm deleted.');
            }
        }
    }
    header('Location: /farms/index.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) {
        flash('error', 'Invalid security token. Please try again.');
        header('Location: /farms/index.php' . ($action === 'edit' ? '?action=edit&id=' . $id : '?action=create'));
        exit();
    }

    $data = [
        'farmer_id' => (int) ($_POST['farmer_id'] ?? 0),
        'farm_name' => trim($_POST['farm_name'] ?? ''),
        'location' => trim($_POST['location'] ?? ''),
        'area_hectares' => trim($_POST['area_hectares'] ?? ''),
        'soil_type_id' => (int) ($_POST['soil_type_id'] ?? 0),
        'irrigation_type_id' => (int) ($_POST['irrigation_type_id'] ?? 0),
    ];
    $errors = [];
    if (!$data['farmer_id']) {
        $errors[] = 'Farmer is required.';
    }
    if (empty($data['farm_name'])) {
        $errors[] = 'Farm name is required.';
    } elseif (!isValidAddress($data['farm_name'])) {
        $errors[] = 'Farm name contains invalid characters.';
    }
    if (empty($data['location'])) {
        $errors[] = 'Location is required.';
    } elseif (!isValidAddress($data['location'])) {
        $errors[] = 'Location contains invalid characters.';
    }
    if (!is_numeric($data['area_hectares']) || $data['area_hectares'] <= 0) {
        $errors[] = 'Area must be a positive number.';
    }
    if (!$data['soil_type_id']) {
        $errors[] = 'Soil type is required.';
    }
    if (!$data['irrigation_type_id']) {
        $errors[] = 'Irrigation type is required.';
    }
    if ($errors) {
        storeFormInput($data);
        flash('error', implode(' ', $errors));
        header('Location: /farms/index.php' . ($action === 'edit' ? '?action=edit&id=' . $id : '?action=create'));
        exit();
    }
    if ($action === 'create') {
        $result = $farmModel->create($data);
        if ($result === null) {
            storeFormInput($data);
            flash('error', 'Could not save. Please check your input.');
            header('Location: /farms/index.php?action=create');
            exit();
        }
        flash('success', 'Farm created.');
        header('Location: /farms/index.php');
        exit();
    }
    if ($action === 'edit' && $id > 0) {
        $result = $farmModel->update($id, $data);
        if ($result === null) {
            storeFormInput($data);
            flash('error', 'Could not save. Please check your input.');
            header('Location: /farms/index.php?action=edit&id=' . $id);
            exit();
        }
        flash('success', 'Farm updated.');
        header('Location: /farms/index.php');
        exit();
    }
}

$pageTitle = 'Farms';

// Reference data for dropdowns (lookup IDs are stored, labels are displayed)
$farmers = $farmerModel->all();
$soilOptions = $db->select('soil_type', 'soil_type_id,type_name', [], ['order' => 'soil_type_id.asc']);
$irrOptions = $db->select('irrigation_type', 'irrigation_type_id,type_name', [], ['order' => 'irrigation_type_id.asc']);

// Search/filter/sort/pagination
$search = trim($_GET['search'] ?? '');
$irrFilter = (int) ($_GET['irrigation'] ?? 0);
$sort = whitelistSort($_GET['sort'] ?? 'farm_id', ['farm_id', 'farm_name', 'area_hectares'], 'farm_id');
$dir = whitelistDir($_GET['dir'] ?? 'asc');
$page = (int) ($_GET['page'] ?? 1);

$filters = [];
if ($search !== '') {
    foreach (preg_split('/\s+/', trim($search)) as $word) {
        if ($word === '') continue;
        $w = rawurlencode($word);
        $filters[] = 'or=(farm_name.ilike.%25' . $w . '%25,location.ilike.%25' . $w . '%25)';
    }
}
if ($irrFilter > 0) {
    $filters[] = 'irrigation_type_id=eq.' . $irrFilter;
}

$countRows = $db->select('farm', 'farm_id', $filters);
$total = count($countRows);
$p = pagination($page, $total);
$rows = $farmModel->all(
    $filters,
    ['order' => $sort . '.' . $dir, 'limit' => $p['limit'], 'offset' => $p['offset']],
);

// Edit row
$editRow = null;
if ($action === 'edit' && $id > 0) {
    $editRow = $farmModel->find($id);
}

// Repopulate from stored form input (after validation error redirect)
$formInput = getFormInput();
if (!empty($formInput)) {
    $editRow = array_merge($editRow ?? [], $formInput);
}
?>
<?php if ($action === 'create' || $action === 'edit'): ?>
    <div class="max-w-xl fade-in">
        <div class="bg-white p-6 rounded-lg border border-gray-200 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4"><?php echo $action === 'create'
                ? 'Add Farm'
                : 'Edit Farm'; ?></h2>
            <p class="text-sm text-gray-500 -mt-2 mb-4">Fields marked * are required. Area must be a positive number of hectares.</p>
            <form method="POST" action="index.php?action=<?php
            echo $action;
            echo $id > 0 ? '&id=' . $id : '';
            ?>" class="space-y-4">
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Farmer <span class="text-red-500">*</span></label>
                        <select name="farmer_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                            <option value="">Select farmer</option>
                            <?php foreach ($farmers as $f): ?><option value="<?php echo $f[
    'farmer_id'
]; ?>"<?php echo ($editRow['farmer_id'] ?? 0) == $f['farmer_id'] ? ' selected' : ''; ?>><?php echo e(
    $f['first_name'] . ' ' . $f['last_name'],
); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Farm Name <span class="text-red-500">*</span></label>
                        <input type="text" name="farm_name" required pattern="[A-Za-z0-9\s.,#\-/()]+" title="Letters, digits, spaces and common punctuation only." value="<?php echo e(
                            $editRow['farm_name'] ?? ($_POST['farm_name'] ?? ''),
                        ); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Location <span class="text-red-500">*</span></label>
                    <input type="text" name="location" required pattern="[A-Za-z0-9\s.,#\-/()]+" title="Letters, digits, spaces and common punctuation only." value="<?php echo e(
                        $editRow['location'] ?? ($_POST['location'] ?? ''),
                    ); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Area (hectares) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" name="area_hectares" required value="<?php echo e(
                            $editRow['area_hectares'] ?? ($_POST['area_hectares'] ?? ''),
                        ); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Soil Type <span class="text-red-500">*</span></label>
                        <select name="soil_type_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                            <option value="">Select soil type</option>
                            <?php foreach ($soilOptions as $o): ?><option value="<?php echo $o[
    'soil_type_id'
]; ?>"<?php echo ($editRow['soil_type_id'] ?? 0) == $o['soil_type_id'] ? ' selected' : ''; ?>><?php echo e(
    $o['type_name'],
); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Irrigation Type <span class="text-red-500">*</span></label>
                    <select name="irrigation_type_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                        <option value="">Select irrigation type</option>
                        <?php foreach ($irrOptions as $o): ?><option value="<?php echo $o[
    'irrigation_type_id'
]; ?>"<?php echo ($editRow['irrigation_type_id'] ?? 0) == $o['irrigation_type_id'] ? ' selected' : ''; ?>><?php echo e(
    $o['type_name'],
); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition"><?php echo $action ===
                    'create'
                        ? 'Create Farm'
                        : 'Update Farm'; ?></button>
                    <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-100 transition">Cancel</a>
                </div>
            </form>
        </div>
        <a href="index.php" class="text-blue-600 hover:underline">&larr; Back to list</a>
    </div>
<?php else: ?>
    <div class="flex justify-between items-end mb-4 fade-in">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Farms</h1>
            <p class="text-gray-500 text-sm">Manage farm information. Deleting a farm is blocked while the farm still has planting records.</p>
        </div>
        <a href="index.php?action=create" class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition flex items-center gap-2"><i class="fa-solid fa-plus"></i> Add Farm</a>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <form method="GET" action="index.php" class="px-6 py-4 border-b border-gray-100 flex flex-col md:flex-row gap-4 items-end">
            <div class="relative w-full md:w-1/3">
                <i class="fa-solid fa-search absolute left-3 top-3 text-gray-400"></i>
                <input type="text" id="searchInput" name="search" value="<?php echo e(
                    $search,
                ); ?>" placeholder="Search farm name or location..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
            </div>
            <div class="relative w-full md:w-1/3">
                <label class="block text-xs font-medium text-gray-500 mb-1">Irrigation Type</label>
                <select id="irrigationFilter" name="irrigation" onchange="this.form.submit()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                    <option value="">All</option>
                    <?php foreach ($irrOptions as $o): ?><option value="<?php echo $o[
    'irrigation_type_id'
]; ?>"<?php echo $irrFilter === (int) $o['irrigation_type_id']
    ? ' selected'
    : ''; ?>><?php echo e($o['type_name']); ?></option><?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition flex items-center gap-2"><i class="fa-solid fa-search"></i> Search</button>
            <div class="flex gap-2">
                <a href="index.php?search=<?php echo rawurlencode($search); ?>&irrigation=<?php echo $irrFilter
    ?: ''; ?>&sort=farm_id&dir=asc" class="px-4 py-2 text-sm font-medium rounded transition <?php echo $sort === 'farm_id'
    ? 'bg-blue-50 text-blue-600'
    : 'border border-gray-200 text-gray-700 hover:bg-gray-50'; ?>">Sort by ID</a>
                <a href="index.php?search=<?php echo rawurlencode($search); ?>&irrigation=<?php echo $irrFilter
    ?: ''; ?>&sort=farm_name&dir=asc" class="px-4 py-2 text-sm font-medium rounded transition <?php echo $sort === 'farm_name'
    ? 'bg-blue-50 text-blue-600'
    : 'border border-gray-200 text-gray-700 hover:bg-gray-50'; ?>">Sort by Name</a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-gray-500 uppercase text-xs">
                        <th class="px-6 py-3">ID</th>
                        <th class="px-6 py-3">Farm Name</th>
                        <th class="px-6 py-3">Farmer</th>
                        <th class="px-6 py-3">Location</th>
                        <th class="px-6 py-3">Area (ha)</th>
                        <th class="px-6 py-3">Soil</th>
                        <th class="px-6 py-3">Irrigation</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($rows as $r): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-medium text-gray-900">#<?php echo $r['farm_id']; ?></td>
                            <td class="px-6 py-3 text-gray-700 font-medium"><?php echo e($r['farm_name']); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e(
                                ($r['farmer']['first_name'] ?? '') . ' ' . ($r['farmer']['last_name'] ?? ''),
                            ); ?></td>
                            <td class="px-6 py-3 text-gray-700 truncate max-w-xs"><?php echo e($r['location']); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo number_format(
                                $r['area_hectares'],
                                2,
                            ); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e(
                                $r['soil_type']['type_name'] ?? '—',
                            ); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e(
                                $r['irrigation_type']['type_name'] ?? '—',
                            ); ?></td>
                            <td class="px-6 py-3 text-right">
                                <a href="index.php?action=edit&id=<?php echo $r[
                                    'farm_id'
                                ]; ?>" class="text-blue-600 hover:underline text-sm">Edit</a>
                                <form method="POST" action="index.php?action=delete&id=<?php echo $r[
                                    'farm_id'
                                ]; ?>" class="inline-block">
                                    <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                                    <button type="submit" data-confirm="Delete this farm? Related planting records will also be affected." class="text-red-600 hover:underline text-sm ml-4">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total > 0): ?>
            <div class="px-6 py-4 border-t border-gray-100">
                <?php echo renderPager(
                    '/farms/index.php?' .
                        http_build_query(
                            array_filter([
                                'search' => $search,
                                'irrigation' => $irrFilter ?: '',
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
            <i class="fa-solid fa-seedling text-4xl mb-3 opacity-30"></i>
            <?php if ($search !== '' || $irrFilter > 0): ?>
                <p class="text-lg font-medium text-gray-700 mb-1">No farms match your filters</p>
                <p class="text-sm text-gray-500 mb-4">Try adjusting your search or irrigation type filter.</p>
                <a href="index.php" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                    <i class="fa-solid fa-xmark"></i> Clear Filters
                </a>
            <?php else: ?>
                <p class="text-lg font-medium text-gray-700 mb-1">No farms yet</p>
                <p class="text-sm text-gray-500 mb-4">Register a farm to start tracking crops and plantings. Each farm belongs to a farmer.</p>
                <a href="index.php?action=create" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition">
                    <i class="fa-solid fa-plus"></i> Add First Farm
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
