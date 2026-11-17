<?php
// models/Farmer.php
require_once __DIR__ . '/Model.php';
class Farmer extends Model
{
    protected string $table = 'farmer';
    protected string $primaryKey = 'farmer_id';
    protected string $columns = '*';

    /** Farmers with their farms (1:M) for the farmers-and-farms report. */
    public function allWithFarms(array $filters = [], array $options = []): array
    {
        return $this->db->select(
            'farmer',
            '*,farm(farm_name,location,area_hectares)',
            $filters,
            $options,
        );
    }

    public function search(string $term): array
    {
        $filters = [];
        foreach (preg_split('/\s+/', trim($term)) as $word) {
            if ($word === '') continue;
            $w = rawurlencode($word);
            $filters[] = 'or=(first_name.ilike.%25' . $w . '%25,last_name.ilike.%25' . $w . '%25,address.ilike.%25' . $w . '%25)';
        }
        return $this->all($filters);
    }
}
