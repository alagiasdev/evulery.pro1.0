<?php

namespace App\Services;

use App\Core\Database;
use App\Models\EmergencyClosure;
use App\Models\Reservation;
use App\Models\ReservationLog;
use App\Models\SlotOverride;
use App\Models\TimeSlot;
use PDO;

/**
 * Chiusura straordinaria (emergenze). Orchestratore:
 *  - blocca le nuove prenotazioni nel periodo/fascia (slot_overrides)
 *  - gestisce le prenotazioni esistenti: ANNULLA subito (mode=cancel) oppure
 *    SOSPENDE (mode=suspend, recuperabili alla riapertura)
 *  - invia le email ai clienti
 *
 * Le scritture DB stanno in transazione; le email partono DOPO il commit
 * (best-effort) per non far fallire un annullamento gia' valido se l'SMTP ha
 * un problema.
 */
class EmergencyClosureService
{
    private PDO $db;
    private EmergencyClosure $closures;
    private Reservation $reservations;
    private ReservationLog $logs;
    private SlotOverride $overrides;
    private TimeSlot $slots;

    public function __construct()
    {
        $this->db           = Database::getInstance();
        $this->closures     = new EmergencyClosure();
        $this->reservations = new Reservation();
        $this->logs         = new ReservationLog();
        $this->overrides    = new SlotOverride();
        $this->slots        = new TimeSlot();
    }

    /**
     * Prenotazioni che verrebbero interessate dalla chiusura (anteprima).
     */
    public function affectedReservations(int $tenantId, string $dateFrom, string $dateTo, ?string $timeFrom, ?string $timeTo): array
    {
        return $this->reservations->findInClosureWindow($tenantId, $dateFrom, $dateTo, $timeFrom, $timeTo);
    }

    /**
     * Applica la chiusura. $p: date_from, date_to, time_from, time_to,
     * scope_label, message. Ritorna ['closure_id', 'affected', 'mode'].
     */
    public function apply(int $tenantId, array $tenant, array $p, string $mode, ?int $userId): array
    {
        $dateFrom = $p['date_from'];
        $dateTo   = $p['date_to'];
        $timeFrom = $p['time_from'] ?? null;
        $timeTo   = $p['time_to'] ?? null;
        $note     = 'Chiusura straordinaria';

        $affected = $this->affectedReservations($tenantId, $dateFrom, $dateTo, $timeFrom, $timeTo);

        $this->db->beginTransaction();
        try {
            // 1) blocca le nuove prenotazioni
            $blockedIds = $this->block($tenantId, $dateFrom, $dateTo, $timeFrom, $timeTo, $note);

            // 2) crea l'evento chiusura
            $closureId = $this->closures->create([
                'tenant_id'            => $tenantId,
                'date_from'            => $dateFrom,
                'date_to'              => $dateTo,
                'time_from'            => $timeFrom,
                'time_to'              => $timeTo,
                'scope_label'          => $p['scope_label'] ?? 'Giorno intero',
                'mode'                 => $mode,
                'message'              => $p['message'] ?? null,
                'blocked_override_ids' => $blockedIds,
                'affected_count'       => count($affected),
                'created_by'           => $userId,
            ]);

            // 3) gestisci le prenotazioni esistenti
            $newStatus = $mode === 'suspend' ? 'suspended' : 'cancelled';
            $logNote   = $mode === 'suspend'
                ? 'Servizio sospeso (chiusura straordinaria)'
                : 'Annullata per chiusura straordinaria';

            foreach ($affected as $r) {
                $prev = $r['status'];
                $this->closures->addItem($closureId, (int)$r['id'], $prev);
                $this->reservations->updateStatus((int)$r['id'], $newStatus, $mode === 'cancel' ? 'staff' : null);
                $this->logs->create((int)$r['id'], $prev, $newStatus, $userId, $logNote);
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        // 4) email DOPO il commit
        foreach ($affected as $r) {
            try {
                if ($mode === 'suspend') {
                    \App\Services\MailService::sendEmergencySuspended($r, $tenant, $p);
                } else {
                    \App\Services\MailService::sendEmergencyCancelled($r, $tenant, $p);
                }
            } catch (\Throwable $e) {
                app_log('EmergencyClosure: invio email fallito per prenotazione #' . $r['id'] . ' — ' . $e->getMessage(), 'error');
            }
        }

        return ['closure_id' => $closureId, 'affected' => count($affected), 'mode' => $mode];
    }

    /**
     * Riapertura (solo modalita' suspend): le prenotazioni FUTURE tornano allo
     * stato precedente (recupero) con email "tutto risolto"; quelle il cui
     * servizio e' gia' passato vengono annullate con email di scuse + invito a
     * riprenotare. Rimuove il blocco nuove prenotazioni.
     */
    public function reopen(int $tenantId, array $tenant, int $closureId): array
    {
        $closure = $this->closures->findById($closureId);
        if (!$closure || (int)$closure['tenant_id'] !== $tenantId || $closure['status'] !== 'active') {
            return ['recovered' => 0, 'lost' => 0];
        }

        $items = $this->closures->itemsWithReservations($closureId);
        $now   = date('Y-m-d H:i:s');
        $recovered = [];
        $lost = [];

        $this->db->beginTransaction();
        try {
            foreach ($items as $it) {
                // agisci solo su quelle ancora sospese (il ristoratore potrebbe
                // averne gestita qualcuna a mano nel frattempo)
                if ($it['status'] !== 'suspended') {
                    continue;
                }
                $datetime = $it['reservation_date'] . ' ' . $it['reservation_time'];
                $prev = in_array($it['previous_status'], ['confirmed', 'pending'], true) ? $it['previous_status'] : 'confirmed';

                if ($datetime >= $now) {
                    // futuro: recupera
                    $this->reservations->updateStatus((int)$it['id'], $prev);
                    $this->logs->create((int)$it['id'], 'suspended', $prev, null, 'Servizio ripristinato (riapertura)');
                    $recovered[] = $it;
                } else {
                    // passato: annulla
                    $this->reservations->updateStatus((int)$it['id'], 'cancelled', 'staff');
                    $this->logs->create((int)$it['id'], 'suspended', 'cancelled', null, 'Servizio non recuperabile (gia\' trascorso)');
                    $lost[] = $it;
                }
            }

            // rimuovi il blocco e chiudi l'evento
            $this->overrides->deleteByIds($this->closures->blockedOverrideIds($closure), $tenantId);
            $this->closures->markResolved($closureId, 'reopened');

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        foreach ($recovered as $r) {
            try { \App\Services\MailService::sendEmergencyRecovered($r, $tenant); } catch (\Throwable $e) {
                app_log('EmergencyClosure reopen: email recupero fallita #' . $r['id'] . ' — ' . $e->getMessage(), 'error');
            }
        }
        foreach ($lost as $r) {
            try { \App\Services\MailService::sendEmergencyCancelled($r, $tenant, ['rebook' => true]); } catch (\Throwable $e) {
                app_log('EmergencyClosure reopen: email scuse fallita #' . $r['id'] . ' — ' . $e->getMessage(), 'error');
            }
        }

        return ['recovered' => count($recovered), 'lost' => count($lost)];
    }

    /**
     * Chiusura definitiva (da una sospensione): tutte le prenotazioni ancora
     * sospese vengono annullate con email di scuse + invito a riprenotare. Il
     * blocco nuove prenotazioni resta (il locale e' chiuso).
     */
    public function closeDefinitive(int $tenantId, array $tenant, int $closureId): array
    {
        $closure = $this->closures->findById($closureId);
        if (!$closure || (int)$closure['tenant_id'] !== $tenantId || $closure['status'] !== 'active') {
            return ['cancelled' => 0];
        }

        $items = $this->closures->itemsWithReservations($closureId);
        $cancelled = [];

        $this->db->beginTransaction();
        try {
            foreach ($items as $it) {
                if ($it['status'] !== 'suspended') {
                    continue;
                }
                $this->reservations->updateStatus((int)$it['id'], 'cancelled', 'staff');
                $this->logs->create((int)$it['id'], 'suspended', 'cancelled', null, 'Chiusura straordinaria confermata');
                $cancelled[] = $it;
            }
            $this->closures->markResolved($closureId, 'closed');
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        foreach ($cancelled as $r) {
            try { \App\Services\MailService::sendEmergencyCancelled($r, $tenant, ['rebook' => true]); } catch (\Throwable $e) {
                app_log('EmergencyClosure close: email scuse fallita #' . $r['id'] . ' — ' . $e->getMessage(), 'error');
            }
        }

        return ['cancelled' => count($cancelled)];
    }

    /**
     * Inserisce i blocchi in slot_overrides e ritorna gli id creati.
     * Giorno intero → un blocco full-day per data. Fascia → blocco per ogni
     * slot configurato che cade in [timeFrom, timeTo).
     */
    private function block(int $tenantId, string $dateFrom, string $dateTo, ?string $timeFrom, ?string $timeTo, string $note): array
    {
        $ids = [];
        $cursor = new \DateTime($dateFrom);
        $end = new \DateTime($dateTo);

        while ($cursor <= $end) {
            $date = $cursor->format('Y-m-d');

            if ($timeFrom === null || $timeTo === null) {
                $ids[] = $this->overrides->blockFullDay($tenantId, $date, $note);
            } else {
                $dayOfWeek = (int)$cursor->format('N') - 1; // 0=Lun .. 6=Dom
                foreach ($this->slots->findByTenantAndDay($tenantId, $dayOfWeek) as $slot) {
                    $slotTime = $slot['slot_time']; // HH:MM:SS
                    if ($slotTime >= $timeFrom && $slotTime < $timeTo) {
                        $ids[] = $this->overrides->blockSlot($tenantId, $date, substr($slotTime, 0, 8), $note);
                    }
                }
            }
            $cursor->modify('+1 day');
        }

        return array_values(array_unique($ids));
    }
}
