<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * CreditRechargeRequest — richieste di ricarica crediti email iniziate
 * dal reseller e approvate/rifiutate dal super admin.
 *
 * status: pending, approved, rejected
 */
class CreditRechargeRequest
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING  => 'In attesa',
        self::STATUS_APPROVED => 'Approvata',
        self::STATUS_REJECTED => 'Rifiutata',
    ];

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, t.name AS tenant_name, t.slug AS tenant_slug,
                    t.email_credits_balance,
                    u.first_name AS reseller_first_name, u.last_name AS reseller_last_name,
                    u.email AS reseller_email
             FROM credit_recharge_requests r
             LEFT JOIN tenants t ON t.id = r.tenant_id
             LEFT JOIN users u ON u.id = r.reseller_id
             WHERE r.id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function create(int $resellerId, int $tenantId, int $credits, ?string $notes = null): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO credit_recharge_requests
             (tenant_id, reseller_id, credits_requested, notes_reseller, status, created_at)
             VALUES (:tid, :rid, :cr, :nt, "pending", NOW())'
        );
        $stmt->execute([
            'tid' => $tenantId,
            'rid' => $resellerId,
            'cr'  => $credits,
            'nt'  => $notes,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Lista richieste di un reseller, ordinata desc.
     */
    public function listByReseller(int $resellerId, int $limit = 100): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, t.name AS tenant_name, t.slug AS tenant_slug,
                    t.email_credits_balance
             FROM credit_recharge_requests r
             LEFT JOIN tenants t ON t.id = r.tenant_id
             WHERE r.reseller_id = :rid
             ORDER BY r.created_at DESC
             LIMIT :lim"
        );
        $stmt->bindValue('rid', $resellerId, PDO::PARAM_INT);
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Lista per admin, opzionalmente filtrata per status.
     */
    public function listAll(?string $status = null, int $limit = 200): array
    {
        $where = '';
        $params = [];
        if ($status !== null && $status !== '') {
            $where = 'WHERE r.status = :status';
            $params['status'] = $status;
        }

        $sql = "SELECT r.*, t.name AS tenant_name, t.slug AS tenant_slug,
                       t.email_credits_balance,
                       u.first_name AS reseller_first_name, u.last_name AS reseller_last_name
                FROM credit_recharge_requests r
                LEFT JOIN tenants t ON t.id = r.tenant_id
                LEFT JOIN users u ON u.id = r.reseller_id
                {$where}
                ORDER BY r.created_at DESC
                LIMIT :lim";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countByStatus(?int $resellerId = null): array
    {
        $where = $resellerId !== null ? 'WHERE reseller_id = :rid' : '';
        $sql = "SELECT status, COUNT(*) AS cnt
                FROM credit_recharge_requests
                {$where}
                GROUP BY status";
        $stmt = $this->db->prepare($sql);
        if ($resellerId !== null) {
            $stmt->bindValue('rid', $resellerId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $out = array_fill_keys(array_keys(self::STATUSES), 0);
        foreach ($stmt->fetchAll() as $row) {
            $out[$row['status']] = (int)$row['cnt'];
        }
        return $out;
    }

    public function countPendingAdmin(): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM credit_recharge_requests WHERE status = 'pending'");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Somma totale crediti già approvati ed accreditati (lifetime).
     */
    public function sumApprovedCredits(int $resellerId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(credits_requested), 0)
             FROM credit_recharge_requests
             WHERE reseller_id = :rid AND status = 'approved'"
        );
        $stmt->execute(['rid' => $resellerId]);
        return (int)$stmt->fetchColumn();
    }

    public function approve(int $id, int $adminUserId, ?string $notes = null): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE credit_recharge_requests
             SET status = "approved", processed_by = :uid, processed_at = NOW(), notes_admin = :nt
             WHERE id = :id AND status = "pending"'
        );
        return $stmt->execute(['uid' => $adminUserId, 'nt' => $notes, 'id' => $id])
            && $stmt->rowCount() === 1;
    }

    public function reject(int $id, int $adminUserId, string $reason): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE credit_recharge_requests
             SET status = "rejected", processed_by = :uid, processed_at = NOW(), notes_admin = :nt
             WHERE id = :id AND status = "pending"'
        );
        return $stmt->execute(['uid' => $adminUserId, 'nt' => $reason, 'id' => $id])
            && $stmt->rowCount() === 1;
    }
}
