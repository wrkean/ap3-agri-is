<?php
// api/search.php — JSON search endpoint for live search / auto-suggest
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/Database.php';

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

$sb = new Database();
$results = [];

switch ($module) {
    case 'farmers':
        $rows = $sb->select('farmer', 'farmer_id,first_name,last_name,address', [], ['limit' => 100]);
        foreach ($rows as $r) {
            $name = $r['first_name'] . ' ' . $r['last_name'];
            if (stripos($name, $query) !== false || stripos($r['address'] ?? '', $query) !== false) {
                $results[] = ['id' => $r['farmer_id'], 'label' => $name, 'sub' => $r['address'] ?? ''];
            }
        }
        break;
    case 'farms':
        $rows = $sb->select('farm', 'farm_id,farm_name,location', [], ['limit' => 100]);
        foreach ($rows as $r) {
            if (stripos($r['farm_name'], $query) !== false || stripos($r['location'] ?? '', $query) !== false) {
                $results[] = ['id' => $r['farm_id'], 'label' => $r['farm_name'], 'sub' => $r['location'] ?? ''];
            }
        }
        break;
    case 'crops':
        $rows = $sb->select('crop', 'crop_id,crop_name,variety,crop_category', [], ['limit' => 100]);
        foreach ($rows as $r) {
            if (stripos($r['crop_name'], $query) !== false || stripos($r['variety'] ?? '', $query) !== false) {
                $results[] = ['id' => $r['crop_id'], 'label' => $r['crop_name'], 'sub' => $r['variety'] . ' (' . $r['crop_category'] . ')'];
            }
        }
        break;
    case 'planting':
        $rows = $sb->select('planting', '*,farm(farm_name),crop(crop_name)', [], ['limit' => 100]);
        foreach ($rows as $r) {
            $label = '#' . $r['planting_id'] . ' — ' . ($r['farm']['farm_name'] ?? '?') . ' / ' . ($r['crop']['crop_name'] ?? '?');
            if (stripos($label, $query) !== false || stripos($r['status'] ?? '', $query) !== false) {
                $results[] = ['id' => $r['planting_id'], 'label' => $label, 'sub' => $r['status'] ?? ''];
            }
        }
        break;
    case 'harvest':
        $rows = $sb->select('harvest', '*,planting(planting_id,farm(farm_name),crop(crop_name))', [], ['limit' => 100]);
        foreach ($rows as $r) {
            $label = '#' . $r['harvest_id'] . ' — ' . ($r['planting']['farm']['farm_name'] ?? '?') . ' / ' . ($r['planting']['crop']['crop_name'] ?? '?');
            if (stripos($label, $query) !== false || stripos($r['quality_grade'] ?? '', $query) !== false) {
                $results[] = ['id' => $r['harvest_id'], 'label' => $label, 'sub' => $r['quality_grade'] ?? ''];
            }
        }
        break;
}

$results = array_slice($results, 0, $limit);
header('Content-Type: application/json');
echo json_encode(['results' => $results]);
