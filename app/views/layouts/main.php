<?php
$flash       = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);
$currentUser = $_SESSION['user'] ?? null;
$basePath    = '/research';
// $content, $pageTitle, $breadcrumbs are passed from Controller::render()
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="<?= h($_SESSION['csrf_token'] ?? '') ?>">
    <title><?= h($pageTitle ?? 'PSU Research') ?> | ระบบบริหารจัดการงานวิจัย</title>

    <!-- Bootstrap 5.3.3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Font Awesome 6.5.1 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- DataTables 1.13.8 + Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <!-- Select2 4.1.0-rc.0 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <!-- SweetAlert2 11 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/research/public/css/style.css">

    <style>
        :root {
            --primary:        #003B6D;
            --secondary:      #0066CC;
            --light-blue:     #E8F0FE;
            --sidebar-bg:     #002855;
            --sidebar-width:  260px;
            --topbar-height:  60px;
            --sidebar-text:   rgba(255, 255, 255, 0.82);
            --sidebar-hover:  rgba(255, 255, 255, 0.1);
            --sidebar-active: rgba(255, 255, 255, 0.18);
        }

        /* ─── Reset & Base ─── */
        body {
            font-family: 'Segoe UI', 'Noto Sans Thai', Arial, sans-serif;
            background: #f4f6fb;
            color: #212529;
            overflow-x: hidden;
        }

        /* ─── Sidebar ─── */
        #sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--sidebar-bg);
            z-index: 1050;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease, width 0.3s ease;
            overflow-y: auto;
            overflow-x: hidden;
        }

        #sidebar::-webkit-scrollbar { width: 4px; }
        #sidebar::-webkit-scrollbar-track { background: transparent; }
        #sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 2px; }

        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 18px 20px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            min-height: var(--topbar-height);
            flex-shrink: 0;
        }

        .sidebar-header .brand-icon {
            width: 38px;
            height: 38px;
            background: rgba(255,255,255,0.15);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .sidebar-header .brand-icon i {
            color: #ffffff;
            font-size: 18px;
        }

        .sidebar-header .brand-text { line-height: 1.2; }

        .sidebar-header .brand-text .brand-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: #ffffff;
            white-space: nowrap;
        }

        .sidebar-header .brand-text .brand-subtitle {
            font-size: 0.68rem;
            color: rgba(255,255,255,0.6);
            white-space: nowrap;
        }

        /* ─── Sidebar Nav ─── */
        .sidebar-nav {
            padding: 12px 0;
            flex: 1;
        }

        .sidebar-nav .nav-divider {
            height: 1px;
            background: rgba(255,255,255,0.1);
            margin: 8px 16px;
        }

        .sidebar-nav .nav-section-label {
            font-size: 0.65rem;
            font-weight: 600;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.4);
            padding: 14px 20px 4px;
        }

        .sidebar-nav .nav-item > .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 20px;
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: 0.875rem;
            border-radius: 0;
            transition: background 0.2s, color 0.2s;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }

        .sidebar-nav .nav-item > .nav-link:hover {
            background: var(--sidebar-hover);
            color: #ffffff;
        }

        .sidebar-nav .nav-item > .nav-link.active {
            background: var(--sidebar-active);
            color: #ffffff;
            border-left: 3px solid #0066CC;
            padding-left: 17px;
        }

        .sidebar-nav .nav-item > .nav-link .nav-icon {
            width: 20px;
            text-align: center;
            font-size: 0.9rem;
            flex-shrink: 0;
            color: rgba(255,255,255,0.6);
        }

        .sidebar-nav .nav-item > .nav-link:hover .nav-icon,
        .sidebar-nav .nav-item > .nav-link.active .nav-icon {
            color: #ffffff;
        }

        .sidebar-nav .nav-item > .nav-link .nav-arrow {
            margin-left: auto;
            font-size: 0.7rem;
            transition: transform 0.25s;
            color: rgba(255,255,255,0.4);
        }

        .sidebar-nav .nav-item > .nav-link[aria-expanded="true"] .nav-arrow {
            transform: rotate(90deg);
        }

        /* Sub-menu */
        .sidebar-nav .sub-menu {
            list-style: none;
            padding: 0;
            margin: 0;
            background: rgba(0,0,0,0.15);
        }

        .sidebar-nav .sub-menu .sub-item > a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 20px 7px 50px;
            color: rgba(255,255,255,0.65);
            text-decoration: none;
            font-size: 0.82rem;
            transition: background 0.2s, color 0.2s;
        }

        .sidebar-nav .sub-menu .sub-item > a::before {
            content: '';
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: rgba(255,255,255,0.35);
            flex-shrink: 0;
        }

        .sidebar-nav .sub-menu .sub-item > a:hover {
            background: rgba(255,255,255,0.08);
            color: #ffffff;
        }

        .sidebar-nav .sub-menu .sub-item > a.active {
            color: #66bbff;
        }

        .sidebar-nav .sub-menu .sub-item > a.active::before {
            background: #66bbff;
        }

        /* ─── Top Navbar ─── */
        #topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--topbar-height);
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            z-index: 1040;
            display: flex;
            align-items: center;
            padding: 0 20px;
            gap: 12px;
            transition: left 0.3s ease;
            box-shadow: 0 1px 6px rgba(0,0,0,0.06);
        }

        #sidebar-toggle {
            border: none;
            background: none;
            color: #5a6a85;
            font-size: 1.1rem;
            padding: 6px 8px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.2s;
            flex-shrink: 0;
        }

        #sidebar-toggle:hover {
            background: var(--light-blue);
            color: var(--primary);
        }

        .topbar-breadcrumb {
            flex: 1;
            min-width: 0;
        }

        .topbar-breadcrumb .breadcrumb {
            margin: 0;
            padding: 0;
            background: none;
            font-size: 0.82rem;
        }

        .topbar-breadcrumb .breadcrumb-item a {
            color: var(--secondary);
            text-decoration: none;
        }

        .topbar-breadcrumb .breadcrumb-item a:hover { text-decoration: underline; }

        .topbar-breadcrumb .breadcrumb-item.active { color: #6c757d; }

        /* Topbar actions */
        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .topbar-btn {
            position: relative;
            width: 38px;
            height: 38px;
            border: none;
            background: none;
            color: #5a6a85;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
            text-decoration: none;
        }

        .topbar-btn:hover {
            background: var(--light-blue);
            color: var(--primary);
        }

        .notification-badge {
            position: absolute;
            top: 4px;
            right: 4px;
            background: #dc3545;
            color: #ffffff;
            font-size: 0.6rem;
            font-weight: 700;
            min-width: 16px;
            height: 16px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 3px;
            line-height: 1;
        }

        .notification-badge.d-none { display: none !important; }

        /* User Avatar Dropdown */
        .user-avatar-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .user-avatar-btn:hover { background: var(--light-blue); }

        .user-avatar-btn .avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #dee2e6;
        }

        .user-avatar-btn .avatar-placeholder {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #ffffff;
            font-size: 0.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-avatar-btn .user-info { line-height: 1.2; text-align: left; }

        .user-avatar-btn .user-name {
            font-size: 0.82rem;
            font-weight: 600;
            color: #212529;
            max-width: 130px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .user-avatar-btn .user-role {
            font-size: 0.68rem;
            color: #6c757d;
        }

        /* ─── Main Content ─── */
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--topbar-height);
            min-height: calc(100vh - var(--topbar-height));
            transition: margin-left 0.3s ease;
        }

        /* ─── Flash Messages ─── */
        .flash-container {
            position: fixed;
            top: calc(var(--topbar-height) + 12px);
            right: 20px;
            z-index: 2000;
            min-width: 300px;
            max-width: 420px;
        }

        .flash-container .alert {
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
            border-radius: 10px;
        }

        /* ─── Responsive / Mobile ─── */
        @media (max-width: 991.98px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.show { transform: translateX(0); }
            #topbar { left: 0; }
            .main-content { margin-left: 0; }

            .sidebar-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.5);
                z-index: 1045;
            }

            .sidebar-overlay.show { display: block; }
            .user-avatar-btn .user-info { display: none; }
        }

        @media (max-width: 575.98px) {
            .flash-container {
                left: 12px;
                right: 12px;
                min-width: unset;
                max-width: unset;
            }
        }
    </style>
</head>
<body>
<?php $uri = $_SERVER['REQUEST_URI'] ?? ''; ?>

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ─── SIDEBAR ─── -->
<nav id="sidebar" aria-label="เมนูหลัก">

    <!-- Brand Header -->
    <div class="sidebar-header">
        <div class="brand-icon">
            <i class="fas fa-flask"></i>
        </div>
        <div class="brand-text">
            <div class="brand-title">ระบบวิจัย</div>
            <div class="brand-subtitle">PSU Research</div>
        </div>
    </div>

    <!-- Navigation -->
    <ul class="sidebar-nav list-unstyled mb-0">

        <!-- หน้าหลัก -->
        <li class="nav-item">
            <a href="/research/"
               class="nav-link <?= ($uri === '/research/' || $uri === '/research') ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-home"></i></span>
                <span>หน้าหลัก</span>
            </a>
        </li>

        <!-- Dashboard ผู้บริหาร (admin/superadmin/executive) -->
        <?php if (in_array($currentUser['role'] ?? '', ['admin', 'superadmin', 'executive'])): ?>
        <li class="nav-item">
            <a href="/research/dashboard"
               class="nav-link <?= str_contains($uri, '/dashboard') ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-chart-pie"></i></span>
                <span>Dashboard ผู้บริหาร</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- Divider -->
        <li><div class="nav-divider"></div></li>

        <!-- ข้อเสนอโครงการ -->
        <li class="nav-item">
            <button class="nav-link <?= str_contains($uri, '/proposals') ? 'active' : '' ?>"
                    data-bs-toggle="collapse"
                    data-bs-target="#collapseProposals"
                    aria-expanded="<?= str_contains($uri, '/proposals') ? 'true' : 'false' ?>"
                    aria-controls="collapseProposals">
                <span class="nav-icon"><i class="fas fa-file-alt"></i></span>
                <span>ข้อเสนอโครงการ</span>
                <i class="fas fa-chevron-right nav-arrow"></i>
            </button>
            <div class="collapse <?= str_contains($uri, '/proposals') ? 'show' : '' ?>"
                 id="collapseProposals">
                <ul class="sub-menu list-unstyled">
                    <li class="sub-item">
                        <a href="/research/proposals"
                           class="<?= $uri === '/research/proposals' ? 'active' : '' ?>">
                            รายการทั้งหมด
                        </a>
                    </li>
                    <?php if (in_array($currentUser['role'] ?? '', ['admin', 'superadmin'])): ?>
                    <li class="sub-item">
                        <a href="/research/proposals/create"
                           class="<?= str_contains($uri, '/proposals/create') ? 'active' : '' ?>">
                            เพิ่มข้อเสนอใหม่
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </li>

        <!-- โครงการวิจัย -->
        <li class="nav-item">
            <button class="nav-link <?= str_contains($uri, '/projects') ? 'active' : '' ?>"
                    data-bs-toggle="collapse"
                    data-bs-target="#collapseProjects"
                    aria-expanded="<?= str_contains($uri, '/projects') ? 'true' : 'false' ?>"
                    aria-controls="collapseProjects">
                <span class="nav-icon"><i class="fas fa-flask"></i></span>
                <span>โครงการวิจัย</span>
                <i class="fas fa-chevron-right nav-arrow"></i>
            </button>
            <div class="collapse <?= str_contains($uri, '/projects') ? 'show' : '' ?>"
                 id="collapseProjects">
                <ul class="sub-menu list-unstyled">
                    <li class="sub-item">
                        <a href="/research/projects"
                           class="<?= $uri === '/research/projects' ? 'active' : '' ?>">
                            รายการโครงการ
                        </a>
                    </li>
                    <li class="sub-item">
                        <a href="/research/projects?view=progress"
                           class="<?= (str_contains($uri, '/projects') && ($_GET['view'] ?? '') === 'progress') ? 'active' : '' ?>">
                            ติดตามความคืบหน้า
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <!-- ผู้ทรงคุณวุฒิ (not shown to executive) -->
        <?php if (($currentUser['role'] ?? '') !== 'executive'): ?>
        <li class="nav-item">
            <button class="nav-link <?= str_contains($uri, '/reviewers') ? 'active' : '' ?>"
                    data-bs-toggle="collapse"
                    data-bs-target="#collapseReviewers"
                    aria-expanded="<?= str_contains($uri, '/reviewers') ? 'true' : 'false' ?>"
                    aria-controls="collapseReviewers">
                <span class="nav-icon"><i class="fas fa-user-tie"></i></span>
                <span>ผู้ทรงคุณวุฒิ</span>
                <i class="fas fa-chevron-right nav-arrow"></i>
            </button>
            <div class="collapse <?= str_contains($uri, '/reviewers') ? 'show' : '' ?>"
                 id="collapseReviewers">
                <ul class="sub-menu list-unstyled">
                    <li class="sub-item">
                        <a href="/research/reviewers"
                           class="<?= $uri === '/research/reviewers' ? 'active' : '' ?>">
                            ทะเบียนผู้ทรง
                        </a>
                    </li>
                    <?php if (in_array($currentUser['role'] ?? '', ['admin', 'superadmin'])): ?>
                    <li class="sub-item">
                        <a href="/research/reviewers?view=assignments"
                           class="<?= (str_contains($uri, '/reviewers') && ($_GET['view'] ?? '') === 'assignments') ? 'active' : '' ?>">
                            การมอบหมายงาน
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </li>
        <?php endif; ?>

        <!-- การเงิน (not shown to executive) -->
        <?php if (($currentUser['role'] ?? '') !== 'executive'): ?>
        <li class="nav-item">
            <a href="/research/payments"
               class="nav-link <?= str_contains($uri, '/payments') ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-money-bill-wave"></i></span>
                <span>การเงิน</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- Divider -->
        <li><div class="nav-divider"></div></li>

        <!-- ตั้งค่าระบบ (admin/superadmin only) -->
        <?php if (in_array($currentUser['role'] ?? '', ['admin', 'superadmin'])): ?>
        <li class="nav-item">
            <button class="nav-link <?= str_contains($uri, '/settings') ? 'active' : '' ?>"
                    data-bs-toggle="collapse"
                    data-bs-target="#collapseSettings"
                    aria-expanded="<?= str_contains($uri, '/settings') ? 'true' : 'false' ?>"
                    aria-controls="collapseSettings">
                <span class="nav-icon"><i class="fas fa-cog"></i></span>
                <span>ตั้งค่าระบบ</span>
                <i class="fas fa-chevron-right nav-arrow"></i>
            </button>
            <div class="collapse <?= str_contains($uri, '/settings') ? 'show' : '' ?>"
                 id="collapseSettings">
                <ul class="sub-menu list-unstyled">
                    <li class="sub-item">
                        <a href="/research/settings/funding"
                           class="<?= str_contains($uri, '/settings/funding') ? 'active' : '' ?>">
                            แหล่งทุน
                        </a>
                    </li>
                    <li class="sub-item">
                        <a href="/research/settings/fields"
                           class="<?= str_contains($uri, '/settings/fields') ? 'active' : '' ?>">
                            สาขาวิชา
                        </a>
                    </li>
                    <li class="sub-item">
                        <a href="/research/settings/users"
                           class="<?= str_contains($uri, '/settings/users') ? 'active' : '' ?>">
                            ผู้ใช้งาน
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        <?php endif; ?>

    </ul>
</nav><!-- /#sidebar -->


<!-- ─── TOP NAVBAR ─── -->
<header id="topbar">

    <!-- Hamburger Toggle -->
    <button id="sidebar-toggle" aria-label="เปิด/ปิดเมนู">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Breadcrumb (topbar) -->
    <div class="topbar-breadcrumb d-none d-md-block">
        <?php if (!empty($breadcrumbs)): ?>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/research/">หน้าหลัก</a></li>
                <?php foreach ($breadcrumbs as $bc): ?>
                    <?php if (!empty($bc['url'])): ?>
                        <li class="breadcrumb-item">
                            <a href="/research<?= h($bc['url']) ?>"><?= h($bc['label']) ?></a>
                        </li>
                    <?php else: ?>
                        <li class="breadcrumb-item active" aria-current="page"><?= h($bc['label']) ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </nav>
        <?php else: ?>
        <span class="text-muted" style="font-size:0.85rem;">
            <?= h($pageTitle ?? 'ระบบบริหารจัดการงานวิจัย') ?>
        </span>
        <?php endif; ?>
    </div>

    <!-- Topbar Actions -->
    <div class="topbar-actions ms-auto">

        <!-- Notification Bell -->
        <a href="/research/notifications"
           class="topbar-btn"
           id="notification-bell"
           aria-label="การแจ้งเตือน"
           title="การแจ้งเตือน">
            <i class="fas fa-bell"></i>
            <span class="notification-badge d-none" id="notification-count">0</span>
        </a>

        <!-- User Dropdown -->
        <div class="dropdown">
            <button class="user-avatar-btn"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-label="เมนูผู้ใช้งาน">
                <?php if (!empty($currentUser['avatar'])): ?>
                    <img src="<?= h($currentUser['avatar']) ?>"
                         alt="avatar"
                         class="avatar"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="avatar-placeholder" style="display:none;">
                        <?= mb_strtoupper(mb_substr($currentUser['name'] ?? 'U', 0, 1)) ?>
                    </div>
                <?php else: ?>
                    <div class="avatar-placeholder">
                        <?= mb_strtoupper(mb_substr($currentUser['name'] ?? 'U', 0, 1)) ?>
                    </div>
                <?php endif; ?>
                <div class="user-info d-none d-lg-block">
                    <div class="user-name"><?= h($currentUser['name'] ?? 'ผู้ใช้งาน') ?></div>
                    <div class="user-role">
                        <?php
                        $roleLabels = [
                            'superadmin' => 'ผู้ดูแลระบบสูงสุด',
                            'admin'      => 'ผู้ดูแลระบบ',
                            'executive'  => 'ผู้บริหาร',
                            'user'       => 'ผู้ใช้งาน',
                        ];
                        echo h($roleLabels[$currentUser['role'] ?? 'user'] ?? 'ผู้ใช้งาน');
                        ?>
                    </div>
                </div>
                <i class="fas fa-chevron-down ms-1 d-none d-lg-inline" style="font-size:0.65rem; color:#6c757d;"></i>
            </button>

            <ul class="dropdown-menu dropdown-menu-end shadow-sm mt-1" style="min-width:200px; border-radius:10px; font-size:0.875rem;">
                <li>
                    <div class="dropdown-header text-truncate" style="max-width:200px;">
                        <?= h($currentUser['email'] ?? '') ?>
                    </div>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2" href="/research/profile">
                        <i class="fas fa-user-circle text-muted" style="width:16px;"></i>
                        โปรไฟล์ของฉัน
                    </a>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 text-danger"
                       href="/research/auth/logout"
                       onclick="return confirm('ต้องการออกจากระบบ?')">
                        <i class="fas fa-sign-out-alt" style="width:16px;"></i>
                        ออกจากระบบ
                    </a>
                </li>
            </ul>
        </div><!-- /.dropdown -->

    </div><!-- /.topbar-actions -->
</header><!-- /#topbar -->


<!-- ─── FLASH MESSAGES ─── -->
<div class="flash-container" id="flashContainer">
    <?php foreach ($flash as $msg): ?>
    <div class="alert alert-<?= $msg['type'] === 'error' ? 'danger' : h($msg['type']) ?> alert-dismissible fade show d-flex align-items-start gap-2"
         role="alert"
         data-auto-dismiss="4500">
        <?php
        $iconMap = ['success' => 'check-circle', 'danger' => 'exclamation-circle', 'error' => 'exclamation-circle', 'warning' => 'exclamation-triangle', 'info' => 'info-circle'];
        $iconType = $msg['type'] === 'error' ? 'danger' : $msg['type'];
        $iconName = $iconMap[$msg['type']] ?? 'info-circle';
        ?>
        <i class="fas fa-<?= $iconName ?> mt-1 flex-shrink-0"></i>
        <div><?= $msg['message'] ?></div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
    <?php endforeach; ?>
</div><!-- /.flash-container -->


<!-- ─── MAIN CONTENT ─── -->
<main class="main-content" id="mainContent">
    <div class="container-fluid py-4">

        <!-- Breadcrumb (below topbar, in content area) -->
        <?php if (!empty($breadcrumbs)): ?>
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/research/">หน้าหลัก</a></li>
                <?php foreach ($breadcrumbs as $bc): ?>
                    <?php if (!empty($bc['url'])): ?>
                        <li class="breadcrumb-item">
                            <a href="/research<?= h($bc['url']) ?>"><?= h($bc['label']) ?></a>
                        </li>
                    <?php else: ?>
                        <li class="breadcrumb-item active"><?= h($bc['label']) ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </nav>
        <?php endif; ?>

        <!-- ─── SCRIPTS (loaded before view content so libraries are available to inline scripts) ─── -->
        <!-- jQuery 3.7.1 -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <!-- Bootstrap 5.3.3 JS Bundle -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- DataTables 1.13.8 + Bootstrap 5 -->
        <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
        <!-- Select2 4.1.0-rc.0 -->
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <!-- Chart.js 4.4.2 -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
        <!-- SweetAlert2 11 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
        <!-- Custom App JS -->
        <script src="/research/public/js/app.js"></script>

        <?php echo $content; ?>

    </div>
</main>


<script>
(function () {
    'use strict';

    /* ─── CSRF Helper ─── */
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    if (typeof $ !== 'undefined') {
        $.ajaxSetup({ headers: { 'X-CSRF-Token': csrfToken } });
    }

    /* ─── Sidebar Toggle ─── */
    const sidebar        = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const sidebarToggle  = document.getElementById('sidebar-toggle');

    function openSidebar() {
        sidebar.classList.add('show');
        sidebarOverlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('show');
        sidebarOverlay.classList.remove('show');
        document.body.style.overflow = '';
    }

    sidebarToggle.addEventListener('click', function () {
        if (window.innerWidth <= 991) {
            sidebar.classList.contains('show') ? closeSidebar() : openSidebar();
        } else {
            const isCollapsed = sidebar.classList.contains('collapsed');
            if (isCollapsed) {
                sidebar.classList.remove('collapsed');
                sidebar.style.width = '';
                document.getElementById('topbar').style.left = '';
                document.getElementById('mainContent').style.marginLeft = '';
            } else {
                sidebar.classList.add('collapsed');
                sidebar.style.width = '0px';
                document.getElementById('topbar').style.left = '0';
                document.getElementById('mainContent').style.marginLeft = '0';
            }
        }
    });

    sidebarOverlay.addEventListener('click', closeSidebar);

    window.addEventListener('resize', function () {
        if (window.innerWidth > 991) { closeSidebar(); }
    });

    /* ─── Auto-dismiss Flash Messages ─── */
    document.querySelectorAll('[data-auto-dismiss]').forEach(function (el) {
        const delay = parseInt(el.getAttribute('data-auto-dismiss'), 10) || 4500;
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            if (bsAlert) bsAlert.close();
        }, delay);
    });

    /* ─── Notification Poll ─── */
    const notifBadge = document.getElementById('notification-count');

    function fetchUnreadCount() {
        fetch('/research/notifications/unread-count', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': csrfToken }
        })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (data) {
            if (!data) return;
            const count = parseInt(data.count ?? 0, 10);
            if (count > 0) {
                notifBadge.textContent = count > 99 ? '99+' : count;
                notifBadge.classList.remove('d-none');
            } else {
                notifBadge.classList.add('d-none');
            }
        })
        .catch(function () { /* silently ignore */ });
    }

    fetchUnreadCount();
    setInterval(fetchUnreadCount, 5 * 60 * 1000);

    /* ─── DataTables Defaults ─── */
    if (typeof $.fn !== 'undefined' && typeof $.fn.DataTable !== 'undefined') {
        $.extend(true, $.fn.dataTable.defaults, {
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/th.json' },
            responsive: true,
            pageLength: 25,
            dom: '<"row mb-3"<"col-sm-6"l><"col-sm-6 d-flex justify-content-end"f>>' +
                 '<"table-responsive"t>' +
                 '<"row mt-3"<"col-sm-6"i><"col-sm-6 d-flex justify-content-end"p>>',
        });
    }

    /* ─── SweetAlert2 Defaults ─── */
    if (typeof Swal !== 'undefined') {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
        window.PSU = window.PSU || {};
        window.PSU.Toast = Toast;
    }

    /* ─── Confirm Delete Helper ─── */
    window.PSU = window.PSU || {};
    window.PSU.confirmDelete = function (formId, message) {
        message = message || 'ต้องการลบรายการนี้หรือไม่?';
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'ยืนยันการลบ',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ลบ',
                cancelButtonText: 'ยกเลิก',
            }).then(function (result) {
                if (result.isConfirmed) {
                    document.getElementById(formId).submit();
                }
            });
        } else if (confirm(message)) {
            document.getElementById(formId).submit();
        }
        return false;
    };

})();
</script>

</body>
</html>
