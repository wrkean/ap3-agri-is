<?php
/**
 * Supabase REST helper for the Agriculture Information System.
 *
 * Calls the Supabase REST API (PostgREST) from PHP using cURL.
 * Reads SUPABASE_URL and SUPABASE_ANON_KEY from .env via loadEnv().
 *
 * Usage:
 *   $sb = new Supabase();
 *   $rows = $sb->select('crop', '*', 'crop_id=eq.1');
 *   $sb->insert('crop', ['crop_name'=>'Rice', ...]);
 *   $sb->update('crop', ['variety'=>'New'], 'crop_id=eq.1');
 *   $sb->delete('crop', 'crop_id=eq.1');
 */

class Supabase {
    private string $url;
    private string $anonKey;
    private array $headers;

    public function __construct() {
        $this->loadEnv();
        $this->url = rtrim($_ENV['SUPABASE_URL'] ?? getenv('SUPABASE_URL') ?? '', '/');
        $this->anonKey = $_ENV['SUPABASE_ANON_KEY'] ?? getenv('SUPABASE_ANON_KEY') ?? '';

        if (empty($this->url) || empty($this->anonKey)) {
            // Don't die here; let the caller handle missing credentials gracefully.
            error_log('Supabase: SUPABASE_URL or SUPABASE_ANON_KEY is not set.');
        }

        $this->headers = [
            'apikey: ' . $this->anonKey,
            'Authorization: Bearer ' . $this->anonKey,
            'Content-Type: application/json',
            'Prefer: return=representation',
        ];
    }

    /**
     * Load key=value lines from .env into $_ENV if not already set.
     */
    private function loadEnv(): void {
        $envFile = __DIR__ . '/../.env';
        $exampleFile = __DIR__ . '/../.env.example';
        $file = file_exists($envFile) ? $envFile : ($exampleFile ?? null);
        if (!$file || !is_readable($file)) {
            return;
        }
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, '#') === 0 || strpos($line, '=') === false) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Strip surrounding quotes
            if (preg_match('/^"(.*)"$/', $value, $m)) {
                $value = $m[1];
            } elseif (preg_match("/^'(.*)'$/", $value, $m)) {
                $value = $m[1];
            }
            if (!isset($_ENV[$key]) && !getenv($key)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }

    /**
     * Make a request to the Supabase REST API.
     *
     * @param string $method  HTTP method (GET, POST, PATCH, DELETE)
     * @param string $path    Table or endpoint path, e.g. 'crop' or 'rpc/function_name'
     * @param array|null $query Query parameters (filters, select, order, limit, offset)
     * @param array|null $body  JSON body for POST/PATCH
     * @return array|false Decoded JSON response, or false on error
     */
    public function request(string $method, string $path, array $query = null, array $body = null) {
        $url = $this->url . '/rest/v1/' . ltrim($path, '/');

        if ($query) {
            $parts = [];
            foreach ($query as $key => $value) {
                if (is_int($key)) {
                    // Raw filter string (already URL-encoded by the caller)
                    $parts[] = $value;
                } else {
                    $parts[] = $key . '=' . $value;
                }
            }
            $url .= '?' . implode('&', $parts);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // ok for school demo; enable in prod

        if ($body && in_array($method, ['POST', 'PATCH', 'PUT'], true)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("Supabase cURL error: $error");
            return [];
        }

        if ($httpCode >= 400) {
            error_log("Supabase HTTP $httpCode for $method $url: $response");
            return [];
        }

        if ($response === '' || $response === 'null') {
            return [];
        }

        $decoded = json_decode($response, true);
        return $decoded === null ? [] : $decoded;
    }

    /** SELECT rows from a table. */
    public function select(string $table, string $columns = '*', array $filters = [], array $options = []) {
        $query = array_merge(['select' => $columns], $filters, $options);
        return $this->request('GET', $table, $query);
    }

    /** INSERT a row. Returns the inserted row(s). */
    public function insert(string $table, array $data) {
        return $this->request('POST', $table, null, $data);
    }

    /** UPDATE rows matching filters. Returns updated row(s). */
    public function update(string $table, array $data, array $filters) {
        return $this->request('PATCH', $table, $filters, $data);
    }

    /** DELETE rows matching filters. */
    public function delete(string $table, array $filters) {
        return $this->request('DELETE', $table, $filters);
    }

    /** Get a single row by primary key. */
    public function find(string $table, int $id, string $pk = 'id') {
        $rows = $this->select($table, '*', ["$pk=eq.$id"], ['limit' => 1]);
        return $rows[0] ?? null;
    }
}
