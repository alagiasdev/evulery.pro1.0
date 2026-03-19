<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\TenantResolver;
use App\Core\Validator;
use App\Models\Promotion;

class PromotionsController
{
    public function index(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $promoModel = new Promotion();
        $promotions = $promoModel->findAllByTenant($tenantId);

        $active = [];
        $inactive = [];
        foreach ($promotions as $p) {
            if ($p['is_active']) {
                $active[] = $p;
            } else {
                $inactive[] = $p;
            }
        }

        // KPI data
        $discountedLast30 = $promoModel->getDiscountedBookings($tenantId, 30);
        $growthPercent = $promoModel->getPromoGrowthPercent($tenantId);

        view('dashboard/settings/promotions', [
            'title'           => 'Promozioni',
            'activeMenu'      => 'promotions',
            'active'          => $active,
            'inactive'        => $inactive,
            'discountedLast30' => $discountedLast30,
            'growthPercent'   => $growthPercent,
            'tenant'          => TenantResolver::current(),
        ], 'dashboard');
    }

    public function store(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $data = $request->all();

        $promoData = $this->validateAndBuild($data, url('dashboard/settings/promotions'));

        (new Promotion())->create($tenantId, $promoData);

        flash('success', "Promozione \"{$promoData['name']}\" creata con successo.");
        Response::redirect(url('dashboard/settings/promotions'));
    }

    public function edit(Request $request): void
    {
        $id = (int)$request->param('id');
        $tenantId = Auth::tenantId();

        $promo = (new Promotion())->findById($id, $tenantId);
        if (!$promo) {
            flash('danger', 'Promozione non trovata.');
            Response::redirect(url('dashboard/settings/promotions'));
        }

        view('dashboard/settings/promotions-edit', [
            'title'      => 'Modifica Promozione',
            'activeMenu' => 'promotions',
            'promo'      => $promo,
            'tenant'     => TenantResolver::current(),
        ], 'dashboard');
    }

    public function update(Request $request): void
    {
        $id = (int)$request->param('id');
        $tenantId = Auth::tenantId();
        $data = $request->all();

        $redirectUrl = url("dashboard/settings/promotions/{$id}/edit");
        $promoData = $this->validateAndBuild($data, $redirectUrl);

        $promoModel = new Promotion();
        $existing = $promoModel->findById($id, $tenantId);
        if (!$existing) {
            flash('danger', 'Promozione non trovata.');
            Response::redirect(url('dashboard/settings/promotions'));
        }

        $promoModel->update($id, $tenantId, $promoData);

        flash('success', "Promozione \"{$promoData['name']}\" aggiornata.");
        Response::redirect(url('dashboard/settings/promotions'));
    }

    public function toggle(Request $request): void
    {
        $id = (int)$request->param('id');
        $tenantId = Auth::tenantId();

        (new Promotion())->toggleActive($id, $tenantId);
        flash('success', 'Stato promozione aggiornato.');
        Response::redirect(url('dashboard/settings/promotions'));
    }

    public function delete(Request $request): void
    {
        $id = (int)$request->param('id');
        $tenantId = Auth::tenantId();

        if ((new Promotion())->delete($id, $tenantId)) {
            flash('success', 'Promozione eliminata.');
        } else {
            flash('danger', 'Promozione non trovata.');
        }

        Response::redirect(url('dashboard/settings/promotions'));
    }

    /**
     * Shared validation + data building for store and update.
     */
    private function validateAndBuild(array $data, string $redirectUrl): array
    {
        $v = Validator::make($data)
            ->required('name', 'Nome promozione')
            ->maxLength('name', 100, 'Nome promozione')
            ->required('discount_percent', 'Percentuale sconto')
            ->required('type', 'Tipo promozione');

        if ($v->fails()) {
            flash('danger', $v->firstError());
            Session::flash('old_input', $data);
            Response::redirect($redirectUrl);
        }

        $discount = (int)$data['discount_percent'];
        if ($discount < 5 || $discount > 50) {
            flash('danger', 'Lo sconto deve essere tra 5% e 50%.');
            Session::flash('old_input', $data);
            Response::redirect($redirectUrl);
        }

        $type = $data['type'];
        if (!in_array($type, ['recurring', 'time_slot', 'specific_date'])) {
            flash('danger', 'Tipo promozione non valido.');
            Response::redirect($redirectUrl);
        }

        $promoData = [
            'name'             => trim($data['name']),
            'discount_percent' => $discount,
            'type'             => $type,
            'days_of_week'     => null,
            'time_from'        => null,
            'time_to'          => null,
            'date_from'        => null,
            'date_to'          => null,
        ];

        if ($type === 'recurring') {
            if (empty($data['days'])) {
                flash('danger', 'Seleziona almeno un giorno della settimana.');
                Session::flash('old_input', $data);
                Response::redirect($redirectUrl);
            }
            $promoData['days_of_week'] = implode(',', array_map('intval', $data['days']));
            if (!empty($data['time_from']) && !empty($data['time_to'])) {
                $promoData['time_from'] = $data['time_from'];
                $promoData['time_to'] = $data['time_to'];
            }
        } elseif ($type === 'time_slot') {
            if (empty($data['time_from']) || empty($data['time_to'])) {
                flash('danger', 'Inserisci orario inizio e fine.');
                Session::flash('old_input', $data);
                Response::redirect($redirectUrl);
            }
            $promoData['time_from'] = $data['time_from'];
            $promoData['time_to'] = $data['time_to'];
            if (!empty($data['days'])) {
                $promoData['days_of_week'] = implode(',', array_map('intval', $data['days']));
            }
        } elseif ($type === 'specific_date') {
            if (empty($data['date_from'])) {
                flash('danger', 'Inserisci la data.');
                Session::flash('old_input', $data);
                Response::redirect($redirectUrl);
            }
            $promoData['date_from'] = $data['date_from'];
            $promoData['date_to'] = !empty($data['date_to']) ? $data['date_to'] : $data['date_from'];
            if (!empty($data['time_from']) && !empty($data['time_to'])) {
                $promoData['time_from'] = $data['time_from'];
                $promoData['time_to'] = $data['time_to'];
            }
        }

        return $promoData;
    }
}
