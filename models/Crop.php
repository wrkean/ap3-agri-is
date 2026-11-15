<?php
// models/Crop.php
class Crop
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Crop varieties with their species, category and season labels.
     * The nested select is resolved natively by Supabase/PostgREST.
     */
    public function all(array $filters = [], array $options = []): array
    {
        $cache = new Cache();
        // Only cache unfiltered list queries.
        if (empty($filters) && empty($options)) {
            $cached = $cache->get('crop:all');
            if ($cached !== null) {
                return $cached;
            }
        }
        $rows = $this->db->select(
            'crop',
            '*,species(crop_name,crop_category(category_name)),season(season_name)',
            $filters,
            $options,
        );
        if (empty($filters) && empty($options)) {
            $cache->set("crop:all", $rows, 1800);
        }
        return $rows;
    }

    public function find(int $id): ?array
    {
        return $this->db->find('crop', $id, 'crop_id');
    }

    public function create(array $data): ?array
    {
        $result = $this->db->insert('crop', $data);
        if ($result !== null) {
            (new Cache())->flush();
        }
        return $result;
    }

    public function update(int $id, array $data): ?array
    {
        $result = $this->db->update('crop', $data, ['crop_id=eq.' . $id]);
        if ($result !== null) {
            (new Cache())->flush();
        }
        return $result;
    }

    public function delete(int $id): ?array
    {
        $result = $this->db->delete('crop', ['crop_id=eq.' . $id]);
        if ($result !== null) {
            (new Cache())->flush();
        }
        return $result;
    }

    /**
     * Search by variety or species name, optionally filtered by category
     * and season IDs.
     */
    public function search(string $term, string $categoryId = '', string $seasonId = ''): array
    {
        $filters = [];
        foreach (preg_split('/\s+/', trim($term)) as $word) {
            if ($word === '') {
                continue;
            }
            $w = '%25' . rawurlencode($word) . '%25';
            $speciesIds = $this->speciesIdsMatching($word);
            $parts = ['variety.ilike.' . $w];
            if ($speciesIds) {
                $parts[] = 'species_id.in.(' . implode(',', $speciesIds) . ')';
            }
            $filters[] = 'or=(' . implode(',', $parts) . ')';
        }
        if ($categoryId !== '') {
            $ids = $this->speciesIdsByCategory((int) $categoryId);
            $filters[] = 'species_id=in.(' . implode(',', $ids ?: [0]) . ')';
        }
        if ($seasonId !== '') {
            $filters[] = 'season_id=eq.' . (int) $seasonId;
        }
        return $this->all($filters);
    }

    /** Species IDs whose crop name matches a free-text word. */
    public function speciesIdsMatching(string $term): array
    {
        $rows = $this->db->select('species', 'species_id', [
            'crop_name=ilike.%25' . rawurlencode($term) . '%25',
        ]);
        return array_map('intval', array_column($rows, 'species_id'));
    }

    /** Species IDs belonging to a crop category. */
    public function speciesIdsByCategory(int $categoryId): array
    {
        $rows = $this->db->select('species', 'species_id', [
            'crop_category_id=eq.' . $categoryId,
        ]);
        return array_map('intval', array_column($rows, 'species_id'));
    }

    /**
     * Map crop_id => "Species — Variety" for pages that cannot nest three
     * relation levels deep through the Supabase join.
     */
    public function labels(): array
    {
        $labels = [];
        foreach ($this->all() as $row) {
            $species = $row['species']['crop_name'] ?? '';
            $labels[(int) $row['crop_id']] = trim($species . ' — ' . ($row['variety'] ?? ''), ' —');
        }
        return $labels;
    }

    public function count(): int
    {
        return count($this->db->select('crop', 'crop_id'));
    }
}
