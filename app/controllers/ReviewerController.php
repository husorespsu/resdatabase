<?php

class ReviewerController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    // ─── Expert Reviewer Registry ─────────────────────────────────────────────

    /**
     * List all expert reviewers with search.
     */
    public function index(): void
    {
        $search    = $_GET['search'] ?? '';
        $reviewers = (new ExpertReviewer())->getAll($search);

        $this->render('reviewers/index', [
            'pageTitle' => 'ทะเบียนผู้ทรงคุณวุฒิ',
            'reviewers' => $reviewers,
            'search'    => $search,
            'csrfToken' => $_SESSION['csrf_token'] ?? '',
        ]);
    }

    /**
     * Redirect to index with ?action=create so the create modal is triggered.
     */
    public function create(): void
    {
        $this->redirect('/reviewers?action=create');
    }

    /**
     * Store a new reviewer.
     */
    public function store(): void
    {
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flashRedirect('error', 'การตรวจสอบความปลอดภัยล้มเหลว', '/reviewers');
        }

        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name']  ?? '');

        if ($firstName === '' || $lastName === '') {
            $this->flashRedirect('error', 'กรุณากรอกชื่อและนามสกุล', '/reviewers?action=create');
        }

        $data = [
            'title'          => sanitizeInput($_POST['title']          ?? ''),
            'first_name'     => sanitizeInput($firstName),
            'last_name'      => sanitizeInput($lastName),
            'expertise'      => sanitizeInput($_POST['expertise']      ?? ''),
            'institution'    => sanitizeInput($_POST['institution']    ?? ''),
            'position'       => sanitizeInput($_POST['position']       ?? ''),
            'email'          => sanitizeInput($_POST['email']          ?? ''),
            'phone'          => sanitizeInput($_POST['phone']          ?? ''),
            'bank_name'      => sanitizeInput($_POST['bank_name']      ?? ''),
            'bank_account'   => sanitizeInput($_POST['bank_account']   ?? ''),
            'bank_branch'    => sanitizeInput($_POST['bank_branch']    ?? ''),
            'id_card_number' => sanitizeInput($_POST['id_card_number'] ?? ''),
            'address'        => sanitizeInput($_POST['address']        ?? ''),
        ];

        $id = (new ExpertReviewer())->createReviewer($data);

        if ($id) {
            $this->flashRedirect('success', 'เพิ่มผู้ทรงคุณวุฒิเรียบร้อยแล้ว', '/reviewers');
        } else {
            $this->flashRedirect('error', 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', '/reviewers?action=create');
        }
    }

    /**
     * Redirect to index with edit modal query param.
     */
    public function edit(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $this->redirect("/reviewers?action=edit&id={$id}");
    }

    /**
     * Update an existing reviewer.
     */
    public function update(array $params): void
    {
        $id = (int)($params['id'] ?? 0);

        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flashRedirect('error', 'การตรวจสอบความปลอดภัยล้มเหลว', '/reviewers');
        }

        $reviewerModel = new ExpertReviewer();
        $reviewer      = $reviewerModel->getById($id);

        if (!$reviewer) {
            $this->flashRedirect('error', 'ไม่พบข้อมูลผู้ทรงคุณวุฒิ', '/reviewers');
        }

        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name']  ?? '');

        if ($firstName === '' || $lastName === '') {
            $this->flashRedirect('error', 'กรุณากรอกชื่อและนามสกุล', "/reviewers?action=edit&id={$id}");
        }

        $data = [
            'title'          => sanitizeInput($_POST['title']          ?? ''),
            'first_name'     => sanitizeInput($firstName),
            'last_name'      => sanitizeInput($lastName),
            'expertise'      => sanitizeInput($_POST['expertise']      ?? ''),
            'institution'    => sanitizeInput($_POST['institution']    ?? ''),
            'position'       => sanitizeInput($_POST['position']       ?? ''),
            'email'          => sanitizeInput($_POST['email']          ?? ''),
            'phone'          => sanitizeInput($_POST['phone']          ?? ''),
            'bank_name'      => sanitizeInput($_POST['bank_name']      ?? ''),
            'bank_account'   => sanitizeInput($_POST['bank_account']   ?? ''),
            'bank_branch'    => sanitizeInput($_POST['bank_branch']    ?? ''),
            'id_card_number' => sanitizeInput($_POST['id_card_number'] ?? ''),
            'address'        => sanitizeInput($_POST['address']        ?? ''),
        ];

        $result = $reviewerModel->updateReviewer($id, $data);

        if ($result) {
            $this->flashRedirect('success', 'บันทึกข้อมูลเรียบร้อยแล้ว', '/reviewers');
        } else {
            $this->flashRedirect('error', 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', "/reviewers?action=edit&id={$id}");
        }
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggle(array $params): void
    {
        $id = (int)($params['id'] ?? 0);

        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
                $this->json(['success' => false, 'message' => 'CSRF validation failed'], 403);
            }
            $this->flashRedirect('error', 'การตรวจสอบความปลอดภัยล้มเหลว', '/reviewers');
        }

        $result = (new ExpertReviewer())->toggleActive($id);

        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            $this->json([
                'success' => (bool)$result,
                'message' => $result ? 'เปลี่ยนสถานะเรียบร้อย' : 'เกิดข้อผิดพลาด',
            ]);
            return;
        }

        if ($result) {
            $this->flashRedirect('success', 'เปลี่ยนสถานะเรียบร้อย', '/reviewers');
        } else {
            $this->flashRedirect('error', 'เกิดข้อผิดพลาด', '/reviewers');
        }
    }

    // ─── Assign Reviewers ─────────────────────────────────────────────────────

    /**
     * Show the assign-reviewers page for a proposal.
     */
    public function assignForm(array $params): void
    {
        $proposalId = (int)($params['id'] ?? 0);
        $proposal   = (new Proposal())->getById($proposalId);

        if (!$proposal) {
            $this->flashRedirect('error', 'ไม่พบข้อมูลข้อเสนอโครงการ', '/proposals');
        }

        $reviewModel    = new ProposalReview();
        $currentReviews = $reviewModel->getByProposal($proposalId);
        $assignedCount  = count($currentReviews);
        $slotsRemaining = max(0, 3 - $assignedCount);
        $assignedIds    = array_column($currentReviews, 'reviewer_id');
        $allReviewers   = (new ExpertReviewer())->getActiveList();

        $this->render('reviewers/assign', [
            'pageTitle'      => 'มอบหมายผู้ทรงคุณวุฒิ',
            'proposalId'     => $proposalId,
            'proposal'       => $proposal,
            'currentReviews' => $currentReviews,
            'assignedCount'  => $assignedCount,
            'slotsRemaining' => $slotsRemaining,
            'assignedIds'    => $assignedIds,
            'allReviewers'   => $allReviewers,
            'csrfToken'      => $_SESSION['csrf_token'] ?? '',
        ]);
    }

    /**
     * Store a reviewer assignment.
     */
    public function assignStore(array $params): void
    {
        $proposalId = (int)($params['id'] ?? 0);

        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flashRedirect('error', 'การตรวจสอบความปลอดภัยล้มเหลว', "/proposals/{$proposalId}/assign-reviewers");
        }

        $proposal = (new Proposal())->getById($proposalId);
        if (!$proposal) {
            $this->flashRedirect('error', 'ไม่พบข้อมูลข้อเสนอโครงการ', '/proposals');
        }

        $reviewModel = new ProposalReview();

        if ($reviewModel->countByProposal($proposalId) >= 3) {
            $this->flashRedirect('error', 'ไม่สามารถมอบหมายผู้ทรงคุณวุฒิได้เกิน 3 คน', "/proposals/{$proposalId}/assign-reviewers");
        }

        $user   = getCurrentUser();
        $userId = (int)($user['id'] ?? 0);

        $data = [
            'proposal_id'   => $proposalId,
            'reviewer_id'   => (int)($_POST['reviewer_id']   ?? 0),
            'assigned_date' => $_POST['assigned_date'] ?? date('Y-m-d'),
            'due_date'      => $_POST['due_date']       ?? null,
            'created_by'    => $userId,
        ];

        $reviewId = $reviewModel->assign($data);

        if ($reviewId) {
            $notifModel = new Notification();
            $notifModel->createForAdmin(
                'review_assigned',
                'มอบหมายผู้ทรงคุณวุฒิใหม่',
                "มอบหมายผู้ทรงคุณวุฒิสำหรับโครงการ: {$proposal['title_th']}",
                'proposal_reviews',
                $reviewId
            );
            $this->flashRedirect('success', 'มอบหมายผู้ทรงคุณวุฒิเรียบร้อยแล้ว', "/proposals/{$proposalId}/assign-reviewers");
        } else {
            $this->flashRedirect('error', 'เกิดข้อผิดพลาด หรือผู้ทรงคุณวุฒิท่านนี้ได้รับมอบหมายแล้ว', "/proposals/{$proposalId}/assign-reviewers");
        }
    }

    // ─── Invitation Letter ────────────────────────────────────────────────────

    /**
     * Show invitation letter form/detail for a review.
     */
    public function invitation(array $params): void
    {
        $reviewId = (int)($params['id'] ?? 0);
        $review   = (new ProposalReview())->getById($reviewId);

        if (!$review) {
            $this->flashRedirect('error', 'ไม่พบข้อมูลการพิจารณา', '/proposals');
        }

        $this->render('reviewers/invitation', [
            'pageTitle' => 'หนังสือเชิญผู้ทรงคุณวุฒิ',
            'review'    => $review,
            'csrfToken' => $_SESSION['csrf_token'] ?? '',
        ]);
    }

    /**
     * Save invitation letter data including optional PDF upload.
     */
    public function saveInvitation(array $params): void
    {
        $reviewId = (int)($params['id'] ?? 0);

        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flashRedirect('error', 'การตรวจสอบความปลอดภัยล้มเหลว', "/reviews/{$reviewId}/invitation");
        }

        $reviewModel = new ProposalReview();
        $review      = $reviewModel->getById($reviewId);

        if (!$review) {
            $this->flashRedirect('error', 'ไม่พบข้อมูลการพิจารณา', '/proposals');
        }

        $data = [
            'invitation_letter_number' => sanitizeInput($_POST['invitation_letter_number'] ?? ''),
            'invitation_sent_date'     => $_POST['invitation_sent_date'] ?? null,
        ];

        // Handle optional PDF upload
        if (!empty($_FILES['invitation_file']['name'])) {
            $uploadDir = BASE_PATH . '/public/uploads/invitations/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $file    = $_FILES['invitation_file'];
            $allowed = ['pdf', 'doc', 'docx'];
            $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed, true)) {
                $this->flashRedirect('error', 'ประเภทไฟล์ไม่ถูกต้อง (รองรับ PDF, DOC, DOCX)', "/reviews/{$reviewId}/invitation");
            }

            $newName = 'inv_' . $reviewId . '_' . time() . '.' . $ext;

            if (move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
                $data['invitation_file_path'] = '/uploads/invitations/' . $newName;
            } else {
                $this->flashRedirect('error', 'ไม่สามารถอัปโหลดไฟล์ได้', "/reviews/{$reviewId}/invitation");
            }
        }

        $result = $reviewModel->saveInvitation($reviewId, $data);

        if ($result) {
            $this->flashRedirect('success', 'บันทึกข้อมูลหนังสือเชิญเรียบร้อยแล้ว', "/reviews/{$reviewId}/invitation");
        } else {
            $this->flashRedirect('error', 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', "/reviews/{$reviewId}/invitation");
        }
    }

    /**
     * Generate and stream an invitation letter PDF using TCPDF.
     */
    public function generateInvitationPDF(array $params): void
    {
        $reviewId    = (int)($params['id'] ?? 0);
        $reviewModel = new ProposalReview();
        $review      = $reviewModel->getById($reviewId);

        if (!$review) {
            $this->flashRedirect('error', 'ไม่พบข้อมูลการพิจารณา', '/proposals');
        }

        $tcpdfPath = BASE_PATH . '/vendor/tecnickcom/tcpdf/tcpdf.php';
        if (!file_exists($tcpdfPath)) {
            $this->flashRedirect('error', 'ไม่พบไลบรารี TCPDF', "/reviews/{$reviewId}/invitation");
        }

        require_once $tcpdfPath;

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('PSU Research System');
        $pdf->SetAuthor('มหาวิทยาลัยสงขลานครินทร์');
        $pdf->SetTitle('หนังสือเชิญผู้ทรงคุณวุฒิ');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(25, 20, 20);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();
        $pdf->SetFont('freeserif', '', 14);

        $letterNo      = htmlspecialchars($review['invitation_letter_number'] ?? 'ที่ .../..........', ENT_QUOTES, 'UTF-8');
        $sentDate      = !empty($review['invitation_sent_date'])
            ? $this->formatThaiDate($review['invitation_sent_date'])
            : date('d/m/Y');
        $reviewerName  = htmlspecialchars($review['reviewer_full_name'] ?? '', ENT_QUOTES, 'UTF-8');
        $institution   = htmlspecialchars($review['institution']        ?? '', ENT_QUOTES, 'UTF-8');
        $proposalTitle = htmlspecialchars($review['proposal_title']     ?? '', ENT_QUOTES, 'UTF-8');
        $proposalCode  = htmlspecialchars($review['proposal_code']      ?? '', ENT_QUOTES, 'UTF-8');
        $dueDate       = !empty($review['due_date'])
            ? $this->formatThaiDate($review['due_date'])
            : '.....................';

        // PSU Blue header bar
        $pdf->SetFillColor(0, 59, 109);
        $pdf->Rect(0, 0, 210, 12, 'F');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('freeserif', 'B', 13);
        $pdf->SetXY(0, 1);
        $pdf->Cell(210, 10, 'มหาวิทยาลัยสงขลานครินทร์  Prince of Songkla University', 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('freeserif', '', 14);
        $pdf->Ln(8);

        $html = <<<HTML
<table cellpadding="4">
  <tr>
    <td width="55%"></td>
    <td width="45%" align="right">
      <strong>{$letterNo}</strong><br>
      กองบริหารงานวิจัย มหาวิทยาลัยสงขลานครินทร์<br>
      วันที่ {$sentDate}
    </td>
  </tr>
</table>
<br>
<p align="center"><strong>หนังสือเชิญผู้ทรงคุณวุฒิ</strong></p>
<br>
<p>เรียน {$reviewerName}<br>{$institution}</p>
<p style="text-indent:2em;">
ด้วยมหาวิทยาลัยสงขลานครินทร์ มีความประสงค์ขอความอนุเคราะห์จากท่านในการพิจารณาตรวจประเมินโครงการวิจัย
เรื่อง <strong>&quot;{$proposalTitle}&quot;</strong> (รหัส {$proposalCode})
ซึ่งเป็นโครงการที่ยื่นขอรับทุนสนับสนุนการวิจัยจากมหาวิทยาลัยสงขลานครินทร์
</p>
<p style="text-indent:2em;">
ในการนี้ มหาวิทยาลัยฯ ใคร่ขอความกรุณาจากท่านในการพิจารณาให้ความเห็นและข้อเสนอแนะ
พร้อมส่งผลการพิจารณากลับมายังมหาวิทยาลัยฯ ภายในวันที่ <strong>{$dueDate}</strong>
</p>
<p style="text-indent:2em;">จึงเรียนมาเพื่อโปรดพิจารณา และขอขอบพระคุณเป็นอย่างสูงในความอนุเคราะห์ครั้งนี้</p>
<br><br>
<p align="center">
ขอแสดงความนับถือ<br><br><br>
(...................................................)<br>
ผู้อำนวยการกองบริหารงานวิจัย<br>
มหาวิทยาลัยสงขลานครินทร์
</p>
HTML;

        $pdf->writeHTML($html, true, false, true, false, '');

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="invitation_' . $reviewId . '.pdf"');

        $pdf->Output('invitation_' . $reviewId . '.pdf', 'I');
        exit;
    }

    // ─── Review Result ────────────────────────────────────────────────────────

    /**
     * Save review result submitted by a reviewer.
     */
    public function saveResult(array $params): void
    {
        $reviewId = (int)($params['id'] ?? 0);

        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flashRedirect('error', 'การตรวจสอบความปลอดภัยล้มเหลว', '/proposals');
        }

        $reviewModel = new ProposalReview();
        $review      = $reviewModel->getById($reviewId);

        if (!$review) {
            $this->flashRedirect('error', 'ไม่พบข้อมูลการพิจารณา', '/proposals');
        }

        $proposalId = (int)($review['proposal_id'] ?? 0);

        $data = [
            'review_result'   => $_POST['review_result']   ?? null,
            'review_score'    => isset($_POST['review_score']) ? (int)$_POST['review_score'] : null,
            'review_comments' => sanitizeInput($_POST['review_comments'] ?? ''),
            'received_date'   => $_POST['received_date']   ?? null,
        ];

        $result = $reviewModel->saveResult($reviewId, $data);

        if ($result) {
            $this->flashRedirect('success', 'บันทึกผลการพิจารณาเรียบร้อยแล้ว', "/proposals/{$proposalId}/assign-reviewers");
        } else {
            $this->flashRedirect('error', 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', "/proposals/{$proposalId}/assign-reviewers");
        }
    }

    // ─── Payments ─────────────────────────────────────────────────────────────

    /**
     * Save payment info for a review.
     */
    public function savePayment(array $params): void
    {
        $reviewId = (int)($params['id'] ?? 0);

        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flashRedirect('error', 'การตรวจสอบความปลอดภัยล้มเหลว', '/payments');
        }

        $reviewModel = new ProposalReview();
        $review      = $reviewModel->getById($reviewId);

        if (!$review) {
            $this->flashRedirect('error', 'ไม่พบข้อมูลการพิจารณา', '/payments');
        }

        $data = [
            'payment_amount'    => isset($_POST['payment_amount'])    ? (float)$_POST['payment_amount']    : null,
            'payment_date'      => $_POST['payment_date']             ?? null,
            'payment_status'    => $_POST['payment_status']           ?? 'pending',
            'payment_reference' => sanitizeInput($_POST['payment_reference'] ?? ''),
        ];

        $result = $reviewModel->savePayment($reviewId, $data);

        if ($result) {
            $this->flashRedirect('success', 'บันทึกข้อมูลการจ่ายเงินเรียบร้อยแล้ว', '/payments');
        } else {
            $this->flashRedirect('error', 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', '/payments');
        }
    }

    /**
     * List all payments with filters.
     */
    public function payments(): void
    {
        $filters = [
            'payment_status' => $_GET['payment_status'] ?? '',
            'search'         => $_GET['search']         ?? '',
            'date_from'      => $_GET['date_from']      ?? '',
            'date_to'        => $_GET['date_to']        ?? '',
        ];

        $reviewModel = new ProposalReview();
        $payments    = $reviewModel->getAllPayments($filters);
        $summary     = $reviewModel->getPaymentSummary();

        $this->render('reviewers/payment', [
            'pageTitle' => 'รายการค่าตอบแทนผู้ทรงคุณวุฒิ',
            'payments'  => $payments,
            'summary'   => $summary,
            'filters'   => $filters,
            'csrfToken' => $_SESSION['csrf_token'] ?? '',
        ]);
    }

    /**
     * Export payment report to Excel.
     */
    public function exportPayments(): void
    {
        $filters = [
            'payment_status' => $_GET['payment_status'] ?? '',
            'search'         => $_GET['search']         ?? '',
            'date_from'      => $_GET['date_from']      ?? '',
            'date_to'        => $_GET['date_to']        ?? '',
        ];

        $payments    = (new ProposalReview())->getAllPayments($filters);
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('ค่าตอบแทนผู้ทรงคุณวุฒิ');

        $headers = [
            'A' => 'ลำดับ',
            'B' => 'โครงการ',
            'C' => 'ผู้ทรงคุณวุฒิ',
            'D' => 'จำนวนเงิน',
            'E' => 'วันที่จ่าย',
            'F' => 'เลขอ้างอิง',
            'G' => 'สถานะ',
        ];

        foreach ($headers as $col => $label) {
            $cell = $col . '1';
            $sheet->setCellValue($cell, $label);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF003B6D');
            $sheet->getStyle($cell)->getFont()->getColor()->setARGB('FFFFFFFF');
        }

        $row = 2;
        foreach ($payments as $i => $p) {
            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $p['proposal_title']      ?? '');
            $sheet->setCellValue("C{$row}", $p['reviewer_full_name']  ?? '');
            $sheet->setCellValue("D{$row}", $p['payment_amount']      ?? 0);
            $sheet->setCellValue("E{$row}", $p['payment_date']        ?? '');
            $sheet->setCellValue("F{$row}", $p['payment_reference']   ?? '');
            $sheet->setCellValue("G{$row}", $p['payment_status']      ?? '');
            $row++;
        }

        foreach (array_keys($headers) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="payment_report.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function formatThaiDate(string $date): string
    {
        $thaiMonths = [
            1  => 'มกราคม',   2  => 'กุมภาพันธ์', 3  => 'มีนาคม',
            4  => 'เมษายน',   5  => 'พฤษภาคม',    6  => 'มิถุนายน',
            7  => 'กรกฎาคม',  8  => 'สิงหาคม',    9  => 'กันยายน',
            10 => 'ตุลาคม',   11 => 'พฤศจิกายน',  12 => 'ธันวาคม',
        ];

        $ts    = strtotime($date);
        $day   = (int)date('j', $ts);
        $month = (int)date('n', $ts);
        $year  = (int)date('Y', $ts) + 543;

        return "{$day} {$thaiMonths[$month]} {$year}";
    }

}
