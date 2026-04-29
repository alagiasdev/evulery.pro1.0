<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Models\Customer;
use App\Models\Tenant;

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
 */
class CustomerLookupController
{
    public function lookup(Request $request): void
    {
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
