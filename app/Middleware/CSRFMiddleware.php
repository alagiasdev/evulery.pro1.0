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

        if (!CSRF::validate($token)) {
            // DIAGNOSTIC (temporaneo): cosa vediamo quando CSRF fallisce?
            $sessionToken = $_SESSION['_csrf_token'] ?? null;
            $lastActivity = $_SESSION['_last_activity'] ?? null;
            $idleSec = $lastActivity ? (time() - $lastActivity) : null;
            $hasCookie = isset($_COOKIE[session_name()]);
            // Distingue "body perso in transito" da "manca solo _csrf":
            // post_keys = SOLO i nomi dei campi arrivati (nessun valore/dato sensibile);
            // content_len/type = cosa ha dichiarato il browser; ip = per incrociare con Cloudflare.
            $postKeys    = !empty($_POST) ? implode(',', array_keys($_POST)) : '(VUOTO)';
            $contentType = $_SERVER['CONTENT_TYPE'] ?? 'n/a';
            $contentLen  = $_SERVER['CONTENT_LENGTH'] ?? 'n/a';
            $clientIp    = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'n/a';
            app_log(sprintf(
                'CSRF FAIL uri=%s method=%s session_id=%s has_cookie=%s submitted_token=%s session_token=%s idle_sec=%s post_keys=[%s] content_len=%s content_type=%s ip=%s referer=%s ua=%s',
                $uri,
                $request->method(),
                session_id() ?: 'NONE',
                $hasCookie ? 'yes' : 'no',
                $token ? substr($token, 0, 8) . '...' : 'EMPTY',
                $sessionToken ? substr($sessionToken, 0, 8) . '...' : 'MISSING',
                $idleSec ?? 'n/a',
                $postKeys,
                $contentLen,
                $contentType,
                $clientIp,
                $_SERVER['HTTP_REFERER'] ?? 'n/a',
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 80)
            ), 'warning');

            Session::flash('alert_type', 'danger');
            Session::flash('alert_message', 'Token di sicurezza non valido. Riprova.');
            Response::back();
        }
    }
}
