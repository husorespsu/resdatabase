<?php

declare(strict_types=1);

class DashboardController extends Controller
{
    private Proposal $proposalModel;
    private Project  $projectModel;

    public function __construct()
    {
        parent::__construct();
        $this->proposalModel = new Proposal();
        $this->projectModel  = new Project();
    }

    // ── Admin Home Dashboard ──────────────────────────────────

    public function index(): void
    {
        $user = $this->getCurrentUser();

        // Executives go straight to the executive dashboard
        if ($user && $user['role'] === 'executive') {
            $this->redirect('/dashboard');
        }

        $statusCounts    = $this->proposalModel->countByStatus();
        $recentProposals = $this->proposalModel->getRecentProposals(10);
        $recentProjects  = $this->projectModel->getRecentProjects(5);
        $projectStats    = $this->projectModel->getStats();

        $reviewModel   = new ProposalReview();
        $pendingReviews = $reviewModel->getPendingDueDates();

        $notifModel  = new Notification();
        $unreadCount = $notifModel->getUnreadCount((int)($user['id'] ?? 0));

        $this->render('dashboard/admin_home', [
            'pageTitle'       => 'หน้าหลัก',
            'breadcrumbs'     => [['label' => 'หน้าหลัก']],
            'currentUser'     => $user,
            'statusCounts'    => $statusCounts,
            'recentProposals' => $recentProposals,
            'recentProjects'  => $recentProjects,
            'projectStats'    => $projectStats,
            'pendingReviews'  => $pendingReviews,
            'unreadCount'     => $unreadCount,
        ]);
    }

    // ── Executive Dashboard ───────────────────────────────────

    public function executive(): void
    {
        $user = $this->getCurrentUser();

        // Build filters from GET
        $filters = [
            'budget_year'     => isset($_GET['year']) && is_array($_GET['year']) ? $_GET['year'] : [],
            'month'           => isset($_GET['month']) && is_array($_GET['month']) ? $_GET['month'] : [],
            'field_of_study_id' => !empty($_GET['field_of_study_id']) ? (int)$_GET['field_of_study_id'] : null,
            'funding_type'    => $_GET['funding_type'] ?? '',
        ];
        // Remove empty budget_year filter
        if (empty($filters['budget_year'])) unset($filters['budget_year']);
        if (empty($filters['month'])) unset($filters['month']);
        if (empty($filters['funding_type'])) unset($filters['funding_type']);
        if (empty($filters['field_of_study_id'])) unset($filters['field_of_study_id']);

        $dashData = $this->proposalModel->getForDashboard($filters);
        $kpis     = $this->proposalModel->getKpis($filters);

        // Build month series for line chart (last 12 months)
        $monthSeries = $this->buildMonthSeries($dashData['byMonth'] ?? []);

        // Chart 5: stacked bar – need to pivot byStatusYear into series per status
        $statusYearPivot = $this->pivotStatusYear($dashData['byStatusYear'] ?? []);

        $filterYears  = $this->proposalModel->getYears();
        $filterFields = $this->proposalModel->getFieldsOfStudy();

        $notifModel  = new Notification();
        $unreadCount = $notifModel->getUnreadCount((int)($user['id'] ?? 0));

        $this->render('dashboard/index', [
            'pageTitle'       => 'แดชบอร์ดผู้บริหาร',
            'breadcrumbs'     => [['label' => 'แดชบอร์ดผู้บริหาร']],
            'kpis'            => $kpis,
            'byFunding'       => $dashData['byFunding']    ?? [],
            'byType'          => $dashData['byType']       ?? [],
            'monthSeries'     => $monthSeries,
            'byField'         => $dashData['byField']      ?? [],
            'statusYearPivot' => $statusYearPivot,
            'summary'         => $dashData['summary']      ?? [],
            'filterYears'     => $filterYears,
            'filterFields'    => $filterFields,
            'selectedYears'   => $_GET['year']   ?? [],
            'selectedMonths'  => $_GET['month']  ?? [],
            'selectedField'   => $_GET['field_of_study_id'] ?? '',
            'selectedFunding' => $_GET['funding_type'] ?? '',
            'unreadCount'     => $unreadCount,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────

    private function buildMonthSeries(array $rows): array
    {
        $thaiMonths = [
            '01' => 'ม.ค.', '02' => 'ก.พ.', '03' => 'มี.ค.',
            '04' => 'เม.ย.', '05' => 'พ.ค.', '06' => 'มิ.ย.',
            '07' => 'ก.ค.', '08' => 'ส.ค.', '09' => 'ก.ย.',
            '10' => 'ต.ค.', '11' => 'พ.ย.', '12' => 'ธ.ค.',
        ];

        $indexed = [];
        foreach ($rows as $r) {
            $indexed[$r['month_key']] = (int)$r['total'];
        }

        $labels = $values = [];
        for ($i = 11; $i >= 0; $i--) {
            $key    = date('Y-m', strtotime("-{$i} months"));
            [$y, $m] = explode('-', $key);
            $labels[] = $thaiMonths[$m] . ' ' . ((int)$y + 543);
            $values[] = $indexed[$key] ?? 0;
        }
        return ['labels' => $labels, 'values' => $values];
    }

    private function pivotStatusYear(array $rows): array
    {
        $years    = [];
        $statuses = ['draft', 'reviewing', 'approved', 'rejected'];
        $data     = [];

        foreach ($rows as $row) {
            $year   = $row['budget_year'] ?? 'N/A';
            $status = $row['status']      ?? '';
            if (!in_array($year, $years, true)) $years[] = $year;
            $data[$year][$status] = (int)$row['total'];
        }

        rsort($years);

        $series = [];
        foreach ($statuses as $st) {
            $vals = [];
            foreach ($years as $yr) {
                $vals[] = $data[$yr][$st] ?? 0;
            }
            $series[] = ['name' => thai_status_label($st, 'proposal'), 'data' => $vals];
        }

        return ['labels' => $years, 'series' => $series];
    }
}
