<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class SlotOverride
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all closures (full-day overrides) for a tenant, ordered by date.
     */
    public function findClosuresByTenant(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM slot_overrides
             WHERE tenant_id = :tenant_id AND slot_time IS NULL AND is_closed = 1
             ORDER BY override_date ASC'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        return $stmt->fetchAll();
    }

    /**
     * Get upcoming closures (today or future).
     */
    public function findUpcomingClosures(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM slot_overrides
             WHERE tenant_id = :tenant_id AND slot_time IS NULL AND is_closed = 1
             AND override_date >= CURDATE()
             ORDER BY override_date ASC'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        return $stmt->fetchAll();
    }

    /**
     * Get closed dates as a simple array for a date range (for widget calendar).
     */
    public function getClosedDates(int $tenantId, string $from, string $to): array
    {
        $stmt = $this->db->prepare(
            'SELECT override_date FROM slot_overrides
             WHERE tenant_id = :tenant_id AND slot_time IS NULL AND is_closed = 1
             AND override_date BETWEEN :from AND :to
             ORDER BY override_date ASC'
        );
        $stmt->execute(['tenant_id' => $tenantId, 'from' => $from, 'to' => $to]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Add a full-day closure. Skips if already exists.
     */
    public function addClosure(int $tenantId, string $date, ?string $note = null): bool
    {
        // Check if already exists
        $stmt = $this->db->prepare(
            'SELECT id FROM slot_overrides
             WHERE tenant_id = :tenant_id AND override_date = :date AND slot_time IS NULL AND is_closed = 1
             LIMIT 1'
        );
        $stmt->execute(['tenant_id' => $tenantId, 'date' => $date]);
        if ($stmt->fetch()) {
            return false; // already exists
        }

        $stmt = $this->db->prepare(
            'INSERT INTO slot_overrides (tenant_id, override_date, slot_time, max_covers, is_closed, note)
             VALUES (:tenant_id, :date, NULL, NULL, 1, :note)'
        );
        $stmt->execute(['tenant_id' => $tenantId, 'date' => $date, 'note' => $note]);
        return true;
    }

    /**
     * Add closures for a date range (inclusive).
     */
    public function addClosureRange(int $tenantId, string $from, string $to, ?string $note = null): int
    {
        $start = new \DateTime($from);
        $end = new \DateTime($to);
        $count = 0;

        while ($start <= $end) {
            if ($this->addClosure($tenantId, $start->format('Y-m-d'), $note)) {
                $count++;
            }
            $start->modify('+1 day');
        }

        return $count;
    }

    /**
     * Delete a closure by ID (only full-day closures).
     */
    public function deleteClosure(int $id, int $tenantId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM slot_overrides
             WHERE id = :id AND tenant_id = :tenant_id AND slot_time IS NULL AND is_closed = 1'
        );
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Delete all closures in a date range.
     */
    public function deleteClosureRange(int $tenantId, string $from, string $to): int
    {
        $stmt = $this->db->prepare(
            'DELETE FROM slot_overrides
             WHERE tenant_id = :tenant_id AND slot_time IS NULL AND is_closed = 1
             AND override_date BETWEEN :from AND :to'
        );
        $stmt->execute(['tenant_id' => $tenantId, 'from' => $from, 'to' => $to]);
        return $stmt->rowCount();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM slot_overrides WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Blocco "giorno intero" che RESTITUISCE l'id (riusato dalla chiusura
     * straordinaria, che deve poi poter rimuovere esattamente cio' che ha
     * creato). Se esiste gia' una chiusura full-day per quella data ne ritorna
     * l'id senza duplicare. Diverso da addClosure() che ritorna solo bool.
     */
    public function blockFullDay(int $tenantId, string $date, ?string $note = null): int
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM slot_overrides
             WHERE tenant_id = :t AND override_date = :d AND slot_time IS NULL AND is_closed = 1 LIMIT 1'
        );
        $stmt->execute(['t' => $tenantId, 'd' => $date]);
        $existing = $stmt->fetchColumn();
        if ($existing) {
            return (int)$existing;
        }
        $stmt = $this->db->prepare(
            'INSERT INTO slot_overrides (tenant_id, override_date, slot_time, max_covers, is_closed, note)
             VALUES (:t, :d, NULL, NULL, 1, :n)'
        );
        $stmt->execute(['t' => $tenantId, 'd' => $date, 'n' => $note]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Blocco di un singolo slot orario (per chiusure parziali: solo cena, solo
     * pranzo, fascia). Ritorna l'id creato/esistente.
     */
    public function blockSlot(int $tenantId, string $date, string $slotTime, ?string $note = null): int
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM slot_overrides
             WHERE tenant_id = :t AND override_date = :d AND slot_time = :s AND is_closed = 1 LIMIT 1'
        );
        $stmt->execute(['t' => $tenantId, 'd' => $date, 's' => $slotTime]);
        $existing = $stmt->fetchColumn();
        if ($existing) {
            return (int)$existing;
        }
        $stmt = $this->db->prepare(
            'INSERT INTO slot_overrides (tenant_id, override_date, slot_time, max_covers, is_closed, note)
             VALUES (:t, :d, :s, NULL, 1, :n)'
        );
        $stmt->execute(['t' => $tenantId, 'd' => $date, 's' => $slotTime, 'n' => $note]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Rimuove gli override per id (riapertura chiusura straordinaria). Il check
     * sul tenant evita di toccare righe di altri ristoranti.
     */
    public function deleteByIds(array $ids, int $tenantId): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (empty($ids)) {
            return 0;
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare(
            "DELETE FROM slot_overrides WHERE tenant_id = ? AND id IN ($in)"
        );
        $stmt->execute(array_merge([$tenantId], $ids));
        return $stmt->rowCount();
    }
}