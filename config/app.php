<?php

declare(strict_types=1);

/**
 * Application configuration.
 *
 * All sensitive values are read from $_ENV (populated by vlucas/phpdotenv).
 * This file is intentionally not namespaced so it can be required anywhere
 * and simply returns an associative array.
 *
 * Usage:
 *   $config = require __DIR__ . '/app.php';
 *   echo $config['app']['name'];
 */

return [

    // ── Application ──────────────────────────────────────────
    'app' => [
        'name'    => $_ENV['APP_NAME']  ?? 'PSU Research Management',
        'url'     => $_ENV['APP_URL']   ?? 'http://localhost/research',
        'env'     => $_ENV['APP_ENV']   ?? 'production',
        'debug'   => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'key'     => $_ENV['APP_KEY']   ?? '',
        'locale'  => 'th_TH',
        'timezone'=> 'Asia/Bangkok',
    ],

    // ── Session ──────────────────────────────────────────────
    'session' => [
        'name'            => 'psu_research_session',
        'lifetime'        => 7200,          // seconds (2 hours)
        'cookie_secure'   => false,         // set true when using HTTPS
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ],

    // ── File Uploads ─────────────────────────────────────────
    'upload' => [
        'max_size'      => 10 * 1024 * 1024, // 10 MB in bytes
        'allowed_types' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'image/jpeg',
            'image/png',
            'image/gif',
        ],
        'allowed_extensions' => ['pdf','doc','docx','xls','xlsx','ppt','pptx','jpg','jpeg','png','gif'],
        'base_path'     => __DIR__ . '/../storage/uploads',
        'base_url'      => ($_ENV['APP_URL'] ?? 'http://localhost/research') . '/storage/uploads',
    ],

    // ── Pagination ───────────────────────────────────────────
    'pagination' => [
        'per_page'     => 15,
        'max_per_page' => 100,
        'window'       => 2,    // pages shown on each side of current page
    ],

    // ── Mail ─────────────────────────────────────────────────
    'mail' => [
        'host'         => $_ENV['MAIL_HOST']         ?? 'smtp.gmail.com',
        'port'         => (int) ($_ENV['MAIL_PORT']  ?? 587),
        'username'     => $_ENV['MAIL_USERNAME']     ?? '',
        'password'     => $_ENV['MAIL_PASSWORD']     ?? '',
        'from_name'    => $_ENV['MAIL_FROM_NAME']    ?? 'PSU Research System',
        'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@psu.ac.th',
        'encryption'   => 'tls',
    ],

];
