<?php
/**
 * View: มอบหมายผู้ทรงคุณวุฒิ (Assign Reviewers to Proposal)
 * Variables injected by Controller::render():
 * @var array  $proposal
 * @var array  $currentReviews
 * @var int    $assignedCount
 * @var int    $slotsRemaining
 * @var array  $allReviewers
 * @var array  $assignedIds
 * @var string $csrfToken
 */

$reviewResultMap = [
    'approved'                => 'ผ่านการพิจารณา',
    'approved_with_condition' => 'ผ่านมีเงื่อนไข',
    'rejected'                => 'ไม่ผ่านการพิจารณา',
];
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/proposals">โครงการวิจัย</a></li>
        <li class="breadcrumb-item">
            <a href="<?= BASE_URL ?>/proposals/<?= $proposal['id'] ?>"><?= htmlspecialchars($proposal['proposal_code']) ?></a>
        </li>
        <li class="breadcrumb-item active">ผู้ทรงคุณวุฒิ</li>
    </ol>
</nav>

<!-- Page Header -->
<div class="p-4 rounded mb-4 text-white" style="background: linear-gradient(135deg,#003B6D,#0066CC);">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-users me-2"></i>มอบหมายผู้ทรงคุณวุฒิพิจารณาโครงการ
            </h4>
            <p class="mb-1 fw-semibold opacity-90"><?= htmlspecialchars($proposal['title_th']) ?></p>
            <small class="opacity-75">
                <i class="fas fa-hashtag me-1"></i><?= htmlspecialchars($proposal['proposal_code']) ?>
                &nbsp;|&nbsp;
                <i class="fas fa-calendar me-1"></i>ปีงบประมาณ <?= htmlspecialchars($proposal['budget_year'] ?? '') ?>
            </small>
        </div>
        <div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-pill fw-semibold"
             style="<?= $slotsRemaining > 0 ? 'background:#d1e7dd; color:#0a3622; border:2px solid #198754;' : 'background:#f8d7da; color:#58151c; border:2px solid #dc3545;' ?>">
            <i class="fas fa-<?= $slotsRemaining > 0 ? 'user-plus' : 'user-check' ?>"></i>
            มอบหมายแล้ว <?= $assignedCount ?>/3 คน
        </div>
    </div>
</div>

<!-- Current Reviewers -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header text-white py-3" style="background:#003B6D; border-radius:.75rem .75rem 0 0;">
        <h6 class="mb-0 fw-bold">
            <i class="fas fa-list me-2"></i>ผู้ทรงคุณวุฒิที่ได้รับมอบหมาย
        </h6>
    </div>
    <div class="card-body">
        <?php if (empty($currentReviews)): ?>
            <div class="text-center text-muted py-4">
                <i class="fas fa-user-slash fa-3x mb-3 opacity-25"></i>
                <p>ยังไม่มีการมอบหมายผู้ทรงคุณวุฒิ</p>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr style="background:#f8f9fa; font-size:.85rem;">
                        <th>#</th>
                        <th>ชื่อ-นามสกุล</th>
                        <th>สังกัด</th>
                        <th>วันมอบหมาย</th>
                        <th>กำหนดส่ง</th>
                        <th>ผลการพิจารณา</th>
                        <th>คะแนน</th>
                        <th>ค่าตอบแทน</th>
                        <th class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($currentReviews as $idx => $review): ?>
                    <tr>
                        <td class="fw-bold text-muted"><?= $idx + 1 ?></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($review['reviewer_full_name'] ?? '') ?></div>
                            <small class="text-muted"><?= htmlspecialchars($review['expertise'] ?? '') ?></small>
                        </td>
                        <td class="small"><?= htmlspecialchars($review['institution'] ?? '') ?></td>
                        <td class="small">
                            <?= !empty($review['assigned_date']) ? date('d/m/Y', strtotime($review['assigned_date'])) : '-' ?>
                        </td>
                        <td class="small">
                            <?php
                            if (!empty($review['due_date'])) {
                                $due  = strtotime($review['due_date']);
                                $diff = (int)ceil(($due - time()) / 86400);
                                $cls  = $diff < 0 ? 'text-danger' : ($diff <= 3 ? 'text-warning' : 'text-muted');
                                echo '<span class="' . $cls . '">' . date('d/m/Y', $due) . '</span>';
                                if ($diff < 0)     echo ' <small class="text-danger">(เกินกำหนด)</small>';
                                elseif ($diff <= 7) echo ' <small class="text-warning">(' . $diff . ' วัน)</small>';
                            } else { echo '-'; }
                            ?>
                        </td>
                        <td>
                            <?php
                            $rv = $review['review_result'] ?? '';
                            if (!empty($rv) && $rv !== 'pending'):
                                $rvColors = [
                                    'approved'                => 'bg-success',
                                    'approved_with_condition' => 'bg-warning text-dark',
                                    'rejected'                => 'bg-danger',
                                ];
                            ?>
                                <span class="badge <?= $rvColors[$rv] ?? 'bg-secondary' ?> px-2 py-1">
                                    <?= htmlspecialchars($reviewResultMap[$rv] ?? $rv) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary">รอผล</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= !empty($review['score']) ? '<strong>' . $review['score'] . '</strong>/100' : '-' ?>
                        </td>
                        <td>
                            <?php $pStat = $review['payment_status'] ?? 'pending'; ?>
                            <?= $pStat === 'paid'
                                ? '<span class="badge bg-success">จ่ายแล้ว</span>'
                                : '<span class="badge bg-warning text-dark">รอดำเนินการ</span>' ?>
                            <?php if (!empty($review['payment_amount'])): ?>
                                <br><small class="text-muted"><?= number_format((float)$review['payment_amount'], 2) ?> บาท</small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="<?= BASE_URL ?>/reviews/<?= $review['id'] ?>/invitation"
                                   class="btn btn-outline-primary" title="หนังสือเชิญ">
                                    <i class="fas fa-envelope"></i>
                                </a>
                                <button class="btn btn-outline-success btn-save-result"
                                        data-review='<?= htmlspecialchars(json_encode($review, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>'
                                        title="บันทึกผล">
                                    <i class="fas fa-clipboard-check"></i>
                                </button>
                                <button class="btn btn-outline-warning btn-payment"
                                        data-review='<?= htmlspecialchars(json_encode($review, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>'
                                        title="ค่าตอบแทน">
                                    <i class="fas fa-money-bill"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Reviewer Form -->
<?php if ($slotsRemaining > 0): ?>
<div class="card border-0 shadow-sm">
    <div class="card-header text-white py-3" style="background:#003B6D; border-radius:.75rem .75rem 0 0;">
        <h6 class="mb-0 fw-bold">
            <i class="fas fa-user-plus me-2"></i>เพิ่มผู้ทรงคุณวุฒิ
            <span class="badge bg-warning text-dark ms-2">เหลือ <?= $slotsRemaining ?> ช่อง</span>
        </h6>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>/proposals/<?= $proposal['id'] ?>/assign-reviewers">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="mb-3">
                <label class="form-label fw-semibold">
                    เลือกผู้ทรงคุณวุฒิ <span class="text-danger">*</span>
                </label>
                <select name="reviewer_id" id="reviewerSelect" class="form-select" required>
                    <option value="">-- ค้นหาและเลือกผู้ทรงคุณวุฒิ --</option>
                    <?php foreach ($allReviewers as $rv): ?>
                        <?php if (!in_array($rv['id'], $assignedIds)): ?>
                        <option value="<?= $rv['id'] ?>"
                                data-institution="<?= htmlspecialchars($rv['institution'] ?? '') ?>"
                                data-expertise="<?= htmlspecialchars($rv['expertise'] ?? '') ?>">
                            <?= htmlspecialchars($rv['title'] . $rv['first_name'] . ' ' . $rv['last_name']) ?>
                            — <?= htmlspecialchars($rv['institution'] ?? '') ?>
                            [<?= htmlspecialchars($rv['expertise'] ?? '') ?>]
                        </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <div id="reviewerInfo" class="mt-2 p-2 bg-light rounded small d-none">
                    <i class="fas fa-info-circle text-primary me-1"></i>
                    <span id="reviewerInfoText"></span>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">วันที่มอบหมาย</label>
                    <input type="date" name="assigned_date" class="form-control"
                           value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">กำหนดส่งผล</label>
                    <input type="date" name="due_date" class="form-control"
                           value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn text-white" style="background:#003B6D;">
                    <i class="fas fa-paper-plane me-1"></i>มอบหมาย
                </button>
                <a href="<?= BASE_URL ?>/proposals/<?= $proposal['id'] ?>" class="btn btn-outline-secondary">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>
<?php else: ?>
<div class="alert alert-warning d-flex align-items-center gap-3">
    <i class="fas fa-exclamation-triangle fa-2x flex-shrink-0"></i>
    <div>
        <strong>ครบจำนวนผู้ทรงคุณวุฒิแล้ว</strong>
        <p class="mb-0 small">โครงการนี้ได้รับการมอบหมายผู้ทรงคุณวุฒิครบ 3 ท่านแล้ว ไม่สามารถเพิ่มได้อีก</p>
    </div>
</div>
<?php endif; ?>

<!-- Review Result Modal -->
<div class="modal fade" id="resultModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header text-white" style="background:#003B6D;">
                <h5 class="modal-title">
                    <i class="fas fa-clipboard-check me-2"></i>บันทึกผลการพิจารณา
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="resultForm" method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="modal-body">
                    <div id="resultReviewerInfo" class="alert alert-info mb-3 small"></div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            ผลการพิจารณา <span class="text-danger">*</span>
                        </label>
                        <div class="d-flex gap-3 flex-wrap">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="review_result"
                                       id="result_approve" value="approved" required>
                                <label class="form-check-label text-success fw-semibold" for="result_approve">
                                    <i class="fas fa-check-circle me-1"></i>ผ่านการพิจารณา
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="review_result"
                                       id="result_revision" value="approved_with_condition">
                                <label class="form-check-label text-warning fw-semibold" for="result_revision">
                                    <i class="fas fa-edit me-1"></i>ผ่านมีเงื่อนไข
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="review_result"
                                       id="result_reject" value="rejected">
                                <label class="form-check-label text-danger fw-semibold" for="result_reject">
                                    <i class="fas fa-times-circle me-1"></i>ไม่ผ่านการพิจารณา
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">คะแนน (0-100)</label>
                            <input type="number" name="review_score" id="resultScore" class="form-control"
                                   min="0" max="100" placeholder="0-100">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">วันที่รับผลการพิจารณา</label>
                            <input type="date" name="received_date" id="resultReceivedDate" class="form-control">
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label fw-semibold">ความเห็น/ข้อเสนอแนะ</label>
                        <textarea name="review_comments" id="resultComments" class="form-control"
                                  rows="4" placeholder="ความเห็นและข้อเสนอแนะของผู้ทรงคุณวุฒิ"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn text-white" style="background:#003B6D;">
                        <i class="fas fa-save me-1"></i>บันทึกผล
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header text-white" style="background:#003B6D;">
                <h5 class="modal-title">
                    <i class="fas fa-money-bill-wave me-2"></i>บันทึกค่าตอบแทน
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="paymentForm" method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="modal-body">
                    <div id="paymentReviewerInfo" class="alert alert-info mb-3 small"></div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">จำนวนเงิน (บาท)</label>
                        <div class="input-group">
                            <span class="input-group-text">฿</span>
                            <input type="number" name="payment_amount" id="paymentAmount"
                                   class="form-control" min="0" step="0.01" placeholder="0.00">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">วันที่จ่ายเงิน</label>
                        <input type="date" name="payment_date" id="paymentDate" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">เลขอ้างอิงการโอน</label>
                        <input type="text" name="payment_reference" id="paymentReference"
                               class="form-control" placeholder="เลขที่เอกสาร/เลขอ้างอิง">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">สถานะ</label>
                        <select name="payment_status" id="paymentStatus" class="form-select">
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

    // Reviewer select info display
    $('#reviewerSelect').on('change', function () {
        const opt = $(this).find('option:selected');
        if ($(this).val()) {
            $('#reviewerInfoText').text(
                'สังกัด: ' + opt.data('institution') + ' | ความเชี่ยวชาญ: ' + opt.data('expertise')
            );
            $('#reviewerInfo').removeClass('d-none');
        } else {
            $('#reviewerInfo').addClass('d-none');
        }
    });

    // Save result modal
    $(document).on('click', '.btn-save-result', function () {
        const r = JSON.parse($(this).attr('data-review'));
        $('#resultReviewerInfo').html(
            '<strong>' + (r.reviewer_full_name || '') + '</strong> | ' + (r.institution || '')
        );
        $('#resultForm').attr('action', '<?= BASE_URL ?>/reviews/' + r.id + '/result');
        $('input[name="review_result"]').prop('checked', false);
        $('input[name="review_result"][value="' + (r.review_result || '') + '"]').prop('checked', true);
        $('#resultScore').val(r.score || '');
        $('#resultComments').val(r.review_comments || '');
        $('#resultReceivedDate').val(r.received_date || '');
        new bootstrap.Modal(document.getElementById('resultModal')).show();
    });

    // Payment modal
    $(document).on('click', '.btn-payment', function () {
        const r = JSON.parse($(this).attr('data-review'));
        $('#paymentReviewerInfo').html(
            '<strong>' + (r.reviewer_full_name || '') + '</strong><br>' +
            'ธนาคาร: ' + (r.bank_name || '-') + ' | บัญชี: ' + (r.bank_account || '-')
        );
        $('#paymentForm').attr('action', '<?= BASE_URL ?>/reviews/' + r.id + '/payment');
        $('#paymentAmount').val(r.payment_amount || '');
        $('#paymentDate').val(r.payment_date || '');
        $('#paymentReference').val(r.payment_reference || '');
        $('#paymentStatus').val(r.payment_status || 'pending');
        new bootstrap.Modal(document.getElementById('paymentModal')).show();
    });
});
</script>
