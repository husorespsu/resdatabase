<?php

declare(strict_types=1);

class ProfileController extends Controller
{
    // ── GET /profile ──────────────────────────────────────────────
    public function show(): void
    {
        $user = $this->requireAuth();

        // Refresh from DB so we always have latest data
        $userModel = new User();
        $fresh     = $userModel->findById((int)$user['id']);
        if (!$fresh) {
            $this->flashRedirect('error', 'ไม่พบข้อมูลผู้ใช้งาน', '/');
        }

        $this->render('profile/edit', [
            'pageTitle'  => 'โปรไฟล์ของฉัน',
            'breadcrumbs'=> [['label' => 'โปรไฟล์ของฉัน']],
            'user'       => $fresh,
            'csrfToken'  => $_SESSION['csrf_token'] ?? '',
        ]);
    }

    // ── POST /profile/update ──────────────────────────────────────
    public function update(): void
    {
        $currentUser = $this->requireAuth();
        $uid         = (int)$currentUser['id'];

        if (($_POST['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
            $this->flashRedirect('error', 'การตรวจสอบความปลอดภัยล้มเหลว', '/profile');
        }

        $name       = trim($_POST['name']       ?? '');
        $department = trim($_POST['department']  ?? '');
        $phone      = trim($_POST['phone']       ?? '');

        if ($name === '') {
            $this->flashRedirect('error', 'กรุณากรอกชื่อ-นามสกุล', '/profile');
        }

        $data = [
            'name'       => $name,
            'department' => $department ?: null,
            'phone'      => $phone      ?: null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // ── Avatar upload ──────────────────────────────────────────
        if (!empty($_FILES['avatar']['tmp_name'])) {
            $file    = $_FILES['avatar'];
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 2 * 1024 * 1024; // 2 MB

            if (!in_array($file['type'], $allowed, true)) {
                $this->flashRedirect('error', 'อนุญาตเฉพาะไฟล์รูปภาพ (JPG, PNG, GIF, WebP)', '/profile');
            }
            if ($file['size'] > $maxSize) {
                $this->flashRedirect('error', 'ขนาดไฟล์ต้องไม่เกิน 2 MB', '/profile');
            }

            $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_' . $uid . '_' . time() . '.' . strtolower($ext);
            $dest     = BASE_PATH . '/public/uploads/avatars/' . $filename;

            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                $this->flashRedirect('error', 'ไม่สามารถอัปโหลดรูปภาพได้', '/profile');
            }

            // Delete old avatar file (if it's a local upload)
            $userModel  = new User();
            $oldUser    = $userModel->findById($uid);
            $oldAvatar  = $oldUser['avatar'] ?? '';
            if ($oldAvatar && str_starts_with($oldAvatar, '/research/public/uploads/avatars/')) {
                $oldFile = BASE_PATH . '/public/uploads/avatars/' . basename($oldAvatar);
                if (file_exists($oldFile)) {
                    @unlink($oldFile);
                }
            }

            $data['avatar'] = '/research/public/uploads/avatars/' . $filename;
        }

        $userModel = new User();
        $userModel->update($uid, $data);

        // Refresh session
        $fresh = $userModel->findById($uid);
        $_SESSION['user'] = $fresh;

        $this->flashRedirect('success', 'บันทึกข้อมูลโปรไฟล์เรียบร้อยแล้ว', '/profile');
    }

    // ── POST /profile/change-password ────────────────────────────
    public function changePassword(): void
    {
        $currentUser = $this->requireAuth();
        $uid         = (int)$currentUser['id'];

        if (($_POST['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
            $this->flashRedirect('error', 'การตรวจสอบความปลอดภัยล้มเหลว', '/profile');
        }

        // Only allow local accounts
        $userModel = new User();
        $user      = $userModel->findById($uid);

        if (!empty($user['google_id'])) {
            $this->flashRedirect('error', 'บัญชี Google ไม่สามารถเปลี่ยนรหัสผ่านได้', '/profile');
        }

        $currentPw  = $_POST['current_password'] ?? '';
        $newPw      = $_POST['new_password']      ?? '';
        $confirmPw  = $_POST['confirm_password']  ?? '';

        if ($currentPw === '' || $newPw === '' || $confirmPw === '') {
            $this->flashRedirect('error', 'กรุณากรอกข้อมูลให้ครบถ้วน', '/profile');
        }
        if (strlen($newPw) < 8) {
            $this->flashRedirect('error', 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 8 ตัวอักษร', '/profile');
        }
        if ($newPw !== $confirmPw) {
            $this->flashRedirect('error', 'รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน', '/profile');
        }

        // Verify current password
        if (empty($user['password']) || !password_verify($currentPw, $user['password'])) {
            $this->flashRedirect('error', 'รหัสผ่านปัจจุบันไม่ถูกต้อง', '/profile');
        }

        $userModel->update($uid, [
            'password'   => password_hash($newPw, PASSWORD_BCRYPT),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->flashRedirect('success', 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว', '/profile');
    }

    // ── Helper: require authenticated user ────────────────────────
    private function requireAuth(): array
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            $this->redirect('/auth/login');
        }
        return $user;
    }
}
