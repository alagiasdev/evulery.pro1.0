<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Table;
use App\Models\Tenant;
use PDO;

/**
 * Logica di assegnazione tavoli.
 *
 * Turni: un tavolo è occupato per `table_duration` + `table_turnover_buffer`
 * minuti a partire dall'orario prenotato. Due prenotazioni sullo stesso
 * tavolo sono compatibili se distano almeno W = durata + buffer minuti.
 *
 * Auto-assegnazione: scorre i tavoli attivi in ordine di priorità e prende
 * il primo libero con capienza sufficiente; se nessun singolo tavolo basta,
 * cerca una coppia combinabile entrambi liberi. Mai bloccante.
 */
class TableAssigner
{
    private PDO $db;
    /** Stati prenotazione che occupano fisicamente un tavolo. */
    private const BUSY_STATUSES = ['confirmed', 'pending', 'arrived'];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Assegna automaticamente un tavolo (o una combinazione) alla prenotazione
     * e scrive il risultato. Ritorna gli id dei tavoli assegnati ([] se nessuno).
     */
    public function autoAssign(int $tenantId, int $reservationId, string $date, string $time, int $partySize): array
    {
        $tenant = (new Tenant())->findById($tenantId);
        if (!$tenant || empty($tenant['table_auto_assign'])) {
            return [];
        }

        $ids = $this->pickTables($tenantId, $date, $time, $partySize, $reservationId);
        if (!empty($ids)) {
            $this->setAssignment($reservationId, $ids, true);
        }
        return $ids;
    }

    /**
     * Sceglie i tavoli per una prenotazione (singolo o coppia combinabile),
     * senza scrivere nulla. Ritorna gli id ([] se nessuna soluzione).
     */
    public function pickTables(int $tenantId, string $date, string $time, int $partySize, int $excludeReservationId = 0): array
    {
        $window = $this->windowMinutes($tenantId);
        $tables = (new Table())->findActiveByTenant($tenantId); // ordine di priorità
        if (empty($tables)) {
            return [];
        }

        $occ = $this->occupationMap($tenantId, $date, $excludeReservationId);
        $start = $this->timeToMinutes($time);

        // 1) singolo tavolo, in ordine di priorità
        foreach ($tables as $t) {
            if ((int)$t['capacity'] >= $partySize
                && $this->isFree((int)$t['id'], $start, $occ, $window)) {
                return [(int)$t['id']];
            }
        }

        // 2) combinazione di due tavoli
        $byId = [];
        foreach ($tables as $t) {
            $byId[(int)$t['id']] = $t;
        }
        foreach ((new Table())->allCombinations($tenantId) as $c) {
            $a = (int)$c['table_a_id'];
            $b = (int)$c['table_b_id'];
            if (!isset($byId[$a], $byId[$b])) {
                continue; // uno dei due non è attivo
            }
            $cap = (int)$byId[$a]['capacity'] + (int)$byId[$b]['capacity'];
            if ($cap >= $partySize
                && $this->isFree($a, $start, $occ, $window)
                && $this->isFree($b, $start, $occ, $window)) {
                return [$a, $b];
            }
        }

        return [];
    }

    /**
     * Opzioni assegnabili per una prenotazione (per il menù di override).
     * Ogni voce: ['table_ids'=>[...], 'capacity'=>int, 'label'=>string].
     */
    public function availableOptions(int $tenantId, string $date, string $time, int $partySize, int $excludeReservationId = 0): array
    {
        $window = $this->windowMinutes($tenantId);
        $tables = (new Table())->findActiveByTenant($tenantId);
        if (empty($tables)) {
            return [];
        }
        $occ = $this->occupationMap($tenantId, $date, $excludeReservationId);
        $start = $this->timeToMinutes($time);

        $byId = [];
        foreach ($tables as $t) {
            $byId[(int)$t['id']] = $t;
        }

        $options = [];
        // singoli
        foreach ($tables as $t) {
            if ((int)$t['capacity'] >= $partySize
                && $this->isFree((int)$t['id'], $start, $occ, $window)) {
                $options[] = [
                    'table_ids' => [(int)$t['id']],
                    'capacity'  => (int)$t['capacity'],
                    'label'     => $t['name'] . ' — ' . (int)$t['capacity'] . ' posti',
                ];
            }
        }
        // combinazioni
        foreach ((new Table())->allCombinations($tenantId) as $c) {
            $a = (int)$c['table_a_id'];
            $b = (int)$c['table_b_id'];
            if (!isset($byId[$a], $byId[$b])) {
                continue;
            }
            $cap = (int)$byId[$a]['capacity'] + (int)$byId[$b]['capacity'];
            if ($cap >= $partySize
                && $this->isFree($a, $start, $occ, $window)
                && $this->isFree($b, $start, $occ, $window)) {
                $options[] = [
                    'table_ids' => [$a, $b],
                    'capacity'  => $cap,
                    'label'     => $byId[$a]['name'] . ' + ' . $byId[$b]['name'] . ' — combinazione, ' . $cap . ' posti',
                ];
            }
        }
        return $options;
    }

    /** Scrive l'assegnazione (rimpiazza quella esistente). */
    public function setAssignment(int $reservationId, array $tableIds, bool $isAuto): void
    {
        $this->db->beginTransaction();
        try {
            $del = $this->db->prepare('DELETE FROM reservation_tables WHERE reservation_id = :r');
            $del->execute(['r' => $reservationId]);

            if (!empty($tableIds)) {
                $ins = $this->db->prepare(
                    'INSERT INTO reservation_tables (reservation_id, table_id, is_auto)
                     VALUES (:r, :t, :a)'
                );
                foreach (array_unique(array_map('intval', $tableIds)) as $tid) {
                    if ($tid > 0) {
                        $ins->execute(['r' => $reservationId, 't' => $tid, 'a' => $isAuto ? 1 : 0]);
                    }
                }
            }
            $this->db->commit();
        } catch (\PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** Id dei tavoli attualmente assegnati a una prenotazione. */
    public function currentAssignment(int $reservationId): array
    {
        $stmt = $this->db->prepare(
            'SELECT table_id FROM reservation_tables WHERE reservation_id = :r ORDER BY table_id'
        );
        $stmt->execute(['r' => $reservationId]);
        return array_map('intval', array_column($stmt->fetchAll(), 'table_id'));
    }

    /**
     * Mappa reservationId => [['id'=>int,'name'=>string,'is_auto'=>int], ...]
     * per più prenotazioni in una query sola (evita N+1 nella lista).
     */
    public function assignmentsFor(array $reservationIds): array
    {
        $reservationIds = array_values(array_filter(array_map('intval', $reservationIds)));
        if (empty($reservationIds)) {
            return [];
        }
        $ph = implode(',', array_fill(0, count($reservationIds), '?'));
        $stmt = $this->db->prepare(
            "SELECT rt.reservation_id, rt.table_id, rt.is_auto, t.name
             FROM reservation_tables rt
             JOIN restaurant_tables t ON t.id = rt.table_id
             WHERE rt.reservation_id IN ($ph)
             ORDER BY rt.table_id"
        );
        $stmt->execute($reservationIds);
        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[(int)$row['reservation_id']][] = [
                'id'      => (int)$row['table_id'],
                'name'    => $row['name'],
                'is_auto' => (int)$row['is_auto'],
            ];
        }
        return $map;
    }

    // ─── interni ─────────────────────────────────────────────────

    /** Durata occupazione + buffer pulizia, in minuti. */
    private function windowMinutes(int $tenantId): int
    {
        $tenant = (new Tenant())->findById($tenantId);
        $duration = (int)($tenant['table_duration'] ?? 90);
        $buffer   = (int)($tenant['table_turnover_buffer'] ?? 15);
        return max(15, $duration) + max(0, $buffer);
    }

    /**
     * Orari di inizio (in minuti) delle prenotazioni che occupano ciascun
     * tavolo nella data indicata: tableId => [start1, start2, ...].
     */
    private function occupationMap(int $tenantId, string $date, int $excludeReservationId): array
    {
        $statuses = "'" . implode("','", self::BUSY_STATUSES) . "'";
        $sql = "SELECT rt.table_id, r.reservation_time
                FROM reservation_tables rt
                JOIN reservations r ON r.id = rt.reservation_id
                WHERE r.tenant_id = :t
                  AND r.reservation_date = :d
                  AND r.status IN ($statuses)
                  AND r.id <> :ex";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['t' => $tenantId, 'd' => $date, 'ex' => $excludeReservationId]);

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[(int)$row['table_id']][] = $this->timeToMinutes($row['reservation_time']);
        }
        return $map;
    }

    /** Un tavolo è libero al minuto $start se nessuna occupazione dista < $window. */
    private function isFree(int $tableId, int $start, array $occ, int $window): bool
    {
        foreach ($occ[$tableId] ?? [] as $busyStart) {
            if (abs($start - $busyStart) < $window) {
                return false;
            }
        }
        return true;
    }

    private function timeToMinutes(string $time): int
    {
        $p = explode(':', $time);
        return ((int)($p[0] ?? 0)) * 60 + ((int)($p[1] ?? 0));
    }
}
