<?php
// models/User.php
class User
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function all(array $filters = [], array $options = []): array
    {
        $cache = new Cache();
        if (empty($filters) && empty($options)) {
            $cached = $cache->get('user:all');
            if ($cached !== null) {
                return $cached;
            }
        }
        $rows = $this->db->select('users', '*', $filters, $options);
        if (empty($filters) && empty($options)) {
            $cache->set('user:all', $rows, 1800);
        }
        return $rows;
    }

    public function find(int $id): ?array
    {
        return $this->db->find('users', $id, 'user_id');
    }

    public function findByEmail(string $email): ?array
    {
        $rows = $this->db->select('users', '*', ['email' => 'eq.' . rawurlencode($email)], ['limit' => 1]);
        return is_array($rows) ? ($rows[0] ?? null) : null;
    }

    // Aliases for callers that prefer other naming.
    public function getByEmail(string $email): ?array
    {
        return $this->findByEmail($email);
    }

    public function byEmail(string $email): ?array
    {
        return $this->findByEmail($email);
    }

    public function create(array $data): ?array
    {
        $result = $this->db->insert('users', $data);
        if ($result !== null) {
            (new Cache())->flush();
        }
        return $result;
    }

    public function update(int $id, array $data): ?array
    {
        $result = $this->db->update('users', $data, ['user_id=eq.' . $id]);
        if ($result !== null) {
            (new Cache())->flush();
        }
        return $result;
    }

    public function delete(int $id): ?array
    {
        $result = $this->db->delete('users', ['user_id=eq.' . $id]);
        if ($result !== null) {
            (new Cache())->flush();
        }
        return $result;
    }

    public function search(string $term): array
    {
        $filters = [];
        foreach (preg_split('/\s+/', trim($term)) as $word) {
            if ($word === '') {
                continue;
            }
            $w = rawurlencode($word);
            $filters[] = 'or=(email.ilike.%25' . $w . '%25,full_name.ilike.%25' . $w . '%25)';
        }
        return $this->all($filters);
    }

    public function count(): int
    {
        return count($this->db->select('users', 'user_id'));
    }

    /**
     * Normalize a raw users row into the shape used by auth/session code.
     */
    public function normalize(array $row): array
    {
        return [
            'id' => $row['user_id'] ?? ($row['id'] ?? null),
            'email' => $row['email'] ?? '',
            'password' => $row['password_hash'] ?? ($row['password'] ?? ''),
            'role' => $row['role'] ?? '',
            'name' => $row['full_name'] ?? ($row['name'] ?? ''),
        ];
    }
}

// Backwards/alternative class name alias.
if (!class_exists('Users')) {
    class Users extends User
    {
    }
}
