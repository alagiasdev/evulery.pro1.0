<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Order
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Genera il prossimo order_number atomico per il tenant: ORD-001, ORD-002, ...
     */
    public function getNextOrderNumber(int $tenantId): string
    {
        $stmt = $this->db->prepare(
            'SELECT MAX(CAST(SUBSTRING(order_number, 5) AS UNSIGNED)) as max_num
             FROM orders WHERE tenant_id = :tenant_id'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        $row = $stmt->fetch();
        $next = ($row['max_num'] ?? 0) + 1;
        return 'ORD-' . str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Crea un ordine. Ritorna l'ID.
     */
    public function create(int $tenantId, array $data): int
    {
        $orderNumber = $this->getNextOrderNumber($tenantId);

        $stmt = $this->db->prepare(
            'INSERT INTO orders (tenant_id, customer_id, order_number, order_type, status,
                                 pickup_time, subtotal, discount_amount, promo_id, total,
                                 payment_method, payment_status, stripe_session_id,
                                 customer_name, customer_phone, customer_email,
                                 delivery_address, delivery_cap, delivery_notes, delivery_fee,
                                 notes)
             VALUES (:tenant_id, :customer_id, :order_number, :order_type, :status,
                     :pickup_time, :subtotal, :discount_amount, :promo_id, :total,
                     :payment_method, :payment_status, :stripe_session_id,
                     :customer_name, :customer_phone, :customer_email,
                     :delivery_address, :delivery_cap, :delivery_notes, :delivery_fee,
                     :notes)'
        );
        $stmt->execute([
            'tenant_id'         => $tenantId,
            'customer_id'       => $data['customer_id'] ?? null,
            'order_number'      => $orderNumber,
            'order_type'        => $data['order_type'] ?? 'takeaway',
            'status'            => $data['status'] ?? 'pending',
            'pickup_time'       => $data['pickup_time'] ?? null,
            'subtotal'          => $data['subtotal'] ?? 0,
            'discount_amount'   => $data['discount_amount'] ?? 0,
            'promo_id'          => $data['promo_id'] ?? null,
            'total'             => $data['total'] ?? 0,
            'payment_method'    => $data['payment_method'] ?? 'cash',
            'payment_status'    => $data['payment_status'] ?? 'pending',
            'stripe_session_id' => $data['stripe_session_id'] ?? null,
            'customer_name'     => $data['customer_name'],
            'customer_phone'    => $data['customer_phone'],
            'customer_email'    => $data['customer_email'] ?? null,
            'delivery_address'  => $data['delivery_address'] ?? null,
            'delivery_cap'      => $data['delivery_cap'] ?? null,
            'delivery_notes'    => $data['delivery_notes'] ?? null,
            'delivery_fee'      => $data['delivery_fee'] ?? 0,
            'notes'             => $data['notes'] ?? null,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function findById(int $id, int $tenantId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM orders WHERE id = :id AND tenant_id = :tenant_id'
        );
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        return $stmt->fetch() ?: null;
    }

    public function findByOrderNumber(string $orderNumber, int $tenantId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM orders WHERE order_number = :order_number AND tenant_id = :tenant_id'
        );
        $stmt->execute(['order_number' => $orderNumber, 'tenant_id' => $tenantId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Ordini con filtri: status, date_from, date_to, search, order_type.
     * Paginazione: limit + offset.
     */
    public function findByTenant(int $tenantId, array $filters = []): array
    {
        $where = ['o.tenant_id = :tenant_id'];
        $params = ['tenant_id' => $tenantId];

        if (!empty($filters['status'])) {
            $where[] = 'o.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['order_type'])) {
            $where[] = 'o.order_type = :order_type';
            $params['order_type'] = $filters['order_type'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'o.created_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'o.created_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['search'])) {
            $where[] = '(o.customer_name LIKE :search OR o.order_number LIKE :search2 OR o.customer_phone LIKE :search3)';
            $params['search'] = '%' . $filters['search'] . '%';
            $params['search2'] = '%' . $filters['search'] . '%';
            $params['search3'] = '%' . $filters['search'] . '%';
        }

        $whereStr = implode(' AND ', $where);
        $limit = (int)($filters['limit'] ?? 50);
        $offset = (int)($filters['offset'] ?? 0);

        $stmt = $this->db->prepare(
            "SELECT o.* FROM orders o WHERE {$whereStr}
             ORDER BY o.created_at DESC LIMIT {$limit} OFFSET {$offset}"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Conta ordini per i filtri dati (per paginazione).
     */
    public function countByTenant(int $tenantId, array $filters = []): int
    {
        $where = ['o.tenant_id = :tenant_id'];
        $params = ['tenant_id' => $tenantId];

        if (!empty($filters['status'])) {
            $where[] = 'o.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['order_type'])) {
            $where[] = 'o.order_type = :order_type';
            $params['order_type'] = $filters['order_type'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'o.created_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'o.created_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['search'])) {
            $where[] = '(o.customer_name LIKE :search OR o.order_number LIKE :search2 OR o.customer_phone LIKE :search3)';
            $params['search'] = '%' . $filters['search'] . '%';
            $params['search2'] = '%' . $filters['search'] . '%';
            $params['search3'] = '%' . $filters['search'] . '%';
        }

        $whereStr = implode(' AND ', $where);
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM orders o WHERE {$whereStr}");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Ordini di oggi raggruppati per status (per kanban).
     */
    public function getKanbanData(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM orders
             WHERE tenant_id = :tenant_id
               AND DATE(created_at) = CURDATE()
               AND status NOT IN ('completed','cancelled','rejected')
             ORDER BY created_at ASC"
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        $rows = $stmt->fetchAll();

        $kanban = [
            'pending'   => [],
            'accepted'  => [],
            'preparing' => [],
            'ready'     => [],
        ];
        foreach ($rows as $row) {
            $kanban[$row['status']][] = $row;
        }
        return $kanban;
    }

    /**
     * Statistiche del giorno: totale ordini, incasso, per tipo.
     */
    public function getStats(int $tenantId, ?string $date = null): array
    {
        $date = $date ?? date('Y-m-d');
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(*) as total_orders,
                SUM(CASE WHEN status NOT IN ('cancelled','rejected') THEN total ELSE 0 END) as revenue,
                SUM(CASE WHEN order_type = 'takeaway' THEN 1 ELSE 0 END) as takeaway_count,
                SUM(CASE WHEN order_type = 'delivery' THEN 1 ELSE 0 END) as delivery_count,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN status IN ('cancelled','rejected') THEN 1 ELSE 0 END) as cancelled_count
             FROM orders
             WHERE tenant_id = :tenant_id AND DATE(created_at) = :date"
        );
        $stmt->execute(['tenant_id' => $tenantId, 'date' => $date]);
        $row = $stmt->fetch();
        return [
            'total_orders'    => (int)($row['total_orders'] ?? 0),
            'revenue'         => (float)($row['revenue'] ?? 0),
            'takeaway_count'  => (int)($row['takeaway_count'] ?? 0),
            'delivery_count'  => (int)($row['delivery_count'] ?? 0),
            'completed_count' => (int)($row['completed_count'] ?? 0),
            'cancelled_count' => (int)($row['cancelled_count'] ?? 0),
        ];
    }

    /**
     * Aggiorna lo stato di un ordine con validazione transizioni.
     */
    public function updateStatus(int $id, int $tenantId, string $newStatus, ?string $reason = null): bool
    {
        $order = $this->findById($id, $tenantId);
        if (!$order) {
            return false;
        }

        $valid = $this->getValidTransitions($order['status']);
        if (!in_array($newStatus, $valid, true)) {
            return false;
        }

        $sql = 'UPDATE orders SET status = :status, updated_at = NOW()';
        $params = ['id' => $id, 'tenant_id' => $tenantId, 'status' => $newStatus];

        if ($newStatus === 'rejected' && $reason !== null) {
            $sql .= ', rejected_reason = :reason';
            $params['reason'] = $reason;
        }

        $sql .= ' WHERE id = :id AND tenant_id = :tenant_id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Transizioni stato valide.
     */
    public function getValidTransitions(string $currentStatus): array
    {
        return match ($currentStatus) {
            'pending'   => ['accepted', 'rejected'],
            'accepted'  => ['preparing', 'cancelled'],
            'preparing' => ['ready', 'cancelled'],
            'ready'     => ['completed'],
            default     => [],
        };
    }

    public function findByStripeSession(string $sessionId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM orders WHERE stripe_session_id = :session_id'
        );
        $stmt->execute(['session_id' => $sessionId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Conta ordini in un certo slot (per capacity check).
     */
    public function countBySlot(int $tenantId, string $pickupTime): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM orders
             WHERE tenant_id = :tenant_id
               AND pickup_time = :pickup_time
               AND status NOT IN ('cancelled','rejected')"
        );
        $stmt->execute(['tenant_id' => $tenantId, 'pickup_time' => $pickupTime]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Aggiorna payment_status.
     */
    /**
     * Ordini delivery del giorno per la board fattorino.
     * Solo stati attivi: accepted, preparing, ready.
     */
    public function findDeliveryReady(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM orders
             WHERE tenant_id = :tenant_id
               AND order_type = 'delivery'
               AND status IN ('accepted','preparing','ready')
               AND DATE(created_at) = CURDATE()
             ORDER BY FIELD(status, 'ready', 'preparing', 'accepted'), pickup_time ASC"
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        return $stmt->fetchAll();
    }

    public function updatePaymentStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE orders SET payment_status = :status, updated_at = NOW() WHERE id = :id'
        );
        return $stmt->execute(['id' => $id, 'status' => $status]);
    }

    /**
     * Ordini completati oggi (per kanban completed count).
     */
    public function getCompletedToday(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM orders
             WHERE tenant_id = :tenant_id
               AND DATE(created_at) = CURDATE()
               AND status IN ('completed','cancelled','rejected')
             ORDER BY updated_at DESC
             LIMIT 20"
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        return $stmt->fetchAll();
    }
}
