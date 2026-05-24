<?php

class ProjectController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    // -------------------------------------------------------------------------
    // Public actions
    // -------------------------------------------------------------------------

    /**
     * List all projects with filters; supports Excel export.
     */
    public function index(): void
    {
        $filters = [
            'status'            => $_GET['status']            ?? '',
            'budget_year'       => $_GET['budget_year']       ?? '',
            'funding_source_id' => $_GET['funding_source_id'] ?? '',
            'field_of_study_id' => $_GET['field_of_study_id'] ?? '',
            'search'            => $_GET['search']            ?? '',
        ];
        $filters = array_filter($filters, fn($v) => $v !== '');

        $projectModel = new Project();

        // Handle Excel export
        if (($_GET['export'] ?? '') === 'excel') {
            $projects = $projectModel->getAll($filters);
            $this->exportExcel($projects);
            return;
        }

        $projects       = $projectModel->getAll($filters);
        $stats          = $projectModel->getStats();
        $fundingSources = $projectModel->getFundingSources();
        $fieldsOfStudy  = $projectModel->getFieldsOfStudy();
        $years          = $projectModel->getYears();

        $this->render('projects/index', [
            'pageTitle'      => 'โครงการวิจัย',
            'projects'       => $projects,
            'stats'          => $stats,
            'fundingSources' => $fundingSources,
            'fieldsOfStudy'  => $fieldsOfStudy,
            'years'          => $years,
            'filters'        => array_merge([
                'status'            => '',
                'budget_year'       => '',
                'funding_source_id' => '',
                'field_of_study_id' => '',
                'search'            => '',
            ], $filters),
        ]);
    }

    /**
     * Show create project form.
     */
    public function create(): void
    {
        $projectModel = new Project();
        $this->render('projects/create', [
            'pageTitle'          => 'เพิ่มโครงการวิจัย',
            'breadcrumbs'        => [
                ['label' => 'โครงการวิจัย', 'url' => '/projects'],
                ['label' => 'เพิ่มโครงการใหม่'],
            ],
            'availableProposals' => $projectModel->getAvailableProposals(),
            'csrfToken'          => $_SESSION['csrf_token'] ?? '',
        ]);
    }

    /**
     * Handle project create form submission.
     */
    public function store(): void
    {
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flashRedirect('error', 'การตรวจสอบความปลอดภัยล้มเหลว', '/projects/create');
        }

        $user = $this->getCurrentUser();

        $proposalId    = (int)($_POST['proposal_id'] ?? 0) ?: null;
        $approvedBudget = (float)str_replace(',', '', $_POST['approved_budget'] ?? 0);

        if ($approvedBudget <= 0) {
            $this->flashRedirect('error', 'กรุณากรอกงบประมาณที่อนุมัติ', '/projects/create');
        }

        $now = date('Y-m-d H:i:s');

        // Auto-generate project code
        $projectModel = new Project();
        $seq = $projectModel->count() + 1;
        $thaiYear = (int)date('Y') + 543;
        $projectCode = $_POST['project_code'] ?? "PRJ-{$thaiYear}-" . str_pad($seq, 3, '0', STR_PAD_LEFT);

        $data = [
            'proposal_id'         => $proposalId,
            'project_code'        => trim($projectCode),
            'status'              => 'approved',
            'approved_date'       => $_POST['approved_date'] ?: date('Y-m-d'),
            'approved_budget'     => $approvedBudget,
            'approved_by'         => (int)$user['id'],
            'contract_number'     => trim($_POST['contract_number'] ?? '') ?: null,
            'contract_date'       => $_POST['contract_date'] ?: null,
            'actual_start_date'   => $_POST['actual_start_date'] ?: null,
            'progress_percentage' => 0,
            'notes'               => trim($_POST['notes'] ?? '') ?: null,
            'created_at'          => $now,
            'updated_at'          => $now,
        ];

        $id = $projectModel->create($data);
        if (!$id) {
            $this->flashRedirect('error', 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', '/projects/create');
        }

        $projectModel->logActivity((int)$user['id'], 'create', $id);

        $notifModel = new Notification();
        $notifModel->createForAdmin(
            'project_created',
            'โครงการวิจัยใหม่',
            "สร้างโครงการ {$data['project_code']} แล้ว",
            'research_projects',
            $id
        );

        $this->flashRedirect('success', "บันทึกโครงการ {$data['project_code']} สำเร็จ", "/projects/{$id}");
    }

    /**
     * Show project detail.
     */
    public function show(array $params): void
    {
        $id           = (int)($params['id'] ?? 0);
        $projectModel = new Project();
        $project      = $projectModel->getById($id);

        if (!$project) {
            $this->flashRedirect('error', 'ไม่พบข้อมูลโครงการวิจัย', '/projects');
        }

        $breadcrumbs = [
            ['label' => 'โครงการวิจัย', 'url' => '/projects'],
            ['label' => $project['title_th'] ?? 'รายละเอียดโครงการ', 'url' => ''],
        ];

        $this->render('projects/show', [
            'pageTitle'   => $project['title_th'] ?? 'รายละเอียดโครงการ',
            'project'     => $project,
            'breadcrumbs' => $breadcrumbs,
            'csrfToken'   => $_SESSION['csrf_token'] ?? '',
        ]);
    }

    /**
     * Handle project updates: progress, status, notes.
     */
    public function update(array $params): void
    {
        $id = (int)($params['id'] ?? 0);

        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flashRedirect('error', 'การตรวจสอบความปลอดภัยล้มเหลว', "/projects/{$id}");
        }

        $projectModel = new Project();
        $project      = $projectModel->getById($id);

        if (!$project) {
            $this->flashRedirect('error', 'ไม่พบข้อมูลโครงการ', '/projects');
        }

        $user   = getCurrentUser();
        $userId = (int)($user['id'] ?? 0);
        $action = $_POST['action'] ?? '';

        if ($action === 'progress') {
            $pct    = (int)($_POST['progress_percentage'] ?? 0);
            $result = $projectModel->updateProgress($id, $pct);
            if ($result) {
                $this->setFlash('success', "อัปเดตความคืบหน้าเป็น {$pct}% สำเร็จ");
            } else {
                $this->setFlash('error', 'เกิดข้อผิดพลาดในการอัปเดตความคืบหน้า');
            }
            $this->redirect("/projects/{$id}");
        }

        if ($action === 'status') {
            $newStatus = $_POST['status'] ?? '';
            $result    = $projectModel->updateStatus($id, $newStatus, $userId);

            if ($result) {
                // Notify admins
                $notifModel = new Notification();
                $label      = thai_status_label($newStatus, 'project');
                $notifModel->createForAdmin(
                    'status_changed',
                    'สถานะโครงการเปลี่ยนแปลง',
                    "โครงการ \"{$project['title_th']}\" เปลี่ยนสถานะเป็น: {$label}",
                    'projects',
                    $id
                );
                $this->setFlash('success', "เปลี่ยนสถานะเป็น \"{$label}\" สำเร็จ");
            } else {
                $this->setFlash('error', 'เกิดข้อผิดพลาดในการเปลี่ยนสถานะ');
            }
            $this->redirect("/projects/{$id}");
        }

        if ($action === 'notes') {
            $result = $projectModel->update($id, [
                'notes'      => sanitizeInput($_POST['notes'] ?? ''),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            if ($result) {
                $this->setFlash('success', 'บันทึกหมายเหตุสำเร็จ');
            } else {
                $this->setFlash('error', 'เกิดข้อผิดพลาดในการบันทึกหมายเหตุ');
            }
            $this->redirect("/projects/{$id}");
        }

        $this->redirect("/projects/{$id}");
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function exportExcel(array $projects): void
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('โครงการวิจัย');

        $headers = [
            'A' => 'รหัสโครงการ',
            'B' => 'ชื่อโครงการ',
            'C' => 'หัวหน้า',
            'D' => 'แหล่งทุน',
            'E' => 'งบอนุมัติ',
            'F' => 'ความคืบหน้า%',
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
        foreach ($projects as $p) {
            $sheet->setCellValue("A{$row}", $p['project_code']        ?? '');
            $sheet->setCellValue("B{$row}", $p['title_th']            ?? '');
            $sheet->setCellValue("C{$row}", $p['pi_name']             ?? '');
            $sheet->setCellValue("D{$row}", $p['funding_source_name'] ?? '');
            $sheet->setCellValue("E{$row}", $p['approved_budget']     ?? 0);
            $sheet->setCellValue("F{$row}", ($p['progress_percentage'] ?? 0) . '%');
            $sheet->setCellValue("G{$row}", thai_status_label($p['status'] ?? '', 'project'));
            $row++;
        }

        foreach (array_keys($headers) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="projects_' . date('Ymd') . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
