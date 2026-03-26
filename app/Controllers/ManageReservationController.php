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

        if (in_array($reservation['status'], ['cancelled', 'arrived', 'noshow'])) {
            flash('danger', 'Questa prenotazione non può essere annullata.');
            Response::redirect(url("manage/{$token}"));
        }

        $reservationModel->updateStatus($reservation['id'], 'cancelled');
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
                error_log('Cancellation notification failed: ' . $e->getMessage());
            }
        }

        flash('success', 'La tua prenotazione è stata annullata.');
        Response::redirect(url("manage/{$token}"));
    }
}