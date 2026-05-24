<?php
/**
 * View: รายการค่าตอบแทนผู้ทรงคุณวุฒิ (Reviewer Payments)
 * Variables injected by Controller::render():
 * @var array  $payments
 * @var array  $summary   keys: pending_count, paid_count, pending_amount, paid_amount
 * @var array  $filters
 * @var string $csrfToken
 */

$reviewResultMap = [
    'approved'                => 'ผ่าน',
    'approved_with_condition' => 'ผ่านมีเงื่อนไข',
    'rejected'                => 'ไม่ผ่าน',
];

$pendingCount = (int)($summary['pending_count']  ?? 0);
$paidCount    = (int)($summary['paid_count']     ?? 0);
$pendingTotal = (float)($summary['pending_amount'] ?? 0);
$paidTotal    = (float)($summary['paid_amount']    ?? 0);
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="mb-1 fw-bold" style="color:#003B6D;">
            <i class="fas fa-money-bill-wave me-2"></i>รายการค่าตอบแทนผู้ทรงคุณวุฒิ
        </h4>
        <p class="text-muted mb-0 small">จัดการการจ่ายค่าตอบแทนผู้ทรงคุณวุฒิพิจารณาโครงการวิจัย</p>
    </div>
    <a href="<?= BASE_URL ?>/payments/export?<?= http_build_query($filters) ?>"
       class="btn btn-success fw-semibold">
        <i class="fas fa-file-excel me-1"></i>Export Excel
    </a>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 text-white" style="background: linear-gradient(135deg,#f59e0b,#d97706); border-radius:.75rem;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div style="font-size:2rem; font-weight:700; line-height:1;"><?= $pendingCount ?></div>
                        <div class="small opacity-75 mt-1">รายการรอดำเนินการ</div>
                        <div class="fw-semibold mt-2">฿<?= number_format($pendingTotal, 2) ?></div>
                    </div>
                    <i class="fas fa-clock fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 text-white" style="background: linear-gradient(135deg,#10b981,#059669); border-radius:.75rem;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div style="font-size:2rem; font-weight:700; line-height:1;"><?= $paidCount ?></div>
                        <div class="small opacity-75 mt-1">รายการจ่ายแล้ว</div>
                        <div class="fw-semibold mt-2">฿<?= number_format($paidTotal, 2) ?></div>
                    </div>
                    <i class="fas fa-check-circle fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="<?= BASE_URL ?>/payments" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold small mb-1">ค้นหา (รหัส/ชื่อโครงการ หรือ ผู้ทรงคุณวุฒิ)</label>
                <input type="text" name="search" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                       placeholder="พิมพ์เพื่อค้นหา...">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small mb-1">สถานะการจ่ายเงิน</label>
                <select name="payment_status" class="form-select form-select-sm">
                    <option value="">-- ทั้งหมด --</option>
                    <option value="pending" <?= ($filters['payment_status'] ?? '') === 'pending' ? 'selected' : '' ?>>รอดำเนินการ</option>
                    <option value="paid"    <?= ($filters['payment_status'] ?? '') === 'paid'    ? 'selected' : '' ?>>จ่ายแล้ว</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small mb-1">จากวันที่</label>
                <input type="date" name="date_from" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small mb-1">ถึงวันที่</label>
                <input type="date" name="date_to" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm text-white" style="background:#003B6D;">
                    <i class="fas fa-search me-1"></i>ค้นหา
                </button>
                <a href="<?= BASE_URL ?>/payments" class="btn btn-outline-secondary btn-sm">ล้าง</a>
            </div>
        </form>
    </div>
</div>

<!-- Payments Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="paymentsTable" class="table table-hover align-middle mb-0" style="width:100%">
                <thead>
                    <tr style="background:#003B6D; color:#fff;">
                        <th>#</th>
                        <th>โครงการ</th>
                        <th>ผู้ทรงคุณวุฒิ</th>
                        <th>ผลพิจารณา</th>
                        <th>จำนวนเงิน</th>
                        <th>วันที่จ่าย</th>
                        <th>เลขอ้างอิง</th>
                        <th>สถานะ</th>
                        <th class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $i => $p): ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td>
                            <div class="fw-semibold small"><?= htmlspecialchars($p['proposal_code'] ?? '') ?></div>
                            <div class="text-muted" style="font-size:.8rem; max-width:220px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                <?= htmlspecialchars($p['proposal_title'] ?? '') ?>
                            </div>
                            <?php if (!empty($p['budget_year'])): ?>
                            <span class="badge bg-light text-dark border small"><?= htmlspecialchars($p['budget_year']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-semibold small"><?= htmlspecialchars($p['reviewer_full_name'] ?? '') ?></div>
                            <div class="text-muted" style="font-size:.8rem;"><?= htmlspecialchars($p['institution'] ?? '') ?></div>
                        </td>
                        <td>
                            <?php if (!empty($p['review_result']) && $p['review_result'] !== 'pending'): ?>
                                <?php
                                $rClass = match($p['review_result']) {
                                    'approved'                => 'text-success',
                                    'approved_with_condition' => 'text-warning',
                                    'rejected'                => 'text-danger',
                                    default                   => 'text-muted',
                                };
                                ?>
                                <span class="small fw-semibold <?= $rClass ?>">
                                    <?= htmlspecialchars($reviewResultMap[$p['review_result']] ?? $p['review_result']) ?>
                                </span>
                                <?php if (!empty($p['review_score'])): ?>
                                    <br><small class="text-muted"><?= $p['review_score'] ?>/100 คะแนน</small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted small">รอผล</span>
                            <?php endif; ?>
                        </td>
                        <td class="fw-semibold">
                            <?= !empty($p['payment_amount']) ? '฿' . number_format((float)$p['payment_amount'], 2) : '<span class="text-muted">-</span>' ?>
                        </td>
                        <td class="small">
                            <?= !empty($p['payment_date']) ? date('d/m/Y', strtotime($p['payment_date'])) : '<span class="text-muted">-</span>' ?>
                        </td>
                        <td class="small text-muted">
                            <?= htmlspecialchars($p['payment_reference'] ?? '-') ?>
                        </td>
                        <td>
                            <?php if (($p['payment_status'] ?? '') === 'paid'): ?>
                                <span class="badge px-2 py-1" style="background:#d1fae5; color:#065f46; border:1px solid #a7f3d0;">
                                    <i class="fas fa-check-circle me-1"></i>จ่ายแล้ว
                                </span>
                            <?php else: ?>
                                <span class="badge px-2 py-1" style="background:#fef3c7; color:#92400e; border:1px solid #fde68a;">
                                    <i class="fas fa-clock me-1"></i>รอดำเนินการ
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary btn-edit-payment"
                                    data-payment='<?= htmlspecialchars(json_encode($p, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>'
                                    title="แก้ไขการจ่ายเงิน">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">
                            <i class="fas fa-receipt fa-2x mb-2 d-block opacity-25"></i>
                            ไม่พบรายการค่าตอบแทน
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Payment Edit Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header text-white" style="background:#003B6D;">
                <h5 class="modal-title">
                    <i class="fas fa-money-bill me-2"></i>แก้ไขค่าตอบแทน
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="paymentForm" method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="modal-body">
                    <div id="modalPaymentInfo" class="alert alert-info small mb-3"></div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">จำนวนเงิน (บาท)</label>
                        <div class="input-group">
                            <span class="input-group-text">฿</span>
                            <input type="number" name="payment_amount" id="modalAmount"
                                   class="form-control" min="0" step="0.01" placeholder="0.00">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">วันที่จ่ายเงิน</label>
                        <input type="date" name="payment_date" id="modalDate" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">เลขอ้างอิงการโอน</label>
                        <input type="text" name="payment_reference" id="modalReference"
                               class="form-control" placeholder="เลขที่เอกสาร/เลขอ้างอิง">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">สถานะ</label>
                        <select name="payment_status" id="modalStatus" class="form-select">
                            <option value="pending">รอดำเนินการ</option>
                            <option value="paid">จ่ายแล้ว</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn text-white" style="background:#003B6D;">
                        <i class="fas fa-save me-1"></i>บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    // Init DataTable (layout already loaded the plugin)
    $('#paymentsTable').DataTable({
        pageLength: 25,
        order: [[5, 'desc']],
        columnDefs: [{ orderable: false, targets: [8] }]
    });

    // Open payment edit modal
    $(document).on('click', '.btn-edit-payment', function () {
        const p = JSON.parse($(this).attr('data-payment'));

        $('#modalPaymentInfo').html(
            '<strong>' + (p.reviewer_full_name || '') + '</strong> &mdash; ' +
            (p.proposal_code || '') + '<br><span class="text-muted">' +
            (p.proposal_title || '') + '</span>'
        );
        $('#paymentForm').attr('action', '<?= BASE_URL ?>/reviews/' + p.id + '/payment');
        $('#modalAmount').val(p.payment_amount || '');
        $('#modalDate').val(p.payment_date || '');
        $('#modalReference').val(p.payment_reference || '');
        $('#modalStatus').val(p.payment_status || 'pending');

        new bootstrap.Modal(document.getElementById('paymentModal')).show();
    });
});
</script>
