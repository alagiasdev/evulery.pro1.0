<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Service
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all(): array
    {
        return $this->db->query('SELECT * FROM services ORDER BY sort_order ASC')->fetchAll();
    }

    public function allActive(): array
    {
        return $this->db->query('SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order ASC')->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM services WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findByKey(string $key): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM services WHERE `key` = :key LIMIT 1');
        $stmt->execute(['key' => $key]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $maxSort = (int)$this->db->query('SELECT COALESCE(MAX(sort_order), 0) FROM services')->fetchColumn();

        $stmt = $this->db->prepare(
            'INSERT INTO services (`key`, name, description, sort_order, is_active)
             VALUES (:key, :name, :description, :sort_order, :is_active)'
        );
        $stmt->execute([
            'key'         => $data['key'],
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'sort_order'  => $maxSort + 1,
            'is_active'   => $data['is_active'] ?? 1,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];
        $allowed = ['key', 'name', 'description', 'sort_order', 'is_active'];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`{$field}` = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = 'UPDATE services SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        // Don't delete if used in any plan
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM plan_services WHERE service_id = :id');
        $stmt->execute(['id' => $id]);
        if ((int)$stmt->fetchColumn() > 0) {
            return false;
        }

        $stmt = $this->db->prepare('DELETE FROM services WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Get all services with which plans include them
     */
    public function allWithPlans(): array
    {
        $services = $this->all();

        $rows = $this->db->query(
            'SELECT ps.service_id, p.id as plan_id, p.name as plan_name, p.color as plan_color
             FROM plan_services ps
             JOIN plans p ON p.id = ps.plan_id
             ORDER BY p.sort_order ASC'
        )->fetchAll();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['service_id']][] = $row;
        }

        foreach ($services as &$svc) {
            $svc['plans'] = $map[$svc['id']] ?? [];
        }

        return $services;
    }

    public function count(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM services')->fetchColumn();
    }
}
