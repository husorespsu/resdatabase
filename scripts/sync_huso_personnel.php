<?php

/**
 * HUSO Personnel Scraper
 *
 * Scrapes academic staff (บุคลากรสายวิชาการ) from https://huso.psu.ac.th/personnel/
 * and populates the huso_personnel table.
 *
 * Usage:
 *   php scripts/sync_huso_personnel.php
 *
 * Run from the project root directory.
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->safeLoad();
require_once BASE_PATH . '/config/database.php';

// ─── Config ────────────────────────────────────────────────────────────────

$BASE_URL = 'https://huso.psu.ac.th/personnel/';

// Department page IDs (undergraduate / academic departments)
$undergradDeptIds = [22, 23, 24, 25, 26, 27, 28, 29, 30, 32, 33, 34, 35, 37, 38, 39, 48];
$graduateDeptIds  = [40, 41];
$doctoralDeptIds  = [42, 49];

// ─── Helpers ───────────────────────────────────────────────────────────────

function fetchPage(string $url): string|false
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; PSU-Research-Bot/1.0)',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_ENCODING       => '',
    ]);
    $html = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err || $code !== 200) {
        fwrite(STDERR, "  [WARN] Failed to fetch {$url} (HTTP {$code}, err: {$err})\n");
        return false;
    }
    return $html;
}

function parseDepartmentName(string $html): string
{
    // Try to find department/page heading
    if (preg_match('/<h[12][^>]*>\s*(.*?)\s*<\/h[12]>/is', $html, $m)) {
        return strip_tags($m[1]);
    }
    return '';
}

/**
 * Parse staff names from a department page.
 * Returns array of ['full_name' => ..., 'position' => ..., 'email' => ..., 'department' => ...]
 */
function parseStaffFromPage(string $html, string $departmentName): array
{
    $staff = [];

    if (empty($html)) return $staff;

    // Convert encoding if needed (TIS-620 is Thai Windows encoding)
    if (!mb_check_encoding($html, 'UTF-8')) {
        $html = mb_convert_encoding($html, 'UTF-8', 'ISO-8859-1');
    }

    // Fix meta charset tag issues
    $html = preg_replace('/<meta[^>]+charset[^>]+>/i', '', $html);

    // Use DOMDocument to parse
    $dom = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    // Strategy 1: Look for profile cards (common Bootstrap card structure)
    // Try to find person name elements — typically in h4, h5, h6 or strong tags within card/profile divs
    $cardNodes = $xpath->query(
        '//*[contains(@class,"card") or contains(@class,"staff") or contains(@class,"person") or contains(@class,"profile")]'
    );

    $found = [];

    if ($cardNodes && $cardNodes->length > 0) {
        foreach ($cardNodes as $card) {
            $name = '';
            $position = '';
            $email = '';

            // Find name in heading tags within this card
            $headings = $xpath->query('.//h4|.//h5|.//h6|.//strong', $card);
            foreach ($headings as $h) {
                $text = trim($h->textContent);
                if (isThaiPersonName($text)) {
                    $name = cleanName($text);
                    break;
                }
            }

            // Find position/email in dl/dd or p tags
            $dds = $xpath->query('.//dd|.//p|.//small|.//span', $card);
            foreach ($dds as $dd) {
                $text = trim($dd->textContent);
                if (empty($position) && isAcademicPosition($text)) {
                    $position = cleanName($text);
                }
                if (empty($email) && str_contains($text, '@')) {
                    if (preg_match('/[\w.+-]+@[\w.-]+\.\w+/', $text, $em)) {
                        $email = $em[0];
                    }
                }
            }

            if ($name && !isset($found[$name])) {
                $found[$name] = true;
                $staff[] = [
                    'full_name'  => $name,
                    'position'   => $position,
                    'email'      => $email,
                    'department' => $departmentName,
                ];
            }
        }
    }

    // Strategy 2: Scan all h4/h5/h6 headings across the page that look like person names
    if (empty($staff)) {
        $headings = $xpath->query('//h4|//h5|//h6');
        foreach ($headings as $h) {
            $text = trim($h->textContent);
            if (isThaiPersonName($text) && !isset($found[$text])) {
                $found[$text] = true;
                $staff[] = [
                    'full_name'  => cleanName($text),
                    'position'   => '',
                    'email'      => '',
                    'department' => $departmentName,
                ];
            }
        }
    }

    return $staff;
}

/**
 * Check if text looks like a Thai person name with academic title.
 */
function isThaiPersonName(string $text): bool
{
    if (mb_strlen($text) < 5 || mb_strlen($text) > 200) return false;

    // Must contain Thai characters
    if (!preg_match('/[\x{0E00}-\x{0E7F}]/u', $text)) return false;

    // Common Thai academic prefixes / titles
    $titles = [
        'อาจารย์', 'ดร.', 'ผู้ช่วยศาสตราจารย์', 'รองศาสตราจารย์', 'ศาสตราจารย์',
        'ผศ.', 'รศ.', 'ศ.',
        'นาย', 'นาง', 'นางสาว',
    ];

    foreach ($titles as $title) {
        if (mb_strpos($text, $title) !== false) return true;
    }

    return false;
}

/**
 * Check if text is an academic/administrative position.
 */
function isAcademicPosition(string $text): bool
{
    if (mb_strlen($text) < 3 || mb_strlen($text) > 200) return false;
    $keywords = ['อาจารย์', 'ผู้ช่วยศาสตราจารย์', 'รองศาสตราจารย์', 'ศาสตราจารย์', 'ผศ.', 'รศ.', 'ดร.'];
    foreach ($keywords as $kw) {
        if (mb_strpos($text, $kw) !== false) return true;
    }
    return false;
}

/**
 * Clean up a name string.
 */
function cleanName(string $name): string
{
    $name = preg_replace('/\s+/', ' ', $name);
    $name = trim($name);
    // Remove trailing dots only if not part of Thai abbreviation
    return $name;
}

// ─── Main ──────────────────────────────────────────────────────────────────

echo "=== HUSO Personnel Sync ===\n";
echo "Started: " . date('Y-m-d H:i:s') . "\n\n";

$pdo = DatabaseConfig::getInstance();
$allStaff = [];

$deptSets = [
    ['type' => 'undergraduate', 'ids' => $undergradDeptIds, 'file' => 'index_staff_dept.php'],
    ['type' => 'graduate',      'ids' => $graduateDeptIds,  'file' => 'index_staff_dept_ma.php'],
    ['type' => 'doctoral',      'ids' => $doctoralDeptIds,  'file' => 'index_staff_dept_phd.php'],
];

foreach ($deptSets as $set) {
    echo "[{$set['type']}]\n";
    foreach ($set['ids'] as $deptId) {
        $url = $BASE_URL . $set['file'] . '?id=' . $deptId;
        echo "  Fetching dept ID {$deptId}: {$url}\n";

        $html = fetchPage($url);
        if ($html === false) {
            echo "  -> Skipped (fetch failed)\n";
            continue;
        }

        $deptName = parseDepartmentName($html);
        $staff    = parseStaffFromPage($html, $deptName);

        echo "  -> Dept: " . ($deptName ?: '(unknown)') . ", Found: " . count($staff) . " staff\n";

        foreach ($staff as $s) {
            $allStaff[] = array_merge($s, ['dept_id' => $deptId, 'dept_type' => $set['type']]);
        }

        usleep(300_000); // 300ms delay between requests
    }
}

echo "\nTotal staff found: " . count($allStaff) . "\n";

if (empty($allStaff)) {
    echo "[WARN] No staff found. The website structure may have changed.\n";
    echo "       You can also add staff manually via the database.\n";
    exit(1);
}

// ─── Upsert into DB ────────────────────────────────────────────────────────

echo "\nInserting into database...\n";

// Clear existing data
$pdo->exec("TRUNCATE TABLE huso_personnel");

$stmt = $pdo->prepare(
    "INSERT INTO huso_personnel (full_name, department, position, email, dept_id, dept_type)
     VALUES (:full_name, :department, :position, :email, :dept_id, :dept_type)"
);

$inserted = 0;
$seenNames = [];

foreach ($allStaff as $s) {
    if (empty($s['full_name'])) continue;
    $key = $s['full_name'] . '|' . ($s['dept_id'] ?? '');
    if (isset($seenNames[$key])) continue;
    $seenNames[$key] = true;

    try {
        $stmt->execute([
            ':full_name'  => $s['full_name'],
            ':department' => $s['department'] ?: null,
            ':position'   => $s['position']   ?: null,
            ':email'      => $s['email']       ?: null,
            ':dept_id'    => $s['dept_id']     ?? null,
            ':dept_type'  => $s['dept_type']   ?? null,
        ]);
        $inserted++;
    } catch (\Throwable $e) {
        fwrite(STDERR, "  [ERROR] Insert failed for '{$s['full_name']}': " . $e->getMessage() . "\n");
    }
}

echo "Inserted: {$inserted} records\n";
echo "\nDone: " . date('Y-m-d H:i:s') . "\n";
