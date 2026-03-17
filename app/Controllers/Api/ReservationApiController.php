<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\ReservationLog;
use App\Models\Tenant;
use App\Services\AvailabilityService;
use App\Services\MailService;

class ReservationApiController
{
    public function store(Request $request): void
    {
        $slug = $request->param('slug');
        $tenant = (new Tenant())->findBySlug($slug);

        if (!$tenant || !$tenant['is_active']) {
            Response::error('Ristorante non trovato.', 'TENANT_NOT_FOUND', 404);
        }

        $data = $request->isJson() ? $request->json() : $request->all();

        $v = Validator::make($data)
            ->required('date', 'Data')
            ->date('date', 'Data')
            ->required('time', 'Orario')
            ->time('time', 'Orario')
            ->required('party_size', 'Persone')
            ->integer('party_size', 'Persone')
            ->between('party_size', 1, 50, 'Persone')
            ->required('first_name', 'Nome')
            ->required('last_name', 'Cognome')
            ->required('email', 'Email')
            ->email('email', 'Email')
            ->required('phone', 'Telefono')
            ->phone('phone', 'Telefono');

        if ($v->fails()) {
            Response::error($v->firstError(), 'VALIDATION_ERROR', 422);
        }

        // Validate booking advance window
        $bookingDate = strtotime($data['date']);
        $today = strtotime(date('Y-m-d'));
        $daysAhead = (int)(($bookingDate - $today) / 86400);
        $advanceMin = (int)($tenant['booking_advance_min'] ?? 0);
        $advanceMax = (int)($tenant['booking_advance_max'] ?? 60);

        if ($daysAhead < $advanceMin) {
            Response::error("Le prenotazioni richiedono almeno {$advanceMin} giorni di anticipo.", 'DATE_TOO_SOON', 422);
        }
        if ($daysAhead > $advanceMax) {
            Response::error("Le prenotazioni sono possibili fino a {$advanceMax} giorni in anticipo.", 'DATE_TOO_FAR', 422);
        }

        // Find or create customer (before locking to minimize transaction duration)
        $customer = (new Customer())->findOrCreate($tenant['id'], [
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'],
        ]);

        // Block blacklisted customers
        if (!empty($customer['is_blocked'])) {
            Response::error(
                'Non è possibile effettuare la prenotazione. Contatta il ristorante telefonicamente.',
                'CUSTOMER_BLOCKED',
                403
            );
        }

        // Determine deposit - only require if Stripe is configured
        $stripeConfigured = !empty(env('STRIPE_SECRET_KEY', '')) && env('STRIPE_SECRET_KEY') !== 'sk_test_xxx';
        $depositRequired = ($tenant['deposit_enabled'] && $stripeConfigured) ? 1 : 0;
        $depositAmount = $depositRequired ? $tenant['deposit_amount'] : null;
        $status = $depositRequired ? 'pending' : 'confirmed';

        // Build reservation data
        $reservationData = [
            'tenant_id'        => $tenant['id'],
            'customer_id'      => $customer['id'],
            'reservation_date' => $data['date'],
            'reservation_time' => $data['time'],
            'party_size'       => (int)$data['party_size'],
            'status'           => $status,
            'deposit_required' => $depositRequired,
            'deposit_amount'   => $depositAmount,
            'source'           => 'widget',
        ];

        if (!empty($data['notes'])) {
            $reservationData['customer_notes'] = substr($data['notes'], 0, 1000);
        }

        // Atomic check + book (prevents race condition / double-booking)
        $availability = new AvailabilityService();
        $reservationId = $availability->atomicBook(
            $tenant['id'], $data['date'], $data['time'], (int)$data['party_size'], $reservationData
        );

        if ($reservationId === null) {
            $suggestions = $availability->getSuggestions(
                $tenant['id'], $data['date'], (int)$data['party_size'], $data['time']
            );
            Response::error(
                'Posti non disponibili per l\'orario selezionato.',
                'SLOT_UNAVAILABLE',
                409,
                ['suggestions' => $suggestions]
            );
        }

        // Log creation
        (new ReservationLog())->create($reservationId, null, $status, null, 'Prenotazione da widget');
        (new Customer())->incrementBookings($customer['id']);

        // Send confirmation email (non-blocking: failure doesn't affect booking)
        if ($status === 'confirmed') {
            $emailData = array_merge($customer, [
                'reservation_date' => $data['date'],
                'reservation_time' => $data['time'],
                'party_size'       => (int)$data['party_size'],
                'customer_notes'   => $reservationData['customer_notes'] ?? '',
            ]);
            MailService::sendReservationConfirmation($emailData, $tenant);
        }

        $responseData = [
            'reservation_id' => $reservationId,
            'status'         => $status,
            'date'           => $data['date'],
            'time'           => $data['time'],
            'party_size'     => (int)$data['party_size'],
        ];

        // If deposit required, create Stripe Checkout session
        if ($depositRequired && $depositAmount > 0) {
            // Stripe integration will be added in Phase 8
            $responseData['deposit_required'] = true;
            $responseData['deposit_amount'] = $depositAmount;
            $responseData['message'] = 'Prenotazione creata. Caparra richiesta.';
        }

        Response::success($responseData, 'Prenotazione creata con successo.', 201);
    }

    public function show(Request $request): void
    {
        $slug = $request->param('slug');
        $tenant = (new Tenant())->findBySlug($slug);

        if (!$tenant || !$tenant['is_active']) {
            Response::error('Ristorante non trovato.', 'TENANT_NOT_FOUND', 404);
        }

        $id = (int)$request->param('id');
        $email = $request->query('email', '');

        $reservation = (new Reservation())->findWithCustomer($id);

        if (!$reservation || $reservation['email'] !== $email || (int)$reservation['tenant_id'] !== (int)$tenant['id']) {
            Response::error('Prenotazione non trovata.', 'NOT_FOUND', 404);
        }

        Response::success([
            'id'         => $reservation['id'],
            'date'       => $reservation['reservation_date'],
            'time'       => format_time($reservation['reservation_time']),
            'party_size' => (int)$reservation['party_size'],
            'status'     => $reservation['status'],
            'name'       => $reservation['first_name'] . ' ' . $reservation['last_name'],
        ]);
    }

    public function cancel(Request $request): void
    {
        $slug = $request->param('slug');
        $tenant = (new Tenant())->findBySlug($slug);

        if (!$tenant || !$tenant['is_active']) {
            Response::error('Ristorante non trovato.', 'TENANT_NOT_FOUND', 404);
        }

        $id = (int)$request->param('id');
        $data = $request->isJson() ? $request->json() : $request->all();
        $email = $data['email'] ?? '';

        $reservationModel = new Reservation();
        $reservation = $reservationModel->findWithCustomer($id);

        if (!$reservation || $reservation['email'] !== $email || (int)$reservation['tenant_id'] !== (int)$tenant['id']) {
            Response::error('Prenotazione non trovata.', 'NOT_FOUND', 404);
        }

        if (in_array($reservation['status'], ['cancelled', 'arrived', 'noshow'])) {
            Response::error('Questa prenotazione non può essere annullata.', 'INVALID_STATUS', 400);
        }

        $reservationModel->updateStatus($id, 'cancelled');
        (new ReservationLog())->create($id, $reservation['status'], 'cancelled', null, 'Annullata dal cliente');

        Response::success(null, 'Prenotazione annullata con successo.');
    }
}
