<?php
/**
 * View: proposals/show.php
 * Proposal detail view - Thai UI
 */

if (!function_exists('fmtBudget')) {
    function fmtBudget(?float $a): string {
        if ($a === null || $a == 0) return '-';
        return '฿' . number_format($a, 2);
    }
}

if (!function_exists('fmtDate')) {
    function fmtDate(?string $d, bool $long = false): string {
        if (!$d) return '-';
        $ts = strtotime($d);
        if (!$ts) return '-';
        $months      = [1=>'มกราคม',2=>'กุมภาพันธ์',3=>'มีนาคม',4=>'เมษายน',5=>'พฤษภาคม',6=>'มิถุนายน',
                        7=>'กรกฎาคม',8=>'สิงหาคม',9=>'กันยายน',10=>'ตุลาคม',11=>'พฤศจิกายน',12=>'ธันวาคม'];
        $shortMonths = [1=>'ม.ค.',2=>'ก.พ.',3=>'มี.ค.',4=>'เม.ย.',5=>'พ.ค.',6=>'มิ.ย.',
                        7=>'ก.ค.',8=>'ส.ค.',9=>'ก.ย.',10=>'ต.ค.',11=>'พ.ย.',12=>'ธ.ค.'];
        $day  = date('j', $ts);
        $mon  = $long ? $months[(int)date('n', $ts)] : $shortMonths[(int)date('n', $ts)];
        $year = (int)date('Y', $ts) + 543;
        return "{$day} {$mon} {$year}";
    }
}

$statusMap = [
    'draft'     => ['secondary', 'ฉบับร่าง'],
    'reviewing' => ['warning',   'อยู่ระหว่างพิจารณา'],
    'approved'  => ['success',   'อนุมัติ'],
    'rejected'  => ['danger',    'ปฏิเสธ'],
];
[$statusColor, $statusLabel] = $statusMap[$proposal['status'] ?? 'draft'] ?? ['secondary', 'ไม่ทราบสถานะ'];

$isAdmin = in_array($currentUser['role'] ?? '', ['admin', 'superadmin']);

// Valid status transitions
$transitions = [
    'draft'     => [['reviewing','อยู่ระหว่างพิจารณา','warning'], ['rejected','ปฏิเสธ','danger']],
    'reviewing' => [['approved','อนุมัติ','success'], ['rejected','ปฏิเสธ','danger'], ['draft','ส่งคืนฉบับร่าง','secondary']],
    'approved'  => [],
    'rejected'  => [['draft','ส่งคืนฉบับร่าง','secondary']],
];
$validTransitions = $transitions[$proposal['status']] ?? [];

$reviewerStatusMap = [
    'pending'                 => ['secondary', 'รอดำเนินการ'],
    'approved'                => ['success',   'ผ่านการพิจารณา'],
    'approved_with_condition' => ['warning',   'ผ่านมีเงื่อนไข'],
    'rejected'                => ['danger',    'ไม่ผ่านการพิจารณา'],
];
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/proposals">ข้อเสนอโครงการวิจัย</a></li>
        <li class="breadcrumb-item active">ดูรายละเอียด</li>
    </ol>
</nav>

<!-- Page header -->
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-<?= $statusColor ?> fs-6"><?= $statusLabel ?></span>
            <code style="color:#003B6D;" class="fs-6"><?= h($proposal['proposal_code'] ?? '') ?></code>
        </div>
        <h1 class="h4 fw-bold mb-0" style="color:#003B6D;"><?= h($proposal['title_th']) ?></h1>
        <?php if (!empty($proposal['title_en'])): ?>
        <p class="text-muted mt-1 mb-0 fst-italic"><?= h($proposal['title_en']) ?></p>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if ($isAdmin && !in_array($proposal['status'], ['approved'])): ?>
        <a href="<?= BASE_URL ?>/proposals/<?= (int)$proposal['id'] ?>/edit" class="btn btn-outline-secondary">
            <i class="fas fa-edit me-1"></i>แก้ไข
        </a>
        <?php endif; ?>
        <?php if ($isAdmin && !empty($validTransitions)): ?>
        <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#statusModal">
            <i class="fas fa-exchange-alt me-1"></i>เปลี่ยนสถานะ
        </button>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/proposals" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>กลับ
        </a>
    </div>
</div>

<!-- Linked project banner -->
<?php if (!empty($proposal['linked_project'])): ?>
<div class="alert alert-success d-flex align-items-center gap-3 mb-4">
    <i class="fas fa-check-circle fa-lg"></i>
    <div>
        <strong>โครงการวิจัยถูกสร้างแล้ว</strong>
        รหัสโครงการ: <code><?= h($proposal['linked_project']['project_code']) ?></code>
        สถานะ: <span class="badge bg-primary"><?= h($proposal['linked_project']['project_status']) ?></span>
        <a href="<?= BASE_URL ?>/projects/<?= (int)$proposal['linked_project']['id'] ?>" class="btn btn-sm btn-success ms-2">
            <i class="fas fa-external-link-alt me-1"></i>ดูโครงการ
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Info cards row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-3">
                <div class="text-muted small mb-1">รหัสข้อเสนอ</div>
                <div class="fw-bold text-psu-blue"><?= htmlspecialchars($proposal['proposal_code'] ?? '-') ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-3">
                <div class="text-muted small mb-1">งบประมาณที่ขอ</div>
                <div class="fw-bold text-success"><?= fmtBudget((float)($proposal['budget_requested'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-3">
                <div class="text-muted small mb-1">ปีงบประมาณ</div>
                <div class="fw-bold"><?= htmlspecialchars($proposal['budget_year'] ?? '-') ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-3">
                <div class="text-muted small mb-1">วันที่ยื่น</div>
                <div class="fw-bold"><?= fmtDate($proposal['submitted_at'] ?? null) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">

        <!-- ชื่อโครงการ -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light border-0">
                <h6 class="mb-0 fw-bold text-psu-blue"><i class="fas fa-file-alt me-2"></i>ชื่อโครงการ</h6>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <span class="badge bg-secondary me-2">TH</span>
                    <span class="fw-semibold"><?= htmlspecialchars($proposal['title_th']) ?></span>
                </div>
                <?php if (!empty($proposal['title_en'])): ?>
                <div>
                    <span class="badge bg-secondary me-2">EN</span>
                    <span class="fst-italic text-muted"><?= htmlspecialchars($proposal['title_en']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- บทคัดย่อ -->
        <?php if (!empty($proposal['abstract_th'])): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light border-0">
                <h6 class="mb-0 fw-bold text-psu-blue"><i class="fas fa-paragraph me-2"></i>บทคัดย่อ</h6>
            </div>
            <div class="card-body">
                <p class="mb-0" style="white-space:pre-wrap; line-height:1.8;"><?= htmlspecialchars($proposal['abstract_th']) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- วัตถุประสงค์ -->
        <?php if (!empty($proposal['objectives'])): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light border-0">
                <h6 class="mb-0 fw-bold text-psu-blue"><i class="fas fa-list-ul me-2"></i>วัตถุประสงค์การวิจัย</h6>
            </div>
            <div class="card-body">
                <p class="mb-0" style="white-space:pre-wrap; line-height:1.8;"><?= htmlspecialchars($proposal['objectives']) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- ระเบียบวิธีวิจัย -->
        <?php if (!empty($proposal['methodology'])): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light border-0">
                <h6 class="mb-0 fw-bold text-psu-blue"><i class="fas fa-project-diagram me-2"></i>ระเบียบวิธีวิจัย</h6>
            </div>
            <div class="card-body">
                <p class="mb-0" style="white-space:pre-wrap; line-height:1.8;"><?= htmlspecialchars($proposal['methodology']) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- ผู้ทรงคุณวุฒิ -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light border-0 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-psu-blue">
                    <i class="fas fa-users me-2"></i>ผู้ทรงคุณวุฒิ
                    <span class="badge bg-<?= count($reviewers) >= 3 ? 'success' : 'warning' ?> ms-2">
                        <?= count($reviewers) ?>/3
                    </span>
                </h6>
                <?php if ($isAdmin): ?>
                <a href="<?= BASE_URL ?>/proposals/<?= (int)$proposal['id'] ?>/assign-reviewers" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-user-plus me-1"></i>มอบหมายผู้ทรงคุณวุฒิ
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if (empty($reviewers)): ?>
                <p class="text-center text-muted py-4 mb-0">ยังไม่มีผู้ทรงคุณวุฒิที่มอบหมาย</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">#</th>
                                <th>ชื่อผู้ทรงคุณวุฒิ</th>
                                <th>หน่วยงาน</th>
                                <th class="text-center">สถานะ</th>
                                <th class="text-center">กำหนดส่ง</th>
                                <th class="text-center">ผล</th>
                                <th class="text-center">คะแนน</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($reviewers as $i => $rev): ?>
                            <?php
                            $rrKey = $rev['review_result'] ?? 'pending';
                            [$rColor, $rLabel] = $reviewerStatusMap[$rrKey] ?? ['secondary','ไม่ทราบ'];
                            ?>
                            <tr>
                                <td class="ps-3"><?= $i + 1 ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($rev['reviewer_full_name'] ?? '-') ?></td>
                                <td><small class="text-muted"><?= htmlspecialchars($rev['institution'] ?? '-') ?></small></td>
                                <td class="text-center"><span class="badge bg-<?= $rColor ?>"><?= $rLabel ?></span></td>
                                <td class="text-center small"><?= fmtDate($rev['due_date'] ?? null) ?></td>
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
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center fw-semibold">
                                    <?= !empty($rev['review_score']) ? htmlspecialchars($rev['review_score']) : '-' ?>
                                </td>
                            </tr>
                            <?php if (!empty($rev['review_comments'])): ?>
                            <tr class="table-light">
                                <td colspan="7" class="ps-5 py-2">
                                    <small class="text-muted"><i class="fas fa-comment me-1"></i><?= htmlspecialchars($rev['review_comments']) ?></small>
                                </td>
                            </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">

        <!-- ผู้วิจัย -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light border-0">
                <h6 class="mb-0 fw-bold text-psu-blue"><i class="fas fa-id-card me-2"></i>ผู้วิจัย</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <span class="badge bg-psu-blue mb-1">หัวหน้าโครงการ</span>
                    <div class="fw-bold"><?= htmlspecialchars($proposal['pi_name'] ?? '-') ?></div>
                    <?php if (!empty($proposal['pi_department'])): ?>
                    <small class="text-muted"><?= htmlspecialchars($proposal['pi_department']) ?></small>
                    <?php endif; ?>
                    <?php if (!empty($proposal['pi_email'])): ?>
                    <div><a href="mailto:<?= htmlspecialchars($proposal['pi_email']) ?>" class="small text-psu-accent">
                        <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($proposal['pi_email']) ?>
                    </a></div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($proposal['co_investigators_list'])): ?>
                <hr class="my-2">
                <span class="badge bg-secondary mb-2">ผู้ร่วมวิจัย</span>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($proposal['co_investigators_list'] as $co): ?>
                    <li class="d-flex align-items-center gap-2 py-1">
                        <i class="fas fa-user text-muted"></i>
                        <span><?= htmlspecialchars(is_array($co) ? ($co['name'] ?? '') : $co) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- ข้อมูลแหล่งทุน/สาขา -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light border-0">
                <h6 class="mb-0 fw-bold text-psu-blue"><i class="fas fa-info-circle me-2"></i>รายละเอียด</h6>
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">แหล่งทุน</dt>
                    <dd class="col-7 fw-semibold"><?= htmlspecialchars($proposal['funding_source_name'] ?? '-') ?></dd>

                    <dt class="col-5 text-muted">ประเภททุน</dt>
                    <dd class="col-7">
                        <?php if (!empty($proposal['funding_source_type'])): ?>
                        <span class="badge bg-info text-dark"><?= htmlspecialchars($proposal['funding_source_type']) ?></span>
                        <?php else: ?>-<?php endif; ?>
                    </dd>

                    <dt class="col-5 text-muted">สาขาวิชา</dt>
                    <dd class="col-7"><?= htmlspecialchars($proposal['field_name_th'] ?? '-') ?></dd>

                    <dt class="col-5 text-muted">คณะ</dt>
                    <dd class="col-7"><?= htmlspecialchars($proposal['faculty_name'] ?? '-') ?></dd>

                    <dt class="col-5 text-muted">วันที่เริ่ม</dt>
                    <dd class="col-7"><?= fmtDate($proposal['start_date'] ?? null) ?></dd>

                    <dt class="col-5 text-muted">วันที่สิ้นสุด</dt>
                    <dd class="col-7"><?= fmtDate($proposal['end_date'] ?? null) ?></dd>

                    <dt class="col-5 text-muted">สร้างเมื่อ</dt>
                    <dd class="col-7"><?= fmtDate($proposal['created_at'] ?? null) ?></dd>
                </dl>
            </div>
        </div>

        <!-- เอกสารแนบ -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light border-0">
                <h6 class="mb-0 fw-bold text-psu-blue"><i class="fas fa-paperclip me-2"></i>เอกสารแนบ</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($proposal['attachment_path'])): ?>
                <a href="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($proposal['attachment_path']) ?>"
                   class="btn btn-outline-danger w-100" target="_blank">
                    <i class="fas fa-file-pdf me-2"></i>ดาวน์โหลดเอกสาร PDF
                </a>
                <?php else: ?>
                <p class="text-muted text-center mb-0"><i class="fas fa-folder-open d-block fs-3 mb-1"></i>ไม่มีเอกสารแนบ</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- หมายเหตุสถานะ -->
        <?php if (!empty($proposal['status_notes'])): ?>
        <div class="card border-0 shadow-sm border-start border-4 border-<?= $statusColor ?> mb-4">
            <div class="card-body">
                <h6 class="fw-bold text-<?= $statusColor ?>"><i class="fas fa-comment-alt me-2"></i>หมายเหตุ</h6>
                <p class="mb-0 small"><?= htmlspecialchars($proposal['status_notes']) ?></p>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- =====================================================
     Status Change Modal
===================================================== -->
<?php if ($isAdmin && !empty($validTransitions)): ?>
<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="<?= BASE_URL ?>/proposals/<?= (int)$proposal['id'] ?>/status" id="statusChangeForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <input type="hidden" name="status" id="modalSelectedStatus">
            <div class="modal-content">
                <div class="modal-header text-white" style="background:#003B6D;">
                    <h5 class="modal-title" id="statusModalLabel">
                        <i class="fas fa-exchange-alt me-2"></i>เปลี่ยนสถานะข้อเสนอ
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        สถานะปัจจุบัน: <span class="badge bg-<?= $statusColor ?>"><?= $statusLabel ?></span>
                    </p>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">เลือกสถานะใหม่</label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($validTransitions as [$tStatus, $tLabel, $tColor]): ?>
                            <button type="button" class="btn btn-outline-<?= $tColor ?> btn-status-opt"
                                    data-val="<?= $tStatus ?>">
                                <?= $tLabel ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label for="statusNotes" class="form-label fw-semibold">หมายเหตุ</label>
                        <textarea name="notes" id="statusNotes" class="form-control" rows="3"
                                  placeholder="ระบุเหตุผลหรือข้อสังเกต (ถ้ามี)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn text-white" id="confirmStatusChange" style="background:#003B6D;" disabled>
                        <i class="fas fa-check me-1"></i>ยืนยัน
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
.text-psu-accent{ color: var(--psu-accent); }
.bg-psu-blue    { background-color: var(--psu-blue); }
.btn-psu-primary{ background-color:var(--psu-accent); border-color:var(--psu-accent); color:#fff; }
.btn-psu-primary:hover{ background-color:var(--psu-blue); border-color:var(--psu-blue); color:#fff; }
.btn-outline-psu{ border-color:var(--psu-accent); color:var(--psu-accent); }
.btn-outline-psu:hover{ background-color:var(--psu-accent); color:#fff; }
</style>

<script>
$(document).ready(function () {

    // Status option selection
    $(document).on('click', '.btn-status-opt', function () {
        $('.btn-status-opt').removeClass('active');
        $(this).addClass('active');
        $('#modalSelectedStatus').val($(this).data('val'));
        $('#confirmStatusChange').prop('disabled', false);
    });

    // Confirm status change with SweetAlert
    $('#statusChangeForm').on('submit', function (e) {
        e.preventDefault();
        var newStatus = $('#modalSelectedStatus').val();
        if (!newStatus) return;

        var labels = {
            reviewing: 'อยู่ระหว่างพิจารณา',
            approved:  'อนุมัติ',
            rejected:  'ปฏิเสธ',
            draft:     'ฉบับร่าง'
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
        }).then(function (result) {
            if (result.isConfirmed) {
                document.getElementById('statusChangeForm').submit();
            }
        });
    });
});
</script>
