<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Tenant
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM tenants WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM tenants WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetch() ?: null;
    }

    public function findByDomain(string $domain): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM tenants WHERE custom_domain = :domain AND is_active = 1 LIMIT 1');
        $stmt->execute(['domain' => $domain]);
        return $stmt->fetch() ?: null;
    }

    public function all(): array
    {
        $stmt = $this->db->prepare('SELECT * FROM tenants ORDER BY created_at DESC');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function allPaginated(?string $search, int $limit, int $offset): array
    {
        $sql = 'SELECT t.*, p.name as plan_name, p.color as plan_color FROM tenants t LEFT JOIN plans p ON p.id = t.plan_id';
        $params = [];

        if ($search) {
            $sql .= ' WHERE (t.name LIKE :s1 OR t.slug LIKE :s2 OR t.email LIKE :s3)';
            $like = "%{$search}%";
            $params['s1'] = $like;
            $params['s2'] = $like;
            $params['s3'] = $like;
        }

        $sql .= ' ORDER BY t.created_at DESC LIMIT :lim OFFSET :off';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue('off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countFiltered(?string $search): int
    {
        $sql = 'SELECT COUNT(*) FROM tenants';
        $params = [];

        if ($search) {
            $sql .= ' WHERE (name LIKE :s1 OR slug LIKE :s2 OR email LIKE :s3)';
            $like = "%{$search}%";
            $params['s1'] = $like;
            $params['s2'] = $like;
            $params['s3'] = $like;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO tenants (slug, name, email, phone, address, plan, plan_id, plan_price, table_duration, time_step, is_active)
             VALUES (:slug, :name, :email, :phone, :address, :plan, :plan_id, :plan_price, :table_duration, :time_step, :is_active)'
        );
        $stmt->execute([
            'slug'           => $data['slug'],
            'name'           => $data['name'],
            'email'          => $data['email'],
            'phone'          => $data['phone'] ?? null,
            'address'        => $data['address'] ?? null,
            'plan'           => $data['plan'] ?? 'base',
            'plan_id'        => $data['plan_id'] ?? null,
            'plan_price'     => $data['plan_price'] ?? 49.00,
            'table_duration' => $data['table_duration'] ?? 90,
            'time_step'      => $data['time_step'] ?? 30,
            'is_active'      => $data['is_active'] ?? 0,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];
        $allowed = [
            'slug', 'name', 'email', 'phone', 'address', 'logo_url',
            'custom_domain', 'domain_status', 'cname_target',
            'plan', 'plan_id', 'plan_price', 'deposit_enabled', 'deposit_amount', 'deposit_mode',
            'cancellation_policy', 'booking_instructions', 'confirmation_mode',
            'table_duration', 'time_step',
            'booking_advance_min', 'booking_advance_max',
            'segment_occasionale', 'segment_abituale', 'segment_vip',
            'stripe_account_id', 'stripe_connect_status', 'stripe_connect_at',
            'deposit_type', 'deposit_bank_info', 'deposit_payment_link',
            'stripe_sk', 'stripe_pk', 'stripe_wh_secret',
            'timezone', 'menu_enabled', 'promo_widget_only',
            'notify_new_reservation', 'notify_cancellation',
            'notif_title_new_reservation', 'notif_body_new_reservation',
            'notif_title_cancellation', 'notif_body_cancellation',
            'notif_title_deposit', 'notif_body_deposit',
            'menu_hero_image', 'menu_tagline',
            'opening_hours', 'is_active',
        ];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`{$field}` = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = 'UPDATE tenants SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function toggleActive(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE tenants SET is_active = NOT is_active WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function count(): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM tenants');
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function countActive(): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM tenants WHERE is_active = 1');
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Check if a tenant's plan includes a specific service.
     * Results are cached per-request to avoid repeated queries.
     */
    private static array $serviceCache = [];

    public function canUseService(int $tenantId, string $serviceKey): bool
    {
        $services = $this->getTenantServiceKeys($tenantId);
        return in_array($serviceKey, $services, true);
    }

    /**
     * Get all service keys included in a tenant's plan.
     * Cached per-request.
     */
    public function getTenantServiceKeys(int $tenantId): array
    {
        if (isset(self::$serviceCache[$tenantId])) {
            return self::$serviceCache[$tenantId];
        }

        $stmt = $this->db->prepare(
            'SELECT s.`key`
             FROM services s
             JOIN plan_services ps ON ps.service_id = s.id
             JOIN tenants t ON t.plan_id = ps.plan_id
             WHERE t.id = :tid AND s.is_active = 1'
        );
        $stmt->execute(['tid' => $tenantId]);
        $keys = $stmt->fetchAll(PDO::FETCH_COLUMN);

        self::$serviceCache[$tenantId] = $keys;
        return $keys;
    }

    /**
     * Check if a tenant's subscription is expired.
     * Returns subscription data if expired, null if valid or no subscription.
     */
    public function getExpiredSubscription(int $tenantId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT s.*, p.name as plan_name
             FROM subscriptions s
             LEFT JOIN plans p ON p.id = s.plan_id
             WHERE s.tenant_id = :tid AND s.status IN ("active","trialing")
             ORDER BY s.current_period_end DESC LIMIT 1'
        );
        $stmt->execute(['tid' => $tenantId]);
        $sub = $stmt->fetch();

        if ($sub && $sub['current_period_end'] && strtotime($sub['current_period_end']) < time()) {
            return $sub;
        }

        return null;
    }

    // --- Email Credits ---

    public function addCredits(int $tenantId, int $amount): void
    {
        $this->db->prepare(
            'UPDATE tenants SET email_credits_balance = email_credits_balance + :amount WHERE id = :id'
        )->execute(['amount' => $amount, 'id' => $tenantId]);
    }

    public function deductCredits(int $tenantId, int $amount): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE tenants SET email_credits_balance = email_credits_balance - :amount
             WHERE id = :id AND email_credits_balance >= :check'
        );
        $stmt->execute(['amount' => $amount, 'id' => $tenantId, 'check' => $amount]);
        return $stmt->rowCount() > 0;
    }
}
