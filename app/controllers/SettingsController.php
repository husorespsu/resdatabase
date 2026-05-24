<?php

class SettingsController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    // =========================================================================
    // FUNDING SOURCES
    // =========================================================================

    /**
     * List all funding sources (GET /settings/funding).
     */
    public function funding(): void
    {
        $db      = DatabaseConfig::getInstance();
        $sources = $db->query("
            SELECT fs.*,
                   (SELECT COUNT(*) FROM research_proposals p WHERE p.funding_source_id = fs.id) AS proposal_count
            FROM funding_sources fs
            ORDER BY fs.name ASC
        ")->fetchAll();

        $this->render('settings/funding', [
            'pageTitle'      => 'จัดการแหล่งทุน',
            'fundingSources' => $sources,
            'csrfToken'      => $_SESSION['csrf_token'] ?? '',
        ]);
    }

    /**
     * Create a new funding source (POST /settings/funding/store).
     */
    public function storeFunding(): void
    {
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flashRedirect('error', 'การตรวจสอบความปลอดภัยล้มเหลว', '/settings/funding');
        }

        $name = trim($_POST['name'] ?? '');
        $type = trim($_POST['type'] ?? '');

        if ($name === '') {
            $this->flashRedirect('error', 'กรุณากรอกชื่อแหล่งทุน', '/settings/funding');
        }

        if (!in_array($type, ['internal', 'external'], true)) {
            $this->flashRedirect('error', 'กรุณาเลือกประเภทแหล่งทุน', '/settings/funding');
        }

        $budgetYear = trim($_POST['budget_year'] ?? '');
        $db   = DatabaseConfig::getInstance();
        $stmt = $db->prepare("
            INSERT INTO funding_sources (name, type, organization, description, budget_year, is_active, created_at)
            VALUES (:name, :type, :organization, :description, :budget_year, :is_active, NOW())
        ");
        $stmt->execute([
            ':name'         => sanitizeInput($name),
            ':type'         => $type,
            ':organization' => sanitizeInput($_POST['organization'] ?? ''),
            ':description'  => sanitizeInput($_POST['description']  ?? ''),
            ':budget_year'  => $budgetYear !== '' ? $budgetYear : null,
            ':is_active'    => isset($_POST['is_active']) ? 1 : 0,
        ]);

        $this->flashRedirect('success', "เพิ่มแหล่งทุน \"{$name}\" เรียบร้อยแล้ว", '/settings/funding');
    }

    /**
     * Update a funding source (POST /settings/funding/{id}/update).
     */
    public function updateFunding(array $params): void
    {
        $id = (int)($params['id'] ?? 0);

        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flashRedirect('error', 'การตรวจสอบความปลอดภัยล้มเหลว', '/settings/funding');
        }

        $name = trim($_POST['name'] ?? '');
        $type = trim($_POST['type'] ?? '');

        if ($name === '') {
            $this->flashRedirect('error', 'กรุณากรอกชื่อแหล่งทุน', '/settings/funding');
        }

        if (!in_array($type, ['internal', 'external'], true)) {
            $this->flashRedirect('error', 'กรุณาเลือกประเภทแหล่งทุน', '/settings/funding');
        }

        $budgetYear = trim($_POST['budget_year'] ?? '');
        $db   = DatabaseConfig::getInstance();
        $stmt = $db->prepare("
            UPDATE funding_sources
            SET name = :name, type = :type, organization = :organization,
                description = :description, budget_year = :budget_year,
                is_active = :is_active, updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':name'         => sanitizeInput($name),
            ':type'         => $type,
            ':organization' => sanitizeInput($_POST['organization'] ?? ''),
            ':description'  => sanitizeInput($_POST['description']  ?? ''),
            ':budget_year'  => $budgetYear !== '' ? $budgetYear : null,
            ':is_active'    => isset($_POST['is_active']) ? 1 : 0,
            ':id'           => $id,
        ]);

        $this->flashRedirect('success', "แก้ไขแหล่งทุน \"{$name}\" เรียบร้อยแล้ว", '/settings/funding');
    }

    /**
     * Delete a funding source (POST /settings/funding/{id}/delete).
     */
    public function deleteFunding(array $params): void
    {
        $id = (int)($params['id'] ?? 0);

        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flashRedirect('error', 'การตรวจสอบความปลอดภัยล้มเหลว', '/settings/funding');
        }

        $db    = DatabaseConfig::getInstance();
        $check = $db->prepare("SELECT COUNT(*) FROM research_proposals WHERE funding_source_id = :id");
        $check->execute([':id' => $id]);
        $linked = (int)$check->fetchColumn();

        if ($linked > 0) {
            $this->flashRedirect('error', "ไม่สามารถลบได้ เนื่องจากมีข้อเสนอโครงการที่ใช้งานอยู่ {$linked} รายการ", '/settings/funding');
        }

        $stmt = $db->prepare("DELETE FROM funding_sources WHERE id = :id");
        $stmt->execute([':id' => $id]);

        $this->flashRedirect('success', 'ลบแหล่งทุนเรียบร้อยแล้ว', '/settings/funding');
    }

    // =========================================================================
    // FIELDS OF STUDY
    // =========================================================================

    /**
     * List all fields of study (GET /settings/fields).
     */
    public function fields(): void
    {
        $db     = DatabaseConfig::getInstance();
        $fields = $db->query("
            SELECT fos.*,
                   (SELECT COUNT(*) FROM research_proposals p WHERE p.field_of_study_id = fos.id) AS proposal_count
            FROM fields_of_study fos
            ORDER BY fos.faculty ASC, fos.name_th ASC
        ")->fetchAll();

        // Unique, non-empty faculties for the filter bar
        $faculties = array_values(array_unique(array_filter(
            array_column($fields, 'faculty')
        )));
        sort($faculties);

        $this->render('settings/fields', [
            'pageTitle' => 'จัดการสาขาวิชา',
            'fields'    => $fields,
            'faculties' => $faculties,
            'csrfToken' => $_SESSION['csrf_token'] ?? '',
        ]);
    }

    /**
     * Create a new field of study (POST /settings/fields/store).
     */
    public function storeField(): void
    {
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flashRedirect('error', 'การตรวจสอบความปลอดภัยล้มเหลว', '/settings/fields');
        }

        $code   = trim($_POST['code']    ?? '');
        $nameTh = trim($_POST['name_th'] ?? '');

        if ($code === '' || $nameTh === '') {
            $this->flashRedirect('error', 'กรุณากรอกรหัสและชื่อสาขาวิชา (ภาษาไทย)', '/settings/fields');
        }

        $db   = DatabaseConfig::getInstance();
        $stmt = $db->prepare("
            INSERT INTO fields_of_study (code, name_th, name_en, faculty, created_at)
            VALUES (:code, :name_th, :name_en, :faculty, NOW())
        ");
        $stmt->execute([
            ':code'    => sanitizeInput($code),
            ':name_th' => sanitizeInput($nameTh),
            ':name_en' => sanitizeInput($_POST['name_en'] ?? ''),
            ':faculty' => sanitizeInput($_POST['faculty'] ?? ''),
        ]);

        $this->flashRedirect('success', "เพิ่มสาขาวิชา \"{$nameTh}\" เรียบร้อยแล้ว", '/settings/fields');
    }

    /**
     * Update a field of study (POST /settings/fields/{id}/update).
     */
    public function updateField(array $params): void
    {
        $id = (int)($params['id'] ?? 0);

        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flashRedirect('error', 'การตรวจสอบความปลอดภัยล้มเหลว', '/settings/fields');
        }

        $code   = trim($_POST['code']    ?? '');
        $nameTh = trim($_POST['name_th'] ?? '');

        if ($code === '' || $nameTh === '') {
            $this->flashRedirect('error', 'กรุณากรอกรหัสและชื่อสาขาวิชา (ภาษาไทย)', '/settings/fields');
        }

        $db   = DatabaseConfig::getInstance();
        $stmt = $db->prepare("
            UPDATE fields_of_study
            SET code = :code, name_th = :name_th, name_en = :name_en, faculty = :faculty
            WHERE id = :id
        ");
        $stmt->execute([
            ':code'    => sanitizeInput($code),
            ':name_th' => sanitizeInput($nameTh),
            ':name_en' => sanitizeInput($_POST['name_en'] ?? ''),
            ':faculty' => sanitizeInput($_POST['faculty'] ?? ''),
            ':id'      => $id,
        ]);

        $this->flashRedirect('success', "แก้ไขสาขาวิชา \"{$nameTh}\" เรียบร้อยแล้ว", '/settings/fields');
    }

    /**
     * Delete a field of study (POST /settings/fields/{id}/delete).
     */
    public function deleteField(array $params): void
    {
        $id = (int)($params['id'] ?? 0);

        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flashRedirect('error', 'การตรวจสอบความปลอดภัยล้มเหลว', '/settings/fields');
        }

        $db    = DatabaseConfig::getInstance();
        $check = $db->prepare("SELECT COUNT(*) FROM research_proposals WHERE field_of_study_id = :id");
        $check->execute([':id' => $id]);
        $linked = (int)$check->fetchColumn();

        if ($linked > 0) {
            $this->flashRedirect('error', "ไม่สามารถลบได้ เนื่องจากมีข้อเสนอโครงการที่ใช้งานอยู่ {$linked} รายการ", '/settings/fields');
        }

        $stmt = $db->prepare("DELETE FROM fields_of_study WHERE id = :id");
        $stmt->execute([':id' => $id]);

        $this->flashRedirect('success', 'ลบสาขาวิชาเรียบร้อยแล้ว', '/settings/fields');
    }

    // =========================================================================
    // USER MANAGEMENT
    // =========================================================================

    /**
     * List all users with stats (GET /settings/users).
     */
    public function users(): void
    {
        $users = (new User())->getAllWithStats();

        // Pre-compute which users have related records (to disable hard-delete)
        $pdo = DatabaseConfig::getInstance();
        $userIds = array_column($users, 'id');
        $relatedUserIds = [];

        if (!empty($userIds)) {
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));

            $stmt = $pdo->prepare(
                "SELECT DISTINCT u FROM (
                    SELECT principal_investigator_id AS u FROM research_proposals WHERE principal_investigator_id IN ({$placeholders})
                    UNION
                    SELECT created_by FROM research_proposals WHERE created_by IN ({$placeholders})
                    UNION
                    SELECT created_by FROM proposal_reviews WHERE created_by IN ({$placeholders})
                ) t WHERE u IS NOT NULL"
            );
            $stmt->execute(array_merge($userIds, $userIds, $userIds));
            $relatedUserIds = array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'u');
        }

        $this->render('settings/users', [
            'pageTitle'      => 'จัดการผู้ใช้งาน',
            'users'          => $users,
            'currentUser'    => $this->getCurrentUser(),
            'csrfToken'      => $_SESSION['csrf_token'] ?? '',
            'relatedUserIds' => array_map('intval', $relatedUserIds),
        ]);
    }

    /**
     * Create a new local user (POST /settings/users/store).
     */
    public function createUser(): void
    {
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flashRedirect('error', 'CSRF validation failed', '/settings/users');
        }

        $actor = $this->getCurrentUser();

        $name     = trim($_POST['name']     ?? '');
        $email    = trim($_POST['email']    ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password']      ?? '';
        $role     = trim($_POST['role']     ?? 'executive');

        // Admin cannot create superadmin
        if ($actor['role'] === 'admin' && $role === 'superadmin') {
            $this->flashRedirect('error', 'ไม่มีสิทธิ์กำหนดบทบาทผู้ดูแลระบบสูงสุด', '/settings/users');
        }

        $allowed = ['superadmin', 'admin', 'executive'];
        if (!in_array($role, $allowed, true) || empty($name) || empty($email)) {
            $this->flashRedirect('error', 'ข้อมูลไม่ครบถ้วน กรุณาตรวจสอบอีกครั้ง', '/settings/users');
        }

        $userModel = new User();

        // Check duplicate email
        if ($userModel->findByEmail($email)) {
            $this->flashRedirect('error', "อีเมล {$email} มีในระบบแล้ว", '/settings/users');
        }

        $data = [
            'name'       => $name,
            'email'      => $email,
            'role'       => $role,
            'is_active'  => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (!empty($username)) $data['username'] = $username;
        if (!empty($password)) $data['password'] = password_hash($password, PASSWORD_DEFAULT);

        $userModel->create($data);

        $this->setFlash('success', "เพิ่มผู้ใช้ {$name} เรียบร้อยแล้ว");
        $this->redirect('/settings/users');
    }

    /**
     * Update a user's role (POST /settings/users/{id}/role).
     */
    public function updateRole(array $params): void
    {
        $id = (int)($params['id'] ?? 0);

        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->json(['success' => false, 'message' => 'CSRF validation failed'], 403);
        }

        $actor   = $this->getCurrentUser();
        $actorId = (int)($actor['id'] ?? 0);

        if ($id === $actorId) {
            $this->json(['success' => false, 'message' => 'ไม่สามารถเปลี่ยนบทบาทของตนเองได้'], 403);
        }

        $role    = trim($_POST['role'] ?? '');
        $allowed = ['admin', 'executive', 'superadmin'];

        // Admin cannot assign superadmin
        if ($actor['role'] === 'admin') {
            $allowed = ['admin', 'executive'];
        }

        if (!in_array($role, $allowed, true)) {
            $this->json(['success' => false, 'message' => 'บทบาทที่เลือกไม่ถูกต้องหรือไม่มีสิทธิ์']);
        }

        (new User())->updateRole($id, $role);
        $this->json(['success' => true, 'message' => 'เปลี่ยนบทบาทเรียบร้อยแล้ว']);
    }

    /**
     * Toggle a user's active status (POST /settings/users/{id}/toggle).
     */
    public function toggleUser(array $params): void
    {
        $id = (int)($params['id'] ?? 0);

        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->json(['success' => false, 'message' => 'CSRF validation failed'], 403);
        }

        $actor = $this->getCurrentUser();
        if ($id === (int)($actor['id'] ?? 0)) {
            $this->json(['success' => false, 'message' => 'ไม่สามารถปิดใช้งานบัญชีของตนเองได้'], 403);
        }

        $userModel = new User();
        $target    = $userModel->findById($id);

        // Admin cannot toggle superadmin
        if ($actor['role'] === 'admin' && ($target['role'] ?? '') === 'superadmin') {
            $this->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการบัญชีผู้ดูแลระบบสูงสุด'], 403);
        }

        $userModel->toggleActive($id);
        $updated  = $userModel->findById($id);
        $isActive = (bool)($updated['is_active'] ?? false);

        $this->json([
            'success'   => true,
            'is_active' => (int)$isActive,
            'message'   => ($isActive ? 'เปิดใช้งาน' : 'ปิดใช้งาน') . 'ผู้ใช้เรียบร้อยแล้ว',
        ]);
    }

    /**
     * Delete a user (POST /settings/users/{id}/delete).
     */
    public function deleteUser(array $params): void
    {
        $id = (int)($params['id'] ?? 0);

        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flashRedirect('error', 'การตรวจสอบความปลอดภัยล้มเหลว', '/settings/users');
        }

        $actor     = $this->getCurrentUser();
        $userModel = new User();
        $target    = $userModel->findById($id);

        if (!$target) {
            $this->flashRedirect('error', 'ไม่พบผู้ใช้งาน', '/settings/users');
        }

        if ($id === (int)($actor['id'] ?? 0)) {
            $this->flashRedirect('error', 'ไม่สามารถลบบัญชีของตนเองได้', '/settings/users');
        }

        // Admin cannot delete superadmin
        if ($actor['role'] === 'admin' && $target['role'] === 'superadmin') {
            $this->flashRedirect('error', 'ไม่มีสิทธิ์ลบบัญชีผู้ดูแลระบบสูงสุด', '/settings/users');
        }

        // Check FK constraints: proposals created by / PI, and reviews created by
        $pdo = DatabaseConfig::getInstance();

        $stmtP = $pdo->prepare(
            "SELECT COUNT(*) FROM research_proposals WHERE principal_investigator_id = ? OR created_by = ?"
        );
        $stmtP->execute([$id, $id]);
        $proposalCount = (int)$stmtP->fetchColumn();

        $stmtR = $pdo->prepare("SELECT COUNT(*) FROM proposal_reviews WHERE created_by = ?");
        $stmtR->execute([$id]);
        $reviewCount = (int)$stmtR->fetchColumn();

        if ($proposalCount > 0 || $reviewCount > 0) {
            $parts = [];
            if ($proposalCount > 0) $parts[] = "ข้อเสนอโครงการ {$proposalCount} รายการ";
            if ($reviewCount   > 0) $parts[] = "การประเมิน {$reviewCount} รายการ";
            $detail = implode(' และ ', $parts);
            $this->flashRedirect(
                'error',
                "ไม่สามารถลบ \"{$target['name']}\" ได้ เนื่องจากมีข้อมูลที่เกี่ยวข้องอยู่: {$detail} — แนะนำให้ปิดใช้งานแทน",
                '/settings/users'
            );
        }

        // Notifications have CASCADE, but delete explicitly to be safe
        $pdo->prepare("DELETE FROM notifications WHERE user_id = ?")->execute([$id]);

        try {
            $userModel->delete($id);
        } catch (\Throwable $e) {
            $this->flashRedirect(
                'error',
                'ไม่สามารถลบผู้ใช้งานได้ เนื่องจากมีข้อมูลที่เกี่ยวข้องในระบบ — แนะนำให้ปิดใช้งานแทน',
                '/settings/users'
            );
        }

        $this->flashRedirect('success', "ลบผู้ใช้ \"{$target['name']}\" เรียบร้อยแล้ว", '/settings/users');
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    private function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
