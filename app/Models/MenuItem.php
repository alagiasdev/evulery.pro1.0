<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class MenuItem
{
    private PDO $db;

    /**
     * EU 14 mandatory allergens: key => Italian label.
     */
    public const ALLERGENS = [
        'gluten'      => 'Glutine',
        'crustaceans' => 'Crostacei',
        'eggs'        => 'Uova',
        'fish'        => 'Pesce',
        'peanuts'     => 'Arachidi',
        'soy'         => 'Soia',
        'milk'        => 'Latte',
        'nuts'        => 'Frutta a guscio',
        'celery'      => 'Sedano',
        'mustard'     => 'Senape',
        'sesame'      => 'Sesamo',
        'sulphites'   => 'Anidride solforosa',
        'lupin'       => 'Lupini',
        'molluscs'    => 'Molluschi',
    ];

    /**
     * Short labels for allergen badge icons (1-2 chars).
     */
    public const ALLERGEN_ICONS = [
        'gluten'      => 'G',
        'crustaceans' => 'Cr',
        'eggs'        => 'U',
        'fish'        => 'P',
        'peanuts'     => 'Ar',
        'soy'         => 'S',
        'milk'        => 'L',
        'nuts'        => 'Fg',
        'celery'      => 'Se',
        'mustard'     => 'Sn',
        'sesame'      => 'Ss',
        'sulphites'   => 'So',
        'lupin'       => 'Lu',
        'molluscs'    => 'Mo',
    ];

    /**
     * Colors for allergen badge icons.
     */
    public const ALLERGEN_COLORS = [
        'gluten'      => '#D84315',
        'crustaceans' => '#E65100',
        'eggs'        => '#F9A825',
        'fish'        => '#0277BD',
        'peanuts'     => '#8D6E63',
        'soy'         => '#558B2F',
        'milk'        => '#1565C0',
        'nuts'        => '#6D4C41',
        'celery'      => '#2E7D32',
        'mustard'     => '#F57F17',
        'sesame'      => '#795548',
        'sulphites'   => '#7B1FA2',
        'lupin'       => '#AD1457',
        'molluscs'    => '#00838F',
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findAllByTenant(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT mi.*, mc.name as category_name, mc.sort_order as category_sort
             FROM menu_items mi
             JOIN menu_categories mc ON mc.id = mi.category_id AND mc.tenant_id = mi.tenant_id
             WHERE mi.tenant_id = :tenant_id
             ORDER BY mc.sort_order ASC, mi.sort_order ASC, mi.name ASC'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        return $stmt->fetchAll();
    }

    /**
     * For public page: available items in active categories, grouped hierarchically.
     * Returns array of parent categories, each with 'subcategories' array.
     * Each subcategory (or the parent itself) has an 'items' key.
     *
     * Structure:
     * [
     *   { id, name, icon, description, items: [...], subcategories: [
     *       { id, name, icon, items: [...] },
     *   ] },
     * ]
     */
    public function findAvailableGrouped(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT mi.*,
                    mc.name as category_name, mc.id as cat_id, mc.description as category_description,
                    mc.icon as category_icon, mc.parent_id as cat_parent_id,
                    COALESCE(parent.sort_order, mc.sort_order) as parent_sort,
                    CASE WHEN mc.parent_id IS NULL THEN 0 ELSE mc.sort_order END as sub_sort
             FROM menu_items mi
             JOIN menu_categories mc ON mc.id = mi.category_id AND mc.tenant_id = mi.tenant_id
             LEFT JOIN menu_categories parent ON parent.id = mc.parent_id AND parent.tenant_id = mi.tenant_id
             WHERE mi.tenant_id = :tenant_id AND mi.is_available = 1 AND mc.is_active = 1
                   AND (mc.parent_id IS NULL OR parent.is_active = 1)
             ORDER BY parent_sort ASC, sub_sort ASC, mi.sort_order ASC, mi.name ASC'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        $rows = $stmt->fetchAll();

        // Build: parents with subcategories
        $parents = [];

        foreach ($rows as $row) {
            $row['allergens'] = $this->decodeAllergens($row['allergens']);
            $catId = (int)$row['cat_id'];
            $parentId = $row['cat_parent_id'] !== null ? (int)$row['cat_parent_id'] : null;

            if ($parentId === null) {
                // Item belongs to a top-level category directly
                if (!isset($parents[$catId])) {
                    $parents[$catId] = [
                        'id'            => $catId,
                        'name'          => $row['category_name'],
                        'description'   => $row['category_description'],
                        'icon'          => $row['category_icon'] ?? 'bi-list',
                        'items'         => [],
                        'subcategories' => [],
                    ];
                }
                $parents[$catId]['items'][] = $row;
            } else {
                // Item belongs to a subcategory
                if (!isset($parents[$parentId])) {
                    // Parent might have no direct items — fetch parent info
                    $parentCat = (new MenuCategory())->findById($parentId, $tenantId);
                    $parents[$parentId] = [
                        'id'            => $parentId,
                        'name'          => $parentCat['name'] ?? '',
                        'description'   => $parentCat['description'] ?? '',
                        'icon'          => $parentCat['icon'] ?? 'bi-list',
                        'items'         => [],
                        'subcategories' => [],
                    ];
                }
                if (!isset($parents[$parentId]['subcategories'][$catId])) {
                    $parents[$parentId]['subcategories'][$catId] = [
                        'id'    => $catId,
                        'name'  => $row['category_name'],
                        'icon'  => $row['category_icon'] ?? 'bi-list',
                        'items' => [],
                    ];
                }
                $parents[$parentId]['subcategories'][$catId]['items'][] = $row;
            }
        }

        // Convert subcategories associative to indexed
        foreach ($parents as &$p) {
            $p['subcategories'] = array_values($p['subcategories']);
        }

        return array_values($parents);
    }

    /**
     * Per store ordini: piatti ordinabili + disponibili, raggruppati come findAvailableGrouped.
     */
    public function findOrderableGrouped(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT mi.*,
                    mc.name as category_name, mc.id as cat_id, mc.description as category_description,
                    mc.icon as category_icon, mc.parent_id as cat_parent_id,
                    COALESCE(parent.sort_order, mc.sort_order) as parent_sort,
                    CASE WHEN mc.parent_id IS NULL THEN 0 ELSE mc.sort_order END as sub_sort
             FROM menu_items mi
             JOIN menu_categories mc ON mc.id = mi.category_id AND mc.tenant_id = mi.tenant_id
             LEFT JOIN menu_categories parent ON parent.id = mc.parent_id AND parent.tenant_id = mi.tenant_id
             WHERE mi.tenant_id = :tenant_id AND mi.is_available = 1 AND mi.is_orderable = 1 AND mc.is_active = 1
                   AND (mc.parent_id IS NULL OR parent.is_active = 1)
             ORDER BY parent_sort ASC, sub_sort ASC, mi.sort_order ASC, mi.name ASC'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        $rows = $stmt->fetchAll();

        $parents = [];
        foreach ($rows as $row) {
            $row['allergens'] = $this->decodeAllergens($row['allergens']);
            $catId = (int)$row['cat_id'];
            $parentId = $row['cat_parent_id'] !== null ? (int)$row['cat_parent_id'] : null;

            if ($parentId === null) {
                if (!isset($parents[$catId])) {
                    $parents[$catId] = [
                        'id'            => $catId,
                        'name'          => $row['category_name'],
                        'description'   => $row['category_description'],
                        'icon'          => $row['category_icon'] ?? 'bi-list',
                        'items'         => [],
                        'subcategories' => [],
                    ];
                }
                $parents[$catId]['items'][] = $row;
            } else {
                if (!isset($parents[$parentId])) {
                    $parentCat = (new MenuCategory())->findById($parentId, $tenantId);
                    $parents[$parentId] = [
                        'id'            => $parentId,
                        'name'          => $parentCat['name'] ?? '',
                        'description'   => $parentCat['description'] ?? '',
                        'icon'          => $parentCat['icon'] ?? 'bi-list',
                        'items'         => [],
                        'subcategories' => [],
                    ];
                }
                if (!isset($parents[$parentId]['subcategories'][$catId])) {
                    $parents[$parentId]['subcategories'][$catId] = [
                        'id'    => $catId,
                        'name'  => $row['category_name'],
                        'icon'  => $row['category_icon'] ?? 'bi-list',
                        'items' => [],
                    ];
                }
                $parents[$parentId]['subcategories'][$catId]['items'][] = $row;
            }
        }

        foreach ($parents as &$p) {
            $p['subcategories'] = array_values($p['subcategories']);
        }

        return array_values($parents);
    }

    public function findById(int $id, int $tenantId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM menu_items WHERE id = :id AND tenant_id = :tenant_id'
        );
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        $item = $stmt->fetch();
        if ($item) {
            $item['allergens'] = $this->decodeAllergens($item['allergens']);
        }
        return $item ?: null;
    }

    public function findDailySpecials(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT mi.*, mc.name as category_name
             FROM menu_items mi
             JOIN menu_categories mc ON mc.id = mi.category_id AND mc.tenant_id = mi.tenant_id
             WHERE mi.tenant_id = :tenant_id AND mi.is_daily_special = 1 AND mi.is_available = 1 AND mc.is_active = 1
             ORDER BY mi.sort_order ASC'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        $items = $stmt->fetchAll();
        foreach ($items as &$item) {
            $item['allergens'] = $this->decodeAllergens($item['allergens']);
        }
        return $items;
    }

    public function create(int $tenantId, array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO menu_items (tenant_id, category_id, name, description, price, image_url, allergens,
                                     is_available, is_daily_special, is_orderable, prep_minutes, max_daily_qty, sort_order)
             VALUES (:tenant_id, :category_id, :name, :description, :price, :image_url, :allergens,
                     :is_available, :is_daily_special, :is_orderable, :prep_minutes, :max_daily_qty, :sort_order)'
        );
        $stmt->execute([
            'tenant_id'        => $tenantId,
            'category_id'      => (int)$data['category_id'],
            'name'             => $data['name'],
            'description'      => $data['description'] ?? null,
            'price'            => (float)$data['price'],
            'image_url'        => $data['image_url'] ?? null,
            'allergens'        => $this->encodeAllergens($data['allergens'] ?? []),
            'is_available'     => $data['is_available'] ?? 1,
            'is_daily_special' => $data['is_daily_special'] ?? 0,
            'is_orderable'     => $data['is_orderable'] ?? 0,
            'prep_minutes'     => !empty($data['prep_minutes']) ? (int)$data['prep_minutes'] : null,
            'max_daily_qty'    => !empty($data['max_daily_qty']) ? (int)$data['max_daily_qty'] : null,
            'sort_order'       => $data['sort_order'] ?? 0,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, int $tenantId, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE menu_items SET category_id = :category_id, name = :name, description = :description,
                    price = :price, image_url = :image_url, allergens = :allergens,
                    is_available = :is_available, is_daily_special = :is_daily_special,
                    is_orderable = :is_orderable, prep_minutes = :prep_minutes, max_daily_qty = :max_daily_qty,
                    sort_order = :sort_order
             WHERE id = :id AND tenant_id = :tenant_id'
        );
        return $stmt->execute([
            'id'               => $id,
            'tenant_id'        => $tenantId,
            'category_id'      => (int)$data['category_id'],
            'name'             => $data['name'],
            'description'      => $data['description'] ?? null,
            'price'            => (float)$data['price'],
            'image_url'        => $data['image_url'] ?? null,
            'allergens'        => $this->encodeAllergens($data['allergens'] ?? []),
            'is_available'     => $data['is_available'] ?? 1,
            'is_daily_special' => $data['is_daily_special'] ?? 0,
            'is_orderable'     => $data['is_orderable'] ?? 0,
            'prep_minutes'     => !empty($data['prep_minutes']) ? (int)$data['prep_minutes'] : null,
            'max_daily_qty'    => !empty($data['max_daily_qty']) ? (int)$data['max_daily_qty'] : null,
            'sort_order'       => $data['sort_order'] ?? 0,
        ]);
    }

    public function delete(int $id, int $tenantId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM menu_items WHERE id = :id AND tenant_id = :tenant_id'
        );
        return $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
    }

    public function toggleAvailable(int $id, int $tenantId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE menu_items SET is_available = NOT is_available WHERE id = :id AND tenant_id = :tenant_id'
        );
        return $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
    }

    public function toggleDailySpecial(int $id, int $tenantId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE menu_items SET is_daily_special = NOT is_daily_special WHERE id = :id AND tenant_id = :tenant_id'
        );
        return $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
    }

    public function countByTenant(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                COUNT(*) as total,
                SUM(is_available) as available,
                SUM(is_daily_special AND is_available) as specials
             FROM menu_items WHERE tenant_id = :tenant_id'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        return $stmt->fetch();
    }

    public function getNextSortOrder(int $categoryId, int $tenantId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(MAX(sort_order), 0) + 1 as next_sort
             FROM menu_items WHERE category_id = :category_id AND tenant_id = :tenant_id'
        );
        $stmt->execute(['category_id' => $categoryId, 'tenant_id' => $tenantId]);
        return (int)$stmt->fetch()['next_sort'];
    }

    private function encodeAllergens(array $allergens): ?string
    {
        $valid = array_intersect($allergens, array_keys(self::ALLERGENS));
        return empty($valid) ? null : json_encode(array_values($valid));
    }

    private function decodeAllergens(?string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }
        return json_decode($json, true) ?? [];
    }
}
