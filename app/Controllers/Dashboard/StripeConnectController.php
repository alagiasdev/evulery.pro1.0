<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Models\Tenant;
use App\Services\AuditLog;

class StripeConnectController
{
    /**
     * Redirect to Stripe OAuth authorization page.
     */
    public function redirect(Request $request): void
    {
        if (gate_service('deposit', url('dashboard/settings/deposit'))) return;

        $tenant = TenantResolver::current();

        // Already connected?
        if (!empty($tenant['stripe_account_id']) && ($tenant['stripe_connect_status'] ?? 'none') === 'active') {
            flash('info', 'Il tuo account Stripe è già collegato.');
            Response::redirect(url('dashboard/settings/deposit'));
        }

        // Generate state token (anti-CSRF)
        $state = bin2hex(random_bytes(16));
        $_SESSION['stripe_connect_state'] = $state;

        $params = http_build_query([
            'response_type'  => 'code',
            'client_id'      => env('STRIPE_CONNECT_CLIENT_ID', ''),
            'scope'          => 'read_write',
            'redirect_uri'   => url('dashboard/settings/stripe/callback'),
            'state'          => $state,
            'stripe_user[email]'         => $tenant['email'] ?? '',
            'stripe_user[business_name]' => $tenant['name'] ?? '',
            'stripe_user[country]'       => 'IT',
        ]);

        Response::redirect('https://connect.stripe.com/oauth/authorize?' . $params);
    }

    /**
     * Handle Stripe OAuth callback — exchange code for stripe_user_id.
     */
    public function callback(Request $request): void
    {
        $state = $request->query('state', '');
        $code  = $request->query('code', '');
        $error = $request->query('error', '');

        // Verify state token
        if (!$state || $state !== ($_SESSION['stripe_connect_state'] ?? '')) {
            flash('danger', 'Token di sicurezza non valido. Riprova.');
            Response::redirect(url('dashboard/settings/deposit'));
        }
        unset($_SESSION['stripe_connect_state']);

        // User denied access
        if ($error) {
            flash('warning', 'Connessione Stripe annullata.');
            Response::redirect(url('dashboard/settings/deposit'));
        }

        if (!$code) {
            flash('danger', 'Codice di autorizzazione mancante. Riprova.');
            Response::redirect(url('dashboard/settings/deposit'));
        }

        try {
            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

            $response = \Stripe\OAuth::token([
                'grant_type' => 'authorization_code',
                'code'       => $code,
            ]);

            $stripeAccountId = $response->stripe_user_id;
            $tenantId = Auth::tenantId();

            (new Tenant())->update($tenantId, [
                'stripe_account_id'     => $stripeAccountId,
                'stripe_connect_status' => 'active',
                'stripe_connect_at'     => date('Y-m-d H:i:s'),
            ]);

            AuditLog::log(AuditLog::STRIPE_CONNECTED, "Account Stripe: {$stripeAccountId}", Auth::id(), $tenantId);

            // Refresh tenant in resolver
            TenantResolver::setCurrent((new Tenant())->findById($tenantId));

            flash('success', 'Account Stripe collegato con successo!');
        } catch (\Exception $e) {
            app_log('Stripe Connect OAuth error: ' . $e->getMessage(), 'error');
            flash('danger', 'Errore durante la connessione a Stripe. Riprova.');
        }

        Response::redirect(url('dashboard/settings/deposit'));
    }

    /**
     * Disconnect (deauthorize) Stripe account.
     */
    public function disconnect(Request $request): void
    {
        if (gate_service('deposit', url('dashboard/settings/deposit'))) return;

        $tenant = TenantResolver::current();
        $tenantId = Auth::tenantId();

        if (!empty($tenant['stripe_account_id'])) {
            try {
                \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
                \Stripe\OAuth::deauthorize([
                    'client_id'      => env('STRIPE_CONNECT_CLIENT_ID', ''),
                    'stripe_user_id' => $tenant['stripe_account_id'],
                ]);
            } catch (\Exception $e) {
                app_log('Stripe deauthorize error: ' . $e->getMessage(), 'error');
            }

            (new Tenant())->update($tenantId, [
                'stripe_account_id'     => null,
                'stripe_connect_status' => 'revoked',
                'stripe_connect_at'     => null,
                'deposit_enabled'       => 0,
            ]);

            AuditLog::log(AuditLog::STRIPE_DISCONNECTED, null, Auth::id(), $tenantId);
            TenantResolver::setCurrent((new Tenant())->findById($tenantId));

            flash('success', 'Account Stripe disconnesso. La caparra è stata disattivata.');
        }

        Response::redirect(url('dashboard/settings/deposit'));
    }
}
