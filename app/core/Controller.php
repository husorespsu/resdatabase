<?php

declare(strict_types=1);

/**
 * Base Controller (no namespace – all controllers extend this globally).
 */
abstract class Controller
{
    protected string $viewsPath;
    protected string $layoutsPath;

    public function __construct()
    {
        // __DIR__ = app/core → dirname(__DIR__) = app → app/views
        $this->viewsPath   = dirname(__DIR__) . '/views';
        $this->layoutsPath = dirname(__DIR__) . '/views/layouts';
    }

    // ── View Rendering ────────────────────────────────────────

    /**
     * Render a view inside a layout.
     * @param string $view  e.g. 'proposals/index'
     * @param array  $data  Variables extracted into view scope
     * @param string $layout  Layout name ('main' or 'auth')
     */
    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        extract($data, EXTR_SKIP);

        $viewFile = $this->resolveView($view);
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        $layoutFile = $this->layoutsPath . '/' . $layout . '.php';
        if (!file_exists($layoutFile)) {
            throw new \RuntimeException("Layout [{$layout}] not found at: {$layoutFile}");
        }
        require $layoutFile;
    }

    /** Render a view without a layout (for AJAX partials). */
    protected function view(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require $this->resolveView($view);
    }

    private function resolveView(string $view): string
    {
        $path = $this->viewsPath . '/' . str_replace('.', '/', $view) . '.php';
        if (!file_exists($path)) {
            throw new \RuntimeException("View [{$view}] not found at: {$path}");
        }
        return $path;
    }

    // ── Redirect ──────────────────────────────────────────────

    protected function redirect(string $path): never
    {
        $appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost/research', '/');
        $location = (str_starts_with($path, 'http://') || str_starts_with($path, 'https://'))
            ? $path
            : $appUrl . '/' . ltrim($path, '/');
        header('Location: ' . $location, true, 302);
        exit;
    }

    // ── JSON Response ─────────────────────────────────────────

    protected function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // ── Current User ──────────────────────────────────────────

    protected function getCurrentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    protected function isAdmin(): bool
    {
        $user = $this->getCurrentUser();
        return $user && in_array($user['role'], ['admin', 'superadmin']);
    }

    protected function isSuperAdmin(): bool
    {
        $user = $this->getCurrentUser();
        return $user && $user['role'] === 'superadmin';
    }

    // ── Flash Messages ────────────────────────────────────────

    protected function setFlash(string $type, string $message): void
    {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    protected function getFlash(): array
    {
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $messages;
    }

    protected function flashRedirect(string $type, string $message, string $path): never
    {
        $this->setFlash($type, $message);
        $this->redirect($path);
    }
}
