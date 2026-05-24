<?php

declare(strict_types=1);

class User extends Model
{
    protected string $table = 'users';

    public function findByEmail(string $email): ?array
    {
        return $this->queryOne("SELECT * FROM {$this->table} WHERE email = ? LIMIT 1", [$email]);
    }

    public function findByGoogleId(string $googleId): ?array
    {
        return $this->queryOne("SELECT * FROM {$this->table} WHERE google_id = ? LIMIT 1", [$googleId]);
    }

    public function createFromGoogle(array $googleData): array
    {
        $existing = $this->findByGoogleId($googleData['google_id']);
        if ($existing) {
            $this->update($existing['id'], [
                'name'       => $googleData['name'],
                'avatar'     => $googleData['avatar'] ?? null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            return $this->findById($existing['id']);
        }

        // First user becomes superadmin
        $isFirst = ($this->count() === 0);
        $role    = $isFirst ? 'superadmin' : 'executive';

        $id = $this->create([
            'google_id'  => $googleData['google_id'],
            'email'      => $googleData['email'],
            'name'       => $googleData['name'],
            'avatar'     => $googleData['avatar'] ?? null,
            'role'       => $role,
            'is_active'  => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->findById($id);
    }

    public function findByUsername(string $username): ?array
    {
        return $this->queryOne(
            "SELECT * FROM {$this->table} WHERE username = ? LIMIT 1",
            [$username]
        );
    }

    public function updateLastLogin(int $id): bool
    {
        return $this->update($id, ['last_login' => date('Y-m-d H:i:s')]);
    }

    public function getActiveUsers(?string $role = null): array
    {
        $sql    = "SELECT * FROM {$this->table} WHERE is_active = 1";
        $params = [];
        if ($role !== null) {
            $sql   .= " AND role = ?";
            $params[] = $role;
        }
        $sql .= " ORDER BY name";
        return $this->query($sql, $params);
    }

    public function toggleActive(int $id): bool
    {
        $user = $this->findById($id);
        if (!$user) return false;
        return $this->update($id, [
            'is_active'  => $user['is_active'] ? 0 : 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function updateRole(int $id, string $role): bool
    {
        $allowed = ['superadmin', 'admin', 'executive'];
        if (!in_array($role, $allowed, true)) return false;
        return $this->update($id, ['role' => $role, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    public function getAllWithStats(): array
    {
        return $this->query("
            SELECT u.*,
                   COUNT(DISTINCT rp.id) AS proposal_count,
                   COUNT(DISTINCT rj.id) AS project_count
            FROM {$this->table} u
            LEFT JOIN research_proposals rp ON rp.principal_investigator_id = u.id
            LEFT JOIN research_projects  rj ON rj.proposal_id = rp.id
            GROUP BY u.id
            ORDER BY u.name
        ", []);
    }
}
