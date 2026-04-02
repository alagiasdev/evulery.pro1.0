<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class ReviewRequest
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id, ?int $tenantId = null): ?array
    {
        $sql = 'SELECT rr.*, c.first_name, c.last_name, c.email, c.phone
                FROM review_requests rr
                LEFT JOIN customers c ON rr.customer_id = c.id
                WHERE rr.id = :id';
        $params = ['id' => $id];
        if ($tenantId !== null) {
            $sql .= ' AND rr.tenant_id = :tid';
            $params['tid'] = $tenantId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    public function findByToken(string $token): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT rr.*, t.slug, t.name AS tenant_name, t.logo_url, t.review_url,
                    t.review_platform_label, t.review_filter_enabled, t.review_filter_threshold,
                    t.review_filter_message, t.is_active AS tenant_active,
                    c.first_name, c.last_name, c.email AS customer_email,
                    res.reservation_date, res.reservation_time, res.party_size
             FROM review_requests rr
             JOIN tenants t ON rr.tenant_id = t.id
             LEFT JOIN customers c ON rr.customer_id = c.id
             LEFT JOIN reservations res ON rr.reservation_id = res.id
             WHERE rr.token = :token LIMIT 1'
        );
        $stmt->execute(['token' => $token]);
        return $stmt->fetch() ?: null;
    }

    public function findByTenant(int $tenantId, array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $sql = 'SELECT rr.*, c.first_name, c.last_name, c.email, c.phone, c.total_bookings,
                       res.reservation_date, res.reservation_time, res.party_size
                FROM review_requests rr
                LEFT JOIN customers c ON rr.customer_id = c.id
                LEFT JOIN reservations res ON rr.reservation_id = res.id
                WHERE rr.tenant_id = :tid';
        $params = ['tid' => $tenantId];

        $sql = $this->applyFilters($sql, $params, $filters);
        $sql .= ' ORDER BY rr.created_at DESC LIMIT :lim OFFSET :off';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue('off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countByTenant(int $tenantId, array $filters = []): int
    {
        $sql = 'SELECT COUNT(*) FROM review_requests rr WHERE rr.tenant_id = :tid';
        $params = ['tid' => $tenantId];
        $sql = $this->applyFilters($sql, $params, $filters);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    private function applyFilters(string $sql, array &$params, array $filters): string
    {
        if (!empty($filters['feedback_status'])) {
            $sql .= ' AND rr.feedback_status = :fst';
            $params['fst'] = $filters['feedback_status'];
        }
        if (!empty($filters['has_feedback'])) {
            $sql .= ' AND rr.feedback_text IS NOT NULL';
        }
        if (!empty($filters['source'])) {
            $sql .= ' AND rr.source = :src';
            $params['src'] = $filters['source'];
        }
        if (!empty($filters['rating_min'])) {
            $sql .= ' AND rr.rating >= :rmin';
            $params['rmin'] = (int) $filters['rating_min'];
        }
        if (!empty($filters['rating_max'])) {
            $sql .= ' AND rr.rating <= :rmax';
            $params['rmax'] = (int) $filters['rating_max'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= ' AND rr.created_at >= :dfrom';
            $params['dfrom'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $sql .= ' AND rr.created_at <= :dto';
            $params['dto'] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['search'])) {
            $sql .= ' AND (c.first_name LIKE :srch OR c.last_name LIKE :srch OR c.email LIKE :srch)';
            $params['srch'] = '%' . $filters['search'] . '%';
        }
        return $sql;
    }

    public function getStats(int $tenantId, ?string $from = null, ?string $to = null): array
    {
        $where = 'WHERE rr.tenant_id = :tid';
        $params = ['tid' => $tenantId];
        if ($from) {
            $where .= ' AND rr.created_at >= :from';
            $params['from'] = $from . ' 00:00:00';
        }
        if ($to) {
            $where .= ' AND rr.created_at <= :to';
            $params['to'] = $to . ' 23:59:59';
        }

        $stmt = $this->db->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN rr.source = 'email' THEN 1 ELSE 0 END) AS total_email,
                SUM(CASE WHEN rr.source != 'email' THEN 1 ELSE 0 END) AS total_anonymous,
                SUM(CASE WHEN rr.sent_at IS NOT NULL THEN 1 ELSE 0 END) AS total_sent,
                SUM(CASE WHEN rr.opened_at IS NOT NULL THEN 1 ELSE 0 END) AS total_opened,
                SUM(CASE WHEN rr.clicked_at IS NOT NULL THEN 1 ELSE 0 END) AS total_clicked,
                SUM(CASE WHEN rr.rating IS NOT NULL THEN 1 ELSE 0 END) AS total_rated,
                AVG(rr.rating) AS avg_rating,
                SUM(CASE WHEN rr.feedback_text IS NOT NULL THEN 1 ELSE 0 END) AS total_feedback,
                SUM(CASE WHEN rr.feedback_status = 'new' THEN 1 ELSE 0 END) AS feedback_new,
                SUM(CASE WHEN rr.rating >= 4 AND rr.clicked_at IS NOT NULL THEN 1 ELSE 0 END) AS total_redirected
             FROM review_requests rr
             $where"
        );
        $stmt->execute($params);
        $row = $stmt->fetch();

        return [
            'total'            => (int) ($row['total'] ?? 0),
            'total_email'      => (int) ($row['total_email'] ?? 0),
            'total_anonymous'  => (int) ($row['total_anonymous'] ?? 0),
            'total_sent'       => (int) ($row['total_sent'] ?? 0),
            'total_opened'     => (int) ($row['total_opened'] ?? 0),
            'total_clicked'    => (int) ($row['total_clicked'] ?? 0),
            'total_rated'      => (int) ($row['total_rated'] ?? 0),
            'avg_rating'       => $row['avg_rating'] ? round((float) $row['avg_rating'], 1) : null,
            'total_feedback'   => (int) ($row['total_feedback'] ?? 0),
            'feedback_new'     => (int) ($row['feedback_new'] ?? 0),
            'total_redirected' => (int) ($row['total_redirected'] ?? 0),
            'open_rate'        => ($row['total_sent'] ?? 0) > 0 ? round(($row['total_opened'] / $row['total_sent']) * 100) : 0,
            'click_rate'       => ($row['total_sent'] ?? 0) > 0 ? round(($row['total_clicked'] / $row['total_sent']) * 100) : 0,
        ];
    }

    public function getRatingDistribution(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT rating, COUNT(*) AS cnt
             FROM review_requests
             WHERE tenant_id = :tid AND rating IS NOT NULL
             GROUP BY rating ORDER BY rating DESC'
        );
        $stmt->execute(['tid' => $tenantId]);
        $dist = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        foreach ($stmt->fetchAll() as $row) {
            $dist[(int) $row['rating']] = (int) $row['cnt'];
        }
        return $dist;
    }

    public function getHistory(int $tenantId, int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT rr.*, c.first_name, c.last_name, c.email,
                    res.reservation_date, res.reservation_time, res.party_size
             FROM review_requests rr
             LEFT JOIN customers c ON rr.customer_id = c.id
             LEFT JOIN reservations res ON rr.reservation_id = res.id
             WHERE rr.tenant_id = :tid
             ORDER BY rr.created_at DESC
             LIMIT :lim OFFSET :off'
        );
        $stmt->bindValue('tid', $tenantId, PDO::PARAM_INT);
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue('off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        if (empty($data['token'])) {
            $data['token'] = bin2hex(random_bytes(32));
        }
        $cols = ['tenant_id', 'reservation_id', 'customer_id', 'token', 'source', 'sent_at'];
        $fields = [];
        $params = [];
        foreach ($cols as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = $col;
                $params[$col] = $data[$col];
            }
        }
        $colStr = implode(', ', $fields);
        $valStr = implode(', ', array_map(fn($f) => ':' . $f, $fields));

        $stmt = $this->db->prepare("INSERT INTO review_requests ($colStr) VALUES ($valStr)");
        $stmt->execute($params);
        return (int) $this->db->lastInsertId();
    }

    public function markOpened(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE review_requests SET opened_at = NOW() WHERE id = :id AND opened_at IS NULL'
        );
        $stmt->execute(['id' => $id]);
    }

    public function markClicked(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE review_requests SET clicked_at = NOW() WHERE id = :id AND clicked_at IS NULL'
        );
        $stmt->execute(['id' => $id]);
    }

    public function saveRating(int $id, int $rating): void
    {
        $stmt = $this->db->prepare(
            'UPDATE review_requests SET rating = :rating WHERE id = :id'
        );
        $stmt->execute(['id' => $id, 'rating' => $rating]);
    }

    public function saveFeedbackText(int $id, string $text): void
    {
        $stmt = $this->db->prepare(
            "UPDATE review_requests SET feedback_text = :text, feedback_status = 'new' WHERE id = :id"
        );
        $stmt->execute(['id' => $id, 'text' => $text]);
    }

    public function saveFeedbackReply(int $id, string $reply): void
    {
        $stmt = $this->db->prepare(
            "UPDATE review_requests SET feedback_reply = :reply, feedback_status = 'replied' WHERE id = :id"
        );
        $stmt->execute(['id' => $id, 'reply' => $reply]);
    }

    public function updateFeedbackStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare(
            'UPDATE review_requests SET feedback_status = :st WHERE id = :id'
        );
        $stmt->execute(['id' => $id, 'st' => $status]);
    }

    public function existsForReservation(int $reservationId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM review_requests WHERE reservation_id = :rid LIMIT 1'
        );
        $stmt->execute(['rid' => $reservationId]);
        return (bool) $stmt->fetchColumn();
    }

    public function countRecentByCustomer(int $customerId, int $tenantId, int $days = 30): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM review_requests
             WHERE customer_id = :cid AND tenant_id = :tid AND source = 'email'
             AND created_at > DATE_SUB(NOW(), INTERVAL :days DAY)"
        );
        $stmt->bindValue('cid', $customerId, PDO::PARAM_INT);
        $stmt->bindValue('tid', $tenantId, PDO::PARAM_INT);
        $stmt->bindValue('days', $days, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function getMonthlyStats(int $tenantId, int $months = 4): array
    {
        $stmt = $this->db->prepare(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month,
                    COUNT(*) AS sent,
                    SUM(CASE WHEN clicked_at IS NOT NULL THEN 1 ELSE 0 END) AS clicked,
                    SUM(CASE WHEN rating >= 4 AND clicked_at IS NOT NULL THEN 1 ELSE 0 END) AS redirected
             FROM review_requests
             WHERE tenant_id = :tid AND created_at >= DATE_SUB(NOW(), INTERVAL :m MONTH)
             GROUP BY month ORDER BY month"
        );
        $stmt->bindValue('tid', $tenantId, PDO::PARAM_INT);
        $stmt->bindValue('m', $months, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
