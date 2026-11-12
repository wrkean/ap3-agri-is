<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/crud.php';
requireAuth();

$db = new Database();
$cropModel = new Crop($db);
$action = $_GET['action'] ?? 'list';
$id = (int) ($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'species_id' => (int) ($_POST['species_id'] ?? 0),
        'variety' => trim($_POST['variety'] ?? ''),
        'growing_period_days' => (int) ($_POST['growing_period_days'] ?? 0),
        'season_id' => (int) ($_POST['season_id'] ?? 0),
    ];
    $errors = [];
    if (!$data['species_id']) {
        $errors[] = 'Crop is required.';
    }
    if (empty($data['variety'])) {
        $errors[] = 'Variety is required.';
    }
    if (!$data['growing_period_days']) {
        $errors[] = 'Growing period (days) is required.';
    }
    if (!$data['season_id']) {
        $errors[] = 'Recommended season is required.';
    }
    if ($errors) {
        flash('error', implode(' ', $errors));
        header('Location: crops/index.php' . ($action === 'edit' ? '?action=edit&id=' . $id : '?action=create'));
        exit();
    }
    if ($action === 'create') {
        $cropModel->create($data);
        flash('success', 'Crop created.');
        header('Location: crops/index.php');
        exit();
    }
    if ($action === 'edit' && $id > 0) {
        $cropModel->update($id, $data);
        flash('success', 'Crop updated.');
        header('Location: crops/index.php');
        exit();
    }
}

if ($action === 'delete' && $id > 0) {
    $plantings = $db->select('planting', 'planting_id', ['crop_id=eq.' . $id]);
    if (!empty($plantings)) {
        flash('error', 'Cannot delete crop: has planting records.');
    } else {
        $cropModel->delete($id);
        flash('success', 'Crop deleted.');
    }
    header('Location: crops/index.php');
    exit();
}

$pageTitle = 'Crops';

// Reference data for dropdowns
$speciesOptions = $db->select('species', 'species_id,crop_name,crop_category(category_name)', [], ['order' => 'crop_name.asc']);
$catOptions = $db->select('crop_category', 'crop_category_id,category_name', [], ['order' => 'crop_category_id.asc']);
$seasonOptions = $db->select('season', 'season_id,season_name', [], ['order' => 'season_id.asc']);

$search = trim($_GET['search'] ?? '');
$catFilter = (int) ($_GET['category'] ?? 0);
$seasonFilter = (int) ($_GET['season'] ?? 0);
$sort = $_GET['sort'] ?? 'crop_id';
$dir = $_GET['dir'] ?? 'asc';
$page = (int) ($_GET['page'] ?? 1);

$filters = [];
if ($search !== '') {
    foreach (preg_split('/\s+/', trim($search)) as $word) {
        if ($word === '') continue;
        $w = rawurlencode($word);
        $parts = ['variety.ilike.%' . $w . '%'];
        $speciesIds = $cropModel->speciesIdsMatching($word);
        if ($speciesIds) {
            $parts[] = 'species_id.in.(' . implode(',', $speciesIds) . ')';
        }
        $filters[] = 'or=(' . implode(',', $parts) . ')';
    }
}
if ($catFilter > 0) {
    $ids = $cropModel->speciesIdsByCategory($catFilter);
    $filters[] = 'species_id=in.(' . implode(',', $ids ?: [0]) . ')';
}
if ($seasonFilter > 0) {
    $filters[] = 'season_id=eq.' . $seasonFilter;
}

$countRows = $db->select('crop', 'crop_id', $filters);
$total = count($countRows);
$p = pagination($page, $total);
$rows = $cropModel->all(
    $filters,
    ['order' => $sort . '.' . $dir, 'limit' => $p['limit'], 'offset' => $p['offset']],
);

$editRow = null;
if ($action === 'edit' && $id > 0) {
    $editRow = $cropModel->find($id);
}
?>
<?php if ($action === 'create' || $action === 'edit'): ?>
    <div class="max-w-lg fade-in">
        <div class="bg-white p-6 rounded-lg border border-gray-200 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4"><?php echo $action === 'create'
                ? 'Add Crop'
                : 'Edit Crop'; ?></h2>
            <form method="POST" action="index.php?action=<?php
            echo $action;
            echo $id > 0 ? '&id=' . $id : '';
            ?>" class="space-y-4">
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Crop <span class="text-red-500">*</span></label>
                    <select name="species_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                        <option value="">Select crop</option>
                        <?php foreach ($speciesOptions as $o): ?><option value="<?php echo $o[
    'species_id'
]; ?>"<?php echo ($editRow['species_id'] ?? 0) == $o['species_id'] ? ' selected' : ''; ?>><?php echo e(
    $o['crop_name'] . ' (' . ($o['crop_category']['category_name'] ?? '') . ')',
); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Variety <span class="text-red-500">*</span></label>
                    <input type="text" name="variety" required value="<?php echo e(
                        $editRow['variety'] ?? ($_POST['variety'] ?? ''),
                    ); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Growing Period (days) <span class="text-red-500">*</span></label>
                        <input type="number" name="growing_period_days" required value="<?php echo e(
                            $editRow['growing_period_days'] ?? ($_POST['growing_period_days'] ?? ''),
                        ); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Recommended Season <span class="text-red-500">*</span></label>
                        <select name="season_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                            <option value="">Select season</option>
                            <?php foreach ($seasonOptions as $o): ?><option value="<?php echo $o[
    'season_id'
]; ?>"<?php echo ($editRow['season_id'] ?? 0) == $o['season_id'] ? ' selected' : ''; ?>><?php echo e(
    $o['season_name'],
); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition"><?php echo $action ===
                    'create'
                        ? 'Create Crop'
                        : 'Update Crop'; ?></button>
                    <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-100 transition">Cancel</a>
                </div>
            </form>
        </div>
        <a href="index.php" class="text-blue-600 hover:underline">&larr; Back to list</a>
    </div>
<?php else: ?>
    <div class="flex justify-between items-end mb-4 fade-in">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Crops</h1>
            <p class="text-gray-500 text-sm">Manage crop varieties</p>
        </div>
        <a href="index.php?action=create" class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition flex items-center gap-2"><i class="fa-solid fa-plus"></i> Add Crop</a>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <form method="GET" action="index.php" class="px-6 py-4 border-b border-gray-100 flex flex-col md:flex-row gap-4 items-end">
            <div class="relative w-full md:w-1/3">
                <i class="fa-solid fa-search absolute left-3 top-3 text-gray-400"></i>
                <input type="text" id="searchInput" name="search" value="<?php echo e(
                    $search,
                ); ?>" placeholder="Search crop name or variety..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
            </div>
            <div class="relative w-full md:w-1/3">
                <label class="block text-xs font-medium text-gray-500 mb-1">Category</label>
                <select id="categoryFilter" name="category" onchange="this.form.submit()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                    <option value="">All</option>
                    <?php foreach ($catOptions as $o): ?><option value="<?php echo $o[
    'crop_category_id'
]; ?>"<?php echo $catFilter === (int) $o['crop_category_id']
    ? ' selected'
    : ''; ?>><?php echo e($o['category_name']); ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="relative w-full md:w-1/3">
                <label class="block text-xs font-medium text-gray-500 mb-1">Season</label>
                <select id="seasonFilter" name="season" onchange="this.form.submit()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                    <option value="">All</option>
                    <?php foreach ($seasonOptions as $o): ?><option value="<?php echo $o[
    'season_id'
]; ?>"<?php echo $seasonFilter === (int) $o['season_id']
    ? ' selected'
    : ''; ?>><?php echo e($o['season_name']); ?></option><?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition flex items-center gap-2"><i class="fa-solid fa-search"></i> Search</button>
            <div class="flex gap-2">
                <a href="index.php?search=<?php echo rawurlencode($search); ?>&category=<?php echo $catFilter
    ?: ''; ?>&season=<?php echo $seasonFilter ?: ''; ?>&sort=crop_id&dir=asc" class="px-4 py-2 text-sm font-medium rounded transition <?php echo $sort === 'crop_id'
    ? 'bg-blue-50 text-blue-600'
    : 'border border-gray-200 text-gray-700 hover:bg-gray-50'; ?>">Sort by ID</a>
                <a href="index.php?search=<?php echo rawurlencode($search); ?>&category=<?php echo $catFilter
    ?: ''; ?>&season=<?php echo $seasonFilter
    ?: ''; ?>&sort=variety&dir=asc" class="px-4 py-2 text-sm font-medium rounded transition <?php echo $sort === 'variety'
    ? 'bg-blue-50 text-blue-600'
    : 'border border-gray-200 text-gray-700 hover:bg-gray-50'; ?>">Sort by Variety</a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-gray-500 uppercase text-xs">
                        <th class="px-6 py-3">ID</th>
                        <th class="px-6 py-3">Crop Name</th>
                        <th class="px-6 py-3">Variety</th>
                        <th class="px-6 py-3">Category</th>
                        <th class="px-6 py-3">Growing Period</th>
                        <th class="px-6 py-3">Season</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($rows as $r): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-medium text-gray-900">#<?php echo $r['crop_id']; ?></td>
                            <td class="px-6 py-3 text-gray-700 font-medium"><?php echo e(
                                $r['species']['crop_name'] ?? '—',
                            ); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e($r['variety']); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e(
                                $r['species']['crop_category']['category_name'] ?? '—',
                            ); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo $r['growing_period_days']; ?> days</td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e(
                                $r['season']['season_name'] ?? '—',
                            ); ?></td>
                            <td class="px-6 py-3 text-right">
                                <a href="index.php?action=edit&id=<?php echo $r[
                                    'crop_id'
                                ]; ?>" class="text-blue-600 hover:underline text-sm">Edit</a>
                                <a href="index.php?action=delete&id=<?php echo $r[
                                    'crop_id'
                                ]; ?>" class="text-red-600 hover:underline text-sm ml-4" data-confirm="Delete this crop? Related planting records will be affected.">Delete</a>
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
                                'category' => $catFilter ?: '',
                                'season' => $seasonFilter ?: '',
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
            <i class="fa-solid fa-carrot text-4xl mb-3 opacity-30"></i>
            <p>No crops found.</p>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
