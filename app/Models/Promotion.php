<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Promotion
{
    private PDO $db;
    private static array $cache = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findAllByTenant(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM promotions WHERE tenant_id = :tenant_id ORDER BY is_active DESC, created_at DESC'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        return $stmt->fetchAll();
    }

    public function findActiveByTenant(int $tenantId): array
    {
        if (isset(self::$cache[$tenantId])) {
            return self::$cache[$tenantId];
        }

        $stmt = $this->db->prepare(
            'SELECT * FROM promotions WHERE tenant_id = :tenant_id AND is_active = 1'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        self::$cache[$tenantId] = $stmt->fetchAll();
        return self::$cache[$tenantId];
    }

    public function findById(int $id, int $tenantId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM promotions WHERE id = :id AND tenant_id = :tenant_id'
        );
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        return $stmt->fetch() ?: null;
    }

    public function create(int $tenantId, array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO promotions (tenant_id, name, description, discount_percent, type, days_of_week, time_from, time_to, date_from, date_to, is_active, applies_to)
             VALUES (:tenant_id, :name, :description, :discount_percent, :type, :days_of_week, :time_from, :time_to, :date_from, :date_to, :is_active, :applies_to)'
        );
        $stmt->execute([
            'tenant_id'        => $tenantId,
            'name'             => $data['name'],
            'description'      => $data['description'] ?? null,
            'discount_percent' => (int)$data['discount_percent'],
            'type'             => $data['type'],
            'days_of_week'     => $data['days_of_week'] ?? null,
            'time_from'        => $data['time_from'] ?? null,
            'time_to'          => $data['time_to'] ?? null,
            'date_from'        => $data['date_from'] ?? null,
            'date_to'          => $data['date_to'] ?? null,
            'is_active'        => $data['is_active'] ?? 1,
            'applies_to'       => $data['applies_to'] ?? 'all',
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, int $tenantId, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE promotions SET name = :name, description = :description,
                    discount_percent = :discount_percent, type = :type,
                    days_of_week = :days_of_week, time_from = :time_from, time_to = :time_to,
                    date_from = :date_from, date_to = :date_to, applies_to = :applies_to
             WHERE id = :id AND tenant_id = :tenant_id'
        );
        return $stmt->execute([
            'id'               => $id,
            'tenant_id'        => $tenantId,
            'name'             => $data['name'],
            'description'      => $data['description'] ?? null,
            'discount_percent' => (int)$data['discount_percent'],
            'type'             => $data['type'],
            'days_of_week'     => $data['days_of_week'] ?? null,
            'time_from'        => $data['time_from'] ?? null,
            'time_to'          => $data['time_to'] ?? null,
            'date_from'        => $data['date_from'] ?? null,
            'date_to'          => $data['date_to'] ?? null,
            'applies_to'       => $data['applies_to'] ?? 'all',
        ]);
    }

    public function toggleActive(int $id, int $tenantId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE promotions SET is_active = NOT is_active WHERE id = :id AND tenant_id = :tenant_id'
        );
        return $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
    }

    public function delete(int $id, int $tenantId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM promotions WHERE id = :id AND tenant_id = :tenant_id'
        );
        return $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
    }

    /**
     * Count reservations with discount in the last N days.
     */
    public function getDiscountedBookings(int $tenantId, int $days = 30): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as cnt FROM reservations
             WHERE tenant_id = :tenant_id AND discount_percent IS NOT NULL
             AND reservation_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)'
        );
        $stmt->execute(['tenant_id' => $tenantId, 'days' => $days]);
        return (int)$stmt->fetch()['cnt'];
    }

    /**
     * Calculate the booking increase % in promo slots vs non-promo.
     * Compares last 30 days with discount vs previous 30 days.
     */
    public function getPromoGrowthPercent(int $tenantId): ?int
    {
        // Last 30 days discounted bookings
        $current = $this->getDiscountedBookings($tenantId, 30);
        // Previous 30 days (31-60)
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as cnt FROM reservations
             WHERE tenant_id = :tenant_id AND discount_percent IS NOT NULL
             AND reservation_date >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)
             AND reservation_date < DATE_SUB(CURDATE(), INTERVAL 30 DAY)'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        $previous = (int)$stmt->fetch()['cnt'];

        if ($previous === 0 && $current === 0) return null;
        if ($previous === 0) return 100;
        return (int)round(($current - $previous) / $previous * 100);
    }

    /**
     * Count reservations per promotion (using discount_percent match) in last N days.
     * Returns array keyed by promotion id => count.
     */
    public function getBookingsPerPromo(int $tenantId, int $days = 30): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.id, COUNT(r.id) as cnt
             FROM promotions p
             LEFT JOIN reservations r ON r.promotion_id = p.id
                AND r.reservation_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
             WHERE p.tenant_id = :tenant_id
             GROUP BY p.id'
        );
        $stmt->execute(['tenant_id' => $tenantId, 'days' => $days]);
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(int)$row['id']] = (int)$row['cnt'];
        }
        return $result;
    }

    /**
     * Find the best applicable promotion for a given date+time.
     * Returns the promotion with the highest discount, or null.
     * Priority on tie: specific_date > recurring.
     *
     * @param string $context 'reservations' or 'orders' — filters by applies_to field
     */
    public function findApplicable(int $tenantId, string $date, string $time, string $context = 'reservations'): ?array
    {
        $promos = $this->findActiveByTenant($tenantId);
        if (empty($promos)) {
            return null;
        }

        // day of week: 0=Mon ... 6=Sun (date('N') - 1)
        $dow = (int)date('N', strtotime($date)) - 1;
        $timeMinutes = $this->timeToMinutes($time);

        $typePriority = ['specific_date' => 3, 'recurring' => 2, 'time_slot' => 2];
        $best = null;
        $bestScore = 0;

        foreach ($promos as $promo) {
            // Filter by context (applies_to)
            $appliesTo = $promo['applies_to'] ?? 'all';
            if ($appliesTo !== 'all' && $appliesTo !== $context) {
                continue;
            }

            if (!$this->matchesPromotion($promo, $date, $dow, $timeMinutes)) {
                continue;
            }

            $score = ($promo['discount_percent'] * 10) + ($typePriority[$promo['type']] ?? 0);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $promo;
            }
        }

        return $best;
    }

    private function matchesPromotion(array $promo, string $date, int $dow, int $timeMinutes): bool
    {
        switch ($promo['type']) {
            case 'recurring':
            case 'time_slot': // legacy — treated as recurring
                if ($promo['days_of_week'] !== null) {
                    $days = array_map('intval', explode(',', $promo['days_of_week']));
                    if (!in_array($dow, $days)) {
                        return false;
                    }
                }
                if ($promo['time_from'] && $promo['time_to']) {
                    $from = $this->timeToMinutes($promo['time_from']);
                    $to = $this->timeToMinutes($promo['time_to']);
                    if ($timeMinutes < $from || $timeMinutes >= $to) {
                        return false;
                    }
                }
                return true;

            case 'specific_date':
                if (!$promo['date_from']) {
                    return false;
                }
                $dateTo = $promo['date_to'] ?: $promo['date_from'];
                if ($date < $promo['date_from'] || $date > $dateTo) {
                    return false;
                }
                if ($promo['time_from'] && $promo['time_to']) {
                    $from = $this->timeToMinutes($promo['time_from']);
                    $to = $this->timeToMinutes($promo['time_to']);
                    if ($timeMinutes < $from || $timeMinutes >= $to) {
                        return false;
                    }
                }
                return true;

            default:
                return false;
        }
    }

    private function timeToMinutes(string $time): int
    {
        $parts = explode(':', substr($time, 0, 5));
        return (int)$parts[0] * 60 + (int)($parts[1] ?? 0);
    }
}
