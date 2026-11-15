<?php
/**
 * Database abstraction for the Agriculture Information System.
 *
 * Uses the Supabase REST API (PostgREST) exclusively. The .env file must
 * contain SUPABASE_URL and SUPABASE_ANON_KEY. The optional server-only
 * SUPABASE_SERVICE_KEY is used only for the users lookup in auth.php.
 */
require_once __DIR__ . '/Supabase.php';

class Database
{
    private string $driver = 'supabase';
    private Supabase $supabase;

    /**
     * @param bool $useServiceKey Use the server-side SUPABASE_SERVICE_KEY instead
     *                            of the anon key. Server-only; never for browser-facing queries.
     */
    public function __construct(bool $useServiceKey = false)
    {
        $this->supabase = new Supabase($useServiceKey);
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function select(string $table, string $columns = '*', array $filters = [], array $options = [])
    {
        return $this->supabase->select($table, $columns, $filters, $options);
    }

    /** Insert a row. Returns the inserted row, or null on failure. */
    public function insert(string $table, array $data)
    {
        $result = $this->supabase->insert($table, $data);
        // Supabase returns an array of inserted rows; unwrap the first one.
        if (is_array($result) && isset($result[0]) && is_array($result[0])) {
            return $result[0];
        }
        return $result;
    }

    /** Update rows matching filters. Returns updated row(s), or null on failure. */
    public function update(string $table, array $data, array $filters)
    {
        return $this->supabase->update($table, $data, $filters);
    }

    /** Delete rows matching filters. Returns deleted row(s), or null on failure. */
    public function delete(string $table, array $filters)
    {
        return $this->supabase->delete($table, $filters);
    }

    public function find(string $table, int $id, string $pk = 'id')
    {
        return $this->supabase->find($table, $id, $pk);
    }
}
