<?php

class NotificationController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * List paginated notifications for the current user.
     */
    public function index(): void
    {
        $user   = getCurrentUser();
        $userId = (int)($user['id'] ?? 0);
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 20;

        $notifModel    = new Notification();
        $result        = $notifModel->getPaginatedForUser($userId, $page, $limit);

        $this->render('notifications/index', [
            'pageTitle'     => 'การแจ้งเตือน',
            'notifications' => $result['notifications'] ?? $result,
            'pagination'    => $result['pagination']    ?? [],
        ]);
    }

    /**
     * Return unread notification count as JSON.
     */
    public function unreadCount(): void
    {
        $user   = getCurrentUser();
        $userId = (int)($user['id'] ?? 0);
        $count  = (new Notification())->getUnreadCount($userId);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['count' => $count], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(array $params): void
    {
        $id     = (int)($params['id'] ?? 0);
        $user   = getCurrentUser();
        $userId = (int)($user['id'] ?? 0);

        (new Notification())->markRead($id, $userId);

        // AJAX check
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?? '/notifications');
    }

    /**
     * Mark all notifications as read for the current user.
     */
    public function markAllRead(): void
    {
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flashRedirect('error', 'การตรวจสอบความปลอดภัยล้มเหลว', '/notifications');
        }

        $user   = getCurrentUser();
        $userId = (int)($user['id'] ?? 0);

        (new Notification())->markAllRead($userId);

        $this->flashRedirect('success', 'ทำเครื่องหมายอ่านทั้งหมดเรียบร้อยแล้ว', '/notifications');
    }
}
