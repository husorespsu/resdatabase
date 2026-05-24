<?php
/**
 * View: settings/users.php
 * Variables: $users, $currentUser, $csrfToken, $pageTitle
 */
$currentId    = (int)($currentUser['id'] ?? 0);
$isSuperadmin = ($currentUser['role'] ?? '') === 'superadmin';

if (!function_exists('getInitials')) {
    function getInitials(string $name): string {
        $parts = explode(' ', trim($name));
        $ini   = '';
        foreach ($parts as $p) {
            $ini .= mb_substr($p, 0, 1);
            if (mb_strlen($ini) >= 2) break;
        }
        return mb_strtoupper($ini ?: 'U');
    }
}

$roleMap = [
    'superadmin' => ['label' => 'ผู้ดูแลระบบสูงสุด', 'color' => 'danger'],
    'admin'      => ['label' => 'ผู้ดูแลระบบ',        'color' => 'primary'],
    'executive'  => ['label' => 'ผู้บริหาร',           'color' => 'info'],
];
$relatedUserIds = $relatedUserIds ?? [];

// Stats
$totalActive = 0;
$cntSuper = $cntAdmin = 0;
foreach ($users as $u) {
    if ($u['is_active']) $totalActive++;
    if ($u['role'] === 'superadmin') $cntSuper++;
    if ($u['role'] === 'admin')      $cntAdmin++;
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:#003B6D;">
            <i class="fas fa-users me-2"></i><?= h($pageTitle ?? 'จัดการผู้ใช้งาน') ?>
        </h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">หน้าหลัก</a></li>
                <li class="breadcrumb-item">การตั้งค่า</li>
                <li class="breadcrumb-item active">จัดการผู้ใช้งาน</li>
            </ol>
        </nav>
    </div>
    <button type="button" class="btn text-white" style="background:#003B6D;"
            data-bs-toggle="modal" data-bs-target="#modalCreateUser">
        <i class="fas fa-user-plus me-1"></i> เพิ่มผู้ใช้งาน
    </button>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                    <i class="fas fa-users text-primary"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold text-primary"><?= count($users) ?></div>
                    <div class="small text-muted">ผู้ใช้ทั้งหมด</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                    <i class="fas fa-user-check text-success"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold text-success"><?= $totalActive ?></div>
                    <div class="small text-muted">ใช้งานอยู่</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                    <i class="fas fa-shield-alt text-danger"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold text-danger"><?= $cntSuper ?></div>
                    <div class="small text-muted">Superadmin</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                    <i class="fas fa-user-cog text-info"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold text-info"><?= $cntAdmin ?></div>
                    <div class="small text-muted">Admin</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold" style="color:#003B6D;">
            <i class="fas fa-table me-1"></i> รายชื่อผู้ใช้งาน
            <span class="badge bg-secondary ms-1"><?= count($users) ?></span>
        </span>
        <div class="d-flex gap-1">
            <button class="btn btn-sm btn-outline-secondary active" id="btnFilterAll">ทั้งหมด</button>
            <?php if ($isSuperadmin): ?>
            <button class="btn btn-sm btn-outline-danger"  data-role-filter="superadmin">Superadmin</button>
            <?php endif; ?>
            <button class="btn btn-sm btn-outline-primary" data-role-filter="admin">Admin</button>
            <button class="btn btn-sm btn-outline-info"    data-role-filter="executive">Executive</button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="tableUsers" class="table table-hover align-middle mb-0 w-100">
                <thead>
                    <tr style="background:#003B6D;color:#fff;">
                        <th class="ps-3 fw-semibold" style="width:5%"></th>
                        <th class="fw-semibold" style="width:22%">ชื่อ-นามสกุล</th>
                        <th class="fw-semibold" style="width:22%">อีเมล</th>
                        <th class="fw-semibold" style="width:13%">แผนก</th>
                        <th class="fw-semibold" style="width:15%">บทบาท</th>
                        <th class="fw-semibold text-center" style="width:8%">สถานะ</th>
                        <th class="fw-semibold" style="width:10%">เข้าสู่ระบบล่าสุด</th>
                        <th class="fw-semibold text-center" style="width:5%">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="8" class="text-center py-5 text-muted">
                        <i class="fas fa-users fa-2x d-block mb-2 opacity-50"></i>ไม่พบผู้ใช้งาน
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($users as $user):
                        $isSelf   = ($user['id'] == $currentId);
                        $rInfo    = $roleMap[$user['role']] ?? ['label' => $user['role'], 'color' => 'secondary'];
                        $initials = getInitials($user['name'] ?? '');
                        $lastLogin = !empty($user['last_login'])
                            ? date('d/m/Y H:i', strtotime($user['last_login']))
                            : 'ยังไม่เคยเข้า';
                        // Admin cannot manage superadmin
                        $canManage = !$isSelf && ($isSuperadmin || $user['role'] !== 'superadmin');
                    ?>
                    <tr class="<?= $isSelf ? 'table-active' : '' ?>">
                        <td class="ps-3">
                            <?php if (!empty($user['avatar'])): ?>
                                <img src="<?= h($user['avatar']) ?>" class="rounded-circle" width="38" height="38" style="object-fit:cover;" alt="">
                            <?php else: ?>
                                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white small flex-shrink-0"
                                     style="width:38px;height:38px;background:#003B6D;"><?= $initials ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-semibold small">
                                <?= h($user['name'] ?? '-') ?>
                                <?php if ($isSelf): ?>
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.6rem;">ฉัน</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($user['username'])): ?>
                                <div class="text-muted" style="font-size:.75rem;"><i class="fas fa-at me-1"></i><?= h($user['username']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted">
                            <a href="mailto:<?= h($user['email']) ?>" class="text-muted text-decoration-none">
                                <?= h($user['email']) ?>
                            </a>
                        </td>
                        <td class="small text-muted"><?= h($user['department'] ?? '-') ?></td>
                        <td>
                            <?php if ($canManage): ?>
                                <select class="form-select form-select-sm role-select"
                                        data-user-id="<?= (int)$user['id'] ?>"
                                        data-current-role="<?= h($user['role']) ?>"
                                        style="min-width:140px;">
                                    <?php foreach ($roleMap as $rk => $rv):
                                        // Admin cannot set superadmin
                                        if (!$isSuperadmin && $rk === 'superadmin') continue;
                                    ?>
                                        <option value="<?= $rk ?>" <?= $user['role'] === $rk ? 'selected' : '' ?>>
                                            <?= $rv['label'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <span class="badge bg-<?= $rInfo['color'] ?>"><?= $rInfo['label'] ?></span>
                                <?php if ($isSelf): ?><div class="text-muted" style="font-size:.72rem;">ไม่สามารถเปลี่ยนของตนเองได้</div><?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-center status-cell" data-user-id="<?= (int)$user['id'] ?>">
                            <?php if ($user['is_active']): ?>
                                <span class="badge bg-success">ใช้งาน</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">ปิดใช้งาน</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted"><?= $lastLogin ?></td>
                        <td class="text-center">
                            <?php if ($canManage): ?>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <button class="dropdown-item btn-toggle-user"
                                                    data-user-id="<?= (int)$user['id'] ?>"
                                                    data-user-name="<?= h($user['name'] ?? '', ENT_QUOTES) ?>"
                                                    data-is-active="<?= (int)$user['is_active'] ?>">
                                                <i class="fas fa-<?= $user['is_active'] ? 'user-slash' : 'user-check' ?> me-2 text-<?= $user['is_active'] ? 'warning' : 'success' ?>"></i>
                                                <?= $user['is_active'] ? 'ปิดใช้งาน' : 'เปิดใช้งาน' ?>
                                            </button>
                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <?php $hasRelated = in_array((int)$user['id'], $relatedUserIds, true); ?>
                                            <button class="dropdown-item <?= $hasRelated ? 'text-muted' : 'text-danger' ?> btn-delete-user"
                                                    data-user-id="<?= (int)$user['id'] ?>"
                                                    data-user-name="<?= h($user['name'] ?? '', ENT_QUOTES) ?>"
                                                    data-has-related="<?= $hasRelated ? '1' : '0' ?>"
                                                    title="<?= $hasRelated ? 'มีข้อมูลเกี่ยวข้อง — ปิดใช้งานแทน' : 'ลบบัญชีนี้ออกจากระบบ' ?>">
                                                <i class="fas fa-<?= $hasRelated ? 'ban' : 'trash' ?> me-2"></i>
                                                <?= $hasRelated ? 'ลบไม่ได้ (มีข้อมูล)' : 'ลบผู้ใช้' ?>
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            <?php else: ?>
                                <i class="fas fa-lock text-muted"></i>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Hidden delete forms -->
<div id="deleteForms"></div>

<!-- ── Modal: เพิ่มผู้ใช้ ─────────────────────────────── -->
<div class="modal fade" id="modalCreateUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/settings/users/store">
                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                <div class="modal-header text-white" style="background:#003B6D;">
                    <h6 class="modal-title fw-semibold">
                        <i class="fas fa-user-plus me-2"></i>เพิ่มผู้ใช้งานใหม่
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="เช่น สมชาย ใจดี">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">อีเมล <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required placeholder="example@psu.ac.th">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">ชื่อผู้ใช้ (Username)</label>
                            <input type="text" name="username" class="form-control" placeholder="สำหรับ login ด้วย password">
                            <div class="form-text">เว้นว่างถ้าใช้ Google Login เท่านั้น</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">รหัสผ่าน</label>
                            <input type="password" name="password" class="form-control" placeholder="ใส่ถ้าต้องการ login ด้วย password">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">บทบาท <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <?php if ($isSuperadmin): ?>
                                <option value="superadmin">ผู้ดูแลระบบสูงสุด (Superadmin)</option>
                                <?php endif; ?>
                                <option value="admin">ผู้ดูแลระบบ (Admin)</option>
                                <option value="executive" selected>ผู้บริหาร (Executive)</option>
                            </select>
                        </div>
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
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = '<?= h($csrfToken) ?>';

    // DataTable
    var dt;
    if (typeof $.fn.DataTable !== 'undefined') {
        dt = $('#tableUsers').DataTable({
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/th.json' },
            pageLength: 25,
            order: [[4, 'asc'], [1, 'asc']],
            columnDefs: [
                { orderable: false, targets: [0, 7] },
                { searchable: false, targets: [0, 5, 7] },
            ],
        });

        document.getElementById('btnFilterAll').addEventListener('click', function () {
            dt.column(4).search('').draw();
        });
        document.querySelectorAll('[data-role-filter]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                dt.column(4).search(this.dataset.roleFilter, true, false).draw();
            });
        });
    }

    // Role change (AJAX)
    document.querySelectorAll('.role-select').forEach(function (sel) {
        sel.addEventListener('change', function () {
            const userId      = this.dataset.userId;
            const currentRole = this.dataset.currentRole;
            const newRole     = this.value;
            const select      = this;
            const roleLabels  = { superadmin:'ผู้ดูแลระบบสูงสุด', admin:'ผู้ดูแลระบบ', executive:'ผู้บริหาร' };

            Swal.fire({
                title: 'ยืนยันการเปลี่ยนบทบาท',
                html: `เปลี่ยนเป็น <strong>${roleLabels[newRole] || newRole}</strong> ใช่หรือไม่?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#003B6D',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก',
            }).then(function (r) {
                if (!r.isConfirmed) { select.value = currentRole; return; }

                fetch('<?= BASE_URL ?>/settings/users/' + userId + '/role', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ csrf_token: csrfToken, role: newRole }),
                })
                .then(r => r.json())
                .then(function (data) {
                    if (data.success) {
                        select.dataset.currentRole = newRole;
                        showToast(data.message, 'success');
                    } else {
                        select.value = currentRole;
                        showToast(data.message || 'เกิดข้อผิดพลาด', 'danger');
                    }
                })
                .catch(function () { select.value = currentRole; showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'danger'); });
            });
        });
    });

    // Toggle active (AJAX)
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-toggle-user');
        if (!btn) return;

        const userId   = btn.dataset.userId;
        const userName = btn.dataset.userName;
        const isActive = btn.dataset.isActive === '1';

        Swal.fire({
            title: `ยืนยันการ${isActive ? 'ปิด' : 'เปิด'}ใช้งาน`,
            html: `ต้องการ<strong>${isActive ? 'ปิด' : 'เปิด'}ใช้งาน</strong>บัญชี <strong>${userName}</strong>?`,
            icon: isActive ? 'warning' : 'question',
            showCancelButton: true,
            confirmButtonColor: isActive ? '#dc3545' : '#003B6D',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก',
        }).then(function (r) {
            if (!r.isConfirmed) return;

            fetch('<?= BASE_URL ?>/settings/users/' + userId + '/toggle', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ csrf_token: csrfToken }),
            })
            .then(r => r.json())
            .then(function (data) {
                if (data.success) {
                    // Update status badge
                    const cell = document.querySelector('.status-cell[data-user-id="' + userId + '"]');
                    if (cell) {
                        cell.innerHTML = data.is_active
                            ? '<span class="badge bg-success">ใช้งาน</span>'
                            : '<span class="badge bg-secondary">ปิดใช้งาน</span>';
                    }
                    // Update button data
                    btn.dataset.isActive = data.is_active ? '1' : '0';
                    const icon = btn.querySelector('i');
                    if (icon) icon.className = `fas fa-${data.is_active ? 'user-slash' : 'user-check'} me-2 text-${data.is_active ? 'warning' : 'success'}`;
                    btn.innerHTML = (data.is_active
                        ? '<i class="fas fa-user-slash me-2 text-warning"></i>ปิดใช้งาน'
                        : '<i class="fas fa-user-check me-2 text-success"></i>เปิดใช้งาน');
                    btn.dataset.isActive = data.is_active ? '1' : '0';
                    showToast(data.message, 'success');
                } else {
                    showToast(data.message || 'เกิดข้อผิดพลาด', 'danger');
                }
            })
            .catch(function () { showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'danger'); });
        });
    });

    // Delete user
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-delete-user');
        if (!btn) return;

        const userId     = btn.dataset.userId;
        const userName   = btn.dataset.userName;
        const hasRelated = btn.dataset.hasRelated === '1';

        if (hasRelated) {
            Swal.fire({
                title: 'ไม่สามารถลบได้',
                html: `บัญชี <strong>${userName}</strong> มีข้อมูลที่เกี่ยวข้องในระบบ<br>
                       <small class="text-muted">หากต้องการระงับการใช้งาน กรุณาใช้ปุ่ม "ปิดใช้งาน" แทน</small>`,
                icon: 'info',
                confirmButtonColor: '#003B6D',
                confirmButtonText: 'เข้าใจแล้ว',
            });
            return;
        }

        Swal.fire({
            title: 'ยืนยันการลบผู้ใช้',
            html: `ต้องการลบบัญชี <strong>${userName}</strong> ออกจากระบบ?<br>
                   <small class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>การดำเนินการนี้ไม่สามารถย้อนกลับได้</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash me-1"></i>ลบ',
            cancelButtonText: 'ยกเลิก',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= BASE_URL ?>/settings/users/' + userId + '/delete';
            form.innerHTML = `<input type="hidden" name="csrf_token" value="${csrfToken}">`;
            document.getElementById('deleteForms').appendChild(form);
            form.submit();
        });
    });

    function showToast(msg, type) {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
        const t = document.createElement('div');
        t.className = `toast align-items-center text-bg-${type} border-0 show`;
        t.innerHTML = `<div class="d-flex"><div class="toast-body">${msg}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
        container.appendChild(t);
        setTimeout(() => t.remove(), 4000);
    }
});
</script>
