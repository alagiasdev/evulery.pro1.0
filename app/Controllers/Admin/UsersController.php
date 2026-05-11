<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Models\ResellerProfile;
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

    // ─── Reseller CRUD ─────────────────────────────────────────────

    public function createReseller(Request $request): void
    {
        view('admin/users/reseller-form', [
            'title'      => 'Nuovo Reseller',
            'activeMenu' => 'users',
            'user'       => null,
            'profile'    => null,
            'defaults'   => [
                'commission_setup'        => ResellerProfile::DEFAULT_COMMISSION_SETUP,
                'commission_starter'      => ResellerProfile::DEFAULT_COMMISSION_STARTER,
                'commission_professional' => ResellerProfile::DEFAULT_COMMISSION_PROFESSIONAL,
                'commission_enterprise'   => ResellerProfile::DEFAULT_COMMISSION_ENTERPRISE,
            ],
        ], 'admin');
    }

    public function storeReseller(Request $request): void
    {
        $data = $request->all();
        $firstName = trim($data['first_name'] ?? '');
        $lastName  = trim($data['last_name'] ?? '');
        $email     = trim($data['email'] ?? '');
        $password  = $data['password'] ?? '';

        if (!$firstName || !$lastName || !$email || !$password) {
            flash('danger', 'Compila tutti i campi obbligatori.');
            Response::redirect(url('admin/users/reseller/create'));
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('danger', 'Email non valida.');
            Response::redirect(url('admin/users/reseller/create'));
            return;
        }

        if (strlen($password) < 8) {
            flash('danger', 'La password deve essere almeno 8 caratteri.');
            Response::redirect(url('admin/users/reseller/create'));
            return;
        }

        $userModel = new User();
        if ($userModel->findByEmail($email)) {
            flash('danger', 'Esiste già un utente con questa email.');
            Response::redirect(url('admin/users/reseller/create'));
            return;
        }

        $userId = $userModel->create([
            'tenant_id'  => null,
            'email'      => $email,
            'password'   => $password,
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'role'       => 'reseller',
            'is_active'  => 1,
        ]);

        (new ResellerProfile())->create($userId, [
            'commission_setup'        => $this->parseCommission($data['commission_setup'] ?? null, ResellerProfile::DEFAULT_COMMISSION_SETUP),
            'commission_starter'      => $this->parseCommission($data['commission_starter'] ?? null, ResellerProfile::DEFAULT_COMMISSION_STARTER),
            'commission_professional' => $this->parseCommission($data['commission_professional'] ?? null, ResellerProfile::DEFAULT_COMMISSION_PROFESSIONAL),
            'commission_enterprise'   => $this->parseCommission($data['commission_enterprise'] ?? null, ResellerProfile::DEFAULT_COMMISSION_ENTERPRISE),
            'notes'                   => trim($data['notes'] ?? '') ?: null,
        ]);

        AuditLog::log(
            AuditLog::USER_CREATED ?? 'user.created',
            "Reseller creato: {$firstName} {$lastName} ({$email})",
            Auth::id()
        );

        flash('success', "Reseller \"{$firstName} {$lastName}\" creato.");
        Response::redirect(url('admin/users/reseller/' . $userId . '/edit'));
    }

    public function editReseller(Request $request): void
    {
        $userId = (int)$request->param('id');
        $userModel = new User();
        $user = $userModel->findById($userId);

        if (!$user || $user['role'] !== 'reseller') {
            flash('danger', 'Reseller non trovato.');
            Response::redirect(url('admin/users') . '?role=reseller');
            return;
        }

        $profile = (new ResellerProfile())->findByUserId($userId);

        view('admin/users/reseller-form', [
            'title'      => "Reseller: {$user['first_name']} {$user['last_name']}",
            'activeMenu' => 'users',
            'user'       => $user,
            'profile'    => $profile,
            'defaults'   => null,
        ], 'admin');
    }

    public function updateReseller(Request $request): void
    {
        $userId = (int)$request->param('id');
        $userModel = new User();
        $user = $userModel->findById($userId);

        if (!$user || $user['role'] !== 'reseller') {
            flash('danger', 'Reseller non trovato.');
            Response::redirect(url('admin/users') . '?role=reseller');
            return;
        }

        $data = $request->all();
        $firstName = trim($data['first_name'] ?? $user['first_name']);
        $lastName  = trim($data['last_name'] ?? $user['last_name']);
        $email     = trim($data['email'] ?? $user['email']);
        $isActive  = isset($data['is_active']) ? 1 : 0;
        $password  = $data['password'] ?? '';

        if (!$firstName || !$lastName || !$email) {
            flash('danger', 'Compila tutti i campi obbligatori.');
            Response::redirect(url("admin/users/reseller/{$userId}/edit"));
            return;
        }

        $updateData = [
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'email'      => $email,
            'is_active'  => $isActive,
        ];
        if ($password !== '') {
            if (strlen($password) < 8) {
                flash('danger', 'La password deve essere almeno 8 caratteri.');
                Response::redirect(url("admin/users/reseller/{$userId}/edit"));
                return;
            }
            $updateData['password'] = $password;
        }

        $userModel->update($userId, $updateData);

        (new ResellerProfile())->update($userId, [
            'commission_setup'        => $this->parseCommission($data['commission_setup'] ?? null, ResellerProfile::DEFAULT_COMMISSION_SETUP),
            'commission_starter'      => $this->parseCommission($data['commission_starter'] ?? null, ResellerProfile::DEFAULT_COMMISSION_STARTER),
            'commission_professional' => $this->parseCommission($data['commission_professional'] ?? null, ResellerProfile::DEFAULT_COMMISSION_PROFESSIONAL),
            'commission_enterprise'   => $this->parseCommission($data['commission_enterprise'] ?? null, ResellerProfile::DEFAULT_COMMISSION_ENTERPRISE),
            'notes'                   => trim($data['notes'] ?? '') ?: null,
        ]);

        AuditLog::log(
            AuditLog::USER_UPDATED ?? 'user.updated',
            "Reseller aggiornato: {$firstName} {$lastName} ({$email})",
            Auth::id()
        );

        flash('success', 'Reseller aggiornato.');
        Response::redirect(url("admin/users/reseller/{$userId}/edit"));
    }

    private function parseCommission($value, float $default): float
    {
        if ($value === null || $value === '') {
            return $default;
        }
        $clean = (float)str_replace(',', '.', (string)$value);
        return max(0, $clean);
    }
}
