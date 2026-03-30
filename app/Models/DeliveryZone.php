<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class DeliveryZone
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findByTenant(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM delivery_zones WHERE tenant_id = :tenant_id ORDER BY sort_order ASC, name ASC'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['postal_codes'] = json_decode($row['postal_codes'], true) ?? [];
        }
        return $rows;
    }

    public function findById(int $id, int $tenantId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM delivery_zones WHERE id = :id AND tenant_id = :tenant_id'
        );
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $row['postal_codes'] = json_decode($row['postal_codes'], true) ?? [];
        return $row;
    }

    /**
     * Trova la zona attiva che copre un dato CAP.
     */
    public function findByPostalCode(int $tenantId, string $cap): ?array
    {
        $zones = $this->findActiveByTenant($tenantId);
        foreach ($zones as $zone) {
            if (in_array($cap, $zone['postal_codes'], true)) {
                return $zone;
            }
        }
        return null;
    }

    public function findActiveByTenant(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM delivery_zones WHERE tenant_id = :tenant_id AND is_active = 1 ORDER BY sort_order ASC'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['postal_codes'] = json_decode($row['postal_codes'], true) ?? [];
        }
        return $rows;
    }

    public function create(int $tenantId, array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO delivery_zones (tenant_id, name, postal_codes, fee, min_amount, is_active, sort_order)
             VALUES (:tenant_id, :name, :postal_codes, :fee, :min_amount, :is_active, :sort_order)'
        );
        $stmt->execute([
            'tenant_id'    => $tenantId,
            'name'         => $data['name'],
            'postal_codes' => json_encode($data['postal_codes'] ?? [], JSON_UNESCAPED_UNICODE),
            'fee'          => (float)($data['fee'] ?? 0),
            'min_amount'   => (float)($data['min_amount'] ?? 0),
            'is_active'    => $data['is_active'] ?? 1,
            'sort_order'   => (int)($data['sort_order'] ?? 0),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, int $tenantId, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE delivery_zones
             SET name = :name, postal_codes = :postal_codes, fee = :fee,
                 min_amount = :min_amount, is_active = :is_active, sort_order = :sort_order
             WHERE id = :id AND tenant_id = :tenant_id'
        );
        return $stmt->execute([
            'id'           => $id,
            'tenant_id'    => $tenantId,
            'name'         => $data['name'],
            'postal_codes' => json_encode($data['postal_codes'] ?? [], JSON_UNESCAPED_UNICODE),
            'fee'          => (float)($data['fee'] ?? 0),
            'min_amount'   => (float)($data['min_amount'] ?? 0),
            'is_active'    => $data['is_active'] ?? 1,
            'sort_order'   => (int)($data['sort_order'] ?? 0),
        ]);
    }

    public function delete(int $id, int $tenantId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM delivery_zones WHERE id = :id AND tenant_id = :tenant_id'
        );
        return $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
    }
}
