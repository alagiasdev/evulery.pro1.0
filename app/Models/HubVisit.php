<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/** Visite alla Vetrina/Hub, con l'UTM in arrivo (per il funnel marketing). */
class HubVisit
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function record(int $tenantId, string $channel, ?string $src, ?string $med, ?string $camp): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO hub_visits (tenant_id, channel, utm_source, utm_medium, utm_campaign)
             VALUES (:t, :ch, :s, :m, :c)'
        );
        $stmt->execute(['t' => $tenantId, 'ch' => $channel, 's' => $src, 'm' => $med, 'c' => $camp]);
    }

    /** Visite per canale + campagna nel periodo (per created_at). */
    public function aggregate(int $tenantId, string $from, string $to): array
    {
        $stmt = $this->db->prepare(
            "SELECT channel, COALESCE(utm_campaign, '') AS campaign, COUNT(*) AS n
             FROM hub_visits
             WHERE tenant_id = :t AND DATE(created_at) BETWEEN :from AND :to
             GROUP BY channel, campaign"
        );
        $stmt->execute(['t' => $tenantId, 'from' => $from, 'to' => $to]);
        return $stmt->fetchAll();
    }
}
