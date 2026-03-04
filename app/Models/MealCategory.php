<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class MealCategory
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findActiveByTenant(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM meal_categories
             WHERE tenant_id = :tenant_id AND is_active = 1
             ORDER BY sort_order ASC'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        return $stmt->fetchAll();
    }

    public function findAllByTenant(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM meal_categories
             WHERE tenant_id = :tenant_id
             ORDER BY sort_order ASC'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        return $stmt->fetchAll();
    }

    public function categorizeTime(array $categories, string $slotTime): ?array
    {
        $slotMinutes = $this->timeToMinutes($slotTime);
        foreach ($categories as $cat) {
            $start = $this->timeToMinutes(substr($cat['start_time'], 0, 5));
            $end   = $this->timeToMinutes(substr($cat['end_time'], 0, 5));
            if ($slotMinutes >= $start && $slotMinutes < $end) {
                return $cat;
            }
        }
        return null;
    }

    private function timeToMinutes(string $time): int
    {
        $parts = explode(':', $time);
        return (int)$parts[0] * 60 + (int)$parts[1];
    }

    public function upsert(int $tenantId, array $data): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO meal_categories (tenant_id, name, display_name, start_time, end_time, sort_order, is_active)
             VALUES (:tenant_id, :name, :display_name, :start_time, :end_time, :sort_order, :is_active)
             ON DUPLICATE KEY UPDATE
                display_name = VALUES(display_name),
                start_time = VALUES(start_time),
                end_time = VALUES(end_time),
                sort_order = VALUES(sort_order),
                is_active = VALUES(is_active)'
        );
        $stmt->execute([
            'tenant_id'    => $tenantId,
            'name'         => $data['name'],
            'display_name' => $data['display_name'],
            'start_time'   => $data['start_time'],
            'end_time'     => $data['end_time'],
            'sort_order'   => (int)($data['sort_order'] ?? 0),
            'is_active'    => $data['is_active'] ? 1 : 0,
        ]);
    }

    public function seedDefaults(int $tenantId): void
    {
        $defaults = [
            ['brunch',       'Brunch',       '09:00', '11:30', 1],
            ['pranzo',       'Pranzo',       '11:30', '15:00', 2],
            ['aperitivo',    'Aperitivo',    '17:00', '19:00', 3],
            ['cena',         'Cena',         '19:00', '22:30', 4],
            ['after_dinner', 'After Dinner', '22:30', '23:59', 5],
        ];
        foreach ($defaults as [$name, $display, $start, $end, $order]) {
            $this->upsert($tenantId, [
                'name'         => $name,
                'display_name' => $display,
                'start_time'   => $start,
                'end_time'     => $end,
                'sort_order'   => $order,
                'is_active'    => true,
            ]);
        }
    }

    public function delete(int $id, int $tenantId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM meal_categories WHERE id = :id AND tenant_id = :tenant_id'
        );
        return $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
    }
}
