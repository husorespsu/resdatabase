<?php

declare(strict_types=1);

class Proposal extends Model
{
    protected string $table = 'research_proposals';

    // ── List / Detail ─────────────────────────────────────────

    public function getAll(array $filters = []): array
    {
        $sql = "SELECT
                    rp.id, rp.proposal_code, rp.title_th, rp.title_en,
                    rp.status, rp.budget_requested, rp.budget_year,
                    rp.submitted_at, rp.created_at, rp.attachment_path,
                    COALESCE(rp.pi_name, u.name) AS pi_name, u.id AS pi_id,
                    fs.id AS funding_source_id, fs.name AS funding_source_name, fs.type AS funding_source_type,
                    fos.id AS field_of_study_id, fos.name_th AS field_name_th, fos.faculty AS faculty_name
                FROM {$this->table} rp
                LEFT JOIN users u   ON rp.principal_investigator_id = u.id
                LEFT JOIN funding_sources fs  ON rp.funding_source_id = fs.id
                LEFT JOIN fields_of_study fos ON rp.field_of_study_id = fos.id
                WHERE 1=1";

        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND rp.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['funding_source_id'])) {
            $sql .= " AND rp.funding_source_id = ?";
            $params[] = $filters['funding_source_id'];
        }
        if (!empty($filters['field_of_study_id'])) {
            $sql .= " AND rp.field_of_study_id = ?";
            $params[] = $filters['field_of_study_id'];
        }
        if (!empty($filters['budget_year'])) {
            $sql .= " AND rp.budget_year = ?";
            $params[] = $filters['budget_year'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (rp.title_th LIKE ? OR rp.proposal_code LIKE ? OR COALESCE(rp.pi_name, u.name) LIKE ?)";
            $like = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$like, $like, $like]);
        }

        $sql .= " ORDER BY rp.created_at DESC";
        return $this->query($sql, $params);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT
                    rp.*,
                    COALESCE(rp.pi_name, u.name) AS pi_name,
                    u.email AS pi_email, u.phone AS pi_phone, u.department AS pi_department,
                    fs.name AS funding_source_name, fs.type AS funding_source_type,
                    fos.name_th AS field_name_th, fos.name_en AS field_name_en, fos.faculty AS faculty_name,
                    cu.name AS created_by_name
                FROM {$this->table} rp
                LEFT JOIN users u   ON rp.principal_investigator_id = u.id
                LEFT JOIN funding_sources fs  ON rp.funding_source_id = fs.id
                LEFT JOIN fields_of_study fos ON rp.field_of_study_id = fos.id
                LEFT JOIN users cu  ON rp.created_by = cu.id
                WHERE rp.id = ? LIMIT 1";

        $proposal = $this->queryOne($sql, [$id]);
        if (!$proposal) return null;

        $proposal['co_investigators_list'] = [];
        if (!empty($proposal['co_investigators'])) {
            $decoded = json_decode($proposal['co_investigators'], true);
            $proposal['co_investigators_list'] = is_array($decoded) ? $decoded : [];
        }

        if ($proposal['status'] === 'approved') {
            $project = $this->queryOne(
                "SELECT id, project_code, status AS project_status, approved_budget, contract_number
                 FROM research_projects WHERE proposal_id = ? LIMIT 1",
                [$id]
            );
            $proposal['linked_project'] = $project;
        } else {
            $proposal['linked_project'] = null;
        }

        return $proposal;
    }

    // ── Create / Update ───────────────────────────────────────

    public function createProposal(array $data, int $createdBy): int
    {
        // Generate proposal_code: PSU-{year}-{seq:03d}
        $year = $data['budget_year'] ?? (int)date('Y');
        $cnt  = (int)$this->queryOne(
            "SELECT COUNT(*) AS c FROM {$this->table} WHERE budget_year = ?", [$year]
        )['c'];
        $data['proposal_code'] = sprintf('PSU-%d-%03d', $year, $cnt + 1);

        if (isset($data['co_investigators']) && is_array($data['co_investigators'])) {
            $data['co_investigators'] = json_encode($data['co_investigators'], JSON_UNESCAPED_UNICODE);
        }
        if (!empty($data['status']) && $data['status'] === 'reviewing') {
            $data['submitted_at'] = date('Y-m-d H:i:s');
        }

        $data['created_by'] = $createdBy;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        return $this->create($data);
    }

    public function updateProposal(int $id, array $data, int $updatedBy): bool
    {
        if (isset($data['co_investigators']) && is_array($data['co_investigators'])) {
            $data['co_investigators'] = json_encode($data['co_investigators'], JSON_UNESCAPED_UNICODE);
        }

        $prev = $this->queryOne("SELECT status FROM {$this->table} WHERE id = ?", [$id]);
        $prevStatus = $prev['status'] ?? null;

        if (!empty($data['status']) && $data['status'] === 'reviewing' && $prevStatus !== 'reviewing') {
            $data['submitted_at'] = date('Y-m-d H:i:s');
        }
        $data['updated_at'] = date('Y-m-d H:i:s');

        $result = $this->update($id, $data);

        if ($result && !empty($data['status']) && $data['status'] === 'approved' && $prevStatus !== 'approved') {
            $this->createProjectFromProposal($id, $updatedBy);
        }

        return $result;
    }

    // ── Status ────────────────────────────────────────────────

    public function countByStatus(): array
    {
        $rows = $this->query(
            "SELECT status, COUNT(*) AS total FROM {$this->table} GROUP BY status", []
        );
        $result = ['draft' => 0, 'reviewing' => 0, 'approved' => 0, 'rejected' => 0];
        foreach ($rows as $row) {
            $result[$row['status']] = (int)$row['total'];
        }
        return $result;
    }

    public function validateReviewers(int $proposalId): int
    {
        $row = $this->queryOne(
            "SELECT COUNT(*) AS cnt FROM proposal_reviews WHERE proposal_id = ?", [$proposalId]
        );
        return (int)($row['cnt'] ?? 0);
    }

    // ── Dashboard Data ────────────────────────────────────────

    public function getForDashboard(array $filters = []): array
    {
        $where  = 'WHERE 1=1';
        $params = [];

        if (!empty($filters['budget_year'])) {
            if (is_array($filters['budget_year'])) {
                $placeholders = implode(',', array_fill(0, count($filters['budget_year']), '?'));
                $where  .= " AND rp.budget_year IN ({$placeholders})";
                $params  = array_merge($params, $filters['budget_year']);
            } else {
                $where  .= " AND rp.budget_year = ?";
                $params[] = $filters['budget_year'];
            }
        }
        if (!empty($filters['funding_type'])) {
            $where  .= " AND fs.type = ?";
            $params[] = $filters['funding_type'];
        }
        if (!empty($filters['field_of_study_id'])) {
            $where  .= " AND rp.field_of_study_id = ?";
            $params[] = $filters['field_of_study_id'];
        }
        if (!empty($filters['month'])) {
            if (is_array($filters['month'])) {
                $phs    = implode(',', array_fill(0, count($filters['month']), '?'));
                $where .= " AND EXTRACT(MONTH FROM rp.submitted_at)::INT IN ({$phs})";
                $params = array_merge($params, $filters['month']);
            }
        }

        $byFunding = $this->query("
            SELECT fs.name AS funding_name, fs.type AS funding_type,
                   COUNT(rp.id) AS total, COALESCE(SUM(rp.budget_requested),0) AS total_budget
            FROM {$this->table} rp
            LEFT JOIN funding_sources fs ON rp.funding_source_id = fs.id
            {$where}
            GROUP BY fs.id, fs.name, fs.type
            ORDER BY total DESC
        ", $params);

        $byType = $this->query("
            SELECT fs.type AS funding_type, COUNT(rp.id) AS total
            FROM {$this->table} rp
            LEFT JOIN funding_sources fs ON rp.funding_source_id = fs.id
            {$where}
            GROUP BY fs.type
        ", $params);

        $byMonth = $this->query("
            SELECT TO_CHAR(COALESCE(rp.submitted_at, rp.created_at), 'YYYY-MM') AS month_key,
                   COUNT(rp.id) AS total
            FROM {$this->table} rp
            LEFT JOIN funding_sources fs ON rp.funding_source_id = fs.id
            {$where}
            GROUP BY month_key ORDER BY month_key ASC LIMIT 12
        ", $params);

        $byField = $this->query("
            SELECT fos.name_th AS field_name, COALESCE(SUM(rp.budget_requested),0) AS total_budget
            FROM {$this->table} rp
            LEFT JOIN fields_of_study fos ON rp.field_of_study_id = fos.id
            LEFT JOIN funding_sources fs  ON rp.funding_source_id = fs.id
            {$where}
            GROUP BY fos.id, fos.name_th ORDER BY total_budget DESC LIMIT 10
        ", $params);

        $byStatusYear = $this->query("
            SELECT rp.budget_year, rp.status, COUNT(rp.id) AS total
            FROM {$this->table} rp
            LEFT JOIN funding_sources fs ON rp.funding_source_id = fs.id
            {$where}
            GROUP BY rp.budget_year, rp.status ORDER BY rp.budget_year DESC
        ", $params);

        $summary = $this->query("
            SELECT fs.name AS funding_name, fs.type,
                   COUNT(rp.id) AS total,
                   SUM(CASE WHEN rp.status='approved' THEN 1 ELSE 0 END) AS approved,
                   SUM(CASE WHEN rp.status='reviewing' THEN 1 ELSE 0 END) AS reviewing,
                   SUM(CASE WHEN rp.status='rejected' THEN 1 ELSE 0 END) AS rejected,
                   COALESCE(SUM(rp.budget_requested),0) AS total_budget
            FROM {$this->table} rp
            LEFT JOIN funding_sources fs ON rp.funding_source_id = fs.id
            {$where}
            GROUP BY fs.id, fs.name, fs.type ORDER BY total DESC
        ", $params);

        return compact('byFunding', 'byType', 'byMonth', 'byField', 'byStatusYear', 'summary');
    }

    public function getKpis(array $filters = []): array
    {
        $data = $this->getForDashboard($filters);

        $totalRow = $this->queryOne("SELECT COUNT(*) AS c, COALESCE(SUM(budget_requested),0) AS b FROM {$this->table}", []);
        $byStatus = $this->countByStatus();

        $projectStats = $this->queryOne("
            SELECT
              SUM(CASE WHEN status='in_progress' THEN 1 ELSE 0 END) AS in_progress,
              SUM(CASE WHEN status='closed' THEN 1 ELSE 0 END) AS closed
            FROM research_projects
        ", []);

        return [
            'total_proposals' => (int)($totalRow['c'] ?? 0),
            'total_budget'    => (float)($totalRow['b'] ?? 0),
            'reviewing'       => $byStatus['reviewing'],
            'approved'        => $byStatus['approved'],
            'in_progress'     => (int)($projectStats['in_progress'] ?? 0),
            'closed'          => (int)($projectStats['closed'] ?? 0),
        ];
    }

    public function getYears(): array
    {
        return array_column(
            $this->query("SELECT DISTINCT budget_year FROM {$this->table} WHERE budget_year IS NOT NULL ORDER BY budget_year DESC", []),
            'budget_year'
        );
    }

    // ── Auto-create Project ───────────────────────────────────

    public function createProjectFromProposal(int $proposalId, ?int $approvedBy): int
    {
        $existing = $this->queryOne("SELECT id FROM research_projects WHERE proposal_id = ? LIMIT 1", [$proposalId]);
        if ($existing) return (int)$existing['id'];

        $proposal = $this->getById($proposalId);
        if (!$proposal) return 0;

        $year = $proposal['budget_year'] ?? (int)date('Y');
        $cnt  = (int)($this->queryOne("SELECT COUNT(*) AS c FROM research_projects", [])['c'] ?? 0);
        $code = sprintf('PRJ-%d-%03d', $year, $cnt + 1);

        return $this->execute(
            "INSERT INTO research_projects
             (proposal_id, project_code, status, approved_date, approved_budget, approved_by, created_at, updated_at)
             VALUES (?, ?, 'approved', CURRENT_DATE, ?, ?, NOW(), NOW())",
            [$proposalId, $code, $proposal['budget_requested'], $approvedBy]
        ) ? (int)$this->getPdo()->lastInsertId() : 0;
    }

    // ── Dropdowns ─────────────────────────────────────────────

    public function getFundingSources(): array
    {
        return $this->query("SELECT id, name, type FROM funding_sources WHERE is_active=1 ORDER BY name", []);
    }

    public function getFieldsOfStudy(): array
    {
        return $this->query("SELECT id, code, name_th, name_en, faculty FROM fields_of_study ORDER BY faculty, name_th", []);
    }

    public function getResearchers(): array
    {
        return $this->query(
            "SELECT id, name, department, email FROM users WHERE is_active=1 ORDER BY name", []
        );
    }

    public function getRecentProposals(int $limit = 10): array
    {
        return $this->query("
            SELECT rp.id, rp.proposal_code, rp.title_th, rp.status, rp.created_at, COALESCE(rp.pi_name, u.name) AS pi_name
            FROM {$this->table} rp
            LEFT JOIN users u ON rp.principal_investigator_id = u.id
            ORDER BY rp.created_at DESC LIMIT ?
        ", [$limit]);
    }
}
