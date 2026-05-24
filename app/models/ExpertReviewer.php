<?php

declare(strict_types=1);

class ExpertReviewer extends Model
{
    protected string $table = 'expert_reviewers';

    public function getAll(string $search = ''): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];
        if ($search !== '') {
            $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR institution LIKE ? OR expertise LIKE ?)";
            $like = '%' . $search . '%';
            $params = [$like, $like, $like, $like];
        }
        $sql .= " ORDER BY last_name, first_name";
        return $this->query($sql, $params);
    }

    public function getFullName(array $reviewer): string
    {
        return trim(($reviewer['title'] ?? '') . ' ' . $reviewer['first_name'] . ' ' . $reviewer['last_name']);
    }

    public function toggleActive(int $id): bool
    {
        $reviewer = $this->findById($id);
        if (!$reviewer) return false;
        $newVal = $reviewer['is_active'] ? 0 : 1;
        return $this->update($id, ['is_active' => $newVal, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    public function getByExpertise(string $keyword): array
    {
        return $this->query("
            SELECT id, title, first_name, last_name, institution, expertise
            FROM {$this->table}
            WHERE is_active = 1 AND expertise LIKE ?
            ORDER BY last_name, first_name
        ", ['%' . $keyword . '%']);
    }

    public function getAssignmentHistory(int $reviewerId): array
    {
        return $this->query("
            SELECT pr.*, rp.title_th, rp.proposal_code, rp.budget_year
            FROM proposal_reviews pr
            JOIN research_proposals rp ON pr.proposal_id = rp.id
            WHERE pr.reviewer_id = ?
            ORDER BY pr.created_at DESC
        ", [$reviewerId]);
    }

    public function getActiveList(): array
    {
        return $this->query("
            SELECT id, title, first_name, last_name, institution, expertise, email
            FROM {$this->table} WHERE is_active = 1 ORDER BY last_name, first_name
        ", []);
    }

    public function createReviewer(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['is_active']  = 1;
        return $this->create($data);
    }

    public function updateReviewer(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->update($id, $data);
    }
}
