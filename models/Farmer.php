<?php
// models/Farmer.php
class Farmer {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function all(array $filters = [], array $options = []): array {
        return $this->db->select('farmer', '*', $filters, $options);
    }

    public function find(int $id): ?array {
        return $this->db->find('farmer', $id, 'farmer_id');
    }

    public function create(array $data): array {
        return $this->db->insert('farmer', $data);
    }

    public function update(int $id, array $data): array {
        return $this->db->update('farmer', $data, ['farmer_id=eq.' . $id]);
    }

    public function delete(int $id): array {
        return $this->db->delete('farmer', ['farmer_id=eq.' . $id]);
    }

    public function search(string $term): array {
        $filters = [];
        foreach (preg_split('/\s+/', trim($term)) as $word) {
            if ($word === '') continue;
            $w = rawurlencode($word);
            $filters[] = 'or=(first_name.ilike.%' . $w . '%,last_name.ilike.%' . $w . '%,address.ilike.%' . $w . '%)';
        }
        return $this->all($filters);
    }

    public function count(): int {
        return count($this->all());
    }
}
