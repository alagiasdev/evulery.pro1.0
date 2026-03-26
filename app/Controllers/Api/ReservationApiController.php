<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\ReservationLog;
use App\Models\Tenant;
use App\Models\Promotion;
use App\Services\AuditLog;
use App\Services\AvailabilityService;
use App\Services\MailService;
use App\Services\NotificationService;

class ReservationApiController
{
    public function store(Request $request): void
    {
        $slug = $request->param('slug');
        $tenantModel = new Tenant();
        $tenant = $tenantModel->findBySlug($slug);

        if (!$tenant || !$tenant['is_active']) {
            Response::error('Ristorante non trovato.', 'TENANT_NOT_FOUND', 404);
        }

        // Block expired subscriptions
        if ($tenantModel->getExpiredSubscription((int)$tenant['id'])) {
            Response::error('Il servizio di prenotazione non è al momento disponibile.', 'SUBSCRIPTION_EXPIRED', 403);
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

        // Check for duplicate booking (same customer, same date, active status)
        $forceDuplicate = !empty($data['force_duplicate']);
        if (!$forceDuplicate) {
            $existing = (new Reservation())->findActiveByCustomerDate($tenant['id'], $customer['id'], $data['date']);
            if (!empty($existing)) {
                $times = array_map(fn($r) => substr($r['reservation_time'], 0, 5), $existing);
                Response::json([
                    'success' => false,
                    'error'   => [
                        'code'    => 'DUPLICATE_WARNING',
                        'message' => 'Hai già una prenotazione per questo giorno alle ' . implode(', ', $times) . '. Vuoi procedere comunque?',
                    ],
                    'existing' => $existing,
                ], 409);
            }
        }

        // Determine deposit - require if tenant has deposit enabled AND plan includes deposit service
        $canUseDeposit = (new \App\Models\Tenant())->canUseService((int)$tenant['id'], 'deposit');
        $depositRequired = ($tenant['deposit_enabled'] && $canUseDeposit) ? 1 : 0;
        $depositType = $tenant['deposit_type'] ?? 'info';

        // Calculate deposit based on mode: per_table (fixed) or per_person (× party_size)
        $depositAmount = null;
        if ($depositRequired) {
            $baseAmount = (float)$tenant['deposit_amount'];
            $depositMode = $tenant['deposit_mode'] ?? 'per_table';
            $depositAmount = $depositMode === 'per_person'
                ? $baseAmount * (int)$data['party_size']
                : $baseAmount;
        }

        // Determine initial status: pending if deposit required OR manual confirmation mode
        if ($depositRequired) {
            $status = 'pending';
        } elseif (($tenant['confirmation_mode'] ?? 'auto') === 'manual') {
            $status = 'pending';
        } else {
            $status = 'confirmed';
        }

        // Lookup applicable promotion (server-side, never trust client)
        $promo = (new Promotion())->findApplicable($tenant['id'], $data['date'], $data['time']);

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

        if ($promo) {
            $reservationData['discount_percent'] = (int)$promo['discount_percent'];
        }

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
        $isManualMode = !$depositRequired && ($tenant['confirmation_mode'] ?? 'auto') === 'manual';
        $logNote = $isManualMode ? 'Prenotazione da widget (in attesa di conferma)' : 'Prenotazione da widget';
        (new ReservationLog())->create($reservationId, null, $status, null, $logNote);
        (new Customer())->incrementBookings($customer['id']);

        // Send confirmation email (non-blocking: failure doesn't affect booking)
        // In manual mode, email is sent later when the owner confirms
        if ($status === 'confirmed') {
            $full = (new Reservation())->findWithCustomer($reservationId);
            if ($full) {
                MailService::sendReservationConfirmation($full, $tenant);
            }
        }

        AuditLog::log(AuditLog::RESERVATION_CREATED, "Prenotazione #{$reservationId} (API)", null, (int)$tenant['id']);

        // Notify restaurant owner (email + campanella + push) — non-blocking
        try {
            $fullRes = $full ?? (new Reservation())->findWithCustomer($reservationId);
            if ($fullRes) {
                (new NotificationService())->notifyNewReservation($fullRes, $tenant);
            }
        } catch (\Throwable $e) {
            error_log('Notification failed: ' . $e->getMessage());
        }

        $responseData = [
            'reservation_id' => $reservationId,
            'status'         => $status,
            'date'           => $data['date'],
            'time'           => $data['time'],
            'party_size'     => (int)$data['party_size'],
        ];

        // If deposit required, handle based on deposit_type
        if ($depositRequired && $depositAmount > 0) {
            $responseData['deposit_required'] = true;
            $responseData['deposit_amount'] = $depositAmount;
            $responseData['deposit_type'] = $depositType;

            if ($depositType === 'stripe' && !empty($tenant['stripe_sk'])) {
                // Stripe integrated: create Checkout session with tenant's own keys
                try {
                    $tenantStripeKey = decrypt_value($tenant['stripe_sk']);
                    if (!$tenantStripeKey) {
                        throw new \RuntimeException('Chiave Stripe non valida');
                    }
                    \Stripe\Stripe::setApiKey($tenantStripeKey);

                    $depositMode = $tenant['deposit_mode'] ?? 'per_table';
                    $description = $depositMode === 'per_person'
                        ? "Caparra {$tenant['name']} - €" . number_format((float)$tenant['deposit_amount'], 2, ',', '.') . " × {$data['party_size']} persone"
                        : "Caparra prenotazione {$tenant['name']}";

                    $session = \Stripe\Checkout\Session::create([
                        'payment_method_types' => ['card'],
                        'line_items' => [[
                            'price_data' => [
                                'currency'     => 'eur',
                                'unit_amount'  => (int)round($depositAmount * 100),
                                'product_data' => [
                                    'name'        => "Caparra - {$tenant['name']}",
                                    'description' => $description,
                                ],
                            ],
                            'quantity' => 1,
                        ]],
                        'mode'        => 'payment',
                        'success_url' => url("{$slug}/booking/success") . '?session_id={CHECKOUT_SESSION_ID}',
                        'cancel_url'  => url("{$slug}/booking/cancel") . "?reservation_id={$reservationId}",
                        'metadata'    => [
                            'reservation_id' => $reservationId,
                            'tenant_id'      => $tenant['id'],
                        ],
                        'expires_at' => time() + 1800,
                    ]);

                    $responseData['stripe_checkout_url'] = $session->url;
                    $responseData['message'] = 'Prenotazione creata. Verrai reindirizzato al pagamento.';
                } catch (\Exception $e) {
                    app_log('Stripe Checkout error: ' . $e->getMessage(), 'error');
                    $responseData['message'] = 'Prenotazione creata. Contatta il ristorante per il pagamento della caparra.';
                }
            } elseif ($depositType === 'link' && !empty($tenant['deposit_payment_link'])) {
                // External payment link
                $responseData['deposit_payment_link'] = $tenant['deposit_payment_link'];
                $responseData['message'] = 'Prenotazione creata. Effettua il pagamento della caparra tramite il link.';
            } else {
                // Bank info (default)
                $responseData['deposit_bank_info'] = $tenant['deposit_bank_info'] ?? '';
                $responseData['message'] = 'Prenotazione creata. Effettua il bonifico per confermare.';
            }
        }

        $successMsg = $isManualMode
            ? 'Prenotazione ricevuta! Il ristorante confermerà a breve.'
            : 'Prenotazione creata con successo.';
        Response::success($responseData, $successMsg, 201);
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

        // Notify restaurant owner (email + campanella + push)
        try {
            (new NotificationService())->notifyCancellation($reservation, $tenant, 'cliente');
        } catch (\Throwable $e) {
            error_log('Cancellation notification failed: ' . $e->getMessage());
        }

        AuditLog::log(AuditLog::RESERVATION_STATUS, "Prenotazione #{$id}: cancelled (API)", null, (int)$tenant['id']);

        Response::success(null, 'Prenotazione annullata con successo.');
    }

    /**
     * Create a new Stripe Checkout session for an existing pending reservation.
     * POST /api/v1/tenants/{slug}/reservations/{id}/retry-payment
     */
    public function retryPayment(Request $request): void
    {
        $slug = $request->param('slug');
        $tenant = (new Tenant())->findBySlug($slug);

        if (!$tenant || !$tenant['is_active']) {
            Response::error('Ristorante non trovato.', 'TENANT_NOT_FOUND', 404);
        }

        $id = (int)$request->param('id');
        $reservation = (new Reservation())->findWithCustomer($id);

        if (!$reservation || (int)$reservation['tenant_id'] !== (int)$tenant['id']) {
            Response::error('Prenotazione non trovata.', 'NOT_FOUND', 404);
        }

        if ($reservation['status'] !== 'pending') {
            Response::error('La prenotazione non è in attesa di pagamento.', 'INVALID_STATUS', 400);
        }

        if (($tenant['deposit_type'] ?? '') !== 'stripe' || empty($tenant['stripe_sk'])) {
            Response::error('Pagamento Stripe non configurato.', 'STRIPE_NOT_CONFIGURED', 400);
        }

        $depositAmount = (float)($reservation['deposit_amount'] ?? 0);
        if ($depositAmount <= 0) {
            Response::error('Nessuna caparra da pagare.', 'NO_DEPOSIT', 400);
        }

        try {
            $tenantStripeKey = decrypt_value($tenant['stripe_sk']);
            if (!$tenantStripeKey) {
                throw new \RuntimeException('Chiave Stripe non valida');
            }
            \Stripe\Stripe::setApiKey($tenantStripeKey);

            $depositMode = $tenant['deposit_mode'] ?? 'per_table';
            $description = $depositMode === 'per_person'
                ? "Caparra {$tenant['name']} - €" . number_format((float)$tenant['deposit_amount'], 2, ',', '.') . " × {$reservation['party_size']} persone"
                : "Caparra prenotazione {$tenant['name']}";

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency'     => 'eur',
                        'unit_amount'  => (int)round($depositAmount * 100),
                        'product_data' => [
                            'name'        => "Caparra - {$tenant['name']}",
                            'description' => $description,
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode'        => 'payment',
                'success_url' => url("{$slug}/booking/success") . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => url("{$slug}/booking/cancel") . "?reservation_id={$id}",
                'metadata'    => [
                    'reservation_id' => $id,
                    'tenant_id'      => $tenant['id'],
                ],
                'expires_at' => time() + 1800,
            ]);

            Response::success([
                'stripe_checkout_url' => $session->url,
            ], 'Sessione di pagamento creata.');
        } catch (\Exception $e) {
            app_log('Stripe retry-payment error: ' . $e->getMessage(), 'error');
            Response::error('Errore nella creazione del pagamento. Riprova.', 'STRIPE_ERROR', 500);
        }
    }
}
