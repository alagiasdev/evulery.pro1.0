<?php

namespace App\Middleware;

use App\Core\CSRF;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class CSRFMiddleware
{
    public function handle(Request $request): void
    {
        if ($request->method() !== 'POST') {
            return;
        }

        // Skip CSRF for API/webhook endpoints
        $uri = $request->uri();
        if (str_starts_with($uri, '/api/')) {
            return;
        }

        $token = $request->input('_csrf');
        $sessionToken = Session::get('_csrf_token', '');

        if (!CSRF::validate($token)) {
            // Diagnostic log to investigate persistent CSRF errors
            app_log(sprintf(
                'CSRF FAIL on %s | session_id=%s | submitted_token=%s | session_token=%s | cookie=%s',
                $uri,
                substr(session_id(), 0, 8),
                $token ? substr($token, 0, 8) . '...' : 'null',
                $sessionToken ? substr($sessionToken, 0, 8) . '...' : 'empty',
                $_COOKIE[session_name()] ?? 'no_cookie'
            ), 'warning');

            Session::flash('alert_type', 'danger');
            Session::flash('alert_message', 'Token di sicurezza non valido. Riprova.');
            Response::back();
        }
    }
}
