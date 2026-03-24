<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\TenantResolver;

class SuspendedController
{
    public function index(Request $request): void
    {
        $tenant = TenantResolver::current();
        $tenantId = (int)$tenant['id'];

        // Fetch subscription details
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT s.*, p.name as plan_name
             FROM subscriptions s
             LEFT JOIN plans p ON p.id = s.plan_id
             WHERE s.tenant_id = :tid AND s.status IN ("active","trialing")
             ORDER BY s.current_period_end DESC LIMIT 1'
        );
        $stmt->execute(['tid' => $tenantId]);
        $subscription = $stmt->fetch();

        $expiredDate = $subscription['current_period_end'] ?? null;
        $daysSinceExpiry = $expiredDate ? (int)ceil((time() - strtotime($expiredDate)) / 86400) : 0;
        $planName = $subscription['plan_name'] ?? ($tenant['plan'] ?? 'Base');

        view('dashboard/suspended', [
            'title'           => 'Abbonamento Sospeso',
            'activeMenu'      => '',
            'subscription'    => $subscription,
            'expiredDate'     => $expiredDate,
            'daysSinceExpiry' => $daysSinceExpiry,
            'planName'        => $planName,
            'supportEmail'    => env('SUPPORT_EMAIL', 'supporto@evulery.it'),
            'supportPhone'    => env('SUPPORT_PHONE', ''),
        ], 'dashboard');
    }
}
