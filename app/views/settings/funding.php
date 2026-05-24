<?php
/**
 * PSU Research - Settings: Funding Sources View
 * @var array $fundingSources
 * @var string $title
 */
$csrfToken = $_SESSION['csrf_token'] ?? '';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:#003B6D;">
            <i class="fas fa-wallet me-2"></i><?= h($pageTitle ?? 'จัดการแหล่งทุน') ?>
        </h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 breadcrumb-psu">
                <li class="breadcrumb-item"><a href="/research/">หน้าหลัก</a></li>
                <li class="breadcrumb-item">การตั้งค่า</li>
                <li class="breadcrumb-item active">จัดการแหล่งทุน</li>
            </ol>
        </nav>
    </div>
    <button type="button" class="btn text-white" style="background:#003B6D;" data-bs-toggle="modal" data-bs-target="#modalCreate">
        <i class="fas fa-plus me-1"></i> เพิ่มแหล่งทุน
    </button>
</div>

<!-- DataTable Card -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold" style="color:#003B6D;">
            <i class="fas fa-table me-1"></i> รายการแหล่งทุนทั้งหมด
            <span class="badge bg-secondary ms-2"><?= count($fundingSources) ?></span>
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="tableFunding" class="table table-hover align-middle mb-0 w-100">
                <thead>
                    <tr style="background:#003B6D;color:#fff;">
                        <th class="ps-3" style="width:5%">#</th>
                        <th style="width:25%">ชื่อแหล่งทุน</th>
                        <th style="width:12%">ประเภท</th>
                        <th style="width:20%">องค์กร / หน่วยงาน</th>
                        <th style="width:10%">ปีงบฯ (พ.ศ.)</th>
                        <th style="width:10%">โครงการ</th>
                        <th style="width:10%">สถานะ</th>
                        <th style="width:8%" class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($fundingSources)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-2x d-block mb-2 opacity-50"></i>
                                ยังไม่มีแหล่งทุนในระบบ
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($fundingSources as $i => $fs): ?>
                            <tr data-id="<?= $fs['id'] ?>">
                                <td class="ps-3 text-muted"><?= $i + 1 ?></td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($fs['name']) ?></div>
                                    <?php if (!empty($fs['description'])): ?>
                                        <small class="text-muted"><?= htmlspecialchars(mb_substr($fs['description'], 0, 60)) ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($fs['type'] === 'internal'): ?>
                                        <span class="badge badge-psu-internal">ภายใน</span>
                                    <?php else: ?>
                                        <span class="badge badge-psu-external">ภายนอก</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted"><?= htmlspecialchars($fs['organization'] ?? '-') ?></td>
                                <td class="text-center"><?= $fs['budget_year'] ? htmlspecialchars($fs['budget_year']) : '-' ?></td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border"><?= number_format($fs['proposal_count']) ?></span>
                                </td>
                                <td>
                                    <?php if ($fs['is_active']): ?>
                                        <span class="badge bg-success">ใช้งาน</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">ปิดใช้งาน</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button"
                                            class="btn btn-outline-primary btn-edit"
                                            title="แก้ไข"
                                            data-id="<?= $fs['id'] ?>"
                                            data-name="<?= htmlspecialchars($fs['name'], ENT_QUOTES) ?>"
                                            data-type="<?= htmlspecialchars($fs['type'], ENT_QUOTES) ?>"
                                            data-organization="<?= htmlspecialchars($fs['organization'] ?? '', ENT_QUOTES) ?>"
                                            data-description="<?= htmlspecialchars($fs['description'] ?? '', ENT_QUOTES) ?>"
                                            data-budget_year="<?= htmlspecialchars($fs['budget_year'] ?? '', ENT_QUOTES) ?>"
                                            data-is_active="<?= $fs['is_active'] ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEdit">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                        <?php if ($fs['proposal_count'] == 0): ?>
                                            <button type="button"
                                                class="btn btn-outline-danger btn-delete"
                                                title="ลบ"
                                                data-id="<?= $fs['id'] ?>"
                                                data-name="<?= htmlspecialchars($fs['name'], ENT_QUOTES) ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-outline-secondary" disabled title="มีโครงการที่ใช้งานอยู่">
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

<!-- ===================== MODAL: CREATE ===================== -->
<div class="modal fade" id="modalCreate" tabindex="-1" aria-labelledby="modalCreateLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="/research/settings/funding/store" id="formCreate">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="modal-header text-white" style="background:#003B6D;">
                    <h5 class="modal-title" id="modalCreateLabel">
                        <i class="fas fa-plus-circle me-2"></i>เพิ่มแหล่งทุนใหม่
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Name -->
                        <div class="col-12">
                            <label class="form-label required">ชื่อแหล่งทุน</label>
                            <input type="text" class="form-control" name="name" maxlength="255"
                                placeholder="เช่น ทุนวิจัยงบประมาณแผ่นดิน" required>
                        </div>
                        <!-- Type -->
                        <div class="col-md-6">
                            <label class="form-label required">ประเภทแหล่งทุน</label>
                            <div class="d-flex gap-4 mt-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="type" id="typeInternal" value="internal" required>
                                    <label class="form-check-label" for="typeInternal">
                                        <span class="badge badge-psu-internal">ภายใน</span> (งบประมาณมหาวิทยาลัย)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="type" id="typeExternal" value="external">
                                    <label class="form-check-label" for="typeExternal">
                                        <span class="badge badge-psu-external">ภายนอก</span> (หน่วยงานภายนอก)
                                    </label>
                                </div>
                            </div>
                        </div>
                        <!-- Budget Year -->
                        <div class="col-md-6">
                            <label class="form-label">ปีงบประมาณ (พ.ศ.)</label>
                            <input type="text" class="form-control" name="budget_year" maxlength="4"
                                placeholder="เช่น 2568" pattern="\d{4}">
                        </div>
                        <!-- Organization -->
                        <div class="col-12">
                            <label class="form-label">องค์กร / หน่วยงาน</label>
                            <input type="text" class="form-control" name="organization" maxlength="255"
                                placeholder="เช่น สำนักงานคณะกรรมการวิจัยแห่งชาติ (วช.)">
                        </div>
                        <!-- Description -->
                        <div class="col-12">
                            <label class="form-label">รายละเอียด</label>
                            <textarea class="form-control" name="description" rows="3"
                                placeholder="คำอธิบายเพิ่มเติมเกี่ยวกับแหล่งทุน..."></textarea>
                        </div>
                        <!-- Active -->
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActiveCreate" checked>
                                <label class="form-check-label" for="isActiveCreate">เปิดใช้งาน</label>
                            </div>
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

<!-- ===================== MODAL: EDIT ===================== -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="formEdit" action="">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="modal-header text-white" style="background:#003B6D;">
                    <h5 class="modal-title" id="modalEditLabel">
                        <i class="fas fa-edit me-2"></i>แก้ไขแหล่งทุน
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label required">ชื่อแหล่งทุน</label>
                            <input type="text" class="form-control" name="name" id="editName" maxlength="255" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">ประเภทแหล่งทุน</label>
                            <div class="d-flex gap-4 mt-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="type" id="editTypeInternal" value="internal">
                                    <label class="form-check-label" for="editTypeInternal">
                                        <span class="badge badge-psu-internal">ภายใน</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="type" id="editTypeExternal" value="external">
                                    <label class="form-check-label" for="editTypeExternal">
                                        <span class="badge badge-psu-external">ภายนอก</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ปีงบประมาณ (พ.ศ.)</label>
                            <input type="text" class="form-control" name="budget_year" id="editBudgetYear" maxlength="4" pattern="\d{4}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">องค์กร / หน่วยงาน</label>
                            <input type="text" class="form-control" name="organization" id="editOrganization" maxlength="255">
                        </div>
                        <div class="col-12">
                            <label class="form-label">รายละเอียด</label>
                            <textarea class="form-control" name="description" id="editDescription" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="editIsActive">
                                <label class="form-check-label" for="editIsActive">เปิดใช้งาน</label>
                            </div>
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

<!-- ===================== DELETE FORMS (hidden) ===================== -->
<div id="deleteForms"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Initialize DataTable
    if (typeof $.fn.DataTable !== 'undefined') {
        $('#tableFunding').DataTable({
            language: window.DataTablesThaiLang || {},
            pageLength: 25,
            order: [[4, 'desc'], [1, 'asc']],
            columnDefs: [
                { orderable: false, targets: [7] },
                { searchable: false, targets: [0, 6, 7] },
            ],
        });
    }

    // Populate Edit Modal
    document.querySelectorAll('.btn-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const data = this.dataset;
            const form = document.getElementById('formEdit');
            form.action = '/research/settings/funding/' + data.id + '/update';
            document.getElementById('editName').value         = data.name;
            document.getElementById('editOrganization').value = data.organization;
            document.getElementById('editDescription').value  = data.description;
            document.getElementById('editBudgetYear').value   = data.budget_year;
            document.getElementById('editIsActive').checked   = data.is_active === '1';
            // Radio type
            const typeVal = data.type;
            document.querySelectorAll('#modalEdit input[name="type"]').forEach(function (r) {
                r.checked = (r.value === typeVal);
            });
        });
    });

    // Delete confirmation
    document.querySelectorAll('.btn-delete').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id   = this.dataset.id;
            const name = this.dataset.name;

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'ยืนยันการลบ',
                    html: `ต้องการลบแหล่งทุน <strong>${name}</strong> ใช่หรือไม่?<br><small class="text-muted">การดำเนินการนี้ไม่สามารถย้อนกลับได้</small>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: '<i class="fas fa-trash me-1"></i> ลบ',
                    cancelButtonText: 'ยกเลิก',
                }).then(function (result) {
                    if (result.isConfirmed) {
                        submitDeleteForm(id);
                    }
                });
            } else {
                if (confirm('ต้องการลบแหล่งทุน "' + name + '" ใช่หรือไม่?')) {
                    submitDeleteForm(id);
                }
            }
        });
    });

    function submitDeleteForm(id) {
        const container = document.getElementById('deleteForms');
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/research/settings/funding/' + id + '/delete';
        form.innerHTML = '<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">';
        container.appendChild(form);
        form.submit();
    }
});
</script>

<style>
.badge-psu-internal { background-color: #0066CC; color: #fff; }
.badge-psu-external { background-color: #7c3aed; color: #fff; }
.required::after { content: ' *'; color: #dc2626; }
</style>
