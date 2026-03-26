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

        view('booking/widget', [
            'tenant'       => $tenant,
            'tenantName'   => $tenant['name'],
            'tenantLogo'   => $tenant['logo_url'],
            'slug'         => $slug,
            'isEmbed'      => $isEmbed,
            'petFriendly'  => !empty($tenant['pet_friendly']),
            'kidsFriendly' => !empty($tenant['kids_friendly']),
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

        if ($sessionId && ($tenant['deposit_type'] ?? '') === 'stripe' && !empty($tenant['stripe_sk'])) {
            try {
                $tenantKey = decrypt_value($tenant['stripe_sk']);
                if (!$tenantKey) throw new \RuntimeException('Invalid stripe key');
                \Stripe\Stripe::setApiKey($tenantKey);
                $session = \Stripe\Checkout\Session::retrieve($sessionId);
                $reservationId = $session->metadata->reservation_id ?? null;
                if ($reservationId) {
                    $reservation = (new Reservation())->findWithCustomer((int)$reservationId);
                    $depositPaid = ($session->payment_status === 'paid');
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
