<?php

namespace App\Controllers\Ordering;

use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tenant;

class OrderStoreController
{
    /**
     * GET /{slug}/order — Store pubblico standalone.
     */
    public function show(Request $request): void
    {
        $slug = $request->param('slug');
        $tenantModel = new Tenant();
        $tenant = $tenantModel->findBySlug($slug);

        if (!$tenant || !$tenant['is_active']) {
            Response::notFound();
        }

        // Check subscription expiry
        $expiredSub = $tenantModel->getExpiredSubscription((int)$tenant['id']);
        if ($expiredSub) {
            view('booking/suspended', [
                'tenantName'    => $tenant['name'],
                'tenantLogo'    => $tenant['logo_url'],
                'tenantPhone'   => $tenant['phone'] ?? '',
                'tenantEmail'   => $tenant['email'] ?? '',
                'tenantAddress' => $tenant['address'] ?? '',
            ]);
            return;
        }

        // If ordering not enabled or service not available, redirect to menu
        if (!$tenant['ordering_enabled'] || !$tenantModel->canUseService((int)$tenant['id'], 'online_ordering')) {
            header('Location: ' . url("{$slug}/menu"));
            exit;
        }

        TenantResolver::setCurrent($tenant);

        view('ordering/store', [
            'title'      => 'Ordina - ' . $tenant['name'],
            'tenant'     => $tenant,
            'tenantName' => $tenant['name'],
            'tenantLogo' => $tenant['logo_url'] ?? null,
            'slug'       => $slug,
            'phone'      => $tenant['phone'] ?? null,
            'address'    => $tenant['address'] ?? null,
            'apiBaseUrl' => url('api/v1'),
        ]);
    }

    /**
     * GET /{slug}/order/success — Pagina conferma ordine.
     */
    public function success(Request $request): void
    {
        $slug = $request->param('slug');
        $tenantModel = new Tenant();
        $tenant = $tenantModel->findBySlug($slug);

        if (!$tenant || !$tenant['is_active']) {
            Response::notFound();
        }

        TenantResolver::setCurrent($tenant);

        $orderNumber = $request->query('order', '');

        // Fetch order details
        $order = null;
        $orderItems = [];
        if ($orderNumber) {
            $orderModel = new Order();
            $order = $orderModel->findByOrderNumber($orderNumber, (int)$tenant['id']);
            if ($order) {
                $orderItems = (new OrderItem())->findByOrder((int)$order['id']);
            }
        }

        view('ordering/success', [
            'title'       => 'Ordine confermato - ' . $tenant['name'],
            'tenant'      => $tenant,
            'tenantName'  => $tenant['name'],
            'tenantLogo'  => $tenant['logo_url'] ?? null,
            'slug'        => $slug,
            'orderNumber' => $orderNumber,
            'order'       => $order,
            'orderItems'  => $orderItems,
            'phone'       => $tenant['phone'] ?? null,
        ]);
    }
}
