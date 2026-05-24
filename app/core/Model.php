<?php

declare(strict_types=1);

/**
 * Base Model (no namespace).
 * Gets PDO from DatabaseConfig::getInstance() automatically.
 */
abstract class Model
{
    protected \PDO  $db;
    protected string $table      = '';
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = DatabaseConfig::getInstance();
    }

    // ── Find ──────────────────────────────────────────────────

    public function findAll(array $conditions = [], string $order = '', ?int $limit = null): array
    {
        [$where, $params] = $this->buildWhere($conditions);
        $sql = "SELECT * FROM {$this->table}" . $where;
        if ($order !== '') $sql .= ' ORDER BY ' . $order;
        if ($limit !== null) $sql .= ' LIMIT ' . (int)$limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findById(int|string $id): ?array
    {
        $sql  = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $columns      = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql  = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        return (int)$this->db->lastInsertId();
    }

    public function update(int|string $id, array $data): bool
    {
        if (empty($data)) return false;
        $sets = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        $sql  = "UPDATE {$this->table} SET {$sets} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([...array_values($data), $id]);
    }

    public function delete(int|string $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }

    // ── Raw Query ─────────────────────────────────────────────

    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    // ── Count ─────────────────────────────────────────────────

    public function count(array $conditions = []): int
    {
        [$where, $params] = $this->buildWhere($conditions);
        $sql  = "SELECT COUNT(*) FROM {$this->table}" . $where;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    // ── Pagination ────────────────────────────────────────────

    public function paginate(int $page, int $perPage, array $conditions = [], string $order = ''): array
    {
        $total  = $this->count($conditions);
        $pages  = (int)ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;

        [$where, $params] = $this->buildWhere($conditions);
        $sql = "SELECT * FROM {$this->table}" . $where;
        if ($order !== '') $sql .= ' ORDER BY ' . $order;
        $sql .= ' LIMIT ' . $perPage . ' OFFSET ' . $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [
            'data'         => $stmt->fetchAll(\PDO::FETCH_ASSOC),
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => $pages,
        ];
    }

    // ── Activity Logging ──────────────────────────────────────

    public function logActivity(
        ?int $userId,
        string $action,
        ?int $recordId = null,
        mixed $old = null,
        mixed $new = null
    ): void {
        $sql = "INSERT INTO activity_logs
                    (user_id, action, table_name, record_id, old_value, new_value, ip_address)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userId,
            $action,
            $this->table,
            $recordId,
            $old !== null ? json_encode($old, JSON_UNESCAPED_UNICODE) : null,
            $new !== null ? json_encode($new, JSON_UNESCAPED_UNICODE) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────

    private function buildWhere(array $conditions): array
    {
        if (empty($conditions)) return ['', []];

        $clauses = [];
        $params  = [];
        foreach ($conditions as $col => $val) {
            if ($val === null) {
                $clauses[] = "{$col} IS NULL";
            } else {
                $clauses[] = "{$col} = ?";
                $params[]  = $val;
            }
        }
        return [' WHERE ' . implode(' AND ', $clauses), $params];
    }

    protected function getPdo(): \PDO
    {
        return $this->db;
    }
}
