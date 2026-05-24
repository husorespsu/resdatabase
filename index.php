<?php

declare(strict_types=1);

// ─────────────────────────────────────────────────────────────
//  BOOTSTRAP
// ─────────────────────────────────────────────────────────────

define('BASE_PATH', __DIR__);
define('APP_PATH',  BASE_PATH . '/app');

// Load .env early so BASE_URL can be read from environment
require_once BASE_PATH . '/vendor/autoload.php';
$_dotenvEarly = Dotenv\Dotenv::createImmutable(BASE_PATH);
$_dotenvEarly->safeLoad();

define('BASE_URL', rtrim($_ENV['APP_BASE_URL'] ?? '/research', '/'));

// Composer autoloader already loaded above
// .env already loaded above

// ─────────────────────────────────────────────────────────────
//  SESSION
// ─────────────────────────────────────────────────────────────

ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode',  '1');
ini_set('session.cookie_samesite', 'Lax');
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', '1');
}
session_name('psu_research_sess');
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ─────────────────────────────────────────────────────────────
//  CORE REQUIRES (order matters)
// ─────────────────────────────────────────────────────────────

require_once BASE_PATH . '/config/database.php';   // DatabaseConfig class
require_once APP_PATH  . '/helpers/functions.php';  // Global helpers
require_once APP_PATH  . '/core/Model.php';         // Base Model
require_once APP_PATH  . '/core/Controller.php';    // Base Controller
require_once APP_PATH  . '/core/Middleware.php';    // Auth guards
require_once APP_PATH  . '/core/Router.php';        // Router

// ─────────────────────────────────────────────────────────────
//  MODEL REQUIRES (preload all models)
// ─────────────────────────────────────────────────────────────

require_once APP_PATH . '/models/User.php';
require_once APP_PATH . '/models/Proposal.php';
require_once APP_PATH . '/models/Project.php';
require_once APP_PATH . '/models/ExpertReviewer.php';
require_once APP_PATH . '/models/ProposalReview.php';
require_once APP_PATH . '/models/Notification.php';

// ─────────────────────────────────────────────────────────────
//  ROUTER SETUP
// ─────────────────────────────────────────────────────────────

$router = new Router();

// ── Profile ───────────────────────────────────────────────────
$router->get( '/profile',                  'ProfileController@show',           ['auth']);
$router->post('/profile/update',           'ProfileController@update',         ['auth']);
$router->post('/profile/change-password',  'ProfileController@changePassword', ['auth']);

// ── Auth ──────────────────────────────────────────────────────
$router->get( '/auth/login',            'AuthController@showLogin');
$router->get( '/auth/google',           'AuthController@redirectToGoogle');
$router->get( '/auth/google/callback',  'AuthController@handleGoogleCallback');
$router->get( '/auth/logout',           'AuthController@logout');
$router->post('/auth/local-login',      'AuthController@localLogin');

// ── Dashboard / Home ──────────────────────────────────────────
$router->get( '/',          'DashboardController@index',     ['auth']);
$router->get( '/dashboard', 'DashboardController@executive', ['auth']);

// ── Proposals ─────────────────────────────────────────────────
$router->get( '/proposals',              'ProposalController@index',        ['auth']);
$router->get( '/proposals/create',       'ProposalController@create',       ['auth', 'role:admin,superadmin']);
$router->post('/proposals/store',        'ProposalController@store',        ['auth', 'role:admin,superadmin']);
$router->get( '/proposals/{id}',         'ProposalController@show',         ['auth']);
$router->get( '/proposals/{id}/edit',    'ProposalController@edit',         ['auth', 'role:admin,superadmin']);
$router->post('/proposals/{id}/update',  'ProposalController@update',       ['auth', 'role:admin,superadmin']);
$router->post('/proposals/{id}/delete',  'ProposalController@delete',       ['auth', 'role:admin,superadmin']);
$router->post('/proposals/{id}/status',  'ProposalController@updateStatus', ['auth', 'role:admin,superadmin']);

// ── Reviewer assignment (nested under proposals) ───────────────
$router->get( '/proposals/{id}/assign-reviewers', 'ReviewerController@assignForm',  ['auth', 'role:admin,superadmin']);
$router->post('/proposals/{id}/assign-reviewers', 'ReviewerController@assignStore', ['auth', 'role:admin,superadmin']);

// ── Projects ──────────────────────────────────────────────────
$router->get( '/projects',              'ProjectController@index',  ['auth']);
$router->get( '/projects/create',       'ProjectController@create', ['auth', 'role:admin,superadmin']);
$router->post('/projects/store',        'ProjectController@store',  ['auth', 'role:admin,superadmin']);
$router->get( '/projects/{id}',         'ProjectController@show',   ['auth']);
$router->post('/projects/{id}/update',  'ProjectController@update', ['auth', 'role:admin,superadmin']);

// ── Expert Reviewers ──────────────────────────────────────────
$router->get( '/reviewers',              'ReviewerController@index',  ['auth']);
$router->get( '/reviewers/create',       'ReviewerController@create', ['auth', 'role:admin,superadmin']);
$router->post('/reviewers/store',        'ReviewerController@store',  ['auth', 'role:admin,superadmin']);
$router->get( '/reviewers/{id}/edit',    'ReviewerController@edit',   ['auth', 'role:admin,superadmin']);
$router->post('/reviewers/{id}/update',  'ReviewerController@update', ['auth', 'role:admin,superadmin']);
$router->post('/reviewers/{id}/toggle',  'ReviewerController@toggle', ['auth', 'role:admin,superadmin']);

// ── Reviews ───────────────────────────────────────────────────
$router->get( '/reviews/{id}/invitation',  'ReviewerController@invitation',     ['auth']);
$router->post('/reviews/{id}/invitation',  'ReviewerController@saveInvitation', ['auth', 'role:admin,superadmin']);
$router->get( '/reviews/{id}/pdf',         'ReviewerController@generateInvitationPDF', ['auth']);
$router->post('/reviews/{id}/result',      'ReviewerController@saveResult',     ['auth', 'role:admin,superadmin']);
$router->post('/reviews/{id}/payment',     'ReviewerController@savePayment',    ['auth', 'role:admin,superadmin']);

// ── Payments ──────────────────────────────────────────────────
$router->get( '/payments',          'ReviewerController@payments',       ['auth']);
$router->get( '/payments/export',   'ReviewerController@exportPayments', ['auth']);

// ── Notifications ─────────────────────────────────────────────
$router->get( '/notifications',              'NotificationController@index',       ['auth']);
$router->get( '/notifications/unread-count', 'NotificationController@unreadCount', ['auth']);
$router->post('/notifications/{id}/read',    'NotificationController@markRead',    ['auth']);
$router->post('/notifications/read-all',     'NotificationController@markAllRead', ['auth']);

// ── Personnel API (HUSO autocomplete) ────────────────────────
$router->get( '/api/personnel/search', 'PersonnelController@search', ['auth']);
$router->get( '/api/personnel/all',    'PersonnelController@all',    ['auth']);

// ── Settings (superadmin only) ────────────────────────────────
$router->get( '/settings/funding',            'SettingsController@funding',       ['auth', 'role:superadmin,admin']);
$router->post('/settings/funding/store',       'SettingsController@storeFunding',  ['auth', 'role:superadmin,admin']);
$router->post('/settings/funding/{id}/update', 'SettingsController@updateFunding', ['auth', 'role:superadmin,admin']);
$router->post('/settings/funding/{id}/delete', 'SettingsController@deleteFunding', ['auth', 'role:superadmin,admin']);
$router->get( '/settings/fields',              'SettingsController@fields',        ['auth', 'role:superadmin,admin']);
$router->post('/settings/fields/store',        'SettingsController@storeField',    ['auth', 'role:superadmin,admin']);
$router->post('/settings/fields/{id}/update',  'SettingsController@updateField',   ['auth', 'role:superadmin,admin']);
$router->post('/settings/fields/{id}/delete',  'SettingsController@deleteField',   ['auth', 'role:superadmin,admin']);
$router->get( '/settings/users',               'SettingsController@users',       ['auth', 'role:superadmin,admin']);
$router->post('/settings/users/store',         'SettingsController@createUser',  ['auth', 'role:superadmin,admin']);
$router->post('/settings/users/{id}/role',     'SettingsController@updateRole',  ['auth', 'role:superadmin,admin']);
$router->post('/settings/users/{id}/toggle',   'SettingsController@toggleUser',  ['auth', 'role:superadmin,admin']);
$router->post('/settings/users/{id}/delete',   'SettingsController@deleteUser',  ['auth', 'role:superadmin,admin']);

// ─────────────────────────────────────────────────────────────
//  DISPATCH
// ─────────────────────────────────────────────────────────────

// Strip /research base path, remove query string
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestUri = rawurldecode($requestUri);

if (str_starts_with($requestUri, BASE_URL)) {
    $requestUri = substr($requestUri, strlen(BASE_URL));
}
$requestUri = '/' . ltrim($requestUri, '/');
if ($requestUri === '') $requestUri = '/';

$requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

try {
    if (!$router->dispatch($requestMethod, $requestUri)) {
        http_response_code(404);
        ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>404 – ไม่พบหน้า | PSU Research</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { min-height:100vh; background:linear-gradient(135deg,#003B6D,#0066CC); display:flex; align-items:center; justify-content:center; }
        .box { background:#fff; border-radius:20px; padding:52px 48px; text-align:center; max-width:480px; box-shadow:0 24px 60px rgba(0,0,0,.25); }
        .code { font-size:5rem; font-weight:800; color:#003B6D; line-height:1; }
    </style>
</head>
<body>
<div class="box">
    <div class="code">404</div>
    <h2 class="mt-2 mb-1">ไม่พบหน้าที่ร้องขอ</h2>
    <p class="text-muted">URL ที่ระบุไม่มีอยู่ในระบบ</p>
    <a href="/research/" class="btn btn-primary mt-2" style="background:#003B6D;border-color:#003B6D">กลับหน้าหลัก</a>
</div>
</body>
</html>
        <?php
    }
} catch (\Throwable $e) {
    http_response_code(500);
    $debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
    ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>500 – เกิดข้อผิดพลาด | PSU Research</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { min-height:100vh; background:linear-gradient(135deg,#003B6D,#0066CC); display:flex; align-items:center; justify-content:center; }
        .box { background:#fff; border-radius:20px; padding:52px 48px; text-align:center; max-width:600px; box-shadow:0 24px 60px rgba(0,0,0,.25); }
        .code { font-size:4rem; font-weight:800; color:#DC3545; line-height:1; }
        pre { text-align:left; background:#f8f9fa; padding:16px; border-radius:8px; font-size:.8rem; overflow:auto; max-height:300px; }
    </style>
</head>
<body>
<div class="box">
    <div class="code">500</div>
    <h2 class="mt-2 mb-1">เกิดข้อผิดพลาดในระบบ</h2>
    <?php if ($debug): ?>
    <p class="text-danger"><?= htmlspecialchars($e->getMessage()) ?></p>
    <pre><?= htmlspecialchars($e->getTraceAsString()) ?></pre>
    <?php else: ?>
    <p class="text-muted">กรุณาติดต่อผู้ดูแลระบบ</p>
    <?php endif; ?>
    <a href="/research/" class="btn btn-primary mt-2" style="background:#003B6D;border-color:#003B6D">กลับหน้าหลัก</a>
</div>
</body>
</html>
    <?php
}
