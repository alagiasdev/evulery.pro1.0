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

            TenantResolver::setCurrent($tenant);
        }
    }
}
