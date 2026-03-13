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
}
