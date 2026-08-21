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

    // =========================================================================
    // "AL COMPLETO" — blocco del canale ONLINE per un giorno/servizio.
    //
    // Meccanismo volutamente DISGIUNTO dalle chiusure straordinarie: un blocco
    // al-completo e' un override per-slot con `max_covers = 0` e `is_closed = 0`.
    //   - AvailabilityService salta gli slot con max_covers <= 0 -> il widget
    //     mostra "pieno" (nessun orario), NON "chiuso" (che sarebbe is_closed).
    //   - getClosedDates() filtra is_closed=1 -> la data NON risulta chiusa nel
    //     calendario del widget (giorno operativo, non "chiuso").
    //   - le chiusure straordinarie usano is_closed=1: spazi WHERE disgiunti,
    //     nessuna collisione (una non cancella l'altra).
    // =========================================================================

    /** Nota-marcatore (documentazione in DB; il match avviene sulla struttura). */
    public const FULL_NOTE = 'Al completo';

    /**
     * Marca "al completo" un insieme di slot per una data. Idempotente per slot
     * (non duplica). $slotTimes: array di orari 'HH:MM' o 'HH:MM:SS'. Ritorna il
     * numero di slot effettivamente marcati (nuovi).
     */
    public function markFull(int $tenantId, string $date, array $slotTimes): int
    {
        $check = $this->db->prepare(
            'SELECT id FROM slot_overrides
             WHERE tenant_id = :t AND override_date = :d AND slot_time = :s
             AND is_closed = 0 AND max_covers = 0 LIMIT 1'
        );
        $insert = $this->db->prepare(
            'INSERT INTO slot_overrides (tenant_id, override_date, slot_time, max_covers, is_closed, note)
             VALUES (:t, :d, :s, 0, 0, :n)'
        );

        $count = 0;
        foreach ($slotTimes as $slotTime) {
            $slotTime = substr($slotTime, 0, 8); // normalizza a HH:MM(:SS)
            if ($slotTime === '') {
                continue;
            }
            $check->execute(['t' => $tenantId, 'd' => $date, 's' => $slotTime]);
            if ($check->fetch()) {
                continue; // gia' al completo
            }
            $insert->execute(['t' => $tenantId, 'd' => $date, 's' => $slotTime, 'n' => self::FULL_NOTE]);
            $count++;
        }
        return $count;
    }

    /**
     * Rimuove i blocchi "al completo" per una data. $slotTimes null = tutti gli
     * slot al-completo della data; altrimenti solo quelli indicati. NON tocca
     * le chiusure straordinarie (is_closed=1) ne' altri override. Ritorna il n.
     * di righe rimosse.
     */
    public function unmarkFull(int $tenantId, string $date, ?array $slotTimes = null): int
    {
        if ($slotTimes === null) {
            $stmt = $this->db->prepare(
                'DELETE FROM slot_overrides
                 WHERE tenant_id = :t AND override_date = :d
                 AND slot_time IS NOT NULL AND is_closed = 0 AND max_covers = 0'
            );
            $stmt->execute(['t' => $tenantId, 'd' => $date]);
            return $stmt->rowCount();
        }

        $times = array_values(array_filter(array_map(
            fn($s) => substr((string)$s, 0, 8),
            $slotTimes
        )));
        if (empty($times)) {
            return 0;
        }
        $in = implode(',', array_fill(0, count($times), '?'));
        $stmt = $this->db->prepare(
            "DELETE FROM slot_overrides
             WHERE tenant_id = ? AND override_date = ?
             AND is_closed = 0 AND max_covers = 0
             AND slot_time IN ($in)"
        );
        $stmt->execute(array_merge([$tenantId, $date], $times));
        return $stmt->rowCount();
    }

    /**
     * Gli orari (HH:MM:SS) marcati "al completo" per una data.
     */
    public function fullSlotTimes(int $tenantId, string $date): array
    {
        $stmt = $this->db->prepare(
            'SELECT slot_time FROM slot_overrides
             WHERE tenant_id = :t AND override_date = :d
             AND slot_time IS NOT NULL AND is_closed = 0 AND max_covers = 0'
        );
        $stmt->execute(['t' => $tenantId, 'd' => $date]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Le date FUTURE (oggi incluso) con prenotazioni online chiuse, con il numero
     * di orari bloccati per ciascuna. Per la pagina "Disponibilità online".
     * @return array<array{override_date:string, slots:int}>
     */
    public function fullDates(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT override_date, COUNT(*) AS slots
             FROM slot_overrides
             WHERE tenant_id = :t AND slot_time IS NOT NULL AND is_closed = 0 AND max_covers = 0
               AND override_date >= CURDATE()
             GROUP BY override_date
             ORDER BY override_date ASC'
        );
        $stmt->execute(['t' => $tenantId]);
        return $stmt->fetchAll();
    }
}