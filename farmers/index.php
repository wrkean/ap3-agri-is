<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/crud.php';
requireAuth();

$sb = new Database();
$action = $_GET['action'] ?? 'list';
$id = (int) ($_GET['id'] ?? 0);
$error = '';
$success = getFlash('success');
$validationError = getFlash('error');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'contact_number' => trim($_POST['contact_number'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'date_registered' => trim($_POST['date_registered'] ?? ''),
    ];

    $errors = [];
    if (empty($data['first_name'])) {
        $errors[] = 'First name is required.';
    }
    if (empty($data['last_name'])) {
        $errors[] = 'Last name is required.';
    }
    if (empty($data['date_registered'])) {
        $errors[] = 'Date registered is required.';
    } elseif (!DateTime::createFromFormat('Y-m-d', $data['date_registered'])) {
        $errors[] = 'Invalid date registered.';
    }

    if ($errors) {
        flash('error', implode(' ', $errors));
        header('Location: farmers/index.php' . ($action === 'edit' ? '?action=edit&id=' . $id : '?action=create'));
        exit();
    }

    if ($action === 'create') {
        $sb->insert('farmer', $data);
        flash('success', 'Farmer created successfully.');
        header('Location: farmers/index.php');
        exit();
    } elseif ($action === 'edit' && $id > 0) {
        $sb->update('farmer', $data, ['farmer_id=eq.' . $id]);
        flash('success', 'Farmer updated successfully.');
        header('Location: farmers/index.php');
        exit();
    }
}

// Handle delete
if ($action === 'delete' && $id > 0) {
    $farms = $sb->select('farm', 'farm_id', ['farmer_id=eq.' . $id]);
    if (!empty($farms)) {
        flash('error', 'Cannot delete farmer: has associated farm records.');
    } else {
        $sb->delete('farmer', ['farmer_id=eq.' . $id]);
        flash('success', 'Farmer deleted successfully.');
    }
    header('Location: farmers/index.php');
    exit();
}

$pageTitle = 'Farmers';

// List with search/filter/sort/pagination
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'farmer_id';
$dir = $_GET['dir'] ?? 'asc';
$page = (int) ($_GET['page'] ?? 1);

$filters = [];
if ($search !== '') {
    foreach (preg_split('/\s+/', trim($search)) as $word) {
        if ($word === '') continue;
        $w = rawurlencode($word);
        $filters[] = 'or=(first_name.ilike.%' . $w . '%,last_name.ilike.%' . $w . '%,address.ilike.%' . $w . '%)';
    }
}

// Get count for pagination
$countRows = $sb->select('farmer', 'farmer_id', $filters);
$total = count($countRows);
$p = pagination($page, $total);

$rows = $sb->select(
    'farmer',
    '*',
    $filters,
    [
        'order' => $sort . '.' . $dir,
        'limit' => $p['limit'],
        'offset' => $p['offset'],
    ],
);

// Edit form data
$editRow = null;
if ($action === 'edit' && $id > 0) {
    $editRow = $sb->find('farmer', $id, 'farmer_id');
}
?>
<?php if ($action === 'create' || $action === 'edit'): ?>
    <div class="max-w-lg fade-in">
        <div class="bg-white p-6 rounded-lg border border-gray-200 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">
                <?php echo $action === 'create' ? 'Add Farmer' : 'Edit Farmer'; ?>
            </h2>
            <form method="POST" action="index.php?action=<?php
            echo $action;
            echo $id > 0 ? '&id=' . $id : '';
            ?>" class="space-y-4">
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                        <input type="text" name="first_name" required value="<?php echo e(
                            $editRow['first_name'] ?? ($_POST['first_name'] ?? ''),
                        ); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                        <input type="text" name="last_name" required value="<?php echo e(
                            $editRow['last_name'] ?? ($_POST['last_name'] ?? ''),
                        ); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                    <input type="tel" name="contact_number" value="<?php echo e(
                        $editRow['contact_number'] ?? ($_POST['contact_number'] ?? ''),
                    ); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none" placeholder="09171234567">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <textarea name="address" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none"><?php echo e(
                        $editRow['address'] ?? ($_POST['address'] ?? ''),
                    ); ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date Registered <span class="text-red-500">*</span></label>
                    <input type="date" name="date_registered" required value="<?php echo e(
                        $editRow['date_registered'] ?? ($_POST['date_registered'] ?? date('Y-m-d')),
                    ); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition"><?php echo $action ===
                    'create'
                        ? 'Create Farmer'
                        : 'Update Farmer'; ?></button>
                    <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-100 transition">Cancel</a>
                </div>
            </form>
        </div>
        <a href="index.php" class="text-blue-600 hover:underline">&larr; Back to list</a>
    </div>
<?php else: ?>
    <div class="flex justify-between items-end mb-4 fade-in">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Farmers</h1>
            <p class="text-gray-500 text-sm">Manage farmer information</p>
        </div>
        <a href="index.php?action=create" class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Add Farmer
        </a>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <form method="GET" action="index.php" class="px-6 py-4 border-b border-gray-100 flex flex-col md:flex-row gap-4 items-end">
            <div class="relative w-full md:w-1/2">
                <i class="fa-solid fa-search absolute left-3 top-3 text-gray-400"></i>
                <input type="text" id="searchInput" name="search" value="<?php echo e(
                    $search,
                ); ?>" placeholder="Search by name or address..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition flex items-center gap-2"><i class="fa-solid fa-search"></i> Search</button>
            <div class="flex gap-2">
                <a href="index.php?search=<?php echo rawurlencode(
                    $search,
                ); ?>&sort=farmer_id&dir=asc" class="px-4 py-2 text-sm font-medium rounded transition <?php echo $sort ===
'farmer_id'
    ? 'bg-blue-50 text-blue-600'
    : 'border border-gray-200 text-gray-700 hover:bg-gray-50'; ?>">Sort by ID</a>
                <a href="index.php?search=<?php echo rawurlencode(
                    $search,
                ); ?>&sort=first_name&dir=asc" class="px-4 py-2 text-sm font-medium rounded transition <?php echo $sort ===
'first_name'
    ? 'bg-blue-50 text-blue-600'
    : 'border border-gray-200 text-gray-700 hover:bg-gray-50'; ?>">Sort by Name</a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-gray-500 uppercase text-xs">
                        <th class="px-6 py-3">ID</th>
                        <th class="px-6 py-3">Name</th>
                        <th class="px-6 py-3">Contact</th>
                        <th class="px-6 py-3">Address</th>
                        <th class="px-6 py-3">Registered</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($rows as $r): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-medium text-gray-900">#<?php echo $r['farmer_id']; ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e(
                                $r['first_name'] . ' ' . $r['last_name'],
                            ); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e($r['contact_number'] ?? '—'); ?></td>
                            <td class="px-6 py-3 text-gray-700 truncate max-w-xs"><?php echo e(
                                $r['address'] ?? '—',
                            ); ?></td>
                            <td class="px-6 py-3 text-gray-700"><?php echo e($r['date_registered'] ?? '—'); ?></td>
                            <td class="px-6 py-3 text-right">
                                <a href="index.php?action=edit&id=<?php echo $r[
                                    'farmer_id'
                                ]; ?>" class="text-blue-600 hover:underline text-sm">Edit</a>
                                <a href="index.php?action=delete&id=<?php echo $r[
                                    'farmer_id'
                                ]; ?>" class="text-red-600 hover:underline text-sm ml-4" data-confirm="Delete this farmer? This cannot be undone.">Delete</a>
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
                        http_build_query(array_filter(['search' => $search, 'sort' => $sort, 'dir' => $dir])),
                    $p['page'],
                    $p['pages'],
                ); ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($total === 0): ?>
        <div class="text-center py-12 text-gray-500">
            <i class="fa-solid fa-users text-4xl mb-3 opacity-30"></i>
            <p>No farmers found.</p>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
