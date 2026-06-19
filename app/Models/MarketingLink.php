<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Campagne salvate del generatore link (Marketing). Dedup su
 * destination+source+medium+campaign; performance via join con reservations
 * (stesso channel + utm_campaign).
 */
class MarketingLink
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(array $d): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO marketing_links (tenant_id, destination, utm_source, utm_medium, utm_campaign, channel, url)
             VALUES (:t, :dest, :src, :med, :camp, :ch, :url)'
        );
        $stmt->execute([
            't'    => $d['tenant_id'],
            'dest' => $d['destination'],
            'src'  => $d['utm_source'],
            'med'  => $d['utm_medium'] ?? null,
            'camp' => $d['utm_campaign'] ?? null,
            'ch'   => $d['channel'],
            'url'  => $d['url'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Campagne salvate del tenant con il numero di prenotazioni attribuite
     * (match su channel + utm_campaign, escluse annullate/no-show).
     */
    public function findByTenantWithStats(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ml.*,
                    (SELECT COUNT(*) FROM reservations r
                       WHERE r.tenant_id = ml.tenant_id
                         AND r.channel = ml.channel
                         AND COALESCE(r.utm_campaign, '') = COALESCE(ml.utm_campaign, '')
                         AND r.status NOT IN ('cancelled', 'noshow')) AS bookings
             FROM marketing_links ml
             WHERE ml.tenant_id = :t
             ORDER BY ml.created_at DESC"
        );
        $stmt->execute(['t' => $tenantId]);
        return $stmt->fetchAll();
    }

    /** True se esiste gia' una campagna con la stessa chiave (anti-duplicato). */
    public function existsDuplicate(int $tenantId, string $destination, string $source, ?string $medium, ?string $campaign): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM marketing_links
             WHERE tenant_id = :t AND destination = :dest AND utm_source = :src
               AND COALESCE(utm_medium, '') = COALESCE(:med, '')
               AND COALESCE(utm_campaign, '') = COALESCE(:camp, '')
             LIMIT 1"
        );
        $stmt->execute(['t' => $tenantId, 'dest' => $destination, 'src' => $source, 'med' => $medium, 'camp' => $campaign]);
        return (bool)$stmt->fetchColumn();
    }

    public function delete(int $id, int $tenantId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM marketing_links WHERE id = :id AND tenant_id = :t');
        $stmt->execute(['id' => $id, 't' => $tenantId]);
        return $stmt->rowCount() > 0;
    }
}
