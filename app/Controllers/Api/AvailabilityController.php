<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Models\SlotOverride;
use App\Models\Tenant;
use App\Services\AvailabilityService;

class AvailabilityController
{
    public function check(Request $request): void
    {
        $slug = $request->param('slug');
        $date = $request->query('date');
        $partySize = (int)$request->query('party_size', 2);
        $grouped = (bool)$request->query('grouped', false);
        $source = $request->query('source', 'widget');
        if (!in_array($source, ['widget', 'dashboard'])) {
            $source = 'widget';
        }

        if (!$date) {
            Response::error('Il parametro date è obbligatorio.', 'MISSING_DATE', 400);
        }

        $tenantModel = new Tenant();
        $tenant = $tenantModel->findBySlug($slug);

        if (!$tenant || !$tenant['is_active']) {
            Response::error('Ristorante non trovato.', 'TENANT_NOT_FOUND', 404);
        }

        // Block expired subscriptions
        if ($source === 'widget' && $tenantModel->getExpiredSubscription((int)$tenant['id'])) {
            Response::error('Il servizio di prenotazione non è al momento disponibile.', 'SUBSCRIPTION_EXPIRED', 403);
        }

        $service = new AvailabilityService();

        $responseData = [
            'date'       => $date,
            'party_size' => $partySize,
        ];

        if ($grouped) {
            $responseData['grouped_slots'] = $service->getGroupedSlots(
                $tenant['id'], $date, $partySize, $source
            );
            $responseData['today_bookings'] = $service->getTodayBookingCount($tenant['id']);
        } else {
            $responseData['slots'] = $service->getAvailableSlots(
                $tenant['id'], $date, $partySize, $source
            );
        }

        Response::success($responseData);
    }

    /**
     * Returns closed dates for a given month range (for widget calendar).
     * GET /api/v1/tenants/{slug}/closures?from=2026-03-01&to=2026-03-31
     */
    public function closedDates(Request $request): void
    {
        $slug = $request->param('slug');
        $from = $request->query('from');
        $to = $request->query('to');

        if (!$from || !$to) {
            Response::error('Parametri from e to obbligatori.', 'MISSING_PARAMS', 400);
        }

        $tenantModel = new Tenant();
        $tenant = $tenantModel->findBySlug($slug);
        if (!$tenant || !$tenant['is_active']) {
            Response::error('Ristorante non trovato.', 'TENANT_NOT_FOUND', 404);
        }

        // Block expired subscriptions
        if ($tenantModel->getExpiredSubscription((int)$tenant['id'])) {
            Response::error('Il servizio di prenotazione non è al momento disponibile.', 'SUBSCRIPTION_EXPIRED', 403);
        }

        $dates = (new SlotOverride())->getClosedDates($tenant['id'], $from, $to);

        Response::success(['closed_dates' => $dates]);
    }
}
