<?php
/**
 * View: projects/create.php — เพิ่มโครงการวิจัย
 * Variables: $availableProposals, $csrfToken, $flash
 */
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0" style="color:#003B6D;">
        <i class="fas fa-flask me-2"></i>เพิ่มโครงการวิจัย
    </h4>
    <a href="<?= BASE_URL ?>/projects" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>กลับ
    </a>
</div>

<form method="POST" action="<?= BASE_URL ?>/projects/store" id="projectForm" novalidate>
    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">

    <!-- ── Section 1: ข้อเสนอโครงการที่เชื่อมโยง ──────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header text-white" style="background:#003B6D;">
            <h6 class="mb-0 fw-semibold"><span class="badge bg-white text-dark me-2">1</span>ข้อเสนอโครงการที่ได้รับอนุมัติ</h6>
        </div>
        <div class="card-body">
            <?php if (empty($availableProposals)): ?>
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    ไม่มีข้อเสนอโครงการที่รออยู่ (ต้องมีสถานะ "อนุมัติ" และยังไม่มีโครงการ)
                    คุณยังสามารถสร้างโครงการแบบไม่ผูกกับข้อเสนอได้
                </div>
            <?php else: ?>
                <div class="mb-3">
                    <label class="form-label fw-semibold">เลือกข้อเสนอโครงการที่อนุมัติแล้ว</label>
                    <select name="proposal_id" id="proposal_id" class="form-select">
                        <option value="">-- ไม่ผูกกับข้อเสนอโครงการ --</option>
                        <?php foreach ($availableProposals as $p): ?>
                            <option value="<?= (int)$p['id'] ?>"
                                data-budget="<?= (float)($p['budget_requested'] ?? 0) ?>"
                                data-year="<?= (int)($p['budget_year'] ?? 0) ?>">
                                <?= h($p['proposal_code']) ?> — <?= h(mb_substr($p['title_th'], 0, 60)) ?>
                                (<?= h($p['pi_name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">เลือกข้อเสนอโครงการที่ได้รับการอนุมัติแล้ว เพื่อดึงข้อมูลงบประมาณมากรอกอัตโนมัติ</div>
                </div>

                <!-- Preview of selected proposal -->
                <div id="proposalPreview" class="d-none rounded p-3" style="background:#f0f4f8;border-left:4px solid #003B6D;">
                    <div class="small fw-semibold mb-1" id="previewTitle"></div>
                    <div class="small text-muted" id="previewPi"></div>
                    <div class="small text-muted" id="previewBudget"></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Section 2: ข้อมูลโครงการ ───────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header text-white" style="background:#003B6D;">
            <h6 class="mb-0 fw-semibold"><span class="badge bg-white text-dark me-2">2</span>ข้อมูลโครงการ</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">รหัสโครงการ <span class="text-danger">*</span></label>
                    <input type="text" name="project_code" id="project_code" class="form-control" required
                           placeholder="เช่น PRJ-2567-001"
                           value="<?= h($_POST['project_code'] ?? '') ?>">
                    <div class="form-text">จะถูกสร้างอัตโนมัติหากปล่อยว่าง</div>
                    <div class="invalid-feedback">กรุณากรอกรหัสโครงการ</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">วันที่อนุมัติ <span class="text-danger">*</span></label>
                    <input type="date" name="approved_date" class="form-control" required
                           value="<?= h($_POST['approved_date'] ?? date('Y-m-d')) ?>">
                    <div class="invalid-feedback">กรุณาระบุวันที่อนุมัติ</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">งบประมาณที่อนุมัติ (บาท) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">฿</span>
                        <input type="text" name="approved_budget" id="approved_budget" class="form-control text-end"
                               placeholder="0.00" required inputmode="decimal"
                               value="<?= h($_POST['approved_budget'] ?? '') ?>">
                        <span class="input-group-text">บาท</span>
                    </div>
                    <div class="form-text" id="budgetDisplay"></div>
                    <div class="invalid-feedback">กรุณากรอกงบประมาณที่อนุมัติ</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">วันที่เริ่มดำเนินการ</label>
                    <input type="date" name="actual_start_date" class="form-control"
                           value="<?= h($_POST['actual_start_date'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- ── Section 3: สัญญา ────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header text-white" style="background:#003B6D;">
            <h6 class="mb-0 fw-semibold"><span class="badge bg-white text-dark me-2">3</span>ข้อมูลสัญญา</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">เลขที่สัญญา</label>
                    <input type="text" name="contract_number" class="form-control"
                           placeholder="เช่น PSU-R-2567-001"
                           value="<?= h($_POST['contract_number'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">วันที่ทำสัญญา</label>
                    <input type="date" name="contract_date" class="form-control"
                           value="<?= h($_POST['contract_date'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- ── Section 4: หมายเหตุ ─────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header text-white" style="background:#003B6D;">
            <h6 class="mb-0 fw-semibold"><span class="badge bg-white text-dark me-2">4</span>หมายเหตุ</h6>
        </div>
        <div class="card-body">
            <textarea name="notes" class="form-control" rows="3"
                placeholder="บันทึกข้อมูลเพิ่มเติมหรือหมายเหตุ"><?= h($_POST['notes'] ?? '') ?></textarea>
        </div>
    </div>

    <!-- ── Buttons ──────────────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-5">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
            <a href="<?= BASE_URL ?>/projects" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>ยกเลิก
            </a>
            <button type="submit" class="btn btn-lg px-4 text-white" style="background:#003B6D;">
                <i class="fas fa-save me-1"></i>บันทึกโครงการ
            </button>
        </div>
    </div>
</form>

<script>
$(document).ready(function () {

    // ── Proposal selection → auto-fill budget ─────────────────────────
    var proposals = {};
    <?php foreach ($availableProposals as $p): ?>
    proposals[<?= (int)$p['id'] ?>] = {
        title:  <?= json_encode($p['title_th']) ?>,
        pi:     <?= json_encode($p['pi_name'] ?? '') ?>,
        budget: <?= (float)($p['budget_requested'] ?? 0) ?>,
        year:   <?= (int)($p['budget_year'] ?? 0) ?>
    };
    <?php endforeach; ?>

    $('#proposal_id').on('change', function () {
        var id = parseInt($(this).val());
        if (!id || !proposals[id]) {
            $('#proposalPreview').addClass('d-none');
            return;
        }
        var p = proposals[id];
        $('#previewTitle').text(p.title);
        $('#previewPi').text('หัวหน้าโครงการ: ' + p.pi);
        $('#previewBudget').text('งบประมาณที่ขอ: ฿' + p.budget.toLocaleString('th-TH', {minimumFractionDigits:2}) + ' | ปีงบประมาณ: ' + p.year);
        $('#proposalPreview').removeClass('d-none');

        // Auto-fill budget if empty
        if (!$('#approved_budget').val()) {
            $('#approved_budget').val(p.budget).trigger('input');
        }
    });

    // ── Budget display ────────────────────────────────────────────────
    $('#approved_budget').on('input', function () {
        var n = parseFloat($(this).val().replace(/[^0-9.]/g, ''));
        $('#budgetDisplay').text(isNaN(n) ? '' : 'จำนวน: ฿' + n.toLocaleString('th-TH', {minimumFractionDigits:2}));
    });

    // ── Form submit ───────────────────────────────────────────────────
    $('#projectForm').on('submit', function (e) {
        var form = this;
        // allow empty project_code (auto-generated)
        document.getElementById('project_code').removeAttribute('required');
        if (!form.checkValidity()) {
            e.preventDefault(); $(form).addClass('was-validated');
        }
    });
});
</script>
