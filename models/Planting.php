<?php
// models/Planting.php
require_once __DIR__ . '/Model.php';
class Planting extends Model
{
    protected string $table = 'planting';
    protected string $primaryKey = 'planting_id';
    protected string $columns = '*,farm(farm_name),crop(variety,species(crop_name)),planting_status(status_name)';

    public function search(string $term, string $statusId = '', string $farmId = ''): array
    {
        $filters = [];
        if ($term !== '') {
            // planting_date is a date column, so only exact YYYY-MM-DD matches
            // can work; anything else simply matches nothing.
            if (isValidDate($term)) {
                $filters[] = 'planting_date=eq.' . $term;
            } else {
                $filters[] = 'planting_id=eq.-1';
            }
        }
        if ($statusId !== '') {
            $filters[] = 'planting_status_id=eq.' . (int) $statusId;
        }
        if ($farmId !== '') {
            $filters[] = 'farm_id=eq.' . (int) $farmId;
        }
        return $this->all($filters);
    }

    /** Planting counts grouped by planting_status_id (counted in PHP because
     * Supabase/PostgREST disables aggregate functions by default). */
    public function byStatus(): array
    {
        $cache = new Cache();
        $cached = $cache->get('planting:byStatus');
        if ($cached !== null) {
            return $cached;
        }
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
        $cache->set('planting:byStatus', $result, 600);
        return $result;
    }
}
