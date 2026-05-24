<?php
/**
 * PSU Research - Settings: Fields of Study View
 * @var array $fields
 * @var array $faculties
 * @var string $title
 */
$csrfToken = $_SESSION['csrf_token'] ?? '';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:#003B6D;">
            <i class="fas fa-graduation-cap me-2"></i><?= h($pageTitle ?? 'จัดการสาขาวิชา') ?>
        </h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 breadcrumb-psu">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard">หน้าหลัก</a></li>
                <li class="breadcrumb-item"><a href="#">การตั้งค่า</a></li>
                <li class="breadcrumb-item active">จัดการสาขาวิชา</li>
            </ol>
        </nav>
    </div>
    <button type="button" class="btn text-white" style="background:#003B6D;" data-bs-toggle="modal" data-bs-target="#modalCreateField">
        <i class="fas fa-plus me-1"></i> เพิ่มสาขาวิชา
    </button>
</div>

<!-- Faculty Filter Bar -->
<?php if (!empty($faculties)): ?>
<div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
    <span class="text-muted small">กรองตามคณะ:</span>
    <button class="btn btn-sm text-white faculty-filter active" style="background:#003B6D;" data-faculty="">ทั้งหมด</button>
    <?php foreach ($faculties as $fac): ?>
        <button class="btn btn-sm btn-outline-secondary faculty-filter" data-faculty="<?= htmlspecialchars($fac, ENT_QUOTES) ?>">
            <?= htmlspecialchars($fac) ?>
        </button>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- DataTable Card -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <span class="fw-semibold" style="color:#003B6D;">
            <i class="fas fa-table me-1"></i> รายการสาขาวิชาทั้งหมด
            <span class="badge bg-secondary ms-2"><?= count($fields) ?></span>
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="tableFields" class="table table-hover align-middle mb-0 w-100">
                <thead style="background:#003B6D;color:#fff;">
                    <tr>
                        <th class="ps-3" style="width:5%">#</th>
                        <th style="width:12%">รหัสสาขา</th>
                        <th style="width:30%">ชื่อ (ภาษาไทย)</th>
                        <th style="width:30%">ชื่อ (ภาษาอังกฤษ)</th>
                        <th style="width:15%">คณะ / หน่วยงาน</th>
                        <th style="width:8%" class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($fields)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fs-1 d-block mb-2"></i>
                                ยังไม่มีสาขาวิชาในระบบ
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($fields as $i => $f): ?>
                            <tr data-faculty="<?= htmlspecialchars($f['faculty'] ?? '', ENT_QUOTES) ?>">
                                <td class="ps-3 text-muted"><?= $i + 1 ?></td>
                                <td>
                                    <code style="color:#003B6D;" class="fw-semibold"><?= htmlspecialchars($f['code']) ?></code>
                                </td>
                                <td><?= htmlspecialchars($f['name_th']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($f['name_en']) ?></td>
                                <td>
                                    <?php if (!empty($f['faculty'])): ?>
                                        <span class="badge border" style="background:#E8F0FE;color:#003B6D;">
                                            <?= htmlspecialchars($f['faculty']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button"
                                            class="btn btn-outline-primary btn-edit-field"
                                            title="แก้ไข"
                                            data-id="<?= $f['id'] ?>"
                                            data-code="<?= htmlspecialchars($f['code'], ENT_QUOTES) ?>"
                                            data-name_th="<?= htmlspecialchars($f['name_th'], ENT_QUOTES) ?>"
                                            data-name_en="<?= htmlspecialchars($f['name_en'], ENT_QUOTES) ?>"
                                            data-faculty="<?= htmlspecialchars($f['faculty'] ?? '', ENT_QUOTES) ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEditField">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                        <?php $canDelete = ($f['proposal_count'] ?? 0) == 0; ?>
                                        <?php if ($canDelete): ?>
                                            <button type="button"
                                                class="btn btn-outline-danger btn-delete-field"
                                                title="ลบ"
                                                data-id="<?= $f['id'] ?>"
                                                data-name="<?= htmlspecialchars($f['name_th'], ENT_QUOTES) ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-outline-secondary" disabled
                                                title="มีโครงการที่ใช้งานอยู่ <?= $f['proposal_count'] ?> รายการ">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===================== MODAL: CREATE FIELD ===================== -->
<div class="modal fade" id="modalCreateField" tabindex="-1" aria-labelledby="modalCreateFieldLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/settings/fields/store" id="formCreateField">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="modal-header text-white" style="background:#003B6D;">
                    <h5 class="modal-title" id="modalCreateFieldLabel">
                        <i class="fas fa-plus-circle me-2"></i>เพิ่มสาขาวิชาใหม่
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Code -->
                        <div class="col-md-4">
                            <label class="form-label required">รหัสสาขาวิชา</label>
                            <input type="text" class="form-control text-uppercase" name="code" maxlength="20"
                                placeholder="เช่น CS, MATH, BIO" pattern="[A-Za-z0-9\-]+" required>
                            <div class="form-text">ตัวอักษรภาษาอังกฤษและตัวเลขเท่านั้น</div>
                        </div>
                        <!-- Faculty -->
                        <div class="col-md-8">
                            <label class="form-label">คณะ / หน่วยงาน</label>
                            <input type="text" class="form-control" name="faculty"
                                list="facultySuggestions" placeholder="เช่น คณะวิทยาศาสตร์">
                            <datalist id="facultySuggestions">
                                <?php foreach ($faculties as $fac): ?>
                                    <option value="<?= htmlspecialchars($fac) ?>">
                                <?php endforeach; ?>
                                <option value="คณะวิทยาศาสตร์">
                                <option value="คณะวิศวกรรมศาสตร์">
                                <option value="คณะแพทยศาสตร์">
                                <option value="คณะเภสัชศาสตร์">
                                <option value="คณะพยาบาลศาสตร์">
                                <option value="คณะศึกษาศาสตร์">
                                <option value="คณะมนุษยศาสตร์และสังคมศาสตร์">
                                <option value="คณะเศรษฐศาสตร์">
                                <option value="คณะนิติศาสตร์">
                                <option value="คณะทรัพยากรธรรมชาติ">
                                <option value="คณะอุตสาหกรรมเกษตร">
                                <option value="คณะเทคโนโลยีและสิ่งแวดล้อม">
                                <option value="วิทยาลัยนานาชาติ">
                            </datalist>
                        </div>
                        <!-- Name TH -->
                        <div class="col-12">
                            <label class="form-label required">ชื่อสาขาวิชา (ภาษาไทย)</label>
                            <input type="text" class="form-control" name="name_th"
                                placeholder="เช่น วิทยาการคอมพิวเตอร์" required>
                        </div>
                        <!-- Name EN -->
                        <div class="col-12">
                            <label class="form-label required">ชื่อสาขาวิชา (ภาษาอังกฤษ)</label>
                            <input type="text" class="form-control" name="name_en"
                                placeholder="e.g. Computer Science" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn text-white" style="background:#003B6D;">
                        <i class="fas fa-save me-1"></i> บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===================== MODAL: EDIT FIELD ===================== -->
<div class="modal fade" id="modalEditField" tabindex="-1" aria-labelledby="modalEditFieldLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="formEditField" action="">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="modal-header text-white" style="background:#003B6D;">
                    <h5 class="modal-title" id="modalEditFieldLabel">
                        <i class="fas fa-edit me-2"></i>แก้ไขสาขาวิชา
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label required">รหัสสาขาวิชา</label>
                            <input type="text" class="form-control text-uppercase" name="code" id="editFieldCode"
                                maxlength="20" pattern="[A-Za-z0-9\-]+" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">คณะ / หน่วยงาน</label>
                            <input type="text" class="form-control" name="faculty" id="editFieldFaculty"
                                list="facultySuggestionsEdit">
                            <datalist id="facultySuggestionsEdit">
                                <?php foreach ($faculties as $fac): ?>
                                    <option value="<?= htmlspecialchars($fac) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="col-12">
                            <label class="form-label required">ชื่อสาขาวิชา (ภาษาไทย)</label>
                            <input type="text" class="form-control" name="name_th" id="editFieldNameTh" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label required">ชื่อสาขาวิชา (ภาษาอังกฤษ)</label>
                            <input type="text" class="form-control" name="name_en" id="editFieldNameEn" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn text-white" style="background:#003B6D;">
                        <i class="fas fa-save me-1"></i> บันทึกการแก้ไข
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden delete forms container -->
<div id="deleteFieldForms"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // DataTable
    if (typeof $.fn.DataTable !== 'undefined') {
        var dt = $('#tableFields').DataTable({
            language: window.DataTablesThaiLang || {},
            pageLength: 25,
            order: [[4, 'asc'], [1, 'asc']],
            columnDefs: [
                { orderable: false, targets: [5] },
                { searchable: false, targets: [0, 5] },
            ],
        });

        // Faculty filter buttons
        document.querySelectorAll('.faculty-filter').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.faculty-filter').forEach(b => b.style.background=''; b.style.color=''; b.style.borderColor='');
                document.querySelectorAll('.faculty-filter').forEach(b => b.classList.add('btn-outline-secondary'));
                this.classList.add('active');
                this.classList.remove('btn-outline-secondary');

                var fac = this.dataset.faculty;
                if (fac === '') {
                    dt.search('').columns(4).search('').draw();
                } else {
                    dt.columns(4).search(fac).draw();
                }
            });
        });
        // Fix first button style
        
    }

    // Populate Edit Field Modal
    document.querySelectorAll('.btn-edit-field').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const data = this.dataset;
            document.getElementById('formEditField').action = '<?= BASE_URL ?>/settings/fields/' + data.id + '/update';
            document.getElementById('editFieldCode').value    = data.code;
            document.getElementById('editFieldNameTh').value  = data.name_th;
            document.getElementById('editFieldNameEn').value  = data.name_en;
            document.getElementById('editFieldFaculty').value = data.faculty;
        });
    });

    // Delete confirmation
    document.querySelectorAll('.btn-delete-field').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id   = this.dataset.id;
            const name = this.dataset.name;

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'ยืนยันการลบ',
                    html: `ต้องการลบสาขาวิชา <strong>${name}</strong> ใช่หรือไม่?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: '<i class="fas fa-trash me-1"></i> ลบ',
                    cancelButtonText: 'ยกเลิก',
                }).then(function (result) {
                    if (result.isConfirmed) submitDeleteField(id);
                });
            } else {
                if (confirm('ต้องการลบสาขาวิชา "' + name + '" ใช่หรือไม่?')) {
                    submitDeleteField(id);
                }
            }
        });
    });

    function submitDeleteField(id) {
        const container = document.getElementById('deleteFieldForms');
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= BASE_URL ?>/settings/fields/' + id + '/delete';
        form.innerHTML = '<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">';
        container.appendChild(form);
        form.submit();
    }

    // Auto-uppercase code input
    document.querySelectorAll('input[name="code"]').forEach(function (input) {
        input.addEventListener('input', function () {
            const pos = this.selectionStart;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(pos, pos);
        });
    });
});
</script>

<style>
.bg-light-blue { background-color: #E8F0FE; }
.required::after { content: ' *'; color: #dc2626; }
.faculty-filter.active { background: #003B6D !important; border-color: #003B6D !important; color: #fff !important; }
</style>
