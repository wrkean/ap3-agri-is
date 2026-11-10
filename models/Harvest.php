<?php
// models/Harvest.php
class Harvest {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function all(array $filters = [], array $options = []): array {
        return $this->db->select('harvest', '*,planting(planting_id,farm(farm_name),crop(crop_name))', $filters, $options);
    }

    public function find(int $id): ?array {
        return $this->db->find('harvest', $id, 'harvest_id');
    }

    public function create(array $data): array {
        return $this->db->insert('harvest', $data);
    }

    public function update(int $id, array $data): array {
        return $this->db->update('harvest', $data, ['harvest_id=eq.' . $id]);
    }

    public function delete(int $id): array {
        return $this->db->delete('harvest', ['harvest_id=eq.' . $id]);
    }

    public function search(string $term, string $grade = ''): array {
        $filters = [];
        if ($term !== '') {
            $filters[] = 'quality_grade=ilike.%' . rawurlencode($term) . '%';
        }
        if ($grade !== '') {
            $filters[] = 'quality_grade=eq.' . rawurlencode($grade);
        }
        return $this->all($filters);
    }

    public function count(): int {
        return count($this->db->select('harvest', 'harvest_id'));
    }

    public function totalQuantity(): float {
        $rows = $this->db->select('harvest', 'quantity_kg');
        $total = 0;
        foreach ($rows as $r) {
            $total += (float)($r['quantity_kg'] ?? 0);
        }
        return $total;
    }
}
