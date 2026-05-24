<?php
/**
 * migrate_to_neon.php
 *
 * Migrates data from local MySQL → Neon PostgreSQL.
 * Run once from project root:
 *   php scripts/migrate_to_neon.php
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->safeLoad();

// ── Connections ───────────────────────────────────────────────────────────

echo "Connecting to MySQL (local)...\n";
$mysql = new PDO(
    'mysql:host=127.0.0.1;dbname=research_management;charset=utf8mb4',
    'root', '',
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    ]
);
echo "  OK\n";

echo "Connecting to Neon (PostgreSQL)...\n";
$neon = new PDO(
    'pgsql:host=ep-super-violet-aokxcrb4-pooler.c-2.ap-southeast-1.aws.neon.tech;port=5432;dbname=neondb;sslmode=require',
    'neondb_owner',
    'npg_uc0UHz7BfFaC',
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
);
echo "  OK\n\n";

// ── Create Schema ─────────────────────────────────────────────────────────

echo "Creating PostgreSQL schema...\n";
$schema = file_get_contents(BASE_PATH . '/database/neon_schema.sql');

// Split on semicolons, skipping empty statements
$statements = array_filter(
    array_map('trim', explode(';', $schema)),
    fn(string $s) => $s !== ''
);

foreach ($statements as $sql) {
    try {
        $neon->exec($sql);
    } catch (PDOException $e) {
        // Ignore "already exists" errors
        if (!str_contains($e->getMessage(), 'already exists')) {
            echo "  [WARN] " . substr($sql, 0, 80) . "...\n  -> " . $e->getMessage() . "\n";
        }
    }
}
echo "  Schema ready.\n\n";

// ── Helpers ───────────────────────────────────────────────────────────────

function toJson(?string $v): ?string
{
    if ($v === null || $v === '') return null;
    $decoded = json_decode($v, true);
    if (json_last_error() !== JSON_ERROR_NONE) return null;
    return json_encode($decoded, JSON_UNESCAPED_UNICODE);
}

function toBool(mixed $v): bool
{
    return (bool)(int)$v;
}

function toTs(?string $v): ?string
{
    return ($v === null || $v === '0000-00-00 00:00:00' || $v === '') ? null : $v;
}

function toDate(?string $v): ?string
{
    return ($v === null || $v === '0000-00-00' || $v === '') ? null : $v;
}

function insertMany(PDO $pg, string $table, array $rows, array $cols): void
{
    if (empty($rows)) { echo "  (no rows)\n"; return; }
    $placeholders = '(' . implode(',', array_fill(0, count($cols), '?')) . ')';
    $sql = sprintf(
        'INSERT INTO %s (%s) VALUES %s ON CONFLICT DO NOTHING',
        $table,
        implode(',', $cols),
        $placeholders
    );
    $stmt = $pg->prepare($sql);
    foreach ($rows as $row) {
        $stmt->execute(array_values($row));
    }
    echo "  Inserted: " . count($rows) . " rows\n";
}

function resetSequence(PDO $pg, string $table, string $col = 'id'): void
{
    $pg->exec("SELECT setval(pg_get_serial_sequence('{$table}', '{$col}'),
               COALESCE((SELECT MAX({$col}) FROM {$table}), 0) + 1, false)");
}

// ── Migrate Tables (order respects FK dependencies) ───────────────────────

$tables = [
    // [mysql_query, pg_table, transform_fn]
    'users' => function () use ($mysql, $neon): void {
        echo "[users]\n";
        $rows = $mysql->query("SELECT * FROM users")->fetchAll();
        $cols = ['id','google_id','username','password','email','name','avatar',
                 'role','department','phone','is_active','last_login','created_at','updated_at'];
        $data = array_map(fn($r) => [
            'id'         => (int)$r['id'],
            'google_id'  => $r['google_id'],
            'username'   => $r['username'],
            'password'   => $r['password'],
            'email'      => $r['email'],
            'name'       => $r['name'],
            'avatar'     => $r['avatar'],
            'role'       => $r['role'],
            'department' => $r['department'],
            'phone'      => $r['phone'],
            'is_active'  => toBool($r['is_active']) ? 'true' : 'false',
            'last_login' => toTs($r['last_login']),
            'created_at' => toTs($r['created_at']) ?? date('Y-m-d H:i:s'),
            'updated_at' => toTs($r['updated_at']) ?? date('Y-m-d H:i:s'),
        ], $rows);
        insertMany($neon, 'users', $data, $cols);
        resetSequence($neon, 'users');
    },

    'funding_sources' => function () use ($mysql, $neon): void {
        echo "[funding_sources]\n";
        $rows = $mysql->query("SELECT * FROM funding_sources")->fetchAll();
        $cols = ['id','name','type','organization','description','budget_year',
                 'is_active','created_at','updated_at'];
        $data = array_map(fn($r) => [
            'id'           => (int)$r['id'],
            'name'         => $r['name'],
            'type'         => $r['type'],
            'organization' => $r['organization'],
            'description'  => $r['description'],
            'budget_year'  => $r['budget_year'] ? (int)$r['budget_year'] : null,
            'is_active'    => toBool($r['is_active']) ? 'true' : 'false',
            'created_at'   => toTs($r['created_at']) ?? date('Y-m-d H:i:s'),
            'updated_at'   => toTs($r['updated_at']) ?? date('Y-m-d H:i:s'),
        ], $rows);
        insertMany($neon, 'funding_sources', $data, $cols);
        resetSequence($neon, 'funding_sources');
    },

    'fields_of_study' => function () use ($mysql, $neon): void {
        echo "[fields_of_study]\n";
        $rows = $mysql->query("SELECT * FROM fields_of_study")->fetchAll();
        $cols = ['id','code','name_th','name_en','faculty','created_at'];
        $data = array_map(fn($r) => [
            'id'         => (int)$r['id'],
            'code'       => $r['code'],
            'name_th'    => $r['name_th'],
            'name_en'    => $r['name_en'],
            'faculty'    => $r['faculty'],
            'created_at' => toTs($r['created_at']) ?? date('Y-m-d H:i:s'),
        ], $rows);
        insertMany($neon, 'fields_of_study', $data, $cols);
        resetSequence($neon, 'fields_of_study');
    },

    'expert_reviewers' => function () use ($mysql, $neon): void {
        echo "[expert_reviewers]\n";
        $rows = $mysql->query("SELECT * FROM expert_reviewers")->fetchAll();
        $cols = ['id','title','first_name','last_name','expertise','institution','position',
                 'email','phone','bank_name','bank_account','bank_branch','id_card_number',
                 'address','is_active','created_at','updated_at'];
        $data = array_map(fn($r) => [
            'id'             => (int)$r['id'],
            'title'          => $r['title'],
            'first_name'     => $r['first_name'],
            'last_name'      => $r['last_name'],
            'expertise'      => $r['expertise'],
            'institution'    => $r['institution'],
            'position'       => $r['position'],
            'email'          => $r['email'],
            'phone'          => $r['phone'],
            'bank_name'      => $r['bank_name'],
            'bank_account'   => $r['bank_account'],
            'bank_branch'    => $r['bank_branch'],
            'id_card_number' => $r['id_card_number'],
            'address'        => $r['address'],
            'is_active'      => toBool($r['is_active']) ? 'true' : 'false',
            'created_at'     => toTs($r['created_at']) ?? date('Y-m-d H:i:s'),
            'updated_at'     => toTs($r['updated_at']) ?? date('Y-m-d H:i:s'),
        ], $rows);
        insertMany($neon, 'expert_reviewers', $data, $cols);
        resetSequence($neon, 'expert_reviewers');
    },

    'research_proposals' => function () use ($mysql, $neon): void {
        echo "[research_proposals]\n";
        $rows = $mysql->query("SELECT * FROM research_proposals")->fetchAll();
        $cols = ['id','proposal_code','title_th','title_en','principal_investigator_id',
                 'pi_name','co_investigators','field_of_study_id','funding_source_id',
                 'budget_requested','budget_year','abstract','objectives','methodology',
                 'start_date','end_date','status','attachment_path','submitted_at',
                 'approved_by','approved_at','notes','created_by','created_at','updated_at'];
        $data = array_map(fn($r) => [
            'id'                        => (int)$r['id'],
            'proposal_code'             => $r['proposal_code'],
            'title_th'                  => $r['title_th'],
            'title_en'                  => $r['title_en'],
            'principal_investigator_id' => $r['principal_investigator_id'] ? (int)$r['principal_investigator_id'] : null,
            'pi_name'                   => $r['pi_name'],
            'co_investigators'          => toJson($r['co_investigators']),
            'field_of_study_id'         => $r['field_of_study_id']  ? (int)$r['field_of_study_id']  : null,
            'funding_source_id'         => $r['funding_source_id']  ? (int)$r['funding_source_id']  : null,
            'budget_requested'          => $r['budget_requested'],
            'budget_year'               => (int)$r['budget_year'],
            'abstract'                  => $r['abstract'],
            'objectives'                => $r['objectives'],
            'methodology'               => $r['methodology'],
            'start_date'                => toDate($r['start_date']),
            'end_date'                  => toDate($r['end_date']),
            'status'                    => $r['status'],
            'attachment_path'           => $r['attachment_path'],
            'submitted_at'              => toTs($r['submitted_at']),
            'approved_by'               => $r['approved_by'] ? (int)$r['approved_by'] : null,
            'approved_at'               => toTs($r['approved_at']),
            'notes'                     => $r['notes'],
            'created_by'                => (int)$r['created_by'],
            'created_at'                => toTs($r['created_at']) ?? date('Y-m-d H:i:s'),
            'updated_at'                => toTs($r['updated_at']) ?? date('Y-m-d H:i:s'),
        ], $rows);
        insertMany($neon, 'research_proposals', $data, $cols);
        resetSequence($neon, 'research_proposals');
    },

    'research_projects' => function () use ($mysql, $neon): void {
        echo "[research_projects]\n";
        $rows = $mysql->query("SELECT * FROM research_projects")->fetchAll();
        $cols = ['id','proposal_id','project_code','status','approved_date','approved_budget',
                 'approved_by','contract_number','contract_date','actual_start_date',
                 'actual_end_date','progress_percentage','final_report_submitted_at',
                 'notes','created_at','updated_at'];
        $data = array_map(fn($r) => [
            'id'                        => (int)$r['id'],
            'proposal_id'               => (int)$r['proposal_id'],
            'project_code'              => $r['project_code'],
            'status'                    => $r['status'],
            'approved_date'             => toDate($r['approved_date']),
            'approved_budget'           => $r['approved_budget'],
            'approved_by'               => $r['approved_by'] ? (int)$r['approved_by'] : null,
            'contract_number'           => $r['contract_number'],
            'contract_date'             => toDate($r['contract_date']),
            'actual_start_date'         => toDate($r['actual_start_date']),
            'actual_end_date'           => toDate($r['actual_end_date']),
            'progress_percentage'       => (int)$r['progress_percentage'],
            'final_report_submitted_at' => toTs($r['final_report_submitted_at']),
            'notes'                     => $r['notes'],
            'created_at'                => toTs($r['created_at']) ?? date('Y-m-d H:i:s'),
            'updated_at'                => toTs($r['updated_at']) ?? date('Y-m-d H:i:s'),
        ], $rows);
        insertMany($neon, 'research_projects', $data, $cols);
        resetSequence($neon, 'research_projects');
    },

    'proposal_reviews' => function () use ($mysql, $neon): void {
        echo "[proposal_reviews]\n";
        $rows = $mysql->query("SELECT * FROM proposal_reviews")->fetchAll();
        $cols = ['id','proposal_id','reviewer_id','assigned_date','due_date','received_date',
                 'invitation_letter_number','invitation_sent_date','invitation_file_path',
                 'review_result','review_score','review_comments','payment_amount',
                 'payment_date','payment_status','payment_reference','reminder_sent_count',
                 'last_reminder_sent_at','created_by','created_at','updated_at'];
        $data = array_map(fn($r) => [
            'id'                       => (int)$r['id'],
            'proposal_id'              => (int)$r['proposal_id'],
            'reviewer_id'              => (int)$r['reviewer_id'],
            'assigned_date'            => toDate($r['assigned_date']),
            'due_date'                 => toDate($r['due_date']),
            'received_date'            => toDate($r['received_date']),
            'invitation_letter_number' => $r['invitation_letter_number'],
            'invitation_sent_date'     => toDate($r['invitation_sent_date']),
            'invitation_file_path'     => $r['invitation_file_path'],
            'review_result'            => $r['review_result'],
            'review_score'             => $r['review_score'],
            'review_comments'          => $r['review_comments'],
            'payment_amount'           => $r['payment_amount'],
            'payment_date'             => toDate($r['payment_date']),
            'payment_status'           => $r['payment_status'],
            'payment_reference'        => $r['payment_reference'],
            'reminder_sent_count'      => (int)$r['reminder_sent_count'],
            'last_reminder_sent_at'    => toTs($r['last_reminder_sent_at']),
            'created_by'               => (int)$r['created_by'],
            'created_at'               => toTs($r['created_at']) ?? date('Y-m-d H:i:s'),
            'updated_at'               => toTs($r['updated_at']) ?? date('Y-m-d H:i:s'),
        ], $rows);
        insertMany($neon, 'proposal_reviews', $data, $cols);
        resetSequence($neon, 'proposal_reviews');
    },

    'notifications' => function () use ($mysql, $neon): void {
        echo "[notifications]\n";
        $rows = $mysql->query("SELECT * FROM notifications")->fetchAll();
        $cols = ['id','user_id','type','title','message','related_table',
                 'related_id','is_read','created_at'];
        $data = array_map(fn($r) => [
            'id'            => (int)$r['id'],
            'user_id'       => (int)$r['user_id'],
            'type'          => $r['type'],
            'title'         => $r['title'],
            'message'       => $r['message'],
            'related_table' => $r['related_table'],
            'related_id'    => $r['related_id'] ? (int)$r['related_id'] : null,
            'is_read'       => toBool($r['is_read']) ? 'true' : 'false',
            'created_at'    => toTs($r['created_at']) ?? date('Y-m-d H:i:s'),
        ], $rows);
        insertMany($neon, 'notifications', $data, $cols);
        resetSequence($neon, 'notifications');
    },

    'activity_logs' => function () use ($mysql, $neon): void {
        echo "[activity_logs]\n";
        $rows = $mysql->query("SELECT * FROM activity_logs")->fetchAll();
        $cols = ['id','user_id','action','table_name','record_id',
                 'old_value','new_value','ip_address','created_at'];
        $data = array_map(fn($r) => [
            'id'         => (int)$r['id'],
            'user_id'    => $r['user_id'] ? (int)$r['user_id'] : null,
            'action'     => $r['action'],
            'table_name' => $r['table_name'],
            'record_id'  => $r['record_id'] ? (int)$r['record_id'] : null,
            'old_value'  => toJson($r['old_value']),
            'new_value'  => toJson($r['new_value']),
            'ip_address' => $r['ip_address'],
            'created_at' => toTs($r['created_at']) ?? date('Y-m-d H:i:s'),
        ], $rows);
        insertMany($neon, 'activity_logs', $data, $cols);
        resetSequence($neon, 'activity_logs');
    },

    'huso_personnel' => function () use ($mysql, $neon): void {
        echo "[huso_personnel]\n";
        $rows = $mysql->query("SELECT * FROM huso_personnel")->fetchAll();
        $cols = ['id','full_name','department','position','email','dept_id','dept_type','created_at','updated_at'];
        $data = array_map(fn($r) => [
            'id'         => (int)$r['id'],
            'full_name'  => $r['full_name'],
            'department' => $r['department'],
            'position'   => $r['position'],
            'email'      => $r['email'],
            'dept_id'    => $r['dept_id'] ? (int)$r['dept_id'] : null,
            'dept_type'  => $r['dept_type'],
            'created_at' => toTs($r['created_at']) ?? date('Y-m-d H:i:s'),
            'updated_at' => toTs($r['updated_at']) ?? date('Y-m-d H:i:s'),
        ], $rows);
        insertMany($neon, 'huso_personnel', $data, $cols);
        resetSequence($neon, 'huso_personnel');
    },
];

echo "Migrating data...\n";
foreach ($tables as $fn) {
    $fn();
}

echo "\n=== Migration complete! ===\n";
echo "Neon DB: neondb @ ep-super-violet-aokxcrb4-pooler.c-2.ap-southeast-1.aws.neon.tech\n";
