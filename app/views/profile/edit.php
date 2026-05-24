<?php
/**
 * View: profile/edit.php
 * @var array  $user
 * @var string $csrfToken
 */

$isGoogleAccount = !empty($user['google_id']);
$hasPassword     = !empty($user['password']);
$roleLabels = [
    'superadmin' => 'ผู้ดูแลระบบสูงสุด',
    'admin'      => 'ผู้ดูแลระบบ',
    'executive'  => 'ผู้บริหาร',
    'user'       => 'ผู้ใช้งาน',
];
$roleColors = [
    'superadmin' => 'danger',
    'admin'      => 'primary',
    'executive'  => 'success',
    'user'       => 'secondary',
];
$roleLabel = $roleLabels[$user['role'] ?? 'user'] ?? 'ผู้ใช้งาน';
$roleColor = $roleColors[$user['role'] ?? 'user'] ?? 'secondary';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">หน้าหลัก</a></li>
        <li class="breadcrumb-item active">โปรไฟล์ของฉัน</li>
    </ol>
</nav>

<div class="row g-4 justify-content-center">

    <!-- Left: Avatar + Summary -->
    <div class="col-lg-3 col-md-4">

        <!-- Avatar Card -->
        <div class="card border-0 shadow-sm text-center mb-3">
            <div class="card-body py-4">
                <div class="position-relative d-inline-block mb-3" id="avatarWrapper">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="<?= h($user['avatar']) ?>" alt="avatar"
                             id="avatarPreview"
                             class="rounded-circle border border-3"
                             style="width:96px;height:96px;object-fit:cover;border-color:#003B6D!important;"
                             onerror="this.style.display='none';document.getElementById('avatarInitial').style.display='flex';">
                        <div id="avatarInitial"
                             class="rounded-circle d-none align-items-center justify-content-center fw-bold fs-3 text-white"
                             style="width:96px;height:96px;background:#003B6D;">
                            <?= mb_strtoupper(mb_substr($user['name'] ?? 'U', 0, 1)) ?>
                        </div>
                    <?php else: ?>
                        <img id="avatarPreview" src="" alt="" class="rounded-circle border border-3 d-none"
                             style="width:96px;height:96px;object-fit:cover;border-color:#003B6D!important;">
                        <div id="avatarInitial"
                             class="rounded-circle d-flex align-items-center justify-content-center fw-bold fs-3 text-white"
                             style="width:96px;height:96px;background:#003B6D;">
                            <?= mb_strtoupper(mb_substr($user['name'] ?? 'U', 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <!-- Camera overlay -->
                    <label for="avatarInput"
                           class="position-absolute bottom-0 end-0 btn btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center"
                           style="width:30px;height:30px;background:#003B6D;color:#fff;cursor:pointer;"
                           title="เปลี่ยนรูปโปรไฟล์">
                        <i class="fas fa-camera" style="font-size:.7rem;"></i>
                    </label>
                </div>

                <h6 class="fw-bold mb-1" style="color:#003B6D;"><?= h($user['name'] ?? '') ?></h6>
                <span class="badge bg-<?= $roleColor ?>"><?= $roleLabel ?></span>
                <?php if ($isGoogleAccount): ?>
                <div class="mt-2">
                    <span class="badge bg-light text-dark border">
                        <i class="fab fa-google me-1 text-danger"></i>Google Account
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Account Info -->
        <div class="card border-0 shadow-sm">
            <div class="card-header py-2" style="background:#003B6D;">
                <h6 class="mb-0 text-white fw-bold small">
                    <i class="fas fa-info-circle me-1"></i>ข้อมูลบัญชี
                </h6>
            </div>
            <div class="card-body small">
                <div class="mb-2">
                    <div class="text-muted">อีเมล</div>
                    <div class="fw-semibold text-truncate"><?= h($user['email'] ?? '-') ?></div>
                </div>
                <?php if (!empty($user['username'])): ?>
                <div class="mb-2">
                    <div class="text-muted">ชื่อผู้ใช้</div>
                    <div class="fw-semibold"><?= h($user['username']) ?></div>
                </div>
                <?php endif; ?>
                <div class="mb-2">
                    <div class="text-muted">สิทธิ์การใช้งาน</div>
                    <span class="badge bg-<?= $roleColor ?>"><?= $roleLabel ?></span>
                </div>
                <div class="mb-0">
                    <div class="text-muted">เข้าสู่ระบบล่าสุด</div>
                    <div class="fw-semibold">
                        <?= !empty($user['last_login'])
                            ? date('d/m/Y H:i', strtotime($user['last_login']))
                            : '-' ?>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Right: Forms -->
    <div class="col-lg-7 col-md-8">

        <!-- Profile Info Form -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header py-3" style="background:#003B6D;border-radius:.75rem .75rem 0 0;">
                <h5 class="mb-0 text-white fw-bold">
                    <i class="fas fa-user-edit me-2"></i>ข้อมูลส่วนตัว
                </h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="<?= BASE_URL ?>/profile/update"
                      enctype="multipart/form-data" id="profileForm">
                    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                    <!-- Avatar hidden input (tied to the left-panel label) -->
                    <input type="file" id="avatarInput" name="avatar"
                           accept="image/jpeg,image/png,image/gif,image/webp"
                           class="d-none">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                ชื่อ-นามสกุล <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control"
                                   value="<?= h($user['name'] ?? '') ?>"
                                   placeholder="ชื่อ-นามสกุล" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">อีเมล</label>
                            <input type="email" class="form-control bg-light"
                                   value="<?= h($user['email'] ?? '') ?>" disabled readonly>
                            <div class="form-text text-muted">
                                <i class="fas fa-lock me-1"></i>ไม่สามารถเปลี่ยนอีเมลได้
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">หมายเลขโทรศัพท์</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="tel" name="phone" class="form-control"
                                       value="<?= h($user['phone'] ?? '') ?>"
                                       placeholder="เช่น 081-234-5678"
                                       pattern="[0-9\-\+\(\) ]{9,20}">
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">หน่วยงาน / คณะ</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-building"></i></span>
                                <input type="text" name="department" class="form-control"
                                       value="<?= h($user['department'] ?? '') ?>"
                                       placeholder="ชื่อหน่วยงาน / คณะ / สำนักงาน">
                            </div>
                        </div>

                        <!-- Avatar file name display -->
                        <div class="col-12 d-none" id="avatarFileRow">
                            <div class="alert alert-info py-2 mb-0 small d-flex align-items-center gap-2">
                                <i class="fas fa-image"></i>
                                <span id="avatarFileName"></span>
                                <button type="button" class="btn-close ms-auto" id="clearAvatar" style="font-size:.65rem;"></button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn text-white fw-semibold px-4" style="background:#003B6D;">
                            <i class="fas fa-save me-1"></i>บันทึกข้อมูล
                        </button>
                        <a href="<?= BASE_URL ?>/" class="btn btn-outline-secondary">ยกเลิก</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change Password Form -->
        <?php if (!$isGoogleAccount): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header py-3" style="background:#003B6D;border-radius:.75rem .75rem 0 0;">
                <h5 class="mb-0 text-white fw-bold">
                    <i class="fas fa-lock me-2"></i>เปลี่ยนรหัสผ่าน
                </h5>
            </div>
            <div class="card-body p-4">
                <?php if (!$hasPassword): ?>
                <div class="alert alert-warning d-flex align-items-center gap-2">
                    <i class="fas fa-exclamation-triangle flex-shrink-0"></i>
                    <div>
                        บัญชีนี้ยังไม่ได้ตั้งรหัสผ่าน กรุณาติดต่อผู้ดูแลระบบเพื่อตั้งรหัสผ่าน
                    </div>
                </div>
                <?php else: ?>
                <form method="POST" action="<?= BASE_URL ?>/profile/change-password" id="pwForm">
                    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">รหัสผ่านปัจจุบัน <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="current_password" id="currentPw"
                                       class="form-control" placeholder="รหัสผ่านปัจจุบัน" required
                                       autocomplete="current-password">
                                <button type="button" class="btn btn-outline-secondary toggle-pw" data-target="currentPw">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">รหัสผ่านใหม่ <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="new_password" id="newPw"
                                       class="form-control" placeholder="อย่างน้อย 8 ตัวอักษร"
                                       minlength="8" required autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary toggle-pw" data-target="newPw">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <!-- Strength meter -->
                            <div class="progress mt-2" style="height:4px;" id="pwStrengthBar">
                                <div class="progress-bar" id="pwStrengthFill" style="width:0%; transition:.3s;"></div>
                            </div>
                            <div class="form-text" id="pwStrengthText"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">ยืนยันรหัสผ่านใหม่ <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="confirm_password" id="confirmPw"
                                       class="form-control" placeholder="ยืนยันรหัสผ่านใหม่"
                                       required autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary toggle-pw" data-target="confirmPw">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text text-danger d-none" id="pwMismatch">
                                <i class="fas fa-times-circle me-1"></i>รหัสผ่านไม่ตรงกัน
                            </div>
                            <div class="form-text text-success d-none" id="pwMatch">
                                <i class="fas fa-check-circle me-1"></i>รหัสผ่านตรงกัน
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-warning fw-semibold px-4">
                            <i class="fas fa-key me-1"></i>เปลี่ยนรหัสผ่าน
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3 d-flex align-items-center gap-3 text-muted">
                <i class="fab fa-google fa-lg text-danger flex-shrink-0"></i>
                <span class="small">บัญชี Google ไม่สามารถเปลี่ยนรหัสผ่านผ่านระบบนี้ได้ กรุณาจัดการรหัสผ่านผ่าน Google Account</span>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
$(document).ready(function () {

    // ── Avatar preview ───────────────────────────────────────────
    $('#avatarInput').on('change', function () {
        const file = this.files[0];
        if (!file) return;

        if (file.size > 2 * 1024 * 1024) {
            alert('ขนาดไฟล์ต้องไม่เกิน 2 MB');
            this.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            $('#avatarPreview').attr('src', e.target.result).removeClass('d-none');
            $('#avatarInitial').addClass('d-none');
        };
        reader.readAsDataURL(file);

        $('#avatarFileName').text(file.name);
        $('#avatarFileRow').removeClass('d-none');
    });

    $('#clearAvatar').on('click', function () {
        $('#avatarInput').val('');
        $('#avatarFileRow').addClass('d-none');
        // Restore original avatar
        const origSrc = '<?= h($user['avatar'] ?? '') ?>';
        if (origSrc) {
            $('#avatarPreview').attr('src', origSrc).removeClass('d-none');
            $('#avatarInitial').addClass('d-none');
        } else {
            $('#avatarPreview').addClass('d-none');
            $('#avatarInitial').removeClass('d-none');
        }
    });

    // ── Toggle password visibility ───────────────────────────────
    $(document).on('click', '.toggle-pw', function () {
        const targetId = $(this).data('target');
        const input    = document.getElementById(targetId);
        const icon     = $(this).find('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // ── Password strength meter ──────────────────────────────────
    $('#newPw').on('input', function () {
        const pw    = $(this).val();
        const fill  = $('#pwStrengthFill');
        const text  = $('#pwStrengthText');
        let score   = 0;
        if (pw.length >= 8)  score++;
        if (/[A-Z]/.test(pw)) score++;
        if (/[0-9]/.test(pw)) score++;
        if (/[^A-Za-z0-9]/.test(pw)) score++;

        const levels = [
            { pct:  0,  cls: '',        label: '' },
            { pct: 25,  cls: 'bg-danger',  label: '<span class="text-danger">อ่อนมาก</span>' },
            { pct: 50,  cls: 'bg-warning', label: '<span class="text-warning">อ่อน</span>' },
            { pct: 75,  cls: 'bg-info',    label: '<span class="text-info">ปานกลาง</span>' },
            { pct: 100, cls: 'bg-success', label: '<span class="text-success">แข็งแกร่ง</span>' },
        ];
        const lvl = levels[pw.length === 0 ? 0 : score] || levels[1];
        fill.css('width', lvl.pct + '%').attr('class', 'progress-bar ' + lvl.cls);
        text.html(lvl.label);
        checkPwMatch();
    });

    // ── Password match check ─────────────────────────────────────
    $('#confirmPw').on('input', checkPwMatch);
    function checkPwMatch() {
        const nw = $('#newPw').val();
        const cf = $('#confirmPw').val();
        if (!cf) { $('#pwMismatch, #pwMatch').addClass('d-none'); return; }
        if (nw === cf) {
            $('#pwMismatch').addClass('d-none');
            $('#pwMatch').removeClass('d-none');
        } else {
            $('#pwMatch').addClass('d-none');
            $('#pwMismatch').removeClass('d-none');
        }
    }

    // ── Prevent PW submit if mismatch ───────────────────────────
    $('#pwForm').on('submit', function (e) {
        const nw = $('#newPw').val();
        const cf = $('#confirmPw').val();
        if (nw !== cf) {
            e.preventDefault();
            Swal.fire({ icon: 'error', title: 'รหัสผ่านไม่ตรงกัน', text: 'กรุณาตรวจสอบรหัสผ่านใหม่และยืนยันรหัสผ่าน', confirmButtonColor: '#003B6D' });
        }
    });

});
</script>
