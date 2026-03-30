<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\TenantResolver;
use App\Core\Validator;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Tenant;
use App\Services\AuditLog;

class MenuController
{
    private function gate(): bool
    {
        return gate_service('digital_menu');
    }

    // --- Tab: Piatti (default) ---

    public function index(Request $request): void
    {
        $tenant = TenantResolver::current();
        $canUseMenu = tenant_can('digital_menu');

        if (!$canUseMenu) {
            view('dashboard/menu/index', [
                'title'           => 'Menù Digitale',
                'activeMenu'      => 'menu',
                'tenant'          => $tenant,
                'canUseMenu'      => false,
                'hierarchy'       => [],
                'categories'      => [],
                'itemsByCategory' => [],
                'stats'           => ['total' => 0, 'available' => 0, 'specials' => 0],
                'allergens'       => MenuItem::ALLERGENS,
                'allergenIcons'   => MenuItem::ALLERGEN_ICONS,
            ], 'dashboard');
            return;
        }

        $tenantId = Auth::tenantId();

        $catModel = new MenuCategory();
        $itemModel = new MenuItem();

        $hierarchy = $catModel->findAllHierarchical($tenantId);
        $categories = $catModel->findAllByTenant($tenantId);
        $items = $itemModel->findAllByTenant($tenantId);
        $stats = $itemModel->countByTenant($tenantId);

        // Group items by category
        $itemsByCategory = [];
        foreach ($items as $item) {
            $item['allergens'] = is_string($item['allergens']) ? json_decode($item['allergens'], true) ?? [] : ($item['allergens'] ?? []);
            $itemsByCategory[(int)$item['category_id']][] = $item;
        }

        view('dashboard/menu/index', [
            'title'            => 'Menù Digitale',
            'activeMenu'       => 'menu',
            'canUseMenu'       => true,
            'tenant'           => $tenant,
            'hierarchy'        => $hierarchy,
            'categories'       => $categories,
            'itemsByCategory'  => $itemsByCategory,
            'stats'            => $stats,
            'allergens'        => MenuItem::ALLERGENS,
            'allergenIcons'    => MenuItem::ALLERGEN_ICONS,
            'allergenColors'   => MenuItem::ALLERGEN_COLORS,
        ], 'dashboard');
    }

    // --- Tab: Categorie ---

    public function categoriesIndex(Request $request): void
    {
        if ($this->gate()) return;
        $tenantId = Auth::tenantId();
        $tenant = TenantResolver::current();

        $catModel = new MenuCategory();
        $hierarchy = $catModel->findAllHierarchical($tenantId);
        $parents = $catModel->findParentsByTenant($tenantId);
        $counts = $catModel->getItemCounts($tenantId);

        view('dashboard/menu/categories', [
            'title'          => 'Menù - Categorie',
            'activeMenu'     => 'menu',
            'tenant'         => $tenant,
            'hierarchy'      => $hierarchy,
            'parents'        => $parents,
            'counts'         => $counts,
            'categoryIcons'  => MenuCategory::ICONS,
        ], 'dashboard');
    }

    // --- Tab: Aspetto ---

    public function appearanceIndex(Request $request): void
    {
        if ($this->gate()) return;
        $tenant = TenantResolver::current();

        view('dashboard/menu/appearance', [
            'title'      => 'Menù - Aspetto',
            'activeMenu' => 'menu',
            'tenant'     => $tenant,
        ], 'dashboard');
    }

    // --- Categories CRUD ---

    public function storeCategory(Request $request): void
    {
        if ($this->gate()) return;
        $tenantId = Auth::tenantId();
        $data = $request->all();

        $v = Validator::make($data)
            ->required('name', 'Nome categoria')
            ->maxLength('name', 100, 'Nome categoria');

        if ($v->fails()) {
            flash('danger', $v->firstError());
            Response::redirect(url('dashboard/menu/categories'));
        }

        $catModel = new MenuCategory();

        // Handle parent_id: enforce max depth = 1
        $parentId = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
        if ($parentId !== null) {
            $parent = $catModel->findById($parentId, $tenantId);
            if (!$parent || $parent['parent_id'] !== null) {
                // parent doesn't exist or is itself a subcategory → reject
                flash('danger', 'Categoria padre non valida.');
                Response::redirect(url('dashboard/menu/categories'));
                return;
            }
        }

        $icon = $data['icon'] ?? 'bi-list';
        if (!array_key_exists($icon, MenuCategory::ICONS)) {
            $icon = 'bi-list';
        }
        $catModel->create($tenantId, [
            'parent_id'   => $parentId,
            'name'        => trim($data['name']),
            'description' => trim($data['description'] ?? ''),
            'icon'        => $icon,
            'sort_order'  => $catModel->getNextSortOrder($tenantId, $parentId),
        ]);

        AuditLog::log(AuditLog::MENU_CATEGORY_CREATED, "Categoria: {$data['name']}", Auth::id(), $tenantId);

        $label = $parentId ? 'Sottocategoria' : 'Categoria';
        flash('success', "{$label} \"{$data['name']}\" creata.");
        $redirectUrl = url('dashboard/menu/categories');
        if ($parentId) {
            $redirectUrl .= '?open=' . $parentId;
        }
        Response::redirect($redirectUrl);
    }

    public function updateCategory(Request $request): void
    {
        if ($this->gate()) return;
        $id = (int)$request->param('id');
        $tenantId = Auth::tenantId();
        $data = $request->all();

        $catModel = new MenuCategory();
        $existing = $catModel->findById($id, $tenantId);
        if (!$existing) {
            flash('danger', 'Categoria non trovata.');
            Response::redirect(url('dashboard/menu/categories'));
        }

        $v = Validator::make($data)
            ->required('name', 'Nome categoria')
            ->maxLength('name', 100, 'Nome categoria');

        if ($v->fails()) {
            flash('danger', $v->firstError());
            Response::redirect(url('dashboard/menu/categories'));
        }

        $icon = $data['icon'] ?? $existing['icon'];
        if (!array_key_exists($icon, MenuCategory::ICONS)) {
            $icon = $existing['icon'];
        }
        $catModel->update($id, $tenantId, [
            'name'        => trim($data['name']),
            'description' => trim($data['description'] ?? ''),
            'icon'        => $icon,
            'sort_order'  => $existing['sort_order'],
            'is_active'   => isset($data['is_active']) ? 1 : (int)$existing['is_active'],
        ]);

        AuditLog::log(AuditLog::MENU_CATEGORY_UPDATED, "Categoria ID: {$id}", Auth::id(), $tenantId);

        flash('success', 'Categoria aggiornata.');
        Response::redirect(url('dashboard/menu/categories'));
    }

    public function deleteCategory(Request $request): void
    {
        if ($this->gate()) return;
        $id = (int)$request->param('id');
        $tenantId = Auth::tenantId();

        $catModel = new MenuCategory();
        $cat = $catModel->findById($id, $tenantId);
        if (!$cat) {
            flash('danger', 'Categoria non trovata.');
            Response::redirect(url('dashboard/menu/categories'));
            return;
        }

        $isParent = $cat['parent_id'] === null;

        if ($isParent) {
            // Parent: check items in self AND all subcategories
            if ($catModel->hasItemsIncludingChildren($id, $tenantId)) {
                flash('danger', 'Elimina prima tutti i piatti di questa categoria e delle sue sottocategorie.');
                Response::redirect(url('dashboard/menu/categories'));
                return;
            }
            $catModel->deleteWithChildren($id, $tenantId);
            AuditLog::log(AuditLog::MENU_CATEGORY_DELETED, "Categoria ID: {$id}", Auth::id(), $tenantId);
            flash('success', 'Categoria e sottocategorie eliminate.');
        } else {
            // Subcategory: check only its own items
            if ($catModel->hasItems($id, $tenantId)) {
                flash('danger', 'Elimina prima tutti i piatti di questa sottocategoria.');
                Response::redirect(url('dashboard/menu/categories'));
                return;
            }
            $catModel->delete($id, $tenantId);
            AuditLog::log(AuditLog::MENU_CATEGORY_DELETED, "Categoria ID: {$id}", Auth::id(), $tenantId);
            flash('success', 'Sottocategoria eliminata.');
        }
        Response::redirect(url('dashboard/menu/categories'));
    }

    // --- Items CRUD ---

    public function createItem(Request $request): void
    {
        if ($this->gate()) return;
        $tenantId = Auth::tenantId();
        $catModel = new MenuCategory();
        $hierarchy = $catModel->findAllHierarchical($tenantId);

        if (empty($hierarchy)) {
            flash('danger', 'Crea prima almeno una categoria.');
            Response::redirect(url('dashboard/menu'));
        }

        $old = $_SESSION['_flash']['old_input'] ?? [];

        view('dashboard/menu/create', [
            'title'          => 'Nuovo Piatto',
            'activeMenu'     => 'menu',
            'hierarchy'      => $hierarchy,
            'allergens'      => MenuItem::ALLERGENS,
            'allergenIcons'  => MenuItem::ALLERGEN_ICONS,
            'allergenColors' => MenuItem::ALLERGEN_COLORS,
            'old'            => $old,
            'tenant'         => TenantResolver::current(),
        ], 'dashboard');
    }

    public function storeItem(Request $request): void
    {
        if ($this->gate()) return;
        $tenantId = Auth::tenantId();
        $data = $request->all();

        $itemData = $this->validateItemData($data, $tenantId, url('dashboard/menu/items/create'));

        // Handle image upload
        $imageUrl = $this->handleItemImageUpload($tenantId);
        if ($imageUrl) {
            $itemData['image_url'] = $imageUrl;
        }

        $itemModel = new MenuItem();
        $itemData['sort_order'] = $itemModel->getNextSortOrder((int)$itemData['category_id'], $tenantId);

        $itemModel->create($tenantId, $itemData);

        AuditLog::log(AuditLog::MENU_ITEM_CREATED, "Piatto: {$itemData['name']}", Auth::id(), $tenantId);

        flash('success', "Piatto \"{$itemData['name']}\" aggiunto al menù.");
        Response::redirect(url('dashboard/menu'));
    }

    public function editItem(Request $request): void
    {
        if ($this->gate()) return;
        $id = (int)$request->param('id');
        $tenantId = Auth::tenantId();

        $item = (new MenuItem())->findById($id, $tenantId);
        if (!$item) {
            flash('danger', 'Piatto non trovato.');
            Response::redirect(url('dashboard/menu'));
        }

        $hierarchy = (new MenuCategory())->findAllHierarchical($tenantId);
        $old = $_SESSION['_flash']['old_input'] ?? $item;

        view('dashboard/menu/edit', [
            'title'          => 'Modifica Piatto',
            'activeMenu'     => 'menu',
            'item'           => $item,
            'hierarchy'      => $hierarchy,
            'allergens'      => MenuItem::ALLERGENS,
            'allergenIcons'  => MenuItem::ALLERGEN_ICONS,
            'allergenColors' => MenuItem::ALLERGEN_COLORS,
            'old'            => $old,
            'tenant'         => TenantResolver::current(),
        ], 'dashboard');
    }

    public function updateItem(Request $request): void
    {
        if ($this->gate()) return;
        $id = (int)$request->param('id');
        $tenantId = Auth::tenantId();

        $itemModel = new MenuItem();
        $existing = $itemModel->findById($id, $tenantId);
        if (!$existing) {
            flash('danger', 'Piatto non trovato.');
            Response::redirect(url('dashboard/menu'));
        }

        $data = $request->all();
        $redirectUrl = url("dashboard/menu/items/{$id}/edit");
        $itemData = $this->validateItemData($data, $tenantId, $redirectUrl);

        // Handle image
        if (!empty($data['remove_image'])) {
            $this->deleteItemImage($existing['image_url']);
            $itemData['image_url'] = null;
        } else {
            $newImage = $this->handleItemImageUpload($tenantId);
            if ($newImage) {
                $this->deleteItemImage($existing['image_url']);
                $itemData['image_url'] = $newImage;
            } else {
                $itemData['image_url'] = $existing['image_url'];
            }
        }

        $itemData['sort_order'] = $existing['sort_order'];
        $itemModel->update($id, $tenantId, $itemData);

        AuditLog::log(AuditLog::MENU_ITEM_UPDATED, "Piatto ID: {$id}", Auth::id(), $tenantId);

        flash('success', "Piatto \"{$itemData['name']}\" aggiornato.");
        Response::redirect(url('dashboard/menu'));
    }

    public function deleteItem(Request $request): void
    {
        if ($this->gate()) return;
        $id = (int)$request->param('id');
        $tenantId = Auth::tenantId();

        $itemModel = new MenuItem();
        $item = $itemModel->findById($id, $tenantId);
        if ($item) {
            $this->deleteItemImage($item['image_url']);
            $itemModel->delete($id, $tenantId);
            AuditLog::log(AuditLog::MENU_ITEM_DELETED, "Piatto ID: {$id}", Auth::id(), $tenantId);
            flash('success', 'Piatto eliminato.');
        } else {
            flash('danger', 'Piatto non trovato.');
        }
        Response::redirect(url('dashboard/menu'));
    }

    public function toggleAvailable(Request $request): void
    {
        if ($this->gate()) return;
        $id = (int)$request->param('id');
        $tenantId = Auth::tenantId();
        (new MenuItem())->toggleAvailable($id, $tenantId);
        flash('success', 'Disponibilità aggiornata.');
        Response::redirect(url('dashboard/menu'));
    }

    public function toggleDailySpecial(Request $request): void
    {
        if ($this->gate()) return;
        $id = (int)$request->param('id');
        $tenantId = Auth::tenantId();
        (new MenuItem())->toggleDailySpecial($id, $tenantId);
        flash('success', 'Piatto del giorno aggiornato.');
        Response::redirect(url('dashboard/menu'));
    }

    public function toggleMenu(Request $request): void
    {
        if ($this->gate()) return;
        $tenant = TenantResolver::current();
        $newValue = $tenant['menu_enabled'] ? 0 : 1;
        (new Tenant())->update($tenant['id'], ['menu_enabled' => $newValue]);
        AuditLog::log(AuditLog::MENU_TOGGLED, null, Auth::id(), $tenant['id']);
        flash('success', $newValue ? 'Menù pubblico attivato.' : 'Menù pubblico disattivato.');
        Response::redirect(url('dashboard/menu/appearance'));
    }

    public function saveSettings(Request $request): void
    {
        if ($this->gate()) return;
        $tenant = TenantResolver::current();
        $data = $request->all();

        $update = [
            'menu_tagline'   => trim($data['menu_tagline'] ?? ''),
            'opening_hours'  => trim($data['opening_hours'] ?? ''),
        ];

        // Hero image upload
        if (!empty($data['remove_hero_image'])) {
            $this->deleteItemImage($tenant['menu_hero_image']);
            $update['menu_hero_image'] = null;
        } elseif (!empty($_FILES['menu_hero_image']) && $_FILES['menu_hero_image']['error'] === UPLOAD_ERR_OK) {
            $newHero = $this->handleHeroImageUpload($tenant['id']);
            if ($newHero) {
                $this->deleteItemImage($tenant['menu_hero_image']);
                $update['menu_hero_image'] = $newHero;
            }
        }

        (new Tenant())->update($tenant['id'], $update);
        flash('success', 'Impostazioni menù aggiornate.');
        Response::redirect(url('dashboard/menu/appearance'));
    }

    private function handleHeroImageUpload(int $tenantId): ?string
    {
        $file = $_FILES['menu_hero_image'];
        $maxSize = 5 * 1024 * 1024; // 5MB for hero
        if ($file['size'] > $maxSize) {
            flash('danger', 'Immagine hero troppo grande (max 5MB).');
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

        if (!isset($allowed[$mime])) {
            flash('danger', 'Formato immagine non supportato (JPG, PNG, WebP).');
            return null;
        }

        $ext = $allowed[$mime];
        $dir = defined('BASE_PATH') ? BASE_PATH . '/public/uploads/tenants/' : $_SERVER['DOCUMENT_ROOT'] . '/uploads/tenants/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = "hero_{$tenantId}_" . bin2hex(random_bytes(8)) . ".{$ext}";
        if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
            return null;
        }

        return url('uploads/tenants/' . $filename);
    }

    // --- Private helpers ---

    private function validateItemData(array $data, int $tenantId, string $redirectUrl): array
    {
        $v = Validator::make($data)
            ->required('name', 'Nome piatto')
            ->maxLength('name', 150, 'Nome piatto')
            ->required('price', 'Prezzo')
            ->required('category_id', 'Categoria');

        if ($v->fails()) {
            flash('danger', $v->firstError());
            Session::flash('old_input', $data);
            Response::redirect($redirectUrl);
        }

        $price = (float)$data['price'];
        if ($price <= 0) {
            flash('danger', 'Il prezzo deve essere maggiore di 0.');
            Session::flash('old_input', $data);
            Response::redirect($redirectUrl);
        }

        $catModel = new MenuCategory();
        if (!$catModel->findById((int)$data['category_id'], $tenantId)) {
            flash('danger', 'Categoria non valida.');
            Session::flash('old_input', $data);
            Response::redirect($redirectUrl);
        }

        // Validate allergens
        $allergens = $data['allergens'] ?? [];
        $validAllergens = array_intersect($allergens, array_keys(MenuItem::ALLERGENS));

        $result = [
            'category_id'      => (int)$data['category_id'],
            'name'             => trim($data['name']),
            'description'      => trim($data['description'] ?? ''),
            'price'            => $price,
            'allergens'        => $validAllergens,
            'is_available'     => isset($data['is_available']) ? 1 : 0,
            'is_daily_special' => isset($data['is_daily_special']) ? 1 : 0,
        ];

        // Ordering fields (only if service available)
        if (tenant_can('online_ordering')) {
            $result['is_orderable']  = isset($data['is_orderable']) ? 1 : 0;
            $result['prep_minutes']  = !empty($data['prep_minutes']) ? (int)$data['prep_minutes'] : null;
            $result['max_daily_qty'] = !empty($data['max_daily_qty']) ? (int)$data['max_daily_qty'] : null;
        }

        return $result;
    }

    private function handleItemImageUpload(int $tenantId): ?string
    {
        if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES['image'];
        $maxSize = 2 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            flash('danger', 'Immagine troppo grande (max 2MB).');
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

        if (!isset($allowed[$mime])) {
            flash('danger', 'Formato immagine non supportato (JPG, PNG, WebP).');
            return null;
        }

        $ext = $allowed[$mime];
        $dir = defined('BASE_PATH') ? BASE_PATH . '/public/uploads/tenants/' : $_SERVER['DOCUMENT_ROOT'] . '/uploads/tenants/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = "menu_{$tenantId}_" . bin2hex(random_bytes(8)) . ".{$ext}";
        if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
            return null;
        }

        return url('uploads/tenants/' . $filename);
    }

    private function deleteItemImage(?string $imageUrl): void
    {
        if (!$imageUrl) {
            return;
        }
        // Extract filename from URL
        $parts = explode('uploads/tenants/', $imageUrl);
        if (count($parts) === 2) {
            $dir = defined('BASE_PATH') ? BASE_PATH . '/public/uploads/tenants/' : $_SERVER['DOCUMENT_ROOT'] . '/uploads/tenants/';
            $filepath = $dir . basename($parts[1]);
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
    }
}
