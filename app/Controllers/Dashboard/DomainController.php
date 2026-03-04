<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Models\Tenant;

class DomainController
{
    public function index(Request $request): void
    {
        view('dashboard/settings/domain', [
            'title'      => 'Dominio Personalizzato',
            'activeMenu' => 'domain',
            'tenant'     => TenantResolver::current(),
        ], 'dashboard');
    }

    public function update(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $domain = trim($request->input('custom_domain', ''));

        if ($domain === '') {
            (new Tenant())->update($tenantId, [
                'custom_domain' => null,
                'domain_status' => 'none',
                'cname_target'  => null,
            ]);
            flash('success', 'Dominio personalizzato rimosso.');
        } else {
            $cnameTarget = 'app.evulery.pro';
            (new Tenant())->update($tenantId, [
                'custom_domain' => $domain,
                'domain_status' => 'dns_pending',
                'cname_target'  => $cnameTarget,
            ]);
            flash('success', "Dominio impostato. Configura un record CNAME: {$domain} -> {$cnameTarget}");
        }

        Response::redirect(url('dashboard/settings/domain'));
    }

    public function verify(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $tenant = TenantResolver::current();

        if (!$tenant['custom_domain'] || !$tenant['cname_target']) {
            flash('danger', 'Nessun dominio configurato.');
            Response::redirect(url('dashboard/settings/domain'));
        }

        // Check DNS
        $records = @dns_get_record($tenant['custom_domain'], DNS_CNAME);
        $verified = false;

        if ($records) {
            foreach ($records as $record) {
                if (isset($record['target']) && $record['target'] === $tenant['cname_target']) {
                    $verified = true;
                    break;
                }
            }
        }

        if ($verified) {
            (new Tenant())->update($tenantId, ['domain_status' => 'linked']);
            flash('success', 'Dominio verificato e collegato!');
        } else {
            flash('warning', 'DNS non ancora propagato. Il record CNAME non punta a ' . $tenant['cname_target']);
        }

        Response::redirect(url('dashboard/settings/domain'));
    }
}
