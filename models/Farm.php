<?php
// models/Farm.php
require_once __DIR__ . '/Model.php';
class Farm extends Model
{
    protected string $table = 'farm';
    protected string $primaryKey = 'farm_id';
    protected string $columns = '*,farmer(first_name,last_name),soil_type(type_name),irrigation_type(type_name)';

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

    public function byFarmer(int $farmerId): array
    {
        return $this->db->select('farm', '*', ['farmer_id=eq.' . $farmerId]);
    }
}
