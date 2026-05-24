<?php

declare(strict_types=1);

/**
 * Global helper functions for PSU Research Management System.
 *
 * Loaded via Composer's "files" autoload or a require in bootstrap.
 */

// ── HTML Escaping ─────────────────────────────────────────────────────────────

if (!function_exists('h')) {
    /**
     * Safely escape a value for HTML output.
     */
    function h($string): string
    {
        return htmlspecialchars((string) $string, ENT_QUOTES, 'UTF-8');
    }
}

// ── CSRF ──────────────────────────────────────────────────────────────────────

if (!function_exists('generateCsrfToken')) {
    /**
     * Return the CSRF token already set in the session by index.php.
     */
    function generateCsrfToken(): string
    {
        return $_SESSION['csrf_token'] ?? '';
    }
}

if (!function_exists('validateCsrfToken')) {
    /**
     * Validate a CSRF token against the one stored in the session.
     * Constant-time comparison prevents timing attacks.
     */
    function validateCsrfToken(string $token): bool
    {
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}

// ── Date / Number Formatting ──────────────────────────────────────────────────

if (!function_exists('thaiMonthName')) {
    /**
     * Return the full Thai month name for a month number (1–12).
     */
    function thaiMonthName(int $month): string
    {
        $names = [
            1  => 'มกราคม',
            2  => 'กุมภาพันธ์',
            3  => 'มีนาคม',
            4  => 'เมษายน',
            5  => 'พฤษภาคม',
            6  => 'มิถุนายน',
            7  => 'กรกฎาคม',
            8  => 'สิงหาคม',
            9  => 'กันยายน',
            10 => 'ตุลาคม',
            11 => 'พฤศจิกายน',
            12 => 'ธันวาคม',
        ];
        return $names[$month] ?? '';
    }
}

if (!function_exists('thaiMonthShort')) {
    /**
     * Return the abbreviated Thai month name for a month number (1–12).
     */
    function thaiMonthShort(int $month): string
    {
        $names = [
            1  => 'ม.ค.',
            2  => 'ก.พ.',
            3  => 'มี.ค.',
            4  => 'เม.ย.',
            5  => 'พ.ค.',
            6  => 'มิ.ย.',
            7  => 'ก.ค.',
            8  => 'ส.ค.',
            9  => 'ก.ย.',
            10 => 'ต.ค.',
            11 => 'พ.ย.',
            12 => 'ธ.ค.',
        ];
        return $names[$month] ?? '';
    }
}

if (!function_exists('formatThaiDate')) {
    /**
     * Format a Y-m-d date string in the Thai Buddhist calendar.
     *
     * @param string|null $date   Date string (Y-m-d) or any strtotime-compatible string.
     * @param string      $format 'full' = "D MMMM YYYY+543"  (e.g. "16 พฤษภาคม 2569")
     *                            'short' = "D/M/YYYY+543"    (e.g. "16/5/2569")
     */
    function formatThaiDate(?string $date, string $format = 'full'): string
    {
        if ($date === null || $date === '' || $date === '0000-00-00') {
            return '-';
        }

        $ts = strtotime($date);
        if ($ts === false) {
            return '-';
        }

        $day    = (int) date('j', $ts);
        $month  = (int) date('n', $ts);
        $yearBE = (int) date('Y', $ts) + 543;

        if ($format === 'short') {
            return $day . '/' . $month . '/' . $yearBE;
        }

        // default: full
        return $day . ' ' . thaiMonthName($month) . ' ' . $yearBE;
    }
}

if (!function_exists('formatBudget')) {
    /**
     * Format a number as Thai Baht with comma separators.
     *
     * Returns '-' if null or 0.
     *
     * Example: 1500000 → '1,500,000.00 บาท'
     */
    function formatBudget($amount): string
    {
        if ($amount === null || $amount === '' || (float) $amount === 0.0) {
            return '-';
        }

        return number_format((float) $amount, 2, '.', ',') . ' บาท';
    }
}

if (!function_exists('formatRelativeTime')) {
    /**
     * Return a Thai-language relative time string for a datetime string.
     *
     * Examples: "เมื่อกี้", "5 นาทีที่แล้ว", "2 ชั่วโมงที่แล้ว", "3 วันที่แล้ว"
     */
    function formatRelativeTime(string $datetime): string
    {
        $ts   = strtotime($datetime);
        if ($ts === false) {
            return $datetime;
        }

        $diff = time() - $ts;

        if ($diff < 60) {
            return 'เมื่อกี้';
        }

        if ($diff < 3600) {
            $minutes = (int) floor($diff / 60);
            return $minutes . ' นาทีที่แล้ว';
        }

        if ($diff < 86400) {
            $hours = (int) floor($diff / 3600);
            return $hours . ' ชั่วโมงที่แล้ว';
        }

        if ($diff < 2592000) { // 30 days
            $days = (int) floor($diff / 86400);
            return $days . ' วันที่แล้ว';
        }

        if ($diff < 31536000) { // 365 days
            $months = (int) floor($diff / 2592000);
            return $months . ' เดือนที่แล้ว';
        }

        $years = (int) floor($diff / 31536000);
        return $years . ' ปีที่แล้ว';
    }
}

// ── Status Labels & Badges ────────────────────────────────────────────────────

if (!function_exists('thai_status_label')) {
    /**
     * Return the Thai text label for a status string.
     *
     * @param string $status   Status slug (e.g. 'draft', 'in_progress')
     * @param string $context  'proposal' | 'project' | 'review' | 'payment'
     */
    function thai_status_label(string $status, string $context = 'proposal'): string
    {
        $proposalLabels = [
            'draft'     => 'ฉบับร่าง',
            'reviewing' => 'รอพิจารณา',
            'approved'  => 'อนุมัติแล้ว',
            'rejected'  => 'ปฏิเสธ',
        ];

        $projectLabels = [
            'approved'    => 'อนุมัติแล้ว',
            'in_progress' => 'กำลังดำเนินการ',
            'completed'   => 'เสร็จสิ้น',
            'closed'      => 'ปิดแล้ว',
            'cancelled'   => 'ยกเลิก',
        ];

        $reviewLabels = [
            'pending'                 => 'รอผล',
            'approved'                => 'ผ่าน',
            'approved_with_condition' => 'ผ่านมีเงื่อนไข',
            'rejected'                => 'ไม่ผ่าน',
        ];

        $paymentLabels = [
            'pending' => 'รอจ่าย',
            'paid'    => 'จ่ายแล้ว',
        ];

        return match ($context) {
            'project' => $projectLabels[$status] ?? $status,
            'review'  => $reviewLabels[$status]  ?? $status,
            'payment' => $paymentLabels[$status] ?? $status,
            default   => $proposalLabels[$status] ?? $status,
        };
    }
}

if (!function_exists('statusBadgeClass')) {
    /**
     * Return the Bootstrap bg-* class name for a status slug.
     */
    function statusBadgeClass(string $status): string
    {
        $map = [
            'draft'                   => 'secondary',
            'reviewing'               => 'warning',
            'approved'                => 'success',
            'rejected'                => 'danger',
            'in_progress'             => 'primary',
            'completed'               => 'info',
            'closed'                  => 'secondary',
            'cancelled'               => 'danger',
            'pending'                 => 'warning',
            'paid'                    => 'success',
            'approved_with_condition' => 'info',
        ];

        return $map[$status] ?? 'secondary';
    }
}

if (!function_exists('statusBadge')) {
    /**
     * Return a Bootstrap <span class="badge"> for a status value.
     *
     * @param string $status   Status slug
     * @param string $context  'proposal' | 'project' | 'review' | 'payment'
     */
    function statusBadge(string $status, string $context = 'proposal'): string
    {
        $class = statusBadgeClass($status);
        $label = thai_status_label($status, $context);
        return '<span class="badge bg-' . $class . '">' . h($label) . '</span>';
    }
}

// ── Code Generation ───────────────────────────────────────────────────────────

if (!function_exists('generateCode')) {
    /**
     * Generate a formatted project/proposal code.
     *
     * Example: generateCode('PSU', 2025, 1)  → 'PSU-2568-001'
     */
    function generateCode(string $prefix, int $yearCE, int $sequence, int $padding = 3): string
    {
        $yearBE = $yearCE + 543;
        return sprintf('%s-%d-%0' . $padding . 'd', strtoupper($prefix), $yearBE, $sequence);
    }
}

// ── Input Sanitization ────────────────────────────────────────────────────────

if (!function_exists('sanitizeInput')) {
    /**
     * Trim whitespace and strip HTML tags from input.
     */
    function sanitizeInput($input): string
    {
        return trim(strip_tags((string) $input));
    }
}

// ── File Upload ───────────────────────────────────────────────────────────────

if (!function_exists('uploadFile')) {
    /**
     * Validate and move an uploaded file. Returns relative path on success.
     *
     * @param array    $file         Entry from $_FILES
     * @param string   $dest         Absolute destination directory (must be writable)
     * @param array    $allowedMimes Allowed MIME types, e.g. ['application/pdf']
     * @param int      $maxBytes     Maximum allowed file size in bytes
     * @return string                Relative path to the uploaded file
     * @throws \RuntimeException     On any validation or move failure
     */
    function uploadFile(array $file, string $dest, array $allowedMimes, int $maxBytes): string
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $msg = match ($file['error'] ?? -1) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'ไฟล์มีขนาดเกินกำหนด',
                UPLOAD_ERR_NO_FILE                        => 'ไม่ได้เลือกไฟล์',
                default                                   => 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์',
            };
            throw new \RuntimeException($msg);
        }

        if ($file['size'] > $maxBytes) {
            throw new \RuntimeException(
                'ไฟล์มีขนาดเกิน ' . number_format($maxBytes / 1048576, 0) . ' MB'
            );
        }

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowedMimes, true)) {
            throw new \RuntimeException(
                'ประเภทไฟล์ไม่ได้รับอนุญาต (ตรวจพบ: ' . $mimeType . ')'
            );
        }

        if (!is_dir($dest) && !mkdir($dest, 0755, true)) {
            throw new \RuntimeException('ไม่สามารถสร้างโฟลเดอร์ปลายทางได้');
        }

        $extension  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $safeBase   = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
        $uniqueName = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '_' . $safeBase . '.' . $extension;
        $targetPath = rtrim($dest, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $uniqueName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new \RuntimeException('ไม่สามารถบันทึกไฟล์ได้');
        }

        // Return a path relative to the document root (best-effort)
        $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', DIRECTORY_SEPARATOR);
        if ($docRoot !== '' && str_starts_with($targetPath, $docRoot)) {
            return substr($targetPath, strlen($docRoot));
        }

        return $targetPath;
    }
}

// ── Pagination HTML ───────────────────────────────────────────────────────────

if (!function_exists('generatePaginationLinks')) {
    /**
     * Generate Bootstrap 5 pagination HTML.
     *
     * @param int    $current  Current page number (1-based)
     * @param int    $total    Total number of pages
     * @param string $baseUrl  Base URL (without page param)
     */
    function generatePaginationLinks(int $current, int $total, string $baseUrl): string
    {
        if ($total <= 1) {
            return '';
        }

        $window  = 2;
        $pageUrl = fn(int $p): string =>
            $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . 'page=' . $p;

        $html = '<nav aria-label="การนำทางหน้า"><ul class="pagination justify-content-center flex-wrap">';

        // Previous
        if ($current > 1) {
            $html .= '<li class="page-item"><a class="page-link" href="' . h($pageUrl($current - 1)) . '" aria-label="ก่อนหน้า">&laquo;</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
        }

        // First page + ellipsis
        if ($current > $window + 1) {
            $html .= '<li class="page-item"><a class="page-link" href="' . h($pageUrl(1)) . '">1</a></li>';
            if ($current > $window + 2) {
                $html .= '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
            }
        }

        // Window around current
        $start = max(1, $current - $window);
        $end   = min($total, $current + $window);

        for ($i = $start; $i <= $end; $i++) {
            if ($i === $current) {
                $html .= '<li class="page-item active" aria-current="page"><span class="page-link">' . $i . '</span></li>';
            } else {
                $html .= '<li class="page-item"><a class="page-link" href="' . h($pageUrl($i)) . '">' . $i . '</a></li>';
            }
        }

        // Last page + ellipsis
        if ($current < $total - $window) {
            if ($current < $total - $window - 1) {
                $html .= '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
            }
            $html .= '<li class="page-item"><a class="page-link" href="' . h($pageUrl($total)) . '">' . $total . '</a></li>';
        }

        // Next
        if ($current < $total) {
            $html .= '<li class="page-item"><a class="page-link" href="' . h($pageUrl($current + 1)) . '" aria-label="ถัดไป">&raquo;</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';
        }

        $html .= '</ul></nav>';
        return $html;
    }
}
