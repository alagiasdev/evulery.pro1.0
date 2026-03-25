<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class EmailCampaign
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM email_campaigns WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findByTenant(int $tenantId, int $limit = 15, int $offset = 0, bool $archived = false): array
    {
        $arc = $archived ? 1 : 0;
        $stmt = $this->db->prepare(
            "SELECT ec.*, u.first_name AS creator_first, u.last_name AS creator_last
             FROM email_campaigns ec
             LEFT JOIN users u ON u.id = ec.created_by
             WHERE ec.tenant_id = :tid AND ec.is_archived = {$arc}
             ORDER BY ec.created_at DESC
             LIMIT :lim OFFSET :off"
        );
        $stmt->bindValue('tid', $tenantId, PDO::PARAM_INT);
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue('off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countByTenant(int $tenantId, bool $archived = false): int
    {
        $arc = $archived ? 1 : 0;
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM email_campaigns WHERE tenant_id = :tid AND is_archived = {$arc}");
        $stmt->execute(['tid' => $tenantId]);
        return (int) $stmt->fetchColumn();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO email_campaigns (tenant_id, subject, body_text, segment_filter, inactive_days, created_by, created_at)
             VALUES (:tid, :subject, :body, :segment, :inactive_days, :created_by, NOW())'
        );
        $stmt->execute([
            'tid'           => $data['tenant_id'],
            'subject'       => $data['subject'],
            'body'          => $data['body_text'],
            'segment'       => $data['segment_filter'],
            'inactive_days' => $data['inactive_days'] ?? null,
            'created_by'    => $data['created_by'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status): void
    {
        $extra = '';
        if ($status === 'queued') {
            $extra = ', queued_at = NOW()';
        } elseif ($status === 'sent') {
            $extra = ', sent_at = NOW()';
        }

        $this->db->prepare("UPDATE email_campaigns SET status = :status{$extra} WHERE id = :id")
            ->execute(['status' => $status, 'id' => $id]);
    }

    public function updateCounts(int $id, int $sent, int $failed): void
    {
        $this->db->prepare(
            'UPDATE email_campaigns SET sent_count = :sent, failed_count = :failed WHERE id = :id'
        )->execute(['sent' => $sent, 'failed' => $failed, 'id' => $id]);
    }

    public function updateTotalRecipients(int $id, int $total, int $credits): void
    {
        $this->db->prepare(
            'UPDATE email_campaigns SET total_recipients = :total, credits_used = :credits WHERE id = :id'
        )->execute(['total' => $total, 'credits' => $credits, 'id' => $id]);
    }

    public function canSendToday(int $tenantId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM email_campaigns
             WHERE tenant_id = :tid AND status IN ('queued','sending','sent')
             AND DATE(COALESCE(queued_at, created_at)) = CURDATE()"
        );
        $stmt->execute(['tid' => $tenantId]);
        return (int) $stmt->fetchColumn() === 0;
    }

    public function findQueued(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM email_campaigns WHERE status = 'queued' ORDER BY queued_at ASC"
        );
        return $stmt->fetchAll();
    }

    public function delete(int $id): void
    {
        $this->db->prepare("DELETE FROM email_campaign_recipients WHERE campaign_id = :id")
            ->execute(['id' => $id]);
        $this->db->prepare("DELETE FROM email_campaigns WHERE id = :id AND status IN ('draft','queued')")
            ->execute(['id' => $id]);
    }

    /**
     * Count unsent (pending) recipients for a campaign.
     */
    public function countPendingRecipients(int $campaignId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM email_campaign_recipients WHERE campaign_id = :cid AND status = 'pending'"
        );
        $stmt->execute(['cid' => $campaignId]);
        return (int) $stmt->fetchColumn();
    }

    // --- Recipients ---

    public function insertRecipients(int $campaignId, array $recipients): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO email_campaign_recipients (campaign_id, customer_id, email, status)
             VALUES (:cid, :cust_id, :email, :status)'
        );

        foreach ($recipients as $r) {
            $stmt->execute([
                'cid'     => $campaignId,
                'cust_id' => $r['id'],
                'email'   => $r['email'],
                'status'  => 'pending',
            ]);
        }
    }

    public function getPendingRecipients(int $campaignId, int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM email_campaign_recipients
             WHERE campaign_id = :cid AND status = 'pending'
             LIMIT :lim"
        );
        $stmt->bindValue('cid', $campaignId, PDO::PARAM_INT);
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateRecipientStatus(int $recipientId, string $status): void
    {
        $sent = $status === 'sent' ? ', sent_at = NOW()' : '';
        $this->db->prepare("UPDATE email_campaign_recipients SET status = :status{$sent} WHERE id = :id")
            ->execute(['status' => $status, 'id' => $recipientId]);
    }

    public function archive(int $id): void
    {
        $this->db->prepare('UPDATE email_campaigns SET is_archived = 1 WHERE id = :id')
            ->execute(['id' => $id]);
    }

    // --- KPI ---

    public function getKpi(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(*) AS total_campaigns,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) AS sent_campaigns,
                SUM(CASE WHEN status = 'sent' THEN sent_count ELSE 0 END) AS total_sent
             FROM email_campaigns WHERE tenant_id = :tid"
        );
        $stmt->execute(['tid' => $tenantId]);
        return $stmt->fetch();
    }
}
