<?php

namespace App\Controllers\Dashboard;

use App\Core\Request;
use App\Core\TenantResolver;

class HelpController
{
    /**
     * GET /dashboard/help — Guida interna per i ristoratori.
     */
    public function index(Request $request): void
    {
        view('dashboard/help/index', [
            'title'      => 'Guida Evulery',
            'activeMenu' => 'help',
            'tenant'     => TenantResolver::current(),
        ], 'dashboard');
    }
}
