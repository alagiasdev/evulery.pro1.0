<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Customer
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM customers WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findByTenantAndEmail(int $tenantId, string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM customers WHERE tenant_id = :tenant_id AND email = :email LIMIT 1'
        );
        $stmt->execute(['tenant_id' => $tenantId, 'email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public function findByTenant(int $tenantId, ?string $search = null): array
    {
        $sql = 'SELECT * FROM customers WHERE tenant_id = :tenant_id';
        $params = ['tenant_id' => $tenantId];

        if ($search) {
            $sql .= ' AND (first_name LIKE :search OR last_name LIKE :search2 OR email LIKE :search3 OR phone LIKE :search4)';
            $like = "%{$search}%";
            $params['search'] = $like;
            $params['search2'] = $like;
            $params['search3'] = $like;
            $params['search4'] = $like;
        }

        $sql .= ' ORDER BY last_name ASC, first_name ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Paginated customer list with search + segment filter (SQL-based).
     */
    public function findByTenantPaginated(
        int $tenantId,
        ?string $search,
        ?string $segment,
        array $thresholds,
        int $limit,
        int $offset
    ): array {
        $sql = 'SELECT * FROM customers WHERE tenant_id = :tenant_id';
        $params = ['tenant_id' => $tenantId];

        if ($search) {
            $sql .= ' AND (first_name LIKE :s1 OR last_name LIKE :s2 OR email LIKE :s3 OR phone LIKE :s4)';
            $like = "%{$search}%";
            $params['s1'] = $like;
            $params['s2'] = $like;
            $params['s3'] = $like;
            $params['s4'] = $like;
        }

        $sql .= $this->segmentWhere($segment, $thresholds, $params);
        $sql .= ' ORDER BY last_name ASC, first_name ASC LIMIT :lim OFFSET :off';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue('off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Count customers with search + segment filter.
     */
    public function countByTenantFiltered(
        int $tenantId,
        ?string $search,
        ?string $segment,
        array $thresholds
    ): int {
        $sql = 'SELECT COUNT(*) FROM customers WHERE tenant_id = :tenant_id';
        $params = ['tenant_id' => $tenantId];

        if ($search) {
            $sql .= ' AND (first_name LIKE :s1 OR last_name LIKE :s2 OR email LIKE :s3 OR phone LIKE :s4)';
            $like = "%{$search}%";
            $params['s1'] = $like;
            $params['s2'] = $like;
            $params['s3'] = $like;
            $params['s4'] = $like;
        }

        $sql .= $this->segmentWhere($segment, $thresholds, $params);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get segment counts for stats tabs (single query).
     */
    public function segmentCounts(int $tenantId, array $thresholds): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                COUNT(*) as totale,
                SUM(CASE WHEN total_bookings < :th_occ1 THEN 1 ELSE 0 END) as nuovo,
                SUM(CASE WHEN total_bookings >= :th_occ2 AND total_bookings < :th_abi1 THEN 1 ELSE 0 END) as occasionale,
                SUM(CASE WHEN total_bookings >= :th_abi2 AND total_bookings < :th_vip1 THEN 1 ELSE 0 END) as abituale,
                SUM(CASE WHEN total_bookings >= :th_vip2 THEN 1 ELSE 0 END) as vip
             FROM customers WHERE tenant_id = :tenant_id'
        );
        $stmt->execute([
            'tenant_id' => $tenantId,
            'th_occ1'   => $thresholds['occ'],
            'th_occ2'   => $thresholds['occ'],
            'th_abi1'   => $thresholds['abi'],
            'th_abi2'   => $thresholds['abi'],
            'th_vip1'   => $thresholds['vip'],
            'th_vip2'   => $thresholds['vip'],
        ]);
        $row = $stmt->fetch();
        return [
            'totale'      => (int)$row['totale'],
            'nuovo'       => (int)$row['nuovo'],
            'occasionale' => (int)$row['occasionale'],
            'abituale'    => (int)$row['abituale'],
            'vip'         => (int)$row['vip'],
        ];
    }

    private function segmentWhere(?string $segment, array $thresholds, array &$params): string
    {
        if (!$segment) return '';

        return match ($segment) {
            'nuovo'       => ' AND total_bookings < ' . (int)$thresholds['occ'],
            'occasionale' => ' AND total_bookings >= ' . (int)$thresholds['occ'] . ' AND total_bookings < ' . (int)$thresholds['abi'],
            'abituale'    => ' AND total_bookings >= ' . (int)$thresholds['abi'] . ' AND total_bookings < ' . (int)$thresholds['vip'],
            'vip'         => ' AND total_bookings >= ' . (int)$thresholds['vip'],
            default       => '',
        };
    }

    public function findOrCreate(int $tenantId, array $data): array
    {
        $existing = $this->findByTenantAndEmail($tenantId, $data['email']);

        if ($existing) {
            // Update name/phone if changed
            $stmt = $this->db->prepare(
                'UPDATE customers SET first_name = :first_name, last_name = :last_name, phone = :phone WHERE id = :id'
            );
            $stmt->execute([
                'id'         => $existing['id'],
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'phone'      => $data['phone'],
            ]);
            $existing['first_name'] = $data['first_name'];
            $existing['last_name'] = $data['last_name'];
            $existing['phone'] = $data['phone'];
            return $existing;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO customers (tenant_id, first_name, last_name, email, phone)
             VALUES (:tenant_id, :first_name, :last_name, :email, :phone)'
        );
        $stmt->execute([
            'tenant_id'  => $tenantId,
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'],
        ]);

        return $this->findById((int)$this->db->lastInsertId());
    }

    public function incrementBookings(int $id): void
    {
        $this->db->prepare('UPDATE customers SET total_bookings = total_bookings + 1 WHERE id = :id')
                 ->execute(['id' => $id]);
    }

    public function decrementBookings(int $id): void
    {
        $this->db->prepare('UPDATE customers SET total_bookings = GREATEST(total_bookings - 1, 0) WHERE id = :id')
                 ->execute(['id' => $id]);
    }

    public function incrementNoshow(int $id): void
    {
        $this->db->prepare('UPDATE customers SET total_noshow = total_noshow + 1 WHERE id = :id')
                 ->execute(['id' => $id]);
    }

    public function updateNotes(int $id, string $notes): void
    {
        $this->db->prepare('UPDATE customers SET notes = :notes WHERE id = :id')
                 ->execute(['id' => $id, 'notes' => $notes]);
    }

    public function countByTenant(int $tenantId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM customers WHERE tenant_id = :tenant_id');
        $stmt->execute(['tenant_id' => $tenantId]);
        return (int)$stmt->fetchColumn();
    }

    public function block(int $id): void
    {
        $this->db->prepare('UPDATE customers SET is_blocked = 1, blocked_at = NOW() WHERE id = :id')
                 ->execute(['id' => $id]);
    }

    public function unblock(int $id): void
    {
        $this->db->prepare('UPDATE customers SET is_blocked = 0, blocked_at = NULL WHERE id = :id')
                 ->execute(['id' => $id]);
    }

    public function isBlocked(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT is_blocked FROM customers WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return (bool)$stmt->fetchColumn();
    }
}
