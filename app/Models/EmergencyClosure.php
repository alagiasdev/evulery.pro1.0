<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Evento "Chiusura straordinaria" (emergenze). Tiene il periodo, l'ambito, la
 * modalita' (cancel/suspend), il messaggio e lo stato. blocked_override_ids
 * memorizza gli slot_overrides creati per il blocco, cosi' la riapertura
 * rimuove esattamente quelli. La tabella items conserva lo stato precedente di
 * ogni prenotazione, per il ripristino alla riapertura.
 */
class EmergencyClosure
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO emergency_closures
                (tenant_id, date_from, date_to, time_from, time_to, scope_label,
                 mode, message, status, blocked_override_ids, affected_count, created_by)
             VALUES
                (:tenant_id, :date_from, :date_to, :time_from, :time_to, :scope_label,
                 :mode, :message, "active", :blocked, :affected, :created_by)'
        );
        $stmt->execute([
            'tenant_id'   => $data['tenant_id'],
            'date_from'   => $data['date_from'],
            'date_to'     => $data['date_to'],
            'time_from'   => $data['time_from'] ?? null,
            'time_to'     => $data['time_to'] ?? null,
            'scope_label' => $data['scope_label'] ?? 'Giorno intero',
            'mode'        => $data['mode'],
            'message'     => $data['message'] ?? null,
            'blocked'     => isset($data['blocked_override_ids']) ? json_encode($data['blocked_override_ids']) : null,
            'affected'    => (int)($data['affected_count'] ?? 0),
            'created_by'  => $data['created_by'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM emergency_closures WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * La chiusura straordinaria attiva del tenant (per il banner). Al massimo
     * una significativa: prende la piu' recente ancora 'active'.
     */
    public function findActiveByTenant(int $tenantId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM emergency_closures
             WHERE tenant_id = :t AND status = "active"
             ORDER BY created_at DESC LIMIT 1'
        );
        $stmt->execute(['t' => $tenantId]);
        return $stmt->fetch() ?: null;
    }

    public function updateAffectedCount(int $id, int $count): void
    {
        $this->db->prepare('UPDATE emergency_closures SET affected_count = :c WHERE id = :id')
                 ->execute(['c' => $count, 'id' => $id]);
    }

    public function markResolved(int $id, string $resolution): void
    {
        $this->db->prepare(
            'UPDATE emergency_closures
             SET status = "resolved", resolution = :r, resolved_at = NOW()
             WHERE id = :id'
        )->execute(['r' => $resolution, 'id' => $id]);
    }

    public function blockedOverrideIds(array $closure): array
    {
        if (empty($closure['blocked_override_ids'])) {
            return [];
        }
        $ids = json_decode($closure['blocked_override_ids'], true);
        return is_array($ids) ? array_map('intval', $ids) : [];
    }

    // ---- items (snapshot stato precedente per il ripristino) ----

    public function addItem(int $closureId, int $reservationId, string $previousStatus): void
    {
        $this->db->prepare(
            'INSERT INTO emergency_closure_items (closure_id, reservation_id, previous_status)
             VALUES (:c, :r, :p)'
        )->execute(['c' => $closureId, 'r' => $reservationId, 'p' => $previousStatus]);
    }

    /**
     * Items della chiusura con i dati prenotazione+cliente (per la riapertura:
     * decidere futuro vs passato e inviare le email).
     */
    public function itemsWithReservations(int $closureId): array
    {
        $stmt = $this->db->prepare(
            'SELECT eci.previous_status,
                    r.*, c.first_name, c.last_name, c.email, c.phone
             FROM emergency_closure_items eci
             JOIN reservations r ON eci.reservation_id = r.id
             JOIN customers c    ON r.customer_id = c.id
             WHERE eci.closure_id = :c
             ORDER BY r.reservation_date ASC, r.reservation_time ASC'
        );
        $stmt->execute(['c' => $closureId]);
        return $stmt->fetchAll();
    }
}
