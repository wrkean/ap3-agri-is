<?php
/**
 * Unified database abstraction for the Agriculture Information System.
 * Uses Supabase REST API if credentials are configured, otherwise falls back to local SQLite.
 */

require_once __DIR__ . '/Supabase.php';

class Database
{
    private string $driver;
    private ?Supabase $supabase = null;
    private ?PDO $pdo = null;

    public function __construct()
    {
        $envFile = __DIR__ . '/../.env';
        $supabaseUrl = '';
        $supabaseKey = '';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (strpos($line, '#') === 0 || strpos($line, '=') === false) {
                    continue;
                }
                [$k, $v] = explode('=', $line, 2);
                $k = trim($k);
                $v = trim($v);
                if ($k === 'SUPABASE_URL') {
                    $supabaseUrl = $v;
                }
                if ($k === 'SUPABASE_ANON_KEY') {
                    $supabaseKey = $v;
                }
            }
        }

        if (!empty($supabaseUrl) && !empty($supabaseKey) && strpos($supabaseUrl, 'your-project') === false) {
            $this->driver = 'supabase';
            $this->supabase = new Supabase();
        } else {
            $this->driver = 'sqlite';
            $dbPath = __DIR__ . '/../database/agri.db';
            $this->pdo = new PDO('sqlite:' . $dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->exec('PRAGMA foreign_keys = ON');
        }
    }

    public function getDriver(): string
    {
        return $this->driver;
    }
    private function isSupabase(): bool
    {
        return $this->driver === 'supabase';
    }

    public function select(string $table, string $columns = '*', array $filters = [], array $options = [])
    {
        if ($this->isSupabase()) {
            return $this->supabase->select($table, $columns, $filters, $options);
        }
        return $this->sqliteSelect($table, $columns, $filters, $options);
    }

    private function sqliteSelect(string $table, string $columns, array $filters, array $options)
    {
        // Handle aggregate: "status,count"
        if (preg_match('/^(\w+),count(\(\*\))?$/i', trim($columns), $m)) {
            $col = $m[1];
            $sql = "SELECT $col, COUNT(*) as count FROM $table";
            $where = [];
            $params = [];
            foreach ($filters as $f) {
                if (!is_string($f)) {
                    continue;
                }
                $parsed = $this->parseFilter($f);
                if ($parsed) {
                    $where[] = $parsed['sql'];
                    foreach ($parsed['params'] as $p) {
                        $params[] = $p;
                    }
                }
            }
            if ($where) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }
            $sql .= " GROUP BY $col";
            if (!empty($options['order'])) {
                $order = $options['order'];
                if (strpos($order, '.') !== false) {
                    [$c, $dir] = explode('.', $order, 2);
                    $sql .= " ORDER BY $c " . (strtolower($dir) === 'desc' ? 'DESC' : 'ASC');
                }
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Nested selects
        $joins = [];
        $selectCols = '*';
        if ($columns !== '*' && preg_match('/\w+\([^)]*\)/', $columns)) {
            $parts = $this->parseNestedSelect($table, $columns);
            $selectCols = $parts['select'];
            $joins = $parts['joins'];
        } elseif ($columns !== '*') {
            $selectCols = $columns;
        }

        $sql = "SELECT $selectCols FROM $table";
        foreach ($joins as $j) {
            $sql .= ' ' . $j;
        }

        $where = [];
        $params = [];
        foreach ($filters as $f) {
            if (!is_string($f)) {
                continue;
            }
            $parsed = $this->parseFilter($f);
            if ($parsed) {
                $where[] = $parsed['sql'];
                foreach ($parsed['params'] as $p) {
                    $params[] = $p;
                }
            }
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        if (!empty($options['order'])) {
            $order = $options['order'];
            if (strpos($order, '.') !== false) {
                [$col, $dir] = explode('.', $order, 2);
                $sql .= " ORDER BY $col " . (strtolower($dir) === 'desc' ? 'DESC' : 'ASC');
            } else {
                $sql .= " ORDER BY $order";
            }
        }
        if (isset($options['limit'])) {
            $sql .= ' LIMIT ' . (int) $options['limit'];
        }
        if (isset($options['offset'])) {
            $sql .= ' OFFSET ' . (int) $options['offset'];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($joins) {
            $rows = $this->nestRelations($rows, $columns);
            // Group by main table PK to aggregate 1:M relations into arrays
            $rows = $this->groupByParent($table, $rows, $columns);
        }
        return $rows;
    }

    private function extractTopLevelGroups(string $columns): array
    {
        $tokens = [];
        $buf = '';
        $d = 0;
        $len = strlen($columns);
        for ($i = 0; $i < $len; $i++) {
            $ch = $columns[$i];
            if ($ch === '(') {
                $d++;
                $buf .= $ch;
            } elseif ($ch === ')') {
                $d--;
                $buf .= $ch;
            } elseif ($ch === ',' && $d === 0) {
                $tokens[] = trim($buf);
                $buf = '';
            } else {
                $buf .= $ch;
            }
        }
        if (trim($buf) !== '') {
            $tokens[] = trim($buf);
        }

        $groups = [];
        foreach ($tokens as $tok) {
            if (preg_match('/^(\w+)\((.+)\)$/s', $tok, $m)) {
                $gname = $m[1];
                $inner = $m[2];
                $innerTokens = [];
                $buf2 = '';
                $d2 = 0;
                for ($j = 0; $j < strlen($inner); $j++) {
                    $ch = $inner[$j];
                    if ($ch === '(') {
                        $d2++;
                        $buf2 .= $ch;
                    } elseif ($ch === ')') {
                        $d2--;
                        $buf2 .= $ch;
                    } elseif ($ch === ',' && $d2 === 0) {
                        $innerTokens[] = trim($buf2);
                        $buf2 = '';
                    } else {
                        $buf2 .= $ch;
                    }
                }
                if (trim($buf2) !== '') {
                    $innerTokens[] = trim($buf2);
                }

                $cols = [];
                foreach ($innerTokens as $it) {
                    if (preg_match('/^(\w+)\((.+)\)$/s', $it, $m2)) {
                        $cols[] = ['table' => $m2[1], 'cols' => array_map('trim', explode(',', $m2[2]))];
                    } else {
                        $cols[] = trim($it);
                    }
                }
                $groups[] = ['table' => $gname, 'cols' => $cols];
            }
        }
        return $groups;
    }

    private function parseNestedSelect(string $table, string $columns): array
    {
        $joins = [];
        $select = "$table.*";
        // FK mapping: child_table => parent_column (the FK on child that references parent)
        $fkMap = [
            'farm' => ['child' => 'farm', 'fk' => 'farmer_id', 'parent' => 'farmer', 'pk' => 'farmer_id'],
            'planting' => ['child' => 'planting', 'fk' => 'farm_id', 'parent' => 'farm', 'pk' => 'farm_id'],
            'planting2' => ['child' => 'planting', 'fk' => 'crop_id', 'parent' => 'crop', 'pk' => 'crop_id'],
            'harvest' => ['child' => 'harvest', 'fk' => 'planting_id', 'parent' => 'planting', 'pk' => 'planting_id'],
        ];

        $groups = $this->extractTopLevelGroups($columns);
        foreach ($groups as $group) {
            $relTable = $group['table'];
            // Determine FK: is it on $table side or $relTable side?
            $fkOnTable = $relTable . '_id'; // e.g. farm_id on planting table
            $fkOnRel = $table . '_id'; // e.g. farmer_id on farm table (reverse)

            // Check if $table has the FK column (forward relation)
            if (in_array($fkOnTable, $this->getTableColumns($table))) {
                $joinCond = "$table.$fkOnTable = $relTable.{$relTable}_id";
            } elseif (in_array($fkOnRel, $this->getTableColumns($relTable))) {
                // Reverse: relTable has FK to table
                $joinCond = "$relTable.$fkOnRel = $table.{$table}_id";
            } else {
                // Fallback
                $joinCond = "$table.{$relTable}_id = $relTable.{$relTable}_id";
            }

            $topJoin = "LEFT JOIN $relTable ON $joinCond";
            $nestedJoins = [];
            foreach ($group['cols'] as $col) {
                if (is_array($col)) {
                    $nestedTable = $col['table'];
                    $nestedFk = $nestedTable . '_id';
                    foreach ($col['cols'] as $nc) {
                        $nc = trim($nc);
                        $select .= ", $nestedTable.$nc AS {$nestedTable}_{$nc}";
                    }
                    // FK for nested: check if relTable has the FK
                    if (in_array($nestedFk, $this->getTableColumns($relTable))) {
                        $nestedJoinCond = "$relTable.$nestedFk = $nestedTable.{$nestedTable}_id";
                    } elseif (in_array($relTable . '_id', $this->getTableColumns($nestedTable))) {
                        $nestedJoinCond = "$nestedTable.{$relTable}_id = $relTable.{$relTable}_id";
                    } else {
                        $nestedJoinCond = "$relTable.$nestedFk = $nestedTable.{$nestedTable}_id";
                    }
                    $nestedJoins[] = "LEFT JOIN $nestedTable ON $nestedJoinCond";
                } else {
                    $col = trim($col);
                    if ($col === '*') {
                        // Expand * to all columns of the related table
                        foreach ($this->getTableColumns($relTable) as $c) {
                            $select .= ", $relTable.$c AS {$relTable}_$c";
                        }
                    } else {
                        $select .= ", $relTable.$col AS {$relTable}_$col";
                    }
                }
            }
            $joins[] = $topJoin;
            foreach ($nestedJoins as $nj) {
                $joins[] = $nj;
            }
        }
        return ['select' => $select, 'joins' => $joins];
    }

    private function nestRelations(array $rows, string $columns): array
    {
        $groups = $this->extractTopLevelGroups($columns);
        foreach ($rows as &$row) {
            foreach ($groups as $group) {
                $relTable = $group['table'];
                $nested = [];
                foreach ($group['cols'] as $col) {
                    if (is_array($col)) {
                        $nestedTable = $col['table'];
                        $subNested = [];
                        foreach ($col['cols'] as $nc) {
                            $nc = trim($nc);
                            $key = $nestedTable . '_' . $nc;
                            if (array_key_exists($key, $row)) {
                                $subNested[$nc] = $row[$key];
                                unset($row[$key]);
                            }
                        }
                        $nested[$nestedTable] = !empty($subNested) ? $subNested : null;
                    } else {
                        $col = trim($col);
                        if ($col === '*') {
                            foreach ($this->getTableColumns($relTable) as $c) {
                                $key = $relTable . '_' . $c;
                                if (array_key_exists($key, $row)) {
                                    $nested[$c] = $row[$key];
                                    unset($row[$key]);
                                }
                            }
                        } else {
                            $key = $relTable . '_' . $col;
                            if (array_key_exists($key, $row)) {
                                $nested[$col] = $row[$key];
                                unset($row[$key]);
                            }
                        }
                    }
                }
                $row[$relTable] = !empty($nested) ? $nested : null;
            }
        }
        return $rows;
    }

    private function getTableColumns(string $table): array
    {
        static $cache = [];
        if (isset($cache[$table])) {
            return $cache[$table];
        }
        $stmt = $this->pdo->query("PRAGMA table_info($table)");
        $cols = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $cols[] = $row['name'];
        }
        $cache[$table] = $cols;
        return $cols;
    }

    private function groupByParent(string $table, array $rows, string $columns): array
    {
        if (empty($rows)) {
            return $rows;
        }
        $pk = $table . '_id';
        // Determine nested relation names
        $groups = $this->extractTopLevelGroups($columns);
        $relNames = array_column($groups, 'table');

        $grouped = [];
        foreach ($rows as $row) {
            $key = $row[$pk] ?? null;
            if ($key === null) {
                $grouped[] = $row;
                continue;
            }
            if (!isset($grouped[$key])) {
                $grouped[$key] = $row;
                // Initialize nested arrays
                foreach ($relNames as $rn) {
                    $grouped[$key][$rn] = [];
                }
            }
            // Collect nested data
            foreach ($relNames as $rn) {
                if (isset($row[$rn]) && $row[$rn] !== null) {
                    // Avoid duplicates
                    $exists = false;
                    foreach ($grouped[$key][$rn] as $existing) {
                        if (
                            isset($existing[$rn . '_id']) &&
                            isset($row[$rn][$rn . '_id']) &&
                            $existing[$rn . '_id'] === $row[$rn][$rn . '_id']
                        ) {
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists) {
                        $grouped[$key][$rn][] = $row[$rn];
                    }
                }
            }
        }
        // Clean up empty arrays → null (to match Supabase format for no children)
        $result = array_values($grouped);
        foreach ($result as &$r) {
            foreach ($relNames as $rn) {
                if (isset($r[$rn]) && empty($r[$rn])) {
                    $r[$rn] = null;
                }
            }
        }
        return $result;
    }

    private function parseFilter(string $filter): ?array
    {
        if (!preg_match('/^(\w+)=([a-z]+)\.(.+)$/', $filter, $m)) {
            return null;
        }
        $col = $m[1];
        $op = $m[2];
        $val = $m[3];
        switch ($op) {
            case 'eq':
                return ['sql' => "$col = ?", 'params' => [$val]];
            case 'ilike':
            case 'like':
                $val = str_replace('%', '', $val);
                return ['sql' => "$col LIKE ?", 'params' => ['%' . $val . '%']];
            case 'gt':
                return ['sql' => "$col > ?", 'params' => [$val]];
            case 'lt':
                return ['sql' => "$col < ?", 'params' => [$val]];
            case 'gte':
                return ['sql' => "$col >= ?", 'params' => [$val]];
            case 'lte':
                return ['sql' => "$col <= ?", 'params' => [$val]];
            case 'neq':
                return ['sql' => "$col != ?", 'params' => [$val]];
            default:
                return ['sql' => "$col = ?", 'params' => [$val]];
        }
    }

    public function insert(string $table, array $data)
    {
        if ($this->isSupabase()) {
            return $this->supabase->insert($table, $data);
        }
        $cols = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $stmt = $this->pdo->prepare("INSERT INTO $table ($cols) VALUES ($placeholders)");
        $stmt->execute(array_values($data));
        $id = (int) $this->pdo->lastInsertId();
        return $id ? $this->find($table, $id) : [];
    }

    public function update(string $table, array $data, array $filters)
    {
        if ($this->isSupabase()) {
            return $this->supabase->update($table, $data, $filters);
        }
        $set = [];
        $params = [];
        foreach ($data as $col => $val) {
            $set[] = "$col = ?";
            $params[] = $val;
        }
        $sql = "UPDATE $table SET " . implode(', ', $set);
        $where = [];
        foreach ($filters as $f) {
            if (!is_string($f)) {
                continue;
            }
            $parsed = $this->parseFilter($f);
            if ($parsed) {
                $where[] = $parsed['sql'];
                foreach ($parsed['params'] as $p) {
                    $params[] = $p;
                }
            }
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return [];
    }

    public function delete(string $table, array $filters)
    {
        if ($this->isSupabase()) {
            return $this->supabase->delete($table, $filters);
        }
        $sql = "DELETE FROM $table";
        $params = [];
        $where = [];
        foreach ($filters as $f) {
            if (!is_string($f)) {
                continue;
            }
            $parsed = $this->parseFilter($f);
            if ($parsed) {
                $where[] = $parsed['sql'];
                foreach ($parsed['params'] as $p) {
                    $params[] = $p;
                }
            }
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return [];
    }

    public function find(string $table, int $id, string $pk = 'id')
    {
        if ($this->isSupabase()) {
            return $this->supabase->find($table, $id, $pk);
        }
        $stmt = $this->pdo->prepare("SELECT * FROM $table WHERE $pk = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
