<?php
// models/Crop.php
class Crop {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function all(array $filters = [], array $options = []): array {
        return $this->db->select('crop', '*', $filters, $options);
    }

    public function find(int $id): ?array {
        return $this->db->find('crop', $id, 'crop_id');
    }

    public function create(array $data): array {
        return $this->db->insert('crop', $data);
    }

    public function update(int $id, array $data): array {
        return $this->db->update('crop', $data, ['crop_id=eq.' . $id]);
    }

    public function delete(int $id): array {
        return $this->db->delete('crop', ['crop_id=eq.' . $id]);
    }

    public function search(string $term, string $category = '', string $season = ''): array {
        $filters = [];
        foreach (preg_split('/\s+/', trim($term)) as $word) {
            if ($word === '') continue;
            $w = rawurlencode($word);
            $filters[] = 'or=(crop_name.ilike.%' . $w . '%,variety.ilike.%' . $w . '%)';
        }
        if ($category !== '') {
            $filters[] = 'crop_category=eq.' . rawurlencode($category);
        }
        if ($season !== '') {
            $filters[] = 'recommended_season=eq.' . rawurlencode($season);
        }
        return $this->all($filters);
    }

    public function count(): int {
        return count($this->db->select('crop', 'crop_id'));
    }
}
