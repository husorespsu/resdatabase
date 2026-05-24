<?php

declare(strict_types=1);

class Notification extends Model
{
    protected string $table = 'notifications';

    public function getByUser(int $userId, bool $unreadOnly = false, int $limit = 30): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ?";
        $params = [$userId];
        if ($unreadOnly) {
            $sql .= " AND is_read = 0";
        }
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        return $this->query($sql, $params);
    }

    public function getUnreadCount(int $userId): int
    {
        $row = $this->queryOne(
            "SELECT COUNT(*) AS c FROM {$this->table} WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
        return (int)($row['c'] ?? 0);
    }

    public function markRead(int $id, int $userId): bool
    {
        return $this->execute(
            "UPDATE {$this->table} SET is_read = 1 WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }

    public function markAllRead(int $userId): bool
    {
        return $this->execute(
            "UPDATE {$this->table} SET is_read = 1 WHERE user_id = ?",
            [$userId]
        );
    }

    public function createForAdmin(string $type, string $title, string $message, string $relatedTable = '', int $relatedId = 0): void
    {
        $admins = $this->query(
            "SELECT id FROM users WHERE role IN ('admin','superadmin') AND is_active = 1",
            []
        );
        foreach ($admins as $admin) {
            $this->create([
                'user_id'       => $admin['id'],
                'type'          => $type,
                'title'         => $title,
                'message'       => $message,
                'related_table' => $relatedTable,
                'related_id'    => $relatedId ?: null,
                'is_read'       => 0,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function deleteOld(int $days = 30): bool
    {
        return $this->execute(
            "DELETE FROM {$this->table} WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY) AND is_read = 1",
            [$days]
        );
    }

    public function getPaginatedForUser(int $userId, int $page = 1, int $perPage = 20): array
    {
        $total  = (int)($this->queryOne("SELECT COUNT(*) AS c FROM {$this->table} WHERE user_id = ?", [$userId])['c'] ?? 0);
        $pages  = max(1, (int)ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;

        $data = $this->query("
            SELECT * FROM {$this->table} WHERE user_id = ?
            ORDER BY is_read ASC, created_at DESC
            LIMIT ? OFFSET ?
        ", [$userId, $perPage, $offset]);

        return ['data' => $data, 'total' => $total, 'last_page' => $pages, 'current_page' => $page];
    }
}
