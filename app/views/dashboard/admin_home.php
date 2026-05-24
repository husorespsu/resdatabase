<?php
/**
 * View: dashboard/admin_home.php
 * Variables: $currentUser, $statusCounts, $recentProposals, $recentProjects,
 *            $projectStats, $pendingReviews, $unreadCount
 */

// Thai date
$thaiMonths = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
               'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
$thaiDays   = ['Sunday'=>'อาทิตย์','Monday'=>'จันทร์','Tuesday'=>'อังคาร',
               'Wednesday'=>'พุธ','Thursday'=>'พฤหัสบดี','Friday'=>'ศุกร์','Saturday'=>'เสาร์'];
$todayThai  = 'วัน' . $thaiDays[date('l')] . 'ที่ ' . (int)date('j') . ' '
            . $thaiMonths[(int)date('n')] . ' พ.ศ. ' . ((int)date('Y') + 543);

$userName = h($currentUser['name'] ?? 'ผู้ดูแลระบบ');
$initials = mb_strtoupper(mb_substr($currentUser['name'] ?? 'A', 0, 1));

// KPI values
$cntDraft     = (int)($statusCounts['draft']     ?? 0);
$cntReviewing = (int)($statusCounts['reviewing'] ?? 0);
$cntApproved  = (int)($statusCounts['approved']  ?? 0);
$cntRejected  = (int)($statusCounts['rejected']  ?? 0);
$cntTotal     = $cntDraft + $cntReviewing + $cntApproved + $cntRejected;

$cntProjects  = (int)($projectStats['cnt_in_progress'] ?? 0);
$pendingCount = count($pendingReviews ?? []);

$statusBadgeMap = [
    'draft'     => ['secondary', 'ร่าง'],
    'reviewing' => ['warning',   'กำลังพิจารณา'],
    'approved'  => ['success',   'อนุมัติ'],
    'rejected'  => ['danger',    'ปฏิเสธ'],
];
$projectStatusMap = [
    'approved'    => ['primary',   'อนุมัติ'],
    'in_progress' => ['warning',   'กำลังดำเนินการ'],
    'completed'   => ['success',   'เสร็จสิ้น'],
    'closed'      => ['secondary', 'ปิดโครงการ'],
    'cancelled'   => ['danger',    'ยกเลิก'],
];
?>

<!-- ── Welcome Banner ─────────────────────────────────────────── -->
<div class="rounded-3 p-4 mb-4 text-white d-flex align-items-center justify-content-between gap-3"
     style="background:linear-gradient(135deg,#003B6D,#0066CC);">
    <div class="d-flex align-items-center gap-3">
        <?php if (!empty($currentUser['avatar'])): ?>
            <img src="<?= h($currentUser['avatar']) ?>"
                 class="rounded-circle border border-2 border-white"
                 style="width:56px;height:56px;object-fit:cover;" alt="">
        <?php else: ?>
            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold fs-4"
                 style="width:56px;height:56px;background:rgba(255,255,255,.25);">
                <?= $initials ?>
            </div>
        <?php endif; ?>
        <div>
            <h5 class="mb-0 fw-bold">ยินดีต้อนรับ, <?= $userName ?></h5>
            <div class="small opacity-75"><?= $todayThai ?></div>
        </div>
    </div>
    <div class="d-none d-md-flex gap-2">
        <a href="<?= BASE_URL ?>/proposals/create" class="btn btn-sm btn-light text-dark fw-semibold">
            <i class="fas fa-plus-circle me-1"></i>เพิ่มข้อเสนอ
        </a>
        <a href="<?= BASE_URL ?>/dashboard" class="btn btn-sm btn-outline-light">
            <i class="fas fa-chart-bar me-1"></i>Dashboard ผู้บริหาร
        </a>
    </div>
</div>

<!-- ── KPI Cards ─────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <?php
    $kpiCards = [
        ['label'=>'ข้อเสนอทั้งหมด',     'value'=>$cntTotal,     'color'=>'primary', 'icon'=>'fa-file-alt',    'href'=>'<?= BASE_URL ?>/proposals'],
        ['label'=>'รอพิจารณา',           'value'=>$cntReviewing, 'color'=>'warning', 'icon'=>'fa-hourglass-half','href'=>'<?= BASE_URL ?>/proposals?status=reviewing'],
        ['label'=>'โครงการดำเนินการ',    'value'=>$cntProjects,  'color'=>'success', 'icon'=>'fa-flask',        'href'=>'<?= BASE_URL ?>/projects?status=in_progress'],
        ['label'=>'การประเมินใกล้กำหนด', 'value'=>$pendingCount, 'color'=>'danger',  'icon'=>'fa-bell',         'href'=>'<?= BASE_URL ?>/payments'],
    ];
    foreach ($kpiCards as $k):
    ?>
    <div class="col-6 col-md-3">
        <a href="<?= $k['href'] ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-<?= $k['color'] ?> bg-opacity-10 p-3 flex-shrink-0"
                         style="width:52px;height:52px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas <?= $k['icon'] ?> text-<?= $k['color'] ?>"></i>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold text-<?= $k['color'] ?>"><?= $k['value'] ?></div>
                        <div class="small text-muted"><?= $k['label'] ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<!-- ── Main Content Row ───────────────────────────────────────── -->
<div class="row g-4">

    <!-- Recent Proposals -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                <span class="fw-semibold" style="color:#003B6D;">
                    <i class="fas fa-file-alt me-1"></i>ข้อเสนอโครงการล่าสุด
                </span>
                <a href="<?= BASE_URL ?>/proposals" class="btn btn-sm btn-outline-secondary">ดูทั้งหมด</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentProposals)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-2x d-block mb-2 opacity-50"></i>
                        ยังไม่มีข้อเสนอโครงการ
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr style="background:#003B6D;color:#fff;">
                                    <th class="ps-3 fw-semibold" style="white-space:nowrap;">รหัส</th>
                                    <th class="fw-semibold">ชื่อโครงการ</th>
                                    <th class="fw-semibold text-center" style="white-space:nowrap;">สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($recentProposals as $p):
                                [$bc, $bl] = $statusBadgeMap[$p['status'] ?? 'draft'] ?? ['secondary', $p['status']];
                            ?>
                                <tr>
                                    <td class="ps-3">
                                        <a href="<?= BASE_URL ?>/proposals/<?= (int)$p['id'] ?>"
                                           class="fw-semibold text-decoration-none" style="color:#003B6D;">
                                            <?= h($p['proposal_code'] ?? '-') ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="fw-semibold small" style="max-width:260px;">
                                            <?= h($p['title_th'] ?? '-') ?>
                                        </div>
                                        <?php if (!empty($p['pi_name'])): ?>
                                            <div class="text-muted" style="font-size:.78rem;">
                                                <i class="fas fa-user me-1"></i><?= h($p['pi_name']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $bc ?>"><?= $bl ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right column -->
    <div class="col-lg-5 d-flex flex-column gap-4">

        <!-- Pending Reviews -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                <span class="fw-semibold" style="color:#003B6D;">
                    <i class="fas fa-clock me-1"></i>การประเมินใกล้กำหนด
                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">7 วัน</span>
                </span>
                <a href="<?= BASE_URL ?>/payments" class="btn btn-sm btn-outline-secondary">ดูทั้งหมด</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($pendingReviews)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-check-circle text-success fa-2x d-block mb-2"></i>
                        <small>ไม่มีการประเมินที่ใกล้ครบกำหนด</small>
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($pendingReviews as $r):
                            $days = (int)($r['days_remaining'] ?? 0);
                            $dc = $days <= 1 ? 'danger' : ($days <= 3 ? 'warning' : 'success');
                        ?>
                            <li class="list-group-item px-3 py-2">
                                <div class="d-flex align-items-start gap-2">
                                    <div class="flex-grow-1">
                                        <div class="small fw-semibold text-truncate" style="max-width:220px;">
                                            <?= h($r['proposal_title'] ?? '-') ?>
                                        </div>
                                        <div class="text-muted" style="font-size:.75rem;">
                                            <i class="fas fa-user me-1"></i><?= h($r['reviewer_full_name'] ?? '-') ?>
                                        </div>
                                    </div>
                                    <span class="badge bg-<?= $dc ?> flex-shrink-0"><?= $days ?> วัน</span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Projects -->
        <div class="card border-0 shadow-sm flex-grow-1">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                <span class="fw-semibold" style="color:#003B6D;">
                    <i class="fas fa-flask me-1"></i>โครงการวิจัยล่าสุด
                </span>
                <a href="<?= BASE_URL ?>/projects" class="btn btn-sm btn-outline-secondary">ดูทั้งหมด</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentProjects)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-2x d-block mb-2 opacity-50"></i>
                        ยังไม่มีโครงการวิจัย
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentProjects as $rj):
                            $pct = (int)($rj['progress_percentage'] ?? 0);
                            $bc = $pct >= 100 ? 'success' : ($pct >= 60 ? 'primary' : ($pct >= 30 ? 'warning' : 'danger'));
                        ?>
                            <li class="list-group-item px-3 py-2">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <a href="<?= BASE_URL ?>/projects/<?= (int)$rj['id'] ?>"
                                       class="fw-semibold text-decoration-none small" style="color:#003B6D;">
                                        <?= h($rj['project_code'] ?? '-') ?>
                                    </a>
                                    <span class="small fw-bold text-<?= $bc ?>"><?= $pct ?>%</span>
                                </div>
                                <div class="progress" style="height:5px;">
                                    <div class="progress-bar bg-<?= $bc ?>" style="width:<?= $pct ?>%"></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- ── Quick Actions ─────────────────────────────────────────── -->
<div class="row g-3 mt-1">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2">
                <span class="fw-semibold" style="color:#003B6D;">
                    <i class="fas fa-bolt me-1"></i>การดำเนินการด่วน
                </span>
            </div>
            <div class="card-body d-flex flex-wrap gap-2">
                <a href="<?= BASE_URL ?>/proposals/create" class="btn btn-sm text-white" style="background:#003B6D;">
                    <i class="fas fa-plus-circle me-1"></i>เพิ่มข้อเสนอโครงการ
                </a>
                <a href="<?= BASE_URL ?>/projects/create" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-flask me-1"></i>เพิ่มโครงการวิจัย
                </a>
                <a href="<?= BASE_URL ?>/reviewers" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-user-tie me-1"></i>จัดการผู้ทรงคุณวุฒิ
                </a>
                <a href="<?= BASE_URL ?>/payments" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-money-bill-wave me-1"></i>การเงิน
                </a>
                <a href="<?= BASE_URL ?>/dashboard" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-chart-bar me-1"></i>Dashboard ผู้บริหาร
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ── Status Distribution ───────────────────────────────────── -->
<div class="row g-4 mt-0">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2">
                <span class="fw-semibold" style="color:#003B6D;">
                    <i class="fas fa-chart-pie me-1"></i>สถานะข้อเสนอโครงการ
                </span>
            </div>
            <div class="card-body">
                <?php
                $statusDisp = [
                    'draft'     => ['secondary', 'ร่าง'],
                    'reviewing' => ['warning',   'กำลังพิจารณา'],
                    'approved'  => ['success',   'อนุมัติ'],
                    'rejected'  => ['danger',    'ปฏิเสธ'],
                ];
                foreach ($statusDisp as $sk => [$sc, $sl]):
                    $cnt = (int)($statusCounts[$sk] ?? 0);
                    $pct = $cntTotal > 0 ? round($cnt / $cntTotal * 100) : 0;
                ?>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="small text-muted" style="width:120px;"><?= $sl ?></span>
                        <div class="progress flex-grow-1" style="height:10px;">
                            <div class="progress-bar bg-<?= $sc ?>" style="width:<?= $pct ?>%"></div>
                        </div>
                        <span class="badge bg-<?= $sc ?> ms-1"><?= $cnt ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2">
                <span class="fw-semibold" style="color:#003B6D;">
                    <i class="fas fa-flask me-1"></i>สถานะโครงการวิจัย
                </span>
            </div>
            <div class="card-body">
                <?php
                $projDisp = [
                    'cnt_approved'    => ['primary',   'อนุมัติ'],
                    'cnt_in_progress' => ['warning',   'กำลังดำเนินการ'],
                    'cnt_completed'   => ['success',   'เสร็จสิ้น'],
                    'cnt_cancelled'   => ['danger',    'ยกเลิก'],
                ];
                $pTotal = (int)($projectStats['total'] ?? 0);
                foreach ($projDisp as $pk => [$pc, $pl]):
                    $cnt = (int)($projectStats[$pk] ?? 0);
                    $pct = $pTotal > 0 ? round($cnt / $pTotal * 100) : 0;
                ?>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="small text-muted" style="width:120px;"><?= $pl ?></span>
                        <div class="progress flex-grow-1" style="height:10px;">
                            <div class="progress-bar bg-<?= $pc ?>" style="width:<?= $pct ?>%"></div>
                        </div>
                        <span class="badge bg-<?= $pc ?> ms-1"><?= $cnt ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if ($pTotal === 0): ?>
                    <div class="text-center text-muted small py-2">ยังไม่มีข้อมูลโครงการ</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
