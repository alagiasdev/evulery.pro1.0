<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\PushSubscription;
use App\Models\Tenant;

class NotificationService
{
    /**
     * Notify restaurant owner about a new reservation.
     * Email: controlled by tenant toggle. Campanella + Push: controlled by service gating.
     */
    public function notifyNewReservation(array $reservation, array $tenant): void
    {
        $tenantId = (int)$tenant['id'];

        // 1. Email (if toggle enabled)
        if (!empty($tenant['notify_new_reservation'])) {
            try {
                MailService::sendNewReservationNotification($reservation, $tenant);
            } catch (\Throwable $e) {
                error_log("NotificationService: email failed for tenant {$tenantId}: " . $e->getMessage());
            }
        }

        // 2. Campanella + Push (if service enabled)
        if ($this->canPush($tenantId)) {
            $firstName = $reservation['first_name'] ?? '';
            $lastName  = $reservation['last_name'] ?? '';
            $date      = $reservation['reservation_date'] ?? '';
            $time      = substr($reservation['reservation_time'] ?? '', 0, 5);
            $partySize = (int)($reservation['party_size'] ?? 0);

            $placeholders = [
                '{nome}' => "{$firstName} {$lastName}",
                '{data}' => $date,
                '{ora}'  => $time,
                '{coperti}' => $partySize,
            ];
            $title = $this->resolveTemplate($tenant['notif_title_new_reservation'] ?? '', 'Nuova prenotazione', $placeholders);
            $body  = $this->resolveTemplate($tenant['notif_body_new_reservation'] ?? '', "{$firstName} {$lastName} — {$date} ore {$time}, {$partySize} persone", $placeholders);

            $data = [
                'reservation_id' => (int)($reservation['id'] ?? 0),
                'customer_name'  => "{$firstName} {$lastName}",
                'url'            => url("dashboard/reservations/{$reservation['id']}"),
            ];

            // Insert notification record
            (new Notification())->create($tenantId, 'new_reservation', $title, $body, $data);

            // Send push
            $this->sendPush($tenantId, $title, $body, $data);
        }
    }

    /**
     * Notify restaurant owner about a cancellation.
     */
    public function notifyCancellation(array $reservation, array $tenant, string $cancelledBy = 'cliente'): void
    {
        $tenantId = (int)$tenant['id'];

        // 1. Email (if toggle enabled)
        if (!empty($tenant['notify_cancellation'])) {
            try {
                MailService::sendCancellationNotification($reservation, $tenant, $cancelledBy);
            } catch (\Throwable $e) {
                error_log("NotificationService: cancellation email failed for tenant {$tenantId}: " . $e->getMessage());
            }
        }

        // 2. Campanella + Push
        if ($this->canPush($tenantId)) {
            $firstName = $reservation['first_name'] ?? '';
            $lastName  = $reservation['last_name'] ?? '';
            $date      = $reservation['reservation_date'] ?? '';
            $time      = substr($reservation['reservation_time'] ?? '', 0, 5);

            $cancelLabel = $cancelledBy === 'cliente' ? 'dal cliente' : 'dallo staff';
            $placeholders = [
                '{nome}' => "{$firstName} {$lastName}",
                '{data}' => $date,
                '{ora}'  => $time,
                '{da}'   => $cancelLabel,
            ];
            $title = $this->resolveTemplate($tenant['notif_title_cancellation'] ?? '', 'Prenotazione cancellata', $placeholders);
            $body  = $this->resolveTemplate($tenant['notif_body_cancellation'] ?? '', "{$firstName} {$lastName} ({$date} ore {$time}) — annullata {$cancelLabel}", $placeholders);

            $data = [
                'reservation_id' => (int)($reservation['id'] ?? 0),
                'customer_name'  => "{$firstName} {$lastName}",
                'url'            => url('dashboard/reservations'),
            ];

            (new Notification())->create($tenantId, 'cancellation', $title, $body, $data);
            $this->sendPush($tenantId, $title, $body, $data);
        }
    }

    /**
     * Notify restaurant owner that a deposit was received.
     */
    public function notifyDepositReceived(array $reservation, array $tenant): void
    {
        $tenantId = (int)$tenant['id'];

        if ($this->canPush($tenantId)) {
            $firstName = $reservation['first_name'] ?? '';
            $lastName  = $reservation['last_name'] ?? '';
            $amount    = number_format((float)($reservation['deposit_amount'] ?? 0), 2, ',', '.');

            $placeholders = [
                '{nome}'    => "{$firstName} {$lastName}",
                '{importo}' => $amount,
            ];
            $title = $this->resolveTemplate($tenant['notif_title_deposit'] ?? '', 'Caparra ricevuta', $placeholders);
            $body  = $this->resolveTemplate($tenant['notif_body_deposit'] ?? '', "{$firstName} {$lastName} — €{$amount}", $placeholders);

            $data = [
                'reservation_id' => (int)($reservation['id'] ?? 0),
                'url'            => url("dashboard/reservations/{$reservation['id']}"),
            ];

            (new Notification())->create($tenantId, 'deposit_received', $title, $body, $data);
            $this->sendPush($tenantId, $title, $body, $data);
        }
    }

    /**
     * Send Web Push to all registered devices of a tenant.
     */
    public function sendPush(int $tenantId, string $title, string $body, ?array $data = null): void
    {
        $vapidPublic  = env('VAPID_PUBLIC_KEY', '');
        $vapidPrivate = env('VAPID_PRIVATE_KEY', '');
        $vapidSubject = env('VAPID_SUBJECT', 'mailto:support@evulery.it');

        if (!$vapidPublic || !$vapidPrivate) {
            return; // VAPID not configured
        }

        if (!class_exists(\Minishlink\WebPush\WebPush::class)) {
            return; // Library not installed
        }

        $subscriptions = (new PushSubscription())->getByTenant($tenantId);
        if (empty($subscriptions)) {
            return;
        }

        try {
            $auth = [
                'VAPID' => [
                    'subject'    => $vapidSubject,
                    'publicKey'  => $vapidPublic,
                    'privateKey' => $vapidPrivate,
                ],
            ];

            $webPush = new \Minishlink\WebPush\WebPush($auth);

            $payload = json_encode([
                'title' => $title,
                'body'  => $body,
                'url'   => $data['url'] ?? '/dashboard',
                'tag'   => $data['reservation_id'] ?? 'notification',
            ]);

            foreach ($subscriptions as $sub) {
                $webPush->queueNotification(
                    \Minishlink\WebPush\Subscription::create([
                        'endpoint'        => $sub['endpoint'],
                        'publicKey'       => $sub['p256dh'],
                        'authToken'       => $sub['auth'],
                    ]),
                    $payload
                );
            }

            $pushModel = new PushSubscription();
            foreach ($webPush->flush() as $report) {
                if ($report->isSubscriptionExpired()) {
                    $pushModel->deleteByEndpoint($report->getRequest()->getUri()->__toString());
                }
            }
        } catch (\Throwable $e) {
            error_log("NotificationService: push failed for tenant {$tenantId}: " . $e->getMessage());
        }
    }

    /**
     * Resolve a notification template with placeholders, falling back to default.
     */
    private function resolveTemplate(?string $custom, string $default, array $placeholders): string
    {
        $template = (!empty($custom)) ? $custom : $default;
        return strtr($template, $placeholders);
    }

    /**
     * Check if tenant has push_notifications service enabled.
     */
    private function canPush(int $tenantId): bool
    {
        return (new Tenant())->canUseService($tenantId, 'push_notifications');
    }
}
