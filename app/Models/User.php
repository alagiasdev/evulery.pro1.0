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

    /**
     * Elimina un collaboratore 'staff'. Il WHERE è la guardia: solo un utente
     * staff di QUEL tenant può essere rimosso (mai un owner, mai altri tenant).
     */
    public function deleteStaff(int $id, int $tenantId): int
    {
        $stmt = $this->db->prepare(
            "DELETE FROM users WHERE id = :id AND tenant_id = :tid AND role = 'staff'"
        );
        $stmt->execute(['id' => $id, 'tid' => $tenantId]);
        return $stmt->rowCount();
    }

    /**
     * Aggiorna l'utente.
     *
     * I campi privilegiati `role` e `is_active` sono accettati solo se
     * `$allowPrivileged = true` (admin context). Le self-edit dal proprio
     * profilo DEVONO chiamare con `false` (default) per impedire
     * privilege escalation via mass-assignment.
     */
    public function update(int $id, array $data, bool $allowPrivileged = false): bool
    {
        $allowedFields = ['first_name', 'last_name', 'email'];
        if ($allowPrivileged) {
            $allowedFields[] = 'is_active';
            $allowedFields[] = 'role';
        }

        $fields = [];
        $params = ['id' => $id];

        foreach ($allowedFields as $field) {
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

    public function countFiltered(?string $search = null, ?string $role = null, ?int $tenantId = null, ?int $isActive = null): int
    {
        $where = [];
        $params = [];

        if ($search !== null && $search !== '') {
            $where[] = '(u.first_name LIKE :s OR u.last_name LIKE :s OR u.email LIKE :s)';
            $params['s'] = '%' . $search . '%';
        }
        if ($role !== null && $role !== '') {
            $where[] = 'u.role = :role';
            $params['role'] = $role;
        }
        if ($tenantId !== null) {
            $where[] = 'u.tenant_id = :tid';
            $params['tid'] = $tenantId;
        }
        if ($isActive !== null) {
            $where[] = 'u.is_active = :active';
            $params['active'] = $isActive;
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users u {$whereSql}");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function allPaginated(?string $search = null, ?string $role = null, ?int $tenantId = null, ?int $isActive = null, int $limit = 25, int $offset = 0): array
    {
        $where = [];
        $params = [];

        if ($search !== null && $search !== '') {
            $where[] = '(u.first_name LIKE :s OR u.last_name LIKE :s OR u.email LIKE :s)';
            $params['s'] = '%' . $search . '%';
        }
        if ($role !== null && $role !== '') {
            $where[] = 'u.role = :role';
            $params['role'] = $role;
        }
        if ($tenantId !== null) {
            $where[] = 'u.tenant_id = :tid';
            $params['tid'] = $tenantId;
        }
        if ($isActive !== null) {
            $where[] = 'u.is_active = :active';
            $params['active'] = $isActive;
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $stmt = $this->db->prepare(
            "SELECT u.*, t.name AS tenant_name, t.slug AS tenant_slug
             FROM users u
             LEFT JOIN tenants t ON t.id = u.tenant_id
             {$whereSql}
             ORDER BY u.created_at DESC
             LIMIT :lim OFFSET :off"
        );
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue('off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
