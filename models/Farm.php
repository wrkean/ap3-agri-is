<?php
// models/Farm.php
class Farm
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function all(array $filters = [], array $options = []): array
    {
        $cache = new Cache();
        if (empty($filters) && empty($options)) {
            $cached = $cache->get('farm:all');
            if ($cached !== null) {
                return $cached;
            }
        }
        $rows = $this->db->select(
            'farm',
            '*,farmer(first_name,last_name),soil_type(type_name),irrigation_type(type_name)',
            $filters,
            $options,
        );
        if (empty($filters) && empty($options)) {
            $cache->set('farm:all', $rows, 300);
        }
        return $rows;
    }

    public function find(int $id): ?array
    {
        return $this->db->find('farm', $id, 'farm_id');
    }

    public function create(array $data): array
    {
        $result = $this->db->insert('farm', $data);
        (new Cache())->flush();
        return $result;
    }

    public function update(int $id, array $data): array
    {
        $result = $this->db->update('farm', $data, ['farm_id=eq.' . $id]);
        (new Cache())->flush();
        return $result;
    }

    public function delete(int $id): array
    {
        $result = $this->db->delete('farm', ['farm_id=eq.' . $id]);
        (new Cache())->flush();
        return $result;
    }

    public function search(string $term, string $irrigationId = ''): array
    {
        $filters = [];
        foreach (preg_split('/\s+/', trim($term)) as $word) {
            if ($word === '') {
                continue;
            }
            $w = rawurlencode($word);
            $filters[] = 'or=(farm_name.ilike.%25' . $w . '%25,location.ilike.%25' . $w . '%25)';
        }
        if ($irrigationId !== '') {
            $filters[] = 'irrigation_type_id=eq.' . (int) $irrigationId;
        }
        return $this->all($filters);
    }

    public function count(): int
    {
        return count($this->db->select('farm', 'farm_id'));
    }

    public function byFarmer(int $farmerId): array
    {
        return $this->db->select('farm', '*', ['farmer_id=eq.' . $farmerId]);
    }
}
