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
     * Calcola date per il selettore periodo (7gg, 30gg, 90gg, tutto).
     */
    private function getPeriodDates(string $period): array
    {
        return match ($period) {
            '7'   => [date('Y-m-d', strtotime('-7 days')), date('Y-m-d')],
            '30'  => [date('Y-m-d', strtotime('-30 days')), date('Y-m-d')],
            '90'  => [date('Y-m-d', strtotime('-90 days')), date('Y-m-d')],
            'all' => [null, null],
            default => [date('Y-m-d', strtotime('-30 days')), date('Y-m-d')],
        };
    }

    /**
     * GET /dashboard/orders/history — Panoramica (tab default).
     */
    public function history(Request $request): void
    {
        if ($this->gate()) return;

        $tenantId = (int)Auth::tenantId();
        $orderModel = new Order();
        $period = $request->query('period', '30');

        [$dateFrom, $dateTo] = $this->getPeriodDates($period);

        $stats = $orderModel->getHistoryStats($tenantId, $dateFrom, $dateTo);

        // Previous period for comparison
        $prevStats = null;
        if ($dateFrom && $dateTo) {
            $days = (int)$period;
            $prevFrom = date('Y-m-d', strtotime("-" . ($days * 2) . " days"));
            $prevTo = date('Y-m-d', strtotime("-" . ($days + 1) . " days"));
            $prevStats = $orderModel->getHistoryStats($tenantId, $prevFrom, $prevTo);
        }

        // Daily trend
        $trend = [];
        $prevTrend = [];
        if ($dateFrom && $dateTo) {
            $trend = $orderModel->getDailyTrend($tenantId, $dateFrom, $dateTo);
            $days = (int)$period;
            $prevFrom = date('Y-m-d', strtotime("-" . ($days * 2) . " days"));
            $prevTo = date('Y-m-d', strtotime("-" . ($days + 1) . " days"));
            $prevTrend = $orderModel->getDailyTrend($tenantId, $prevFrom, $prevTo);
        }

        view('dashboard/orders/history', [
            'title'      => 'Storico Ordini',
            'activeMenu' => 'orders',
            'tenant'     => TenantResolver::current(),
            'tab'        => 'panoramica',
            'period'     => $period,
            'stats'      => $stats,
            'prevStats'  => $prevStats,
            'trend'      => $trend,
            'prevTrend'  => $prevTrend,
        ], 'dashboard');
    }

    /**
     * GET /dashboard/orders/history/orders — Tab ordini.
     */
    public function historyOrders(Request $request): void
    {
        if ($this->gate()) return;

        $tenantId = (int)Auth::tenantId();
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

        // Preload item summaries
        $itemSummaries = [];
        foreach ($orders as $o) {
            $itemSummaries[$o['id']] = $orderModel->getItemsSummary((int)$o['id']);
        }

        view('dashboard/orders/history', [
            'title'         => 'Storico Ordini',
            'activeMenu'    => 'orders',
            'tenant'        => TenantResolver::current(),
            'tab'           => 'ordini',
            'orders'        => $orders,
            'total'         => $total,
            'filters'       => $filters,
            'itemSummaries' => $itemSummaries,
        ], 'dashboard');
    }

    /**
     * GET /dashboard/orders/history/rankings — Tab classifiche.
     */
    public function historyRankings(Request $request): void
    {
        if ($this->gate()) return;

        $tenantId = (int)Auth::tenantId();
        $orderModel = new Order();
        $period = $request->query('period', '30');

        [$dateFrom, $dateTo] = $this->getPeriodDates($period);

        $topItems     = $orderModel->getTopItems($tenantId, 10, $dateFrom, $dateTo);
        $topCustomers = $orderModel->getTopCustomers($tenantId, 10, $dateFrom, $dateTo);

        view('dashboard/orders/history', [
            'title'        => 'Storico Ordini',
            'activeMenu'   => 'orders',
            'tenant'       => TenantResolver::current(),
            'tab'          => 'classifiche',
            'period'       => $period,
            'topItems'     => $topItems,
            'topCustomers' => $topCustomers,
        ], 'dashboard');
    }

    /**
     * GET /dashboard/orders/history/csv — Esporta ordini in CSV.
     */
    public function exportCsv(Request $request): void
    {
        if ($this->gate()) return;

        $tenantId = (int)Auth::tenantId();
        $orderModel = new Order();

        $filters = [
            'status'     => $request->query('status', ''),
            'order_type' => $request->query('type', ''),
            'date_from'  => $request->query('from', ''),
            'date_to'    => $request->query('to', ''),
            'search'     => $request->query('q', ''),
            'limit'      => 10000,
            'offset'     => 0,
        ];

        $orders = $orderModel->findByTenant($tenantId, $filters);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="ordini-' . date('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

        fputcsv($out, ['#Ordine', 'Data', 'Cliente', 'Telefono', 'Tipo', 'Totale', 'Pagamento', 'Stato'], ';');

        foreach ($orders as $o) {
            fputcsv($out, [
                $o['order_number'],
                date('d/m/Y H:i', strtotime($o['created_at'])),
                $o['customer_name'],
                $o['customer_phone'],
                $o['order_type'] === 'delivery' ? 'Consegna' : 'Asporto',
                number_format((float)$o['total'], 2, ',', '.'),
                $o['payment_method'] === 'stripe' ? 'Carta' : 'Contanti',
                order_status_label($o['status']),
            ], ';');
        }

        fclose($out);
        exit;
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
