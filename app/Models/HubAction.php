<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Vetrina Digitale (Hub) — azioni del ristoratore.
 * Mix di preset (system-defined) e custom (Enterprise only).
 * Render order via sort_order, mostrate solo se is_active=1.
 */
class HubAction
{
    private PDO $db;

    /**
     * Definizioni dei preset disponibili nel sistema.
     * `requires` indica condizioni di disponibilità (servizio o campo tenant).
     * `default_active` indica se al primo seeding l'azione è ON.
     * `locked_position`: se valorizzato, NON è riordinabile dal ristoratore.
     */
    public const PRESETS = [
        'booking' => [
            'label'           => 'Prenota un tavolo',
            'sub'             => 'Scegli data e orario in 30 secondi',
            'icon'            => 'bi-calendar-check',
            'requires'        => null,      // sempre disponibile
            'default_active'  => true,
            'locked_position' => 1,         // sempre prima
            'is_hero'         => true,      // CTA hero, non in lista
        ],
        'menu' => [
            'label'           => 'Guarda il menu',
            'sub'             => 'Allergeni · ingredienti · prezzi',
            'icon'            => 'bi-journal-richtext',
            'requires'        => 'tenant.menu_enabled',
            'default_active'  => true,
            'locked_position' => null,
            'is_hero'         => false,
        ],
        'order' => [
            'label'           => 'Ordina online',
            'sub'             => 'Asporto e consegna a domicilio',
            'icon'            => 'bi-bag-check',
            'requires'        => 'service.online_ordering',
            'default_active'  => true,
            'locked_position' => null,
            'is_hero'         => false,
        ],
        'reviews' => [
            'label'           => 'Lascia una recensione',
            'sub'             => 'Aiutaci a migliorare',
            'icon'            => 'bi-star',
            'requires'        => 'service.review_management',
            'default_active'  => true,
            'locked_position' => null,
            'is_hero'         => false,
        ],
        'promotions' => [
            'label'           => 'Offerte del momento',
            'sub'             => 'Promozioni attive',
            'icon'            => 'bi-gift',
            'requires'        => 'service.promotions',
            'default_active'  => true,
            'locked_position' => null,
            'is_hero'         => false,
        ],
        'whatsapp' => [
            'label'           => 'Scrivici su WhatsApp',
            'sub'             => null,        // dinamico: il numero
            'icon'            => 'bi-whatsapp',
            'requires'        => 'hub.whatsapp_number',
            'default_active'  => true,
            'locked_position' => null,
            'is_hero'         => false,
        ],
        'phone' => [
            'label'           => 'Chiamaci',
            'sub'             => null,        // dinamico: il numero
            'icon'            => 'bi-telephone',
            'requires'        => 'tenant.phone',
            'default_active'  => true,
            'locked_position' => null,
            'is_hero'         => false,
        ],
        'maps' => [
            'label'           => 'Come raggiungerci',
            'sub'             => null,        // dinamico: l'indirizzo abbreviato
            'icon'            => 'bi-geo-alt',
            'requires'        => 'tenant.address',
            'default_active'  => true,
            'locked_position' => null,
            'is_hero'         => false,
        ],
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findAllByTenant(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM tenant_hub_actions WHERE tenant_id = :tid ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute(['tid' => $tenantId]);
        return $stmt->fetchAll();
    }

    public function findActiveByTenant(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM tenant_hub_actions WHERE tenant_id = :tid AND is_active = 1 ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute(['tid' => $tenantId]);
        return $stmt->fetchAll();
    }

    /**
     * Seeds preset rows for a tenant on first hub activation.
     * Idempotent: re-running won't create duplicates (UNIQUE on tenant_id+preset_key).
     */
    public function seedPresets(int $tenantId): void
    {
        $stmt = $this->db->prepare(
            'INSERT IGNORE INTO tenant_hub_actions (tenant_id, action_type, preset_key, is_active, sort_order)
             VALUES (:tid, "preset", :key, :active, :sort)'
        );
        $sort = 1;
        foreach (self::PRESETS as $key => $def) {
            $stmt->execute([
                'tid'    => $tenantId,
                'key'    => $key,
                'active' => $def['default_active'] ? 1 : 0,
                'sort'   => $def['locked_position'] ?? $sort,
            ]);
            $sort++;
        }
    }

    public function setActive(int $actionId, int $tenantId, bool $active): void
    {
        $stmt = $this->db->prepare(
            'UPDATE tenant_hub_actions SET is_active = :a WHERE id = :id AND tenant_id = :tid'
        );
        $stmt->execute(['a' => $active ? 1 : 0, 'id' => $actionId, 'tid' => $tenantId]);
    }

    /**
     * Forces is_active=1 on all locked preset rows for a tenant.
     * Repairs state if a previous save accidentally turned them off
     * (e.g. disabled checkboxes don't submit and used to be set to 0).
     */
    public function ensureLockedActive(int $tenantId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE tenant_hub_actions SET is_active = 1
             WHERE tenant_id = :tid AND action_type = 'preset' AND preset_key = :key"
        );
        foreach (self::PRESETS as $key => $def) {
            if (empty($def['locked_position'])) continue;
            $stmt->execute(['tid' => $tenantId, 'key' => $key]);
        }
    }

    /**
     * Updates sort_order for a list of action IDs in the given order.
     * Used by drag-to-reorder UI.
     */
    public function reorder(int $tenantId, array $orderedIds): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'UPDATE tenant_hub_actions SET sort_order = :s WHERE id = :id AND tenant_id = :tid'
            );
            foreach ($orderedIds as $i => $id) {
                $stmt->execute(['s' => $i + 1, 'id' => (int)$id, 'tid' => $tenantId]);
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function createCustom(int $tenantId, string $label, string $url, string $icon = 'bi-link-45deg', ?string $sub = null): int
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(MAX(sort_order), 0) + 1 AS s FROM tenant_hub_actions WHERE tenant_id = :tid'
        );
        $stmt->execute(['tid' => $tenantId]);
        $sort = (int)$stmt->fetchColumn();

        $stmt = $this->db->prepare(
            'INSERT INTO tenant_hub_actions
                (tenant_id, action_type, custom_label, custom_url, custom_sub, custom_icon, is_active, sort_order)
             VALUES (:tid, "custom", :label, :url, :sub, :icon, 1, :sort)'
        );
        $stmt->execute([
            'tid'   => $tenantId,
            'label' => $label,
            'url'   => $url,
            'sub'   => $sub,
            'icon'  => $icon,
            'sort'  => $sort,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateCustom(int $actionId, int $tenantId, string $label, string $url, string $icon, ?string $sub = null): void
    {
        $stmt = $this->db->prepare(
            'UPDATE tenant_hub_actions
             SET custom_label = :label, custom_url = :url, custom_sub = :sub, custom_icon = :icon
             WHERE id = :id AND tenant_id = :tid AND action_type = "custom"'
        );
        $stmt->execute([
            'label' => $label,
            'url'   => $url,
            'sub'   => $sub,
            'icon'  => $icon,
            'id'    => $actionId,
            'tid'   => $tenantId,
        ]);
    }

    public function deleteCustom(int $actionId, int $tenantId): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM tenant_hub_actions WHERE id = :id AND tenant_id = :tid AND action_type = "custom"'
        );
        $stmt->execute(['id' => $actionId, 'tid' => $tenantId]);
    }
}
