<?php

declare(strict_types=1);

class ProposalController extends Controller
{
    private Proposal       $proposalModel;
    private ProposalReview $reviewModel;

    public function __construct()
    {
        parent::__construct();
        $this->proposalModel = new Proposal();
        $this->reviewModel   = new ProposalReview();
    }

    // ── List ──────────────────────────────────────────────────

    public function index(): void
    {
        $filters = array_filter([
            'status'            => $_GET['status']            ?? '',
            'funding_source_id' => $_GET['funding_source_id'] ?? '',
            'field_of_study_id' => $_GET['field_of_study_id'] ?? '',
            'budget_year'       => $_GET['budget_year']       ?? '',
            'search'            => $_GET['search']            ?? '',
        ]);

        $proposals      = $this->proposalModel->getAll($filters);
        $statusCounts   = $this->proposalModel->countByStatus();
        $fundingSources = $this->proposalModel->getFundingSources();
        $fieldsOfStudy  = $this->proposalModel->getFieldsOfStudy();
        $years          = $this->proposalModel->getYears();

        $this->render('proposals/index', [
            'pageTitle'      => 'ข้อเสนอโครงการวิจัย',
            'breadcrumbs'    => [['label' => 'ข้อเสนอโครงการ']],
            'proposals'      => $proposals,
            'statusCounts'   => $statusCounts,
            'fundingSources' => $fundingSources,
            'fieldsOfStudy'  => $fieldsOfStudy,
            'years'          => $years,
            'filters'        => array_merge([
                'status' => '', 'funding_source_id' => '', 'field_of_study_id' => '',
                'budget_year' => '', 'search' => '',
            ], $filters),
            'currentUser'    => $this->getCurrentUser(),
        ]);
    }

    // ── Create ────────────────────────────────────────────────

    public function create(): void
    {
        $this->render('proposals/create', [
            'pageTitle'      => 'เพิ่มข้อเสนอโครงการ',
            'breadcrumbs'    => [
                ['label' => 'ข้อเสนอโครงการ', 'url' => '/proposals'],
                ['label' => 'เพิ่มใหม่'],
            ],
            'fundingSources' => $this->proposalModel->getFundingSources(),
            'fieldsOfStudy'  => $this->proposalModel->getFieldsOfStudy(),
            'currentUser'    => $this->getCurrentUser(),
            'csrfToken'      => $_SESSION['csrf_token'] ?? '',
        ]);
    }

    // ── Store ─────────────────────────────────────────────────

    public function store(): void
    {
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flashRedirect('error', 'การตรวจสอบความปลอดภัยล้มเหลว', '/proposals/create');
        }

        $user   = $this->getCurrentUser();
        $errors = $this->validateInput($_POST);
        if ($errors) {
            $this->flashRedirect('error', implode('<br>', $errors), '/proposals/create');
        }

        $attachmentPath = $this->handleUpload($_FILES['attachment'] ?? []);

        $coInvestigators = [];
        foreach ((array)($_POST['co_investigators'] ?? []) as $name) {
            $name = trim($name);
            if ($name !== '') $coInvestigators[] = ['name' => $name];
        }

        $status = ($_POST['action'] ?? '') === 'submit' ? 'reviewing' : 'draft';

        // Validate reviewer count before moving to reviewing
        if ($status === 'reviewing') {
            // New proposal — can't have reviewers yet, force draft
            $status = 'draft';
            $this->setFlash('warning', 'ข้อเสนอใหม่จะถูกบันทึกเป็นฉบับร่างก่อน กรุณามอบหมายผู้ทรงคุณวุฒิก่อนส่งพิจารณา');
        }

        $data = [
            'title_th'                  => trim($_POST['title_th']),
            'title_en'                  => trim($_POST['title_en'] ?? '') ?: null,
            'principal_investigator_id' => null,
            'pi_name'                   => trim($_POST['pi_name'] ?? ''),
            'co_investigators'          => $coInvestigators,
            'funding_source_id'         => (int)($_POST['funding_source_id'] ?? 0) ?: null,
            'field_of_study_id'         => (int)($_POST['field_of_study_id'] ?? 0) ?: null,
            'budget_requested'          => (float)str_replace(',', '', $_POST['requested_budget'] ?? 0),
            'budget_year'               => (int)($_POST['budget_year'] ?? date('Y')),
            'start_date'                => $_POST['start_date'] ?: null,
            'end_date'                  => $_POST['end_date'] ?: null,
            'abstract'                  => trim($_POST['abstract_th'] ?? '') ?: null,
            'objectives'                => trim($_POST['objectives'] ?? '') ?: null,
            'methodology'               => trim($_POST['methodology'] ?? '') ?: null,
            'attachment_path'           => $attachmentPath,
            'status'                    => $status,
        ];

        $proposalId = $this->proposalModel->createProposal($data, (int)$user['id']);

        $notifModel = new Notification();
        $notifModel->createForAdmin('proposal_created', 'ข้อเสนอโครงการใหม่', "โครงการใหม่: {$data['title_th']}", 'research_proposals', $proposalId);

        $this->flashRedirect('success', 'บันทึกข้อเสนอโครงการสำเร็จ', "/proposals/{$proposalId}");
    }

    // ── Show ──────────────────────────────────────────────────

    public function show(array $params): void
    {
        $id       = (int)($params['id'] ?? 0);
        $proposal = $this->proposalModel->getById($id);

        if (!$proposal) {
            $this->flashRedirect('error', 'ไม่พบข้อมูลข้อเสนอโครงการ', '/proposals');
        }

        $reviewers = $this->reviewModel->getByProposal($id);

        $this->render('proposals/show', [
            'pageTitle'   => $proposal['title_th'],
            'breadcrumbs' => [
                ['label' => 'ข้อเสนอโครงการ', 'url' => '/proposals'],
                ['label' => h($proposal['proposal_code'])],
            ],
            'proposal'    => $proposal,
            'reviewers'   => $reviewers,
            'currentUser' => $this->getCurrentUser(),
            'csrfToken'   => $_SESSION['csrf_token'] ?? '',
        ]);
    }

    // ── Edit ──────────────────────────────────────────────────

    public function edit(array $params): void
    {
        $id       = (int)($params['id'] ?? 0);
        $proposal = $this->proposalModel->getById($id);

        if (!$proposal) {
            $this->flashRedirect('error', 'ไม่พบข้อมูลข้อเสนอโครงการ', '/proposals');
        }
        if (in_array($proposal['status'], ['approved', 'rejected'], true)) {
            $this->flashRedirect('error', 'ไม่สามารถแก้ไขข้อเสนอที่อนุมัติหรือปฏิเสธแล้ว', "/proposals/{$id}");
        }

        $this->render('proposals/edit', [
            'pageTitle'      => 'แก้ไขข้อเสนอโครงการ',
            'breadcrumbs'    => [
                ['label' => 'ข้อเสนอโครงการ', 'url' => '/proposals'],
                ['label' => h($proposal['proposal_code']), 'url' => "/proposals/{$id}"],
                ['label' => 'แก้ไข'],
            ],
            'proposal'       => $proposal,
            'fundingSources' => $this->proposalModel->getFundingSources(),
            'fieldsOfStudy'  => $this->proposalModel->getFieldsOfStudy(),
            'currentUser'    => $this->getCurrentUser(),
            'csrfToken'      => $_SESSION['csrf_token'] ?? '',
        ]);
    }

    // ── Update ────────────────────────────────────────────────

    public function update(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flashRedirect('error', 'การตรวจสอบความปลอดภัยล้มเหลว', "/proposals/{$id}/edit");
        }

        $user     = $this->getCurrentUser();
        $proposal = $this->proposalModel->getById($id);
        if (!$proposal) {
            $this->flashRedirect('error', 'ไม่พบข้อมูล', '/proposals');
        }

        $errors = $this->validateInput($_POST);
        if ($errors) {
            $this->flashRedirect('error', implode('<br>', $errors), "/proposals/{$id}/edit");
        }

        $attachmentPath = $proposal['attachment_path'];
        $newFile = $this->handleUpload($_FILES['attachment'] ?? []);
        if ($newFile !== null) {
            // Remove old file
            if ($attachmentPath && file_exists(BASE_PATH . '/public/uploads/' . $attachmentPath)) {
                @unlink(BASE_PATH . '/public/uploads/' . $attachmentPath);
            }
            $attachmentPath = $newFile;
        }

        $coInvestigators = [];
        foreach ((array)($_POST['co_investigators'] ?? []) as $name) {
            $name = trim($name);
            if ($name !== '') $coInvestigators[] = ['name' => $name];
        }

        $data = [
            'title_th'                  => trim($_POST['title_th']),
            'title_en'                  => trim($_POST['title_en'] ?? '') ?: null,
            'principal_investigator_id' => null,
            'pi_name'                   => trim($_POST['pi_name'] ?? ''),
            'co_investigators'          => $coInvestigators,
            'funding_source_id'         => (int)($_POST['funding_source_id'] ?? 0) ?: null,
            'field_of_study_id'         => (int)($_POST['field_of_study_id'] ?? 0) ?: null,
            'budget_requested'          => (float)str_replace(',', '', $_POST['requested_budget'] ?? 0),
            'budget_year'               => (int)($_POST['budget_year'] ?? date('Y')),
            'start_date'                => $_POST['start_date'] ?: null,
            'end_date'                  => $_POST['end_date'] ?: null,
            'abstract'                  => trim($_POST['abstract_th'] ?? '') ?: null,
            'objectives'                => trim($_POST['objectives'] ?? '') ?: null,
            'methodology'               => trim($_POST['methodology'] ?? '') ?: null,
            'attachment_path'           => $attachmentPath,
        ];

        $this->proposalModel->updateProposal($id, $data, (int)$user['id']);
        $this->flashRedirect('success', 'บันทึกการแก้ไขสำเร็จ', "/proposals/{$id}");
    }

    // ── Delete ────────────────────────────────────────────────

    public function delete(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->json(['success' => false, 'message' => 'CSRF ล้มเหลว'], 403);
        }

        $user     = $this->getCurrentUser();
        $proposal = $this->proposalModel->getById($id);
        if (!$proposal) {
            $this->json(['success' => false, 'message' => 'ไม่พบข้อมูล'], 404);
        }

        $this->proposalModel->update($id, ['status' => 'rejected', 'updated_at' => date('Y-m-d H:i:s')]);
        $this->logActivity((int)$user['id'], 'delete', $id);
        $this->json(['success' => true, 'redirect' => '/proposals']);
    }

    // ── Update Status ─────────────────────────────────────────

    public function updateStatus(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flashRedirect('error', 'การตรวจสอบความปลอดภัยล้มเหลว', "/proposals/{$id}");
        }

        $user      = $this->getCurrentUser();
        $proposal  = $this->proposalModel->getById($id);
        if (!$proposal) {
            $this->flashRedirect('error', 'ไม่พบข้อมูล', '/proposals');
        }

        $newStatus = $_POST['status'] ?? '';
        $valid     = $this->validTransitions($proposal['status']);
        if (!in_array($newStatus, $valid, true)) {
            $this->flashRedirect('error', 'การเปลี่ยนสถานะไม่ถูกต้อง', "/proposals/{$id}");
        }

        // Require 3 reviewers before moving to reviewing
        if ($newStatus === 'reviewing') {
            $count = $this->reviewModel->countByProposal($id);
            if ($count < 3) {
                $this->flashRedirect('error', "ต้องมีผู้ทรงคุณวุฒิครบ 3 ท่านก่อน (ปัจจุบัน: {$count} ท่าน)", "/proposals/{$id}");
            }
        }

        $updateData = ['status' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')];
        if ($newStatus === 'approved') {
            $updateData['approved_by'] = (int)$user['id'];
        }
        if (!empty($_POST['notes'])) {
            $updateData['notes'] = sanitizeInput($_POST['notes']);
        }

        $this->proposalModel->updateProposal($id, $updateData, (int)$user['id']);

        $label = thai_status_label($newStatus, 'proposal');
        $notifModel = new Notification();
        $notifModel->createForAdmin('status_changed', "สถานะโครงการเปลี่ยนเป็น: {$label}", $proposal['title_th'], 'research_proposals', $id);

        $this->flashRedirect('success', "เปลี่ยนสถานะเป็น \"{$label}\" สำเร็จ", "/proposals/{$id}");
    }

    // ── Helpers ───────────────────────────────────────────────

    private function validateInput(array $post): array
    {
        $errors = [];
        if (empty(trim($post['title_th'] ?? '')))             $errors[] = 'กรุณากรอกชื่อโครงการภาษาไทย';
        if (empty(trim($post['pi_name'] ?? '')))               $errors[] = 'กรุณาระบุหัวหน้าโครงการ';
        if (empty($post['funding_source_id']))                 $errors[] = 'กรุณาเลือกแหล่งทุน';
        if (empty($post['field_of_study_id']))                 $errors[] = 'กรุณาเลือกสาขาวิชา';
        if ((float)str_replace(',', '', $post['budget_requested'] ?? 0) <= 0)
                                                               $errors[] = 'กรุณากรอกงบประมาณที่ขอ';
        if (empty($post['budget_year']))                       $errors[] = 'กรุณาเลือกปีงบประมาณ';
        if (!empty($post['start_date']) && !empty($post['end_date'])) {
            if (strtotime($post['end_date']) <= strtotime($post['start_date']))
                $errors[] = 'วันที่สิ้นสุดต้องหลังจากวันที่เริ่มต้น';
        }
        return $errors;
    }

    private function handleUpload(array $file): ?string
    {
        if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->flashRedirect('error', 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์', '/proposals/create');
        }

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if ($mimeType !== 'application/pdf') {
            $this->flashRedirect('error', 'อนุญาตเฉพาะไฟล์ PDF เท่านั้น', '/proposals/create');
        }
        if ($file['size'] > 10 * 1024 * 1024) {
            $this->flashRedirect('error', 'ขนาดไฟล์ต้องไม่เกิน 10 MB', '/proposals/create');
        }

        $dir      = BASE_PATH . '/public/uploads/proposals/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $filename = 'proposal_' . time() . '_' . bin2hex(random_bytes(4)) . '.pdf';
        move_uploaded_file($file['tmp_name'], $dir . $filename);
        return 'proposals/' . $filename;
    }

    private function logActivity(int $userId, string $action, int $recordId): void
    {
        try {
            DatabaseConfig::getInstance()->prepare(
                "INSERT INTO activity_logs (user_id, action, table_name, record_id, ip_address, created_at)
                 VALUES (?, ?, 'research_proposals', ?, ?, NOW())"
            )->execute([$userId, $action, $recordId, $_SERVER['REMOTE_ADDR'] ?? null]);
        } catch (\Throwable) {
            // Non-fatal
        }
    }

    private function validTransitions(string $current): array
    {
        return match($current) {
            'draft'     => ['reviewing', 'rejected'],
            'reviewing' => ['approved', 'rejected'],
            'rejected'  => ['draft'],
            default     => [],
        };
    }
}
