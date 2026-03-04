<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\MealCategory;

class MealCategoriesController
{
    public function index(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $categoryModel = new MealCategory();
        $categories = $categoryModel->findAllByTenant($tenantId);

        if (empty($categories)) {
            $categoryModel->seedDefaults($tenantId);
            $categories = $categoryModel->findAllByTenant($tenantId);
        }

        view('dashboard/settings/meal-categories', [
            'title'      => 'Categorie Pasto',
            'activeMenu' => 'meal-categories',
            'categories' => $categories,
        ], 'dashboard');
    }

    public function update(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $data = $request->all();
        $categoryModel = new MealCategory();

        if (isset($data['categories']) && is_array($data['categories'])) {
            foreach ($data['categories'] as $i => $cat) {
                if (empty($cat['name']) || empty($cat['display_name'])) continue;
                $categoryModel->upsert($tenantId, [
                    'name'         => $cat['name'],
                    'display_name' => $cat['display_name'],
                    'start_time'   => $cat['start_time'],
                    'end_time'     => $cat['end_time'],
                    'sort_order'   => (int)($cat['sort_order'] ?? $i),
                    'is_active'    => isset($cat['is_active']) ? true : false,
                ]);
            }
        }

        flash('success', 'Categorie pasto aggiornate.');
        Response::redirect(url('dashboard/settings/meal-categories'));
    }
}
