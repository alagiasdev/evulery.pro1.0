<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Cache;
use App\Core\Database;
use App\Core\Request;
use App\Core\TenantResolver;
use App\Models\Customer;
use App\Models\Reservation;
use App\Services\HeartbeatService;

class HomeController
{
    public function index(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $date = $request->query('date', date('Y-m-d'));
        $db = Database::getInstance();

        $reservationModel = new Reservation();
        $stats = $this->getStatsForDate($tenantId, $date);
        $reservations = $reservationModel->findByTenantAndDate($tenantId, $date);

        // --- Trend: same day last week ---
        $lastWeekDate = date('Y-m-d', strtotime($date . ' -7 days'));
        $lastWeekStats = $this->getStatsForDate($tenantId, $lastWeekDate);

        // --- Prossimi in arrivo (today only, future times) ---
        $nextArrivals = [];
        if ($date === date('Y-m-d')) {
            $stmt = $db->prepare(
                'SELECT r.id, r.reservation_time, r.party_size, r.status,
                        c.first_name, c.last_name, c.phone
                 FROM reservations r
                 JOIN customers c ON r.customer_id = c.id
                 WHERE r.tenant_id = :tenant_id
                 AND r.reservation_date = CURDATE()
                 AND r.status IN ("confirmed", "pending")
                 ORDER BY r.reservation_time ASC
                 LIMIT 8'
            );
            $stmt->execute(['tenant_id' => $tenantId]);
            $nextArrivals = $stmt->fetchAll();
        }

        // --- Capacity: pranzo/cena from time_slots + slot_overrides ---
        $mealCapacity = $this->getMealCapacity($db, $tenantId, $date);

        // --- Upcoming reservations (next 7 days) ---
        $stmt = $db->prepare(
            'SELECT reservation_date, COUNT(*) as count, SUM(party_size) as covers
             FROM reservations
             WHERE tenant_id = :tenant_id
             AND reservation_date >= CURDATE()
             AND reservation_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
             AND status IN ("confirmed", "pending")
             GROUP BY reservation_date
             ORDER BY reservation_date ASC'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        $upcoming = $stmt->fetchAll();

        // --- No-show rate (last 30 days) --- cached 15 min (stable aggregate)
        $noshow = Cache::remember(
            "home_noshow_t{$tenantId}",
            900,
            fn() => $this->getNoshowRate($db, $tenantId)
        );

        // --- Source breakdown (last 30 days) --- cached 15 min (stable aggregate)
        $sources = Cache::remember(
            "home_sources_t{$tenantId}",
            900,
            fn() => $this->getSourceBreakdown($db, $tenantId)
        );

        // --- Compleanni nei prossimi 30 giorni (sidebar) ---
        $birthdays = (new Customer())->findUpcomingBirthdays($tenantId, 30, 10);

        // --- User & tenant info for greeting ---
        $user = Auth::user();
        $tenant = TenantResolver::current();
        $userName = explode(' ', $user['name'] ?? '')[0]; // First name only

        // Fase C — heartbeat per auto-refresh dashboard home. La home mostra
        // sempre le prenotazioni del $date selezionato, quindi riusiamo lo
        // stesso endpoint /heartbeat/reservations: l'unico dataset live e' la
        // lista del giorno. KPI cards/no-show/sources sono aggregati cachati,
        // si aggiornano al refresh manuale dell'utente.
        $hb = HeartbeatService::forReservations($tenantId, $date);
        $heartbeat = [
            'hash'  => $hb['hash'],
            'count' => $hb['count'],
            'url'   => url('dashboard/heartbeat/reservations') . '?date=' . urlencode($date),
        ];

        view('dashboard/home', [
            'title'         => 'Dashboard',
            'activeMenu'    => 'home',
            'stats'         => $stats,
            'lastWeekStats' => $lastWeekStats,
            'lastWeekDate'  => $lastWeekDate,
            'reservations'  => $reservations,
            'nextArrivals'  => $nextArrivals,
            'mealCapacity'  => $mealCapacity,
            'date'          => $date,
            'upcoming'      => $upcoming,
            'noshow'        => $noshow,
            'sources'       => $sources,
            'birthdays'     => $birthdays,
            'userName'      => $userName,
            'tenantName'    => $tenant['name'] ?? '',
            'heartbeat'     => $heartbeat,
        ], 'dashboard');
    }

    private function getStatsForDate(int $tenantId, string $date): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT COUNT(*) as total,
                    COALESCE(SUM(CASE WHEN status NOT IN ("cancelled","noshow") THEN party_size ELSE 0 END), 0) as covers,
                    SUM(CASE WHEN status = "confirmed" THEN 1 ELSE 0 END) as confirmed,
                    SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = "arrived" THEN 1 ELSE 0 END) as arrived,
                    SUM(CASE WHEN status = "noshow" THEN 1 ELSE 0 END) as noshow,
                    SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled
             FROM reservations
             WHERE tenant_id = :tenant_id AND reservation_date = :date'
        );
        $stmt->execute(['tenant_id' => $tenantId, 'date' => $date]);
        return $stmt->fetch();
    }

    /**
     * Capienza e prenotato per ogni categoria pasto ATTIVA del tenant.
     *
     * - capacity = cap_istantaneo × turni
     *   dove cap_istantaneo = max(max_covers degli slot della categoria)
     *   e turni = floor(durata_categoria / (table_duration + buffer)).
     *
     * - booked = PICCO di occupazione fisica in sala durante il servizio.
     *   Per ogni slot della categoria si calcola quante persone sono sedute
     *   in quel momento (somma party_size delle prenotazioni con
     *   [reservation_time, reservation_time + table_duration] che copre lo
     *   slot). Il "booked" della categoria è il max fra questi valori.
     *   Stesso modello che il widget di prenotazione usa per decidere se
     *   uno slot accetta nuovi clienti.
     *   IMPORTANTE: una prenotazione iniziata in Aperitivo che dura 90 min
     *   e spilla in Cena conta nell'occupazione di entrambe (è fisicamente
     *   presente in entrambe le fasce, anche se "appartiene" solo ad
     *   Aperitivo come count).
     *
     * - count = numero di prenotazioni iniziate IN questa categoria.
     *   Diverso dal booked: la spillover non incrementa il count, solo il booked.
     *
     * @return array{categories: array<int, array>, orphanSlots: int}
     */
    private function getMealCapacity(\PDO $db, int $tenantId, string $date): array
    {
        // 1) Categorie attive del tenant
        $stmt = $db->prepare(
            'SELECT id, name, display_name, start_time, end_time
             FROM meal_categories
             WHERE tenant_id = :t AND is_active = 1
             ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute(['t' => $tenantId]);
        $cats = $stmt->fetchAll();
        if (empty($cats)) {
            return ['categories' => [], 'orphanSlots' => 0];
        }

        // Tenant: durata pasto + buffer turnover per il calcolo dei turni
        $stmt = $db->prepare(
            'SELECT table_duration, table_turnover_buffer FROM tenants WHERE id = :t'
        );
        $stmt->execute(['t' => $tenantId]);
        $tCfg = $stmt->fetch() ?: [];
        $turnDuration = max(30, (int)($tCfg['table_duration'] ?? 90))
                      + max(0,  (int)($tCfg['table_turnover_buffer'] ?? 15));

        // 2) Slot del giorno + overrides
        $dow = (int)date('N', strtotime($date)) - 1;
        $stmt = $db->prepare(
            'SELECT slot_time, max_covers
             FROM time_slots
             WHERE tenant_id = :t AND day_of_week = :dow AND is_active = 1'
        );
        $stmt->execute(['t' => $tenantId, 'dow' => $dow]);
        $slots = $stmt->fetchAll();

        $stmt = $db->prepare(
            'SELECT slot_time, max_covers, is_closed
             FROM slot_overrides
             WHERE tenant_id = :t AND override_date = :d'
        );
        $stmt->execute(['t' => $tenantId, 'd' => $date]);
        $overrides = [];
        $dayClosed = false;
        foreach ($stmt->fetchAll() as $ov) {
            if ($ov['slot_time'] === null) {
                if ($ov['is_closed']) $dayClosed = true;
            } else {
                $overrides[$ov['slot_time']] = $ov;
            }
        }

        // Inizializza accumulatori per categoria
        $byKey = [];
        foreach ($cats as $c) {
            $byKey[$c['name']] = [
                'name'         => $c['name'],
                'display_name' => $c['display_name'],
                'start_time'   => substr((string)$c['start_time'], 0, 5),
                'end_time'     => substr((string)$c['end_time'], 0, 5),
                'capacity'     => 0,
                'instant_cap'  => 0, // max max_covers fra gli slot della categoria
                'turns'        => 0, // turni stimati nella durata della categoria
                'booked'       => 0,
                'count'        => 0,
            ];
        }

        if ($dayClosed) {
            return ['categories' => array_values($byKey), 'orphanSlots' => 0];
        }

        // 3) Mappa ogni slot sulla prima categoria attiva che lo copre.
        //    Per ogni categoria teniamo il MAX dei max_covers degli slot.
        $orphanSlots = 0;
        foreach ($slots as $slot) {
            $time = $slot['slot_time'];
            if (isset($overrides[$time])) {
                if ((int)$overrides[$time]['is_closed']) continue;
                $maxCovers = (int)$overrides[$time]['max_covers'];
            } else {
                $maxCovers = (int)$slot['max_covers'];
            }

            $catKey = $this->categoryForTime($cats, $time);
            if ($catKey === null) {
                // Conto come orfano solo gli slot "vivi" (max_covers > 0):
                // se l'operatore li ha gia' azzerati, non sono un problema.
                if ($maxCovers > 0) $orphanSlots++;
                continue;
            }
            if ($maxCovers > $byKey[$catKey]['instant_cap']) {
                $byKey[$catKey]['instant_cap'] = $maxCovers;
            }
        }

        // 4) Calcolo turni × cap istantaneo per ogni categoria
        foreach ($byKey as $key => &$row) {
            if ($row['instant_cap'] === 0) continue; // nessuno slot configurato
            $catMinutes = $this->timeDiffMinutes($row['start_time'], $row['end_time']);
            $turns = max(1, (int)floor($catMinutes / max(1, $turnDuration)));
            $row['turns'] = $turns;
            $row['capacity'] = $row['instant_cap'] * $turns;
        }
        unset($row);

        // 5) Carica tutte le prenotazioni del giorno (status che contano) UNA volta.
        //    Le useremo sia per il "count" (dove inizia) sia per il "booked"
        //    (occupazione fisica = overlap con gli slot della categoria).
        $tableDuration = max(15, (int)($tCfg['table_duration'] ?? 90));
        $stmt = $db->prepare(
            'SELECT reservation_time, party_size
             FROM reservations
             WHERE tenant_id = :t AND reservation_date = :d
             AND status IN ("confirmed", "pending", "arrived")'
        );
        $stmt->execute(['t' => $tenantId, 'd' => $date]);
        $bookings = [];
        foreach ($stmt->fetchAll() as $r) {
            $startMin = $this->timeToMinutes((string)$r['reservation_time']);
            $bookings[] = [
                'start'     => $startMin,
                'end'       => $startMin + $tableDuration,
                'size'      => (int)$r['party_size'],
                'startTime' => substr((string)$r['reservation_time'], 0, 5),
            ];
        }

        // 6) count = prenotazioni che INIZIANO nella categoria
        foreach ($bookings as $b) {
            $catKey = $this->categoryForTime($cats, $b['startTime']);
            if ($catKey === null) continue;
            $byKey[$catKey]['count'] += 1;
        }

        // 7) booked = PICCO di occupazione fisica negli slot della categoria
        //    (= include lo spillover dalla categoria precedente).
        foreach ($byKey as $key => &$row) {
            if ($row['instant_cap'] === 0) continue; // niente slot, niente da calcolare
            $maxOccupied = 0;
            foreach ($slots as $slot) {
                $slotTime = substr((string)$slot['slot_time'], 0, 5);
                if ($this->categoryForTime($cats, $slotTime) !== $key) continue;
                $slotMin = $this->timeToMinutes((string)$slot['slot_time']);
                $occupied = 0;
                foreach ($bookings as $b) {
                    // Una prenotazione è "presente" allo slotMin se
                    // start <= slotMin < end. Stesso criterio di
                    // Reservation::getOccupiedCovers.
                    if ($b['start'] <= $slotMin && $slotMin < $b['end']) {
                        $occupied += $b['size'];
                    }
                }
                if ($occupied > $maxOccupied) $maxOccupied = $occupied;
            }
            $row['booked'] = $maxOccupied;
        }
        unset($row);

        return ['categories' => array_values($byKey), 'orphanSlots' => $orphanSlots];
    }

    /** Differenza in minuti tra due orari "HH:MM" (assume stesso giorno). */
    private function timeDiffMinutes(string $start, string $end): int
    {
        [$sh, $sm] = array_map('intval', explode(':', $start) + [0, 0]);
        [$eh, $em] = array_map('intval', explode(':', $end)   + [0, 0]);
        return max(0, ($eh * 60 + $em) - ($sh * 60 + $sm));
    }

    /** Converte "HH:MM" o "HH:MM:SS" in minuti dalla mezzanotte. */
    private function timeToMinutes(string $time): int
    {
        $parts = explode(':', $time);
        return ((int)($parts[0] ?? 0)) * 60 + ((int)($parts[1] ?? 0));
    }

    /**
     * Trova la prima categoria (in ordine di sort) il cui range
     * [start_time, end_time) copre l'orario indicato. NULL se nessuna.
     */
    private function categoryForTime(array $cats, string $time): ?string
    {
        $t = substr($time, 0, 5); // "HH:MM"
        foreach ($cats as $c) {
            $start = substr((string)$c['start_time'], 0, 5);
            $end   = substr((string)$c['end_time'], 0, 5);
            if ($t >= $start && $t < $end) {
                return (string)$c['name'];
            }
        }
        return null;
    }

    private function getNoshowRate(\PDO $db, int $tenantId): array
    {
        $stmt = $db->prepare(
            'SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = "noshow" THEN 1 ELSE 0 END) as noshow_count
             FROM reservations
             WHERE tenant_id = :tenant_id
             AND reservation_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             AND reservation_date <= CURDATE()
             AND status NOT IN ("cancelled")'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        $row = $stmt->fetch();

        $total = (int)($row['total'] ?? 0);
        $noshowCount = (int)($row['noshow_count'] ?? 0);
        $rate = $total > 0 ? round(($noshowCount / $total) * 100, 1) : 0;

        return [
            'total'   => $total,
            'noshow'  => $noshowCount,
            'rate'    => $rate,
        ];
    }

    private function getSourceBreakdown(\PDO $db, int $tenantId): array
    {
        $stmt = $db->prepare(
            'SELECT source, COUNT(*) as count
             FROM reservations
             WHERE tenant_id = :tenant_id
             AND reservation_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             AND reservation_date <= CURDATE()
             AND status NOT IN ("cancelled")
             GROUP BY source
             ORDER BY count DESC'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        $rows = $stmt->fetchAll();

        $total = array_sum(array_column($rows, 'count'));
        $sources = [];
        foreach ($rows as $row) {
            $sources[] = [
                'source' => $row['source'],
                'count'  => (int)$row['count'],
                'pct'    => $total > 0 ? round(($row['count'] / $total) * 100) : 0,
            ];
        }

        return ['items' => $sources, 'total' => $total];
    }
}