<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Plan
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all(): array
    {
        return $this->db->query(
            'SELECT p.*, (SELECT COUNT(*) FROM subscriptions s WHERE s.plan_id = p.id AND s.status = "active") as active_count
             FROM plans p ORDER BY p.sort_order ASC'
        )->fetchAll();
    }

    public function allActive(): array
    {
        return $this->db->query(
            'SELECT * FROM plans WHERE is_active = 1 ORDER BY sort_order ASC'
        )->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM plans WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM plans WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $maxSort = (int)$this->db->query('SELECT COALESCE(MAX(sort_order), 0) FROM plans')->fetchColumn();

        $stmt = $this->db->prepare(
            'INSERT INTO plans (name, slug, description, price, price_semiannual, price_annual,
                                billing_months_semi, billing_months_annual,
                                color, is_active, sort_order)
             VALUES (:name, :slug, :description, :price, :price_semiannual, :price_annual,
                     :billing_months_semi, :billing_months_annual,
                     :color, :is_active, :sort_order)'
        );
        $stmt->execute([
            'name'                  => $data['name'],
            'slug'                  => $data['slug'],
            'description'           => $data['description'] ?? null,
            'price'                 => $data['price'],
            'price_semiannual'      => $data['price_semiannual'] ?? null,
            'price_annual'          => $data['price_annual'] ?? null,
            'billing_months_semi'   => $data['billing_months_semi'] ?? 5,
            'billing_months_annual' => $data['billing_months_annual'] ?? 10,
            'color'                 => $data['color'] ?? '#1565C0',
            'is_active'             => $data['is_active'] ?? 1,
            'sort_order'            => $maxSort + 1,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];
        $allowed = ['name', 'slug', 'description', 'price', 'price_semiannual', 'price_annual',
                    'billing_months_semi', 'billing_months_annual', 'color', 'is_active', 'sort_order'];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`{$field}` = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = 'UPDATE plans SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM plans WHERE id = :id AND is_default = 0');
        return $stmt->execute(['id' => $id]);
    }

    public function hasSubscriptions(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM subscriptions WHERE plan_id = :id');
        $stmt->execute(['id' => $id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Calcola il prezzo totale per un ciclo di fatturazione.
     *
     * Priorita' di calcolo:
     * 1. Se il piano ha `price_semiannual`/`price_annual` valorizzato (cifra
     *    tonda decisa dal listino, es. €729/€1290 Enterprise) -> usa quella
     *    cifra come subtotal, ignorando billing_months_*.
     * 2. Altrimenti calcola subtotal = monthly_price * billing_months_*
     *    (modello legacy basato su "mesi pagati su X").
     * 3. Sull'subtotal si applica eventualmente l'extra_discount admin.
     *
     * @param array  $plan          Piano (con price, price_semiannual, price_annual, billing_months_*)
     * @param string $cycle         'monthly' | 'semiannual' | 'annual'
     * @param float  $extraDiscount Sconto extra % (0-100)
     * @return array {total: prezzo totale ciclo, monthly: equivalente mensile, months_paid: mesi pagati, months_cycle: mesi ciclo}
     */
    public static function calculatePrice(array $plan, string $cycle = 'annual', float $extraDiscount = 0): array
    {
        $monthlyPrice = (float)$plan['price'];

        if ($cycle === 'monthly') {
            $monthsCycle = 1;
            $monthsPaid  = 1;
            $subtotal    = $monthlyPrice;
        } elseif ($cycle === 'semiannual') {
            $monthsCycle = 6;
            // Se price_semiannual e' valorizzato, usa il prezzo diretto.
            // months_paid e' calcolato per back-compat (es. UI che lo mostra).
            if (!empty($plan['price_semiannual']) && (float)$plan['price_semiannual'] > 0) {
                $subtotal   = (float)$plan['price_semiannual'];
                $monthsPaid = $monthlyPrice > 0 ? round($subtotal / $monthlyPrice, 2) : 6;
            } else {
                $monthsPaid = max(1, min(6, (int)($plan['billing_months_semi'] ?? 5)));
                $subtotal   = $monthlyPrice * $monthsPaid;
            }
        } else { // annual
            $monthsCycle = 12;
            if (!empty($plan['price_annual']) && (float)$plan['price_annual'] > 0) {
                $subtotal   = (float)$plan['price_annual'];
                $monthsPaid = $monthlyPrice > 0 ? round($subtotal / $monthlyPrice, 2) : 12;
            } else {
                $monthsPaid = max(1, min(12, (int)($plan['billing_months_annual'] ?? 10)));
                $subtotal   = $monthlyPrice * $monthsPaid;
            }
        }

        // Sconto extra admin
        $extraDiscount = max(0, min(100, $extraDiscount));
        $total = $subtotal * (1 - $extraDiscount / 100);

        return [
            'total'        => round($total, 2),
            'monthly'      => round($total / $monthsCycle, 2),
            'months_paid'  => $monthsPaid,
            'months_cycle' => $monthsCycle,
            'subtotal'     => round($subtotal, 2),
        ];
    }

    /**
     * Get service IDs associated with a plan
     */
    public function getServiceIds(int $planId): array
    {
        $stmt = $this->db->prepare('SELECT service_id FROM plan_services WHERE plan_id = :id');
        $stmt->execute(['id' => $planId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Sync services for a plan (replace all)
     */
    public function syncServices(int $planId, array $serviceIds): void
    {
        $this->db->prepare('DELETE FROM plan_services WHERE plan_id = :id')->execute(['id' => $planId]);

        if (!empty($serviceIds)) {
            $stmt = $this->db->prepare('INSERT INTO plan_services (plan_id, service_id) VALUES (:pid, :sid)');
            foreach ($serviceIds as $sid) {
                $stmt->execute(['pid' => $planId, 'sid' => (int)$sid]);
            }
        }
    }

    /**
     * Get plans with their services loaded
     */
    public function allWithServices(): array
    {
        $plans = $this->all();

        // Load all plan_services at once
        $rows = $this->db->query(
            'SELECT ps.plan_id, s.id as service_id, s.`key`, s.name
             FROM plan_services ps
             JOIN services s ON s.id = ps.service_id
             ORDER BY s.sort_order ASC'
        )->fetchAll();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['plan_id']][] = $row;
        }

        foreach ($plans as &$plan) {
            $plan['services'] = $map[$plan['id']] ?? [];
        }

        return $plans;
    }

    /**
     * Duplicate a plan with its services
     */
    public function duplicate(int $id): ?int
    {
        $plan = $this->findById($id);
        if (!$plan) {
            return null;
        }

        $serviceIds = $this->getServiceIds($id);

        $newId = $this->create([
            'name'        => $plan['name'] . ' (copia)',
            'slug'        => $plan['slug'] . '-copy-' . time(),
            'description' => $plan['description'],
            'price'       => $plan['price'],
            'color'       => $plan['color'],
            'is_active'   => 0,
        ]);

        $this->syncServices($newId, $serviceIds);
        return $newId;
    }
}
