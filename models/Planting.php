<?php
// models/Planting.php
class Planting
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function all(array $filters = [], array $options = []): array
    {
        return $this->db->select(
            'planting',
            '*,farm(farm_name),crop(variety,species(crop_name)),planting_status(status_name)',
            $filters,
            $options,
        );
    }

    public function find(int $id): ?array
    {
        return $this->db->find('planting', $id, 'planting_id');
    }

    public function create(array $data): array
    {
        return $this->db->insert('planting', $data);
    }

    public function update(int $id, array $data): array
    {
        return $this->db->update('planting', $data, ['planting_id=eq.' . $id]);
    }

    public function delete(int $id): array
    {
        return $this->db->delete('planting', ['planting_id=eq.' . $id]);
    }

    public function search(string $term, string $statusId = '', string $farmId = ''): array
    {
        $filters = [];
        if ($term !== '') {
            $filters[] = 'planting_date=ilike.%25' . rawurlencode($term) . '%25';
        }
        if ($statusId !== '') {
            $filters[] = 'planting_status_id=eq.' . (int) $statusId;
        }
        if ($farmId !== '') {
            $filters[] = 'farm_id=eq.' . (int) $farmId;
        }
        return $this->all($filters);
    }

    public function count(): int
    {
        return count($this->db->select('planting', 'planting_id'));
    }

    /** Planting counts grouped by planting_status_id (counted in PHP because
     * Supabase/PostgREST disables aggregate functions by default). */
    public function byStatus(): array
    {
        $rows = $this->db->select('planting', 'planting_status_id');
        $counts = [];
        foreach ($rows as $row) {
            $id = (int) ($row['planting_status_id'] ?? 0);
            $counts[$id] = ($counts[$id] ?? 0) + 1;
        }
        $result = [];
        foreach ($counts as $id => $count) {
            $result[] = ['planting_status_id' => $id, 'count' => $count];
        }
        return $result;
    }
}
