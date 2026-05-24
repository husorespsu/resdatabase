<?php
/**
 * View: ทะเบียนผู้ทรงคุณวุฒิ (Expert Reviewers Registry)
 * Variables injected by Controller::render():
 * @var array  $reviewers
 * @var string $search
 * @var string $csrfToken
 */
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="mb-1 fw-bold" style="color:#003B6D;">
            <i class="fas fa-user-tie me-2"></i>ทะเบียนผู้ทรงคุณวุฒิ
        </h4>
        <p class="text-muted mb-0 small">จัดการข้อมูลผู้ทรงคุณวุฒิภายนอก</p>
    </div>
    <button class="btn text-white fw-semibold" style="background:#003B6D;"
            data-bs-toggle="modal" data-bs-target="#reviewerModal" id="btnAddReviewer">
        <i class="fas fa-plus me-1"></i>เพิ่มผู้ทรงคุณวุฒิ
    </button>
</div>

<!-- Search Bar -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <form method="GET" action="/research/reviewers" class="row g-2 align-items-center">
            <div class="col-md-6 col-lg-5">
                <div class="input-group">
                    <span class="input-group-text bg-white">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input type="text" name="search" class="form-control"
                           placeholder="ค้นหาชื่อ, สังกัด, ความเชี่ยวชาญ..."
                           value="<?= htmlspecialchars($search ?? '') ?>">
                    <button class="btn text-white" style="background:#003B6D;" type="submit">ค้นหา</button>
                    <?php if (!empty($search)): ?>
                        <a href="/research/reviewers" class="btn btn-outline-secondary">ล้าง</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-auto ms-md-auto text-muted small">
                พบ <strong><?= count($reviewers) ?></strong> รายการ
            </div>
        </form>
    </div>
</div>

<!-- DataTable Card -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="reviewersTable" class="table table-hover align-middle mb-0" style="width:100%">
                <thead>
                    <tr style="background:#003B6D; color:#fff;">
                        <th>#</th>
                        <th>ชื่อ-นามสกุล</th>
                        <th>ตำแหน่ง</th>
                        <th>สังกัด</th>
                        <th>ความเชี่ยวชาญ</th>
                        <th>อีเมล</th>
                        <th>เบอร์โทร</th>
                        <th>สถานะ</th>
                        <th class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviewers as $i => $r): ?>
                    <tr data-id="<?= $r['id'] ?>">
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td>
                            <div class="fw-semibold">
                                <?= htmlspecialchars($r['title'] . $r['first_name'] . ' ' . $r['last_name']) ?>
                            </div>
                        </td>
                        <td class="text-muted small"><?= htmlspecialchars($r['position'] ?? '-') ?></td>
                        <td class="small"><?= htmlspecialchars($r['institution'] ?? '-') ?></td>
                        <td>
                            <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 small">
                                <?= htmlspecialchars($r['expertise'] ?? '-') ?>
                            </span>
                        </td>
                        <td class="small">
                            <?php if (!empty($r['email'])): ?>
                                <a href="mailto:<?= htmlspecialchars($r['email']) ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($r['email']) ?>
                                </a>
                            <?php else: ?>-<?php endif; ?>
                        </td>
                        <td class="small"><?= htmlspecialchars($r['phone'] ?? '-') ?></td>
                        <td>
                            <span class="badge <?= $r['is_active'] ? 'bg-success' : 'bg-secondary' ?>"
                                  id="status-badge-<?= $r['id'] ?>">
                                <?= $r['is_active'] ? 'ใช้งาน' : 'ไม่ใช้งาน' ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary btn-edit"
                                        data-id="<?= $r['id'] ?>"
                                        data-reviewer='<?= htmlspecialchars(json_encode($r, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>'
                                        title="แก้ไข">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn <?= $r['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?> btn-toggle"
                                        data-id="<?= $r['id'] ?>"
                                        data-active="<?= $r['is_active'] ?>"
                                        title="<?= $r['is_active'] ? 'ระงับการใช้งาน' : 'เปิดใช้งาน' ?>">
                                    <i class="fas fa-<?= $r['is_active'] ? 'ban' : 'check' ?>"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($reviewers)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">
                            <i class="fas fa-user-slash fa-2x mb-2 d-block opacity-25"></i>
                            ไม่พบข้อมูลผู้ทรงคุณวุฒิ
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div class="modal fade" id="reviewerModal" tabindex="-1" aria-labelledby="reviewerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header text-white" style="background:#003B6D;">
                <h5 class="modal-title" id="reviewerModalLabel">
                    <i class="fas fa-user-tie me-2"></i>
                    <span id="modalTitleText">เพิ่มผู้ทรงคุณวุฒิ</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="reviewerForm" method="POST" action="/research/reviewers/store">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="modal-body">
                    <div class="row g-3">

                        <!-- Personal Info -->
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-user me-1"></i>ข้อมูลส่วนตัว
                            </h6>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-semibold">คำนำหน้า <span class="text-danger">*</span></label>
                            <select name="title" id="rvTitle" class="form-select" required>
                                <option value="">-- เลือก --</option>
                                <option value="นาย">นาย</option>
                                <option value="นาง">นาง</option>
                                <option value="นางสาว">นางสาว</option>
                                <option value="ดร.">ดร.</option>
                                <option value="ผศ.">ผศ.</option>
                                <option value="ผศ.ดร.">ผศ.ดร.</option>
                                <option value="รศ.">รศ.</option>
                                <option value="รศ.ดร.">รศ.ดร.</option>
                                <option value="ศ.">ศ.</option>
                                <option value="ศ.ดร.">ศ.ดร.</option>
                                <option value="Prof.">Prof.</option>
                                <option value="Dr.">Dr.</option>
                            </select>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label fw-semibold">ชื่อ <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" id="rvFirstName" class="form-control" required
                                   placeholder="ชื่อจริง">
                        </div>

                        <div class="col-md-5">
                            <label class="form-label fw-semibold">นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" id="rvLastName" class="form-control" required
                                   placeholder="นามสกุล">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">ความเชี่ยวชาญ <span class="text-danger">*</span></label>
                            <input type="text" name="expertise" id="rvExpertise" class="form-control" required
                                   placeholder="เช่น วิทยาศาสตร์การแพทย์, วิศวกรรมศาสตร์">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">ตำแหน่ง</label>
                            <input type="text" name="position" id="rvPosition" class="form-control"
                                   placeholder="เช่น อาจารย์ประจำ, รองศาสตราจารย์">
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-semibold">สังกัด/หน่วยงาน</label>
                            <input type="text" name="institution" id="rvInstitution" class="form-control"
                                   placeholder="มหาวิทยาลัย/หน่วยงาน">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">อีเมล</label>
                            <input type="email" name="email" id="rvEmail" class="form-control"
                                   placeholder="example@email.com">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">เบอร์โทรศัพท์</label>
                            <input type="text" name="phone" id="rvPhone" class="form-control"
                                   placeholder="08X-XXX-XXXX">
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-semibold">ที่อยู่</label>
                            <input type="text" name="address" id="rvAddress" class="form-control"
                                   placeholder="ที่อยู่สำหรับติดต่อ">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">เลขบัตรประชาชน</label>
                            <input type="text" name="id_card_number" id="rvIdCard" class="form-control"
                                   placeholder="X-XXXX-XXXXX-XX-X" maxlength="17">
                        </div>

                        <!-- Bank Info -->
                        <div class="col-12 mt-2">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-university me-1"></i>ข้อมูลธนาคาร (สำหรับจ่ายค่าตอบแทน)
                            </h6>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">ธนาคาร</label>
                            <select name="bank_name" id="rvBankName" class="form-select">
                                <option value="">-- เลือกธนาคาร --</option>
                                <option value="ธนาคารกรุงเทพ">ธนาคารกรุงเทพ</option>
                                <option value="ธนาคารกสิกรไทย">ธนาคารกสิกรไทย</option>
                                <option value="ธนาคารกรุงไทย">ธนาคารกรุงไทย</option>
                                <option value="ธนาคารไทยพาณิชย์">ธนาคารไทยพาณิชย์</option>
                                <option value="ธนาคารกรุงศรีอยุธยา">ธนาคารกรุงศรีอยุธยา</option>
                                <option value="ธนาคารออมสิน">ธนาคารออมสิน</option>
                                <option value="ธนาคารอาคารสงเคราะห์">ธนาคารอาคารสงเคราะห์</option>
                                <option value="ธนาคารทหารไทยธนชาต">ธนาคารทหารไทยธนชาต</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">เลขบัญชี</label>
                            <input type="text" name="bank_account" id="rvBankAccount" class="form-control"
                                   placeholder="XXX-X-XXXXX-X">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">สาขา</label>
                            <input type="text" name="bank_branch" id="rvBankBranch" class="form-control"
                                   placeholder="ชื่อสาขา">
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn text-white" style="background:#003B6D;">
                        <i class="fas fa-save me-1"></i>บันทึกข้อมูล
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {

    // DataTable (layout already loaded)
    $('#reviewersTable').DataTable({
        pageLength: 25,
        order: [[1, 'asc']],
        columnDefs: [{ orderable: false, targets: [8] }]
    });

    // Trigger ?action=create modal on load
    <?php if (($_GET['action'] ?? '') === 'create'): ?>
    new bootstrap.Modal(document.getElementById('reviewerModal')).show();
    <?php endif; ?>

    // Reset form + open for add
    $('#btnAddReviewer').on('click', function () {
        resetForm();
        $('#modalTitleText').text('เพิ่มผู้ทรงคุณวุฒิ');
        $('#reviewerForm').attr('action', '/research/reviewers/store');
    });

    // Edit reviewer
    $(document).on('click', '.btn-edit', function () {
        const r = JSON.parse($(this).attr('data-reviewer'));
        populateForm(r);
        $('#modalTitleText').text('แก้ไขข้อมูลผู้ทรงคุณวุฒิ');
        $('#reviewerForm').attr('action', '/research/reviewers/' + r.id + '/update');
        new bootstrap.Modal(document.getElementById('reviewerModal')).show();
    });

    // Toggle active
    $(document).on('click', '.btn-toggle', function () {
        const id     = $(this).data('id');
        const active = parseInt($(this).data('active'));
        const action = active ? 'ระงับการใช้งาน' : 'เปิดใช้งาน';
        const btn    = $(this);

        Swal.fire({
            title: action + '?',
            text: 'ต้องการ' + action + 'ผู้ทรงคุณวุฒิท่านนี้หรือไม่?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#003B6D',
            cancelButtonColor:  '#6c757d',
            confirmButtonText:  'ยืนยัน',
            cancelButtonText:   'ยกเลิก',
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('/research/reviewers/' + id + '/toggle', {
                    csrf_token: '<?= htmlspecialchars($csrfToken) ?>'
                }, function (res) {
                    if (res.success) {
                        const badge = $('#status-badge-' + id);
                        if (res.is_active) {
                            badge.text('ใช้งาน').removeClass('bg-secondary').addClass('bg-success');
                            btn.removeClass('btn-outline-success').addClass('btn-outline-warning');
                            btn.find('i').removeClass('fa-check').addClass('fa-ban');
                            btn.data('active', 1).attr('title', 'ระงับการใช้งาน');
                        } else {
                            badge.text('ไม่ใช้งาน').removeClass('bg-success').addClass('bg-secondary');
                            btn.removeClass('btn-outline-warning').addClass('btn-outline-success');
                            btn.find('i').removeClass('fa-ban').addClass('fa-check');
                            btn.data('active', 0).attr('title', 'เปิดใช้งาน');
                        }
                        Swal.fire({ icon: 'success', title: 'สำเร็จ', text: res.message, timer: 1500, showConfirmButton: false });
                    } else {
                        Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: res.message });
                    }
                }, 'json').fail(function () {
                    Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'ไม่สามารถเชื่อมต่อได้' });
                });
            }
        });
    });

    function resetForm() {
        document.getElementById('reviewerForm').reset();
    }

    function populateForm(r) {
        $('#rvTitle').val(r.title);
        $('#rvFirstName').val(r.first_name);
        $('#rvLastName').val(r.last_name);
        $('#rvExpertise').val(r.expertise);
        $('#rvPosition').val(r.position);
        $('#rvInstitution').val(r.institution);
        $('#rvEmail').val(r.email);
        $('#rvPhone').val(r.phone);
        $('#rvAddress').val(r.address);
        $('#rvIdCard').val(r.id_card_number);
        $('#rvBankName').val(r.bank_name);
        $('#rvBankAccount').val(r.bank_account);
        $('#rvBankBranch').val(r.bank_branch);
    }
});
</script>
