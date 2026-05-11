<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

class ResellerMiddleware
{
    public function handle(Request $request): void
    {
        if (Auth::role() !== 'reseller') {
            Response::error('Accesso non autorizzato.', 'FORBIDDEN', 403);
        }
    }
}
