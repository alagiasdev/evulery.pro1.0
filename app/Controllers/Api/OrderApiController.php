<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Customer;
use App\Models\DeliveryZone;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Promotion;
use App\Models\Tenant;
use App\Services\AuditLog;
use App\Services\NotificationService;

class OrderApiController
{
    /**
     * GET /api/v1/tenants/{slug}/order-menu
     * Ritorna menu ordinabile + impostazioni ordering + pickup slots.
     */
    public function menu(Request $request): void
    {
        $tenant = $this->resolveTenant($request);

        // Check ordering enabled
        if (!$tenant['ordering_enabled']) {
            Response::error('Il servizio ordini non è attivo.', 'SERVICE_NOT_AVAILABLE', 403);
        }

        $menuItems = (new MenuItem())->findOrderableGrouped((int)$tenant['id']);

        // Generate available pickup slots
        $slots = $this->generatePickupSlots($tenant);

        // Delivery zones (if mode = zones)
        $deliveryZones = [];
        if (in_array($tenant['ordering_mode'], ['delivery', 'both'])) {
            if ($tenant['delivery_mode'] === 'zones') {
                $zones = (new DeliveryZone())->findActiveByTenant((int)$tenant['id']);
                foreach ($zones as $z) {
                    $deliveryZones[] = [
                        'id'           => $z['id'],
                        'name'         => $z['name'],
                        'postal_codes' => $z['postal_codes'],
                        'fee'          => (float)$z['fee'],
                        'min_amount'   => (float)$z['min_amount'],
                    ];
                }
            }
        }

        // Today's ordering hours (for open/close status)
        $hours = json_decode($tenant['ordering_hours'] ?? '{}', true) ?: [];
        if (empty($hours)) {
            $hours = json_decode($tenant['opening_hours'] ?? '{}', true) ?: [];
        }
        $today = date('N');
        $todayHours = $hours[$today] ?? null;

        // Applicable promotion
        $promoData = null;
        $promo = (new Promotion())->findApplicable((int)$tenant['id'], date('Y-m-d'), date('H:i'), 'orders');
        if ($promo) {
            $promoData = [
                'id'               => (int)$promo['id'],
                'name'             => $promo['name'],
                'discount_percent' => (int)$promo['discount_percent'],
            ];
        }

        Response::success([
            'menu'     => $menuItems,
            'settings' => [
                'ordering_mode'           => $tenant['ordering_mode'],
                'ordering_prep_minutes'   => (int)$tenant['ordering_prep_minutes'],
                'ordering_min_amount'     => (float)$tenant['ordering_min_amount'],
                'ordering_max_per_slot'   => (int)$tenant['ordering_max_per_slot'],
                'ordering_payment_methods' => $tenant['ordering_payment_methods'],
                'ordering_pickup_interval' => (int)$tenant['ordering_pickup_interval'],
                'delivery_mode'           => $tenant['delivery_mode'],
                'delivery_fee'            => (float)($tenant['delivery_fee'] ?? 0),
                'delivery_min_amount'     => (float)($tenant['delivery_min_amount'] ?? 0),
                'delivery_description'    => $tenant['delivery_description'] ?? '',
                'today_hours'             => $todayHours,
            ],
            'slots'          => $slots,
            'delivery_zones' => $deliveryZones,
            'promotion'      => $promoData,
            'tenant'         => [
                'name'     => $tenant['name'],
                'slug'     => $tenant['slug'],
                'logo_url' => $tenant['logo_url'] ?? null,
                'phone'    => $tenant['phone'] ?? null,
                'email'    => $tenant['email'] ?? null,
                'address'  => $tenant['address'] ?? null,
            ],
        ]);
    }

    /**
     * POST /api/v1/tenants/{slug}/orders
     * Crea un ordine (takeaway o delivery).
     */
    public function store(Request $request): void
    {
        $tenant = $this->resolveTenant($request);

        if (!$tenant['ordering_enabled']) {
            Response::error('Il servizio ordini non è attivo.', 'SERVICE_NOT_AVAILABLE', 403);
        }

        // Check service gating
        if (!(new Tenant())->canUseService((int)$tenant['id'], 'online_ordering')) {
            Response::error('Il servizio ordini online non è disponibile per il tuo piano.', 'SERVICE_NOT_AVAILABLE', 403);
        }

        $data = $request->isJson() ? $request->json() : $request->all();

        // Validate base fields
        $v = Validator::make($data)
            ->required('customer_name', 'Nome')
            ->required('customer_phone', 'Telefono')
            ->phone('customer_phone', 'Telefono')
            ->required('order_type', 'Tipo ordine')
            ->required('payment_method', 'Metodo pagamento');

        if ($v->fails()) {
            Response::error($v->firstError(), 'VALIDATION_ERROR', 422);
        }

        $orderType = $data['order_type'] ?? 'takeaway';
        $paymentMethod = $data['payment_method'] ?? 'cash';

        // Validate order_type matches tenant's ordering_mode
        if ($orderType === 'delivery' && $tenant['ordering_mode'] === 'takeaway') {
            Response::error('Il servizio consegna non è disponibile.', 'DELIVERY_NOT_AVAILABLE', 422);
        }
        if ($orderType === 'takeaway' && $tenant['ordering_mode'] === 'delivery') {
            Response::error('Il servizio asporto non è disponibile.', 'TAKEAWAY_NOT_AVAILABLE', 422);
        }

        // Validate payment method
        $allowedPayments = explode(',', $tenant['ordering_payment_methods'] ?? 'cash');
        if (!in_array($paymentMethod, $allowedPayments, true)) {
            Response::error('Metodo di pagamento non accettato.', 'PAYMENT_METHOD_INVALID', 422);
        }

        // Validate items
        $items = $data['items'] ?? [];
        if (empty($items) || !is_array($items)) {
            Response::error('Il carrello è vuoto.', 'EMPTY_CART', 422);
        }

        // Check applicable promotion (server-side, never trust client)
        $promo = (new Promotion())->findApplicable((int)$tenant['id'], date('Y-m-d'), date('H:i'), 'orders');
        $discountPercent = $promo ? (int)$promo['discount_percent'] : 0;

        // Fetch items from DB (server-side prices)
        $menuItemModel = new MenuItem();
        $orderItems = [];
        $subtotal = 0;

        foreach ($items as $item) {
            $menuItemId = (int)($item['menu_item_id'] ?? 0);
            $qty = max(1, (int)($item['quantity'] ?? 1));

            $menuItem = $menuItemModel->findById($menuItemId, (int)$tenant['id']);
            if (!$menuItem || !$menuItem['is_available'] || !$menuItem['is_orderable']) {
                Response::error("Il piatto \"{$item['item_name']}\" non è disponibile.", 'ITEM_UNAVAILABLE', 422);
            }

            $unitPrice = (float)$menuItem['price'];
            // Apply promo discount
            if ($discountPercent > 0) {
                $unitPrice = round($unitPrice * (1 - $discountPercent / 100), 2);
            }
            $subtotal += $unitPrice * $qty;

            $orderItems[] = [
                'menu_item_id' => $menuItemId,
                'item_name'    => $menuItem['name'],
                'quantity'     => $qty,
                'unit_price'   => $unitPrice,
                'notes'        => $item['notes'] ?? null,
            ];
        }

        // Delivery validation
        $deliveryFee = 0;
        if ($orderType === 'delivery') {
            if (empty($data['delivery_address'])) {
                Response::error('Inserisci l\'indirizzo di consegna.', 'VALIDATION_ERROR', 422);
            }
            if (empty($data['delivery_cap'])) {
                Response::error('Inserisci il CAP di consegna.', 'VALIDATION_ERROR', 422);
            }

            if ($tenant['delivery_mode'] === 'zones') {
                $zone = (new DeliveryZone())->findByPostalCode((int)$tenant['id'], $data['delivery_cap']);
                if (!$zone) {
                    Response::error('Il CAP inserito non rientra nelle zone di consegna.', 'DELIVERY_ZONE_NOT_FOUND', 422);
                }
                $deliveryFee = (float)$zone['fee'];
                $minAmount = (float)$zone['min_amount'];
                if ($subtotal < $minAmount) {
                    Response::error(
                        "Ordine minimo per la tua zona: €" . number_format($minAmount, 2, ',', '.'),
                        'MIN_AMOUNT_NOT_MET',
                        422
                    );
                }
            } else {
                // Simple delivery
                $deliveryFee = (float)($tenant['delivery_fee'] ?? 0);
                $minAmount = (float)($tenant['delivery_min_amount'] ?? 0);
                if ($minAmount > 0 && $subtotal < $minAmount) {
                    Response::error(
                        "Ordine minimo per la consegna: €" . number_format($minAmount, 2, ',', '.'),
                        'MIN_AMOUNT_NOT_MET',
                        422
                    );
                }
            }
        }

        // Check takeaway min amount
        if ($orderType === 'takeaway') {
            $minAmount = (float)($tenant['ordering_min_amount'] ?? 0);
            if ($minAmount > 0 && $subtotal < $minAmount) {
                Response::error(
                    "Ordine minimo: €" . number_format($minAmount, 2, ',', '.'),
                    'MIN_AMOUNT_NOT_MET',
                    422
                );
            }
        }

        // Pickup time + slot capacity
        $pickupTime = $data['pickup_time'] ?? null;
        if ($pickupTime) {
            $orderModel = new Order();
            $slotCount = $orderModel->countBySlot((int)$tenant['id'], $pickupTime);
            $maxPerSlot = (int)($tenant['ordering_max_per_slot'] ?? 10);
            if ($slotCount >= $maxPerSlot) {
                Response::error('Lo slot selezionato è pieno. Scegli un altro orario.', 'SLOT_FULL', 422);
            }
        }

        // Calculate discount amount (original subtotal - discounted subtotal)
        $discountAmount = 0;
        if ($discountPercent > 0) {
            $originalSubtotal = 0;
            foreach ($orderItems as $oi) {
                $mi = $menuItemModel->findById($oi['menu_item_id'], (int)$tenant['id']);
                $originalSubtotal += (float)$mi['price'] * $oi['quantity'];
            }
            $discountAmount = round($originalSubtotal - $subtotal, 2);
        }

        $total = $subtotal + $deliveryFee;

        // Find or create customer
        $customerId = null;
        if (!empty($data['customer_email'])) {
            $nameParts = explode(' ', $data['customer_name'], 2);
            $customer = (new Customer())->findOrCreate((int)$tenant['id'], [
                'first_name' => $nameParts[0] ?? $data['customer_name'],
                'last_name'  => $nameParts[1] ?? '',
                'email'      => $data['customer_email'],
                'phone'      => $data['customer_phone'],
            ]);
            $customerId = $customer['id'];
        }

        // Determine initial status
        $status = $tenant['ordering_auto_accept'] ? 'accepted' : 'pending';

        // Create order
        $orderModel = $orderModel ?? new Order();
        $orderId = $orderModel->create((int)$tenant['id'], [
            'customer_id'       => $customerId,
            'order_type'        => $orderType,
            'status'            => $status,
            'pickup_time'       => $pickupTime,
            'subtotal'          => $subtotal,
            'discount_amount'   => $discountAmount,
            'promo_id'          => $promo ? (int)$promo['id'] : null,
            'total'             => $total,
            'payment_method'    => $paymentMethod,
            'customer_name'     => $data['customer_name'],
            'customer_phone'    => $data['customer_phone'],
            'customer_email'    => $data['customer_email'] ?? null,
            'delivery_address'  => $data['delivery_address'] ?? null,
            'delivery_cap'      => $data['delivery_cap'] ?? null,
            'delivery_notes'    => $data['delivery_notes'] ?? null,
            'delivery_fee'      => $deliveryFee,
            'notes'             => $data['notes'] ?? null,
        ]);

        // Create order items
        (new OrderItem())->createBatch($orderId, $orderItems);

        $order = $orderModel->findById($orderId, (int)$tenant['id']);

        AuditLog::log(AuditLog::ORDER_CREATED, "Ordine #{$order['order_number']} ({$orderType})", null, (int)$tenant['id']);

        // Notify restaurant owner (non-blocking)
        try {
            (new NotificationService())->notifyNewOrder($order, $tenant);
        } catch (\Throwable $e) {
            error_log('Order notification failed: ' . $e->getMessage());
        }

        $responseData = [
            'order_id'     => $orderId,
            'order_number' => $order['order_number'],
            'status'       => $status,
            'total'        => $total,
            'order_type'   => $orderType,
        ];

        // Stripe payment
        if ($paymentMethod === 'stripe' && !empty($tenant['stripe_sk'])) {
            try {
                $tenantStripeKey = decrypt_value($tenant['stripe_sk']);
                if (!$tenantStripeKey) {
                    throw new \RuntimeException('Chiave Stripe non valida');
                }
                \Stripe\Stripe::setApiKey($tenantStripeKey);

                $session = \Stripe\Checkout\Session::create([
                    'payment_method_types' => ['card'],
                    'line_items' => [[
                        'price_data' => [
                            'currency'     => 'eur',
                            'unit_amount'  => (int)round($total * 100),
                            'product_data' => [
                                'name'        => "Ordine {$order['order_number']} - {$tenant['name']}",
                                'description' => "Ordine " . order_type_label($orderType),
                            ],
                        ],
                        'quantity' => 1,
                    ]],
                    'mode'        => 'payment',
                    'success_url' => url("{$tenant['slug']}/order/success") . '?order=' . $order['order_number'],
                    'cancel_url'  => url("{$tenant['slug']}/order") . '?cancelled=1',
                    'metadata'    => [
                        'order_id'  => $orderId,
                        'tenant_id' => $tenant['id'],
                    ],
                    'expires_at' => time() + 1800,
                ]);

                // Save stripe session ID
                $orderModel->updatePaymentStatus($orderId, 'pending');
                $stmt = \App\Core\Database::getInstance()->prepare(
                    'UPDATE orders SET stripe_session_id = :sid WHERE id = :id'
                );
                $stmt->execute(['sid' => $session->id, 'id' => $orderId]);

                $responseData['stripe_checkout_url'] = $session->url;
                $responseData['message'] = 'Ordine creato. Verrai reindirizzato al pagamento.';
            } catch (\Exception $e) {
                app_log('Stripe order checkout error: ' . $e->getMessage(), 'error');
                $responseData['message'] = 'Ordine creato. Contatta il ristorante per il pagamento.';
            }
        } else {
            $responseData['message'] = $status === 'accepted'
                ? 'Ordine confermato!'
                : 'Ordine ricevuto! Il ristorante confermerà a breve.';
        }

        Response::success($responseData, $responseData['message'], 201);
    }

    /**
     * Risolve il tenant da slug con check attivo + subscription + service.
     */
    private function resolveTenant(Request $request): array
    {
        $slug = $request->param('slug');
        $tenantModel = new Tenant();
        $tenant = $tenantModel->findBySlug($slug);

        if (!$tenant || !$tenant['is_active']) {
            Response::error('Ristorante non trovato.', 'TENANT_NOT_FOUND', 404);
        }

        if ($tenantModel->getExpiredSubscription((int)$tenant['id'])) {
            Response::error('Il servizio non è al momento disponibile.', 'SUBSCRIPTION_EXPIRED', 403);
        }

        if (!$tenantModel->canUseService((int)$tenant['id'], 'online_ordering')) {
            Response::error('Il servizio ordini online non è disponibile.', 'SERVICE_NOT_AVAILABLE', 403);
        }

        return $tenant;
    }

    /**
     * Genera gli slot di pickup disponibili basandosi su ordering_hours e prep_minutes.
     */
    private function generatePickupSlots(array $tenant): array
    {
        $slots = [];
        $interval = (int)($tenant['ordering_pickup_interval'] ?? 15);
        $prepMinutes = (int)($tenant['ordering_prep_minutes'] ?? 30);
        $maxPerSlot = (int)($tenant['ordering_max_per_slot'] ?? 10);

        // Parse ordering_hours (JSON: {"1": {"open": "11:00", "close": "22:00"}, ...})
        $hours = json_decode($tenant['ordering_hours'] ?? '{}', true) ?: [];

        // Use opening_hours as fallback if ordering_hours not set
        if (empty($hours)) {
            $openingHours = json_decode($tenant['opening_hours'] ?? '{}', true) ?: [];
            $hours = $openingHours;
        }

        $today = date('N'); // 1=Mon, 7=Sun
        $dayHours = $hours[$today] ?? null;

        if (!$dayHours || empty($dayHours['open']) || empty($dayHours['close'])) {
            return $slots;
        }

        $now = time();
        $earliestPickup = $now + ($prepMinutes * 60);

        $openTime = strtotime('today ' . $dayHours['open']);
        $closeTime = strtotime('today ' . $dayHours['close']);

        $orderModel = new Order();
        $current = max($openTime, $earliestPickup);
        // Round up to next interval
        $remainder = ($current - $openTime) % ($interval * 60);
        if ($remainder > 0) {
            $current += ($interval * 60) - $remainder;
        }

        while ($current < $closeTime) {
            $slotTime = date('Y-m-d H:i:s', $current);
            $slotCount = $orderModel->countBySlot((int)$tenant['id'], $slotTime);

            if ($slotCount < $maxPerSlot) {
                $slots[] = [
                    'time'      => date('H:i', $current),
                    'datetime'  => $slotTime,
                    'available' => $maxPerSlot - $slotCount,
                ];
            }

            $current += $interval * 60;
        }

        return $slots;
    }
}
