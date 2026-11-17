<?php
// models/User.php
require_once __DIR__ . '/Model.php';
class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'user_id';
    protected string $columns = '*';

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
