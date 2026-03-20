<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class MenuCategory
{
    private PDO $db;

    /**
     * Available icons for category picker (Bootstrap Icons).
     */
    public const ICONS = [
        'bi-egg-fried'    => 'Antipasto',
        'bi-cup-hot'      => 'Primo / Zuppa',
        'bi-fire'         => 'Grill / Carne',
        'bi-water'        => 'Pesce / Mare',
        'bi-cake2'        => 'Dolce',
        'bi-cup-straw'    => 'Bevanda',
        'bi-cup'          => 'Caffe',
        'bi-droplet'      => 'Acqua',
        'bi-snow2'        => 'Gelato / Freddo',
        'bi-lightning'    => 'Piccante',
        'bi-star'         => 'Speciale',
        'bi-heart'        => 'Preferiti',
        'bi-trophy'       => 'Signature',
        'bi-moon'         => 'Cena',
        'bi-sun'          => 'Brunch',
        'bi-clock'        => 'Happy Hour',
        'bi-emoji-smile'  => 'Menu Bambini',
        'bi-book'         => 'Menu',
        'bi-list'         => 'Generico',
        'bi-bag'          => 'Asporto',
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * All categories for a tenant, ordered for hierarchical display:
     * parents first (by sort_order), then children grouped under parent.
     */
    public function findAllByTenant(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM menu_categories WHERE tenant_id = :tenant_id ORDER BY sort_order ASC, name ASC'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        return $stmt->fetchAll();
    }

    /**
     * Top-level (parent) categories only.
     */
    public function findParentsByTenant(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM menu_categories WHERE tenant_id = :tenant_id AND parent_id IS NULL ORDER BY sort_order ASC, name ASC'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        return $stmt->fetchAll();
    }

    /**
     * Subcategories of a given parent.
     */
    public function findChildrenOf(int $parentId, int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM menu_categories WHERE tenant_id = :tenant_id AND parent_id = :parent_id ORDER BY sort_order ASC, name ASC'
        );
        $stmt->execute(['tenant_id' => $tenantId, 'parent_id' => $parentId]);
        return $stmt->fetchAll();
    }

    /**
     * Build hierarchical tree: parents with 'children' key.
     */
    public function findAllHierarchical(int $tenantId): array
    {
        $all = $this->findAllByTenant($tenantId);
        $parents = [];
        $childrenMap = [];

        foreach ($all as $cat) {
            if ($cat['parent_id'] === null) {
                $cat['children'] = [];
                $parents[(int)$cat['id']] = $cat;
            } else {
                $childrenMap[(int)$cat['parent_id']][] = $cat;
            }
        }

        foreach ($childrenMap as $parentId => $children) {
            if (isset($parents[$parentId])) {
                $parents[$parentId]['children'] = $children;
            }
        }

        return array_values($parents);
    }

    public function findActiveByTenant(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM menu_categories WHERE tenant_id = :tenant_id AND is_active = 1 ORDER BY sort_order ASC, name ASC'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        return $stmt->fetchAll();
    }

    /**
     * Check if a category is a top-level parent (not a subcategory).
     */
    public function isParent(int $id, int $tenantId): bool
    {
        $cat = $this->findById($id, $tenantId);
        return $cat && $cat['parent_id'] === null;
    }

    /**
     * Check if a parent category has subcategories.
     */
    public function hasChildren(int $id, int $tenantId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as cnt FROM menu_categories WHERE parent_id = :id AND tenant_id = :tenant_id'
        );
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        return (int)$stmt->fetch()['cnt'] > 0;
    }

    public function findById(int $id, int $tenantId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM menu_categories WHERE id = :id AND tenant_id = :tenant_id'
        );
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        return $stmt->fetch() ?: null;
    }

    public function create(int $tenantId, array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO menu_categories (tenant_id, parent_id, name, description, icon, sort_order, is_active)
             VALUES (:tenant_id, :parent_id, :name, :description, :icon, :sort_order, :is_active)'
        );
        $stmt->execute([
            'tenant_id'   => $tenantId,
            'parent_id'   => $data['parent_id'] ?? null,
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'icon'        => $data['icon'] ?? 'bi-list',
            'sort_order'  => $data['sort_order'] ?? 0,
            'is_active'   => $data['is_active'] ?? 1,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, int $tenantId, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE menu_categories SET name = :name, description = :description, icon = :icon, sort_order = :sort_order, is_active = :is_active
             WHERE id = :id AND tenant_id = :tenant_id'
        );
        return $stmt->execute([
            'id'          => $id,
            'tenant_id'   => $tenantId,
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'icon'        => $data['icon'] ?? 'bi-list',
            'sort_order'  => $data['sort_order'] ?? 0,
            'is_active'   => $data['is_active'] ?? 1,
        ]);
    }

    public function delete(int $id, int $tenantId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM menu_categories WHERE id = :id AND tenant_id = :tenant_id'
        );
        return $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
    }

    /**
     * Delete a parent and all its subcategories (only if none have items).
     */
    public function deleteWithChildren(int $id, int $tenantId): bool
    {
        // Delete subcategories first
        $stmt = $this->db->prepare(
            'DELETE FROM menu_categories WHERE parent_id = :id AND tenant_id = :tenant_id'
        );
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);

        return $this->delete($id, $tenantId);
    }

    /**
     * Check if a category (or its subcategories) has items.
     */
    public function hasItems(int $id, int $tenantId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as cnt FROM menu_items WHERE category_id = :id AND tenant_id = :tenant_id'
        );
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        return (int)$stmt->fetch()['cnt'] > 0;
    }

    /**
     * Check if a parent category or any of its subcategories has items.
     */
    public function hasItemsIncludingChildren(int $id, int $tenantId): bool
    {
        if ($this->hasItems($id, $tenantId)) {
            return true;
        }
        $children = $this->findChildrenOf($id, $tenantId);
        foreach ($children as $child) {
            if ($this->hasItems((int)$child['id'], $tenantId)) {
                return true;
            }
        }
        return false;
    }

    public function getNextSortOrder(int $tenantId, ?int $parentId = null): int
    {
        if ($parentId === null) {
            $stmt = $this->db->prepare(
                'SELECT COALESCE(MAX(sort_order), 0) + 1 as next_sort FROM menu_categories WHERE tenant_id = :tenant_id AND parent_id IS NULL'
            );
            $stmt->execute(['tenant_id' => $tenantId]);
        } else {
            $stmt = $this->db->prepare(
                'SELECT COALESCE(MAX(sort_order), 0) + 1 as next_sort FROM menu_categories WHERE tenant_id = :tenant_id AND parent_id = :parent_id'
            );
            $stmt->execute(['tenant_id' => $tenantId, 'parent_id' => $parentId]);
        }
        return (int)$stmt->fetch()['next_sort'];
    }

    /**
     * Count items per category for a tenant.
     * Returns [category_id => count].
     */
    public function getItemCounts(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT category_id, COUNT(*) as cnt FROM menu_items
             WHERE tenant_id = :tenant_id GROUP BY category_id'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        $counts = [];
        foreach ($stmt->fetchAll() as $row) {
            $counts[(int)$row['category_id']] = (int)$row['cnt'];
        }
        return $counts;
    }
}
