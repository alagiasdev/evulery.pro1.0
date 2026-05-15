<?php

namespace App\Controllers\Booking;

use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Models\Reservation;
use App\Models\Tenant;

class BookingController
{
    public function show(Request $request): void
    {
        $slug = $request->param('slug');
        $tenantModel = new Tenant();
        $tenant = $tenantModel->findBySlug($slug);

        if (!$tenant || !$tenant['is_active']) {
            Response::notFound();
        }

        // Check subscription expiry — show suspended page
        $expiredSub = $tenantModel->getExpiredSubscription((int)$tenant['id']);
        if ($expiredSub) {
            $isEmbed = $request->isEmbed();
            if ($isEmbed) {
                view('booking/suspended-embed', [
                    'tenantName' => $tenant['name'],
                ]);
            } else {
                view('booking/suspended', [
                    'tenantName'    => $tenant['name'],
                    'tenantLogo'    => $tenant['logo_url'],
                    'tenantPhone'   => $tenant['phone'] ?? '',
                    'tenantEmail'   => $tenant['email'] ?? '',
                    'tenantAddress' => $tenant['address'] ?? '',
                ]);
            }
            return;
        }

        TenantResolver::setCurrent($tenant);

        // Disable deposit display if plan doesn't include deposit service
        if ($tenant['deposit_enabled'] && !$tenantModel->canUseService((int)$tenant['id'], 'deposit')) {
            $tenant['deposit_enabled'] = 0;
        }

        $isEmbed = $request->isEmbed();
        $layout = $isEmbed ? 'embed' : 'booking';

        // Fasce orarie per la caparra condizionale (il widget mostra la caparra
        // solo per giorni/fasce in cui è attiva)
        $mealCategories = (new \App\Models\MealCategory())->findActiveByTenant((int)$tenant['id']);

        view('booking/widget', [
            'tenant'         => $tenant,
            'tenantName'     => $tenant['name'],
            'tenantLogo'     => $tenant['logo_url'],
            'slug'           => $slug,
            'isEmbed'        => $isEmbed,
            'petFriendly'    => !empty($tenant['pet_friendly']),
            'kidsFriendly'   => !empty($tenant['kids_friendly']),
            'mealCategories' => $mealCategories,
        ], $layout);
    }

    public function success(Request $request): void
    {
        $slug = $request->param('slug');
        $tenant = (new Tenant())->findBySlug($slug);

        if (!$tenant) {
            Response::notFound();
        }

        // Try to retrieve reservation details from Stripe session
        $reservation = null;
        $depositPaid = false;
        $sessionId = $request->query('session_id', '');

        // Recupera la prenotazione dalla sessione Stripe — vale sia per il pagamento
        // caparra ('stripe') sia per la registrazione carta a garanzia ('guarantee').
        if ($sessionId && !empty($tenant['stripe_sk'])) {
            try {
                $tenantKey = decrypt_value($tenant['stripe_sk']);
                if (!$tenantKey) throw new \RuntimeException('Invalid stripe key');
                \Stripe\Stripe::setApiKey($tenantKey);
                $session = \Stripe\Checkout\Session::retrieve($sessionId);
                $reservationId = $session->metadata->reservation_id ?? null;
                if ($reservationId) {
                    $reservation = (new Reservation())->findWithCustomer((int)$reservationId);
                    $depositPaid = (($session->payment_status ?? '') === 'paid');
                }
            } catch (\Exception $e) {
                // Silent fail — show generic confirmation
            }
        }

        view('booking/confirmation', [
            'tenant'       => $tenant,
            'tenantName'   => $tenant['name'],
            'tenantLogo'   => $tenant['logo_url'],
            'reservation'  => $reservation,
            'depositPaid'  => $depositPaid,
            'petFriendly'  => !empty($tenant['pet_friendly']),
            'kidsFriendly' => !empty($tenant['kids_friendly']),
        ], 'booking');
    }

    /**
     * Pagina di completamento prenotazione — destinazione della CTA nell'email
     * "Prenotazione in attesa". Legge lo stato reale della prenotazione e:
     *  - confermata  → schermata "già confermata"
     *  - pending     → rigenera la sessione Stripe e reindirizza (stripe/guarantee)
     *  - scaduta     → schermata "scaduta" con CTA per riprenotare
     */
    public function complete(Request $request): void
    {
        $token = (string)$request->param('token');
        $reservation = (new Reservation())->findByToken($token);
        if (!$reservation) {
            Response::notFound();
        }

        $tenant = (new Tenant())->findById((int)$reservation['tenant_id']);
        if (!$tenant) {
            Response::notFound();
        }

        $status = $reservation['status'];
        $depositType = $tenant['deposit_type'] ?? 'info';

        if (in_array($status, ['confirmed', 'arrived'], true)) {
            $this->renderComplete('confirmed', $reservation, $tenant);
            return;
        }

        if (in_array($status, ['cancelled', 'noshow'], true)) {
            $this->renderComplete('expired', $reservation, $tenant);
            return;
        }

        // status === 'pending': per stripe/guarantee rigenera la sessione e reindirizza
        if (in_array($depositType, ['stripe', 'guarantee'], true) && !empty($tenant['stripe_sk'])) {
            $url = $this->createCheckoutSession($reservation, $tenant);
            if ($url) {
                Response::redirect($url);
                return;
            }
        }

        // pending senza Stripe (bonifico/link) o sessione non creata
        $this->renderComplete('pending', $reservation, $tenant);
    }

    private function renderComplete(string $state, array $reservation, array $tenant): void
    {
        view('booking/complete', [
            'state'       => $state,
            'reservation' => $reservation,
            'tenant'      => $tenant,
            'tenantName'  => $tenant['name'],
            'tenantLogo'  => $tenant['logo_url'] ?? null,
        ], 'booking');
    }

    /**
     * Rigenera una Stripe Checkout Session per una prenotazione pending.
     * payment per il tipo 'stripe', setup per 'guarantee'. Ritorna l'URL o null.
     */
    private function createCheckoutSession(array $reservation, array $tenant): ?string
    {
        $depositType = $tenant['deposit_type'] ?? 'info';
        $amount = (float)($reservation['deposit_amount'] ?? 0);
        $slug   = $tenant['slug'];
        $rid    = (int)$reservation['id'];

        if ($amount <= 0) {
            return null;
        }

        try {
            $key = decrypt_value($tenant['stripe_sk']);
            if (!$key) {
                return null;
            }
            \Stripe\Stripe::setApiKey($key);

            $params = [
                'payment_method_types' => ['card'],
                'success_url' => url("{$slug}/booking/success") . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => url("{$slug}/booking/cancel") . "?reservation_id={$rid}",
                'metadata'    => [
                    'reservation_id' => $rid,
                    'tenant_id'      => $tenant['id'],
                ],
                'expires_at'  => time() + 1800,
            ];

            if ($depositType === 'guarantee') {
                $params['mode'] = 'setup';
                $params['metadata']['kind'] = 'guarantee';
                if (!empty($reservation['email'])) {
                    $params['customer_email'] = $reservation['email'];
                }
            } else {
                $params['mode'] = 'payment';
                $params['line_items'] = [[
                    'price_data' => [
                        'currency'     => 'eur',
                        'unit_amount'  => (int)round($amount * 100),
                        'product_data' => ['name' => "Caparra - {$tenant['name']}"],
                    ],
                    'quantity' => 1,
                ]];
            }

            $session = \Stripe\Checkout\Session::create($params);
            return $session->url;
        } catch (\Exception $e) {
            app_log('complete() Stripe session error: ' . $e->getMessage(), 'error');
            return null;
        }
    }

    public function cancelPayment(Request $request): void
    {
        $slug = $request->param('slug');
        $tenant = (new Tenant())->findBySlug($slug);

        if (!$tenant) {
            Response::notFound();
        }

        // Try to retrieve the reservation for retry-payment
        $reservation = null;
        $canRetry = false;
        $reservationId = (int)$request->query('reservation_id', 0);

        if ($reservationId > 0) {
            $res = (new Reservation())->findWithCustomer($reservationId);
            if ($res && (int)$res['tenant_id'] === (int)$tenant['id'] && $res['status'] === 'pending') {
                $reservation = $res;
                $canRetry = ($tenant['deposit_type'] ?? '') === 'stripe' && !empty($tenant['stripe_sk']);
            }
        }

        view('booking/cancelled', [
            'tenant'       => $tenant,
            'tenantName'   => $tenant['name'],
            'tenantLogo'   => $tenant['logo_url'],
            'reservation'  => $reservation,
            'canRetry'     => $canRetry,
            'petFriendly'  => !empty($tenant['pet_friendly']),
            'kidsFriendly' => !empty($tenant['kids_friendly']),
        ], 'booking');
    }
}
