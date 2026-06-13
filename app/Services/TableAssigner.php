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
 * Capacità elastica (min/max): un tavolo è "valido" per un party di N
 * persone se `min_capacity ≤ N ≤ capacity`. Default min = capacity per i
 * tavoli pre-esistenti → comportamento rigido di un tempo. Il ristoratore
 * allarga la forbice quando vuole.
 *
 * Auto-assegnazione: tra i tavoli liberi e validi sceglie il "fit più
 * stretto" (quello che spreca meno posti rispetto al party); a parità di
 * fit, vince il tavolo con range più stretto (`max - min` minore: un secco
 * `2-2p` batte un `1-2p` perché esprime un intent piu' netto del ristoratore);
 * a parità ancora, la priorità manuale (ordine in lista) e infine l'id più
 * basso (deterministico). Le combinazioni si attivano SOLO se nessun tavolo
 * singolo valido è libero — altrimenti spreca due tavoli per fare il lavoro
 * di uno. Mai bloccante.
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
     *
     * Algoritmo:
     *  1. Filtra i tavoli validi (min≤P≤max) e liberi nel turno.
     *  2. Sceglie il "fit più stretto" (capacity - P minore); tiebreaker
     *     su elasticity asc (range max-min: un secco vince un range che
     *     include P, perché il ristoratore l'ha configurato con intent
     *     dedicato), poi priority asc, poi id asc per determinismo.
     *  3. Se nessun singolo è disponibile, prova una combinazione di
     *     due tavoli (entrambi liberi e con max_a + max_b ≥ P).
     */
    public function pickTables(int $tenantId, string $date, string $time, int $partySize, int $excludeReservationId = 0): array
    {
        $timing = $this->tenantTiming($tenantId);
        $buffer = $timing['buffer'];
        $newDur = (new MealDurationResolver())->resolve($tenantId, $date, $time);
        // Auto-assegnazione: esclude tavoli "solo manuale" (Fase B) e "bloccati" (Fase E).
        // Solo i tavoli con is_bookable_online=1 AND is_blocked=0 sono candidati per
        // l'algoritmo automatico. Gli altri restano assegnabili manualmente dal
        // ristoratore via riassegnazione (vedi allTableOptions / availableOptions).
        $tables = array_values(array_filter(
            (new Table())->findActiveByTenant($tenantId),
            fn($t) => (int)($t['is_bookable_online'] ?? 1) === 1
                  && (int)($t['is_blocked'] ?? 0) === 0
        ));
        if (empty($tables)) {
            return [];
        }

        $occ = $this->occupationMap($tenantId, $date, $excludeReservationId, $timing['global']);
        $start = $this->timeToMinutes($time);

        // 1) singolo tavolo: tutti i candidati validi e liberi, poi ordina per fit.
        $candidates = [];
        foreach ($tables as $idx => $t) {
            $min = (int)($t['min_capacity'] ?? 1);
            $max = (int)$t['capacity'];
            if ($partySize < $min || $partySize > $max) continue;
            if (!$this->isFree((int)$t['id'], $start, $newDur, $occ, $buffer)) continue;
            $candidates[] = [
                'id'    => (int)$t['id'],
                'fit'   => $max - $partySize,   // spreco di posti (più basso = meglio)
                'elast' => $max - $min,          // range del tavolo (0 = secco, n>0 = elastico)
                'prio'  => $idx,                 // posizione in lista (già ordinata per priority)
            ];
        }
        if (!empty($candidates)) {
            usort($candidates, function ($a, $b) {
                return [$a['fit'], $a['elast'], $a['prio'], $a['id']]
                   <=> [$b['fit'], $b['elast'], $b['prio'], $b['id']];
            });
            return [$candidates[0]['id']];
        }

        // 2) combinazione di due tavoli — solo se nessun singolo era valido E
        // il party è troppo grande per stare in uno dei due tavoli della coppia.
        // Questa seconda condizione evita lo spreco: 2 tavoli per 1 persona
        // non ha senso. Coerente con availableOptions (regola wireframe).
        $byId = [];
        foreach ($tables as $t) {
            $byId[(int)$t['id']] = $t;
        }
        $comboCandidates = [];
        foreach ((new Table())->allCombinations($tenantId) as $c) {
            $a = (int)$c['table_a_id'];
            $b = (int)$c['table_b_id'];
            if (!isset($byId[$a], $byId[$b])) continue; // uno dei due non è attivo
            $maxA = (int)$byId[$a]['capacity'];
            $maxB = (int)$byId[$b]['capacity'];
            // Combo utile solo per party > max del singolo più grande della coppia.
            if ($partySize <= max($maxA, $maxB)) continue;
            $cap = $maxA + $maxB;
            if ($cap < $partySize) continue;
            if (!$this->isFree($a, $start, $newDur, $occ, $buffer)) continue;
            if (!$this->isFree($b, $start, $newDur, $occ, $buffer)) continue;
            $comboCandidates[] = [
                'ids' => [$a, $b],
                'fit' => $cap - $partySize,
            ];
        }
        if (!empty($comboCandidates)) {
            usort($comboCandidates, fn($x, $y) => [$x['fit'], $x['ids'][0]] <=> [$y['fit'], $y['ids'][0]]);
            return $comboCandidates[0]['ids'];
        }

        return [];
    }

    /**
     * Opzioni assegnabili per una prenotazione (per il menù di override).
     * Ogni voce: ['table_ids'=>[...], 'capacity'=>int, 'label'=>string].
     */
    public function availableOptions(int $tenantId, string $date, string $time, int $partySize, int $excludeReservationId = 0): array
    {
        $timing = $this->tenantTiming($tenantId);
        $buffer = $timing['buffer'];
        $newDur = (new MealDurationResolver())->resolve($tenantId, $date, $time);
        // Override manuale: l'operatore puo' scegliere tavoli "solo manuale"
        // (is_bookable_online=0) ma NON tavoli bloccati (is_blocked=1, fuori uso).
        $tables = array_values(array_filter(
            (new Table())->findActiveByTenant($tenantId),
            fn($t) => (int)($t['is_blocked'] ?? 0) === 0
        ));
        if (empty($tables)) {
            return [];
        }
        $occ = $this->occupationMap($tenantId, $date, $excludeReservationId, $timing['global']);
        $start = $this->timeToMinutes($time);

        $byId = [];
        foreach ($tables as $t) {
            $byId[(int)$t['id']] = $t;
        }

        $options = [];
        // singoli — validi solo se min ≤ P ≤ max
        foreach ($tables as $t) {
            $min = (int)($t['min_capacity'] ?? 1);
            $max = (int)$t['capacity'];
            if ($partySize < $min || $partySize > $max) continue;
            if (!$this->isFree((int)$t['id'], $start, $newDur, $occ, $buffer)) continue;
            $options[] = [
                'table_ids' => [(int)$t['id']],
                'capacity'  => $max,
                'label'     => $t['name'] . ' — ' . format_capacity($min, $max, false) . ' posti',
            ];
        }
        // combinazioni — solo se almeno uno dei due tavoli da solo non basterebbe.
        // (Se entrambi singoli potessero contenere P, evitiamo lo spreco).
        foreach ((new Table())->allCombinations($tenantId) as $c) {
            $a = (int)$c['table_a_id'];
            $b = (int)$c['table_b_id'];
            if (!isset($byId[$a], $byId[$b])) continue;
            $maxA = (int)$byId[$a]['capacity'];
            $maxB = (int)$byId[$b]['capacity'];
            // Combo utile solo per party > max del singolo più grande.
            if ($partySize <= max($maxA, $maxB)) continue;
            $cap = $maxA + $maxB;
            if ($cap < $partySize) continue;
            if (!$this->isFree($a, $start, $newDur, $occ, $buffer)) continue;
            if (!$this->isFree($b, $start, $newDur, $occ, $buffer)) continue;
            $options[] = [
                'table_ids' => [$a, $b],
                'capacity'  => $cap,
                'label'     => $byId[$a]['name'] . ' + ' . $byId[$b]['name'] . ' — combinazione, ' . $cap . ' posti',
            ];
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

    /**
     * Stato della sala a una data/ora: tableId => dati della prenotazione che
     * occupa il tavolo in quel momento (i tavoli non presenti sono liberi).
     * "Occupato" = l'ora cade in [inizio, inizio + table_duration] (no buffer).
     */
    public function floorState(int $tenantId, string $date, string $time): array
    {
        $tenant = (new Tenant())->findById($tenantId);
        $duration = max(15, (int)($tenant['table_duration'] ?? 90));
        $t = $this->timeToMinutes($time);

        $statuses = "'" . implode("','", self::BUSY_STATUSES) . "'";
        $stmt = $this->db->prepare(
            "SELECT rt.table_id, r.id AS reservation_id, r.reservation_time,
                    r.party_size, r.status, c.first_name, c.last_name
             FROM reservation_tables rt
             JOIN reservations r ON r.id = rt.reservation_id
             JOIN customers c ON c.id = r.customer_id
             WHERE r.tenant_id = :t AND r.reservation_date = :d AND r.status IN ($statuses)"
        );
        $stmt->execute(['t' => $tenantId, 'd' => $date]);

        $state = [];
        foreach ($stmt->fetchAll() as $row) {
            $start = $this->timeToMinutes($row['reservation_time']);
            if ($t >= $start && $t < $start + $duration) {
                // Cognome in MAIUSCOLO, nome in minuscolo (stile TheFork)
                $surname = mb_strtoupper(trim((string)$row['last_name']));
                $given   = mb_strtolower(trim((string)$row['first_name']));
                $state[(int)$row['table_id']] = [
                    'reservation_id' => (int)$row['reservation_id'],
                    'name'           => trim($surname . ' ' . $given),
                    'party'          => (int)$row['party_size'],
                    'time'           => substr((string)$row['reservation_time'], 0, 5),
                    'status'         => $row['status'],
                ];
            }
        }
        return $state;
    }

    /**
     * Tutte le opzioni assegnabili (tavoli attivi + combinazioni), senza
     * filtro di disponibilità — per la riassegnazione manuale dalla mappa
     * e dalla scheda prenotazione. L'operatore può scegliere anche tavoli
     * "non perfetti": qui non si filtra per min/max, si mostra il range.
     */
    public function allTableOptions(int $tenantId): array
    {
        // Riassegnazione manuale: esclude tavoli bloccati (fuori uso) ma include
        // i "solo manuale" (l'operatore puo' assegnarli manualmente).
        $tables = array_values(array_filter(
            (new Table())->findActiveByTenant($tenantId),
            fn($t) => (int)($t['is_blocked'] ?? 0) === 0
        ));
        $byId = [];
        foreach ($tables as $t) {
            $byId[(int)$t['id']] = $t;
        }
        $options = [];
        foreach ($tables as $t) {
            $min = (int)($t['min_capacity'] ?? 1);
            $max = (int)$t['capacity'];
            $options[] = [
                'value' => (string)(int)$t['id'],
                'label' => $t['name'] . ' — ' . format_capacity($min, $max, false) . ' posti',
            ];
        }
        foreach ((new Table())->allCombinations($tenantId) as $c) {
            $a = (int)$c['table_a_id'];
            $b = (int)$c['table_b_id'];
            if (!isset($byId[$a], $byId[$b])) continue;
            $maxA = (int)$byId[$a]['capacity'];
            $maxB = (int)$byId[$b]['capacity'];
            // La combo ha senso solo sopra la soglia del singolo più grande:
            // sotto, conviene il singolo. Mostriamo comunque il range completo.
            $comboMin = max($maxA, $maxB) + 1;
            $comboMax = $maxA + $maxB;
            $options[] = [
                'value' => $a . ',' . $b,
                'label' => $byId[$a]['name'] . ' + ' . $byId[$b]['name']
                    . ' — combinazione, ' . format_seats_range($comboMin, $comboMax),
            ];
        }
        return $options;
    }

    // ─── interni ─────────────────────────────────────────────────

    /**
     * Durata globale (fallback) + buffer turnover del tenant, in minuti.
     * @return array{global:int, buffer:int}
     */
    private function tenantTiming(int $tenantId): array
    {
        $tenant = (new Tenant())->findById($tenantId);
        return [
            'global' => max(15, (int)($tenant['table_duration'] ?? 90)),
            'buffer' => max(0, (int)($tenant['table_turnover_buffer'] ?? 15)),
        ];
    }

    /**
     * Occupazioni per tavolo nella data: tableId => [['start'=>min, 'dur'=>min], ...].
     * La durata e' lo snapshot della prenotazione (duration_minutes); per le
     * righe storiche senza snapshot si usa $globalDur come fallback. Cosi'
     * aperitivo 45' e cena 120' occupano il tavolo per la finestra giusta.
     */
    private function occupationMap(int $tenantId, string $date, int $excludeReservationId, int $globalDur): array
    {
        $statuses = "'" . implode("','", self::BUSY_STATUSES) . "'";
        $sql = "SELECT rt.table_id, r.reservation_time, r.duration_minutes
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
            $map[(int)$row['table_id']][] = [
                'start' => $this->timeToMinutes($row['reservation_time']),
                'dur'   => $row['duration_minutes'] !== null ? (int)$row['duration_minutes'] : $globalDur,
            ];
        }
        return $map;
    }

    /**
     * Un tavolo e' libero per una nuova prenotazione [start, start+newDur]
     * se nessuna occupazione esistente si sovrappone, considerando il buffer
     * di pulizia tra due turni. Due intervalli [a,b] e [c,d] confliggono se
     * a < d+buffer AND c < b+buffer.
     */
    private function isFree(int $tableId, int $start, int $newDur, array $occ, int $buffer): bool
    {
        $newEnd = $start + $newDur;
        foreach ($occ[$tableId] ?? [] as $o) {
            $occEnd = $o['start'] + $o['dur'];
            if ($start < $occEnd + $buffer && $o['start'] < $newEnd + $buffer) {
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
