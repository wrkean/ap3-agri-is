<?php
// models/Farmer.php
class Farmer {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function all(array $filters = [], array $options = []): array {
        $cache = new Cache();
        if (empty($filters) && empty($options)) {
            $cached = $cache->get('farmer:all');
            if ($cached !== null) {
                return $cached;
            }
        }
        $rows = $this->db->select('farmer', '*', $filters, $options);
        if (empty($filters) && empty($options)) {
            $cache->set('farmer:all', $rows, 300);
        }
        return $rows;
    }

    /** Farmers with their farms (1:M) for the farmers-and-farms report. */
    public function allWithFarms(array $filters = [], array $options = []): array {
        return $this->db->select(
            'farmer',
            '*,farm(farm_name,location,area_hectares)',
            $filters,
            $options,
        );
    }

    public function find(int $id): ?array {
        return $this->db->find('farmer', $id, 'farmer_id');
    }

    public function create(array $data): array {
        $result = $this->db->insert('farmer', $data);
        (new Cache())->flush();
        return $result;
    }

    public function update(int $id, array $data): array {
        $result = $this->db->update('farmer', $data, ['farmer_id=eq.' . $id]);
        (new Cache())->flush();
        return $result;
    }

    public function delete(int $id): array {
        $result = $this->db->delete('farmer', ['farmer_id=eq.' . $id]);
        (new Cache())->flush();
        return $result;
    }

    public function search(string $term): array {
        $filters = [];
        foreach (preg_split('/\s+/', trim($term)) as $word) {
            if ($word === '') continue;
            $w = rawurlencode($word);
            $filters[] = 'or=(first_name.ilike.%25' . $w . '%25,last_name.ilike.%25' . $w . '%25,address.ilike.%25' . $w . '%25)';
        }
        return $this->all($filters);
    }

    public function count(): int {
        return count($this->all());
    }
}
