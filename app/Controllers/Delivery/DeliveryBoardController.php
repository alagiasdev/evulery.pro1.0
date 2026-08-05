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
use App\Services\RateLimit;

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

        // Rate limiting SERVER-SIDE (DB) per IP + token: NON aggirabile scartando il
        // cookie di sessione, come invece era col vecchio contatore in $_SESSION (bastava
        // non inviare il cookie per azzerare il limite e brute-forzare il PIN a 4 cifre).
        // Nota: RateLimit::cleanup() elimina i record oltre 300s, quindi la finestra
        // effettiva e' ~5 min (limite condiviso della classe): 5 tentativi / 5 min / IP.
        $ip = $request->ip();
        $limiter = new RateLimit();
        $rlKey = 'dlvpin_' . md5($token);

        if (!$limiter->checkCustom($ip, $rlKey, 5, 300)) {
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

        // Confronto timing-safe; $pin non vuoto per evitare match con un PIN non impostato.
        if ($pin !== '' && hash_equals((string)($tenant['delivery_board_pin'] ?? ''), $pin)) {
            $_SESSION['delivery_board_' . $token] = true;
            header('Location: ' . url("delivery/{$token}/board"));
            exit;
        }

        // Tentativo fallito: conta verso il limite server-side.
        $limiter->recordCustom($ip, $rlKey);

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
