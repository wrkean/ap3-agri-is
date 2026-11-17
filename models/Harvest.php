<?php
// models/Harvest.php
require_once __DIR__ . '/Model.php';
class Harvest extends Model
{
    protected string $table = 'harvest';
    protected string $primaryKey = 'harvest_id';
    protected string $columns = '*,quality_grade(grade_name),planting(farm(farm_name),crop(crop_id,variety))';

    /**
     * Search by quality grade label. The lookup is resolved to IDs first so
     * the filter still targets a base-table column on both backends.
     */
    public function search(string $term, string $gradeId = ''): array
    {
        $filters = [];
        if ($term !== '') {
            $grades = $this->db->select('quality_grade', 'quality_grade_id', [
                'grade_name=ilike.%25' . rawurlencode($term) . '%25',
            ]);
            $ids = array_map('intval', array_column($grades, 'quality_grade_id'));
            $filters[] = 'quality_grade_id=in.(' . implode(',', $ids ?: [0]) . ')';
        }
        if ($gradeId !== '') {
            $filters[] = 'quality_grade_id=eq.' . (int) $gradeId;
        }
        return $this->all($filters);
    }

    public function totalQuantity(): float
    {
        return $this->stats()['quantity'];
    }

    /** Total quantity, total value and quantity-weighted average price. */
    public function stats(): array
    {
        $cache = new Cache();
        $cached = $cache->get('harvest:stats');
        if ($cached !== null) {
            return $cached;
        }
        $rows = $this->db->select('harvest', 'quantity_kg,selling_price_per_kg');
        $quantity = 0.0;
        $value = 0.0;
        foreach ($rows as $row) {
            $qty = (float) ($row['quantity_kg'] ?? 0);
            $quantity += $qty;
            $value += $qty * (float) ($row['selling_price_per_kg'] ?? 0);
        }
        $result = [
            'quantity' => $quantity,
            'value' => $value,
            'average_price' => $quantity > 0 ? $value / $quantity : 0.0,
        ];
        $cache->set('harvest:stats', $result, 600);
        return $result;
    }
}
