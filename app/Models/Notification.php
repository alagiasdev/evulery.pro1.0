<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Notification
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(int $tenantId, string $type, string $title, ?string $body = null, ?array $data = null): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO notifications (tenant_id, type, title, body, data)
             VALUES (:tenant_id, :type, :title, :body, :data)'
        );
        $stmt->execute([
            'tenant_id' => $tenantId,
            'type'      => $type,
            'title'     => $title,
            'body'      => $body,
            'data'      => $data ? json_encode($data) : null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function getUnreadCount(int $tenantId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM notifications WHERE tenant_id = :tid AND read_at IS NULL'
        );
        $stmt->execute(['tid' => $tenantId]);
        return (int)$stmt->fetchColumn();
    }

    public function getRecent(int $tenantId, int $limit = 20): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM notifications WHERE tenant_id = :tid ORDER BY created_at DESC LIMIT :lim'
        );
        $stmt->bindValue('tid', $tenantId, PDO::PARAM_INT);
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            if ($row['data']) {
                $row['data'] = json_decode($row['data'], true);
            }
        }
        unset($row);

        return $rows;
    }

    public function getAllPaginated(int $tenantId, int $limit, int $offset): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM notifications WHERE tenant_id = :tid ORDER BY created_at DESC LIMIT :lim OFFSET :off'
        );
        $stmt->bindValue('tid', $tenantId, PDO::PARAM_INT);
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue('off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            if ($row['data']) {
                $row['data'] = json_decode($row['data'], true);
            }
        }
        unset($row);

        return $rows;
    }

    public function countAll(int $tenantId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM notifications WHERE tenant_id = :tid');
        $stmt->execute(['tid' => $tenantId]);
        return (int)$stmt->fetchColumn();
    }

    public function markAsRead(int $id, int $tenantId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE notifications SET read_at = NOW() WHERE id = :id AND tenant_id = :tid AND read_at IS NULL'
        );
        return $stmt->execute(['id' => $id, 'tid' => $tenantId]);
    }

    public function markAllRead(int $tenantId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE notifications SET read_at = NOW() WHERE tenant_id = :tid AND read_at IS NULL'
        );
        return $stmt->execute(['tid' => $tenantId]);
    }

    public function delete(int $id, int $tenantId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM notifications WHERE id = :id AND tenant_id = :tid'
        );
        return $stmt->execute(['id' => $id, 'tid' => $tenantId]);
    }

    public function deleteAll(int $tenantId): int
    {
        $stmt = $this->db->prepare('DELETE FROM notifications WHERE tenant_id = :tid');
        $stmt->execute(['tid' => $tenantId]);
        return $stmt->rowCount();
    }

    public function purgeOld(int $days = 90): int
    {
        $stmt = $this->db->prepare(
            'DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)'
        );
        $stmt->execute(['days' => $days]);
        return $stmt->rowCount();
    }
}
