<?php
/**
 * PSU Research - Notifications List View
 * @var array  $notifications   Paginated notifications for current user
 * @var int    $totalCount      Total notification count
 * @var int    $unreadCount     Unread notification count
 * @var int    $currentPage     Current page
 * @var int    $totalPages      Total pages
 * @var string $title
 */
$csrfToken   = $_SESSION['csrf_token'] ?? '';
$perPage     = 15;

// Icon map by notification type
$typeIcons = [
    'review_due_7day'  => ['icon' => 'bi-clock',                  'bg' => 'bg-warning-subtle', 'text' => 'text-warning'],
    'review_due_3day'  => ['icon' => 'bi-exclamation-triangle',   'bg' => 'bg-orange-subtle',  'text' => 'text-orange'],
    'review_overdue'   => ['icon' => 'bi-exclamation-circle-fill','bg' => 'bg-danger-subtle',  'text' => 'text-danger'],
    'proposal_status'  => ['icon' => 'bi-file-earmark-check',     'bg' => 'bg-info-subtle',    'text' => 'text-info'],
    'payment_received' => ['icon' => 'bi-cash-stack',             'bg' => 'bg-success-subtle', 'text' => 'text-success'],
    'review_assigned'  => ['icon' => 'bi-person-plus',            'bg' => 'bg-primary-subtle', 'text' => 'text-primary'],
    'project_update'   => ['icon' => 'bi-kanban',                 'bg' => 'bg-info-subtle',    'text' => 'text-info'],
    'system'           => ['icon' => 'bi-gear-fill',              'bg' => 'bg-secondary-subtle','text'=> 'text-secondary'],
];
$defaultIcon = ['icon' => 'bi-bell', 'bg' => 'bg-primary-subtle', 'text' => 'text-primary'];

function getRelativeTime(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)       return 'เมื่อกี้';
    if ($diff < 3600)     return intdiv($diff, 60) . ' นาทีที่แล้ว';
    if ($diff < 86400)    return intdiv($diff, 3600) . ' ชั่วโมงที่แล้ว';
    if ($diff < 604800)   return intdiv($diff, 86400) . ' วันที่แล้ว';
    if ($diff < 2592000)  return intdiv($diff, 604800) . ' สัปดาห์ที่แล้ว';
    if ($diff < 31536000) return intdiv($diff, 2592000) . ' เดือนที่แล้ว';
    return intdiv($diff, 31536000) . ' ปีที่แล้ว';
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-psu mb-1">
            <i class="bi bi-bell-fill me-2"></i><?= htmlspecialchars($title) ?>
            <?php if (!empty($unreadCount) && $unreadCount > 0): ?>
                <span class="badge bg-danger ms-2"><?= $unreadCount > 99 ? '99+' : $unreadCount ?></span>
            <?php endif; ?>
        </h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 breadcrumb-psu">
                <li class="breadcrumb-item"><a href="/research/dashboard">หน้าหลัก</a></li>
                <li class="breadcrumb-item active">การแจ้งเตือน</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <?php if (!empty($unreadCount) && $unreadCount > 0): ?>
            <form method="POST" action="/research/notifications/read-all" id="formReadAll">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <button type="submit" class="btn btn-outline-psu">
                    <i class="bi bi-check2-all me-1"></i> อ่านทั้งหมด
                </button>
            </form>
        <?php endif; ?>
        <a href="/research/notifications/clear-read" class="btn btn-outline-secondary"
            onclick="return confirm('ต้องการลบการแจ้งเตือนที่อ่านแล้วทั้งหมด?')">
            <i class="bi bi-trash me-1"></i> ลบที่อ่านแล้ว
        </a>
    </div>
</div>

<!-- Filter Tabs -->
<ul class="nav nav-tabs mb-3" id="notifTabs">
    <li class="nav-item">
        <a class="nav-link <?= !isset($_GET['filter']) || $_GET['filter'] === 'all' ? 'active' : '' ?>"
            href="/research/notifications">
            ทั้งหมด
            <span class="badge bg-secondary ms-1"><?= $totalCount ?? 0 ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= ($_GET['filter'] ?? '') === 'unread' ? 'active' : '' ?>"
            href="/research/notifications?filter=unread">
            ยังไม่ได้อ่าน
            <?php if (!empty($unreadCount) && $unreadCount > 0): ?>
                <span class="badge bg-danger ms-1"><?= $unreadCount ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= ($_GET['filter'] ?? '') === 'read' ? 'active' : '' ?>"
            href="/research/notifications?filter=read">
            อ่านแล้ว
        </a>
    </li>
</ul>

<!-- Notification List -->
<div class="card card-psu shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($notifications)): ?>
            <!-- Empty State -->
            <div class="text-center py-5 px-4">
                <div class="mb-3">
                    <i class="bi bi-bell-slash text-muted" style="font-size: 3.5rem; opacity: 0.4;"></i>
                </div>
                <h5 class="text-muted fw-semibold">ไม่มีการแจ้งเตือน</h5>
                <p class="text-muted small mb-0">
                    <?php if (($_GET['filter'] ?? '') === 'unread'): ?>
                        คุณได้อ่านการแจ้งเตือนทั้งหมดแล้ว
                    <?php elseif (($_GET['filter'] ?? '') === 'read'): ?>
                        ยังไม่มีการแจ้งเตือนที่อ่านแล้ว
                    <?php else: ?>
                        ยังไม่มีการแจ้งเตือนใด ๆ ในระบบ
                    <?php endif; ?>
                </p>
                <a href="/research/dashboard" class="btn btn-psu-primary mt-3">
                    <i class="bi bi-house me-1"></i> กลับหน้าหลัก
                </a>
            </div>
        <?php else: ?>
            <ul class="list-group list-group-flush notification-list">
                <?php foreach ($notifications as $notif): ?>
                    <?php
                    $iconInfo   = $typeIcons[$notif['type']] ?? $defaultIcon;
                    $isUnread   = !$notif['is_read'];
                    $relTime    = getRelativeTime($notif['created_at']);
                    $fullDate   = date('d/m/Y H:i', strtotime($notif['created_at']));
                    $hasUrl     = !empty($notif['related_url']);
                    ?>
                    <li class="list-group-item list-group-item-action notification-item p-0
                        <?= $isUnread ? 'notification-unread' : 'notification-read' ?>">
                        <a href="<?= $hasUrl ? htmlspecialchars($notif['related_url']) : '#' ?>"
                            class="d-flex align-items-start gap-3 p-3 text-decoration-none text-dark notification-link"
                            data-id="<?= $notif['id'] ?>"
                            data-unread="<?= $isUnread ? '1' : '0' ?>">

                            <!-- Icon -->
                            <div class="notification-icon-wrap flex-shrink-0">
                                <div class="notification-icon-circle <?= $iconInfo['bg'] ?>">
                                    <i class="bi <?= $iconInfo['icon'] ?> <?= $iconInfo['text'] ?>"></i>
                                </div>
                                <?php if ($isUnread): ?>
                                    <span class="unread-dot"></span>
                                <?php endif; ?>
                            </div>

                            <!-- Content -->
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="notification-title fw-semibold <?= $isUnread ? 'text-dark' : 'text-muted' ?>">
                                        <?= htmlspecialchars($notif['title']) ?>
                                    </div>
                                    <div class="notification-time text-muted small flex-shrink-0 ms-3" title="<?= $fullDate ?>">
                                        <i class="bi bi-clock me-1"></i><?= $relTime ?>
                                    </div>
                                </div>
                                <div class="notification-message text-muted small mt-1">
                                    <?= htmlspecialchars($notif['message']) ?>
                                </div>
                                <div class="d-flex align-items-center gap-2 mt-2">
                                    <span class="badge notification-type-badge <?= $iconInfo['bg'] ?> <?= $iconInfo['text'] ?>" style="font-size:0.65rem;">
                                        <?php
                                        $typeLabels = [
                                            'review_due_7day'  => 'แจ้งเตือน 7 วัน',
                                            'review_due_3day'  => 'แจ้งเตือน 3 วัน',
                                            'review_overdue'   => 'เลยกำหนด',
                                            'proposal_status'  => 'สถานะข้อเสนอ',
                                            'payment_received' => 'การเงิน',
                                            'review_assigned'  => 'มอบหมายงาน',
                                            'project_update'   => 'อัปเดตโครงการ',
                                            'system'           => 'ระบบ',
                                        ];
                                        echo $typeLabels[$notif['type']] ?? htmlspecialchars($notif['type']);
                                        ?>
                                    </span>
                                    <?php if ($hasUrl): ?>
                                        <span class="text-muted small">
                                            <i class="bi bi-box-arrow-up-right me-1"></i>คลิกเพื่อดูรายละเอียด
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($isUnread): ?>
                                        <span class="badge bg-primary ms-1" style="font-size:0.6rem;">ใหม่</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Mark read button (shown on hover) -->
                            <?php if ($isUnread): ?>
                                <div class="flex-shrink-0 mark-read-wrap">
                                    <button type="button"
                                        class="btn btn-sm btn-outline-secondary btn-mark-read"
                                        data-id="<?= $notif['id'] ?>"
                                        title="ทำเครื่องหมายว่าอ่านแล้ว"
                                        onclick="event.preventDefault(); event.stopPropagation(); markAsRead(<?= $notif['id'] ?>, this);">
                                        <i class="bi bi-check2"></i>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- Pagination -->
            <?php if (!empty($totalPages) && $totalPages > 1): ?>
                <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top bg-light">
                    <div class="text-muted small">
                        หน้า <?= $currentPage ?> จาก <?= $totalPages ?> หน้า
                        (<?= $totalCount ?> รายการทั้งหมด)
                    </div>
                    <nav aria-label="Notification pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <!-- First -->
                            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" title="หน้าแรก">
                                    <i class="bi bi-chevron-double-left"></i>
                                </a>
                            </li>
                            <!-- Prev -->
                            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage - 1])) ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <!-- Pages -->
                            <?php
                            $start = max(1, $currentPage - 2);
                            $end   = min($totalPages, $currentPage + 2);
                            if ($start > 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif;
                            for ($p = $start; $p <= $end; $p++):
                            ?>
                                <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
                                </li>
                            <?php endfor;
                            if ($end < $totalPages): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif;
                            ?>
                            <!-- Next -->
                            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage + 1])) ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                            <!-- Last -->
                            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>" title="หน้าสุดท้าย">
                                    <i class="bi bi-chevron-double-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
const csrfToken = '<?= htmlspecialchars($csrfToken) ?>';

// Click notification link → mark as read then navigate
document.querySelectorAll('.notification-link').forEach(function (link) {
    link.addEventListener('click', function (e) {
        const id      = this.dataset.id;
        const isUnread = this.dataset.unread === '1';
        const href    = this.href;

        if (!isUnread) return; // already read, let it navigate normally

        e.preventDefault();

        fetch('/research/notifications/' + id + '/read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new URLSearchParams({ csrf_token: csrfToken }),
        })
        .then(function () {
            if (href && href !== '#' && href !== window.location.href) {
                window.location.href = href;
            } else {
                // Mark visually as read
                markItemRead(link.closest('.notification-item'), id);
            }
        })
        .catch(function () {
            if (href && href !== '#') window.location.href = href;
        });
    });
});

// Mark single as read via button
function markAsRead(id, btn) {
    fetch('/research/notifications/' + id + '/read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: new URLSearchParams({ csrf_token: csrfToken }),
    })
    .then(r => r.json())
    .then(function (data) {
        if (data.success) {
            const item = btn.closest('.notification-item');
            markItemRead(item, id);
            updateBadgeCount(-1);
        }
    });
}

function markItemRead(item, id) {
    if (!item) return;
    item.classList.remove('notification-unread');
    item.classList.add('notification-read');
    const dot  = item.querySelector('.unread-dot');
    const wrap = item.querySelector('.mark-read-wrap');
    const badge = item.querySelector('.badge.bg-primary');
    if (dot)   dot.remove();
    if (wrap)  wrap.remove();
    if (badge) badge.remove();
    const link = item.querySelector('.notification-link');
    if (link) link.dataset.unread = '0';
    const title = item.querySelector('.notification-title');
    if (title) { title.classList.remove('text-dark'); title.classList.add('text-muted'); }
}

function updateBadgeCount(delta) {
    const badge = document.querySelector('.notification-badge, #notificationBadge');
    if (!badge) return;
    const current = parseInt(badge.textContent) || 0;
    const newVal  = Math.max(0, current + delta);
    if (newVal === 0) {
        badge.style.display = 'none';
    } else {
        badge.textContent   = newVal > 99 ? '99+' : newVal;
        badge.style.display = '';
    }
}
</script>

<style>
/* Notification list styles */
.notification-item {
    transition: background 0.15s ease;
    border-left: 3px solid transparent !important;
}
.notification-unread {
    background-color: #EBF3FB !important;
    border-left-color: #0066CC !important;
}
.notification-read {
    background-color: #fff;
}
.notification-item:hover {
    background-color: #f0f7ff !important;
}
.notification-unread:hover {
    background-color: #dbeafe !important;
}
.notification-icon-wrap {
    position: relative;
    flex-shrink: 0;
}
.notification-icon-circle {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}
.unread-dot {
    position: absolute;
    top: -2px;
    right: -2px;
    width: 10px;
    height: 10px;
    background: #0066CC;
    border-radius: 50%;
    border: 2px solid #fff;
}
.notification-title {
    font-size: 0.9rem;
    line-height: 1.4;
}
.notification-message {
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.mark-read-wrap {
    opacity: 0;
    transition: opacity 0.2s;
}
.notification-item:hover .mark-read-wrap {
    opacity: 1;
}
.notification-type-badge {
    border: 1px solid rgba(0,0,0,0.08);
}
.min-w-0 { min-width: 0; }
.bg-orange-subtle { background-color: #fff3e0 !important; }
.text-orange { color: #e65100 !important; }
</style>
