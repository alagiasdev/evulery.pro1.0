<?php

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\ReservationLog;
use App\Models\Tenant;
use App\Services\MailService;

class WebhookController
{
    public function handle(Request $request): void
    {
        $payload = file_get_contents('php://input');
        $sig = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        if (!$sig) {
            Response::error('Webhook non configurato.', 'WEBHOOK_ERROR', 400);
        }

        // Try to determine tenant for webhook secret lookup
        $webhookSecret = '';
        $payloadData = json_decode($payload, true);
        $tenantId = $payloadData['data']['object']['metadata']['tenant_id'] ?? null;

        if ($tenantId) {
            $tenantRecord = (new Tenant())->findById((int)$tenantId);
            if ($tenantRecord && !empty($tenantRecord['stripe_wh_secret'])) {
                $webhookSecret = decrypt_value($tenantRecord['stripe_wh_secret']) ?: '';
            }
        }

        // Fallback to platform webhook secret
        if (!$webhookSecret) {
            $webhookSecret = env('STRIPE_WEBHOOK_SECRET', '');
        }

        if (!$webhookSecret) {
            Response::error('Webhook non configurato.', 'WEBHOOK_ERROR', 400);
        }

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig, $webhookSecret);
        } catch (\Exception $e) {
            app_log('Stripe webhook error: ' . $e->getMessage(), 'error');
            Response::error('Firma non valida.', 'INVALID_SIGNATURE', 400);
        }

        $reservationModel = new Reservation();
        $logModel = new ReservationLog();

        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $reservationId = $session->metadata->reservation_id ?? null;
                $orderId = $session->metadata->order_id ?? null;
                $sessionMode = $session->mode ?? 'payment';

                if ($reservationId && $sessionMode === 'setup') {
                    // Carta a garanzia: il cliente ha registrato la carta (nessun addebito)
                    $this->handleGuaranteeSetup($session, (int)$reservationId, $reservationModel, $logModel);
                } elseif ($reservationId) {
                    $reservation = $reservationModel->findById((int)$reservationId);
                    if ($reservation && !$reservation['deposit_paid']) {
                        $db = Database::getInstance();
                        $stmt = $db->prepare(
                            'UPDATE reservations SET deposit_paid = 1, status = "confirmed", stripe_payment_id = :payment_id WHERE id = :id'
                        );
                        $stmt->execute([
                            'payment_id' => $session->payment_intent ?? $session->id,
                            'id'         => $reservationId,
                        ]);
                        $logModel->create((int)$reservationId, 'pending', 'confirmed', null, 'Caparra pagata via Stripe');

                        // Send confirmation email to customer
                        $full = $reservationModel->findWithCustomer((int)$reservationId);
                        $tenantId = $session->metadata->tenant_id ?? ($reservation['tenant_id'] ?? null);
                        if ($full && $tenantId) {
                            $tenant = (new Tenant())->findById((int)$tenantId);
                            if ($tenant) {
                                MailService::sendReservationConfirmation($full, $tenant);
                            }
                        }
                    }
                } elseif ($orderId) {
                    // Online ordering payment
                    $orderModel = new Order();
                    $order = $orderModel->findByStripeSession($session->id);
                    if ($order && $order['payment_status'] !== 'paid') {
                        $orderModel->updatePaymentStatus((int)$order['id'], 'paid');
                        app_log("Order #{$order['order_number']} payment confirmed via Stripe", 'info');
                    }
                }
                break;

            case 'checkout.session.expired':
                $session = $event->data->object;
                $reservationId = $session->metadata->reservation_id ?? null;

                if ($reservationId) {
                    $reservation = $reservationModel->findById((int)$reservationId);
                    // Le caparre richieste MANUALMENTE dal ristoratore (su prenotazioni
                    // gia' accettate) non si auto-cancellano se il cliente tarda a pagare:
                    // la decisione resta al ristoratore.
                    if ($reservation && $reservation['status'] === 'pending' && !$reservation['deposit_paid']
                        && empty($reservation['deposit_manual_request'])) {
                        $isGuarantee = ($reservation['guarantee_status'] ?? 'none') === 'pending';
                        $reservationModel->updateStatus((int)$reservationId, 'cancelled', 'system');
                        $logModel->create(
                            (int)$reservationId, 'pending', 'cancelled', null,
                            $isGuarantee ? 'Carta a garanzia non registrata' : 'Pagamento scaduto'
                        );
                    }
                }
                break;
        }

        Response::json(['received' => true]);
    }

    /**
     * Gestisce il completamento di una Checkout Session in modalità 'setup':
     * il cliente ha registrato la carta a garanzia. Nessun addebito effettuato.
     */
    private function handleGuaranteeSetup(
        $session,
        int $reservationId,
        Reservation $reservationModel,
        ReservationLog $logModel
    ): void {
        $reservation = $reservationModel->findById($reservationId);
        if (!$reservation || ($reservation['guarantee_status'] ?? 'none') !== 'pending') {
            return; // già processato o non pertinente
        }

        $customerId    = $session->customer ?? null;
        $setupIntentId = $session->setup_intent ?? null;
        $paymentMethodId = null;
        $tenant = null;
        $tenantId = $session->metadata->tenant_id ?? ($reservation['tenant_id'] ?? null);

        // Recupera il payment method dalla SetupIntent (serve la chiave del tenant)
        if ($setupIntentId && $tenantId) {
            $tenant = (new Tenant())->findById((int)$tenantId);
            if ($tenant && !empty($tenant['stripe_sk'])) {
                try {
                    $key = decrypt_value($tenant['stripe_sk']);
                    if ($key) {
                        \Stripe\Stripe::setApiKey($key);
                        $si = \Stripe\SetupIntent::retrieve($setupIntentId);
                        $paymentMethodId = $si->payment_method ?? null;
                        if (!$customerId) {
                            $customerId = $si->customer ?? null;
                        }
                    }
                } catch (\Exception $e) {
                    app_log('Stripe SetupIntent retrieve error: ' . $e->getMessage(), 'error');
                }
            }
        }

        $db = Database::getInstance();
        $stmt = $db->prepare(
            'UPDATE reservations
             SET guarantee_status = "secured", status = "confirmed",
                 stripe_customer_id = :cust, stripe_payment_method_id = :pm, stripe_setup_intent_id = :si
             WHERE id = :id'
        );
        $stmt->execute([
            'cust' => $customerId,
            'pm'   => $paymentMethodId,
            'si'   => $setupIntentId,
            'id'   => $reservationId,
        ]);
        $logModel->create($reservationId, 'pending', 'confirmed', null, 'Carta a garanzia registrata');

        // Email di conferma al cliente (il ristoratore è già stato notificato alla creazione)
        $full = $reservationModel->findWithCustomer($reservationId);
        if ($full && $tenantId) {
            if (!$tenant) {
                $tenant = (new Tenant())->findById((int)$tenantId);
            }
            if ($tenant) {
                MailService::sendReservationConfirmation($full, $tenant);
            }
        }
    }
}
