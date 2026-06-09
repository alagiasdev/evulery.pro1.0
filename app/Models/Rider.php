<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Rider
{
    private PDO $db;

    /** Palette fissa accettata lato server (vedi wireframe color picker). */
    public const ALLOWED_COLORS = [
        '#dc3545', '#0d6efd', '#f59e0b', '#6f42c1',
        '#0EA5E9', '#10b981', '#ec4899', '#6c757d',
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id, int $tenantId): ?array
    {
        // Esclude i rider soft-deleted: la UI non deve mai mostrarli ne'
        // permetterne l'uso (es. assegnazione, modifica). Lo storico ordini
        // fa JOIN diretto, non passa da qui.
        $stmt = $this->db->prepare(
            'SELECT * FROM riders
             WHERE id = :id AND tenant_id = :tid AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'tid' => $tenantId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Tutti i rider del tenant, attivi e archiviati (esclusi i soft-deleted). */
    public function findAll(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM riders
             WHERE tenant_id = :tid AND deleted_at IS NULL
             ORDER BY is_active DESC, name ASC'
        );
        $stmt->execute(['tid' => $tenantId]);
        return $stmt->fetchAll();
    }

    /** Solo rider attivi (usato per il dropdown assegnazione). */
    public function findActive(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, color_hex FROM riders
             WHERE tenant_id = :tid AND is_active = 1 AND deleted_at IS NULL
             ORDER BY name ASC'
        );
        $stmt->execute(['tid' => $tenantId]);
        return $stmt->fetchAll();
    }

    public function create(int $tenantId, array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO riders (tenant_id, name, phone, color_hex, is_active)
             VALUES (:tid, :name, :phone, :color, :active)'
        );
        $stmt->execute([
            'tid'    => $tenantId,
            'name'   => $data['name'],
            'phone'  => $data['phone'] ?: null,
            'color'  => $this->validColor($data['color_hex'] ?? null),
            'active' => !empty($data['is_active']) ? 1 : 0,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, int $tenantId, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE riders SET name = :name, phone = :phone,
                color_hex = :color, is_active = :active
             WHERE id = :id AND tenant_id = :tid'
        );
        return $stmt->execute([
            'name'   => $data['name'],
            'phone'  => $data['phone'] ?: null,
            'color'  => $this->validColor($data['color_hex'] ?? null),
            'active' => !empty($data['is_active']) ? 1 : 0,
            'id'     => $id,
            'tid'    => $tenantId,
        ]);
    }

    public function toggleActive(int $id, int $tenantId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE riders SET is_active = 1 - is_active
             WHERE id = :id AND tenant_id = :tid AND deleted_at IS NULL'
        );
        return $stmt->execute(['id' => $id, 'tid' => $tenantId]);
    }

    /**
     * Soft delete: nasconde definitivamente il rider dalla UI ma mantiene
     * la riga in DB cosi' lo storico ordini (FK orders.rider_id) continua
     * a poter fare JOIN e mostrare il nome del rider che ha consegnato.
     *
     * Vincolo: consentito solo su rider gia' archiviati (is_active=0)
     * per evitare eliminazioni accidentali di rider operativi.
     */
    public function softDelete(int $id, int $tenantId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE riders SET deleted_at = NOW()
             WHERE id = :id AND tenant_id = :tid
               AND is_active = 0 AND deleted_at IS NULL'
        );
        $stmt->execute(['id' => $id, 'tid' => $tenantId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Statistiche aggregate per ogni rider, in un range di date.
     * "Tempo medio consegna" = secondi tra rider_assigned_at e updated_at
     * sugli ordini status='completed' (l'updated_at riflette l'ultimo
     * cambio stato, che per i completed e' il passaggio a quello stato).
     *
     * @return array<int,array{rider:array, total:int, completed:int, cancelled:int, avg_minutes:?int, total_value:float}>
     */
    public function getStats(int $tenantId, string $dateFrom, string $dateTo): array
    {
        $sql = 'SELECT
                    r.id, r.name, r.color_hex, r.is_active,
                    COUNT(o.id) AS total,
                    SUM(CASE WHEN o.status = "completed" THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN o.status = "cancelled" THEN 1 ELSE 0 END) AS cancelled,
                    AVG(CASE WHEN o.status = "completed" AND o.rider_assigned_at IS NOT NULL
                        THEN TIMESTAMPDIFF(SECOND, o.rider_assigned_at, o.updated_at)
                        ELSE NULL END) AS avg_seconds,
                    COALESCE(SUM(CASE WHEN o.status = "completed" THEN o.total ELSE 0 END), 0) AS total_value
                FROM riders r
                LEFT JOIN orders o ON o.rider_id = r.id
                    AND o.tenant_id = :tid_o
                    AND DATE(o.created_at) BETWEEN :df AND :dt
                WHERE r.tenant_id = :tid_r AND r.deleted_at IS NULL
                GROUP BY r.id, r.name, r.color_hex, r.is_active
                ORDER BY total DESC, r.name ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'tid_o' => $tenantId,
            'tid_r' => $tenantId,
            'df'    => $dateFrom,
            'dt'    => $dateTo,
        ]);

        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['total']        = (int)$row['total'];
            $row['completed']    = (int)$row['completed'];
            $row['cancelled']    = (int)$row['cancelled'];
            $row['avg_minutes']  = $row['avg_seconds'] !== null ? (int)round($row['avg_seconds'] / 60) : null;
            $row['total_value']  = (float)$row['total_value'];
            unset($row['avg_seconds']);
        }
        return $rows;
    }

    /** Conta ordini gestiti dal rider nel mese corrente (per lista). */
    public function countOrdersThisMonth(int $riderId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM orders
             WHERE rider_id = :rid
               AND created_at >= DATE_FORMAT(CURDATE(), "%Y-%m-01")'
        );
        $stmt->execute(['rid' => $riderId]);
        return (int)$stmt->fetchColumn();
    }

    /** Sanitize del colore: deve essere nella palette consentita. */
    private function validColor(?string $color): string
    {
        if ($color && in_array($color, self::ALLOWED_COLORS, true)) {
            return $color;
        }
        return '#6c757d';
    }
}
