<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Models\Customer;
use App\Models\Reservation;
use App\Services\AuditLog;

class CustomersController
{
    public function index(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $search = $request->query('q');
        $segment = $request->query('segment');
        $page = max(1, (int)$request->query('page', 1));
        $perPage = 25;
        $tenant = TenantResolver::current();
        $thresholds = [
            'occ' => (int)($tenant['segment_occasionale'] ?? 2),
            'abi' => (int)($tenant['segment_abituale'] ?? 4),
            'vip' => (int)($tenant['segment_vip'] ?? 10),
        ];

        $customerModel = new Customer();

        // Segment stats (single SQL query, no full load)
        $stats = $customerModel->segmentCounts($tenantId, $thresholds);

        // Count + paginated fetch with search + segment filter
        $total = $customerModel->countByTenantFiltered($tenantId, $search, $segment, $thresholds);

        // Build base URL preserving filters
        $baseParams = [];
        if ($search) $baseParams[] = 'q=' . urlencode($search);
        if ($segment) $baseParams[] = 'segment=' . urlencode($segment);
        $baseUrl = url('dashboard/customers') . ($baseParams ? '?' . implode('&', $baseParams) : '');

        $paginator = new Paginator($total, $perPage, $page, $baseUrl);
        $customers = $customerModel->findByTenantPaginated(
            $tenantId, $search, $segment, $thresholds,
            $paginator->limit(), $paginator->offset()
        );

        view('dashboard/customers/index', [
            'title'      => 'Clienti',
            'activeMenu' => 'customers',
            'customers'  => $customers,
            'search'     => $search,
            'segment'    => $segment,
            'stats'      => $stats,
            'tenant'     => $tenant,
            'pagination' => $paginator->links(),
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
                'is_blocked'     => (bool)($c['is_blocked'] ?? false),
            ];
        }

        Response::json(['success' => true, 'data' => $results]);
    }

    public function stats(Request $request): void
    {
        $tenant = TenantResolver::current();
        $canUseStats = tenant_can('statistics');

        if (!$canUseStats) {
            view('dashboard/customers/stats', [
                'title'        => 'Statistiche Clienti',
                'activeMenu'   => 'customers',
                'tenant'       => $tenant,
                'canUseStats'  => false,
                'dateFrom'     => date('Y-m-d', strtotime('-90 days')),
                'dateTo'       => date('Y-m-d'),
                'kpi'          => ['total' => 0, 'new_in_period' => 0, 'avg_bookings' => 0, 'return_rate' => 0, 'noshow_rate' => 0],
                'topClients'   => [],
                'segments'     => ['nuovo' => 0, 'occasionale' => 0, 'abituale' => 0, 'vip' => 0, 'totale' => 0],
                'thresholds'   => ['occ' => 2, 'abi' => 4, 'vip' => 10],
            ], 'dashboard');
            return;
        }

        $tenantId = Auth::tenantId();

        // Period filter (default: last 90 days)
        $dateTo = $request->query('to', date('Y-m-d'));
        $dateFrom = $request->query('from', date('Y-m-d', strtotime('-90 days')));

        // Validate dates
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) $dateFrom = date('Y-m-d', strtotime('-90 days'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) $dateTo = date('Y-m-d');

        $thresholds = [
            'occ' => (int)($tenant['segment_occasionale'] ?? 2),
            'abi' => (int)($tenant['segment_abituale'] ?? 4),
            'vip' => (int)($tenant['segment_vip'] ?? 10),
        ];

        $customerModel = new Customer();
        $kpi = $customerModel->getStats($tenantId, $dateFrom, $dateTo);
        $topClients = $customerModel->getTopByFrequency($tenantId, $dateFrom, $dateTo);
        $segments = $customerModel->segmentCounts($tenantId, $thresholds);

        view('dashboard/customers/stats', [
            'title'      => 'Statistiche Clienti',
            'activeMenu' => 'customers',
            'canUseStats' => true,
            'tenant'     => $tenant,
            'dateFrom'   => $dateFrom,
            'dateTo'     => $dateTo,
            'kpi'        => $kpi,
            'topClients' => $topClients,
            'segments'   => $segments,
            'thresholds' => $thresholds,
        ], 'dashboard');
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

        AuditLog::log(AuditLog::CUSTOMER_NOTES_UPDATED, "Cliente ID: {$id}", Auth::id(), Auth::tenantId());

        flash('success', 'Note aggiornate.');
        Response::redirect(url("dashboard/customers/{$id}"));
    }

    public function toggleBlock(Request $request): void
    {
        $id = (int)$request->param('id');
        $customerModel = new Customer();
        $customer = $customerModel->findById($id);

        if (!$customer || (int)$customer['tenant_id'] !== (int)Auth::tenantId()) {
            flash('danger', 'Cliente non trovato.');
            Response::redirect(url('dashboard/customers'));
        }

        if ($customer['is_blocked']) {
            $customerModel->unblock($id);
            flash('success', $customer['first_name'] . ' ' . $customer['last_name'] . ' è stato sbloccato.');
        } else {
            $customerModel->block($id);
            flash('warning', $customer['first_name'] . ' ' . $customer['last_name'] . ' è stato bloccato. Non potrà prenotare.');
        }

        AuditLog::log(AuditLog::CUSTOMER_BLOCKED, "Cliente ID: {$id}", Auth::id(), Auth::tenantId());

        Response::redirect(url("dashboard/customers/{$id}"));
    }
}
