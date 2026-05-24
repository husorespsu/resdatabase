<?php
/**
 * View: proposals/edit.php
 * Edit existing proposal form - Thai UI
 */

// Build co-investigators JSON for JS pre-population
$coListJson = json_encode($proposal['co_investigators_list'] ?? [], JSON_UNESCAPED_UNICODE);
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/research/proposals">ข้อเสนอโครงการวิจัย</a></li>
        <li class="breadcrumb-item">
            <a href="/research/proposals/<?= $proposal['id'] ?>">
                <?= htmlspecialchars($proposal['proposal_code'] ?? 'รายละเอียด') ?>
            </a>
        </li>
        <li class="breadcrumb-item active">แก้ไข</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 fw-bold text-psu-blue mb-0">
        <i class="bi bi-pencil-square me-2"></i>แก้ไขข้อเสนอโครงการ
    </h1>
    <span class="badge bg-secondary fs-6"><?= htmlspecialchars($proposal['proposal_code'] ?? '') ?></span>
</div>

<!-- Flash -->
<?php if (!empty($flash)): ?>
<div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : htmlspecialchars($flash['type']) ?> alert-dismissible fade show">
    <?= $flash['message'] ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST" action="/research/proposals/<?= $proposal['id'] ?>/update"
      enctype="multipart/form-data" id="editProposalForm" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <!-- ========================================================
         Section 1: ชื่อโครงการ
    ======================================================== -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-psu-blue text-white">
            <h5 class="mb-0"><i class="bi bi-1-circle me-2"></i>ชื่อโครงการ</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="title_th" class="form-label fw-semibold">
                    ชื่อโครงการ (ภาษาไทย) <span class="text-danger">*</span>
                </label>
                <textarea name="title_th" id="title_th" class="form-control" rows="3"
                          placeholder="ระบุชื่อโครงการวิจัยภาษาไทย" required><?= htmlspecialchars($proposal['title_th'] ?? '') ?></textarea>
                <div class="invalid-feedback">กรุณากรอกชื่อโครงการภาษาไทย</div>
            </div>
            <div class="mb-0">
                <label for="title_en" class="form-label fw-semibold">ชื่อโครงการ (ภาษาอังกฤษ)</label>
                <textarea name="title_en" id="title_en" class="form-control" rows="2"
                          placeholder="Research Project Title in English"><?= htmlspecialchars($proposal['title_en'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <!-- ========================================================
         Section 2: ผู้วิจัย
    ======================================================== -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-psu-blue text-white">
            <h5 class="mb-0"><i class="bi bi-2-circle me-2"></i>ผู้วิจัย</h5>
        </div>
        <div class="card-body">
            <div class="mb-4">
                <label class="form-label fw-semibold">
                    หัวหน้าโครงการ <span class="text-danger">*</span>
                    <small class="text-muted fw-normal ms-1">(เลือกจากบุคลากรคณะมนุษยศาสตร์ฯ หรือพิมพ์ชื่อเอง)</small>
                </label>
                <?php $currentPiName = $proposal['pi_name'] ?? ''; ?>
                <input type="hidden" name="pi_name" id="pi_name_input" value="<?= h($currentPiName) ?>">
                <select id="pi_select" class="form-select" style="width:100%;">
                    <?php if ($currentPiName): ?>
                        <option value="<?= h($currentPiName) ?>" selected><?= h($currentPiName) ?></option>
                    <?php endif; ?>
                </select>
                <div class="invalid-feedback d-block" id="pi_error" style="display:none!important;"></div>
                <div class="form-text">
                    <i class="fas fa-info-circle me-1 text-info"></i>
                    พิมพ์ชื่อเพื่อค้นหาจากบุคลากรสายวิชาการ หรือพิมพ์ชื่อที่ต้องการหากไม่พบในรายการ
                </div>
            </div>

            <div>
                <label class="form-label fw-semibold">ผู้ร่วมวิจัย</label>
                <div id="coInvestigatorContainer"></div>
                <button type="button" id="addCoInvestigator" class="btn btn-sm btn-outline-psu mt-2">
                    <i class="bi bi-person-plus me-1"></i>เพิ่มผู้ร่วมวิจัย
                </button>
                <div class="form-text">พิมพ์เพื่อค้นหาบุคลากรสายวิชาการ หรือกรอกชื่อเอง</div>
            </div>
        </div>
    </div>

    <!-- ========================================================
         Section 3: สาขาวิชา + แหล่งทุน
    ======================================================== -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-psu-blue text-white">
            <h5 class="mb-0"><i class="bi bi-3-circle me-2"></i>สาขาวิชาและแหล่งทุน</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="field_of_study_id" class="form-label fw-semibold">
                        สาขาวิชา <span class="text-danger">*</span>
                    </label>
                    <select name="field_of_study_id" id="field_of_study_id" class="form-select" required>
                        <option value="">-- เลือกสาขาวิชา --</option>
                        <?php
                        $currentFaculty = '';
                        foreach ($fieldsOfStudy as $fos):
                            if ($fos['faculty'] !== $currentFaculty):
                                if ($currentFaculty !== '') echo '</optgroup>';
                                echo '<optgroup label="' . htmlspecialchars($fos['faculty']) . '">';
                                $currentFaculty = $fos['faculty'];
                            endif;
                        ?>
                        <option value="<?= $fos['id'] ?>"
                                data-faculty="<?= htmlspecialchars($fos['faculty']) ?>"
                                <?= ($proposal['field_of_study_id'] ?? '') == $fos['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($fos['name_th']) ?>
                        </option>
                        <?php endforeach; if ($currentFaculty !== '') echo '</optgroup>'; ?>
                    </select>
                    <div class="invalid-feedback">กรุณาเลือกสาขาวิชา</div>
                    <div class="mt-2">
                        <label class="form-label small text-muted">คณะ/หน่วยงาน</label>
                        <input type="text" id="faculty_display" class="form-control form-control-sm bg-light"
                               readonly value="<?= htmlspecialchars($proposal['faculty_name'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="funding_source_id" class="form-label fw-semibold">
                        แหล่งทุน <span class="text-danger">*</span>
                    </label>
                    <select name="funding_source_id" id="funding_source_id" class="form-select" required>
                        <option value="">-- เลือกแหล่งทุน --</option>
                        <?php foreach ($fundingSources as $fs): ?>
                        <option value="<?= $fs['id'] ?>"
                                data-type="<?= htmlspecialchars($fs['type'] ?? '') ?>"
                                <?= ($proposal['funding_source_id'] ?? '') == $fs['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($fs['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">กรุณาเลือกแหล่งทุน</div>
                    <div class="mt-2" id="fundingTypeBadgeContainer"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================
         Section 4: งบประมาณ + ปีงบประมาณ
    ======================================================== -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-psu-blue text-white">
            <h5 class="mb-0"><i class="bi bi-4-circle me-2"></i>งบประมาณ</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="requested_budget" class="form-label fw-semibold">
                        งบประมาณที่ขอ <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text fw-bold">฿</span>
                        <input type="text" name="requested_budget" id="requested_budget"
                               class="form-control text-end"
                               value="<?= htmlspecialchars($proposal['requested_budget'] ?? '') ?>"
                               required inputmode="decimal">
                        <span class="input-group-text">บาท</span>
                    </div>
                    <div class="form-text" id="budgetDisplay"></div>
                    <div class="invalid-feedback">กรุณากรอกงบประมาณที่ขอ</div>
                </div>
                <div class="col-md-6">
                    <label for="budget_year" class="form-label fw-semibold">
                        ปีงบประมาณ <span class="text-danger">*</span>
                    </label>
                    <select name="budget_year" id="budget_year" class="form-select" required>
                        <option value="">-- เลือกปีงบประมาณ --</option>
                        <?php for ($yr = 2570; $yr >= 2564; $yr--): ?>
                        <option value="<?= $yr ?>"
                            <?= ($proposal['budget_year'] ?? '') == $yr ? 'selected' : '' ?>>
                            <?= $yr ?> (<?= $yr - 543 ?>)
                        </option>
                        <?php endfor; ?>
                    </select>
                    <div class="invalid-feedback">กรุณาเลือกปีงบประมาณ</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================
         Section 5: ระยะเวลา
    ======================================================== -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-psu-blue text-white">
            <h5 class="mb-0"><i class="bi bi-5-circle me-2"></i>ระยะเวลาดำเนินการ</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="start_date" class="form-label fw-semibold">วันที่เริ่มต้น</label>
                    <input type="date" name="start_date" id="start_date" class="form-control"
                           value="<?= htmlspecialchars($proposal['start_date'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label for="end_date" class="form-label fw-semibold">วันที่สิ้นสุด</label>
                    <input type="date" name="end_date" id="end_date" class="form-control"
                           value="<?= htmlspecialchars($proposal['end_date'] ?? '') ?>">
                    <div class="invalid-feedback" id="dateFeedback">วันที่สิ้นสุดต้องหลังจากวันที่เริ่มต้น</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================
         Section 6: รายละเอียด
    ======================================================== -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-psu-blue text-white">
            <h5 class="mb-0"><i class="bi bi-6-circle me-2"></i>รายละเอียดโครงการ</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="abstract_th" class="form-label fw-semibold">บทคัดย่อ</label>
                <textarea name="abstract_th" id="abstract_th" class="form-control" rows="5"><?= htmlspecialchars($proposal['abstract_th'] ?? '') ?></textarea>
                <div class="form-text text-end"><span id="abstractCount">0</span> ตัวอักษร</div>
            </div>
            <div class="mb-3">
                <label for="objectives" class="form-label fw-semibold">วัตถุประสงค์การวิจัย</label>
                <textarea name="objectives" id="objectives" class="form-control" rows="4"><?= htmlspecialchars($proposal['objectives'] ?? '') ?></textarea>
            </div>
            <div class="mb-0">
                <label for="methodology" class="form-label fw-semibold">ระเบียบวิธีวิจัย</label>
                <textarea name="methodology" id="methodology" class="form-control" rows="4"><?= htmlspecialchars($proposal['methodology'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <!-- ========================================================
         Section 7: เอกสารแนบ
    ======================================================== -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-psu-blue text-white">
            <h5 class="mb-0"><i class="bi bi-7-circle me-2"></i>เอกสารแนบ</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($proposal['attachment_path'])): ?>
            <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-file-pdf fs-4 text-danger"></i>
                <div class="flex-grow-1">
                    <strong>ไฟล์ปัจจุบัน:</strong>
                    <a href="/research/uploads/<?= htmlspecialchars($proposal['attachment_path']) ?>"
                       target="_blank" class="ms-1">ดาวน์โหลดไฟล์เดิม</a>
                </div>
            </div>
            <p class="text-muted small mb-2">อัปโหลดไฟล์ใหม่เพื่อแทนที่ไฟล์เดิม (ถ้าต้องการ)</p>
            <?php endif; ?>

            <div class="border-2 border-dashed rounded-3 p-4 text-center" id="dropZone">
                <i class="bi bi-file-earmark-arrow-up fs-1 text-psu-accent d-block mb-2"></i>
                <p class="mb-2 text-muted">ลากและวางไฟล์ที่นี่ หรือ</p>
                <input type="file" name="attachment" id="attachment"
                       class="form-control d-none" accept=".pdf">
                <button type="button" class="btn btn-outline-psu btn-sm"
                        onclick="document.getElementById('attachment').click()">
                    <i class="bi bi-folder2-open me-1"></i>เลือกไฟล์
                </button>
                <div class="form-text mt-2">รองรับเฉพาะไฟล์ PDF ขนาดไม่เกิน 10 MB</div>
            </div>
            <div id="fileInfo" class="mt-2 d-none">
                <div class="d-flex align-items-center gap-2 p-2 bg-light rounded">
                    <i class="bi bi-file-pdf fs-4 text-danger"></i>
                    <div class="flex-grow-1">
                        <div class="fw-semibold" id="fileName"></div>
                        <small class="text-muted" id="fileSize"></small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="removeFile">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================
         Submit buttons
    ======================================================== -->
    <div class="card border-0 shadow-sm mb-5">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
            <a href="/research/proposals/<?= $proposal['id'] ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>ยกเลิก
            </a>
            <button type="submit" class="btn btn-psu-primary btn-lg px-5">
                <i class="bi bi-floppy me-1"></i>บันทึกการแก้ไข
            </button>
        </div>
    </div>

</form>

<style>
:root { --psu-blue:#003B6D; --psu-accent:#0066CC; }
.text-psu-blue  { color: var(--psu-blue); }
.text-psu-accent{ color: var(--psu-accent); }
.bg-psu-blue    { background-color: var(--psu-blue); }
.btn-psu-primary{ background-color:var(--psu-accent); border-color:var(--psu-accent); color:#fff; }
.btn-psu-primary:hover{ background-color:var(--psu-blue); border-color:var(--psu-blue); color:#fff; }
.btn-outline-psu{ border-color:var(--psu-accent); color:var(--psu-accent); }
.btn-outline-psu:hover{ background-color:var(--psu-accent); color:#fff; }
.border-dashed{ border-style:dashed !important; }
#dropZone.drag-over{ background-color:rgba(0,102,204,.08); border-color:var(--psu-accent) !important; }
.co-investigator-row{ background:#f8f9fa; border:1px solid #dee2e6; border-radius:.375rem; padding:.5rem .75rem; }
</style>

<script>
$(document).ready(function () {

    // ---- PI Select2 Autocomplete ----
    function formatPersonOption(person) {
        if (!person.id) return person.text;
        var dept = person.dept ? '<small class="text-muted d-block">' + $('<div>').text(person.dept).html() + '</small>' : '';
        return $('<div>' + $('<div>').text(person.text).html() + dept + '</div>');
    }

    $('#pi_select').select2({
        placeholder: '-- พิมพ์ชื่อเพื่อค้นหาบุคลากร --',
        allowClear: true,
        minimumInputLength: 1,
        language: {
            inputTooShort: function() { return 'พิมพ์อย่างน้อย 1 ตัวอักษร'; },
            noResults:     function() { return 'ไม่พบรายชื่อ — กด Enter เพื่อใช้ชื่อที่พิมพ์'; },
            searching:     function() { return 'กำลังค้นหา...'; },
        },
        ajax: {
            url: '/research/api/personnel/search',
            dataType: 'json',
            delay: 200,
            data: function(params) { return { q: params.term }; },
            processResults: function(data) { return data; },
            cache: true,
        },
        tags: true,
        createTag: function(params) {
            var term = $.trim(params.term);
            if (term === '') return null;
            return { id: term, text: term, newTag: true };
        },
        templateResult:    formatPersonOption,
        templateSelection: function(p) { return p.text; },
    });

    $('#pi_select').on('change', function() {
        $('#pi_name_input').val($(this).val() || '');
    });

    // ---- Co-Investigator rows ----
    var coRowCount = 0;

    function addCoRow(val) {
        coRowCount++;
        var rowId = 'coRow' + coRowCount;
        var $row = $(
            '<div class="co-investigator-row d-flex align-items-start gap-2 mb-2" id="' + rowId + '">' +
            '  <i class="bi bi-person text-muted mt-2"></i>' +
            '  <div class="flex-grow-1">' +
            '    <select class="co-select form-select form-select-sm" style="width:100%;"></select>' +
            '    <input type="hidden" name="co_investigators[]" class="co-hidden-input">' +
            '  </div>' +
            '  <button type="button" class="btn btn-sm btn-outline-danger remove-co mt-1" data-row="' + rowId + '">' +
            '    <i class="bi bi-trash"></i>' +
            '  </button>' +
            '</div>'
        );
        $('#coInvestigatorContainer').append($row);

        var $sel = $row.find('.co-select');
        var $hid = $row.find('.co-hidden-input');

        if (val) {
            $sel.append(new Option(val, val, true, true));
            $hid.val(val);
        }

        $sel.select2({
            placeholder: 'พิมพ์ชื่อเพื่อค้นหา หรือกรอกเอง',
            allowClear: true,
            minimumInputLength: 1,
            language: {
                inputTooShort: function() { return 'พิมพ์อย่างน้อย 1 ตัวอักษร'; },
                noResults:     function() { return 'ไม่พบ — กด Enter เพื่อใช้ชื่อที่พิมพ์'; },
                searching:     function() { return 'กำลังค้นหา...'; },
            },
            ajax: {
                url: '/research/api/personnel/search',
                dataType: 'json',
                delay: 200,
                data: function(params) { return { q: params.term }; },
                processResults: function(data) { return data; },
                cache: true,
            },
            tags: true,
            createTag: function(params) {
                var term = $.trim(params.term);
                if (term === '') return null;
                return { id: term, text: term, newTag: true };
            },
            templateResult: formatPersonOption,
            templateSelection: function(p) { return p.text; },
            dropdownParent: $row,
        });

        $sel.on('change', function() {
            $hid.val($(this).val() || '');
        });
    }

    // Pre-populate from existing data
    var existingCo = <?= $coListJson ?>;
    if (Array.isArray(existingCo) && existingCo.length > 0) {
        existingCo.forEach(function (co) {
            addCoRow(typeof co === 'object' ? (co.name || '') : co);
        });
    }

    $('#addCoInvestigator').on('click', function () { addCoRow(''); });

    $(document).on('click', '.remove-co', function () {
        var rowId = $(this).data('row');
        $('#' + rowId).fadeOut(200, function () { $(this).remove(); });
    });

    // ---- Field of study → Faculty ----
    $('#field_of_study_id').on('change', function () {
        var faculty = $(this).find('option:selected').data('faculty') || '';
        $('#faculty_display').val(faculty);
    });

    // ---- Funding type badge ----
    var typeLabelMap = { internal:'ทุนภายใน', external:'ทุนภายนอก', government:'ทุนรัฐบาล', private:'ทุนเอกชน' };
    var typeColorMap = { internal:'primary', external:'success', government:'warning', private:'info' };

    function showFundingBadge() {
        var type = $('#funding_source_id').find('option:selected').data('type') || '';
        var $c = $('#fundingTypeBadgeContainer').empty();
        if (type) {
            $c.append('<span class="badge bg-' + (typeColorMap[type]||'secondary') + ' mt-1">ประเภท: ' + (typeLabelMap[type]||type) + '</span>');
        }
    }
    $('#funding_source_id').on('change', showFundingBadge);
    showFundingBadge();

    // ---- Budget display ----
    function updateBudgetDisplay() {
        var raw = $('#requested_budget').val().replace(/[^0-9.]/g, '');
        var num = parseFloat(raw);
        $('#budgetDisplay').text(isNaN(num) ? '' : 'จำนวน: ฿' + num.toLocaleString('th-TH', {minimumFractionDigits:2}));
    }
    $('#requested_budget').on('input', updateBudgetDisplay);
    updateBudgetDisplay();

    // ---- Abstract count ----
    function updateAbstractCount() {
        $('#abstractCount').text($('#abstract_th').val().length);
    }
    $('#abstract_th').on('input', updateAbstractCount);
    updateAbstractCount();

    // ---- File upload ----
    $('#attachment').on('change', function () {
        var file = this.files[0];
        if (!file) return;
        if (file.type !== 'application/pdf') {
            Swal.fire('ประเภทไฟล์ไม่ถูกต้อง', 'อนุญาตเฉพาะไฟล์ PDF เท่านั้น', 'error');
            $(this).val(''); return;
        }
        if (file.size > 10 * 1024 * 1024) {
            Swal.fire('ไฟล์ใหญ่เกินไป', 'ขนาดไฟล์ต้องไม่เกิน 10 MB', 'error');
            $(this).val(''); return;
        }
        $('#fileName').text(file.name);
        $('#fileSize').text((file.size/1024).toFixed(1) + ' KB (' + (file.size/(1024*1024)).toFixed(2) + ' MB)');
        $('#fileInfo').removeClass('d-none');
    });

    $('#removeFile').on('click', function () { $('#attachment').val(''); $('#fileInfo').addClass('d-none'); });

    // ---- Drag & drop ----
    var dz = document.getElementById('dropZone');
    ['dragenter','dragover'].forEach(function(e) {
        dz.addEventListener(e, function(ev) { ev.preventDefault(); $(dz).addClass('drag-over'); });
    });
    ['dragleave','drop'].forEach(function(e) {
        dz.addEventListener(e, function(ev) { ev.preventDefault(); $(dz).removeClass('drag-over'); });
    });
    dz.addEventListener('drop', function(e) {
        var file = e.dataTransfer.files[0];
        if (file) {
            var dt = new DataTransfer(); dt.items.add(file);
            document.getElementById('attachment').files = dt.files;
            $('#attachment').trigger('change');
        }
    });

    // ---- Date validation ----
    function validateDates() {
        var s = $('#start_date').val(), e = $('#end_date').val();
        if (s && e && e <= s) {
            $('#end_date')[0].setCustomValidity('invalid');
            $('#dateFeedback').show(); return false;
        }
        $('#end_date')[0].setCustomValidity('');
        $('#dateFeedback').hide(); return true;
    }
    $('#start_date, #end_date').on('change', validateDates);

    // ---- Form submit validation ----
    $('#editProposalForm').on('submit', function (e) {
        var piVal = $('#pi_name_input').val().trim();
        if (!piVal) {
            e.preventDefault(); e.stopPropagation();
            $('#pi_error').text('กรุณาระบุหัวหน้าโครงการ').show();
            $('#pi_select').next('.select2-container').find('.select2-selection').addClass('border-danger');
        }
        if (!this.checkValidity() || !validateDates()) {
            e.preventDefault(); e.stopPropagation();
        }
        $(this).addClass('was-validated');
    });
});
</script>
