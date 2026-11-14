<?php
/**
 * Database abstraction for the Agriculture Information System.
 *
 * Uses the Supabase REST API (PostgREST) exclusively. The .env file must
 * contain SUPABASE_URL and SUPABASE_ANON_KEY.
 */
require_once __DIR__ . '/Supabase.php';

class Database
{
    private string $driver = 'supabase';
    private Supabase $supabase;

    public function __construct()
    {
        $this->supabase = new Supabase();
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function select(string $table, string $columns = '*', array $filters = [], array $options = [])
    {
        return $this->supabase->select($table, $columns, $filters, $options);
    }

    public function insert(string $table, array $data)
    {
        $result = $this->supabase->insert($table, $data);
        // Supabase returns an array of inserted rows; unwrap the first one.
        if (is_array($result) && isset($result[0]) && is_array($result[0])) {
            return $result[0];
        }
        return $result;
    }

    public function update(string $table, array $data, array $filters)
    {
        return $this->supabase->update($table, $data, $filters);
    }

    public function delete(string $table, array $filters)
    {
        return $this->supabase->delete($table, $filters);
    }

    public function find(string $table, int $id, string $pk = 'id')
    {
        return $this->supabase->find($table, $id, $pk);
    }
}
