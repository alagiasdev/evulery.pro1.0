<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\RememberMe;
use App\Core\Request;
use App\Core\Response;

class AuthMiddleware
{
    public function handle(Request $request): void
    {
        if (!Auth::isLoggedIn()) {
            // Sessione assente/persa (es. cookie di sessione azzerato da iOS Safari):
            // prova l'auto-login "Ricordami" prima di rimandare al login.
            if (RememberMe::attemptFromCookie()) {
                return;
            }
            Response::redirect(url('auth/login'));
        }
    }
}
