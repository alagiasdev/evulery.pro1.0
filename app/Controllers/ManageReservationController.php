<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Reservation;
use App\Models\ReservationLog;
use App\Models\Tenant;
use App\Services\MailService;
use App\Services\NotificationService;

class ManageReservationController
{
    /**
     * Scadenza magic link prenotazione: 30 giorni dopo la data della
     * prenotazione. Mitigation per il caso in cui il link sia stato leakato
     * (es. cliente lo gira a terzi, screenshot pubblicato, email inoltrata).
     * Audit finding #3 sistemato 2026-06-08.
     */
    private const TOKEN_TTL_DAYS_AFTER_RESERVATION = 30;

    private function isTokenExpired(array $reservation): bool
    {
        if (empty($reservation['reservation_date'])) {
            return false;
        }
        $resDate = strtotime($reservation['reservation_date']);
        if ($resDate === false) {
            return false;
        }
        $daysSince = (time() - $resDate) / 86400;
        return $daysSince > self::TOKEN_TTL_DAYS_AFTER_RESERVATION;
    }

    public function show(Request $request): void
    {
        $token = $request->param('token');
        $reservation = (new Reservation())->findByToken($token);

        if (!$reservation) {
            view('manage/not-found', [
                'title' => 'Prenotazione non trovata',
            ], 'minimal');
            return;
        }

        if ($this->isTokenExpired($reservation)) {
            view('manage/not-found', [
                'title'    => 'Link scaduto',
                'heading'  => 'Link scaduto',
                'subtitle' => 'Questo link di gestione non è più valido',
                'body'     => 'Per ragioni di sicurezza il link di gestione scade dopo 30 giorni dalla data della prenotazione. La tua prenotazione esiste ancora — per qualsiasi assistenza contatta direttamente il ristorante.',
                'icon'     => 'bi-clock-history',
            ], 'minimal');
            return;
        }

        view('manage/show', [
            'title'       => 'La tua prenotazione',
            'reservation' => $reservation,
            'token'       => $token,
        ], 'minimal');
    }

    public function cancel(Request $request): void
    {
        $token = $request->param('token');
        $reservationModel = new Reservation();
        $reservation = $reservationModel->findByToken($token);

        if (!$reservation) {
            flash('danger', 'Prenotazione non trovata.');
            Response::redirect(url("manage/{$token}"));
        }

        if ($this->isTokenExpired($reservation)) {
            flash('danger', 'Questo link di gestione è scaduto.');
            Response::redirect(url("manage/{$token}"));
        }

        if (in_array($reservation['status'], ['cancelled', 'arrived', 'noshow'])) {
            flash('danger', 'Questa prenotazione non può essere annullata.');
            Response::redirect(url("manage/{$token}"));
        }

        $reservationModel->updateStatus($reservation['id'], 'cancelled', 'customer');
        (new ReservationLog())->create(
            $reservation['id'],
            $reservation['status'],
            'cancelled',
            null,
            'Annullata dal cliente via link'
        );

        // Notify restaurant owner (email + campanella + push)
        $tenant = (new Tenant())->findById((int)$reservation['tenant_id']);
        if ($tenant) {
            try {
                (new NotificationService())->notifyCancellation($reservation, $tenant, 'cliente');
            } catch (\Throwable $e) {
                app_log('Cancellation notification failed (magic link): ' . $e->getMessage(), 'warning');
            }
        }

        flash('success', 'La tua prenotazione è stata annullata.');
        Response::redirect(url("manage/{$token}"));
    }
}