<?php

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuditLog;
use App\Services\LoginThrottle;

class LoginController
{
    public function showForm(Request $request): void
    {
        if (Auth::isLoggedIn()) {
            $this->redirectByRole();
        }

        // Diagnostic: log session/cookie state and outgoing headers on GET /auth/login
        $params = session_get_cookie_params();
        $sentHeaders = headers_list();
        $setCookieHeaders = array_filter($sentHeaders, fn($h) => stripos($h, 'set-cookie') === 0);
        app_log(sprintf(
            'CSRF DEBUG GET | sid=%s | cookie_in=%s | secure=%s | samesite=%s | session_name=%s | set_cookie_out=%s',
            substr(session_id(), 0, 8),
            $_COOKIE[session_name()] ?? 'NO',
            $params['secure'] ? 'true' : 'false',
            $params['samesite'] ?? '',
            session_name(),
            empty($setCookieHeaders) ? 'NONE' : implode(' || ', $setCookieHeaders)
        ), 'info');

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
            AuditLog::log(AuditLog::LOGIN_SUCCESS, "Email: {$email}", Auth::id(), Auth::tenantId());
            $this->redirectByRole();
        }

        $throttle->recordFailedAttempt($email, $ip);
        AuditLog::log(AuditLog::LOGIN_FAILED, "Email: {$email}");

        flash('danger', 'Email o password non validi.');
        Session::flash('old_input', ['email' => $email]);
        Response::redirect(url('auth/login'));
    }

    public function logout(Request $request): void
    {
        // If impersonating, stop impersonation instead of logging out
        if (Auth::isImpersonating()) {
            $impersonatedName = Auth::user()['name'] ?? '';
            AuditLog::log(AuditLog::IMPERSONATION_END, "Fine impersonation di {$impersonatedName} (via logout)", Auth::originalAdminId());
            Auth::stopImpersonation();
            Response::redirect(url('admin/users'));
            return;
        }

        AuditLog::log(AuditLog::LOGOUT, null, Auth::id(), Auth::tenantId());
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
