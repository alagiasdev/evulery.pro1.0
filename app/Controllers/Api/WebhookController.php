<?php

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Reservation;
use App\Models\ReservationLog;

class WebhookController
{
    public function handle(Request $request): void
    {
        $payload = file_get_contents('php://input');
        $sig = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $webhookSecret = env('STRIPE_WEBHOOK_SECRET', '');

        if (!$webhookSecret || !$sig) {
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

                if ($reservationId) {
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
                    }
                }
                break;

            case 'checkout.session.expired':
                $session = $event->data->object;
                $reservationId = $session->metadata->reservation_id ?? null;

                if ($reservationId) {
                    $reservation = $reservationModel->findById((int)$reservationId);
                    if ($reservation && $reservation['status'] === 'pending' && !$reservation['deposit_paid']) {
                        $reservationModel->updateStatus((int)$reservationId, 'cancelled');
                        $logModel->create((int)$reservationId, 'pending', 'cancelled', null, 'Pagamento scaduto');
                    }
                }
                break;
        }

        Response::json(['received' => true]);
    }
}
