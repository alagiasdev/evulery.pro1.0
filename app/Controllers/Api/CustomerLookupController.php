<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Models\Customer;
use App\Models\Tenant;
use App\Services\RateLimit;

/**
 * Public, scoped lookup of a customer by email within a tenant.
 *
 * Used by the booking widget step-4 to decide which UI state to show
 * for the privacy/marketing consent block:
 *   - cliente nuovo o NULL  → checkbox da spuntare (Stato A)
 *   - cliente con consenso 1 → riga sobria "sei iscritto" (Stato B)
 *   - cliente con consenso 0 → checkbox da spuntare, opportunità re-opt-in (Stato C)
 *
 * Privacy: ritorna SOLO has_birthday + marketing_consent. Mai dati
 * personali (nome, telefono, ecc.) — l'endpoint è pubblico e scopable.
 *
 * Rate limit dedicato (10 req/min/IP) sopra al limite globale (60 req/min/IP)
 * per mitigare l'enumerazione email da brute-force. Caso d'uso normale: 1-2
 * lookup nel flow di prenotazione → ben sotto il limite per utenti reali.
 */
class CustomerLookupController
{
    /** Rate limit: max 10 chiamate al minuto per IP. */
    private const LOOKUP_MAX_PER_MIN = 10;
    private const LOOKUP_WINDOW_SEC  = 60;
    /** Chiave breve per stare nel limite VARCHAR(10) di rate_limits.endpoint. */
    private const LOOKUP_KEY         = 'cust_lkp';

    public function lookup(Request $request): void
    {
        // Rate limit dedicato per scoraggiare enumerazione email
        $ip = $request->ip();
        $limiter = new RateLimit();
        if (!$limiter->checkCustom($ip, self::LOOKUP_KEY, self::LOOKUP_MAX_PER_MIN, self::LOOKUP_WINDOW_SEC)) {
            Response::error('Troppe richieste. Riprova tra qualche istante.', 'RATE_LIMITED', 429);
            return;
        }
        $limiter->recordCustom($ip, self::LOOKUP_KEY);

        $slug = (string)$request->param('slug');
        $tenant = (new Tenant())->findBySlug($slug);

        if (!$tenant || !$tenant['is_active']) {
            Response::error('Ristorante non trovato.', 'TENANT_NOT_FOUND', 404);
            return;
        }

        $email = trim((string)$request->query('email', ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Risposta neutra: cliente "non trovato", nessun leak di stato
            Response::json([
                'has_birthday'      => false,
                'marketing_consent' => null,
            ]);
            return;
        }

        $customer = (new Customer())->findByTenantAndEmail((int)$tenant['id'], $email);

        if (!$customer) {
            Response::json([
                'has_birthday'      => false,
                'marketing_consent' => null,
            ]);
            return;
        }

        // Customer trovato: ritorna solo i 2 flag necessari al widget
        Response::json([
            'has_birthday'      => !empty($customer['birthday']),
            'marketing_consent' => $customer['marketing_consent'] !== null
                ? (int)$customer['marketing_consent']
                : null,
        ]);
    }
}
