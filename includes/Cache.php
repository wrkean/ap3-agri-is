<?php
/**
 * Simple file-based cache for the Agriculture Information System.
 *
 * Stores JSON-encoded values in the system temp directory with TTL-based
 * expiration. Gracefully degrades to no-op if the cache directory is not
 * writable (e.g. read-only filesystems).
 */
class Cache
{
    private string $dir;
    private int $defaultTtl;

    public function __construct(int $defaultTtl = 300)
    {
        $this->dir = (sys_get_temp_dir() ?: '/tmp') . '/ap3-agri-cache';
        $this->defaultTtl = $defaultTtl;

        // Disable cache in test mode or when explicitly requested.
        if (getenv('CACHE_DISABLED') === '1') {
            $this->dir = '';
            return;
        }

        if ($this->dir !== '' && !is_dir($this->dir)) {
            @mkdir($this->dir, 0755, true);
        }
    }

    public function get(string $key): ?array
    {
        if ($this->dir === '' || !is_dir($this->dir)) {
            return null;
        }
        $file = $this->dir . '/' . md5($key) . '.json';
        if (!is_file($file)) {
            return null;
        }
        $raw = @file_get_contents($file);
        if ($raw === false) {
            return null;
        }
        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data['expires'], $data['value'])) {
            @unlink($file);
            return null;
        }
        if (time() > (int) $data['expires']) {
            @unlink($file);
            return null;
        }
        return is_array($data['value']) ? $data['value'] : null;
    }

    public function set(string $key, array $value, ?int $ttl = null): void
    {
        if ($this->dir === '' || !is_dir($this->dir)) {
            return;
        }
        $file = $this->dir . '/' . md5($key) . '.json';
        $data = [
            'expires' => time() + ($ttl ?? $this->defaultTtl),
            'value'   => $value,
        ];
        @file_put_contents($file, json_encode($data), LOCK_EX);
    }

    public function forget(string $key): void
    {
        if ($this->dir === '') {
            return;
        }
        $file = $this->dir . '/' . md5($key) . '.json';
        @unlink($file);
    }

    /** Remove all cached entries. */
    public function flush(): void
    {
        if ($this->dir === '' || !is_dir($this->dir)) {
            return;
        }
        foreach (glob($this->dir . '/*.json') ?: [] as $file) {
            @unlink($file);
        }
    }
}
