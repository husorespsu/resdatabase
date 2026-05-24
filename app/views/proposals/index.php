<?php
/**
 * View: proposals/index.php
 * Variables: $proposals, $statusCounts, $fundingSources, $fieldsOfStudy, $years, $filters, $currentUser, $flash
 */
$isAdmin    = in_array($currentUser['role'] ?? '', ['admin', 'superadmin']);
$totalCount = count($proposals ?? []);
?>

<?php if (!empty($flash)): ?>
<div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : h($flash['type']) ?> alert-dismissible fade show">
    <?= $flash['message'] ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Page header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:#003B6D;">
            <i class="fas fa-file-alt me-2"></i>ข้อเสนอโครงการวิจัย
        </h4>
        <p class="text-muted mb-0 small">ทั้งหมด <strong><?= $totalCount ?></strong> รายการ</p>
    </div>
    <?php if ($isAdmin): ?>
    <a href="/research/proposals/create" class="btn text-white" style="background:#003B6D;">
        <i class="fas fa-plus-circle me-1"></i>เพิ่มข้อเสนอใหม่
    </a>
    <?php endif; ?>
</div>

<!-- Status summary cards -->
<div class="row g-3 mb-4">
    <?php
    $summaryCards = [
        ['status' => 'draft',     'label' => 'ฉบับร่าง',             'color' => 'secondary', 'icon' => 'fa-edit'],
        ['status' => 'reviewing', 'label' => 'อยู่ระหว่างพิจารณา',    'color' => 'warning',   'icon' => 'fa-hourglass-half'],
        ['status' => 'approved',  'label' => 'อนุมัติ',               'color' => 'success',   'icon' => 'fa-check-circle'],
        ['status' => 'rejected',  'label' => 'ปฏิเสธ',                'color' => 'danger',    'icon' => 'fa-times-circle'],
    ];
    foreach ($summaryCards as $card):
        $count = $statusCounts[$card['status']] ?? 0;
    ?>
    <div class="col-6 col-md-3">
        <a href="/research/proposals?status=<?= $card['status'] ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 <?= ($filters['status'] ?? '') === $card['status'] ? 'border-2 border-' . $card['color'] : '' ?>">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-<?= $card['color'] ?> bg-opacity-10 p-3 flex-shrink-0"
                         style="width:52px;height:52px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas <?= $card['icon'] ?> text-<?= $card['color'] ?>"></i>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold text-<?= $card['color'] ?>"><?= $count ?></div>
                        <div class="small text-muted"><?= $card['label'] ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="/research/proposals">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">สถานะ</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- ทุกสถานะ --</option>
                        <option value="draft"     <?= ($filters['status'] ?? '') === 'draft'     ? 'selected' : '' ?>>ฉบับร่าง</option>
                        <option value="reviewing" <?= ($filters['status'] ?? '') === 'reviewing' ? 'selected' : '' ?>>อยู่ระหว่างพิจารณา</option>
                        <option value="approved"  <?= ($filters['status'] ?? '') === 'approved'  ? 'selected' : '' ?>>อนุมัติ</option>
                        <option value="rejected"  <?= ($filters['status'] ?? '') === 'rejected'  ? 'selected' : '' ?>>ปฏิเสธ</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">แหล่งทุน</label>
                    <select name="funding_source_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- ทุกแหล่งทุน --</option>
                        <?php foreach ($fundingSources as $fs): ?>
                        <option value="<?= (int)$fs['id'] ?>" <?= ($filters['funding_source_id'] ?? '') == $fs['id'] ? 'selected' : '' ?>>
                            <?= h($fs['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">สาขาวิชา</label>
                    <select name="field_of_study_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- ทุกสาขา --</option>
                        <?php foreach ($fieldsOfStudy as $fos): ?>
                        <option value="<?= (int)$fos['id'] ?>" <?= ($filters['field_of_study_id'] ?? '') == $fos['id'] ? 'selected' : '' ?>>
                            <?= h($fos['name_th']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">ปีงบประมาณ</label>
                    <select name="budget_year" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- ทุกปี --</option>
                        <?php foreach ($years as $yr): ?>
                        <option value="<?= h($yr) ?>" <?= ($filters['budget_year'] ?? '') == $yr ? 'selected' : '' ?>>
                            <?= h($yr) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">ค้นหา</label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control" placeholder="ชื่อโครงการ / รหัส"
                               value="<?= h($filters['search'] ?? '') ?>">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </div>
            <?php if (array_filter($filters)): ?>
            <div class="mt-2">
                <a href="/research/proposals" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times me-1"></i>ล้างตัวกรอง
                </a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Export + Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
        <span class="small text-muted">แสดง <?= $totalCount ?> รายการ</span>
        <div class="d-flex gap-2">
            <a href="/research/proposals?<?= http_build_query(array_merge($_GET, ['export' => 'excel'])) ?>"
               class="btn btn-sm btn-success">
                <i class="fas fa-file-excel me-1"></i>Excel
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="proposalsTable" class="table table-hover align-middle mb-0" style="width:100%">
                <thead>
                    <tr style="background:#003B6D;color:#fff;">
                        <th class="ps-3">รหัส</th>
                        <th>ชื่อโครงการ</th>
                        <th>หัวหน้าโครงการ</th>
                        <th>แหล่งทุน</th>
                        <th class="text-end">งบประมาณ</th>
                        <th class="text-center">ปีงบฯ</th>
                        <th class="text-center">สถานะ</th>
                        <th class="text-center">วันที่ยื่น</th>
                        <?php if ($isAdmin): ?><th class="text-center">จัดการ</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($proposals)): ?>
                    <tr>
                        <td colspan="<?= $isAdmin ? 9 : 8 ?>" class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-2x d-block mb-2 opacity-50"></i>
                            ไม่พบข้อมูลข้อเสนอโครงการ
                            <?php if ($isAdmin): ?>
                            <div class="mt-3">
                                <a href="/research/proposals/create" class="btn btn-sm text-white" style="background:#003B6D;">
                                    <i class="fas fa-plus-circle me-1"></i>เพิ่มข้อเสนอแรก
                                </a>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($proposals as $p): ?>
                    <tr>
                        <td class="ps-3">
                            <a href="/research/proposals/<?= (int)$p['id'] ?>"
                               class="fw-semibold text-decoration-none" style="color:#003B6D;">
                                <?= h($p['proposal_code'] ?? '-') ?>
                            </a>
                        </td>
                        <td>
                            <div class="fw-semibold" style="max-width:260px;">
                                <?= h($p['title_th']) ?>
                            </div>
                            <?php if (!empty($p['title_en'])): ?>
                                <small class="text-muted d-block text-truncate" style="max-width:260px;"><?= h($p['title_en']) ?></small>
                            <?php endif; ?>
                            <?php if (!empty($p['field_name_th'])): ?>
                                <span class="badge bg-light text-dark border mt-1 small"><?= h($p['field_name_th']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="small"><?= h($p['pi_name'] ?? '-') ?></td>
                        <td class="small">
                            <?= h($p['funding_source_name'] ?? '-') ?>
                            <?php if (!empty($p['funding_source_type'])): ?>
                                <span class="badge bg-info bg-opacity-75 text-dark d-block mt-1" style="width:fit-content;">
                                    <?= $p['funding_source_type'] === 'internal' ? 'ภายใน' : 'ภายนอก' ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end small fw-semibold">
                            <?= formatBudget((float)($p['budget_requested'] ?? 0)) ?>
                        </td>
                        <td class="text-center small"><?= h($p['budget_year'] ?? '-') ?></td>
                        <td class="text-center"><?= statusBadge($p['status'] ?? 'draft') ?></td>
                        <td class="text-center small text-muted">
                            <?= formatThaiDate($p['submitted_at'] ?? $p['created_at'] ?? null) ?>
                        </td>
                        <?php if ($isAdmin): ?>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="/research/proposals/<?= (int)$p['id'] ?>"
                                   class="btn btn-outline-primary" title="ดูรายละเอียด">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (!in_array($p['status'] ?? '', ['approved'])): ?>
                                <a href="/research/proposals/<?= (int)$p['id'] ?>/edit"
                                   class="btn btn-outline-secondary" title="แก้ไข">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                                <button type="button"
                                        class="btn btn-outline-warning btn-change-status"
                                        title="เปลี่ยนสถานะ"
                                        data-id="<?= (int)$p['id'] ?>"
                                        data-status="<?= h($p['status'] ?? '') ?>"
                                        data-code="<?= h($p['proposal_code'] ?? '') ?>">
                                    <i class="fas fa-exchange-alt"></i>
                                </button>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Status Change Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="statusForm">
            <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token'] ?? '') ?>">
            <input type="hidden" name="status" id="modalNewStatus">
            <div class="modal-content">
                <div class="modal-header text-white" style="background:#003B6D;">
                    <h6 class="modal-title fw-semibold">
                        <i class="fas fa-exchange-alt me-2"></i>เปลี่ยนสถานะข้อเสนอ
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">รหัสโครงการ: <strong id="modalProposalCode"></strong></p>
                    <label class="form-label fw-semibold">เปลี่ยนสถานะเป็น</label>
                    <div id="statusOptions" class="d-flex flex-wrap gap-2 mb-3"></div>
                    <label class="form-label fw-semibold">หมายเหตุ (ถ้ามี)</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="ระบุเหตุผล..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn text-white" id="confirmStatusBtn" style="background:#003B6D;" disabled>
                        <i class="fas fa-check me-1"></i>ยืนยัน
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
#proposalsTable thead th { white-space: nowrap; font-weight: 600; }
#proposalsTable tbody tr:hover { background: rgba(0,59,109,.04); }
</style>

<script>
$(document).ready(function () {
    $('#proposalsTable').DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/th.json' },
        pageLength: 25,
        lengthMenu: [[10,25,50,100,-1],[10,25,50,100,'ทั้งหมด']],
        order: [[0,'desc']],
        columnDefs: [
            { orderable: false, targets: [-1] },
            { searchable: false, targets: [4,5,7] },
        ],
        dom: '<"d-flex justify-content-between align-items-center mb-2"lf>rtip',
    });

    var transitions = {
        'draft':     [['reviewing','อยู่ระหว่างพิจารณา','warning'],['rejected','ปฏิเสธ','danger']],
        'reviewing': [['approved','อนุมัติ','success'],['rejected','ปฏิเสธ','danger'],['draft','ส่งคืนฉบับร่าง','secondary']],
        'approved':  [],
        'rejected':  [['draft','ส่งคืนฉบับร่าง','secondary']],
    };

    $(document).on('click', '.btn-change-status', function () {
        var id     = $(this).data('id');
        var status = $(this).data('status');
        var code   = $(this).data('code');
        $('#statusForm').attr('action', '/research/proposals/' + id + '/status');
        $('#modalProposalCode').text(code);
        $('#modalNewStatus').val('');
        $('#confirmStatusBtn').prop('disabled', true);
        var opts = transitions[status] || [];
        var $c = $('#statusOptions').empty();
        if (!opts.length) {
            $c.html('<span class="text-muted small">ไม่มีสถานะที่สามารถเปลี่ยนได้</span>');
        } else {
            opts.forEach(function (o) {
                $c.append($('<button type="button">').addClass('btn btn-outline-' + o[2] + ' btn-select-status').data('val', o[0]).text(o[1]));
            });
        }
        new bootstrap.Modal(document.getElementById('statusModal')).show();
    });

    $(document).on('click', '.btn-select-status', function () {
        $('.btn-select-status').removeClass('active');
        $(this).addClass('active');
        $('#modalNewStatus').val($(this).data('val'));
        $('#confirmStatusBtn').prop('disabled', false);
    });

    $('#statusForm').on('submit', function (e) {
        e.preventDefault();
        var newStatus = $('#modalNewStatus').val();
        if (!newStatus) return;
        var labels = { reviewing:'อยู่ระหว่างพิจารณา', approved:'อนุมัติ', rejected:'ปฏิเสธ', draft:'ฉบับร่าง' };
        bootstrap.Modal.getInstance(document.getElementById('statusModal')).hide();
        Swal.fire({
            title: 'ยืนยันการเปลี่ยนสถานะ',
            text: 'เปลี่ยนเป็น "' + (labels[newStatus] || newStatus) + '" ใช่หรือไม่?',
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
});
</script>
