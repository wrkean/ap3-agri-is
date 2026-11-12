<?php
// api/search.php — JSON search endpoint for live search / auto-suggest
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/models.php';

// Only allow authenticated users
if (!isLoggedIn()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$module = $_GET['module'] ?? '';
$query = trim($_GET['q'] ?? '');
$limit = (int)($_GET['limit'] ?? 8);

if (empty($module) || empty($query)) {
    echo json_encode(['results' => []]);
    exit;
}

$db = new Database();
$farmerModel = new Farmer($db);
$farmModel = new Farm($db);
$cropModel = new Crop($db);
$plantingModel = new Planting($db);
$harvestModel = new Harvest($db);
$results = [];
$cropLabels = $cropModel->labels();

switch ($module) {
    case 'farmers':
        $rows = $farmerModel->all([], ['limit' => 100]);
        foreach ($rows as $r) {
            $name = $r['first_name'] . ' ' . $r['last_name'];
            if (stripos($name, $query) !== false || stripos($r['address'] ?? '', $query) !== false) {
                $results[] = ['id' => $r['farmer_id'], 'label' => $name, 'sub' => $r['address'] ?? ''];
            }
        }
        break;
    case 'farms':
        $rows = $farmModel->all([], ['limit' => 100]);
        foreach ($rows as $r) {
            if (stripos($r['farm_name'], $query) !== false || stripos($r['location'] ?? '', $query) !== false) {
                $results[] = ['id' => $r['farm_id'], 'label' => $r['farm_name'], 'sub' => $r['location'] ?? ''];
            }
        }
        break;
    case 'crops':
        $rows = $cropModel->all([], ['limit' => 100]);
        foreach ($rows as $r) {
            $cropName = $r['species']['crop_name'] ?? '';
            $category = $r['species']['crop_category']['category_name'] ?? '';
            if (stripos($cropName, $query) !== false || stripos($r['variety'] ?? '', $query) !== false) {
                $results[] = [
                    'id' => $r['crop_id'],
                    'label' => $cropName,
                    'sub' => $r['variety'] . ' (' . $category . ')',
                ];
            }
        }
        break;
    case 'planting':
        $rows = $plantingModel->all([], ['limit' => 100]);
        foreach ($rows as $r) {
            $cropName = $r['crop']['species']['crop_name'] ?? '?';
            $status = $r['planting_status']['status_name'] ?? '';
            $label = '#' . $r['planting_id'] . ' — ' . ($r['farm']['farm_name'] ?? '?') . ' / ' . $cropName;
            if (stripos($label, $query) !== false || stripos($status, $query) !== false) {
                $results[] = ['id' => $r['planting_id'], 'label' => $label, 'sub' => $status];
            }
        }
        break;
    case 'harvest':
        $rows = $harvestModel->all([], ['limit' => 100]);
        foreach ($rows as $r) {
            $cropId = (int) ($r['planting']['crop']['crop_id'] ?? 0);
            $cropLabel = $cropLabels[$cropId] ?? '?';
            $grade = $r['quality_grade']['grade_name'] ?? '';
            $label = '#' . $r['harvest_id'] . ' — ' . ($r['planting']['farm']['farm_name'] ?? '?') . ' / ' . $cropLabel;
            if (stripos($label, $query) !== false || stripos($grade, $query) !== false) {
                $results[] = ['id' => $r['harvest_id'], 'label' => $label, 'sub' => $grade];
            }
        }
        break;
}

$results = array_slice($results, 0, $limit);
header('Content-Type: application/json');
echo json_encode(['results' => $results]);
