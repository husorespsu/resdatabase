<?php
/**
 * PSU Research Project Management System
 * Cron Job: Check Due Dates and Send Reminders
 *
 * =====================================================================
 * WINDOWS TASK SCHEDULER SETUP INSTRUCTIONS
 * =====================================================================
 * 1. Open Task Scheduler (taskschd.msc)
 * 2. Click "Create Basic Task..."
 * 3. Name: "PSU Research - Check Due Dates"
 * 4. Trigger: Daily at 07:00 AM
 * 5. Action: Start a program
 * 6. Program/script: C:\xampp\php\php.exe
 * 7. Add arguments: C:\xampp\htdocs\research\cron\check_due_dates.php
 * 8. Click Finish
 *
 * To test manually, open Command Prompt and run:
 *   C:\xampp\php\php.exe C:\xampp\htdocs\research\cron\check_due_dates.php
 *
 * =====================================================================
 * LOG FILES
 * =====================================================================
 * Logs are stored in: cron/logs/cron_YYYY-MM-DD.log
 * Log directory is created automatically if it doesn't exist.
 * =====================================================================
 */

// Ensure this runs from CLI only
if (php_sapi_name() !== 'cli' && !isset($_SERVER['TERM'])) {
    // Allow web execution for testing but warn
    if (!isset($_GET['allow_web'])) {
        die('This script should only be run from the command line.');
    }
}

// Define root path
define('ROOT', dirname(__DIR__));
define('CRON_START_TIME', microtime(true));

// =====================================================================
// LOGGING SETUP
// =====================================================================
$logDir = ROOT . '/cron/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/cron_' . date('Y-m-d') . '.log';

function logMessage(string $message, string $level = 'INFO'): void {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $line = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    echo $line;
}

logMessage('=== PSU Research Cron Job: check_due_dates.php STARTED ===');

// =====================================================================
// LOAD DEPENDENCIES
// =====================================================================

// Load .env file manually (simple key=value parser)
$envFile = ROOT . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
    logMessage('.env file loaded');
} else {
    logMessage('.env file not found at ' . $envFile, 'WARNING');
}

// Load config/database.php
$dbConfigFile = ROOT . '/config/database.php';
if (!file_exists($dbConfigFile)) {
    logMessage('FATAL: config/database.php not found at ' . $dbConfigFile, 'ERROR');
    exit(1);
}
require_once $dbConfigFile;
logMessage('config/database.php loaded');

// Load helper functions
$helpersFile = ROOT . '/app/helpers/functions.php';
if (file_exists($helpersFile)) {
    require_once $helpersFile;
    logMessage('app/helpers/functions.php loaded');
} else {
    logMessage('app/helpers/functions.php not found (skipping)', 'WARNING');
}

// Load PHPMailer via Composer autoload
$autoloadFile = ROOT . '/vendor/autoload.php';
if (!file_exists($autoloadFile)) {
    logMessage('FATAL: vendor/autoload.php not found. Run: composer require phpmailer/phpmailer', 'ERROR');
    exit(1);
}
require_once $autoloadFile;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

logMessage('PHPMailer autoload loaded');

// =====================================================================
// DATABASE CONNECTION
// =====================================================================
try {
    // Assumes config/database.php defines getPDO() or $pdo
    if (function_exists('getPDO')) {
        $pdo = getPDO();
    } elseif (isset($db) && $db instanceof PDO) {
        $pdo = $db;
    } else {
        // Fallback: create PDO from env
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            getenv('DB_HOST') ?: 'localhost',
            getenv('DB_PORT') ?: '3306',
            getenv('DB_NAME') ?: 'psu_research'
        );
        $pdo = new PDO($dsn, getenv('DB_USER') ?: 'root', getenv('DB_PASS') ?: '', [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        ]);
    }
    logMessage('Database connection established');
} catch (PDOException $e) {
    logMessage('FATAL: Database connection failed: ' . $e->getMessage(), 'ERROR');
    exit(1);
}

// =====================================================================
// SMTP CONFIGURATION FROM ENV
// =====================================================================
$smtpHost     = getenv('MAIL_HOST')       ?: 'smtp.gmail.com';
$smtpPort     = (int)(getenv('MAIL_PORT') ?: 587);
$smtpUser     = getenv('MAIL_USERNAME')   ?: '';
$smtpPass     = getenv('MAIL_PASSWORD')   ?: '';
$smtpFrom     = getenv('MAIL_FROM_ADDRESS') ?: $smtpUser;
$smtpFromName = getenv('MAIL_FROM_NAME')  ?: 'PSU Research System';
$appName      = getenv('APP_NAME')        ?: 'PSU Research';
$appUrl       = getenv('APP_URL')         ?: 'http://localhost/research';

// =====================================================================
// FETCH ALL ADMIN USERS
// =====================================================================
try {
    $adminStmt = $pdo->prepare("
        SELECT id, name, email
        FROM users
        WHERE role IN ('admin', 'superadmin')
          AND is_active = 1
        ORDER BY name ASC
    ");
    $adminStmt->execute();
    $adminUsers = $adminStmt->fetchAll();
    logMessage('Found ' . count($adminUsers) . ' admin user(s) to notify');
} catch (PDOException $e) {
    logMessage('FATAL: Could not fetch admin users: ' . $e->getMessage(), 'ERROR');
    exit(1);
}

if (empty($adminUsers)) {
    logMessage('No active admin users found. Exiting.', 'WARNING');
    exit(0);
}

// =====================================================================
// COUNTERS
// =====================================================================
$stats = [
    'warning_7day'  => 0,
    'warning_3day'  => 0,
    'overdue'       => 0,
    'emails_sent'   => 0,
    'emails_failed' => 0,
    'notifs_created'=> 0,
];

// =====================================================================
// HELPER: SEND EMAIL VIA PHPMAILER
// =====================================================================
function sendReminderEmail(
    PHPMailer $mailer,
    array $recipients,
    string $subject,
    string $htmlBody,
    string &$errorMsg
): bool {
    try {
        $mailer->clearAddresses();
        $mailer->clearReplyTos();
        foreach ($recipients as $r) {
            $mailer->addAddress($r['email'], $r['name']);
        }
        $mailer->Subject = $subject;
        $mailer->Body    = $htmlBody;
        $mailer->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
        return $mailer->send();
    } catch (PHPMailerException $e) {
        $errorMsg = $e->getMessage();
        return false;
    }
}

// =====================================================================
// HELPER: CREATE IN-APP NOTIFICATION FOR ALL ADMINS
// =====================================================================
function createAdminNotifications(PDO $pdo, array $adminUsers, string $type, string $title, string $message, ?string $relatedUrl): int {
    $count = 0;
    $stmt  = $pdo->prepare("
        INSERT INTO notifications (user_id, type, title, message, related_url, is_read, created_at)
        VALUES (:user_id, :type, :title, :message, :related_url, 0, NOW())
    ");
    foreach ($adminUsers as $admin) {
        try {
            $stmt->execute([
                ':user_id'     => $admin['id'],
                ':type'        => $type,
                ':title'       => $title,
                ':message'     => $message,
                ':related_url' => $relatedUrl,
            ]);
            $count++;
        } catch (PDOException $e) {
            logMessage("Could not create notification for user {$admin['id']}: " . $e->getMessage(), 'WARNING');
        }
    }
    return $count;
}

// =====================================================================
// HELPER: BUILD HTML EMAIL TEMPLATE
// =====================================================================
function buildEmailTemplate(string $appName, string $badgeType, string $badgeText, string $bodyContent, string $appUrl): string {
    $badgeColors = [
        'warning' => '#f59e0b',
        'danger'  => '#dc2626',
        'info'    => '#0066CC',
    ];
    $badgeBg = $badgeColors[$badgeType] ?? '#6b7280';

    return <<<HTML
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$appName}</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:'Sarabun',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:20px 0;">
  <tr>
    <td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
        <!-- Header -->
        <tr>
          <td style="background:#003B6D;padding:24px 32px;">
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td>
                  <h1 style="margin:0;color:#ffffff;font-size:20px;font-weight:700;">{$appName}</h1>
                  <p style="margin:4px 0 0;color:#93c5fd;font-size:13px;">Prince of Songkla University</p>
                </td>
                <td align="right">
                  <span style="background:{$badgeBg};color:#fff;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;">{$badgeText}</span>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <!-- Body -->
        <tr>
          <td style="padding:32px;">
            {$bodyContent}
          </td>
        </tr>
        <!-- Footer -->
        <tr>
          <td style="background:#f9fafb;padding:20px 32px;border-top:1px solid #e5e7eb;">
            <p style="margin:0;color:#6b7280;font-size:12px;text-align:center;">
              อีเมลนี้ส่งอัตโนมัติจากระบบ {$appName}<br>
              กรุณาอย่าตอบกลับอีเมลนี้ | <a href="{$appUrl}" style="color:#0066CC;">เข้าสู่ระบบ</a>
            </p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>
HTML;
}

// =====================================================================
// INITIALIZE PHPMAILER
// =====================================================================
$mailer = new PHPMailer(true);
try {
    $mailer->isSMTP();
    $mailer->Host       = $smtpHost;
    $mailer->SMTPAuth   = true;
    $mailer->Username   = $smtpUser;
    $mailer->Password   = $smtpPass;
    $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mailer->Port       = $smtpPort;
    $mailer->CharSet    = 'UTF-8';
    $mailer->setFrom($smtpFrom, $smtpFromName);
    $mailer->isHTML(true);
    // Disable verbose debug output in cron
    $mailer->SMTPDebug = SMTP::DEBUG_OFF;
    logMessage("PHPMailer configured: {$smtpHost}:{$smtpPort} as {$smtpUser}");
} catch (PHPMailerException $e) {
    logMessage('FATAL: PHPMailer configuration failed: ' . $e->getMessage(), 'ERROR');
    exit(1);
}

// =====================================================================
// SECTION 1: 7-DAY WARNING
// =====================================================================
logMessage('--- Checking 7-day warnings ---');
try {
    $stmt7 = $pdo->prepare("
        SELECT
            pr.id              AS review_id,
            pr.due_date,
            pr.review_result,
            pr.reminder_sent_count,
            p.id               AS proposal_id,
            p.title            AS proposal_title,
            p.proposal_code,
            CONCAT(u.first_name, ' ', u.last_name) AS reviewer_name,
            u.email            AS reviewer_email
        FROM proposal_reviews pr
        JOIN proposals p ON p.id = pr.proposal_id
        JOIN users u ON u.id = pr.reviewer_id
        WHERE DATE(pr.due_date) = DATE(NOW() + INTERVAL 7 DAY)
          AND pr.review_result = 'pending'
        ORDER BY pr.due_date ASC
    ");
    $stmt7->execute();
    $reviews7 = $stmt7->fetchAll();
    logMessage('Found ' . count($reviews7) . ' review(s) due in 7 days');

    foreach ($reviews7 as $r) {
        $daysLeft      = 7;
        $dueDateFmt    = date('d/m/Y', strtotime($r['due_date']));
        $proposalCode  = htmlspecialchars($r['proposal_code']);
        $proposalTitle = htmlspecialchars($r['proposal_title']);
        $reviewerName  = htmlspecialchars($r['reviewer_name']);

        $bodyContent = <<<HTML
<h2 style="color:#003B6D;font-size:18px;margin:0 0 16px;">แจ้งเตือน: กำหนดส่งผลการประเมินใน 7 วัน</h2>
<div style="background:#fef3c7;border-left:4px solid #f59e0b;padding:16px;border-radius:4px;margin-bottom:20px;">
  <p style="margin:0;color:#92400e;font-size:14px;font-weight:600;">เหลือเวลาอีก {$daysLeft} วัน</p>
</div>
<table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;font-size:14px;">
  <tr style="background:#f9fafb;">
    <td style="color:#6b7280;width:40%;border-bottom:1px solid #e5e7eb;">รหัสข้อเสนอโครงการ</td>
    <td style="color:#111827;border-bottom:1px solid #e5e7eb;">{$proposalCode}</td>
  </tr>
  <tr>
    <td style="color:#6b7280;border-bottom:1px solid #e5e7eb;">ชื่อข้อเสนอโครงการ</td>
    <td style="color:#111827;border-bottom:1px solid #e5e7eb;">{$proposalTitle}</td>
  </tr>
  <tr style="background:#f9fafb;">
    <td style="color:#6b7280;border-bottom:1px solid #e5e7eb;">ผู้ทรงคุณวุฒิ</td>
    <td style="color:#111827;border-bottom:1px solid #e5e7eb;">{$reviewerName}</td>
  </tr>
  <tr>
    <td style="color:#6b7280;">กำหนดส่งผล</td>
    <td style="color:#dc2626;font-weight:600;">{$dueDateFmt}</td>
  </tr>
</table>
<div style="margin-top:24px;text-align:center;">
  <a href="{$appUrl}/proposal-reviews/{$r['review_id']}" style="background:#003B6D;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-size:14px;font-weight:600;">ดูรายละเอียดการประเมิน</a>
</div>
HTML;

        $html    = buildEmailTemplate($appName, 'warning', 'แจ้งเตือน 7 วัน', $bodyContent, $appUrl);
        $subject = "[{$appName}] แจ้งเตือน: กำหนดส่งผลประเมิน \"{$r['proposal_title']}\" อีก 7 วัน";
        $errMsg  = '';

        if (sendReminderEmail($mailer, $adminUsers, $subject, $html, $errMsg)) {
            logMessage("Email sent (7-day): review_id={$r['review_id']} proposal={$proposalCode}");
            $stats['emails_sent']++;
        } else {
            logMessage("Email FAILED (7-day): review_id={$r['review_id']} error={$errMsg}", 'ERROR');
            $stats['emails_failed']++;
        }

        $notifTitle   = "กำหนดส่งผลประเมินอีก 7 วัน";
        $notifMessage = "ผู้ทรงคุณวุฒิ {$r['reviewer_name']} ต้องส่งผลประเมิน \"{$r['proposal_title']}\" ภายในวันที่ {$dueDateFmt}";
        $notifUrl     = "/research/proposal-reviews/{$r['review_id']}";
        $created      = createAdminNotifications($pdo, $adminUsers, 'review_due_7day', $notifTitle, $notifMessage, $notifUrl);
        $stats['notifs_created'] += $created;
        $stats['warning_7day']++;
    }
} catch (PDOException $e) {
    logMessage('Error in 7-day check: ' . $e->getMessage(), 'ERROR');
}

// =====================================================================
// SECTION 2: 3-DAY WARNING
// =====================================================================
logMessage('--- Checking 3-day warnings ---');
try {
    $stmt3 = $pdo->prepare("
        SELECT
            pr.id              AS review_id,
            pr.due_date,
            pr.review_result,
            p.id               AS proposal_id,
            p.title            AS proposal_title,
            p.proposal_code,
            CONCAT(u.first_name, ' ', u.last_name) AS reviewer_name,
            u.email            AS reviewer_email
        FROM proposal_reviews pr
        JOIN proposals p ON p.id = pr.proposal_id
        JOIN users u ON u.id = pr.reviewer_id
        WHERE DATE(pr.due_date) = DATE(NOW() + INTERVAL 3 DAY)
          AND pr.review_result = 'pending'
        ORDER BY pr.due_date ASC
    ");
    $stmt3->execute();
    $reviews3 = $stmt3->fetchAll();
    logMessage('Found ' . count($reviews3) . ' review(s) due in 3 days');

    foreach ($reviews3 as $r) {
        $daysLeft      = 3;
        $dueDateFmt    = date('d/m/Y', strtotime($r['due_date']));
        $proposalCode  = htmlspecialchars($r['proposal_code']);
        $proposalTitle = htmlspecialchars($r['proposal_title']);
        $reviewerName  = htmlspecialchars($r['reviewer_name']);

        $bodyContent = <<<HTML
<h2 style="color:#003B6D;font-size:18px;margin:0 0 16px;">แจ้งเตือนด่วน: กำหนดส่งผลการประเมินใน 3 วัน</h2>
<div style="background:#fee2e2;border-left:4px solid #dc2626;padding:16px;border-radius:4px;margin-bottom:20px;">
  <p style="margin:0;color:#991b1b;font-size:14px;font-weight:600;">เหลือเวลาอีกเพียง {$daysLeft} วัน — กรุณาเร่งดำเนินการ</p>
</div>
<table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;font-size:14px;">
  <tr style="background:#f9fafb;">
    <td style="color:#6b7280;width:40%;border-bottom:1px solid #e5e7eb;">รหัสข้อเสนอโครงการ</td>
    <td style="color:#111827;border-bottom:1px solid #e5e7eb;">{$proposalCode}</td>
  </tr>
  <tr>
    <td style="color:#6b7280;border-bottom:1px solid #e5e7eb;">ชื่อข้อเสนอโครงการ</td>
    <td style="color:#111827;border-bottom:1px solid #e5e7eb;">{$proposalTitle}</td>
  </tr>
  <tr style="background:#f9fafb;">
    <td style="color:#6b7280;border-bottom:1px solid #e5e7eb;">ผู้ทรงคุณวุฒิ</td>
    <td style="color:#111827;border-bottom:1px solid #e5e7eb;">{$reviewerName}</td>
  </tr>
  <tr>
    <td style="color:#6b7280;">กำหนดส่งผล</td>
    <td style="color:#dc2626;font-weight:600;">{$dueDateFmt}</td>
  </tr>
</table>
<div style="margin-top:24px;text-align:center;">
  <a href="{$appUrl}/proposal-reviews/{$r['review_id']}" style="background:#dc2626;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-size:14px;font-weight:600;">ดูรายละเอียดการประเมิน</a>
</div>
HTML;

        $html    = buildEmailTemplate($appName, 'danger', 'แจ้งเตือนด่วน 3 วัน', $bodyContent, $appUrl);
        $subject = "[{$appName}] แจ้งเตือนด่วน: กำหนดส่งผลประเมิน \"{$r['proposal_title']}\" อีก 3 วัน!";
        $errMsg  = '';

        if (sendReminderEmail($mailer, $adminUsers, $subject, $html, $errMsg)) {
            logMessage("Email sent (3-day): review_id={$r['review_id']} proposal={$proposalCode}");
            $stats['emails_sent']++;
        } else {
            logMessage("Email FAILED (3-day): review_id={$r['review_id']} error={$errMsg}", 'ERROR');
            $stats['emails_failed']++;
        }

        $notifTitle   = "กำหนดส่งผลประเมินอีก 3 วัน (ด่วน)";
        $notifMessage = "ผู้ทรงคุณวุฒิ {$r['reviewer_name']} ต้องส่งผลประเมิน \"{$r['proposal_title']}\" ภายในวันที่ {$dueDateFmt}";
        $notifUrl     = "/research/proposal-reviews/{$r['review_id']}";
        $created      = createAdminNotifications($pdo, $adminUsers, 'review_due_3day', $notifTitle, $notifMessage, $notifUrl);
        $stats['notifs_created'] += $created;
        $stats['warning_3day']++;
    }
} catch (PDOException $e) {
    logMessage('Error in 3-day check: ' . $e->getMessage(), 'ERROR');
}

// =====================================================================
// SECTION 3: OVERDUE REMINDERS (due_date < TODAY, pending, sent < 5)
// =====================================================================
logMessage('--- Checking overdue reviews ---');
try {
    $stmtOD = $pdo->prepare("
        SELECT
            pr.id                  AS review_id,
            pr.due_date,
            pr.review_result,
            pr.reminder_sent_count,
            DATEDIFF(NOW(), pr.due_date) AS days_overdue,
            p.id                   AS proposal_id,
            p.title                AS proposal_title,
            p.proposal_code,
            CONCAT(u.first_name, ' ', u.last_name) AS reviewer_name,
            u.email                AS reviewer_email
        FROM proposal_reviews pr
        JOIN proposals p ON p.id = pr.proposal_id
        JOIN users u ON u.id = pr.reviewer_id
        WHERE DATE(pr.due_date) < DATE(NOW())
          AND pr.review_result = 'pending'
          AND pr.reminder_sent_count < 5
        ORDER BY days_overdue DESC
    ");
    $stmtOD->execute();
    $overdueReviews = $stmtOD->fetchAll();
    logMessage('Found ' . count($overdueReviews) . ' overdue review(s)');

    foreach ($overdueReviews as $r) {
        $daysOverdue   = (int)$r['days_overdue'];
        $dueDateFmt    = date('d/m/Y', strtotime($r['due_date']));
        $proposalCode  = htmlspecialchars($r['proposal_code']);
        $proposalTitle = htmlspecialchars($r['proposal_title']);
        $reviewerName  = htmlspecialchars($r['reviewer_name']);
        $sentCount     = (int)$r['reminder_sent_count'] + 1;

        $bodyContent = <<<HTML
<h2 style="color:#dc2626;font-size:18px;margin:0 0 16px;">เลยกำหนดส่งผลการประเมิน — ครั้งที่ {$sentCount}/5</h2>
<div style="background:#fee2e2;border-left:4px solid #dc2626;padding:16px;border-radius:4px;margin-bottom:20px;">
  <p style="margin:0;color:#991b1b;font-size:14px;font-weight:600;">เลยกำหนดมาแล้ว {$daysOverdue} วัน — กรุณาติดต่อผู้ทรงคุณวุฒิโดยด่วน</p>
</div>
<table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;font-size:14px;">
  <tr style="background:#f9fafb;">
    <td style="color:#6b7280;width:40%;border-bottom:1px solid #e5e7eb;">รหัสข้อเสนอโครงการ</td>
    <td style="color:#111827;border-bottom:1px solid #e5e7eb;">{$proposalCode}</td>
  </tr>
  <tr>
    <td style="color:#6b7280;border-bottom:1px solid #e5e7eb;">ชื่อข้อเสนอโครงการ</td>
    <td style="color:#111827;border-bottom:1px solid #e5e7eb;">{$proposalTitle}</td>
  </tr>
  <tr style="background:#f9fafb;">
    <td style="color:#6b7280;border-bottom:1px solid #e5e7eb;">ผู้ทรงคุณวุฒิ</td>
    <td style="color:#111827;border-bottom:1px solid #e5e7eb;">{$reviewerName}</td>
  </tr>
  <tr>
    <td style="color:#6b7280;border-bottom:1px solid #e5e7eb;">กำหนดส่งผล (เดิม)</td>
    <td style="color:#dc2626;font-weight:600;border-bottom:1px solid #e5e7eb;">{$dueDateFmt}</td>
  </tr>
  <tr style="background:#f9fafb;">
    <td style="color:#6b7280;">จำนวนวันที่เลยกำหนด</td>
    <td style="color:#dc2626;font-weight:700;">{$daysOverdue} วัน</td>
  </tr>
</table>
<p style="font-size:13px;color:#6b7280;margin-top:16px;">การแจ้งเตือนนี้เป็นครั้งที่ {$sentCount} จาก 5 ครั้ง ระบบจะหยุดส่งอัตโนมัติเมื่อครบ 5 ครั้ง</p>
<div style="margin-top:24px;text-align:center;">
  <a href="{$appUrl}/proposal-reviews/{$r['review_id']}" style="background:#dc2626;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-size:14px;font-weight:600;">ดูรายละเอียดการประเมิน</a>
</div>
HTML;

        $html    = buildEmailTemplate($appName, 'danger', "เลยกำหนด {$daysOverdue} วัน", $bodyContent, $appUrl);
        $subject = "[{$appName}] เลยกำหนด {$daysOverdue} วัน: ผลประเมิน \"{$r['proposal_title']}\" (ครั้งที่ {$sentCount})";
        $errMsg  = '';

        if (sendReminderEmail($mailer, $adminUsers, $subject, $html, $errMsg)) {
            logMessage("Email sent (overdue): review_id={$r['review_id']} days_overdue={$daysOverdue}");
            $stats['emails_sent']++;

            // Increment reminder_sent_count
            $updateStmt = $pdo->prepare("
                UPDATE proposal_reviews
                SET reminder_sent_count = reminder_sent_count + 1,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $updateStmt->execute([':id' => $r['review_id']]);
            logMessage("reminder_sent_count incremented for review_id={$r['review_id']}");
        } else {
            logMessage("Email FAILED (overdue): review_id={$r['review_id']} error={$errMsg}", 'ERROR');
            $stats['emails_failed']++;
        }

        $notifTitle   = "ผลประเมินเลยกำหนด {$daysOverdue} วัน";
        $notifMessage = "ผู้ทรงคุณวุฒิ {$r['reviewer_name']} ยังไม่ส่งผลประเมิน \"{$r['proposal_title']}\" (เลยกำหนดมา {$daysOverdue} วัน)";
        $notifUrl     = "/research/proposal-reviews/{$r['review_id']}";
        $created      = createAdminNotifications($pdo, $adminUsers, 'review_overdue', $notifTitle, $notifMessage, $notifUrl);
        $stats['notifs_created'] += $created;
        $stats['overdue']++;
    }
} catch (PDOException $e) {
    logMessage('Error in overdue check: ' . $e->getMessage(), 'ERROR');
}

// =====================================================================
// SUMMARY
// =====================================================================
$elapsed = round(microtime(true) - CRON_START_TIME, 2);
logMessage('=== CRON JOB COMPLETED ===');
logMessage("7-day warnings processed : {$stats['warning_7day']}");
logMessage("3-day warnings processed : {$stats['warning_3day']}");
logMessage("Overdue reminders sent   : {$stats['overdue']}");
logMessage("Emails sent successfully : {$stats['emails_sent']}");
logMessage("Emails failed            : {$stats['emails_failed']}");
logMessage("Notifications created    : {$stats['notifs_created']}");
logMessage("Execution time           : {$elapsed}s");
logMessage('=== END ===');

exit(0);
