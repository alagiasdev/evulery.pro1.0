<?php

namespace App\Services;

use App\Core\Database;
use App\Models\TimeSlot;
use App\Models\Reservation;
use App\Models\MealCategory;
use App\Models\Promotion;
use PDO;

class AvailabilityService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAvailableSlots(int $tenantId, string $date, int $partySize, string $source = 'widget'): array
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
                        $maxCovers = (int)$override['max_covers'];
                    }
                }
            }

            // Skip slots with no covers (disabled by admin or override)
            if ($maxCovers <= 0) {
                continue;
            }

            // Calculate occupied covers considering overlapping reservations
            $occupied = $reservationModel->getOccupiedCovers($tenantId, $date, $slotTime, $tableDuration);
            $available = $maxCovers - $occupied;

            // Flag past slots when date is today
            $isPast = ($date === date('Y-m-d') && $slotTime < date('H:i'));

            // Lookup applicable promotion for this slot (only if plan includes promotions)
            $promo = $canUsePromos
                ? $promotionModel->findApplicable($tenantId, $date, $slotTime)
                : null;

            $result[] = [
                'time'             => $slotTime,
                'max_covers'       => $maxCovers,
                'occupied_covers'  => $occupied,
                'available_covers' => max(0, $available),
                'is_available'     => $available >= $partySize,
                'is_past'          => $isPast,
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

    public function canBook(int $tenantId, string $date, string $time, int $partySize): bool
    {
        $slots = $this->getAvailableSlots($tenantId, $date, $partySize);

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
    public function atomicBook(int $tenantId, string $date, string $time, int $partySize, array $reservationData): ?int
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
            if (!$this->canBook($tenantId, $date, $time, $partySize)) {
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
