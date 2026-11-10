<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/crud.php';
requireAuth();

$sb = new Supabase();
$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'crop_name'           => trim($_POST['crop_name'] ?? ''),
        'variety'             => trim($_POST['variety'] ?? ''),
        'crop_category'       => trim($_POST['crop_category'] ?? ''),
        'growing_period_days' => (int)($_POST['growing_period_days'] ?? 0),
        'recommended_season'  => trim($_POST['recommended_season'] ?? ''),
    ];
    $errors = [];
    if (empty($data['crop_name'])) $errors[] = 'Crop name is required.';
    if (empty($data['variety'])) $errors[] = 'Variety is required.';
    if (empty($data['crop_category'])) $errors[] = 'Category is required.';
    if (!$data['growing_period_days']) $errors[] = 'Growing period (days) is required.';
    if (empty($data['recommended_season'])) $errors[] = 'Recommended season is required.';
    if ($errors) { flash('error', implode(' ', $errors)); header('Location: crops/index.php' . ($action === 'edit' ? '?action=edit&id=' . $id : '?action=create')); exit; }
    if ($action === 'create') { $sb->insert('crop', $data); flash('success', 'Crop created.'); header('Location: crops/index.php'); exit; }
    if ($action === 'edit' && $id > 0) { $sb->update('crop', $data, ['crop_id=eq.' . $id]); flash('success', 'Crop updated.'); header('Location: crops/index.php'); exit; }
}

if ($action === 'delete' && $id > 0) {
    $plantings = $sb->select('planting', 'planting_id', ['crop_id=eq.' . $id]);
    if (!empty($plantings)) flash('error', 'Cannot delete crop: has planting records.');
    else { $sb->delete('crop', ['crop_id=eq.' . $id]); flash('success', 'Crop deleted.'); }
    header('Location: crops/index.php'); exit;
}

$pageTitle = 'Crops';

$search = trim($_GET['search'] ?? '');
$catFilter = $_GET['category'] ?? '';
$seasonFilter = $_GET['season'] ?? '';
$sort = $_GET['sort'] ?? 'crop_id';
$dir = $_GET['dir'] ?? 'asc';
$page = (int)($_GET['page'] ?? 1);

$filters = [];
if ($search !== '') { $filters[] = 'crop_name=ilike.%' . rawurlencode($search) . '%'; $filters[] = 'variety=ilike.%' . rawurlencode($search) . '%'; }
if ($catFilter !== '') { $filters[] = 'crop_category=eq.' . rawurlencode($catFilter); }
if ($seasonFilter !== '') { $filters[] = 'recommended_season=eq.' . rawurlencode($seasonFilter); }

$countRows = $sb->select('crop', 'crop_id', $filters);
$total = count($countRows);
$p = pagination($page, $total);
$rows = $sb->select('crop', '*', array_merge($filters, ['order' => $sort . '.' . $dir, 'limit' => $p['limit'], 'offset' => $p['offset']]));

$editRow = null;
if ($action === 'edit' && $id > 0) { $editRow = $sb->find('crop', $id, 'crop_id'); }

$catOptions = ['Cereal', 'Vegetable', 'Fruit', 'Legume', 'Root Crop'];
$seasonOptions = ['Wet Season', 'Dry Season', 'Year Round'];
?>
<h1 class="text-2xl font-bold text-gray-800 mb-6 fade-in">Crops</h1>

<?php if ($action === 'create' || $action === 'edit'): ?>
    <div class="max-w-lg fade-in">
        <div class="bg-white p-6 rounded-lg border border-gray-200 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4"><?php echo $action === 'create' ? 'Add Crop' : 'Edit Crop'; ?></h2>
            <form method="POST" action="index.php?action=<?php echo $action; ?><?php echo $id > 0 ? '&id=' . $id : ''; ?>" class="space-y-4">
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Crop Name <span class="text-red-500">*</span></label>
                    <input type="text" name="crop_name" required value="<?php echo e($editRow['crop_name'] ?? ($_POST['crop_name'] ?? '')); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Variety <span class="text-red-500">*</span></label>
                    <input type="text" name="variety" required value="<?php echo e($editRow['variety'] ?? ($_POST['variety'] ?? '')); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                    <select name="crop_category" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                        <?php foreach ($catOptions as $o): ?><option value="<?php echo e($o); ?>"<?php echo ($editRow['crop_category'] ?? '') == $o ? ' selected' : ''; ?>><?php echo e($o); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Growing Period (days) <span class="text-red-500">*</span></label>
                        <input type="number" name="growing_period_days" required value="<?php echo e($editRow['growing_period_days'] ?? ($_POST['growing_period_days'] ?? '')); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Recommended Season <span class="text-red-500">*</span></label>
                        <select name="recommended_season" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                            <?php foreach ($seasonOptions as $o): ?><option value="<?php echo e($o); ?>"<?php echo ($editRow['recommended_season'] ?? '') == $o ? ' selected' : ''; ?>><?php echo e($o); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition"><?php echo $action === 'create' ? 'Create Crop' : 'Update Crop'; ?></button>
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
            <p class="text-gray-500 text-sm">Manage crop master data</p>
        </div>
        <a href="index.php?action=create" class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition flex items-center gap-2"><i class="fa-solid fa-plus"></i> Add Crop</a>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex flex-col md:flex-row gap-4 items-end">
            <div class="relative w-full md:w-1/3">
                <i class="fa-solid fa-search absolute left-3 top-3 text-gray-400"></i>
                <input type="text" id="searchInput" value="<?php echo e($search); ?>" placeholder="Search crop name or variety..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
            </div>
            <div class="relative w-full md:w-1/3">
                <label class="block text-xs font-medium text-gray-500 mb-1">Category</label>
                <select id="categoryFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                    <option value="">All</option>
                    <?php foreach ($catOptions as $o): ?><option value="<?php echo e($o); ?>"<?php echo $catFilter === $o ? ' selected' : ''; ?>><?php echo e($o); ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="relative w-full md:w-1/3">
                <label class="block text-xs font-medium text-gray-500 mb-1">Season</label>
                <select id="seasonFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                    <option value="">All</option>
                    <?php foreach ($seasonOptions as $o): ?><option value="<?php echo e($o); ?>"<?php echo $seasonFilter === $o ? ' selected' : ''; ?>><?php echo e($o); ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="flex gap-2">
                <a href="index.php?search=<?php echo rawurlencode($search); ?>&category=<?php echo rawurlencode($catFilter); ?>&season=<?php echo rawurlencode($seasonFilter); ?>&sort=crop_id&dir=asc" class="px-4 py-2 bg-blue-50 text-blue-600 text-sm font-medium rounded hover:bg-blue-100 transition">Sort by ID</a>
                <a href="index.php?search=<?php echo rawurlencode($search); ?>&category=<?php echo rawurlencode($catFilter); ?>&season=<?php echo rawurlencode($seasonFilter); ?>&sort=crop_name&dir=asc" class="px-4 py-2 border border-gray-200 text-gray-700 text-sm font-medium rounded hover:bg-gray-50 transition">Sort by Name</a>
            </div>
        </div>

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
                            <td class="px-6 py-3 text-gray-700 font-medium"><?php echo e($r['crop_name']); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e($r['variety']); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e($r['crop_category']); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo $r['growing_period_days']; ?> days</td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e($r['recommended_season']); ?></td>
                            <td class="px-6 py-3 text-right">
                                <a href="index.php?action=edit&id=<?php echo $r['crop_id']; ?>" class="text-blue-600 hover:underline text-sm">Edit</a>
                                <a href="index.php?action=delete&id=<?php echo $r['crop_id']; ?>" class="text-red-600 hover:underline text-sm ml-4" data-confirm="Delete this crop? Related planting records will be affected.">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total > 0): ?>
            <div class="px-6 py-4 border-t border-gray-100">
                <?php echo renderPager($_SERVER['PHP_SELF'] . '?' . http_build_query(array_filter(['search' => $search, 'category' => $catFilter, 'season' => $seasonFilter, 'sort' => $sort, 'dir' => $dir])), $p['page'], $p['pages']); ?>
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