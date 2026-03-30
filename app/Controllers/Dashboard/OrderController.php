<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\AuditLog;
use App\Services\NotificationService;

class OrderController
{
    private function gate(): bool
    {
        return gate_service('online_ordering');
    }

    /**
     * GET /dashboard/orders — Kanban ordini del giorno.
     */
    public function index(Request $request): void
    {
        if ($this->gate()) return;

        $tenant = TenantResolver::current();
        $orderModel = new Order();

        $kanban = $orderModel->getKanbanData((int)$tenant['id']);
        $stats = $orderModel->getStats((int)$tenant['id']);
        $completed = $orderModel->getCompletedToday((int)$tenant['id']);

        view('dashboard/orders/index', [
            'title'      => 'Ordini',
            'activeMenu' => 'orders',
            'tenant'     => $tenant,
            'kanban'     => $kanban,
            'stats'      => $stats,
            'completed'  => $completed,
        ], 'dashboard');
    }

    /**
     * GET /dashboard/orders/{id} — Dettaglio ordine.
     */
    public function show(Request $request): void
    {
        if ($this->gate()) return;

        $tenantId = Auth::tenantId();
        $id = (int)$request->param('id');

        $order = (new Order())->findById($id, $tenantId);
        if (!$order) {
            flash('danger', 'Ordine non trovato.');
            Response::redirect(url('dashboard/orders'));
        }

        $items = (new OrderItem())->findByOrder($id);

        view('dashboard/orders/show', [
            'title'      => "Ordine {$order['order_number']}",
            'activeMenu' => 'orders',
            'tenant'     => TenantResolver::current(),
            'order'      => $order,
            'items'      => $items,
        ], 'dashboard');
    }

    /**
     * POST /dashboard/orders/{id}/status — Cambio stato.
     */
    public function updateStatus(Request $request): void
    {
        if ($this->gate()) return;

        $tenantId = Auth::tenantId();
        $id = (int)$request->param('id');
        $data = $request->all();

        $newStatus = $data['status'] ?? '';
        $reason = $data['rejected_reason'] ?? null;

        $orderModel = new Order();
        $order = $orderModel->findById($id, $tenantId);

        if (!$order) {
            flash('danger', 'Ordine non trovato.');
            Response::redirect(url('dashboard/orders'));
        }

        $success = $orderModel->updateStatus($id, $tenantId, $newStatus, $reason);

        if (!$success) {
            flash('danger', 'Transizione di stato non valida.');
            Response::redirect(url("dashboard/orders/{$id}"));
        }

        $eventType = in_array($newStatus, ['cancelled', 'rejected']) ? AuditLog::ORDER_CANCELLED : AuditLog::ORDER_STATUS;
        AuditLog::log($eventType, "Ordine #{$order['order_number']}: {$order['status']} → {$newStatus}", Auth::id(), $tenantId);

        // Notify customer of status change (non-blocking)
        try {
            $updatedOrder = $orderModel->findById($id, $tenantId);
            $tenant = TenantResolver::current();
            (new NotificationService())->notifyOrderStatusChange($updatedOrder, $tenant, $newStatus);
        } catch (\Throwable $e) {
            error_log('Order status notification failed: ' . $e->getMessage());
        }

        flash('success', 'Stato ordine aggiornato: ' . order_status_label($newStatus));

        // If AJAX, return JSON
        if ($request->isJson() || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            Response::json(['success' => true, 'status' => $newStatus]);
        }

        Response::redirect(url('dashboard/orders'));
    }

    /**
     * GET /dashboard/orders/history — Storico ordini paginato.
     */
    public function history(Request $request): void
    {
        if ($this->gate()) return;

        $tenantId = Auth::tenantId();
        $orderModel = new Order();

        $filters = [
            'status'     => $request->query('status', ''),
            'order_type' => $request->query('type', ''),
            'date_from'  => $request->query('from', ''),
            'date_to'    => $request->query('to', ''),
            'search'     => $request->query('q', ''),
            'limit'      => 30,
            'offset'     => max(0, (int)$request->query('offset', 0)),
        ];

        $orders = $orderModel->findByTenant($tenantId, $filters);
        $total = $orderModel->countByTenant($tenantId, $filters);

        view('dashboard/orders/history', [
            'title'      => 'Storico Ordini',
            'activeMenu' => 'orders',
            'tenant'     => TenantResolver::current(),
            'orders'     => $orders,
            'total'      => $total,
            'filters'    => $filters,
        ], 'dashboard');
    }

    /**
     * GET /dashboard/orders/api/kanban — JSON per polling kanban.
     */
    public function apiKanban(Request $request): void
    {
        if ($this->gate()) return;

        $tenantId = Auth::tenantId();
        $orderModel = new Order();

        $kanban = $orderModel->getKanbanData($tenantId);
        $completed = $orderModel->getCompletedToday($tenantId);

        Response::json([
            'success'   => true,
            'kanban'    => $kanban,
            'completed' => $completed,
        ]);
    }

    /**
     * GET /dashboard/orders/api/stats — JSON statistiche giorno.
     */
    public function apiStats(Request $request): void
    {
        if ($this->gate()) return;

        $tenantId = Auth::tenantId();
        $stats = (new Order())->getStats($tenantId);

        Response::json([
            'success' => true,
            'stats'   => $stats,
        ]);
    }
}
