<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Models\Tenant;
use App\Services\MailService;

class DomainController
{
    public function index(Request $request): void
    {
        $tenant = TenantResolver::current();
        $canUseDomain = (new Tenant())->canUseService((int)$tenant['id'], 'custom_domain');

        view('dashboard/settings/domain', [
            'title'         => 'Dominio Personalizzato',
            'activeMenu'    => 'domain',
            'tenant'        => $tenant,
            'canUseDomain'  => $canUseDomain,
        ], 'dashboard');
    }

    public function update(Request $request): void
    {
        if (gate_service('custom_domain', url('dashboard/settings/domain'))) return;

        $tenantId = Auth::tenantId();
        $domain = strtolower(trim($request->input('custom_domain', '')));
        // Normalize: strip protocol and trailing slash if user pasted a full URL
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = rtrim($domain, '/');

        if ($domain === '') {
            (new Tenant())->update($tenantId, [
                'custom_domain' => null,
                'domain_status' => 'none',
                'cname_target'  => null,
            ]);
            flash('success', 'Dominio personalizzato rimosso.');
        } else {
            if (!$this->isValidDomain($domain)) {
                flash('danger', 'Dominio non valido. Usa un formato tipo: prenota.ristorantemario.it');
                Response::redirect(url('dashboard/settings/domain'));
            }

            $cnameTarget = $this->getCnameTarget();
            (new Tenant())->update($tenantId, [
                'custom_domain' => $domain,
                'domain_status' => 'dns_pending',
                'cname_target'  => $cnameTarget,
            ]);
            flash('success', "Dominio salvato. Configura un record CNAME: {$domain} → {$cnameTarget}");
        }

        Response::redirect(url('dashboard/settings/domain'));
    }

    public function verify(Request $request): void
    {
        if (gate_service('custom_domain', url('dashboard/settings/domain'))) return;

        $tenantId = Auth::tenantId();
        $tenant = TenantResolver::current();

        if (!$tenant['custom_domain']) {
            flash('danger', 'Nessun dominio configurato.');
            Response::redirect(url('dashboard/settings/domain'));
        }

        $domain = $tenant['custom_domain'];
        $cnameTarget = $this->getCnameTarget();
        $currentStatus = $tenant['domain_status'] ?? 'dns_pending';

        // Already active → nothing to do
        if (in_array($currentStatus, ['active', 'linked'], true)) {
            flash('info', 'Il dominio è già attivo.');
            Response::redirect(url('dashboard/settings/domain'));
        }

        // Step 2: DNS already verified, check if HTTPS is reachable
        if ($currentStatus === 'dns_ok') {
            if ($this->checkHttps($domain)) {
                (new Tenant())->update($tenantId, ['domain_status' => 'active']);
                flash('success', 'Dominio completamente attivo! I clienti possono ora visitarlo via HTTPS.');
            } else {
                flash('warning', 'Il certificato SSL non è ancora pronto. Di solito entro 24h dall\'attivazione tecnica. Riprova più tardi.');
            }
            Response::redirect(url('dashboard/settings/domain'));
        }

        // Step 1: verify DNS CNAME
        $verified = false;
        $records = @dns_get_record($domain, DNS_CNAME);
        if ($records) {
            foreach ($records as $record) {
                $target = rtrim($record['target'] ?? '', '.');
                if (strcasecmp($target, $cnameTarget) === 0) {
                    $verified = true;
                    break;
                }
            }
        }

        if ($verified) {
            (new Tenant())->update($tenantId, ['domain_status' => 'dns_ok']);
            $this->notifyAdminDomainReady($tenant, $domain);
            flash('success', 'DNS verificato! Stiamo configurando il dominio sul server. Riceverai una email quando sarà pienamente attivo (entro 24h).');
        } else {
            flash('warning', "DNS non ancora propagato. Il record CNAME di {$domain} non punta a {$cnameTarget}. Controlla il pannello DNS e riprova tra 15-30 minuti.");
        }

        Response::redirect(url('dashboard/settings/domain'));
    }

    /**
     * Resolve the CNAME target from env, falling back to the APP_URL host.
     */
    private function getCnameTarget(): string
    {
        $explicit = env('CUSTOM_DOMAIN_CNAME_TARGET');
        if ($explicit) return $explicit;

        $appHost = parse_url((string)env('APP_URL', ''), PHP_URL_HOST);
        return $appHost ?: 'dash.evulery.it';
    }

    /**
     * Basic domain format validation. Accepts sub.domain.tld style.
     */
    private function isValidDomain(string $domain): bool
    {
        if (strlen($domain) > 253) return false;
        return (bool)preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i', $domain);
    }

    /**
     * Quick HTTPS reachability check.
     * Returns true if the domain serves a valid HTTPS response (any 2xx/3xx).
     */
    private function checkHttps(string $domain): bool
    {
        $url = 'https://' . $domain . '/';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY         => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_RETURNTRANSFER => true,
        ]);
        curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code >= 200 && $code < 400;
    }

    /**
     * Notify the admin via email that a tenant has passed DNS verification
     * and now needs the domain added as an alias on cPanel.
     */
    private function notifyAdminDomainReady(array $tenant, string $domain): void
    {
        $supportEmail = env('SUPPORT_EMAIL', 'info@evulery.it');
        $appName = env('APP_NAME', 'Evulery');
        $subject = "[{$appName}] Nuovo dominio da attivare su cPanel: {$domain}";

        $tenantName = htmlspecialchars($tenant['name'] ?? '-', ENT_QUOTES);
        $tenantSlug = htmlspecialchars($tenant['slug'] ?? '-', ENT_QUOTES);
        $tenantId = (int)($tenant['id'] ?? 0);
        $domainEsc = htmlspecialchars($domain, ENT_QUOTES);

        $body = <<<HTML
<h2 style="color:#00844A;">Nuovo dominio da configurare</h2>
<p>Un tenant ha appena verificato il proprio dominio personalizzato. Serve la tua azione su cPanel per completare l'attivazione.</p>

<table style="background:#f5f6fa;padding:12px;border-radius:8px;width:100%;border-collapse:collapse;margin:1rem 0;">
    <tr><td style="padding:6px 10px;"><strong>Tenant</strong></td><td style="padding:6px 10px;">{$tenantName} (slug: {$tenantSlug}, id: {$tenantId})</td></tr>
    <tr><td style="padding:6px 10px;"><strong>Dominio</strong></td><td style="padding:6px 10px;"><code>{$domainEsc}</code></td></tr>
    <tr><td style="padding:6px 10px;"><strong>Stato attuale</strong></td><td style="padding:6px 10px;">dns_ok (CNAME verificato)</td></tr>
</table>

<h3 style="margin-top:1.5rem;">Cosa fare adesso</h3>
<ol>
    <li>Accedi a cPanel dell'account principale</li>
    <li>Vai su <strong>Aliases</strong> (o "Domini parcheggiati")</li>
    <li>Aggiungi <code>{$domainEsc}</code> come alias</li>
    <li>Aspetta che AutoSSL emetta il certificato (in genere 1-4 ore)</li>
    <li>Il tenant cliccherà "Verifica" di nuovo e lo stato passerà automaticamente a "active"</li>
</ol>

<p style="color:#6c757d;font-size:.85rem;margin-top:2rem;">Email automatica da {$appName}.</p>
HTML;

        try {
            (new MailService())->send($supportEmail, $subject, $body);
        } catch (\Throwable $e) {
            // Don't break the verify flow if mail fails; just log
            app_log("Failed to send domain ready notification to admin: " . $e->getMessage(), 'warning');
        }
    }
}
