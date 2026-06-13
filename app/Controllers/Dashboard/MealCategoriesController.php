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

        // La durata per fascia e' gatata (advanced_turns, Professional+).
        // Se il tenant non ha il servizio, ignoriamo i campi durata in input
        // (la UI non li mostra, ma difesa anche lato server). I valori
        // gia' salvati restano intatti -> grandfathering.
        $canAdvancedTurns = tenant_can('advanced_turns');

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

                $upsert = [
                    'name'         => $cat['name'],
                    'display_name' => $cat['display_name'],
                    'start_time'   => $startTime,
                    'end_time'     => $endTime,
                    'sort_order'   => (int)($cat['sort_order'] ?? $i),
                    'is_active'    => !empty($cat['is_active']),
                ];

                if ($canAdvancedTurns) {
                    $dur = $this->parseDuration($cat);
                    if ($dur === false) {
                        flash('danger', "Durata non valida per '{$cat['display_name']}' (consentito 15-300 minuti).");
                        Response::redirect(url('dashboard/settings/meal-categories'));
                    }
                    $upsert += $dur;
                } else {
                    // Senza il servizio: preserva i valori esistenti nel DB
                    $existing = $categoryModel->findByName($tenantId, $cat['name']);
                    $upsert['duration_minutes']     = $existing['duration_minutes'] ?? null;
                    $upsert['duration_minutes_alt'] = $existing['duration_minutes_alt'] ?? null;
                    $upsert['duration_alt_days']    = $existing['duration_alt_days'] ?? null;
                }

                $categoryModel->upsert($tenantId, $upsert);
            }
        }

        flash('success', 'Categorie pasto aggiornate.');
        Response::redirect(url('dashboard/settings/meal-categories'));
    }

    /**
     * Normalizza e valida i campi durata di una categoria dal form.
     * Ritorna array ['duration_minutes','duration_minutes_alt','duration_alt_days']
     * oppure false se un valore e' fuori range (15-300).
     *
     * - durata base vuota -> NULL (usa la globale)
     * - override valido solo se base presente + alt presente + almeno 1 giorno
     */
    private function parseDuration(array $cat): array|false
    {
        $clean = function ($v): ?int {
            $v = trim((string)$v);
            return $v === '' ? null : (int)$v;
        };
        $base = $clean($cat['duration_minutes'] ?? '');
        $alt  = $clean($cat['duration_minutes_alt'] ?? '');

        foreach ([$base, $alt] as $d) {
            if ($d !== null && ($d < 15 || $d > 300)) return false;
        }

        // Giorni override: array di 1-7
        $days = [];
        if (!empty($cat['duration_alt_days']) && is_array($cat['duration_alt_days'])) {
            foreach ($cat['duration_alt_days'] as $d) {
                $d = (int)$d;
                if ($d >= 1 && $d <= 7) $days[] = $d;
            }
            $days = array_values(array_unique($days));
            sort($days);
        }

        // L'override e' coerente solo con base + alt + giorni tutti presenti.
        $hasOverride = $base !== null && $alt !== null && !empty($days);

        return [
            'duration_minutes'     => $base,
            'duration_minutes_alt' => $hasOverride ? $alt : null,
            'duration_alt_days'    => $hasOverride ? json_encode($days) : null,
        ];
    }
}
