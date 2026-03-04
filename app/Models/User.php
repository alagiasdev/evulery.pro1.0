<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public function findByTenant(int $tenantId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE tenant_id = :tenant_id ORDER BY created_at DESC');
        $stmt->execute(['tenant_id' => $tenantId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (tenant_id, email, password_hash, first_name, last_name, role, is_active)
             VALUES (:tenant_id, :email, :password_hash, :first_name, :last_name, :role, :is_active)'
        );
        $stmt->execute([
            'tenant_id'     => $data['tenant_id'] ?? null,
            'email'         => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'],
            'role'          => $data['role'] ?? 'owner',
            'is_active'     => $data['is_active'] ?? 1,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        foreach (['first_name', 'last_name', 'email', 'is_active', 'role'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`{$field}` = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if (isset($data['password']) && $data['password'] !== '') {
            $fields[] = '`password_hash` = :password_hash';
            $params['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if (empty($fields)) {
            return false;
        }

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}
