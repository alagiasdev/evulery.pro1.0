<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;

class TenantMiddleware
{
    public function handle(Request $request): void
    {
        $tenantId = Auth::tenantId();

        if (!$tenantId) {
            Response::error('Nessun tenant associato.', 'NO_TENANT', 403);
        }

        // Load tenant data if not already resolved.
        if (!TenantResolver::current()) {
            $db = Database::getInstance();
            // NB: nessun filtro is_active qui. Un ristorante disattivato manualmente
            // deve vedere la pagina "sospeso" GENTILE (come l'abbonamento scaduto),
            // non un 403 JSON grezzo. Il filtro sarebbe stato: is_active = 1.
            $stmt = $db->prepare('SELECT * FROM tenants WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $tenantId]);
            $tenant = $stmt->fetch();

            if (!$tenant) {
                Response::error('Tenant non trovato.', 'TENANT_NOT_FOUND', 403);
            }

            // Pagine accessibili anche quando sospeso (sospeso/logout/profilo).
            $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
            $allowedWhenSuspended = str_contains($uri, '/dashboard/suspended')
                                 || str_contains($uri, '/auth/logout')
                                 || str_contains($uri, '/dashboard/profile');

            // (1) Ristorante disattivato manualmente dal super admin → pagina sospeso.
            //     L'impersonation la bypassa: l'admin deve poter gestire il tenant spento.
            if (empty($tenant['is_active']) && !$allowedWhenSuspended && !Auth::isImpersonating()) {
                TenantResolver::setCurrent($tenant);
                Response::redirect(url('dashboard/suspended'));
            }

            // (2) Abbonamento scaduto → stessa pagina sospeso (comportamento invariato).
            $subStmt = $db->prepare(
                'SELECT current_period_end FROM subscriptions
                 WHERE tenant_id = :tid AND status IN ("active","trialing")
                 ORDER BY current_period_end DESC LIMIT 1'
            );
            $subStmt->execute(['tid' => $tenantId]);
            $sub = $subStmt->fetch();

            $subscriptionExpired = $sub && $sub['current_period_end'] && strtotime($sub['current_period_end']) < time();

            if ($subscriptionExpired && !$allowedWhenSuspended && !Auth::isImpersonating()) {
                TenantResolver::setCurrent($tenant);
                Response::redirect(url('dashboard/suspended'));
            }

            TenantResolver::setCurrent($tenant);
        }
    }
}
