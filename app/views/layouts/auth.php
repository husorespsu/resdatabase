<?php
$flash = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= h($pageTitle ?? 'เข้าสู่ระบบ') ?> | ระบบบริหารจัดการงานวิจัย</title>

    <!-- Bootstrap 5.3.3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Font Awesome 6.5.1 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --primary:    #003B6D;
            --secondary:  #0066CC;
            --sidebar-bg: #002855;
        }

        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', 'Noto Sans Thai', 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #002855 0%, #0066CC 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .auth-wrapper {
            width: 100%;
            max-width: 450px;
            padding: 20px 15px;
            margin: 0 auto;
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .psu-logo-area {
            text-align: center;
            margin-bottom: 24px;
        }

        .psu-logo-area .logo-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            border: 3px solid rgba(255, 255, 255, 0.4);
        }

        .psu-logo-area .logo-icon i {
            font-size: 36px;
            color: #ffffff;
        }

        .psu-logo-area .university-name-th {
            font-size: 1.1rem;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 4px;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .psu-logo-area .university-name-en {
            font-size: 0.82rem;
            color: rgba(255, 255, 255, 0.82);
            letter-spacing: 0.5px;
        }

        .auth-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            width: 100%;
        }

        .auth-card-body {
            padding: 36px 40px;
        }

        .auth-footer {
            text-align: center;
            padding: 16px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.8rem;
        }

        .auth-footer a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
        }

        .auth-footer a:hover {
            color: #ffffff;
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .auth-card-body { padding: 28px 24px; }
        }
    </style>
</head>
<body>

<div class="auth-wrapper">

    <!-- PSU Logo & University Name -->
    <div class="psu-logo-area">
        <div class="logo-icon">
            <i class="fas fa-university"></i>
        </div>
        <div class="university-name-th">มหาวิทยาลัยสงขลานครินทร์</div>
        <div class="university-name-en">Prince of Songkla University</div>
    </div>

    <!-- Flash Messages (outside card so they are always visible) -->
    <?php foreach ($flash as $msg): ?>
    <div class="alert alert-<?= $msg['type'] === 'error' ? 'danger' : h($msg['type']) ?> alert-dismissible fade show w-100 mb-3"
         role="alert">
        <?= $msg['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endforeach; ?>

    <!-- Page Content -->
    <div class="auth-card">
        <div class="auth-card-body">
            <?= $content ?>
        </div>
    </div>

</div><!-- /.auth-wrapper -->

<!-- Footer -->
<footer class="auth-footer">
    <p class="mb-0">
        &copy; <?= date('Y') ?> มหาวิทยาลัยสงขลานครินทร์ | Prince of Songkla University<br>
        <small>ระบบบริหารจัดการงานวิจัย &mdash; สงวนลิขสิทธิ์</small>
    </p>
</footer>

<!-- Bootstrap 5.3.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
