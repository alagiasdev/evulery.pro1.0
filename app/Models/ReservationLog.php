<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class ReservationLog
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(int $reservationId, ?string $oldStatus, string $newStatus, ?int $changedBy = null, ?string $note = null): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO reservation_logs (reservation_id, old_status, new_status, changed_by, note)
             VALUES (:reservation_id, :old_status, :new_status, :changed_by, :note)'
        );
        $stmt->execute([
            'reservation_id' => $reservationId,
            'old_status'     => $oldStatus,
            'new_status'     => $newStatus,
            'changed_by'     => $changedBy,
            'note'           => $note,
        ]);
    }

    public function findByReservation(int $reservationId): array
    {
        $stmt = $this->db->prepare(
            'SELECT rl.*, u.first_name, u.last_name
             FROM reservation_logs rl
             LEFT JOIN users u ON rl.changed_by = u.id
             WHERE rl.reservation_id = :reservation_id
             ORDER BY rl.created_at DESC'
        );
        $stmt->execute(['reservation_id' => $reservationId]);
        return $stmt->fetchAll();
    }
}
