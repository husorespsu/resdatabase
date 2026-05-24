<?php

declare(strict_types=1);

class Project extends Model
{
    protected string $table = 'research_projects';

    public function getAll(array $filters = []): array
    {
        $sql = "SELECT
                    rj.id, rj.project_code, rj.status, rj.approved_date, rj.approved_budget,
                    rj.contract_number, rj.actual_start_date, rj.actual_end_date,
                    rj.progress_percentage, rj.created_at, rj.updated_at,
                    rp.title_th, rp.title_en, rp.proposal_code, rp.budget_year,
                    u.name AS pi_name,
                    fs.name AS funding_source_name, fs.type AS funding_source_type,
                    fos.name_th AS field_name_th, fos.faculty
                FROM {$this->table} rj
                LEFT JOIN research_proposals rp ON rj.proposal_id = rp.id
                LEFT JOIN users u   ON rp.principal_investigator_id = u.id
                LEFT JOIN funding_sources fs  ON rp.funding_source_id = fs.id
                LEFT JOIN fields_of_study fos ON rp.field_of_study_id = fos.id
                WHERE 1=1";

        $params = [];
        if (!empty($filters['status'])) {
            $sql .= " AND rj.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['budget_year'])) {
            $sql .= " AND rp.budget_year = ?";
            $params[] = $filters['budget_year'];
        }
        if (!empty($filters['funding_source_id'])) {
            $sql .= " AND rp.funding_source_id = ?";
            $params[] = $filters['funding_source_id'];
        }
        if (!empty($filters['field_of_study_id'])) {
            $sql .= " AND rp.field_of_study_id = ?";
            $params[] = $filters['field_of_study_id'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (rp.title_th LIKE ? OR rj.project_code LIKE ?)";
            $like = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$like, $like]);
        }
        $sql .= " ORDER BY rj.created_at DESC";
        return $this->query($sql, $params);
    }

    public function getById(int $id): ?array
    {
        $project = $this->queryOne("
            SELECT rj.*, rp.title_th, rp.title_en, rp.proposal_code, rp.budget_requested,
                   rp.abstract, rp.objectives, rp.budget_year, rp.attachment_path,
                   rp.start_date, rp.end_date, rp.principal_investigator_id,
                   u.name AS pi_name, u.email AS pi_email, u.phone AS pi_phone,
                   fs.name AS funding_source_name, fs.type AS funding_source_type,
                   fos.name_th AS field_name_th, fos.faculty,
                   ab.name AS approved_by_name
            FROM {$this->table} rj
            LEFT JOIN research_proposals rp ON rj.proposal_id = rp.id
            LEFT JOIN users u   ON rp.principal_investigator_id = u.id
            LEFT JOIN users ab  ON rj.approved_by = ab.id
            LEFT JOIN funding_sources fs  ON rp.funding_source_id = fs.id
            LEFT JOIN fields_of_study fos ON rp.field_of_study_id = fos.id
            WHERE rj.id = ? LIMIT 1
        ", [$id]);

        if (!$project) return null;
        $project['reviewers'] = $this->getReviewers((int)$project['proposal_id']);
        return $project;
    }

    public function getReviewers(int $proposalId): array
    {
        return $this->query("
            SELECT pr.*,
                   CONCAT(er.title,' ',er.first_name,' ',er.last_name) AS reviewer_name,
                   er.institution, er.expertise, er.email AS reviewer_email
            FROM proposal_reviews pr
            JOIN expert_reviewers er ON pr.reviewer_id = er.id
            WHERE pr.proposal_id = ?
            ORDER BY pr.created_at ASC
        ", [$proposalId]);
    }

    public function updateProgress(int $id, int $pct): bool
    {
        return $this->update($id, [
            'progress_percentage' => max(0, min(100, $pct)),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);
    }

    public function updateStatus(int $id, string $status, int $userId): bool
    {
        $allowed = ['approved', 'in_progress', 'completed', 'closed', 'cancelled'];
        if (!in_array($status, $allowed, true)) return false;

        $data = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
        if ($status === 'completed') $data['final_report_submitted_at'] = date('Y-m-d H:i:s');
        if ($status === 'in_progress') $data['actual_start_date'] = date('Y-m-d');

        $result = $this->update($id, $data);
        if ($result) $this->logActivity($userId, "status_changed_to_{$status}", $id);
        return $result;
    }

    public function getStats(): array
    {
        return $this->queryOne("
            SELECT COUNT(*) AS total,
                   COALESCE(SUM(approved_budget),0) AS total_budget,
                   SUM(CASE WHEN status='approved'    THEN 1 ELSE 0 END) AS cnt_approved,
                   SUM(CASE WHEN status='in_progress' THEN 1 ELSE 0 END) AS cnt_in_progress,
                   SUM(CASE WHEN status='completed'   THEN 1 ELSE 0 END) AS cnt_completed,
                   SUM(CASE WHEN status='closed'      THEN 1 ELSE 0 END) AS cnt_closed,
                   SUM(CASE WHEN status='cancelled'   THEN 1 ELSE 0 END) AS cnt_cancelled
            FROM {$this->table}
        ", []) ?: [];
    }

    public function getForExport(array $filters = []): array
    {
        return $this->getAll($filters);
    }

    public function getRecentProjects(int $limit = 10): array
    {
        return $this->query("
            SELECT rj.id, rj.project_code, rj.status, rj.progress_percentage,
                   rp.title_th, u.name AS pi_name
            FROM {$this->table} rj
            LEFT JOIN research_proposals rp ON rj.proposal_id = rp.id
            LEFT JOIN users u ON rp.principal_investigator_id = u.id
            ORDER BY rj.updated_at DESC LIMIT ?
        ", [$limit]);
    }

    public function getAvailableProposals(): array
    {
        return $this->query("
            SELECT rp.id, rp.proposal_code, rp.title_th, rp.budget_requested, rp.budget_year,
                   u.name AS pi_name, fs.name AS funding_source_name
            FROM research_proposals rp
            LEFT JOIN users u ON rp.principal_investigator_id = u.id
            LEFT JOIN funding_sources fs ON rp.funding_source_id = fs.id
            WHERE rp.status = 'approved'
              AND rp.id NOT IN (SELECT proposal_id FROM {$this->table} WHERE proposal_id IS NOT NULL)
            ORDER BY rp.budget_year DESC, rp.proposal_code ASC
        ", []);
    }

    public function getFundingSources(): array
    {
        return $this->query(
            "SELECT id, name FROM funding_sources WHERE is_active = 1 ORDER BY name ASC",
            []
        );
    }

    public function getFieldsOfStudy(): array
    {
        return $this->query(
            "SELECT id, name_th FROM fields_of_study ORDER BY name_th ASC",
            []
        );
    }

    public function getYears(): array
    {
        return $this->query(
            "SELECT DISTINCT rp.budget_year
             FROM {$this->table} rj
             LEFT JOIN research_proposals rp ON rj.proposal_id = rp.id
             WHERE rp.budget_year IS NOT NULL
             ORDER BY rp.budget_year DESC",
            []
        );
    }
}
