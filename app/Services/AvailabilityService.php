<?php

namespace App\Services;

use App\Core\Database;
use App\Models\TimeSlot;
use App\Models\Reservation;
use App\Models\MealCategory;
use App\Models\Promotion;
use App\Models\SlotOverride;
use PDO;

class AvailabilityService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAvailableSlots(int $tenantId, string $date, int $partySize, string $source = 'widget', int $excludeReservationId = 0): array
    {
        // Get tenant config
        $stmt = $this->db->prepare('SELECT table_duration, time_step, promo_widget_only FROM tenants WHERE id = :id');
        $stmt->execute(['id' => $tenantId]);
        $tenant = $stmt->fetch();

        if (!$tenant) {
            return [];
        }

        $tableDuration = (int)$tenant['table_duration'];
        $promoWidgetOnly = !empty($tenant['promo_widget_only']);

        // Get day of week (0=Mon, 6=Sun)
        $dayOfWeek = (int)date('N', strtotime($date)) - 1; // PHP date('N'): 1=Mon, 7=Sun

        // Get base slots for this day
        $slots = (new TimeSlot())->findByTenantAndDay($tenantId, $dayOfWeek);

        // Check for date-specific overrides
        $stmt = $this->db->prepare(
            'SELECT * FROM slot_overrides WHERE tenant_id = :tenant_id AND override_date = :date'
        );
        $stmt->execute(['tenant_id' => $tenantId, 'date' => $date]);
        $overrides = $stmt->fetchAll();

        // Check if entire day is closed
        foreach ($overrides as $override) {
            if ($override['slot_time'] === null && $override['is_closed']) {
                return []; // Entire day closed
            }
        }

        $reservationModel = new Reservation();
        $promotionModel = new Promotion();
        $canUsePromos = (new \App\Models\Tenant())->canUseService($tenantId, 'promotions');
        $durationResolver = new \App\Services\MealDurationResolver();
        $result = [];

        foreach ($slots as $slot) {
            $slotTime = substr($slot['slot_time'], 0, 5);
            $maxCovers = (int)$slot['max_covers'];

            // Apply time-specific overrides
            foreach ($overrides as $override) {
                if ($override['slot_time'] !== null && substr($override['slot_time'], 0, 5) === $slotTime) {
                    if ($override['is_closed']) {
                        continue 2; // Skip this slot
                    }
                    if ($override['max_covers'] !== null) {
                        // "Al completo" = max_covers 0 (is_closed 0): blocca il
                        // canale ONLINE (widget) ma NON il ristoratore, che dalla
                        // dashboard continua ad aggiungere a mano (locale aperto,
                        // solo pieno online). Per source='dashboard' si ignora il
                        // blocco e si usa la capienza reale dello slot.
                        if ((int)$override['max_covers'] === 0 && $source === 'dashboard') {
                            continue; // owner: bypassa il blocco al-completo
                        }
                        $maxCovers = (int)$override['max_covers'];
                    }
                }
            }

            // Skip slots with no covers (disabled by admin or override)
            if ($maxCovers <= 0) {
                continue;
            }

            // Calculate occupied covers considering overlapping reservations
            $occupied = $reservationModel->getOccupiedCovers($tenantId, $date, $slotTime, $tableDuration, $excludeReservationId);
            $available = $maxCovers - $occupied;

            // Flag past slots when date is today
            $isPast = ($date === date('Y-m-d') && $slotTime < date('H:i'));

            // Lookup applicable promotion for this slot (only if plan includes promotions)
            $promo = $canUsePromos
                ? $promotionModel->findApplicable($tenantId, $date, $slotTime)
                : null;

            // Durata occupazione per questo slot (fascia+giorno) -> il widget
            // mostra "tavolo riservato fino alle HH:MM" prima della conferma.
            $slotDuration = $durationResolver->resolve($tenantId, $date, $slotTime);

            $result[] = [
                'time'             => $slotTime,
                'max_covers'       => $maxCovers,
                'occupied_covers'  => $occupied,
                'available_covers' => max(0, $available),
                'is_available'     => $available >= $partySize,
                'is_past'          => $isPast,
                'duration'         => $slotDuration,
                'end_time'         => date('H:i', strtotime($slotTime) + $slotDuration * 60),
                'discount_percent' => ($promo && !($promoWidgetOnly && $source !== 'widget'))
                    ? (int)$promo['discount_percent'] : 0,
            ];
        }

        return $result;
    }

    public function getSuggestions(int $tenantId, string $date, int $partySize, string $requestedTime): array
    {
        $allSlots = $this->getAvailableSlots($tenantId, $date, $partySize);
        $suggestions = [];

        foreach ($allSlots as $slot) {
            if ($slot['is_available'] && $slot['time'] !== $requestedTime) {
                $suggestions[] = $slot;
            }
        }

        // Sort by proximity to requested time
        usort($suggestions, function ($a, $b) use ($requestedTime) {
            $diffA = abs(strtotime($a['time']) - strtotime($requestedTime));
            $diffB = abs(strtotime($b['time']) - strtotime($requestedTime));
            return $diffA - $diffB;
        });

        return array_slice($suggestions, 0, 3);
    }

    /**
     * Primo slot prenotabile a partire da $fromDate (incluso), scansionando in
     * avanti giorno per giorno. Rispetta: finestra di prenotazione del tenant
     * (booking_advance_max), chiusure, blocchi "al completo", orari passati.
     * Usato per il messaggio "prossima disponibilità" nel widget quando la data
     * richiesta è sold-out. Ritorna ['date'=>'Y-m-d','time'=>'HH:MM'] o null.
     */
    public function findNextAvailability(int $tenantId, string $fromDate, int $partySize, int $maxScan = 60): ?array
    {
        $stmt = $this->db->prepare('SELECT booking_advance_max FROM tenants WHERE id = :id');
        $stmt->execute(['id' => $tenantId]);
        $col = $stmt->fetchColumn();
        $advanceMax = ($col === false) ? 60 : (int)$col;

        $today   = date('Y-m-d');
        $start   = $fromDate < $today ? $today : $fromDate;       // non partire nel passato
        $hardEnd = date('Y-m-d', strtotime($today . ' +' . max(0, $advanceMax) . ' days'));
        $party   = max(1, $partySize);

        $cursor = new \DateTime($start);
        for ($i = 0; $i < $maxScan; $i++) {
            $d = $cursor->format('Y-m-d');
            if ($d > $hardEnd) {
                break; // fuori dalla finestra di prenotazione
            }
            foreach ($this->getAvailableSlots($tenantId, $d, $party, 'widget') as $slot) {
                if (!empty($slot['is_available']) && empty($slot['is_past'])) {
                    return ['date' => $d, 'time' => $slot['time']];
                }
            }
            $cursor->modify('+1 day');
        }
        return null;
    }

    public function canBook(int $tenantId, string $date, string $time, int $partySize, int $excludeReservationId = 0, string $source = 'widget'): bool
    {
        $slots = $this->getAvailableSlots($tenantId, $date, $partySize, $source, $excludeReservationId);

        foreach ($slots as $slot) {
            if ($slot['time'] === $time && $slot['is_available']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Atomic check + book: locks time_slots for tenant+day, re-checks availability, creates reservation.
     * Returns reservation ID on success, null if slot not available.
     */
    public function atomicBook(int $tenantId, string $date, string $time, int $partySize, array $reservationData, string $source = 'widget'): ?int
    {
        $dayOfWeek = (int)date('N', strtotime($date)) - 1;

        $this->db->beginTransaction();
        try {
            // Lock all slots for this tenant+day to serialize concurrent booking attempts
            $stmt = $this->db->prepare(
                'SELECT id FROM time_slots WHERE tenant_id = :tid AND day_of_week = :dow FOR UPDATE'
            );
            $stmt->execute(['tid' => $tenantId, 'dow' => $dayOfWeek]);

            // Re-check availability within the transaction (consistent read after lock)
            if (!$this->canBook($tenantId, $date, $time, $partySize, 0, $source)) {
                $this->db->rollBack();
                return null;
            }

            // Create reservation atomically
            $reservationId = (new Reservation())->create($reservationData);

            $this->db->commit();
            return $reservationId;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getDailySummary(int $tenantId, string $date): array
    {
        return (new Reservation())->countTodayByTenant($tenantId);
    }

    public function getGroupedSlots(int $tenantId, string $date, int $partySize, string $source = 'widget'): array
    {
        $flatSlots = $this->getAvailableSlots($tenantId, $date, $partySize, $source);
        $categoryModel = new MealCategory();
        $categories = $categoryModel->findActiveByTenant($tenantId);

        if (empty($categories)) {
            return [
                [
                    'category'     => 'all',
                    'display_name' => 'Orari disponibili',
                    'slots'        => $flatSlots,
                ]
            ];
        }

        $grouped = [];
        foreach ($categories as $cat) {
            $grouped[$cat['name']] = [
                'category'     => $cat['name'],
                'display_name' => $cat['display_name'],
                'slots'        => [],
            ];
        }

        $orphans = [];
        foreach ($flatSlots as $slot) {
            $match = $categoryModel->categorizeTime($categories, $slot['time']);
            if ($match) {
                $grouped[$match['name']]['slots'][] = $slot;
            } else {
                $orphans[] = $slot;
            }
        }

        // Fallback group for slots that don't match any meal category
        if (!empty($orphans)) {
            $grouped['altro'] = [
                'category'     => 'altro',
                'display_name' => 'Altro',
                'slots'        => $orphans,
            ];
        }

        return array_values(array_filter($grouped, fn($g) => !empty($g['slots'])));
    }

    /**
     * Stato "Al completo" per una data: quali servizi (fasce) esistono quel
     * giorno, quali sono attualmente al completo, e gli orari coinvolti — per il
     * bottone/popover e il banner nella pagina Prenotazioni.
     *
     * Ritorna:
     *   has_slots        bool   il giorno ha slot attivi (altrimenti niente UI)
     *   services         array  [{name, display_name, times[], total, is_full}]
     *   whole_day_times  array  tutti gli orari attivi del giorno (HH:MM:SS)
     *   whole_day_full   bool   ogni slot attivo del giorno e' al completo
     *   any_full         bool   almeno uno slot al completo
     *   full_labels      array  display_name dei servizi interamente al completo
     */
    public function getDayFullState(int $tenantId, string $date): array
    {
        $dayOfWeek  = (int)date('N', strtotime($date)) - 1;
        $slots      = (new TimeSlot())->findByTenantAndDay($tenantId, $dayOfWeek);
        $catModel   = new MealCategory();
        $categories = $catModel->findActiveByTenant($tenantId);

        $fullFlip = array_flip(array_map(
            fn($t) => substr($t, 0, 5),
            (new SlotOverride())->fullSlotTimes($tenantId, $date)
        ));

        // Prepara i contenitori per ogni categoria attiva
        $services = [];
        foreach ($categories as $cat) {
            $services[$cat['name']] = [
                'name'         => $cat['name'],
                'display_name' => $cat['display_name'],
                'times'        => [],
                'full'         => 0,
            ];
        }
        $orphanTimes = [];
        $orphanFull  = 0;

        foreach ($slots as $s) {
            $t5   = substr($s['slot_time'], 0, 5);
            $full = isset($fullFlip[$t5]);
            $match = !empty($categories) ? $catModel->categorizeTime($categories, $t5) : null;
            if ($match && isset($services[$match['name']])) {
                $services[$match['name']]['times'][] = $s['slot_time'];
                if ($full) {
                    $services[$match['name']]['full']++;
                }
            } else {
                $orphanTimes[] = $s['slot_time'];
                if ($full) {
                    $orphanFull++;
                }
            }
        }

        // Tieni solo i servizi con slot quel giorno; calcola is_full
        $services = array_values(array_filter($services, fn($sv) => !empty($sv['times'])));
        foreach ($services as &$sv) {
            $sv['total']   = count($sv['times']);
            $sv['is_full'] = $sv['total'] > 0 && $sv['full'] >= $sv['total'];
            unset($sv['full']);
        }
        unset($sv);

        // Slot che non ricadono in nessuna fascia (raro): gruppo "Altro"
        if (!empty($orphanTimes)) {
            $services[] = [
                'name'         => 'altro',
                'display_name' => 'Altro',
                'times'        => $orphanTimes,
                'total'        => count($orphanTimes),
                'is_full'      => $orphanFull >= count($orphanTimes),
            ];
        }

        $allTimes  = array_map(fn($s) => $s['slot_time'], $slots);
        $fullCount = 0;
        foreach ($allTimes as $t) {
            if (isset($fullFlip[substr($t, 0, 5)])) {
                $fullCount++;
            }
        }

        return [
            'has_slots'       => count($allTimes) > 0,
            'services'        => $services,
            'whole_day_times' => $allTimes,
            'whole_day_full'  => count($allTimes) > 0 && $fullCount >= count($allTimes),
            'any_full'        => $fullCount > 0,
            'full_labels'     => array_values(array_map(
                fn($sv) => $sv['display_name'],
                array_filter($services, fn($sv) => $sv['is_full'])
            )),
        ];
    }

    public function getTodayBookingCount(int $tenantId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as cnt FROM reservations
             WHERE tenant_id = :tenant_id
             AND reservation_date = CURDATE()
             AND status IN ("confirmed", "pending")'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        return (int)$stmt->fetch()['cnt'];
    }
}
