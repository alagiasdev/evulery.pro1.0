<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Core\Validator;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\ReservationLog;
use App\Services\AvailabilityService;

class ReservationsController
{
    public function index(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $date = $request->query('date', date('Y-m-d'));
        $status = $request->query('status');

        $reservations = (new Reservation())->findByTenantAndDate($tenantId, $date, $status);

        view('dashboard/reservations/index', [
            'title'        => 'Prenotazioni',
            'activeMenu'   => 'reservations',
            'reservations' => $reservations,
            'date'         => $date,
            'status'       => $status,
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
        $customerHistory = (new Reservation())->findByCustomer($reservation['customer_id']);

        view('dashboard/reservations/show', [
            'title'       => 'Dettaglio Prenotazione',
            'activeMenu'  => 'reservations',
            'reservation' => $reservation,
            'logs'        => $logs,
            'history'     => $customerHistory,
        ], 'dashboard');
    }

    public function create(Request $request): void
    {
        $tenant = TenantResolver::current();

        view('dashboard/reservations/create', [
            'title'       => 'Nuova Prenotazione',
            'activeMenu'  => 'reservations',
            'tenantSlug'  => $tenant['slug'],
            'pageScripts' => ['js/dashboard-reservation.js'],
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
            ->integer('party_size', 'Persone');

        if ($v->fails()) {
            flash('danger', $v->firstError());
            \App\Core\Session::flash('old_input', $data);
            Response::redirect(url('dashboard/reservations/create'));
        }

        $forceBooking = !empty($data['force_booking']) && (Auth::isOwner() || Auth::isSuperAdmin());

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
        if ($newStatus === 'noshow') {
            (new Customer())->incrementNoshow($reservation['customer_id']);
        }

        flash('success', 'Stato aggiornato a: ' . status_label($newStatus));
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
            ->integer('party_size', 'Persone');

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
        }

        flash('success', 'Prenotazione modificata con successo.');
        Response::redirect(url("dashboard/reservations/{$id}"));
    }
}
