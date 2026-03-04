<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
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

        if (!$date) {
            Response::error('Il parametro date è obbligatorio.', 'MISSING_DATE', 400);
        }

        $tenant = (new Tenant())->findBySlug($slug);

        if (!$tenant || !$tenant['is_active']) {
            Response::error('Ristorante non trovato.', 'TENANT_NOT_FOUND', 404);
        }

        $service = new AvailabilityService();

        $responseData = [
            'date'       => $date,
            'party_size' => $partySize,
        ];

        if ($grouped) {
            $responseData['grouped_slots'] = $service->getGroupedSlots(
                $tenant['id'], $date, $partySize
            );
            $responseData['today_bookings'] = $service->getTodayBookingCount($tenant['id']);
        } else {
            $responseData['slots'] = $service->getAvailableSlots(
                $tenant['id'], $date, $partySize
            );
        }

        Response::success($responseData);
    }
}
