<?php

namespace App\Controllers\Menu;

use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Tenant;

class MenuPageController
{
    public function show(Request $request): void
    {
        $slug = $request->param('slug');
        $tenant = (new Tenant())->findBySlug($slug);

        if (!$tenant || !$tenant['is_active']) {
            Response::notFound();
        }

        if (!$tenant['menu_enabled']) {
            Response::notFound();
        }

        TenantResolver::setCurrent($tenant);

        $itemModel = new MenuItem();
        $catModel  = new MenuCategory();

        $categories  = $itemModel->findAvailableGrouped($tenant['id']);
        $specials    = $itemModel->findDailySpecials($tenant['id']);
        $activeCats  = $catModel->findActiveByTenant($tenant['id']);
        $itemCounts  = $catModel->getItemCounts($tenant['id']);

        view('menu/public', [
            'title'          => 'Menu - ' . $tenant['name'],
            'tenant'         => $tenant,
            'tenantName'     => $tenant['name'],
            'tenantLogo'     => $tenant['logo_url'] ?? null,
            'slug'           => $slug,
            'categories'     => $categories,
            'specials'       => $specials,
            'activeCats'     => $activeCats,
            'itemCounts'     => $itemCounts,
            'allergens'      => MenuItem::ALLERGENS,
            'allergenIcons'  => MenuItem::ALLERGEN_ICONS,
            'allergenColors' => MenuItem::ALLERGEN_COLORS,
            'heroImage'      => $tenant['menu_hero_image'] ?? null,
            'tagline'        => $tenant['menu_tagline'] ?? null,
            'openingHours'   => $tenant['opening_hours'] ?? null,
            'phone'          => $tenant['phone'] ?? null,
            'address'        => $tenant['address'] ?? null,
        ]);
    }
}
