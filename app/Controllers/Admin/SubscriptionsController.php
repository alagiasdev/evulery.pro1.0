<?php

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;

class SubscriptionsController
{
    public function index(Request $request): void
    {
        $db = Database::getInstance();
        $subscriptions = $db->query(
            'SELECT s.*, t.name as tenant_name, t.slug
             FROM subscriptions s
             JOIN tenants t ON s.tenant_id = t.id
             ORDER BY s.created_at DESC'
        )->fetchAll();

        view('admin/subscriptions', [
            'title'         => 'Abbonamenti',
            'activeMenu'    => 'subscriptions',
            'subscriptions' => $subscriptions,
        ], 'admin');
    }
}
