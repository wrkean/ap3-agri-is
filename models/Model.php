<?php
// models/Model.php
if (!class_exists('Database')) {
    require_once __DIR__ . '/../includes/Database.php';
}
if (!class_exists('Cache')) {
    require_once __DIR__ . '/../includes/Cache.php';
}
abstract class Model
{
    protected Database $db;
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected string $columns = '*';

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function all(array $filters = [], array $options = []): array
    {
        $cache = new Cache();
        $cacheKey = $this->table . ':all';
        if (empty($filters) && empty($options)) {
            $cached = $cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }
        $rows = $this->db->select($this->table, $this->columns, $filters, $options);
        if (empty($filters) && empty($options)) {
            $cache->set($cacheKey, $rows, 1800);
        }
        return $rows;
    }

    public function find(int $id): ?array
    {
        return $this->db->find($this->table, $id, $this->primaryKey);
    }

    public function create(array $data): ?array
    {
        $result = $this->db->insert($this->table, $data);
        if ($result !== null) {
            (new Cache())->flush();
        }
        return $result;
    }

    public function update(int $id, array $data): ?array
    {
        $result = $this->db->update($this->table, $data, [$this->primaryKey . '=eq.' . $id]);
        if ($result !== null) {
            (new Cache())->flush();
        }
        return $result;
    }

    public function delete(int $id): ?array
    {
        $result = $this->db->delete($this->table, [$this->primaryKey . '=eq.' . $id]);
        if ($result !== null) {
            (new Cache())->flush();
        }
        return $result;
    }

    public function count(): int
    {
        return count($this->db->select($this->table, $this->primaryKey));
    }
}
