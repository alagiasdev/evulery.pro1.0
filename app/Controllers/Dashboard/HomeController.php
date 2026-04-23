<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Cache;
use App\Core\Database;
use App\Core\Request;
use App\Core\TenantResolver;
use App\Models\Reservation;

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

        // --- User & tenant info for greeting ---
        $user = Auth::user();
        $tenant = TenantResolver::current();
        $userName = explode(' ', $user['name'] ?? '')[0]; // First name only

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
            'userName'      => $userName,
            'tenantName'    => $tenant['name'] ?? '',
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

    private function getMealCapacity(\PDO $db, int $tenantId, string $date): array
    {
        // Day of week: 0=Mon ... 6=Sun (matching time_slots.day_of_week)
        $dow = (int)date('N', strtotime($date)) - 1;

        // Get base capacity from time_slots
        $stmt = $db->prepare(
            'SELECT slot_time, max_covers
             FROM time_slots
             WHERE tenant_id = :tenant_id
             AND day_of_week = :dow
             AND is_active = 1'
        );
        $stmt->execute(['tenant_id' => $tenantId, 'dow' => $dow]);
        $slots = $stmt->fetchAll();

        // Check for date-specific overrides
        $stmt = $db->prepare(
            'SELECT slot_time, max_covers, is_closed
             FROM slot_overrides
             WHERE tenant_id = :tenant_id
             AND override_date = :date'
        );
        $stmt->execute(['tenant_id' => $tenantId, 'date' => $date]);
        $overrides = [];
        foreach ($stmt->fetchAll() as $ov) {
            if ($ov['slot_time'] === null) {
                // Whole day override
                if ($ov['is_closed']) {
                    return [
                        'pranzo' => ['capacity' => 0, 'booked' => 0, 'count' => 0],
                        'cena'   => ['capacity' => 0, 'booked' => 0, 'count' => 0],
                    ];
                }
            } else {
                $overrides[$ov['slot_time']] = $ov;
            }
        }

        // Calculate capacity per meal
        $capacity = ['pranzo' => 0, 'cena' => 0];
        foreach ($slots as $slot) {
            $time = $slot['slot_time'];
            $hour = (int)substr($time, 0, 2);
            $meal = ($hour < 16) ? 'pranzo' : 'cena';

            if (isset($overrides[$time])) {
                if (!$overrides[$time]['is_closed']) {
                    $capacity[$meal] += (int)$overrides[$time]['max_covers'];
                }
            } else {
                $capacity[$meal] += (int)$slot['max_covers'];
            }
        }

        // Get booked covers per meal
        $stmt = $db->prepare(
            'SELECT
                SUM(CASE WHEN HOUR(reservation_time) < 16 THEN party_size ELSE 0 END) as pranzo_booked,
                SUM(CASE WHEN HOUR(reservation_time) >= 16 THEN party_size ELSE 0 END) as cena_booked,
                SUM(CASE WHEN HOUR(reservation_time) < 16 THEN 1 ELSE 0 END) as pranzo_count,
                SUM(CASE WHEN HOUR(reservation_time) >= 16 THEN 1 ELSE 0 END) as cena_count
             FROM reservations
             WHERE tenant_id = :tenant_id
             AND reservation_date = :date
             AND status IN ("confirmed", "pending", "arrived")'
        );
        $stmt->execute(['tenant_id' => $tenantId, 'date' => $date]);
        $booked = $stmt->fetch();

        return [
            'pranzo' => [
                'capacity' => $capacity['pranzo'],
                'booked'   => (int)($booked['pranzo_booked'] ?? 0),
                'count'    => (int)($booked['pranzo_count'] ?? 0),
            ],
            'cena' => [
                'capacity' => $capacity['cena'],
                'booked'   => (int)($booked['cena_booked'] ?? 0),
                'count'    => (int)($booked['cena_count'] ?? 0),
            ],
        ];
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