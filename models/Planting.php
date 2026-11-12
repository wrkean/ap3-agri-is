<?php
// models/Planting.php
class Planting {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function all(array $filters = [], array $options = []): array {
        return $this->db->select('planting', '*,farm(farm_name),crop(crop_name)', $filters, $options);
    }

    public function find(int $id): ?array {
        return $this->db->find('planting', $id, 'planting_id');
    }

    public function create(array $data): array {
        return $this->db->insert('planting', $data);
    }

    public function update(int $id, array $data): array {
        return $this->db->update('planting', $data, ['planting_id=eq.' . $id]);
    }

    public function delete(int $id): array {
        return $this->db->delete('planting', ['planting_id=eq.' . $id]);
    }

    public function search(string $term, string $status = '', string $farmId = ''): array {
        $filters = [];
        if ($term !== '') {
            $filters[] = 'planting_date=ilike.%' . rawurlencode($term) . '%';
        }
        if ($status !== '') {
            $filters[] = 'status=eq.' . rawurlencode($status);
        }
        if ($farmId !== '') {
            $filters[] = 'farm_id=eq.' . (int)$farmId;
        }
        return $this->all($filters);
    }

    public function count(): int {
        return count($this->db->select('planting', 'planting_id'));
    }

    public function byStatus(): array {
        return $this->db->select('planting', 'status,count');
    }
}
