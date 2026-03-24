<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Services\AuditLog;

class UsersController
{
    public function index(Request $request): void
    {
        $search   = $request->query('q', '');
        $role     = $request->query('role', '');
        $tenantId = $request->query('tenant', '');
        $status   = $request->query('status', '');
        $page     = max(1, (int)$request->query('page', 1));
        $perPage  = 25;

        $userModel = new User();

        // Convert filters
        $roleFilter   = $role !== '' ? $role : null;
        $tenantFilter = $tenantId !== '' ? (int)$tenantId : null;
        $activeFilter = $status !== '' ? (int)$status : null;

        $total = $userModel->countFiltered($search ?: null, $roleFilter, $tenantFilter, $activeFilter);

        // Build base URL preserving filters
        $baseParams = [];
        if ($search !== '')   $baseParams[] = 'q=' . urlencode($search);
        if ($role !== '')     $baseParams[] = 'role=' . urlencode($role);
        if ($tenantId !== '') $baseParams[] = 'tenant=' . urlencode($tenantId);
        if ($status !== '')   $baseParams[] = 'status=' . urlencode($status);
        $baseUrl = url('admin/users') . ($baseParams ? '?' . implode('&', $baseParams) : '');

        $paginator = new Paginator($total, $perPage, $page, $baseUrl);
        $users = $userModel->allPaginated($search ?: null, $roleFilter, $tenantFilter, $activeFilter, $paginator->limit(), $paginator->offset());

        // Tenants for dropdown filter
        $db = Database::getInstance();
        $tenants = $db->query("SELECT id, name FROM tenants ORDER BY name")->fetchAll();

        view('admin/users/index', [
            'title'      => 'Utenti',
            'activeMenu' => 'users',
            'users'      => $users,
            'tenants'    => $tenants,
            'pagination' => $paginator->links(),
            'filter'     => [
                'q'      => $search,
                'role'   => $role,
                'tenant' => $tenantId,
                'status' => $status,
            ],
        ], 'admin');
    }

    public function impersonate(Request $request): void
    {
        $userId = (int)$request->param('id');
        $userModel = new User();
        $target = $userModel->findById($userId);

        if (!$target) {
            flash('danger', 'Utente non trovato.');
            Response::redirect(url('admin/users'));
            return;
        }

        if ($target['role'] === 'super_admin') {
            flash('danger', 'Non puoi impersonare un altro Super Admin.');
            Response::redirect(url('admin/users'));
            return;
        }

        if (!$target['is_active']) {
            flash('danger', 'Non puoi impersonare un utente inattivo.');
            Response::redirect(url('admin/users'));
            return;
        }

        if (!$target['tenant_id']) {
            flash('danger', 'Utente non associato a nessun ristorante.');
            Response::redirect(url('admin/users'));
            return;
        }

        // Get tenant name for logging
        $db = Database::getInstance();
        $stmtT = $db->prepare("SELECT name FROM tenants WHERE id = :id LIMIT 1");
        $stmtT->execute(['id' => $target['tenant_id']]);
        $tenantName = $stmtT->fetchColumn() ?: 'N/A';

        $adminName = Auth::user()['name'] ?? 'Admin';
        $adminId = Auth::id();

        AuditLog::log(
            AuditLog::IMPERSONATION_START,
            "{$adminName} (ID:{$adminId}) → {$target['first_name']} {$target['last_name']} (ID:{$userId}) @ {$tenantName}",
            $adminId
        );

        Auth::startImpersonation($userId);

        Response::redirect(url('dashboard'));
    }
}
