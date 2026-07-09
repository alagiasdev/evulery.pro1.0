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

    private function isPastDate(array $reservation): bool
    {
        if (empty($reservation['reservation_date'])) {
            return false;
        }
        return strtotime($reservation['reservation_date']) < strtotime(date('Y-m-d'));
    }

    /**
     * True se il cliente NON puo' piu' annullare da solo:
     *  - cutoff > 0: manca meno del limite (ore) all'orario della prenotazione
     *    (copre anche il caso di orario gia' passato);
     *  - cutoff = 0 (nessun limite): mantiene il comportamento storico, ossia
     *    blocca solo le prenotazioni di una data gia' passata.
     * Il limite (`cancellation_cutoff_hours`) e' per-ristorante. Fuso Europe/Rome.
     */
    private function isTooLateToCancel(array $reservation): bool
    {
        $cutoff = (int)($reservation['cancellation_cutoff_hours'] ?? 0);
        if ($cutoff <= 0) {
            return $this->isPastDate($reservation);
        }
        if (empty($reservation['reservation_date']) || empty($reservation['reservation_time'])) {
            return false;
        }
        $resTs = strtotime($reservation['reservation_date'] . ' ' . $reservation['reservation_time']);
        if ($resTs === false) {
            return false;
        }
        return time() > ($resTs - $cutoff * 3600);
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

        $tooLate = $this->isTooLateToCancel($reservation);
        view('manage/show', [
            'title'       => 'La tua prenotazione',
            'reservation' => $reservation,
            'token'       => $token,
            'tooLate'     => $tooLate,
            // Messaggio "contatta il ristorante" solo se la prenotazione e' futura
            // ma ormai dentro la finestra di blocco (non per le date gia' passate).
            'cutoffBlocked' => $tooLate && !$this->isPastDate($reservation),
            'cutoffHours' => (int)($reservation['cancellation_cutoff_hours'] ?? 0),
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

        // Enforcement lato server del limite orario: protegge anche dalla POST
        // diretta all'URL (oltre alla scomparsa del bottone in pagina).
        if ($this->isTooLateToCancel($reservation)) {
            flash('danger', 'Non è più possibile annullare online: sei troppo vicino all\'orario della prenotazione. Ti chiediamo di contattare direttamente il ristorante.');
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