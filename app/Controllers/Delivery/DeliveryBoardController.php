<?php

namespace App\Controllers\Delivery;

use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tenant;
use App\Services\AuditLog;
use App\Services\NotificationService;

class DeliveryBoardController
{
    /**
     * GET /delivery/{token} — Mostra form PIN.
     */
    public function show(Request $request): void
    {
        $token = $request->param('token');
        $tenant = $this->resolveTenant($token);

        // Se già autenticato, redirect alla board
        if (!empty($_SESSION['delivery_board_' . $token])) {
            header('Location: ' . url("delivery/{$token}/board"));
            exit;
        }

        view('delivery/board', [
            'title'  => 'Board Consegne - ' . $tenant['name'],
            'tenant' => $tenant,
            'token'  => $token,
            'page'   => 'pin',
            'error'  => null,
        ]);
    }

    /**
     * POST /delivery/{token}/auth — Valida PIN.
     */
    public function auth(Request $request): void
    {
        $token = $request->param('token');
        $tenant = $this->resolveTenant($token);

        // Rate limiting: max 5 tentativi per IP in 15 min
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $rateLimitKey = 'delivery_pin_' . md5($ip . $token);
        $attempts = (int)($_SESSION[$rateLimitKey . '_count'] ?? 0);
        $firstAttempt = (int)($_SESSION[$rateLimitKey . '_time'] ?? 0);

        if ($attempts >= 5 && (time() - $firstAttempt) < 900) {
            view('delivery/board', [
                'title'  => 'Board Consegne - ' . $tenant['name'],
                'tenant' => $tenant,
                'token'  => $token,
                'page'   => 'pin',
                'error'  => 'Troppi tentativi. Riprova tra qualche minuto.',
            ]);
            return;
        }

        $pin = trim($request->input('pin') ?? '');

        if ($pin === $tenant['delivery_board_pin']) {
            // Reset rate limit
            unset($_SESSION[$rateLimitKey . '_count'], $_SESSION[$rateLimitKey . '_time']);
            // Set session
            $_SESSION['delivery_board_' . $token] = true;
            header('Location: ' . url("delivery/{$token}/board"));
            exit;
        }

        // Track failed attempt
        if ($firstAttempt === 0 || (time() - $firstAttempt) >= 900) {
            $_SESSION[$rateLimitKey . '_count'] = 1;
            $_SESSION[$rateLimitKey . '_time'] = time();
        } else {
            $_SESSION[$rateLimitKey . '_count'] = $attempts + 1;
        }

        view('delivery/board', [
            'title'  => 'Board Consegne - ' . $tenant['name'],
            'tenant' => $tenant,
            'token'  => $token,
            'page'   => 'pin',
            'error'  => 'PIN errato. Riprova.',
        ]);
    }

    /**
     * GET /delivery/{token}/board — Board ordini delivery.
     */
    public function board(Request $request): void
    {
        $token = $request->param('token');
        $tenant = $this->resolveTenant($token);
        $this->requirePin($token);

        $tenantId = (int)$tenant['id'];
        $orderModel = new Order();
        $orders = $orderModel->findDeliveryReady($tenantId);

        // Fetch items per order
        $orderItemModel = new OrderItem();
        $orderItems = [];
        foreach ($orders as $order) {
            $orderItems[$order['id']] = $orderItemModel->findByOrder((int)$order['id']);
        }

        // JSON response per polling
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'orders' => $orders,
                'items'  => $orderItems,
                'count'  => count($orders),
            ]);
            exit;
        }

        view('delivery/board', [
            'title'      => 'Board Consegne - ' . $tenant['name'],
            'tenant'     => $tenant,
            'token'      => $token,
            'page'       => 'board',
            'orders'     => $orders,
            'orderItems' => $orderItems,
        ]);
    }

    /**
     * POST /delivery/{token}/complete/{id} — Segna ordine completato.
     */
    public function complete(Request $request): void
    {
        $token = $request->param('token');
        $tenant = $this->resolveTenant($token);
        $this->requirePin($token);

        $orderId = (int)$request->param('id');
        $tenantId = (int)$tenant['id'];
        $orderModel = new Order();
        $order = $orderModel->findById($orderId, $tenantId);

        if (!$order || $order['status'] !== 'ready') {
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                http_response_code(422);
                echo json_encode(['error' => 'Ordine non disponibile per la consegna.']);
                exit;
            }
            header('Location: ' . url("delivery/{$token}/board"));
            exit;
        }

        $orderModel->updateStatus($orderId, $tenantId, 'completed');

        // Audit log
        AuditLog::log(AuditLog::ORDER_STATUS, "Ordine {$order['order_number']} completato via delivery board", null, $tenantId);

        // Notify customer
        TenantResolver::setCurrent($tenant);
        (new NotificationService())->notifyOrderStatusChange($order, $tenant, 'completed');

        if ($this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'order_number' => $order['order_number']]);
            exit;
        }

        header('Location: ' . url("delivery/{$token}/board"));
        exit;
    }

    // --- Helpers ---

    private function resolveTenant(string $token): array
    {
        $tenant = (new Tenant())->findByDeliveryToken($token);
        if (!$tenant) {
            Response::notFound();
            exit;
        }
        return $tenant;
    }

    private function requirePin(string $token): void
    {
        if (empty($_SESSION['delivery_board_' . $token])) {
            header('Location: ' . url("delivery/{$token}"));
            exit;
        }
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
}
