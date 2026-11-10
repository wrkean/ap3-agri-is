<?php
// models/Farm.php
class Farm {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function all(array $filters = [], array $options = []): array {
        return $this->db->select('farm', '*,farmer(first_name,last_name)', $filters, $options);
    }

    public function find(int $id): ?array {
        return $this->db->find('farm', $id, 'farm_id');
    }

    public function create(array $data): array {
        return $this->db->insert('farm', $data);
    }

    public function update(int $id, array $data): array {
        return $this->db->update('farm', $data, ['farm_id=eq.' . $id]);
    }

    public function delete(int $id): array {
        return $this->db->delete('farm', ['farm_id=eq.' . $id]);
    }

    public function search(string $term, string $irrigation = ''): array {
        $filters = [];
        foreach (preg_split('/\s+/', trim($term)) as $word) {
            if ($word === '') continue;
            $w = rawurlencode($word);
            $filters[] = 'or=(farm_name.ilike.%' . $w . '%,location.ilike.%' . $w . '%)';
        }
        if ($irrigation !== '') {
            $filters[] = 'irrigation_type=eq.' . rawurlencode($irrigation);
        }
        return $this->all($filters);
    }

    public function count(): int {
        return count($this->db->select('farm', 'farm_id'));
    }

    public function byFarmer(int $farmerId): array {
        return $this->db->select('farm', '*', ['farmer_id=eq.' . $farmerId]);
    }
}
