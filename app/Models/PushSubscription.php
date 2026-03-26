<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class PushSubscription
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function subscribe(int $tenantId, int $userId, array $subscription): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO push_subscriptions (tenant_id, user_id, endpoint, p256dh, auth, user_agent)
             VALUES (:tid, :uid, :endpoint, :p256dh, :auth, :ua)
             ON DUPLICATE KEY UPDATE
                tenant_id = VALUES(tenant_id),
                user_id = VALUES(user_id),
                p256dh = VALUES(p256dh),
                auth = VALUES(auth),
                user_agent = VALUES(user_agent),
                created_at = NOW()'
        );
        return $stmt->execute([
            'tid'      => $tenantId,
            'uid'      => $userId,
            'endpoint' => $subscription['endpoint'],
            'p256dh'   => $subscription['p256dh'] ?? '',
            'auth'     => $subscription['auth'] ?? '',
            'ua'       => $subscription['user_agent'] ?? null,
        ]);
    }

    public function unsubscribe(string $endpoint): bool
    {
        $stmt = $this->db->prepare('DELETE FROM push_subscriptions WHERE endpoint = :endpoint');
        return $stmt->execute(['endpoint' => $endpoint]);
    }

    public function getByTenant(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM push_subscriptions WHERE tenant_id = :tid'
        );
        $stmt->execute(['tid' => $tenantId]);
        return $stmt->fetchAll();
    }

    public function getByUser(int $tenantId, int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM push_subscriptions WHERE tenant_id = :tid AND user_id = :uid'
        );
        $stmt->execute(['tid' => $tenantId, 'uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function unsubscribeByTenant(string $endpoint, int $tenantId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM push_subscriptions WHERE endpoint = :endpoint AND tenant_id = :tid');
        return $stmt->execute(['endpoint' => $endpoint, 'tid' => $tenantId]);
    }

    public function deleteByEndpoint(string $endpoint): void
    {
        $stmt = $this->db->prepare('DELETE FROM push_subscriptions WHERE endpoint = :endpoint');
        $stmt->execute(['endpoint' => $endpoint]);
    }
}
