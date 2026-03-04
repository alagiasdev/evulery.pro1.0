<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

class AdminMiddleware
{
    public function handle(Request $request): void
    {
        if (!Auth::isSuperAdmin()) {
            Response::error('Accesso non autorizzato.', 'FORBIDDEN', 403);
        }
    }
}
