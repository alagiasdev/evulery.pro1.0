<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/** Click sui pulsanti della Vetrina/Hub, con l'UTM in arrivo. */
class HubClick
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function record(int $tenantId, string $ref, string $type, ?string $label, string $channel, ?string $src, ?string $med, ?string $camp): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO hub_clicks (tenant_id, ref, button_type, label, channel, utm_source, utm_medium, utm_campaign)
             VALUES (:t, :ref, :type, :label, :ch, :s, :m, :c)'
        );
        $stmt->execute([
            't' => $tenantId, 'ref' => $ref, 'type' => $type, 'label' => $label,
            'ch' => $channel, 's' => $src, 'm' => $med, 'c' => $camp,
        ]);
    }

    /** Click totali per canale + campagna nel periodo. */
    public function aggregate(int $tenantId, string $from, string $to): array
    {
        $stmt = $this->db->prepare(
            "SELECT channel, COALESCE(utm_campaign, '') AS campaign, COUNT(*) AS n
             FROM hub_clicks
             WHERE tenant_id = :t AND DATE(created_at) BETWEEN :from AND :to
             GROUP BY channel, campaign"
        );
        $stmt->execute(['t' => $tenantId, 'from' => $from, 'to' => $to]);
        return $stmt->fetchAll();
    }

    /** Click per canale + campagna + pulsante (per il dettaglio per-pulsante). */
    public function aggregateByButton(int $tenantId, string $from, string $to): array
    {
        $stmt = $this->db->prepare(
            "SELECT channel, COALESCE(utm_campaign, '') AS campaign, ref, COUNT(*) AS n
             FROM hub_clicks
             WHERE tenant_id = :t AND DATE(created_at) BETWEEN :from AND :to
             GROUP BY channel, campaign, ref"
        );
        $stmt->execute(['t' => $tenantId, 'from' => $from, 'to' => $to]);
        return $stmt->fetchAll();
    }
}
