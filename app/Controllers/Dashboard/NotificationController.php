<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\Notification;

class NotificationController
{
    /**
     * Full notifications page.
     */
    public function index(Request $request): void
    {
        $tenantId = Auth::tenantId();

        $page = max(1, (int)($request->query('page', 1)));
        $limit = 30;
        $offset = ($page - 1) * $limit;

        $model = new Notification();
        $notifications = $model->getAllPaginated($tenantId, $limit, $offset);
        $total = $model->countAll($tenantId);
        $totalPages = max(1, (int)ceil($total / $limit));

        view('dashboard/notifications/index', [
            'title'         => 'Notifiche',
            'activeMenu'    => 'notifications',
            'notifications' => $notifications,
            'page'          => $page,
            'totalPages'    => $totalPages,
            'total'         => $total,
        ], 'dashboard');
    }

    /**
     * JSON: unread count for badge polling.
     */
    public function apiUnread(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $count = (new Notification())->getUnreadCount($tenantId);
        Response::json(['count' => $count]);
    }

    /**
     * JSON: recent notifications for dropdown.
     */
    public function apiRecent(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $items = (new Notification())->getRecent($tenantId, 15);
        Response::json(['notifications' => $items]);
    }

    /**
     * Mark single notification as read.
     */
    public function markRead(Request $request): void
    {
        $id = (int)$request->param('id');
        $tenantId = Auth::tenantId();
        (new Notification())->markAsRead($id, $tenantId);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            Response::json(['ok' => true]);
        }

        Response::redirect(url('dashboard/notifications'));
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead(Request $request): void
    {
        $tenantId = Auth::tenantId();
        (new Notification())->markAllRead($tenantId);

        // AJAX request → JSON, form submit → redirect
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            Response::json(['ok' => true]);
        }

        flash('success', 'Tutte le notifiche segnate come lette.');
        Response::redirect(url('dashboard/notifications'));
    }

    /**
     * Delete single notification.
     */
    public function destroy(Request $request): void
    {
        $id = (int)$request->param('id');
        $tenantId = Auth::tenantId();
        (new Notification())->delete($id, $tenantId);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            Response::json(['ok' => true]);
        }

        Response::redirect(url('dashboard/notifications'));
    }

    /**
     * Delete all notifications.
     */
    public function destroyAll(Request $request): void
    {
        $tenantId = Auth::tenantId();
        (new Notification())->deleteAll($tenantId);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            Response::json(['ok' => true]);
        }

        flash('success', 'Tutte le notifiche eliminate.');
        Response::redirect(url('dashboard/notifications'));
    }
}
