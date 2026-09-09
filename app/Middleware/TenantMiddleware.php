<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\RememberMe;
use App\Core\Session;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;

class TenantMiddleware
{
    public function handle(Request $request): void
    {
        $tenantId = Auth::tenantId();

        if (!$tenantId) {
            // Utente autenticato ma senza ristorante associato: succede a super
            // admin e reseller che finiscono su /dashboard — tipicamente aprendo
            // la PWA installata sul telefono, che parte sempre dalla start_url del
            // manifest. Rispondere con un 403 JSON li lascia davanti a una pagina
            // bianca con l'errore, senza un link per uscirne: le NAVIGAZIONI vanno
            // quindi rimandate all'area di competenza. Le chiamate JSON/AJAX
            // continuano a ricevere l'errore strutturato di prima.
            $this->redirectHomeByRole($request);
            Response::error('Nessun tenant associato.', 'NO_TENANT', 403);
        }

        // Load tenant data if not already resolved.
        if (!TenantResolver::current()) {
            $db = Database::getInstance();
            // NB: nessun filtro is_active qui. Un ristorante disattivato manualmente
            // deve vedere la pagina "sospeso" GENTILE (come l'abbonamento scaduto),
            // non un 403 JSON grezzo. Il filtro sarebbe stato: is_active = 1.
            $stmt = $db->prepare('SELECT * FROM tenants WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $tenantId]);
            $tenant = $stmt->fetch();

            if (!$tenant) {
                Response::error('Tenant non trovato.', 'TENANT_NOT_FOUND', 403);
            }

            // Pagine accessibili anche quando sospeso (sospeso/logout/profilo).
            $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
            $allowedWhenSuspended = str_contains($uri, '/dashboard/suspended')
                                 || str_contains($uri, '/auth/logout')
                                 || str_contains($uri, '/dashboard/profile');

            // (1) Ristorante disattivato manualmente dal super admin → pagina sospeso.
            //     L'impersonation la bypassa: l'admin deve poter gestire il tenant spento.
            if (empty($tenant['is_active']) && !$allowedWhenSuspended && !Auth::isImpersonating()) {
                TenantResolver::setCurrent($tenant);
                Response::redirect(url('dashboard/suspended'));
            }

            // (2) Abbonamento scaduto → stessa pagina sospeso (comportamento invariato).
            $subStmt = $db->prepare(
                'SELECT current_period_end FROM subscriptions
                 WHERE tenant_id = :tid AND status IN ("active","trialing")
                 ORDER BY current_period_end DESC LIMIT 1'
            );
            $subStmt->execute(['tid' => $tenantId]);
            $sub = $subStmt->fetch();

            $subscriptionExpired = $sub && $sub['current_period_end'] && strtotime($sub['current_period_end']) < time();

            if ($subscriptionExpired && !$allowedWhenSuspended && !Auth::isImpersonating()) {
                TenantResolver::setCurrent($tenant);
                Response::redirect(url('dashboard/suspended'));
            }

            TenantResolver::setCurrent($tenant);
        }
    }

    /**
     * Manda l'utente senza tenant dove ha senso per il suo ruolo. Ritorna
     * (senza fare nulla) se la richiesta si aspetta JSON: in quel caso il
     * chiamante prosegue con Response::error.
     */
    private function redirectHomeByRole(Request $request): void
    {
        if ($this->expectsJson($request)) {
            return;
        }

        if (Auth::isSuperAdmin()) {
            Response::redirect(url('admin'));
        }
        if (Auth::role() === 'reseller') {
            Response::redirect(url('reseller'));
        }

        // Ruolo senza un'area propria e senza ristorante: e' un dato anomalo
        // (account non ancora collegato, o collegamento perso). Qui non basta
        // il logout: va tolto anche il cookie "Ricordami", altrimenti la pagina
        // di login lo riconosce, rifa' l'auto-login e rimanda su /dashboard —
        // rimbalzo infinito (verificato prima di aggiungere questa riga).
        RememberMe::clear();
        Auth::logout();
        // logout() distrugge la sessione: senza riaprirla il messaggio andrebbe
        // perso e l'utente si ritroverebbe al login senza sapere perche'.
        // Serve anche un ID nuovo, altrimenti la risposta porta solo il cookie
        // di cancellazione emesso da destroy() e il flash non arriva a destinazione.
        Session::start();
        Session::regenerate();
        flash('danger', 'Il tuo account non risulta associato a nessun ristorante. Contatta il supporto.');
        Response::redirect(url('auth/login'));
    }

    /**
     * Distingue una navigazione del browser da una chiamata JSON/AJAX: le
     * seconde devono continuare a ricevere l'errore strutturato, non un 302.
     */
    private function expectsJson(Request $request): bool
    {
        if ($request->isJson()) {
            return true;
        }
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        $xhr    = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
        $path   = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);

        return $xhr
            || str_contains($accept, 'application/json')
            || str_ends_with($path, '/json');
    }
}
