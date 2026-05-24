<?php

declare(strict_types=1);

/**
 * Middleware – static auth/role guards (no namespace).
 */
class Middleware
{
    public static function requireAuth(): void
    {
        if (!self::isLoggedIn()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '';
            self::redirect('/auth/login');
        }
        self::checkActive();
    }

    public static function requireRole(array $roles): void
    {
        self::requireAuth();
        $user = self::getAuthUser();
        if (!$user || !in_array($user['role'] ?? '', $roles, true)) {
            $_SESSION['flash'][] = [
                'type'    => 'error',
                'message' => 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้',
            ];
            self::redirect('/');
        }
    }

    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION['user']['id']);
    }

    public static function getAuthUser(): ?array
    {
        $user = $_SESSION['user'] ?? null;
        return (is_array($user) && !empty($user['id'])) ? $user : null;
    }

    public static function checkActive(): void
    {
        $user = self::getAuthUser();
        if ($user === null) return;

        if (empty($user['is_active']) || (int)$user['is_active'] !== 1) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $p = session_get_cookie_params();
                setcookie(session_name(), '', [
                    'expires'  => time() - 42000,
                    'path'     => $p['path'],
                    'domain'   => $p['domain'],
                    'secure'   => $p['secure'],
                    'httponly' => $p['httponly'],
                    'samesite' => 'Lax',
                ]);
            }
            session_destroy();
            $appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost/research', '/');
            header('Location: ' . $appUrl . '/auth/login?deactivated=1', true, 302);
            exit;
        }
    }

    private static function redirect(string $path): never
    {
        $appUrl   = rtrim($_ENV['APP_URL'] ?? 'http://localhost/research', '/');
        $location = (str_starts_with($path, 'http')) ? $path : $appUrl . '/' . ltrim($path, '/');
        header('Location: ' . $location, true, 302);
        exit;
    }
}
