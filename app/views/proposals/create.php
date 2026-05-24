<?php
/**
 * View: proposals/create.php — เพิ่มข้อเสนอโครงการวิจัย
 * Variables: $fundingSources, $fieldsOfStudy, $researchers, $csrfToken, $flash
 */
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0" style="color:#003B6D;">
        <i class="fas fa-file-plus me-2"></i>เพิ่มข้อเสนอโครงการวิจัย
    </h4>
    <a href="<?= BASE_URL ?>/proposals" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>กลับ
    </a>
</div>

<form method="POST" action="<?= BASE_URL ?>/proposals/store" enctype="multipart/form-data" id="proposalForm" novalidate>
    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">

    <!-- ── Section 1: ชื่อโครงการ ──────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header text-white" style="background:#003B6D;">
            <h6 class="mb-0 fw-semibold"><span class="badge bg-white text-dark me-2">1</span>ชื่อโครงการ</h6>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label fw-semibold">ชื่อโครงการ (ภาษาไทย) <span class="text-danger">*</span></label>
                <textarea name="title_th" class="form-control" rows="3" required
                    placeholder="ระบุชื่อโครงการวิจัยภาษาไทย"><?= h($_POST['title_th'] ?? '') ?></textarea>
                <div class="invalid-feedback">กรุณากรอกชื่อโครงการภาษาไทย</div>
            </div>
            <div class="mb-0">
                <label class="form-label fw-semibold">ชื่อโครงการ (ภาษาอังกฤษ)</label>
                <textarea name="title_en" class="form-control" rows="2"
                    placeholder="Research Project Title in English"><?= h($_POST['title_en'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <!-- ── Section 2: ผู้วิจัย ─────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header text-white" style="background:#003B6D;">
            <h6 class="mb-0 fw-semibold"><span class="badge bg-white text-dark me-2">2</span>ผู้วิจัย</h6>
        </div>
        <div class="card-body">
            <div class="mb-4">
                <label class="form-label fw-semibold">
                    หัวหน้าโครงการ <span class="text-danger">*</span>
                    <small class="text-muted fw-normal ms-1">(เลือกจากบุคลากรคณะมนุษยศาสตร์ฯ หรือพิมพ์ชื่อเอง)</small>
                </label>
                <!-- Hidden real input that holds the selected name -->
                <input type="hidden" name="pi_name" id="pi_name_input" value="<?= h($_POST['pi_name'] ?? '') ?>">
                <!-- Select2 element -->
                <select id="pi_select" class="form-select" style="width:100%;">
                    <?php if (!empty($_POST['pi_name'])): ?>
                        <option value="<?= h($_POST['pi_name']) ?>" selected><?= h($_POST['pi_name']) ?></option>
                    <?php endif; ?>
                </select>
                <div class="invalid-feedback d-block" id="pi_error" style="display:none!important;"></div>
                <div class="form-text">
                    <i class="fas fa-info-circle me-1 text-info"></i>
                    พิมพ์ชื่อเพื่อค้นหาจากบุคลากรสายวิชาการ หรือพิมพ์ชื่อที่ต้องการหากไม่พบในรายการ
                </div>
            </div>

            <div>
                <label class="form-label fw-semibold">ผู้ร่วมวิจัย (Co-Investigators)</label>
                <div id="coInvestigatorContainer"></div>
                <button type="button" id="addCoInvestigator" class="btn btn-sm btn-outline-primary mt-2">
                    <i class="fas fa-user-plus me-1"></i>เพิ่มผู้ร่วมวิจัย
                </button>
                <div class="form-text">สามารถเพิ่มรายชื่อผู้ร่วมวิจัยได้หลายคน พิมพ์เพื่อค้นหาหรือกรอกเอง</div>
            </div>
        </div>
    </div>

    <!-- ── Section 3: สาขาวิชา + แหล่งทุน ────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header text-white" style="background:#003B6D;">
            <h6 class="mb-0 fw-semibold"><span class="badge bg-white text-dark me-2">3</span>สาขาวิชาและแหล่งทุน</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">สาขาวิชา <span class="text-danger">*</span></label>
                    <select name="field_of_study_id" id="field_of_study_id" class="form-select" required>
                        <option value="">-- เลือกสาขาวิชา --</option>
                        <?php
                        $curFaculty = '';
                        foreach ($fieldsOfStudy as $fos):
                            if ($fos['faculty'] !== $curFaculty):
                                if ($curFaculty !== '') echo '</optgroup>';
                                echo '<optgroup label="' . h($fos['faculty']) . '">';
                                $curFaculty = $fos['faculty'];
                            endif;
                        ?>
                            <option value="<?= (int)$fos['id'] ?>"
                                data-faculty="<?= h($fos['faculty']) ?>"
                                <?= ($_POST['field_of_study_id'] ?? '') == $fos['id'] ? 'selected' : '' ?>>
                                <?= h($fos['name_th']) ?>
                            </option>
                        <?php endforeach; if ($curFaculty !== '') echo '</optgroup>'; ?>
                    </select>
                    <div class="invalid-feedback">กรุณาเลือกสาขาวิชา</div>
                    <div class="mt-2">
                        <input type="text" id="faculty_display" class="form-control form-control-sm bg-light"
                               readonly placeholder="คณะ/หน่วยงานจะแสดงอัตโนมัติ">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">แหล่งทุน <span class="text-danger">*</span></label>
                    <select name="funding_source_id" id="funding_source_id" class="form-select" required>
                        <option value="">-- เลือกแหล่งทุน --</option>
                        <?php foreach ($fundingSources as $fs): ?>
                            <option value="<?= (int)$fs['id'] ?>" data-type="<?= h($fs['type'] ?? '') ?>"
                                <?= ($_POST['funding_source_id'] ?? '') == $fs['id'] ? 'selected' : '' ?>>
                                <?= h($fs['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">กรุณาเลือกแหล่งทุน</div>
                    <div id="fundingTypeBadge" class="mt-2"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Section 4: งบประมาณ ─────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header text-white" style="background:#003B6D;">
            <h6 class="mb-0 fw-semibold"><span class="badge bg-white text-dark me-2">4</span>งบประมาณ</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">งบประมาณที่ขอ (บาท) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">฿</span>
                        <input type="text" name="requested_budget" id="requested_budget"
                               class="form-control text-end" placeholder="0.00" inputmode="decimal" required
                               value="<?= h($_POST['requested_budget'] ?? '') ?>">
                        <span class="input-group-text">บาท</span>
                    </div>
                    <div class="form-text" id="budgetDisplay"></div>
                    <div class="invalid-feedback">กรุณากรอกงบประมาณ</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">ปีงบประมาณ <span class="text-danger">*</span></label>
                    <select name="budget_year" class="form-select" required>
                        <option value="">-- เลือกปีงบประมาณ --</option>
                        <?php for ($yr = 2570; $yr >= 2564; $yr--): ?>
                            <option value="<?= $yr ?>" <?= ($_POST['budget_year'] ?? '') == $yr ? 'selected' : '' ?>>
                                <?= $yr ?> (<?= $yr - 543 ?>)
                            </option>
                        <?php endfor; ?>
                    </select>
                    <div class="invalid-feedback">กรุณาเลือกปีงบประมาณ</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Section 5: ระยะเวลา ─────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header text-white" style="background:#003B6D;">
            <h6 class="mb-0 fw-semibold"><span class="badge bg-white text-dark me-2">5</span>ระยะเวลาดำเนินการ</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">วันที่เริ่มต้น</label>
                    <input type="date" name="start_date" class="form-control"
                           value="<?= h($_POST['start_date'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">วันที่สิ้นสุด</label>
                    <input type="date" name="end_date" id="end_date" class="form-control"
                           value="<?= h($_POST['end_date'] ?? '') ?>">
                    <div class="invalid-feedback" id="dateFeedback">วันสิ้นสุดต้องหลังวันเริ่มต้น</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Buttons ──────────────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-5">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
            <a href="<?= BASE_URL ?>/proposals" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>ยกเลิก
            </a>
            <div class="d-flex gap-2">
                <button type="submit" name="action" value="draft" class="btn btn-outline-secondary btn-lg px-4">
                    <i class="fas fa-save me-1"></i>บันทึกฉบับร่าง
                </button>
                <button type="button" id="submitBtn" class="btn btn-lg px-4 text-white" style="background:#003B6D;">
                    <i class="fas fa-paper-plane me-1"></i>ส่งพิจารณา
                </button>
            </div>
        </div>
    </div>
</form>

<script>
$(document).ready(function () {

    // ── PI Select2 Autocomplete ───────────────────────────────────────
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
            url: '<?= BASE_URL ?>/api/personnel/search',
            dataType: 'json',
            delay: 200,
            data: function(params) { return { q: params.term }; },
            processResults: function(data) { return data; },
            cache: true,
        },
        tags: true,         // allow free-text entry
        createTag: function(params) {
            var term = $.trim(params.term);
            if (term === '') return null;
            return { id: term, text: term, newTag: true };
        },
        templateResult:    formatPersonOption,
        templateSelection: function(p) { return p.text; },
    });

    // Sync Select2 value → hidden input
    $('#pi_select').on('change', function() {
        var val = $(this).val() || '';
        $('#pi_name_input').val(val);
    });

    // ── Co-Investigator rows ──────────────────────────────────────────
    var coCount = 0;
    function addCoRow(val) {
        coCount++;
        var rowId = 'co' + coCount;
        var $row = $(
            '<div class="d-flex align-items-start gap-2 mb-2 bg-light border rounded p-2" id="' + rowId + '">' +
            '  <i class="fas fa-user text-muted mt-2"></i>' +
            '  <div class="flex-grow-1">' +
            '    <select class="co-select form-select form-select-sm" style="width:100%;"></select>' +
            '    <input type="hidden" name="co_investigators[]" class="co-hidden-input">' +
            '  </div>' +
            '  <button type="button" class="btn btn-sm btn-outline-danger remove-co mt-1" data-id="' + rowId + '">' +
            '    <i class="fas fa-trash"></i>' +
            '  </button>' +
            '</div>'
        );
        $('#coInvestigatorContainer').append($row);

        // If a value was provided, pre-populate
        var $sel = $row.find('.co-select');
        var $hid = $row.find('.co-hidden-input');

        if (val) {
            var opt = new Option(val, val, true, true);
            $sel.append(opt);
            $hid.val(val);
        }

        // Init Select2 on this co-row
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
                url: '<?= BASE_URL ?>/api/personnel/search',
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

        // Sync Select2 → hidden input
        $sel.on('change', function() {
            $hid.val($(this).val() || '');
        });
    }

    $('#addCoInvestigator').on('click', function () { addCoRow(''); });
    $(document).on('click', '.remove-co', function () {
        $('#' + $(this).data('id')).fadeOut(200, function () { $(this).remove(); });
    });
    <?php foreach ((array)($_POST['co_investigators'] ?? []) as $co): ?>
    addCoRow(<?= json_encode(is_array($co) ? ($co['name'] ?? '') : $co) ?>);
    <?php endforeach; ?>

    // ── Field → Faculty ───────────────────────────────────────────────
    $('#field_of_study_id').on('change', function () {
        $('#faculty_display').val($(this).find('option:selected').data('faculty') || '');
    }).trigger('change');

    // ── Funding type badge ────────────────────────────────────────────
    var typeMap = { internal:'ทุนภายใน', external:'ทุนภายนอก' };
    var colorMap = { internal:'primary', external:'success' };
    $('#funding_source_id').on('change', function () {
        var t = $(this).find('option:selected').data('type') || '';
        $('#fundingTypeBadge').html(t ? '<span class="badge bg-' + (colorMap[t]||'secondary') + '">' + (typeMap[t]||t) + '</span>' : '');
    }).trigger('change');

    // ── Budget display ────────────────────────────────────────────────
    $('#requested_budget').on('input', function () {
        var n = parseFloat($(this).val().replace(/[^0-9.]/g, ''));
        $('#budgetDisplay').text(isNaN(n) ? '' : 'จำนวน: ฿' + n.toLocaleString('th-TH', {minimumFractionDigits:2}));
    });

    // ── Date validation ───────────────────────────────────────────────
    function checkDates() {
        var s = $('[name=start_date]').val(), e = $('#end_date').val();
        if (s && e && e <= s) {
            document.getElementById('end_date').setCustomValidity('invalid');
            $('#dateFeedback').show(); return false;
        }
        document.getElementById('end_date').setCustomValidity('');
        $('#dateFeedback').hide(); return true;
    }
    $('[name=start_date], #end_date').on('change', checkDates);

    // ── Form submit (draft) ───────────────────────────────────────────
    $('#proposalForm').on('submit', function (e) {
        var piVal = $('#pi_name_input').val().trim();
        if (!piVal) {
            e.preventDefault();
            $('#pi_error').text('กรุณาระบุหัวหน้าโครงการ').show();
            $('#pi_select').next('.select2-container').find('.select2-selection').addClass('border-danger');
            $(this).addClass('was-validated');
            return;
        }
        $('#pi_error').hide();
        if (!this.checkValidity() || !checkDates()) {
            e.preventDefault(); $(this).addClass('was-validated');
        }
    });

    // ── Submit button ─────────────────────────────────────────────────
    $('#submitBtn').on('click', function () {
        var form = document.getElementById('proposalForm');
        var piVal = $('#pi_name_input').val().trim();
        if (!piVal) {
            $('#pi_error').text('กรุณาระบุหัวหน้าโครงการ').show();
            $('#pi_select').next('.select2-container').find('.select2-selection').addClass('border-danger');
        }
        if (!form.checkValidity() || !checkDates() || !piVal) { $(form).addClass('was-validated'); return; }
        Swal.fire({
            title: 'ยืนยันการส่งพิจารณา',
            text: 'ข้อเสนอใหม่จะถูกบันทึกเป็นฉบับร่างก่อน กรุณามอบหมายผู้ทรงคุณวุฒิก่อนเปลี่ยนสถานะ',
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#003B6D',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'รับทราบและบันทึก',
            cancelButtonText: 'ยกเลิก',
        }).then(function (r) {
            if (r.isConfirmed) {
                $('<input>').attr({type:'hidden',name:'action',value:'draft'}).appendTo('#proposalForm');
                form.submit();
            }
        });
    });
});
</script>
