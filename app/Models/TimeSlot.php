<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class TimeSlot
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findByTenantAndDay(int $tenantId, int $dayOfWeek): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM time_slots
             WHERE tenant_id = :tenant_id AND day_of_week = :day AND is_active = 1 AND max_covers > 0
             ORDER BY slot_time ASC'
        );
        $stmt->execute(['tenant_id' => $tenantId, 'day' => $dayOfWeek]);
        return $stmt->fetchAll();
    }

    public function findAllByTenant(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM time_slots WHERE tenant_id = :tenant_id ORDER BY day_of_week ASC, slot_time ASC'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        return $stmt->fetchAll();
    }

    /**
     * Returns the weekdays (0=Mon..6=Sun) that have at least one active slot with covers > 0.
     */
    public function getWorkingWeekdays(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT DISTINCT day_of_week FROM time_slots
             WHERE tenant_id = :tenant_id AND is_active = 1 AND max_covers > 0
             ORDER BY day_of_week ASC'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function upsert(int $tenantId, int $dayOfWeek, string $slotTime, int $maxCovers, bool $isActive = true): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO time_slots (tenant_id, day_of_week, slot_time, max_covers, is_active)
             VALUES (:tenant_id, :day, :time, :covers, :active)
             ON DUPLICATE KEY UPDATE max_covers = :covers2, is_active = :active2'
        );
        $stmt->execute([
            'tenant_id' => $tenantId,
            'day'       => $dayOfWeek,
            'time'      => $slotTime,
            'covers'    => $maxCovers,
            'active'    => $isActive ? 1 : 0,
            'covers2'   => $maxCovers,
            'active2'   => $isActive ? 1 : 0,
        ]);
    }

    public function deleteByTenant(int $tenantId): void
    {
        $this->db->prepare('DELETE FROM time_slots WHERE tenant_id = :tenant_id')
                 ->execute(['tenant_id' => $tenantId]);
    }
}
