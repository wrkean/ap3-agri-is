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
 *
 * Server-only users lookup (never used by normal queries):
 *   $sb = new Supabase(true); // uses SUPABASE_SERVICE_KEY instead of the anon key
 */

class Supabase
{
    private string $url;
    private string $key;
    private bool $useServiceKey;
    private array $headers;

    /** Last error message from request(), or null when the last call succeeded. */
    public ?string $lastError = null;

    /**
     * @param bool $useServiceKey When true, use the server-side SUPABASE_SERVICE_KEY
     *                            instead of the anon key. Server-only; never expose it
     *                            to the browser. If the service key is missing this fails
     *                            closed (empty key) instead of falling back to anon access.
     */
    public function __construct(bool $useServiceKey = false)
    {
        $this->loadEnv();
        $this->useServiceKey = $useServiceKey;
        $this->url = rtrim($_ENV['SUPABASE_URL'] ?? (getenv('SUPABASE_URL') ?? ''), '/');
        $anonKey = $_ENV['SUPABASE_ANON_KEY'] ?? (getenv('SUPABASE_ANON_KEY') ?? '');
        $serviceKey = $_ENV['SUPABASE_SERVICE_KEY'] ?? (getenv('SUPABASE_SERVICE_KEY') ?? '');

        if ($useServiceKey) {
            $this->key = $serviceKey;
            if (empty($this->key) || empty($this->url)) {
                error_log('Supabase: SUPABASE_SERVICE_KEY is not set; server query denied.');
            }
        } else {
            $this->key = $anonKey;
            if (empty($this->key) || empty($this->url)) {
                // Don't die here; let the caller handle missing credentials gracefully.
                error_log('Supabase: SUPABASE_URL or SUPABASE_ANON_KEY is not set.');
            }
        }

        $this->headers = [
            'apikey: ' . $this->key,
            'Authorization: Bearer ' . $this->key,
            'Content-Type: application/json',
            'Prefer: return=representation',
        ];
    }

    /**
     * Load key=value lines from .env into $_ENV if not already set.
     * When APP_ENV=test and a readable .env.test exists, it is loaded instead.
     */
    private function loadEnv(): void
    {
        $envFile = __DIR__ . '/../.env';
        $testFile = __DIR__ . '/../.env.test';
        $exampleFile = __DIR__ . '/../.env.example';
        if (getenv('APP_ENV') === 'test' && is_readable($testFile)) {
            $file = $testFile;
        } else {
            $file = file_exists($envFile) ? $envFile : $exampleFile ?? null;
        }
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
     * @return array|null Decoded JSON response, [] for an empty (204-style) response,
     *                    or null on failure (see $this->lastError). Never returns
     *                    [] to signal an HTTP error.
     */
    public function request(string $method, string $path, array $query = null, array $body = null)
    {
        $this->lastError = null;
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
            $this->lastError = "Supabase cURL error: $error";
            error_log($this->lastError);
            return null;
        }

        if ($httpCode >= 400) {
            $this->lastError = "Supabase HTTP $httpCode for $method $url: $response";
            error_log($this->lastError);
            return null;
        }

        if ($response === '' || $response === 'null') {
            // Empty (204-style) response is not an error.
            return [];
        }

        $decoded = json_decode($response, true);
        if ($decoded === null) {
            $this->lastError = "Supabase returned invalid JSON for $method $url: $response";
            error_log($this->lastError);
            return null;
        }
        return $decoded;
    }

    /** SELECT rows from a table. Returns [] on failure (see lastError). */
    public function select(string $table, string $columns = '*', array $filters = [], array $options = [])
    {
        $query = array_merge(['select' => $columns], $filters, $options);
        $rows = $this->request('GET', $table, $query);
        return $rows ?? [];
    }

    /** INSERT a row. Returns the inserted row(s), or null on failure. */
    public function insert(string $table, array $data)
    {
        return $this->request('POST', $table, null, $data);
    }

    /** UPDATE rows matching filters. Returns updated row(s), or null on failure. */
    public function update(string $table, array $data, array $filters)
    {
        return $this->request('PATCH', $table, $filters, $data);
    }

    /** DELETE rows matching filters. Returns deleted row(s), or null on failure. */
    public function delete(string $table, array $filters)
    {
        return $this->request('DELETE', $table, $filters);
    }

    /** Get a single row by primary key, or null on failure / not found. */
    public function find(string $table, int $id, string $pk = 'id')
    {
        $rows = $this->select($table, '*', ["$pk=eq.$id"], ['limit' => 1]);
        return $rows[0] ?? null;
    }
}
