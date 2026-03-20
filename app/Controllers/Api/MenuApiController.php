<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Models\MenuItem;
use App\Models\Tenant;

class MenuApiController
{
    public function index(Request $request): void
    {
        $slug = $request->param('slug');

        $tenant = (new Tenant())->findBySlug($slug);

        if (!$tenant || !$tenant['is_active'] || !$tenant['menu_enabled']) {
            Response::error('Menù non disponibile.', 'MENU_NOT_AVAILABLE', 404);
        }

        $menuItem = new MenuItem();
        $categories = $menuItem->findAvailableGrouped((int)$tenant['id']);
        $dailySpecials = $menuItem->findDailySpecials((int)$tenant['id']);

        // Clean up internal fields for public API
        $cleanItem = function (array $item): array {
            return [
                'id'               => (int)$item['id'],
                'name'             => $item['name'],
                'description'      => $item['description'],
                'price'            => (float)$item['price'],
                'image_url'        => $item['image_url'],
                'allergens'        => $item['allergens'],
                'is_daily_special' => (bool)$item['is_daily_special'],
            ];
        };

        $result = [];
        foreach ($categories as $cat) {
            $catData = [
                'id'            => $cat['id'],
                'name'          => $cat['name'],
                'description'   => $cat['description'],
                'items'         => array_map($cleanItem, $cat['items']),
                'subcategories' => [],
            ];
            foreach ($cat['subcategories'] ?? [] as $sub) {
                $catData['subcategories'][] = [
                    'id'    => $sub['id'],
                    'name'  => $sub['name'],
                    'items' => array_map($cleanItem, $sub['items']),
                ];
            }
            $result[] = $catData;
        }

        Response::json([
            'restaurant'     => $tenant['name'],
            'categories'     => $result,
            'daily_specials' => array_map($cleanItem, $dailySpecials),
        ]);
    }
}
