<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\Customer;
use App\Models\Reservation;

class CustomersController
{
    public function index(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $search = $request->query('q');
        $customers = (new Customer())->findByTenant($tenantId, $search);

        view('dashboard/customers/index', [
            'title'      => 'Clienti',
            'activeMenu' => 'customers',
            'customers'  => $customers,
            'search'     => $search,
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
        $results = [];

        foreach (array_slice($customers, 0, 10) as $c) {
            $bookings = (int)$c['total_bookings'];
            $segment = 'nuovo';
            if ($bookings >= 10) $segment = 'vip';
            elseif ($bookings >= 4) $segment = 'abituale';
            elseif ($bookings >= 2) $segment = 'occasionale';

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
        ], 'dashboard');
    }
}
