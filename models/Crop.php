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
        return $this->db->select(
            'crop',
            '*,species(crop_name,crop_category(category_name)),season(season_name)',
            $filters,
            $options,
        );
    }

    public function find(int $id): ?array
    {
        return $this->db->find('crop', $id, 'crop_id');
    }

    public function create(array $data): array
    {
        return $this->db->insert('crop', $data);
    }

    public function update(int $id, array $data): array
    {
        return $this->db->update('crop', $data, ['crop_id=eq.' . $id]);
    }

    public function delete(int $id): array
    {
        return $this->db->delete('crop', ['crop_id=eq.' . $id]);
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
            $w = '%' . rawurlencode($word) . '%';
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
            'crop_name=ilike.%' . rawurlencode($term) . '%',
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
