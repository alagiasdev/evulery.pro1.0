<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Tenant
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM tenants WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM tenants WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetch() ?: null;
    }

    public function findByDomain(string $domain): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM tenants WHERE custom_domain = :domain AND is_active = 1 LIMIT 1');
        $stmt->execute(['domain' => $domain]);
        return $stmt->fetch() ?: null;
    }

    public function all(): array
    {
        $stmt = $this->db->prepare('SELECT * FROM tenants ORDER BY created_at DESC');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO tenants (slug, name, email, phone, address, plan, plan_price, table_duration, time_step, is_active)
             VALUES (:slug, :name, :email, :phone, :address, :plan, :plan_price, :table_duration, :time_step, :is_active)'
        );
        $stmt->execute([
            'slug'           => $data['slug'],
            'name'           => $data['name'],
            'email'          => $data['email'],
            'phone'          => $data['phone'] ?? null,
            'address'        => $data['address'] ?? null,
            'plan'           => $data['plan'] ?? 'base',
            'plan_price'     => $data['plan_price'] ?? 49.00,
            'table_duration' => $data['table_duration'] ?? 90,
            'time_step'      => $data['time_step'] ?? 30,
            'is_active'      => $data['is_active'] ?? 0,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];
        $allowed = [
            'slug', 'name', 'email', 'phone', 'address', 'logo_url',
            'custom_domain', 'domain_status', 'cname_target',
            'plan', 'plan_price', 'deposit_enabled', 'deposit_amount',
            'cancellation_policy', 'table_duration', 'time_step',
            'booking_advance_min', 'booking_advance_max', 'timezone', 'is_active',
        ];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`{$field}` = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = 'UPDATE tenants SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function toggleActive(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE tenants SET is_active = NOT is_active WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function count(): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM tenants');
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function countActive(): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM tenants WHERE is_active = 1');
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
}
