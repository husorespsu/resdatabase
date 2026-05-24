<?php

declare(strict_types=1);

class AuthController extends Controller
{
    private \Google\Client $googleClient;

    public function __construct()
    {
        parent::__construct();
        $this->googleClient = $this->buildGoogleClient();
    }

    // ── Login Page ────────────────────────────────────────────

    public function showLogin(): void
    {
        if (!empty($_SESSION['user']['id'])) {
            $this->redirect('/');
        }

        $flash = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);

        $this->render('auth/login', [
            'pageTitle' => 'เข้าสู่ระบบ',
            'flash'     => $flash,
        ], 'auth');
    }

    // ── Redirect to Google ────────────────────────────────────

    public function redirectToGoogle(): void
    {
        if (!empty($_SESSION['user']['id'])) {
            $this->redirect('/');
        }

        $state = bin2hex(random_bytes(24));
        $_SESSION['oauth2_state'] = $state;
        $this->googleClient->setState($state);

        $authUrl = $this->googleClient->createAuthUrl();
        header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
        exit;
    }

    // ── Google Callback ───────────────────────────────────────

    public function handleGoogleCallback(): void
    {
        // CSRF state check
        $returnedState = $_GET['state'] ?? '';
        $storedState   = $_SESSION['oauth2_state'] ?? '';
        unset($_SESSION['oauth2_state']);

        if (empty($returnedState) || !hash_equals($storedState, $returnedState)) {
            $this->flashRedirect('error', 'คำขอไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง', '/auth/login');
        }

        if (isset($_GET['error'])) {
            $this->flashRedirect('error', 'การเข้าสู่ระบบถูกยกเลิก: ' . h($_GET['error']), '/auth/login');
        }

        $code = $_GET['code'] ?? '';
        if (empty($code)) {
            $this->flashRedirect('error', 'ไม่ได้รับรหัสยืนยันตัวตน กรุณาลองใหม่อีกครั้ง', '/auth/login');
        }

        // Exchange code for token
        try {
            $token = $this->googleClient->fetchAccessTokenWithAuthCode($code);
            if (isset($token['error'])) {
                throw new \RuntimeException($token['error_description'] ?? $token['error']);
            }
            $this->googleClient->setAccessToken($token);
        } catch (\Exception $e) {
            error_log('[Auth] Token exchange: ' . $e->getMessage());
            $this->flashRedirect('error', 'ไม่สามารถยืนยันตัวตนกับ Google ได้', '/auth/login');
        }

        // Get profile
        try {
            $oauth2Service = new \Google\Service\Oauth2($this->googleClient);
            $googleUser    = $oauth2Service->userinfo->get();
        } catch (\Exception $e) {
            error_log('[Auth] Profile fetch: ' . $e->getMessage());
            $this->flashRedirect('error', 'ไม่สามารถดึงข้อมูลผู้ใช้จาก Google ได้', '/auth/login');
        }

        $email    = $googleUser->getEmail()         ?? '';
        $googleId = $googleUser->getId()            ?? '';
        $name     = $googleUser->getName()          ?? '';
        $avatar   = $googleUser->getPicture()       ?? '';
        $verified = $googleUser->getVerifiedEmail() ?? false;

        if (!$verified || empty($email)) {
            $this->flashRedirect('error', 'บัญชี Google ยังไม่ได้รับการยืนยัน', '/auth/login');
        }

        // Domain whitelist
        $allowedDomains = array_map('trim', explode(',', $_ENV['ALLOWED_DOMAINS'] ?? 'psu.ac.th,gmail.com'));
        $emailDomain    = strtolower(substr(strrchr($email, '@'), 1));
        if (!in_array($emailDomain, $allowedDomains, true)) {
            $this->flashRedirect('error', "อีเมล {$email} ไม่ได้รับอนุญาตให้ใช้งานระบบ", '/auth/login');
        }

        // Upsert user
        try {
            $userModel = new User();
            $user = $userModel->createFromGoogle([
                'google_id' => $googleId,
                'name'      => $name,
                'email'     => $email,
                'avatar'    => $avatar,
            ]);
            if (!$user) throw new \RuntimeException('Failed to upsert user');
        } catch (\Exception $e) {
            error_log('[Auth] DB error: ' . $e->getMessage());
            $this->flashRedirect('error', 'เกิดข้อผิดพลาดกับฐานข้อมูล กรุณาลองใหม่อีกครั้ง', '/auth/login');
        }

        if (empty($user['is_active'])) {
            $this->flashRedirect('error', 'บัญชีของคุณถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ', '/auth/login');
        }

        $userModel->updateLastLogin((int)$user['id']);

        // Set session
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'        => (int)$user['id'],
            'name'      => $user['name'],
            'email'     => $user['email'],
            'avatar'    => $user['avatar'] ?? '',
            'role'      => $user['role'],
            'is_active' => (int)($user['is_active'] ?? 1),
        ];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $this->setFlash('success', 'ยินดีต้อนรับ ' . h($name));

        // Role-based redirect
        $role = $user['role'] ?? 'executive';
        if (in_array($role, ['admin', 'superadmin'], true)) {
            $this->redirect('/');
        } else {
            $this->redirect('/dashboard');
        }
    }

    // ── Local Login (dev/admin) ───────────────────────────────

    public function localLogin(): void
    {
        if (!empty($_SESSION['user']['id'])) {
            $this->redirect('/');
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $this->flashRedirect('error', 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน', '/auth/login');
        }

        $userModel = new User();
        $user = $userModel->findByUsername($username);

        if (!$user || empty($user['password']) || !password_verify($password, $user['password'])) {
            $this->flashRedirect('error', 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง', '/auth/login');
        }

        if (empty($user['is_active'])) {
            $this->flashRedirect('error', 'บัญชีนี้ถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ', '/auth/login');
        }

        $userModel->updateLastLogin((int)$user['id']);

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'        => (int)$user['id'],
            'name'      => $user['name'],
            'email'     => $user['email'],
            'avatar'    => $user['avatar'] ?? '',
            'role'      => $user['role'],
            'is_active' => (int)($user['is_active'] ?? 1),
        ];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $this->setFlash('success', 'ยินดีต้อนรับ ' . h($user['name']));

        $role = $user['role'] ?? 'executive';
        if (in_array($role, ['admin', 'superadmin'], true)) {
            $this->redirect('/');
        } else {
            $this->redirect('/dashboard');
        }
    }

    // ── Logout ────────────────────────────────────────────────

    public function logout(): void
    {
        try {
            if (!empty($_SESSION['google_token'])) {
                $this->googleClient->setAccessToken($_SESSION['google_token']);
                $this->googleClient->revokeToken();
            }
        } catch (\Exception $e) {
            // Non-fatal
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();

        header('Location: ' . rtrim($_ENV['APP_URL'] ?? 'http://localhost/research', '/') . '/auth/login');
        exit;
    }

    // ── Helpers ───────────────────────────────────────────────

    private function buildGoogleClient(): \Google\Client
    {
        $config = require BASE_PATH . '/config/google_oauth.php';

        $client = new \Google\Client();
        $client->setClientId($config['client_id']);
        $client->setClientSecret($config['client_secret']);
        $client->setRedirectUri($config['redirect_uri']);
        $client->setScopes([
            \Google\Service\Oauth2::USERINFO_EMAIL,
            \Google\Service\Oauth2::USERINFO_PROFILE,
        ]);
        $client->setAccessType('online');
        $client->setPrompt('select_account consent');
        return $client;
    }
}
