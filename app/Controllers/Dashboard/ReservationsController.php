<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Core\Validator;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\Promotion;
use App\Models\ReservationLog;
use App\Models\Table;
use App\Models\Tenant;
use App\Services\AuditLog;
use App\Services\AvailabilityService;
use App\Services\MailService;
use App\Services\NotificationService;
use App\Services\TableAssigner;

class ReservationsController
{
    public function index(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $searchQuery = trim($request->query('q', ''));
        $upcoming = $request->query('upcoming') === '1';
        $date = $request->query('date', date('Y-m-d'));
        $dateTo = $request->query('date_to');
        $status = $request->query('status');
        $source = $request->query('source');

        // Validate source
        $allowedSources = ['widget', 'phone', 'walkin', 'altro'];
        if ($source && !in_array($source, $allowedSources)) {
            $source = null;
        }

        $searchResults = null;
        if ($searchQuery !== '') {
            $searchResults = (new Reservation())->searchGlobal($tenantId, $searchQuery);
        }

        $resModel = new Reservation();
        if ($upcoming) {
            $reservations = $resModel->findUpcoming($tenantId, 15, $status, $source);
        } else {
            $reservations = $resModel->findByTenantAndDate($tenantId, $date, $status, $dateTo, $source);
        }

        $isRange = !$upcoming && $dateTo && $dateTo !== $date;

        // Gestione Tavoli — assegnazioni per la lista (batch, niente N+1)
        $tableMgmt = (new Tenant())->canUseService($tenantId, 'table_management');
        $tableAssignments = [];
        if ($tableMgmt) {
            $allIds = array_column($reservations, 'id');
            if (!empty($searchResults)) {
                $allIds = array_merge($allIds, array_column($searchResults, 'id'));
            }
            $tableAssignments = (new TableAssigner())->assignmentsFor($allIds);
        }

        view('dashboard/reservations/index', [
            'title'        => 'Prenotazioni',
            'activeMenu'   => 'reservations',
            'reservations' => $reservations,
            'date'         => $date,
            'dateTo'       => $dateTo,
            'isRange'      => $isRange,
            'isUpcoming'   => $upcoming,
            'status'       => $status,
            'source'       => $source,
            'searchQuery'  => $searchQuery,
            'searchResults' => $searchResults,
            'tableMgmt'        => $tableMgmt,
            'tableAssignments' => $tableAssignments,
        ], 'dashboard');
    }

    public function show(Request $request): void
    {
        $id = (int)$request->param('id');
        $reservation = (new Reservation())->findWithCustomer($id);

        if (!$reservation || (int)$reservation['tenant_id'] !== (int)Auth::tenantId()) {
            flash('danger', 'Prenotazione non trovata.');
            Response::redirect(url('dashboard/reservations'));
        }

        $logs = (new ReservationLog())->findByReservation($id);
        $customerHistory = (new Reservation())->findByCustomer($reservation['customer_id'], (int)Auth::tenantId());

        // Gestione Tavoli — assegnazione corrente + opzioni per l'override
        $tableMgmt = (new Tenant())->canUseService((int)Auth::tenantId(), 'table_management');
        $tableOptions = [];
        $tableCurrentValue = '';
        $tableCurrentLabel = '';
        $tableCurrentAuto = false;
        $allTables = []; // raw list per il modale "Combina tavoli..."
        if ($tableMgmt) {
            $assigner = new TableAssigner();
            $tenantId = (int)Auth::tenantId();
            // Override manuale: elenca TUTTI i tavoli (come il popup Sala), senza
            // filtro capienza/turno. Il filtro serve all'auto-assegnazione, non
            // alla scelta dell'operatore, che non va mai bloccata.
            $tableOptions = $assigner->allTableOptions($tenantId);
            // Lista raw tavoli attivi per il modale multi-select (Fase A combina ad-hoc)
            $allTables = (new Table())->findActiveByTenant($tenantId);
            // Tavoli occupati al MOMENTO di questa prenotazione (da altre prenotazioni
            // che si sovrappongono temporalmente). Servono al modale "Combina tavoli"
            // per disabilitare quelli non disponibili in quel turno.
            $busyTableIds = $this->busyTablesForReservation($reservation, $tenantId);
            $current = $assigner->assignmentsFor([$id])[$id] ?? [];
            if (!empty($current)) {
                $curIds = array_map(fn($x) => (int)$x['id'], $current);
                sort($curIds);
                $tableCurrentValue = implode(',', $curIds);
                $tableCurrentLabel = implode(' + ', array_column($current, 'name'));
                $tableCurrentAuto = (bool)($current[0]['is_auto'] ?? 0);
                $known = array_column($tableOptions, 'value');
                if (!in_array($tableCurrentValue, $known, true)) {
                    array_unshift($tableOptions, [
                        'value' => $tableCurrentValue,
                        'label' => implode(' + ', array_column($current, 'name')) . ' (attuale)',
                    ]);
                }
            }
        }

        view('dashboard/reservations/show', [
            'title'             => 'Dettaglio Prenotazione',
            'activeMenu'        => 'reservations',
            'reservation'       => $reservation,
            'tenant'            => TenantResolver::current(),
            'logs'              => $logs,
            'history'           => $customerHistory,
            'tableMgmt'         => $tableMgmt,
            'tableOptions'      => $tableOptions,
            'tableCurrentValue' => $tableCurrentValue,
            'tableCurrentLabel' => $tableCurrentLabel,
            'tableCurrentAuto'  => $tableCurrentAuto,
            'allTables'         => $allTables,
            'busyTableIds'      => $busyTableIds ?? [],
        ], 'dashboard');
    }

    /**
     * Lista degli id-tavolo occupati al momento di una prenotazione, escludendo
     * la prenotazione stessa. Calcolata su sovrapposizione temporale con
     * table_duration del tenant. Restituisce array di int.
     */
    private function busyTablesForReservation(array $reservation, int $tenantId): array
    {
        $tenant = (new Tenant())->findById($tenantId);
        $tableDuration = max(15, (int)($tenant['table_duration'] ?? 90));
        $rStart = strtotime($reservation['reservation_time']);
        $rEnd   = $rStart + $tableDuration * 60;

        $others = (new Reservation())->findByTenantAndDate($tenantId, $reservation['reservation_date']);
        $otherIds = [];
        foreach ($others as $o) {
            if ((int)$o['id'] === (int)$reservation['id']) continue;
            if (!in_array((string)$o['status'], ['confirmed', 'pending', 'arrived'], true)) continue;
            $oStart = strtotime($o['reservation_time']);
            $oEnd   = $oStart + $tableDuration * 60;
            if (max($rStart, $oStart) < min($rEnd, $oEnd)) {
                $otherIds[] = (int)$o['id'];
            }
        }
        if (empty($otherIds)) return [];

        $assignments = (new TableAssigner())->assignmentsFor($otherIds);
        $busy = [];
        foreach ($assignments as $tables) {
            foreach ($tables as $t) $busy[(int)$t['id']] = true;
        }
        return array_keys($busy);
    }

    public function create(Request $request): void
    {
        $tenant = TenantResolver::current();

        // Pre-fill customer if coming from customer page
        $prefillCustomer = null;
        $customerId = (int)$request->query('customer_id', 0);
        if ($customerId > 0) {
            $customer = (new Customer())->findById($customerId);
            if ($customer && (int)$customer['tenant_id'] === (int)Auth::tenantId()) {
                $prefillCustomer = $customer;
            }
        }

        view('dashboard/reservations/create', [
            'title'            => 'Nuova Prenotazione',
            'activeMenu'       => 'reservations',
            'tenantSlug'       => $tenant['slug'],
            'prefillCustomer'  => $prefillCustomer,
            'pageScripts'      => ['js/dashboard-reservation.js'],
        ], 'dashboard');
    }

    public function store(Request $request): void
    {
        $data = $request->all();
        $tenantId = Auth::tenantId();

        $v = Validator::make($data)
            ->required('first_name', 'Nome')
            ->required('last_name', 'Cognome')
            ->required('email', 'Email')
            ->email('email', 'Email')
            ->required('phone', 'Telefono')
            ->required('reservation_date', 'Data')
            ->date('reservation_date', 'Data')
            ->required('reservation_time', 'Orario')
            ->required('party_size', 'Persone')
            ->integer('party_size', 'Persone')
            ->between('party_size', 1, 50, 'Persone');

        if ($v->fails()) {
            flash('danger', $v->firstError());
            \App\Core\Session::flash('old_input', $data);
            Response::redirect(url('dashboard/reservations/create'));
        }

        // Validate booking advance window
        $tenant = TenantResolver::current();
        $bookingDate = strtotime($data['reservation_date']);
        $today = strtotime(date('Y-m-d'));
        $daysAhead = (int)(($bookingDate - $today) / 86400);
        $advanceMin = (int)($tenant['booking_advance_min'] ?? 0);
        $advanceMax = (int)($tenant['booking_advance_max'] ?? 60);

        $forceBooking = !empty($data['force_booking']) && (Auth::isOwner() || Auth::isSuperAdmin());

        if (!$forceBooking && ($daysAhead < $advanceMin || $daysAhead > $advanceMax)) {
            flash('warning', "La data selezionata non rientra nella finestra di prenotazione ({$advanceMin}-{$advanceMax} giorni).");
            \App\Core\Session::flash('old_input', $data);
            Response::redirect(url('dashboard/reservations/create'));
        }

        // Find or create customer (before locking to minimize transaction duration)
        $customer = (new Customer())->findOrCreate($tenantId, [
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'],
        ]);

        // Validate source
        $allowedSources = ['phone', 'walkin', 'altro'];
        $source = in_array($data['source'] ?? '', $allowedSources)
            ? $data['source']
            : 'phone';

        // Lookup applicable promotion (server-side)
        $promo = (new Promotion())->findApplicable($tenantId, $data['reservation_date'], $data['reservation_time']);

        // Build reservation data
        $reservationData = [
            'tenant_id'        => $tenantId,
            'customer_id'      => $customer['id'],
            'reservation_date' => $data['reservation_date'],
            'reservation_time' => $data['reservation_time'],
            'party_size'       => (int)$data['party_size'],
            'status'           => 'confirmed',
            'source'           => $source,
        ];

        if ($promo) {
            $reservationData['discount_percent'] = (int)$promo['discount_percent'];
            $reservationData['promotion_id'] = (int)$promo['id'];
        }

        if (!empty($data['customer_notes'])) {
            $reservationData['customer_notes'] = substr($data['customer_notes'], 0, 1000);
        }

        if ($forceBooking) {
            // Force booking: skip availability check
            $reservationId = (new Reservation())->create($reservationData);
        } else {
            // Atomic check + book (prevents race condition / double-booking)
            $availability = new AvailabilityService();
            $reservationId = $availability->atomicBook(
                $tenantId, $data['reservation_date'], $data['reservation_time'], (int)$data['party_size'], $reservationData
            );

            if ($reservationId === null) {
                flash('warning', 'Attenzione: orario non disponibile per il numero di coperti selezionato. Seleziona un orario diverso o forza la prenotazione.');
                \App\Core\Session::flash('old_input', $data);
                Response::redirect(url('dashboard/reservations/create'));
            }
        }

        // Log
        $sourceLabels = ['phone' => 'telefono', 'walkin' => 'walk-in', 'altro' => 'altro'];
        $sourceLabel = $sourceLabels[$source] ?? 'dashboard';
        (new ReservationLog())->create($reservationId, null, 'confirmed', Auth::id(), "Creata da dashboard ({$sourceLabel})");
        (new Customer())->incrementBookings($customer['id']);

        // Send confirmation email (non-blocking: failure doesn't affect booking)
        $full = (new Reservation())->findWithCustomer($reservationId);
        if ($full) {
            MailService::sendReservationConfirmation($full, $tenant);
        }

        AuditLog::log(AuditLog::RESERVATION_CREATED, "Prenotazione #{$reservationId}", Auth::id(), $tenantId);

        // Auto-assegnazione tavolo — non bloccante: un errore non deve mai
        // compromettere la prenotazione appena creata.
        try {
            if ((new Tenant())->canUseService($tenantId, 'table_management')) {
                (new TableAssigner())->autoAssign(
                    $tenantId, $reservationId,
                    $data['reservation_date'], $data['reservation_time'], (int)$data['party_size']
                );
            }
        } catch (\Throwable $e) {
            error_log('Auto-assegnazione tavolo (dashboard) fallita: ' . $e->getMessage());
        }

        flash('success', 'Prenotazione creata con successo.');
        Response::redirect(url("dashboard/reservations/{$reservationId}"));
    }

    public function updateStatus(Request $request): void
    {
        $id = (int)$request->param('id');
        $newStatus = $request->input('status', '');

        $allowed = ['confirmed', 'arrived', 'noshow', 'cancelled'];
        if (!in_array($newStatus, $allowed)) {
            flash('danger', 'Stato non valido.');
            Response::redirect(url("dashboard/reservations/{$id}"));
        }

        $reservationModel = new Reservation();
        $reservation = $reservationModel->findById($id);

        if (!$reservation || (int)$reservation['tenant_id'] !== (int)Auth::tenantId()) {
            flash('danger', 'Prenotazione non trovata.');
            Response::redirect(url('dashboard/reservations'));
        }

        $reservationModel->updateStatus($id, $newStatus);

        // Log the change
        (new ReservationLog())->create($id, $reservation['status'], $newStatus, Auth::id());

        // Update customer stats
        if ($newStatus === 'arrived' && $reservation['customer_id']) {
            (new Customer())->updateLastVisit((int)$reservation['customer_id'], $reservation['reservation_date']);
        }
        if ($newStatus === 'noshow') {
            (new Customer())->incrementNoshow($reservation['customer_id']);
        }

        // Send confirmation email when manually confirming a pending reservation
        if ($newStatus === 'confirmed' && $reservation['status'] === 'pending') {
            $full = $reservationModel->findWithCustomer($id);
            if ($full) {
                $tenant = TenantResolver::current();
                MailService::sendReservationConfirmation($full, $tenant);
            }
        }

        // Notify restaurant owner when reservation is cancelled
        if ($newStatus === 'cancelled') {
            $full = $reservationModel->findWithCustomer($id);
            if ($full) {
                $tenant = TenantResolver::current();
                try {
                    (new NotificationService())->notifyCancellation($full, $tenant, 'staff');
                } catch (\Throwable $e) {
                    error_log('Cancellation notification failed: ' . $e->getMessage());
                }
            }
        }

        AuditLog::log(AuditLog::RESERVATION_STATUS, "Prenotazione #{$id}: {$newStatus}", Auth::id(), (int)$reservation['tenant_id']);

        flash('success', 'Stato aggiornato a: ' . status_label($newStatus));

        $redirectBack = $request->input('redirect_back', '');
        if ($redirectBack) {
            Response::redirect(url($redirectBack));
        }
        Response::redirect(url("dashboard/reservations/{$id}"));
    }

    public function markDepositPaid(Request $request): void
    {
        $id = (int)$request->param('id');
        $reservationModel = new Reservation();
        $reservation = $reservationModel->findById($id);

        if (!$reservation || (int)$reservation['tenant_id'] !== (int)Auth::tenantId()) {
            flash('danger', 'Prenotazione non trovata.');
            Response::redirect(url('dashboard/reservations'));
        }

        if (!$reservation['deposit_required'] || $reservation['deposit_paid']) {
            flash('info', 'Caparra già segnata come pagata.');
            Response::redirect(url("dashboard/reservations/{$id}"));
        }

        $reservationModel->markDepositPaid($id);
        (new ReservationLog())->create($id, $reservation['status'], $reservation['status'], Auth::id(), 'Caparra segnata come ricevuta');

        AuditLog::log(AuditLog::RESERVATION_UPDATED, "Caparra ricevuta per prenotazione #{$id}", Auth::id(), (int)$reservation['tenant_id']);

        flash('success', 'Caparra segnata come ricevuta.');
        Response::redirect(url("dashboard/reservations/{$id}"));
    }

    public function markDepositRefunded(Request $request): void
    {
        $id = (int)$request->param('id');
        $reservationModel = new Reservation();
        $reservation = $reservationModel->findById($id);

        if (!$reservation || (int)$reservation['tenant_id'] !== (int)Auth::tenantId()) {
            flash('danger', 'Prenotazione non trovata.');
            Response::redirect(url('dashboard/reservations'));
            return;
        }

        if (!$reservation['deposit_required'] || !$reservation['deposit_paid'] || $reservation['deposit_refunded']) {
            flash('info', 'Caparra già rimborsata o non applicabile.');
            Response::redirect(url("dashboard/reservations/{$id}"));
            return;
        }

        $reservationModel->markDepositRefunded($id);
        (new ReservationLog())->create($id, $reservation['status'], $reservation['status'], Auth::id(), 'Caparra segnata come rimborsata');

        AuditLog::log(AuditLog::RESERVATION_UPDATED, "Caparra rimborsata per prenotazione #{$id}", Auth::id(), (int)$reservation['tenant_id']);

        flash('success', 'Caparra segnata come rimborsata.');
        Response::redirect(url("dashboard/reservations/{$id}"));
    }

    /**
     * Addebita la penale no-show sulla carta a garanzia salvata dal cliente.
     * Crea un PaymentIntent off-session con il payment method memorizzato.
     */
    public function chargeGuarantee(Request $request): void
    {
        $id = (int)$request->param('id');
        $reservationModel = new Reservation();
        $reservation = $reservationModel->findById($id);

        if (!$reservation || (int)$reservation['tenant_id'] !== (int)Auth::tenantId()) {
            flash('danger', 'Prenotazione non trovata.');
            Response::redirect(url('dashboard/reservations'));
            return;
        }

        if (($reservation['guarantee_status'] ?? 'none') !== 'secured') {
            flash('info', 'Nessuna carta a garanzia addebitabile per questa prenotazione.');
            Response::redirect(url("dashboard/reservations/{$id}"));
            return;
        }

        $amount = (float)($reservation['deposit_amount'] ?? 0);
        if ($amount <= 0 || empty($reservation['stripe_customer_id']) || empty($reservation['stripe_payment_method_id'])) {
            flash('danger', 'Dati della carta a garanzia incompleti: impossibile addebitare.');
            Response::redirect(url("dashboard/reservations/{$id}"));
            return;
        }

        $tenant = (new Tenant())->findById((int)$reservation['tenant_id']);
        if (!$tenant || empty($tenant['stripe_sk'])) {
            flash('danger', 'Stripe non è configurato.');
            Response::redirect(url("dashboard/reservations/{$id}"));
            return;
        }

        try {
            $key = decrypt_value($tenant['stripe_sk']);
            if (!$key) {
                throw new \RuntimeException('Chiave Stripe non valida');
            }
            \Stripe\Stripe::setApiKey($key);

            $pi = \Stripe\PaymentIntent::create([
                'amount'         => (int)round($amount * 100),
                'currency'       => 'eur',
                'customer'       => $reservation['stripe_customer_id'],
                'payment_method' => $reservation['stripe_payment_method_id'],
                'off_session'    => true,
                'confirm'        => true,
                'description'    => "Penale no-show - prenotazione #{$id} ({$tenant['name']})",
                'metadata'       => [
                    'reservation_id' => $id,
                    'tenant_id'      => $tenant['id'],
                    'kind'           => 'guarantee_penalty',
                ],
            ]);

            if (($pi->status ?? '') !== 'succeeded') {
                flash('warning', 'Addebito non completato (stato: ' . ($pi->status ?? 'sconosciuto') . '). Riprova o contatta il cliente.');
                Response::redirect(url("dashboard/reservations/{$id}"));
                return;
            }

            $reservationModel->markGuaranteeCharged($id, $amount, $pi->id);
            (new ReservationLog())->create(
                $id, $reservation['status'], $reservation['status'], Auth::id(),
                'Penale no-show addebitata: €' . number_format($amount, 2, ',', '.')
            );
            AuditLog::log(AuditLog::RESERVATION_UPDATED, "Penale garanzia addebitata per prenotazione #{$id}", Auth::id(), (int)$reservation['tenant_id']);

            flash('success', 'Penale di €' . number_format($amount, 2, ',', '.') . ' addebitata sulla carta a garanzia.');
        } catch (\Stripe\Exception\CardException $e) {
            app_log('Guarantee charge declined: ' . $e->getMessage(), 'error');
            flash('danger', 'Addebito rifiutato: ' . $this->cardErrorMessage($e));
        } catch (\Exception $e) {
            app_log('Guarantee charge error: ' . $e->getMessage(), 'error');
            flash('danger', 'Errore durante l\'addebito della penale. Riprova più tardi.');
        }

        Response::redirect(url("dashboard/reservations/{$id}"));
    }

    /**
     * Chiude la carta a garanzia senza alcun addebito (cliente presentato
     * o scelta del ristoratore di non applicare la penale).
     */
    public function waiveGuarantee(Request $request): void
    {
        $id = (int)$request->param('id');
        $reservationModel = new Reservation();
        $reservation = $reservationModel->findById($id);

        if (!$reservation || (int)$reservation['tenant_id'] !== (int)Auth::tenantId()) {
            flash('danger', 'Prenotazione non trovata.');
            Response::redirect(url('dashboard/reservations'));
            return;
        }

        if (($reservation['guarantee_status'] ?? 'none') !== 'secured') {
            flash('info', 'Nessuna garanzia attiva da chiudere.');
            Response::redirect(url("dashboard/reservations/{$id}"));
            return;
        }

        $reservationModel->markGuaranteeWaived($id);
        (new ReservationLog())->create($id, $reservation['status'], $reservation['status'], Auth::id(), 'Garanzia chiusa senza addebito');
        AuditLog::log(AuditLog::RESERVATION_UPDATED, "Garanzia chiusa senza addebito per prenotazione #{$id}", Auth::id(), (int)$reservation['tenant_id']);

        flash('success', 'Garanzia chiusa senza addebito.');
        Response::redirect(url("dashboard/reservations/{$id}"));
    }

    /**
     * Traduce in italiano i codici di errore Stripe più comuni per un addebito carta.
     */
    private function cardErrorMessage(\Stripe\Exception\CardException $e): string
    {
        $err = $e->getError();
        $code = $err->decline_code ?? $err->code ?? '';
        return match ($code) {
            'insufficient_funds'        => 'fondi insufficienti sulla carta.',
            'expired_card'              => 'la carta è scaduta.',
            'authentication_required'   => 'la banca richiede l\'autenticazione del titolare: l\'addebito non è possibile senza il cliente presente.',
            'card_declined'             => 'la carta è stata rifiutata dalla banca.',
            'lost_card', 'stolen_card'  => 'la carta risulta bloccata.',
            'do_not_honor'              => 'la banca ha negato l\'operazione.',
            default                     => 'la banca ha rifiutato l\'addebito.',
        };
    }

    /**
     * Override manuale del tavolo assegnato a una prenotazione.
     * table_option = id tavolo, oppure "id,id" per una combinazione, oppure "" per rimuovere.
     */
    public function assignTable(Request $request): void
    {
        $id = (int)$request->param('id');
        $reservation = (new Reservation())->findById($id);
        $tenantId = (int)Auth::tenantId();

        if (!$reservation || (int)$reservation['tenant_id'] !== $tenantId) {
            flash('danger', 'Prenotazione non trovata.');
            Response::redirect(url('dashboard/reservations'));
            return;
        }
        if (gate_service('table_management', url("dashboard/reservations/{$id}"))) return;

        // Valida gli id: ogni tavolo deve appartenere a questo tenant
        $ids = [];
        $raw = trim((string)$request->input('table_option', ''));
        if ($raw !== '') {
            $tableModel = new Table();
            foreach (explode(',', $raw) as $tid) {
                $tid = (int)$tid;
                if ($tid <= 0) continue;
                $table = $tableModel->findById($tid);
                if ($table && (int)$table['tenant_id'] === $tenantId) {
                    $ids[] = $tid;
                }
            }
        }

        (new TableAssigner())->setAssignment($id, $ids, false);
        (new ReservationLog())->create(
            $id, $reservation['status'], $reservation['status'], Auth::id(),
            $ids ? 'Tavolo assegnato manualmente' : 'Tavolo rimosso'
        );
        AuditLog::log(AuditLog::RESERVATION_UPDATED, "Tavolo prenotazione #{$id} aggiornato", Auth::id(), $tenantId);

        flash('success', $ids ? 'Tavolo aggiornato.' : 'Tavolo rimosso.');

        // Torna alla mappa sala se la riassegnazione arriva da lì
        $back = trim((string)$request->input('redirect_back', ''));
        if ($back !== '' && str_starts_with($back, 'dashboard/')) {
            Response::redirect(url($back));
            return;
        }
        Response::redirect(url("dashboard/reservations/{$id}"));
    }

    public function updateNotes(Request $request): void
    {
        $id = (int)$request->param('id');
        $notes = $request->input('internal_notes', '');

        $reservationModel = new Reservation();
        $reservation = $reservationModel->findById($id);

        if (!$reservation || (int)$reservation['tenant_id'] !== (int)Auth::tenantId()) {
            flash('danger', 'Prenotazione non trovata.');
            Response::redirect(url('dashboard/reservations'));
        }

        $reservationModel->updateNotes($id, $notes);
        flash('success', 'Note aggiornate.');
        Response::redirect(url("dashboard/reservations/{$id}"));
    }

    public function destroy(Request $request): void
    {
        $id = (int)$request->param('id');
        $reservationModel = new Reservation();
        $reservation = $reservationModel->findById($id);

        if (!$reservation || (int)$reservation['tenant_id'] !== (int)Auth::tenantId()) {
            flash('danger', 'Prenotazione non trovata.');
            Response::redirect(url('dashboard/reservations'));
        }

        // Allow delete only within 30 minutes of creation
        $createdAt = strtotime($reservation['created_at']);
        $now = time();
        $minutesSinceCreation = ($now - $createdAt) / 60;

        if ($minutesSinceCreation > 30) {
            flash('danger', 'Non è più possibile eliminare questa prenotazione. Il tempo limite di 30 minuti è scaduto.');
            Response::redirect(url("dashboard/reservations/{$id}"));
        }

        // Decrement customer booking count
        (new Customer())->decrementBookings($reservation['customer_id']);

        // Delete related logs first (foreign key)
        (new ReservationLog())->deleteByReservation($id);

        // Delete the reservation
        $reservationModel->delete($id);

        AuditLog::log(AuditLog::RESERVATION_DELETED, "Prenotazione #{$id}", Auth::id(), (int)$reservation['tenant_id']);

        flash('success', 'Prenotazione #' . $id . ' eliminata definitivamente.');
        Response::redirect(url('dashboard/reservations'));
    }

    public function export(Request $request): void
    {
        if (gate_service('export_csv', url('dashboard/reservations'))) return;

        $tenantId = Auth::tenantId();
        $dateFrom = $request->query('date_from', date('Y-m-d'));
        $dateTo = $request->query('date_to', $dateFrom);
        $status = $request->query('status');

        $reservations = (new Reservation())->findForExport($tenantId, $dateFrom, $dateTo, $status);

        $statusLabels = [
            'confirmed' => 'Confermata',
            'pending'   => 'In attesa',
            'arrived'   => 'Arrivato',
            'noshow'    => 'No-show',
            'cancelled' => 'Annullata',
        ];
        $sourceLabels = [
            'widget' => 'Widget',
            'phone'  => 'Telefono',
            'walkin'  => 'Walk-in',
            'altro'  => 'Altro',
        ];

        $tenant = TenantResolver::current();
        $tenantSlug = $tenant['slug'] ?? 'export';
        $filename = "prenotazioni_{$tenantSlug}_{$dateFrom}_{$dateTo}.csv";

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $output = fopen('php://output', 'w');

        // UTF-8 BOM for Excel compatibility
        fwrite($output, "\xEF\xBB\xBF");

        // Header row
        fputcsv($output, [
            'Data', 'Orario', 'Nome', 'Cognome', 'Email', 'Telefono',
            'Persone', 'Sconto', 'Stato', 'Fonte', 'Note cliente', 'Note interne', 'Creata il'
        ], ';');

        foreach ($reservations as $r) {
            fputcsv($output, [
                $r['reservation_date'],
                substr($r['reservation_time'], 0, 5),
                $r['first_name'],
                $r['last_name'],
                $r['email'],
                $r['phone'],
                $r['party_size'],
                !empty($r['discount_percent']) ? '-' . $r['discount_percent'] . '%' : '',
                $statusLabels[$r['status']] ?? $r['status'],
                $sourceLabels[$r['source']] ?? $r['source'],
                $r['customer_notes'] ?? '',
                $r['internal_notes'] ?? '',
                $r['created_at'],
            ], ';');
        }

        fclose($output);
        exit;
    }

    public function edit(Request $request): void
    {
        $id = (int)$request->param('id');
        $reservation = (new Reservation())->findWithCustomer($id);

        if (!$reservation || (int)$reservation['tenant_id'] !== (int)Auth::tenantId()) {
            flash('danger', 'Prenotazione non trovata.');
            Response::redirect(url('dashboard/reservations'));
        }

        if (in_array($reservation['status'], ['cancelled', 'noshow', 'arrived'])) {
            flash('warning', 'Non puoi modificare una prenotazione in stato: ' . status_label($reservation['status']));
            Response::redirect(url("dashboard/reservations/{$id}"));
        }

        $tenant = TenantResolver::current();

        view('dashboard/reservations/edit', [
            'title'       => 'Modifica Prenotazione #' . $id,
            'activeMenu'  => 'reservations',
            'reservation' => $reservation,
            'tenantSlug'  => $tenant['slug'],
            'pageScripts' => ['js/dashboard-reservation.js'],
        ], 'dashboard');
    }

    public function update(Request $request): void
    {
        $id = (int)$request->param('id');
        $data = $request->all();
        $tenantId = Auth::tenantId();

        $reservationModel = new Reservation();
        $reservation = $reservationModel->findById($id);

        if (!$reservation || (int)$reservation['tenant_id'] !== (int)$tenantId) {
            flash('danger', 'Prenotazione non trovata.');
            Response::redirect(url('dashboard/reservations'));
        }

        if (in_array($reservation['status'], ['cancelled', 'noshow', 'arrived'])) {
            flash('warning', 'Non puoi modificare una prenotazione in stato: ' . status_label($reservation['status']));
            Response::redirect(url("dashboard/reservations/{$id}"));
        }

        $v = Validator::make($data)
            ->required('reservation_date', 'Data')
            ->date('reservation_date', 'Data')
            ->required('reservation_time', 'Orario')
            ->required('party_size', 'Persone')
            ->integer('party_size', 'Persone')
            ->between('party_size', 1, 50, 'Persone');

        if ($v->fails()) {
            flash('danger', $v->firstError());
            \App\Core\Session::flash('old_input', $data);
            Response::redirect(url("dashboard/reservations/{$id}/edit"));
        }

        // Check availability (unless force_booking)
        $forceBooking = !empty($data['force_booking']) && (Auth::isOwner() || Auth::isSuperAdmin());
        if (!$forceBooking) {
            $availability = new AvailabilityService();
            if (!$availability->canBook($tenantId, $data['reservation_date'], $data['reservation_time'], (int)$data['party_size'])) {
                flash('warning', 'Attenzione: orario non disponibile per il numero di coperti selezionato. Seleziona un orario diverso o forza la modifica.');
                \App\Core\Session::flash('old_input', $data);
                Response::redirect(url("dashboard/reservations/{$id}/edit"));
            }
        }

        // Build changes description for log
        $changes = [];
        if ($data['reservation_date'] !== $reservation['reservation_date']) {
            $changes[] = 'data: ' . $reservation['reservation_date'] . ' → ' . $data['reservation_date'];
        }
        if (substr($data['reservation_time'], 0, 5) !== substr($reservation['reservation_time'], 0, 5)) {
            $changes[] = 'orario: ' . substr($reservation['reservation_time'], 0, 5) . ' → ' . substr($data['reservation_time'], 0, 5);
        }
        if ((int)$data['party_size'] !== (int)$reservation['party_size']) {
            $changes[] = 'coperti: ' . $reservation['party_size'] . ' → ' . $data['party_size'];
        }

        $updateData = [
            'reservation_date' => $data['reservation_date'],
            'reservation_time' => $data['reservation_time'],
            'party_size'       => (int)$data['party_size'],
            'customer_notes'   => isset($data['customer_notes']) ? substr($data['customer_notes'], 0, 1000) : null,
        ];

        $reservationModel->updateDetails($id, $updateData);

        // Log
        if (!empty($changes)) {
            $note = 'Modificata da dashboard: ' . implode(', ', $changes);
            (new ReservationLog())->create($id, $reservation['status'], $reservation['status'], Auth::id(), $note);

            // Send update notification email to customer
            $updated = (new Reservation())->findWithCustomer($id);
            if ($updated) {
                $tenant = TenantResolver::current();
                MailService::sendReservationConfirmation($updated, $tenant, 'updated');
            }
        }

        AuditLog::log(AuditLog::RESERVATION_UPDATED, "Prenotazione #{$id}", Auth::id(), $tenantId);

        flash('success', 'Prenotazione modificata con successo.');
        Response::redirect(url("dashboard/reservations/{$id}"));
    }
}
