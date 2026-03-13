<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Models\Customer;
use App\Models\Reservation;

class CustomersController
{
    public function index(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $search = $request->query('q');
        $segment = $request->query('segment');
        $tenant = TenantResolver::current();
        $thOcc = (int)($tenant['segment_occasionale'] ?? 2);
        $thAbi = (int)($tenant['segment_abituale'] ?? 4);
        $thVip = (int)($tenant['segment_vip'] ?? 10);

        // Load all customers for stats (unfiltered)
        $allCustomers = (new Customer())->findByTenant($tenantId);
        $stats = ['totale' => count($allCustomers), 'nuovo' => 0, 'occasionale' => 0, 'abituale' => 0, 'vip' => 0];
        foreach ($allCustomers as $c) {
            $b = (int)$c['total_bookings'];
            if ($b >= $thVip) $stats['vip']++;
            elseif ($b >= $thAbi) $stats['abituale']++;
            elseif ($b >= $thOcc) $stats['occasionale']++;
            else $stats['nuovo']++;
        }

        // Apply search + segment filters
        $customers = $search ? (new Customer())->findByTenant($tenantId, $search) : $allCustomers;

        if ($segment) {
            $customers = array_filter($customers, function ($c) use ($segment, $thOcc, $thAbi, $thVip) {
                $b = (int)$c['total_bookings'];
                return match ($segment) {
                    'nuovo'        => $b < $thOcc,
                    'occasionale'  => $b >= $thOcc && $b < $thAbi,
                    'abituale'     => $b >= $thAbi && $b < $thVip,
                    'vip'          => $b >= $thVip,
                    default        => true,
                };
            });
        }

        view('dashboard/customers/index', [
            'title'      => 'Clienti',
            'activeMenu' => 'customers',
            'customers'  => $customers,
            'search'     => $search,
            'segment'    => $segment,
            'stats'      => $stats,
            'tenant'     => $tenant,
        ], 'dashboard');
    }

    public function searchJson(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $q = trim($request->query('q', ''));

        if (mb_strlen($q) < 2) {
            Response::json(['success' => true, 'data' => []]);
        }

        $customers = (new Customer())->findByTenant($tenantId, $q);
        $tenant = TenantResolver::current();
        $thOcc = (int)($tenant['segment_occasionale'] ?? 2);
        $thAbi = (int)($tenant['segment_abituale'] ?? 4);
        $thVip = (int)($tenant['segment_vip'] ?? 10);
        $results = [];

        foreach (array_slice($customers, 0, 10) as $c) {
            $bookings = (int)$c['total_bookings'];
            $segment = 'nuovo';
            if ($bookings >= $thVip) $segment = 'vip';
            elseif ($bookings >= $thAbi) $segment = 'abituale';
            elseif ($bookings >= $thOcc) $segment = 'occasionale';

            $results[] = [
                'id'             => $c['id'],
                'first_name'     => $c['first_name'],
                'last_name'      => $c['last_name'],
                'email'          => $c['email'],
                'phone'          => $c['phone'],
                'total_bookings' => $bookings,
                'total_noshow'   => (int)$c['total_noshow'],
                'segment'        => $segment,
            ];
        }

        Response::json(['success' => true, 'data' => $results]);
    }

    public function show(Request $request): void
    {
        $id = (int)$request->param('id');
        $customer = (new Customer())->findById($id);

        if (!$customer || (int)$customer['tenant_id'] !== (int)Auth::tenantId()) {
            flash('danger', 'Cliente non trovato.');
            Response::redirect(url('dashboard/customers'));
        }

        $reservations = (new Reservation())->findByCustomer($id);

        view('dashboard/customers/show', [
            'title'        => $customer['first_name'] . ' ' . $customer['last_name'],
            'activeMenu'   => 'customers',
            'customer'     => $customer,
            'reservations' => $reservations,
            'tenant'       => TenantResolver::current(),
        ], 'dashboard');
    }

    public function updateNotes(Request $request): void
    {
        $id = (int)$request->param('id');
        $customer = (new Customer())->findById($id);

        if (!$customer || (int)$customer['tenant_id'] !== (int)Auth::tenantId()) {
            flash('danger', 'Cliente non trovato.');
            Response::redirect(url('dashboard/customers'));
        }

        $notes = substr($request->input('notes', ''), 0, 2000);
        (new Customer())->updateNotes($id, $notes);

        flash('success', 'Note aggiornate.');
        Response::redirect(url("dashboard/customers/{$id}"));
    }
}
