<?php
/**
 * View: projects/show.php
 * Project detail with Bootstrap 5 tabbed layout - Thai UI
 */

function fmtBudgetP(?float $a): string {
    return $a === null ? '-' : '฿' . number_format($a, 2);
}

function fmtDateP(?string $d, bool $long = false): string {
    if (!$d) return '-';
    $ts = strtotime($d);
    if (!$ts) return '-';
    $long_months = [1=>'มกราคม',2=>'กุมภาพันธ์',3=>'มีนาคม',4=>'เมษายน',5=>'พฤษภาคม',6=>'มิถุนายน',
                    7=>'กรกฎาคม',8=>'สิงหาคม',9=>'กันยายน',10=>'ตุลาคม',11=>'พฤศจิกายน',12=>'ธันวาคม'];
    $short_months = [1=>'ม.ค.',2=>'ก.พ.',3=>'มี.ค.',4=>'เม.ย.',5=>'พ.ค.',6=>'มิ.ย.',
                     7=>'ก.ค.',8=>'ส.ค.',9=>'ก.ย.',10=>'ต.ค.',11=>'พ.ย.',12=>'ธ.ค.'];
    $day  = date('j', $ts);
    $mon  = $long ? $long_months[(int)date('n', $ts)] : $short_months[(int)date('n', $ts)];
    $year = date('Y', $ts) + 543;
    return "{$day} {$mon} {$year}";
}

function fmtFileSizeP(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

$statusMap = [
    'approved'    => ['primary',   'อนุมัติ'],
    'in_progress' => ['warning',   'กำลังดำเนินการ'],
    'completed'   => ['success',   'เสร็จสิ้น'],
    'closed'      => ['secondary', 'ปิดโครงการ'],
    'cancelled'   => ['danger',    'ยกเลิก'],
];

$statusTransitions = [
    'approved'    => [['in_progress', 'เริ่มดำเนินการ', 'warning'], ['cancelled', 'ยกเลิกโครงการ', 'danger']],
    'in_progress' => [['completed', 'ทำเครื่องหมายเสร็จสิ้น', 'success'], ['cancelled', 'ยกเลิกโครงการ', 'danger']],
    'completed'   => [['closed', 'ปิดโครงการ', 'secondary']],
    'closed'      => [],
    'cancelled'   => [],
];

$reviewerStatusMap = [
    'pending'                 => ['secondary', 'รอดำเนินการ'],
    'approved'                => ['success',   'ผ่านการพิจารณา'],
    'approved_with_condition' => ['warning',   'ผ่านมีเงื่อนไข'],
    'rejected'                => ['danger',    'ไม่ผ่านการพิจารณา'],
];

$paymentStatusMap = [
    'pending' => ['secondary', 'รอดำเนินการ'],
    'paid'    => ['success',   'จ่ายแล้ว'],
    'partial' => ['warning',   'จ่ายบางส่วน'],
];

[$statusColor, $statusLabel] = $statusMap[$project['status'] ?? ''] ?? ['secondary', '-'];
$validTransitions = $statusTransitions[$project['status'] ?? ''] ?? [];
$isAdmin = in_array($currentUser['role'] ?? '', ['admin', 'superadmin']);
$pct = (int)($project['progress_percentage'] ?? 0);
$barColor = $pct >= 100 ? 'success' : ($pct >= 60 ? 'primary' : ($pct >= 30 ? 'warning' : 'danger'));

// Detect active tab from URL hash (via GET param fallback)
$activeTab = $_GET['tab'] ?? 'info';
$tabMap = ['info' => 0, 'reviewers' => 1, 'progress' => 2, 'documents' => 3];
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/projects">โครงการวิจัย</a></li>
        <li class="breadcrumb-item active">รายละเอียด</li>
    </ol>
</nav>

<!-- Header -->
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-<?= $statusColor ?> fs-6"><?= $statusLabel ?></span>
            <code class="text-psu-blue fs-6"><?= htmlspecialchars($project['project_code'] ?? '') ?></code>
        </div>
        <h1 class="h4 fw-bold text-psu-blue mb-0"><?= htmlspecialchars($project['title_th'] ?? '') ?></h1>
        <?php if (!empty($project['proposal_title_en'])): ?>
        <p class="text-muted mt-1 mb-0 fst-italic"><?= htmlspecialchars($project['proposal_title_en']) ?></p>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-start">
        <?php if ($isAdmin && !empty($validTransitions)): ?>
        <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#statusModal">
            <i class="bi bi-arrow-repeat me-1"></i>เปลี่ยนสถานะ
        </button>
        <?php endif; ?>
        <?php if (!empty($project['proposal_id'])): ?>
        <a href="<?= BASE_URL ?>/proposals/<?= $project['proposal_id'] ?>" class="btn btn-outline-secondary">
            <i class="bi bi-file-earmark-text me-1"></i>ดูข้อเสนอเดิม
        </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/projects" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>กลับ
        </a>
    </div>
</div>

<!-- Quick stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="text-muted small mb-1">งบประมาณอนุมัติ</div>
            <div class="fw-bold text-success fs-5"><?= fmtBudgetP((float)($project['approved_budget'] ?? 0)) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="text-muted small mb-1">ความคืบหน้า</div>
            <div class="fw-bold text-<?= $barColor ?> fs-5"><?= $pct ?>%</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="text-muted small mb-1">วันที่เริ่ม</div>
            <div class="fw-bold"><?= fmtDateP($project['start_date'] ?? null) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="text-muted small mb-1">วันที่สิ้นสุด</div>
            <div class="fw-bold"><?= fmtDateP($project['end_date'] ?? null) ?></div>
        </div>
    </div>
</div>

<!-- Overall progress bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="fw-semibold">ความคืบหน้าโดยรวม</span>
            <span class="fw-bold text-<?= $barColor ?>"><?= $pct ?>%</span>
        </div>
        <div class="progress" style="height:16px; border-radius:8px;">
            <div class="progress-bar bg-<?= $barColor ?> progress-bar-striped <?= $pct < 100 ? 'progress-bar-animated' : '' ?>"
                 role="progressbar"
                 style="width:<?= $pct ?>%"
                 aria-valuenow="<?= $pct ?>"
                 aria-valuemin="0" aria-valuemax="100">
                <?php if ($pct > 10): ?><?= $pct ?>%<?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs nav-tabs-psu mb-0" id="projectTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link <?= $activeTab === 'info' ? 'active' : '' ?>" id="tab-info-btn"
                data-bs-toggle="tab" data-bs-target="#tab-info" type="button">
            <i class="bi bi-info-circle me-1"></i>ข้อมูลโครงการ
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link <?= $activeTab === 'reviewers' ? 'active' : '' ?>" id="tab-reviewers-btn"
                data-bs-toggle="tab" data-bs-target="#tab-reviewers" type="button">
            <i class="bi bi-people me-1"></i>ผู้ทรงคุณวุฒิ
            <span class="badge bg-<?= count($project['reviewers']) >= 3 ? 'success' : 'secondary' ?> ms-1">
                <?= count($project['reviewers']) ?>
            </span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link <?= $activeTab === 'progress' ? 'active' : '' ?>" id="tab-progress-btn"
                data-bs-toggle="tab" data-bs-target="#tab-progress" type="button">
            <i class="bi bi-graph-up me-1"></i>ความคืบหน้า
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link <?= $activeTab === 'documents' ? 'active' : '' ?>" id="tab-documents-btn"
                data-bs-toggle="tab" data-bs-target="#tab-documents" type="button">
            <i class="bi bi-folder me-1"></i>เอกสาร
            <span class="badge bg-secondary ms-1"><?= count($project['documents']) ?></span>
        </button>
    </li>
</ul>

<div class="tab-content border border-top-0 rounded-bottom shadow-sm bg-white p-4 mb-5">

    <!-- ================================================================
         Tab 1: ข้อมูลโครงการ
    ================================================================ -->
    <div class="tab-pane fade <?= $activeTab === 'info' ? 'show active' : '' ?>" id="tab-info">
        <div class="row g-4">
            <div class="col-lg-7">

                <!-- ชื่อโครงการ -->
                <h6 class="fw-bold text-psu-blue border-bottom pb-2 mb-3">ชื่อโครงการ</h6>
                <p><span class="badge bg-secondary me-1">TH</span>
                   <strong><?= htmlspecialchars($project['title_th'] ?? '') ?></strong></p>
                <?php if (!empty($project['proposal_title_en'])): ?>
                <p><span class="badge bg-secondary me-1">EN</span>
                   <em class="text-muted"><?= htmlspecialchars($project['proposal_title_en']) ?></em></p>
                <?php endif; ?>

                <!-- ผู้วิจัย -->
                <h6 class="fw-bold text-psu-blue border-bottom pb-2 mt-4 mb-3">ผู้วิจัย</h6>
                <div class="mb-2">
                    <span class="badge bg-psu-blue me-2">หัวหน้าโครงการ</span>
                    <strong><?= htmlspecialchars($project['pi_name'] ?? '-') ?></strong>
                    <?php if (!empty($project['pi_department'])): ?>
                    <small class="text-muted ms-1">(<?= htmlspecialchars($project['pi_department']) ?>)</small>
                    <?php endif; ?>
                </div>
                <?php if (!empty($project['co_investigators_list'])): ?>
                <div>
                    <span class="badge bg-secondary me-2">ผู้ร่วมวิจัย</span>
                    <ul class="list-unstyled mt-2 ps-3">
                        <?php foreach ($project['co_investigators_list'] as $co): ?>
                        <li><i class="bi bi-person me-1 text-muted"></i>
                            <?= htmlspecialchars(is_array($co) ? ($co['name'] ?? '') : $co) ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- หมายเหตุ (editable) -->
                <h6 class="fw-bold text-psu-blue border-bottom pb-2 mt-4 mb-3">หมายเหตุโครงการ</h6>
                <form method="POST" action="<?= BASE_URL ?>/projects/<?= $project['id'] ?>/update">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="update_notes">
                    <textarea name="notes" class="form-control mb-2" rows="4"
                              placeholder="บันทึกหมายเหตุหรือข้อสังเกต..."
                              <?= !$isAdmin ? 'readonly' : '' ?>><?= htmlspecialchars($project['notes'] ?? '') ?></textarea>
                    <?php if ($isAdmin): ?>
                    <button type="submit" class="btn btn-sm btn-psu-primary">
                        <i class="bi bi-floppy me-1"></i>บันทึกหมายเหตุ
                    </button>
                    <?php endif; ?>
                </form>

            </div>
            <div class="col-lg-5">

                <!-- Contract & budget info -->
                <div class="card border-0 bg-light mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold text-psu-blue mb-3">รายละเอียดโครงการ</h6>
                        <dl class="row mb-0 small">
                            <dt class="col-5 text-muted">รหัสโครงการ</dt>
                            <dd class="col-7 fw-semibold"><code><?= htmlspecialchars($project['project_code'] ?? '-') ?></code></dd>

                            <dt class="col-5 text-muted">รหัสข้อเสนอ</dt>
                            <dd class="col-7"><code><?= htmlspecialchars($project['proposal_code'] ?? '-') ?></code></dd>

                            <dt class="col-5 text-muted">เลขที่สัญญา</dt>
                            <dd class="col-7"><?= htmlspecialchars($project['contract_number'] ?? '-') ?></dd>

                            <dt class="col-5 text-muted">วันที่ทำสัญญา</dt>
                            <dd class="col-7"><?= fmtDateP($project['contract_date'] ?? null) ?></dd>

                            <dt class="col-5 text-muted">งบอนุมัติ</dt>
                            <dd class="col-7 fw-bold text-success"><?= fmtBudgetP((float)($project['approved_budget'] ?? 0)) ?></dd>

                            <dt class="col-5 text-muted">ปีงบประมาณ</dt>
                            <dd class="col-7"><?= htmlspecialchars($project['budget_year'] ?? '-') ?></dd>

                            <dt class="col-5 text-muted">แหล่งทุน</dt>
                            <dd class="col-7"><?= htmlspecialchars($project['funding_source_name'] ?? '-') ?></dd>

                            <dt class="col-5 text-muted">สาขาวิชา</dt>
                            <dd class="col-7"><?= htmlspecialchars($project['field_name_th'] ?? '-') ?></dd>

                            <dt class="col-5 text-muted">คณะ</dt>
                            <dd class="col-7"><?= htmlspecialchars($project['faculty_name'] ?? '-') ?></dd>

                            <dt class="col-5 text-muted">วันที่เริ่ม</dt>
                            <dd class="col-7"><?= fmtDateP($project['start_date'] ?? null) ?></dd>

                            <dt class="col-5 text-muted">วันที่สิ้นสุด</dt>
                            <dd class="col-7"><?= fmtDateP($project['end_date'] ?? null) ?></dd>

                            <dt class="col-5 text-muted">อนุมัติโดย</dt>
                            <dd class="col-7"><?= htmlspecialchars($project['approved_by_name'] ?? '-') ?></dd>

                            <dt class="col-5 text-muted">วันที่อนุมัติ</dt>
                            <dd class="col-7"><?= fmtDateP($project['approved_at'] ?? null) ?></dd>
                        </dl>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ================================================================
         Tab 2: ผู้ทรงคุณวุฒิ
    ================================================================ -->
    <div class="tab-pane fade <?= $activeTab === 'reviewers' ? 'show active' : '' ?>" id="tab-reviewers">
        <h5 class="fw-bold text-psu-blue mb-4">ผู้ทรงคุณวุฒิที่มอบหมาย</h5>

        <?php if (empty($project['reviewers'])): ?>
        <p class="text-center text-muted py-5">
            <i class="bi bi-people fs-1 d-block mb-2"></i>ยังไม่มีผู้ทรงคุณวุฒิที่มอบหมาย
        </p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-psu-header">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>ชื่อผู้ทรงคุณวุฒิ</th>
                        <th>หน่วยงาน</th>
                        <th class="text-center">สถานะ</th>
                        <th class="text-center">กำหนดส่ง</th>
                        <th class="text-center">ผลการพิจารณา</th>
                        <th class="text-center">คะแนน</th>
                        <th class="text-center">สถานะค่าตอบแทน</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($project['reviewers'] as $i => $rev):
                    $rrKey = $rev['review_result'] ?? 'pending';
                    [$rColor, $rLabel] = $reviewerStatusMap[$rrKey] ?? ['secondary','ไม่ทราบ'];
                    [$pColor, $pLabel] = $paymentStatusMap[$rev['payment_status'] ?? 'pending'] ?? ['secondary','ไม่ทราบ'];
                ?>
                <tr>
                    <td class="ps-3"><?= $i + 1 ?></td>
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($rev['reviewer_full_name'] ?? '-') ?></div>
                        <?php if (!empty($rev['reviewer_email'])): ?>
                        <small class="text-muted"><?= htmlspecialchars($rev['reviewer_email']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><small class="text-muted"><?= htmlspecialchars($rev['institution'] ?? '-') ?></small></td>
                    <td class="text-center"><span class="badge bg-<?= $rColor ?>"><?= $rLabel ?></span></td>
                    <td class="text-center"><?= fmtDateP($rev['due_date'] ?? null) ?></td>
                    <td class="text-center">
                        <?php
                        $rr = $rev['review_result'] ?? '';
                        if (!empty($rr) && $rr !== 'pending'):
                            $rrBadge = [
                                'approved'                => ['success', 'ผ่าน'],
                                'approved_with_condition' => ['warning', 'ผ่านมีเงื่อนไข'],
                                'rejected'                => ['danger',  'ไม่ผ่าน'],
                            ];
                            [$rrC, $rrL] = $rrBadge[$rr] ?? ['secondary', $rr];
                        ?>
                        <span class="badge bg-<?= $rrC ?>"><?= $rrL ?></span>
                        <?php else: ?><span class="text-muted">-</span><?php endif; ?>
                        <?php if (!empty($rev['review_comments'])): ?>
                        <br><small class="text-muted fst-italic"><?= htmlspecialchars(mb_substr($rev['review_comments'], 0, 50)) ?>...</small>
                        <?php endif; ?>
                    </td>
                    <td class="text-center fw-semibold">
                        <?= !empty($rev['review_score']) ? htmlspecialchars($rev['review_score']) : '-' ?>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-<?= $pColor ?>"><?= $pLabel ?></span>
                        <?php if (!empty($rev['payment_amount'])): ?>
                        <br><small class="text-muted">฿<?= number_format($rev['payment_amount'], 2) ?></small>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- ================================================================
         Tab 3: ความคืบหน้า
    ================================================================ -->
    <div class="tab-pane fade <?= $activeTab === 'progress' ? 'show active' : '' ?>" id="tab-progress">
        <div class="row g-4">
            <div class="col-lg-6">

                <!-- Progress editor -->
                <h5 class="fw-bold text-psu-blue mb-3">อัปเดตความคืบหน้า</h5>
                <?php if ($isAdmin): ?>
                <form method="POST" action="<?= BASE_URL ?>/projects/<?= $project['id'] ?>/update" id="progressForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="update_progress">

                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <label for="progressSlider" class="form-label fw-semibold mb-0">
                                ระดับความคืบหน้า
                            </label>
                            <span class="badge bg-<?= $barColor ?> fs-6" id="progressValueDisplay"><?= $pct ?>%</span>
                        </div>
                        <input type="range" class="form-range" id="progressSlider"
                               name="progress_percentage"
                               min="0" max="100" step="5"
                               value="<?= $pct ?>">
                        <div class="d-flex justify-content-between small text-muted">
                            <span>0%</span><span>25%</span><span>50%</span><span>75%</span><span>100%</span>
                        </div>
                    </div>

                    <div class="progress mb-4" style="height:24px; border-radius:12px;">
                        <div class="progress-bar bg-<?= $barColor ?> progress-bar-striped progress-bar-animated"
                             id="progressPreviewBar"
                             role="progressbar"
                             style="width:<?= $pct ?>%; transition:width .3s ease;"
                             aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-psu-primary">
                        <i class="bi bi-floppy me-1"></i>บันทึกความคืบหน้า
                    </button>
                </form>
                <?php else: ?>
                <div class="alert alert-info">สามารถดูความคืบหน้าได้เท่านั้น</div>
                <?php endif; ?>

            </div>
            <div class="col-lg-6">

                <!-- Status timeline -->
                <h5 class="fw-bold text-psu-blue mb-3">ประวัติการเปลี่ยนแปลงสถานะ</h5>
                <?php
                $statusTimelineLabels = [
                    'approved' => ['success', 'bi-check-circle-fill', 'อนุมัติ'],
                    'in_progress' => ['warning', 'bi-play-circle-fill', 'เริ่มดำเนินการ'],
                    'completed' => ['success', 'bi-trophy-fill', 'เสร็จสิ้น'],
                    'closed' => ['secondary', 'bi-archive-fill', 'ปิดโครงการ'],
                    'cancelled' => ['danger', 'bi-x-circle-fill', 'ยกเลิก'],
                ];
                ?>
                <?php if (empty($project['status_log'])): ?>
                <p class="text-muted">ยังไม่มีประวัติการเปลี่ยนแปลงสถานะ</p>
                <?php else: ?>
                <div class="timeline">
                    <?php foreach ($project['status_log'] as $log):
                        [$tlColor, $tlIcon, $tlLabel] = $statusTimelineLabels[$log['to_status'] ?? ''] ?? ['secondary','bi-circle','ไม่ทราบ'];
                    ?>
                    <div class="timeline-item d-flex gap-3 mb-3">
                        <div class="timeline-icon flex-shrink-0">
                            <div class="rounded-circle bg-<?= $tlColor ?> bg-opacity-10 p-2">
                                <i class="bi <?= $tlIcon ?> text-<?= $tlColor ?>"></i>
                            </div>
                        </div>
                        <div>
                            <div class="fw-semibold"><?= $tlLabel ?></div>
                            <small class="text-muted">
                                โดย <?= htmlspecialchars($log['changed_by_name'] ?? 'ระบบ') ?>
                                เมื่อ <?= fmtDateP($log['changed_at'] ?? null, true) ?>
                            </small>
                            <?php if (!empty($log['notes'])): ?>
                            <div class="small text-muted fst-italic mt-1"><?= htmlspecialchars($log['notes']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- ================================================================
         Tab 4: เอกสาร
    ================================================================ -->
    <div class="tab-pane fade <?= $activeTab === 'documents' ? 'show active' : '' ?>" id="tab-documents">

        <!-- Link to original proposal attachment -->
        <?php if (!empty($project['proposal_attachment'])): ?>
        <div class="alert alert-info d-flex align-items-center gap-3 mb-4">
            <i class="bi bi-file-earmark-pdf fs-3 text-danger"></i>
            <div>
                <strong>เอกสารข้อเสนอโครงการ (ต้นฉบับ)</strong><br>
                <a href="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($project['proposal_attachment']) ?>"
                   class="btn btn-sm btn-outline-danger mt-1" target="_blank">
                    <i class="bi bi-download me-1"></i>ดาวน์โหลดเอกสาร PDF
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Upload new document -->
        <div class="card border-0 bg-light mb-4">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-cloud-upload me-2"></i>อัปโหลดเอกสารเพิ่มเติม
            </div>
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>/projects/<?= $project['id'] ?>/update"
                      enctype="multipart/form-data" id="docUploadForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="upload_document">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label for="document" class="form-label fw-semibold small">เลือกไฟล์</label>
                            <input type="file" name="document" id="document" class="form-control"
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.png">
                            <div class="form-text">PDF, Word, Excel, รูปภาพ ขนาดไม่เกิน 20 MB</div>
                        </div>
                        <div class="col-md-5">
                            <label for="doc_description" class="form-label fw-semibold small">คำอธิบายเอกสาร</label>
                            <input type="text" name="doc_description" id="doc_description"
                                   class="form-control" placeholder="เช่น รายงานความก้าวหน้า ครั้งที่ 1">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-psu-primary w-100">
                                <i class="bi bi-upload me-1"></i>อัปโหลด
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Document list -->
        <h6 class="fw-bold text-psu-blue mb-3">รายการเอกสาร (<?= count($project['documents']) ?> ไฟล์)</h6>
        <?php if (empty($project['documents'])): ?>
        <p class="text-center text-muted py-4">
            <i class="bi bi-folder-x fs-1 d-block mb-2"></i>ยังไม่มีเอกสารที่อัปโหลด
        </p>
        <?php else: ?>
        <div class="list-group">
            <?php foreach ($project['documents'] as $doc):
                $ext = strtolower(pathinfo($doc['original_name'] ?? '', PATHINFO_EXTENSION));
                $iconMap = ['pdf'=>'bi-file-pdf text-danger','docx'=>'bi-file-word text-primary','doc'=>'bi-file-word text-primary',
                            'xlsx'=>'bi-file-excel text-success','xls'=>'bi-file-excel text-success',
                            'jpg'=>'bi-file-image text-info','png'=>'bi-file-image text-info'];
                $icon = $iconMap[$ext] ?? 'bi-file-earmark text-muted';
            ?>
            <div class="list-group-item d-flex align-items-center gap-3">
                <i class="bi <?= $icon ?> fs-3 flex-shrink-0"></i>
                <div class="flex-grow-1">
                    <div class="fw-semibold"><?= htmlspecialchars($doc['original_name'] ?? $doc['filename']) ?></div>
                    <div class="small text-muted">
                        <?= htmlspecialchars($doc['uploaded_by_name'] ?? '-') ?> |
                        <?= fmtDateP($doc['uploaded_at'] ?? null, true) ?> |
                        <?= fmtFileSizeP((int)($doc['file_size'] ?? 0)) ?>
                        <?php if (!empty($doc['description'])): ?>
                        | <?= htmlspecialchars($doc['description']) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($doc['file_path']) ?>"
                   class="btn btn-sm btn-outline-secondary" target="_blank">
                    <i class="bi bi-download"></i>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /tab-content -->

<!-- ================================================================
     Status Change Modal
================================================================ -->
<?php if ($isAdmin && !empty($validTransitions)): ?>
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="<?= BASE_URL ?>/projects/<?= $project['id'] ?>/update" id="statusForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="status" id="selectedStatus">
            <div class="modal-content">
                <div class="modal-header bg-psu-blue text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-arrow-repeat me-2"></i>เปลี่ยนสถานะโครงการ
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        สถานะปัจจุบัน: <span class="badge bg-<?= $statusColor ?>"><?= $statusLabel ?></span>
                    </p>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">เลือกสถานะถัดไป</label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($validTransitions as [$tStatus, $tLabel, $tColor]): ?>
                            <button type="button" class="btn btn-outline-<?= $tColor ?> btn-status-opt"
                                    data-val="<?= $tStatus ?>">
                                <?= $tLabel ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div>
                        <label class="form-label fw-semibold">หมายเหตุ</label>
                        <textarea name="notes_field" class="form-control" rows="3"
                                  placeholder="ระบุเหตุผลหรือข้อสังเกต..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-psu-primary" id="confirmStatusBtn" disabled>
                        <i class="bi bi-check-lg me-1"></i>ยืนยัน
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<style>
:root { --psu-blue:#003B6D; --psu-accent:#0066CC; }
.text-psu-blue  { color: var(--psu-blue); }
.bg-psu-blue    { background-color: var(--psu-blue); }
.btn-psu-primary{ background-color:var(--psu-accent); border-color:var(--psu-accent); color:#fff; }
.btn-psu-primary:hover{ background-color:var(--psu-blue); border-color:var(--psu-blue); color:#fff; }
.nav-tabs-psu .nav-link { color: #495057; border-color: transparent; }
.nav-tabs-psu .nav-link.active { color: var(--psu-blue); border-color: #dee2e6 #dee2e6 #fff; font-weight:600; }
.nav-tabs-psu .nav-link:hover:not(.active) { background: rgba(0,59,109,.05); }
.table-psu-header th { background-color:var(--psu-blue); color:#fff; font-weight:600; }
.timeline-item + .timeline-item { border-top: 1px dashed #dee2e6; padding-top: .5rem; }
</style>

<script>
$(document).ready(function () {

    // Progress slider live update
    $('#progressSlider').on('input', function () {
        var val = parseInt($(this).val());
        $('#progressValueDisplay').text(val + '%');
        $('#progressPreviewBar').css('width', val + '%').attr('aria-valuenow', val);

        var color = val >= 100 ? 'success' : val >= 60 ? 'primary' : val >= 30 ? 'warning' : 'danger';
        $('#progressPreviewBar').removeClass('bg-success bg-primary bg-warning bg-danger').addClass('bg-' + color);
        $('#progressValueDisplay').removeClass('bg-success bg-primary bg-warning bg-danger').addClass('bg-' + color);
    });

    // Progress form confirm
    $('#progressForm').on('submit', function (e) {
        e.preventDefault();
        var pct = $('#progressSlider').val();
        Swal.fire({
            title: 'ยืนยันการอัปเดตความคืบหน้า',
            text: 'อัปเดตความคืบหน้าเป็น ' + pct + '% ใช่หรือไม่?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#003B6D',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก',
        }).then(function (r) {
            if (r.isConfirmed) document.getElementById('progressForm').submit();
        });
    });

    // Status option selection
    $(document).on('click', '.btn-status-opt', function () {
        $('.btn-status-opt').removeClass('active');
        $(this).addClass('active');
        $('#selectedStatus').val($(this).data('val'));
        $('#confirmStatusBtn').prop('disabled', false);
    });

    // Status change confirm
    $('#statusForm').on('submit', function (e) {
        e.preventDefault();
        var newStatus = $('#selectedStatus').val();
        if (!newStatus) return;

        var labels = {
            in_progress: 'กำลังดำเนินการ', completed: 'เสร็จสิ้น',
            closed: 'ปิดโครงการ', cancelled: 'ยกเลิก'
        };

        bootstrap.Modal.getInstance(document.getElementById('statusModal')).hide();

        Swal.fire({
            title: 'ยืนยันการเปลี่ยนสถานะ',
            html: 'ต้องการเปลี่ยนสถานะเป็น <strong>"' + (labels[newStatus] || newStatus) + '"</strong> ใช่หรือไม่?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#003B6D',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก',
        }).then(function (r) {
            if (r.isConfirmed) document.getElementById('statusForm').submit();
        });
    });

    // Restore active tab from URL hash
    var hash = window.location.hash;
    if (hash) {
        var tabId = hash.replace('#', '') + '-btn';
        var btn = document.getElementById(tabId);
        if (btn) new bootstrap.Tab(btn).show();
    }

    // Update hash on tab switch
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (btn) {
        btn.addEventListener('shown.bs.tab', function (e) {
            var target = e.target.getAttribute('data-bs-target');
            if (target) history.replaceState(null, null, target.replace('tab-', '#tab-'));
        });
    });
});
</script>
