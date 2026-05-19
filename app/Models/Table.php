<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Tavoli del ristorante (tabella `restaurant_tables`) + combinazioni.
 * La priorità è globale per tenant: l'auto-assegnazione scorre i tavoli
 * in ordine di `priority` crescente.
 */
class Table
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** Tutti i tavoli del tenant, ordinati per priorità. */
    public function findByTenant(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM restaurant_tables WHERE tenant_id = :t ORDER BY priority ASC, id ASC'
        );
        $stmt->execute(['t' => $tenantId]);
        return $stmt->fetchAll();
    }

    /** Solo i tavoli attivi, in ordine di priorità (per l'auto-assegnazione). */
    public function findActiveByTenant(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM restaurant_tables
             WHERE tenant_id = :t AND is_active = 1
             ORDER BY priority ASC, id ASC'
        );
        $stmt->execute(['t' => $tenantId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM restaurant_tables WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Elenco delle aree distinte usate dal tenant, ordinate per creazione
     * (MIN(id) del primo tavolo): la prima è l'area "principale".
     */
    public function areas(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            "SELECT area FROM restaurant_tables
             WHERE tenant_id = :t AND area IS NOT NULL AND area <> ''
             GROUP BY area ORDER BY MIN(id) ASC"
        );
        $stmt->execute(['t' => $tenantId]);
        return array_column($stmt->fetchAll(), 'area');
    }

    /** Crea un tavolo; la priorità è assegnata in coda (max+1). */
    public function create(int $tenantId, array $data): int
    {
        $maxPrio = (int)$this->db->query(
            'SELECT COALESCE(MAX(priority), -1) FROM restaurant_tables WHERE tenant_id = ' . (int)$tenantId
        )->fetchColumn();

        $stmt = $this->db->prepare(
            'INSERT INTO restaurant_tables
                (tenant_id, name, capacity, priority, area, shape, internal_note, is_active)
             VALUES (:t, :name, :cap, :prio, :area, :shape, :note, :active)'
        );
        $stmt->execute([
            't'      => $tenantId,
            'name'   => $data['name'],
            'cap'    => (int)$data['capacity'],
            'prio'   => $maxPrio + 1,
            'area'   => $data['area'] !== '' ? $data['area'] : null,
            'shape'  => in_array($data['shape'] ?? 'square', ['square', 'round'], true) ? $data['shape'] : 'square',
            'note'   => $data['internal_note'] !== '' ? $data['internal_note'] : null,
            'active' => !empty($data['is_active']) ? 1 : 0,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, int $tenantId, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE restaurant_tables
             SET name = :name, capacity = :cap, area = :area, shape = :shape,
                 internal_note = :note, is_active = :active
             WHERE id = :id AND tenant_id = :t'
        );
        return $stmt->execute([
            'name'   => $data['name'],
            'cap'    => (int)$data['capacity'],
            'area'   => $data['area'] !== '' ? $data['area'] : null,
            'shape'  => in_array($data['shape'] ?? 'square', ['square', 'round'], true) ? $data['shape'] : 'square',
            'note'   => $data['internal_note'] !== '' ? $data['internal_note'] : null,
            'active' => !empty($data['is_active']) ? 1 : 0,
            'id'     => $id,
            't'      => $tenantId,
        ]);
    }

    public function delete(int $id, int $tenantId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM restaurant_tables WHERE id = :id AND tenant_id = :t');
        return $stmt->execute(['id' => $id, 't' => $tenantId]);
    }

    public function toggleActive(int $id, int $tenantId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE restaurant_tables SET is_active = 1 - is_active WHERE id = :id AND tenant_id = :t'
        );
        return $stmt->execute(['id' => $id, 't' => $tenantId]);
    }

    /** Riordina la priorità in base alla sequenza di id ricevuta. */
    public function reorder(int $tenantId, array $orderedIds): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'UPDATE restaurant_tables SET priority = :p WHERE id = :id AND tenant_id = :t'
            );
            $p = 0;
            foreach ($orderedIds as $id) {
                $id = (int)$id;
                if ($id <= 0) continue;
                $stmt->execute(['p' => $p, 'id' => $id, 't' => $tenantId]);
                $p++;
            }
            $this->db->commit();
        } catch (\PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Aggiorna le posizioni sulla mappa sala.
     * $positions: [tableId => ['x' => int, 'y' => int], ...].
     */
    public function updatePositions(int $tenantId, array $positions): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'UPDATE restaurant_tables SET position_x = :x, position_y = :y
                 WHERE id = :id AND tenant_id = :t'
            );
            foreach ($positions as $id => $pos) {
                $stmt->execute([
                    'x'  => (int)$pos['x'],
                    'y'  => (int)$pos['y'],
                    'id' => (int)$id,
                    't'  => $tenantId,
                ]);
            }
            $this->db->commit();
        } catch (\PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ─── Combinazioni ────────────────────────────────────────────

    /** Tutte le coppie combinabili del tenant: [['table_a_id'=>..,'table_b_id'=>..], ...]. */
    public function allCombinations(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT table_a_id, table_b_id FROM table_combinations WHERE tenant_id = :t'
        );
        $stmt->execute(['t' => $tenantId]);
        return $stmt->fetchAll();
    }

    /** Id dei tavoli combinabili con $tableId. */
    public function combinableWith(int $tableId): array
    {
        $stmt = $this->db->prepare(
            'SELECT table_a_id, table_b_id FROM table_combinations
             WHERE table_a_id = :id OR table_b_id = :id2'
        );
        $stmt->execute(['id' => $tableId, 'id2' => $tableId]);
        $ids = [];
        foreach ($stmt->fetchAll() as $row) {
            $ids[] = (int)$row['table_a_id'] === $tableId
                ? (int)$row['table_b_id']
                : (int)$row['table_a_id'];
        }
        return $ids;
    }

    /**
     * Sincronizza le combinazioni di un tavolo: rimuove tutte le coppie che lo
     * coinvolgono e reinserisce quelle verso gli id selezionati. Coppie sempre
     * normalizzate (a < b) per rispettare l'unique.
     */
    public function setCombinations(int $tenantId, int $tableId, array $otherIds): void
    {
        $this->db->beginTransaction();
        try {
            $del = $this->db->prepare(
                'DELETE FROM table_combinations
                 WHERE tenant_id = :t AND (table_a_id = :id OR table_b_id = :id2)'
            );
            $del->execute(['t' => $tenantId, 'id' => $tableId, 'id2' => $tableId]);

            $ins = $this->db->prepare(
                'INSERT IGNORE INTO table_combinations (tenant_id, table_a_id, table_b_id)
                 VALUES (:t, :a, :b)'
            );
            foreach (array_unique(array_map('intval', $otherIds)) as $other) {
                if ($other <= 0 || $other === $tableId) continue;
                $ins->execute([
                    't' => $tenantId,
                    'a' => min($tableId, $other),
                    'b' => max($tableId, $other),
                ]);
            }
            $this->db->commit();
        } catch (\PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
