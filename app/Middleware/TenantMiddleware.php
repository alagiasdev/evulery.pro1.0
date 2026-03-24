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

        // Load tenant data if not already resolved
        if (!TenantResolver::current()) {
            $db = Database::getInstance();
            $stmt = $db->prepare('SELECT * FROM tenants WHERE id = :id AND is_active = 1 LIMIT 1');
            $stmt->execute(['id' => $tenantId]);
            $tenant = $stmt->fetch();

            if (!$tenant) {
                Response::error('Tenant non trovato o disattivato.', 'TENANT_INACTIVE', 403);
            }

            // Check subscription expiry — redirect to suspended page (keep logged in)
            $subStmt = $db->prepare(
                'SELECT current_period_end FROM subscriptions
                 WHERE tenant_id = :tid AND status IN ("active","trialing")
                 ORDER BY current_period_end DESC LIMIT 1'
            );
            $subStmt->execute(['tid' => $tenantId]);
            $sub = $subStmt->fetch();

            $subscriptionExpired = $sub && $sub['current_period_end'] && strtotime($sub['current_period_end']) < time();

            // Allow access to suspended page, logout, and profile even if expired
            $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
            $allowedWhenExpired = str_contains($uri, '/dashboard/suspended')
                               || str_contains($uri, '/auth/logout')
                               || str_contains($uri, '/dashboard/profile');

            if ($subscriptionExpired && !$allowedWhenExpired && !Auth::isImpersonating()) {
                TenantResolver::setCurrent($tenant);
                Response::redirect(url('dashboard/suspended'));
            }

            TenantResolver::setCurrent($tenant);
        }
    }
}
