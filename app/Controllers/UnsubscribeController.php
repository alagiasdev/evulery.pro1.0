<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;

class UnsubscribeController
{
    /**
     * GET: mostra SOLO la pagina di conferma. NESSUNA mutazione qui: i link nelle
     * email vengono spesso pre-caricati/scansionati (antivirus, proxy anti-phishing,
     * prefetch del browser), e un GET con side-effect distruttivo disiscriverebbe
     * clienti senza che abbiano mai cliccato. La disiscrizione avviene solo via POST
     * (confirm), protetto da CSRF.
     */
    public function show(Request $request): void
    {
        $token  = (string) $request->param('token');
        $record = $this->findByToken($token);

        view('email/unsubscribe', [
            'token'      => $token,
            'tenantName' => $record['tenant_name'] ?? '',
            'state'      => $record ? 'confirm' : 'invalid',
        ], null);
    }

    /**
     * POST: esegue la disiscrizione + revoca consenso marketing (GDPR). Protetto da
     * CSRF (middleware 'csrf' sulla rotta) → prefetch/scanner non possono scatenarlo.
     */
    public function confirm(Request $request): void
    {
        $token  = (string) $request->param('token');
        $record = $this->findByToken($token);

        if ($record) {
            Database::getInstance()->prepare(
                "UPDATE customers
                 SET unsubscribed = 1, unsubscribed_at = NOW(),
                     marketing_consent = 0, marketing_consent_at = NOW(),
                     marketing_consent_source = 'unsubscribe_link'
                 WHERE tenant_id = :tid AND email = :email"
            )->execute(['tid' => $record['tenant_id'], 'email' => $record['email']]);
        }

        view('email/unsubscribe', [
            'token'      => $token,
            'tenantName' => $record['tenant_name'] ?? '',
            'state'      => $record ? 'done' : 'invalid',
        ], null);
    }

    /**
     * Risolve il token di disiscrizione. Ritorna [tenant_id, email, tenant_name] o null.
     */
    private function findByToken(string $token): ?array
    {
        if ($token === '') {
            return null;
        }
        $stmt = Database::getInstance()->prepare(
            'SELECT u.tenant_id, u.email, t.name AS tenant_name
             FROM email_unsubscribes u
             JOIN tenants t ON u.tenant_id = t.id
             WHERE u.token = :token LIMIT 1'
        );
        $stmt->execute(['token' => $token]);
        $record = $stmt->fetch();
        return $record ?: null;
    }
}
