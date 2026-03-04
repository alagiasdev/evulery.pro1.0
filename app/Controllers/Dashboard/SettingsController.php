<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Models\Tenant;

class SettingsController
{
    public function general(Request $request): void
    {
        view('dashboard/settings/general', [
            'title'      => 'Impostazioni',
            'activeMenu' => 'settings',
            'tenant'     => TenantResolver::current(),
        ], 'dashboard');
    }

    public function updateGeneral(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $data = $request->all();

        (new Tenant())->update($tenantId, [
            'name'                => $data['name'] ?? '',
            'email'               => $data['email'] ?? '',
            'phone'               => $data['phone'] ?? null,
            'address'             => $data['address'] ?? null,
            'cancellation_policy' => $data['cancellation_policy'] ?? null,
            'table_duration'      => (int)($data['table_duration'] ?? 90),
            'time_step'           => (int)($data['time_step'] ?? 30),
        ]);

        // Refresh tenant in resolver
        $tenant = (new Tenant())->findById($tenantId);
        TenantResolver::setCurrent($tenant);

        flash('success', 'Impostazioni aggiornate.');
        Response::redirect(url('dashboard/settings'));
    }

    public function deposit(Request $request): void
    {
        view('dashboard/settings/deposit', [
            'title'      => 'Caparra',
            'activeMenu' => 'deposit',
            'tenant'     => TenantResolver::current(),
        ], 'dashboard');
    }

    public function updateDeposit(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $data = $request->all();

        (new Tenant())->update($tenantId, [
            'deposit_enabled' => isset($data['deposit_enabled']) ? 1 : 0,
            'deposit_amount'  => !empty($data['deposit_amount']) ? (float)$data['deposit_amount'] : null,
        ]);

        flash('success', 'Impostazioni caparra aggiornate.');
        Response::redirect(url('dashboard/settings/deposit'));
    }
}
