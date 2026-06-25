<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\PushSubscription;

class PushController
{
    /**
     * Save a push subscription for the current user/tenant.
     */
    public function subscribe(Request $request): void
    {
        if (!tenant_can('push_notifications')) {
            Response::json(['error' => 'Servizio non disponibile.'], 403);
        }

        // In impersonation NON registriamo il dispositivo: l'admin sta solo
        // "guardando" il tenant, non è un suo device. Senza questa guardia i
        // browser dell'admin finirebbero registrati sotto i tenant impersonati
        // (e riceverebbero le loro notifiche). La sicurezza è qui, lato server.
        if (Auth::isImpersonating()) {
            Response::json(['ok' => true, 'skipped' => 'impersonation']);
            return;
        }

        $tenantId = Auth::tenantId();
        $userId = Auth::id();
        $data = $request->all();

        $endpoint = trim($data['endpoint'] ?? '');
        $p256dh = trim($data['p256dh'] ?? '');
        $auth = trim($data['auth'] ?? '');

        if (!$endpoint || !$p256dh || !$auth) {
            Response::json(['error' => 'Dati subscription incompleti.'], 422);
        }

        // Basic endpoint validation
        if (!filter_var($endpoint, FILTER_VALIDATE_URL)) {
            Response::json(['error' => 'Endpoint non valido.'], 422);
        }

        $subscription = [
            'endpoint'   => $endpoint,
            'p256dh'     => $p256dh,
            'auth'       => $auth,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ];

        (new PushSubscription())->subscribe($tenantId, $userId, $subscription);
        Response::json(['ok' => true]);
    }

    /**
     * Remove a push subscription.
     */
    public function unsubscribe(Request $request): void
    {
        // In impersonation non tocchiamo i device del ristoratore reale.
        if (Auth::isImpersonating()) {
            Response::json(['ok' => true, 'skipped' => 'impersonation']);
            return;
        }

        $tenantId = Auth::tenantId();
        $endpoint = trim($request->all()['endpoint'] ?? '');

        if ($endpoint) {
            (new PushSubscription())->unsubscribeByTenant($endpoint, $tenantId);
        }

        Response::json(['ok' => true]);
    }

    /**
     * Return the VAPID public key for client-side subscription.
     */
    public function vapidKey(Request $request): void
    {
        if (!tenant_can('push_notifications')) {
            Response::json(['error' => 'Servizio non disponibile.'], 403);
        }

        $key = env('VAPID_PUBLIC_KEY', '');
        Response::json(['key' => $key]);
    }
}
