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
            'tenant'     => \App\Core\TenantResolver::current(),
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

                // Validate time format HH:MM
                $startTime = $cat['start_time'] ?? '';
                $endTime = $cat['end_time'] ?? '';
                if (!preg_match('/^\d{2}:\d{2}$/', $startTime) || !preg_match('/^\d{2}:\d{2}$/', $endTime)) {
                    flash('danger', "Formato orario non valido per '{$cat['display_name']}'. Usa il formato HH:MM.");
                    Response::redirect(url('dashboard/settings/meal-categories'));
                }
                if ($startTime >= $endTime) {
                    flash('danger', "L'orario di inizio deve essere prima dell'orario di fine per '{$cat['display_name']}'.");
                    Response::redirect(url('dashboard/settings/meal-categories'));
                }

                $categoryModel->upsert($tenantId, [
                    'name'         => $cat['name'],
                    'display_name' => $cat['display_name'],
                    'start_time'   => $startTime,
                    'end_time'     => $endTime,
                    'sort_order'   => (int)($cat['sort_order'] ?? $i),
                    'is_active'    => !empty($cat['is_active']),
                ]);
            }
        }

        flash('success', 'Categorie pasto aggiornate.');
        Response::redirect(url('dashboard/settings/meal-categories'));
    }
}
