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
            // --- LOGIN "auto-guarigione" (fix iOS Safari, 2026-08-20) ---
            // Se il CSRF fallisce sul LOGIN perche' NON esiste una sessione con token
            // (cookie di sessione perso: bfcache/snapshot/icona-home/cookie azzerati da
            // Safari), il CSRF non e' tecnicamente applicabile. L'autenticazione vera
            // (email+password + rate-limit) resta INTATTA: proseguiamo al LoginController
            // invece di mostrare l'errore rosso. Il CSRF resta pieno quando una sessione
            // con token esiste (flusso normale) e su ogni altro endpoint. Il login-CSRF
            // (unica cosa qui rilassata) e' a rischio trascurabile per una dashboard B2B.
            if ($uri === '/auth/login' && empty($_SESSION['_csrf_token'])) {
                app_log(
                    'CSRF login recovery: nessuna sessione (cookie perso), proseguo al login. ip='
                    . ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'n/a'),
                    'info'
                );
                return;
            }

            // Un fallimento CSRF e' ormai un evento raro: la causa nota — cookie di
            // sessione perso su iOS Safari — la intercetta il ramo qui sopra dal
            // 2026-08-20. Se ricompare va indagato, quindi resta una riga di log, ma
            // ridotta all'osso e senza dati personali: niente IP, niente user agent,
            // niente frammenti di token.
            //
            // La diagnostica estesa che stava qui (aperta il 2026-04-24, ampliata il
            // 17/07) e' stata rimossa il 2026-09-10 perche' aveva risposto alla sua
            // domanda: l'ultimo CSRF FAIL registrato in produzione e' del 19/08 alle
            // 20:04, il giorno PRIMA della correzione, e diceva has_cookie=no con il
            // corpo della richiesta arrivato intero. Da allora, silenzio.
            app_log(sprintf(
                'CSRF non valido: uri=%s has_cookie=%s session_token=%s',
                $uri,
                isset($_COOKIE[session_name()]) ? 'yes' : 'no',
                empty($_SESSION['_csrf_token']) ? 'MISSING' : 'ok'
            ), 'warning');

            Session::flash('alert_type', 'danger');
            Session::flash('alert_message', 'Token di sicurezza non valido. Riprova.');
            Response::back();
        }
    }
}
