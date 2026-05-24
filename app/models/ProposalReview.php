<?php

declare(strict_types=1);

class ProposalReview extends Model
{
    protected string $table = 'proposal_reviews';

    public function getByProposal(int $proposalId): array
    {
        return $this->query("
            SELECT pr.*,
                   CONCAT(er.title,' ',er.first_name,' ',er.last_name) AS reviewer_full_name,
                   er.institution, er.expertise, er.email AS reviewer_email,
                   er.phone AS reviewer_phone,
                   er.bank_name, er.bank_account, er.bank_branch,
                   pr.review_score AS score
            FROM {$this->table} pr
            JOIN expert_reviewers er ON pr.reviewer_id = er.id
            WHERE pr.proposal_id = ?
            ORDER BY pr.created_at ASC
        ", [$proposalId]);
    }

    public function getById(int $id): ?array
    {
        return $this->queryOne("
            SELECT pr.*,
                   CONCAT(er.title,' ',er.first_name,' ',er.last_name) AS reviewer_full_name,
                   er.institution, er.expertise, er.email AS reviewer_email,
                   er.bank_name, er.bank_account, er.bank_branch, er.id_card_number,
                   rp.title_th AS proposal_title, rp.proposal_code, rp.budget_year,
                   rp.principal_investigator_id
            FROM {$this->table} pr
            JOIN expert_reviewers er ON pr.reviewer_id = er.id
            JOIN research_proposals rp ON pr.proposal_id = rp.id
            WHERE pr.id = ? LIMIT 1
        ", [$id]);
    }

    public function countByProposal(int $proposalId): int
    {
        $row = $this->queryOne(
            "SELECT COUNT(*) AS c FROM {$this->table} WHERE proposal_id = ?",
            [$proposalId]
        );
        return (int)($row['c'] ?? 0);
    }

    public function assign(array $data): int|false
    {
        if ($this->countByProposal((int)$data['proposal_id']) >= 3) {
            return false; // max 3 reviewers
        }

        // Check duplicate
        $dup = $this->queryOne(
            "SELECT id FROM {$this->table} WHERE proposal_id = ? AND reviewer_id = ? LIMIT 1",
            [$data['proposal_id'], $data['reviewer_id']]
        );
        if ($dup) return false;

        $data['review_result'] = 'pending';
        $data['payment_status'] = 'pending';
        $data['reminder_sent_count'] = 0;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->create($data);
    }

    public function saveResult(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->update($id, array_intersect_key($data, array_flip([
            'review_result', 'review_score', 'review_comments', 'received_date', 'updated_at'
        ])));
    }

    public function saveInvitation(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->update($id, array_intersect_key($data, array_flip([
            'invitation_letter_number', 'invitation_sent_date', 'invitation_file_path', 'updated_at'
        ])));
    }

    public function savePayment(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->update($id, array_intersect_key($data, array_flip([
            'payment_amount', 'payment_date', 'payment_status', 'payment_reference', 'updated_at'
        ])));
    }

    public function getAllPayments(array $filters = []): array
    {
        $sql = "SELECT pr.*,
                       CONCAT(er.title,' ',er.first_name,' ',er.last_name) AS reviewer_full_name,
                       er.institution, er.bank_name, er.bank_account,
                       rp.title_th AS proposal_title, rp.proposal_code, rp.budget_year,
                       pr.review_score AS score
                FROM {$this->table} pr
                JOIN expert_reviewers er ON pr.reviewer_id = er.id
                JOIN research_proposals rp ON pr.proposal_id = rp.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['payment_status'])) {
            $sql .= " AND pr.payment_status = ?";
            $params[] = $filters['payment_status'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (rp.title_th LIKE ? OR rp.proposal_code LIKE ? OR er.first_name LIKE ? OR er.last_name LIKE ?)";
            $like = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$like, $like, $like, $like]);
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND pr.payment_date >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND pr.payment_date <= ?";
            $params[] = $filters['date_to'];
        }

        $sql .= " ORDER BY pr.created_at DESC";
        return $this->query($sql, $params);
    }

    public function getPaymentSummary(): array
    {
        return $this->queryOne("
            SELECT
              SUM(CASE WHEN payment_status='pending' THEN 1 ELSE 0 END) AS pending_count,
              SUM(CASE WHEN payment_status='paid'    THEN 1 ELSE 0 END) AS paid_count,
              SUM(CASE WHEN payment_status='pending' THEN COALESCE(payment_amount,0) ELSE 0 END) AS pending_amount,
              SUM(CASE WHEN payment_status='paid'    THEN COALESCE(payment_amount,0) ELSE 0 END) AS paid_amount
            FROM {$this->table}
        ", []) ?: [];
    }

    public function getPendingDueDates(): array
    {
        return $this->query("
            SELECT pr.*,
                   CONCAT(er.title,' ',er.first_name,' ',er.last_name) AS reviewer_name,
                   er.email AS reviewer_email,
                   rp.title_th AS proposal_title, rp.proposal_code,
                   DATEDIFF(pr.due_date, CURDATE()) AS days_remaining
            FROM {$this->table} pr
            JOIN expert_reviewers er ON pr.reviewer_id = er.id
            JOIN research_proposals rp ON pr.proposal_id = rp.id
            WHERE pr.review_result = 'pending'
              AND pr.due_date IS NOT NULL
              AND DATEDIFF(pr.due_date, CURDATE()) <= 7
            ORDER BY pr.due_date ASC
        ", []);
    }

    public function getOverduePending(): array
    {
        return $this->query("
            SELECT pr.*,
                   CONCAT(er.title,' ',er.first_name,' ',er.last_name) AS reviewer_name,
                   er.email AS reviewer_email,
                   rp.title_th AS proposal_title, rp.proposal_code
            FROM {$this->table} pr
            JOIN expert_reviewers er ON pr.reviewer_id = er.id
            JOIN research_proposals rp ON pr.proposal_id = rp.id
            WHERE pr.review_result = 'pending'
              AND pr.due_date < CURDATE()
              AND pr.reminder_sent_count < 5
        ", []);
    }

    public function incrementReminder(int $id): bool
    {
        return $this->execute("
            UPDATE {$this->table}
            SET reminder_sent_count = reminder_sent_count + 1,
                last_reminder_sent_at = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ", [$id]);
    }
}
