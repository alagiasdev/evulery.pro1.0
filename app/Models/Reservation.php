<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Reservation
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM reservations WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findWithCustomer(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT r.*, c.first_name, c.last_name, c.email, c.phone, c.total_bookings, c.total_noshow, c.notes AS customer_notes_persistent
             FROM reservations r
             JOIN customers c ON r.customer_id = c.id
             WHERE r.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findByTenantAndDate(int $tenantId, string $date, ?string $status = null): array
    {
        $sql = 'SELECT r.*, c.first_name, c.last_name, c.email, c.phone, c.total_bookings
                FROM reservations r
                JOIN customers c ON r.customer_id = c.id
                WHERE r.tenant_id = :tenant_id AND r.reservation_date = :date';
        $params = ['tenant_id' => $tenantId, 'date' => $date];

        if ($status) {
            $sql .= ' AND r.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY r.reservation_time ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findForExport(int $tenantId, ?string $dateFrom, ?string $dateTo, ?string $status = null): array
    {
        $sql = 'SELECT r.reservation_date, r.reservation_time, c.first_name, c.last_name,
                       c.email, c.phone, r.party_size, r.status, r.source, r.customer_notes,
                       r.internal_notes, r.created_at
                FROM reservations r
                JOIN customers c ON r.customer_id = c.id
                WHERE r.tenant_id = :tenant_id';
        $params = ['tenant_id' => $tenantId];

        if ($dateFrom) {
            $sql .= ' AND r.reservation_date >= :date_from';
            $params['date_from'] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= ' AND r.reservation_date <= :date_to';
            $params['date_to'] = $dateTo;
        }
        if ($status) {
            $sql .= ' AND r.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY r.reservation_date ASC, r.reservation_time ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findByCustomer(int $customerId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM reservations WHERE customer_id = :customer_id ORDER BY reservation_date DESC, reservation_time DESC'
        );
        $stmt->execute(['customer_id' => $customerId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO reservations (tenant_id, customer_id, reservation_date, reservation_time, party_size, status, deposit_required, deposit_amount, source, customer_notes)
             VALUES (:tenant_id, :customer_id, :reservation_date, :reservation_time, :party_size, :status, :deposit_required, :deposit_amount, :source, :customer_notes)'
        );
        $stmt->execute([
            'tenant_id'        => $data['tenant_id'],
            'customer_id'      => $data['customer_id'],
            'reservation_date' => $data['reservation_date'],
            'reservation_time' => $data['reservation_time'],
            'party_size'       => $data['party_size'],
            'status'           => $data['status'] ?? 'pending',
            'deposit_required' => $data['deposit_required'] ?? 0,
            'deposit_amount'   => $data['deposit_amount'] ?? null,
            'source'           => $data['source'] ?? 'widget',
            'customer_notes'   => $data['customer_notes'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status): bool
    {
        $data = ['id' => $id, 'status' => $status];
        $sql = 'UPDATE reservations SET status = :status';

        if ($status === 'cancelled') {
            $sql .= ', cancelled_at = NOW()';
        }

        $sql .= ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function updateNotes(int $id, string $notes): bool
    {
        $stmt = $this->db->prepare('UPDATE reservations SET internal_notes = :notes WHERE id = :id');
        return $stmt->execute(['id' => $id, 'notes' => $notes]);
    }

    public function updateDetails(int $id, array $data): bool
    {
        $allowed = ['reservation_date', 'reservation_time', 'party_size', 'customer_notes'];
        $sets = [];
        $params = ['id' => $id];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if (empty($sets)) {
            return false;
        }

        $sql = 'UPDATE reservations SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function countBySlot(int $tenantId, string $date, string $timeStart, string $timeEnd): int
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(party_size), 0) as total
             FROM reservations
             WHERE tenant_id = :tenant_id
             AND reservation_date = :date
             AND reservation_time >= :time_start
             AND reservation_time < :time_end
             AND status IN ("confirmed", "pending")'
        );
        $stmt->execute([
            'tenant_id'  => $tenantId,
            'date'       => $date,
            'time_start' => $timeStart,
            'time_end'   => $timeEnd,
        ]);
        return (int)$stmt->fetch()['total'];
    }

    public function countTodayByTenant(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as total,
                    COALESCE(SUM(party_size), 0) as covers,
                    SUM(CASE WHEN status = "confirmed" THEN 1 ELSE 0 END) as confirmed,
                    SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = "arrived" THEN 1 ELSE 0 END) as arrived,
                    SUM(CASE WHEN status = "noshow" THEN 1 ELSE 0 END) as noshow,
                    SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled
             FROM reservations
             WHERE tenant_id = :tenant_id AND reservation_date = CURDATE()'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        return $stmt->fetch();
    }

    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM reservations WHERE id = :id')->execute(['id' => $id]);
    }

    public function getOccupiedCovers(int $tenantId, string $date, string $slotTime, int $tableDuration): int
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(party_size), 0) as occupied
             FROM reservations
             WHERE tenant_id = :tenant_id
             AND reservation_date = :date
             AND reservation_time <= :slot_time
             AND ADDTIME(reservation_time, SEC_TO_TIME(:duration * 60)) > :slot_time2
             AND status IN ("confirmed", "pending")'
        );
        $stmt->execute([
            'tenant_id' => $tenantId,
            'date'      => $date,
            'slot_time' => $slotTime,
            'duration'  => $tableDuration,
            'slot_time2' => $slotTime,
        ]);
        return (int)$stmt->fetch()['occupied'];
    }
}
