<?php

namespace App\Controllers\Booking;

use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Models\Tenant;

class BookingController
{
    public function show(Request $request): void
    {
        $slug = $request->param('slug');
        $tenant = (new Tenant())->findBySlug($slug);

        if (!$tenant || !$tenant['is_active']) {
            Response::notFound();
        }

        TenantResolver::setCurrent($tenant);

        $isEmbed = $request->isEmbed();
        $layout = $isEmbed ? 'embed' : 'booking';

        view('booking/widget', [
            'tenant'      => $tenant,
            'tenantName'  => $tenant['name'],
            'tenantLogo'  => $tenant['logo_url'],
            'slug'        => $slug,
            'isEmbed'     => $isEmbed,
        ], $layout);
    }

    public function success(Request $request): void
    {
        $slug = $request->param('slug');
        $tenant = (new Tenant())->findBySlug($slug);

        if (!$tenant) {
            Response::notFound();
        }

        view('booking/confirmation', [
            'tenant'     => $tenant,
            'tenantName' => $tenant['name'],
            'tenantLogo' => $tenant['logo_url'],
            'message'    => 'Prenotazione confermata!',
        ], 'booking');
    }

    public function cancelPayment(Request $request): void
    {
        $slug = $request->param('slug');
        $tenant = (new Tenant())->findBySlug($slug);

        if (!$tenant) {
            Response::notFound();
        }

        view('booking/cancelled', [
            'tenant'     => $tenant,
            'tenantName' => $tenant['name'],
            'tenantLogo' => $tenant['logo_url'],
        ], 'booking');
    }
}
