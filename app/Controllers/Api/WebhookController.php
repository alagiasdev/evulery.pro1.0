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
                    if ($reservation) {
                        $db = Database::getInstance();
                        // Guardia atomica: la transizione avviene SOLO se la prenotazione è
                        // ancora 'pending' e non pagata. Impedisce che un pagamento tardivo
                        // "resusciti" una prenotazione annullata; il gate su rowCount() evita
                        // email/log duplicati sui retry Stripe (consegne at-least-once).
                        $stmt = $db->prepare(
                            'UPDATE reservations SET deposit_paid = 1, status = "confirmed", stripe_payment_id = :payment_id
                             WHERE id = :id AND status = "pending" AND deposit_paid = 0'
                        );
                        $stmt->execute([
                            'payment_id' => $session->payment_intent ?? $session->id,
                            'id'         => $reservationId,
                        ]);

                        if ($stmt->rowCount() === 1) {
                            $logModel->create((int)$reservationId, 'pending', 'confirmed', null, 'Caparra pagata via Stripe');

                            // Email di conferma al cliente (solo alla vera transizione)
                            $full = $reservationModel->findWithCustomer((int)$reservationId);
                            $tenantId = $session->metadata->tenant_id ?? ($reservation['tenant_id'] ?? null);
                            if ($full && $tenantId) {
                                $tenant = (new Tenant())->findById((int)$tenantId);
                                if ($tenant) {
                                    MailService::sendReservationConfirmation($full, $tenant);
                                }
                            }
                        } else {
                            app_log("Webhook checkout.completed: prenotazione #{$reservationId} non pagabile (stato='{$reservation['status']}', deposit_paid={$reservation['deposit_paid']}) — nessuna azione (retry duplicato o annullata)", 'info');
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

        // [09] Non marcare MAI 'secured' senza i dati per addebitare davvero: servono
        // sia il payment method sia il customer (chargeGuarantee li esige entrambi). Se
        // il retrieve della SetupIntent è fallito (chiave non decifrabile, errore API)
        // restano null: lasciamo guarantee_status='pending' (niente FALSA garanzia),
        // logghiamo per diagnosi e NON confermiamo/inviamo email. Meglio "in attesa"
        // visibile che una garanzia fittizia non addebitabile.
        if (empty($paymentMethodId) || empty($customerId)) {
            app_log(sprintf(
                'Guarantee setup INCOMPLETO prenotazione #%d (tenant %s): payment_method=%s, customer=%s, setup_intent=%s — lasciata in attesa, NON marcata secured.',
                $reservationId,
                $tenantId ?? 'n/a',
                $paymentMethodId ? 'ok' : 'NULL',
                $customerId ? 'ok' : 'NULL',
                $setupIntentId ?? 'n/a'
            ), 'error');
            return;
        }

        $db = Database::getInstance();
        // Guardia atomica: la transizione 'pending' -> 'secured' vince UNA sola volta.
        // Il check a inizio metodo (guarantee_status !== 'pending') copre le consegne
        // duplicate sequenziali; questa guardia + il gate su rowCount() copre anche due
        // consegne CONCORRENTI, evitando email/log doppi.
        $stmt = $db->prepare(
            'UPDATE reservations
             SET guarantee_status = "secured", status = "confirmed",
                 stripe_customer_id = :cust, stripe_payment_method_id = :pm, stripe_setup_intent_id = :si
             WHERE id = :id AND guarantee_status = "pending"'
        );
        $stmt->execute([
            'cust' => $customerId,
            'pm'   => $paymentMethodId,
            'si'   => $setupIntentId,
            'id'   => $reservationId,
        ]);
        if ($stmt->rowCount() !== 1) {
            // Un'altra consegna concorrente ha già registrato la garanzia: niente doppioni.
            app_log("Webhook guarantee setup: prenotazione #{$reservationId} già processata (consegna duplicata) — skip email/log", 'info');
            return;
        }
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
