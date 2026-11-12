<?php
// models/Harvest.php
class Harvest
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function all(array $filters = [], array $options = []): array
    {
        return $this->db->select(
            'harvest',
            '*,quality_grade(grade_name),planting(farm(farm_name),crop(crop_id,variety))',
            $filters,
            $options,
        );
    }

    public function find(int $id): ?array
    {
        return $this->db->find('harvest', $id, 'harvest_id');
    }

    public function create(array $data): array
    {
        return $this->db->insert('harvest', $data);
    }

    public function update(int $id, array $data): array
    {
        return $this->db->update('harvest', $data, ['harvest_id=eq.' . $id]);
    }

    public function delete(int $id): array
    {
        return $this->db->delete('harvest', ['harvest_id=eq.' . $id]);
    }

    /**
     * Search by quality grade label. The lookup is resolved to IDs first so
     * the filter still targets a base-table column on both backends.
     */
    public function search(string $term, string $gradeId = ''): array
    {
        $filters = [];
        if ($term !== '') {
            $grades = $this->db->select('quality_grade', 'quality_grade_id', [
                'grade_name=ilike.%' . rawurlencode($term) . '%',
            ]);
            $ids = array_map('intval', array_column($grades, 'quality_grade_id'));
            $filters[] = 'quality_grade_id=in.(' . implode(',', $ids ?: [0]) . ')';
        }
        if ($gradeId !== '') {
            $filters[] = 'quality_grade_id=eq.' . (int) $gradeId;
        }
        return $this->all($filters);
    }

    public function count(): int
    {
        return count($this->db->select('harvest', 'harvest_id'));
    }

    public function totalQuantity(): float
    {
        return $this->stats()['quantity'];
    }

    /** Total quantity, total value and quantity-weighted average price. */
    public function stats(): array
    {
        $rows = $this->db->select('harvest', 'quantity_kg,selling_price_per_kg');
        $quantity = 0.0;
        $value = 0.0;
        foreach ($rows as $row) {
            $qty = (float) ($row['quantity_kg'] ?? 0);
            $quantity += $qty;
            $value += $qty * (float) ($row['selling_price_per_kg'] ?? 0);
        }
        return [
            'quantity' => $quantity,
            'value' => $value,
            'average_price' => $quantity > 0 ? $value / $quantity : 0.0,
        ];
    }
}
