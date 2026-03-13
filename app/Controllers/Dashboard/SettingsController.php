<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Core\Validator;
use App\Models\Tenant;
use App\Services\AuditLog;

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

        $v = Validator::make($data)
            ->required('name', 'Nome ristorante')
            ->required('email', 'Email')
            ->email('email', 'Email')
            ->between('table_duration', 15, 300, 'Durata tavolo (minuti)')
            ->between('time_step', 5, 120, 'Intervallo orario (minuti)');

        if (isset($data['cancellation_policy']) && mb_strlen($data['cancellation_policy']) > 2000) {
            flash('danger', 'La policy di cancellazione non può superare 2000 caratteri.');
            Response::redirect(url('dashboard/settings'));
        }

        if ($v->fails()) {
            flash('danger', $v->firstError());
            Response::redirect(url('dashboard/settings'));
        }

        // Validate segment thresholds: occasionale < abituale < vip
        $segOcc = max(1, (int)($data['segment_occasionale'] ?? 2));
        $segAbi = max(2, (int)($data['segment_abituale'] ?? 4));
        $segVip = max(3, (int)($data['segment_vip'] ?? 10));

        if ($segOcc >= $segAbi || $segAbi >= $segVip) {
            flash('danger', 'Le soglie segmento devono essere crescenti: Occasionale < Abituale < VIP.');
            Response::redirect(url('dashboard/settings'));
        }

        (new Tenant())->update($tenantId, [
            'name'                 => $data['name'] ?? '',
            'email'                => $data['email'] ?? '',
            'phone'                => $data['phone'] ?? null,
            'address'              => $data['address'] ?? null,
            'cancellation_policy'  => $data['cancellation_policy'] ?? null,
            'table_duration'       => (int)($data['table_duration'] ?? 90),
            'time_step'            => (int)($data['time_step'] ?? 30),
            'segment_occasionale'  => $segOcc,
            'segment_abituale'     => $segAbi,
            'segment_vip'          => $segVip,
        ]);

        AuditLog::log(AuditLog::SETTINGS_UPDATED, null, Auth::id(), $tenantId);

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
