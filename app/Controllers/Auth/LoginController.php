<?php

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\LoginThrottle;

class LoginController
{
    public function showForm(Request $request): void
    {
        if (Auth::isLoggedIn()) {
            $this->redirectByRole();
        }

        view('auth/login', ['title' => 'Accedi'], 'auth');
    }

    public function login(Request $request): void
    {
        $email = trim($request->input('email', ''));
        $password = $request->input('password', '');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        if (!$email || !$password) {
            flash('danger', 'Inserisci email e password.');
            Session::flash('old_input', ['email' => $email]);
            Response::redirect(url('auth/login'));
        }

        // Brute force protection
        $throttle = new LoginThrottle();
        if ($throttle->isLocked($email, $ip)) {
            $remaining = $throttle->remainingSeconds($email);
            $minutes = (int)ceil($remaining / 60);
            flash('danger', "Troppi tentativi di accesso. Riprova tra {$minutes} minuti.");
            Session::flash('old_input', ['email' => $email]);
            Response::redirect(url('auth/login'));
        }

        if (Auth::attempt($email, $password)) {
            $throttle->clearAttempts($email);
            $this->redirectByRole();
        }

        $throttle->recordFailedAttempt($email, $ip);

        flash('danger', 'Email o password non validi.');
        Session::flash('old_input', ['email' => $email]);
        Response::redirect(url('auth/login'));
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        flash('success', 'Logout effettuato con successo.');
        Response::redirect(url('auth/login'));
    }

    private function redirectByRole(): void
    {
        if (Auth::isSuperAdmin()) {
            Response::redirect(url('admin'));
        }
        Response::redirect(url('dashboard'));
    }
}
