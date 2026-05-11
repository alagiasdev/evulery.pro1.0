<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Core\Database;
use App\Models\Tenant;
use App\Models\User;
use App\Models\MealCategory;
use App\Models\Plan;
use App\Models\DemoRequest;
use App\Services\AuditLog;

class TenantsController
{
    public function index(Request $request): void
    {
        $search = $request->query('q');
        $page = max(1, (int)$request->query('page', 1));
        $perPage = 25;

        $tenantModel = new Tenant();
        $total = $tenantModel->countFiltered($search);

        $baseParams = [];
        if ($search) $baseParams[] = 'q=' . urlencode($search);
        $baseUrl = url('admin/tenants') . ($baseParams ? '?' . implode('&', $baseParams) : '');

        $paginator = new Paginator($total, $perPage, $page, $baseUrl);
        $tenants = $tenantModel->allPaginated($search, $paginator->limit(), $paginator->offset());

        view('admin/tenants/index', [
            'title'      => 'Ristoranti',
            'activeMenu' => 'tenants',
            'tenants'    => $tenants,
            'search'     => $search,
            'pagination' => $paginator->links(),
        ], 'admin');
    }

    public function create(Request $request): void
    {
        $plans = (new Plan())->allActive();

        // Se proviene da conversione lead: pre-compila campi e carica info reseller
        $leadId = (int)$request->query('lead_id', 0);
        $sourceLead = null;
        $leadResellerName = null;
        if ($leadId > 0) {
            $sourceLead = (new DemoRequest())->findById($leadId);
            if ($sourceLead && !empty($sourceLead['assigned_reseller_id'])) {
                $db = Database::getInstance();
                $stmt = $db->prepare("SELECT first_name, last_name FROM users WHERE id = :id LIMIT 1");
                $stmt->execute(['id' => $sourceLead['assigned_reseller_id']]);
                $r = $stmt->fetch();
                if ($r) {
                    $leadResellerName = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
                }
            }
        }

        view('admin/tenants/create', [
            'title'            => 'Nuovo Ristorante',
            'activeMenu'       => 'tenants',
            'plans'            => $plans,
            'prefill'          => $request->query(),
            'sourceLead'       => $sourceLead,
            'leadResellerName' => $leadResellerName,
        ], 'admin');
    }

    public function store(Request $request): void
    {
        $data = $request->all();

        $v = Validator::make($data)
            ->required('name', 'Nome')
            ->required('email', 'Email')
            ->email('email', 'Email')
            ->required('owner_email', 'Email proprietario')
            ->email('owner_email', 'Email proprietario')
            ->required('owner_password', 'Password proprietario')
            ->minLength('owner_password', 8, 'Password proprietario')
            ->passwordStrength('owner_password', 'Password proprietario')
            ->required('owner_first_name', 'Nome proprietario')
            ->required('owner_last_name', 'Cognome proprietario');

        if ($v->fails()) {
            flash('danger', $v->firstError());
            \App\Core\Session::flash('old_input', $data);
            Response::redirect(url('admin/tenants/create'));
        }

        $slug = slugify($data['name']);

        // Check slug uniqueness
        $tenantModel = new Tenant();
        if ($tenantModel->findBySlug($slug)) {
            $slug .= '-' . bin2hex(random_bytes(4));
        }

        // Resolve plan
        $planId = (int)($data['plan_id'] ?? 0);
        $planModel = new Plan();
        $plan = $planModel->findById($planId);
        if (!$plan) {
            // Fallback to default plan
            $plans = $planModel->allActive();
            $plan = $plans[0] ?? null;
            $planId = $plan ? (int)$plan['id'] : 0;
        }

        // Se proviene da conversione lead, recupera reseller del lead per snapshot
        $leadId = (int)($data['lead_id'] ?? 0);
        $acquiredByReseller = null;
        $sourceLead = null;
        if ($leadId > 0) {
            $sourceLead = (new DemoRequest())->findById($leadId);
            if ($sourceLead && !empty($sourceLead['assigned_reseller_id'])) {
                $acquiredByReseller = (int)$sourceLead['assigned_reseller_id'];
            }
        }

        // Create tenant
        $tenantId = $tenantModel->create([
            'slug'      => $slug,
            'name'      => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
            'address'   => $data['address'] ?? null,
            'plan'      => 'base',
            'plan_id'   => $planId,
            'is_active' => isset($data['is_active']) ? 1 : 0,
            'acquired_by_reseller_id' => $acquiredByReseller,
        ]);

        // Create subscription
        if ($plan) {
            $db = Database::getInstance();
            $calc = Plan::calculatePrice($plan, 'annual', 0);
            $db->prepare(
                "INSERT INTO subscriptions (tenant_id, plan_id, plan, price, billing_cycle, extra_discount, status, current_period_start, current_period_end)
                 VALUES (:tid, :pid, 'base', :price, 'annual', 0, 'active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 12 MONTH))"
            )->execute([
                'tid'   => $tenantId,
                'pid'   => $planId,
                'price' => $calc['total'],
            ]);
        }

        // Seed default meal categories
        (new MealCategory())->seedDefaults($tenantId);

        // Create owner user
        $userModel = new User();
        $userModel->create([
            'tenant_id'  => $tenantId,
            'email'      => $data['owner_email'],
            'password'   => $data['owner_password'],
            'first_name' => $data['owner_first_name'],
            'last_name'  => $data['owner_last_name'],
            'role'       => 'owner',
        ]);

        AuditLog::log(AuditLog::TENANT_CREATED, "Tenant: {$data['name']} (ID: {$tenantId})", Auth::id());

        // Se origine lead: marca il lead come convertito e logga activity
        if ($sourceLead) {
            $db = Database::getInstance();
            $db->prepare(
                "UPDATE demo_requests
                 SET status = 'customer', converted_tenant_id = :tid, converted_at = NOW(), status_changed_at = NOW()
                 WHERE id = :lid"
            )->execute(['tid' => $tenantId, 'lid' => $leadId]);

            (new DemoRequest())->logActivity(
                $leadId,
                'converted',
                "Convertito a cliente: tenant #{$tenantId} ({$data['name']})",
                Auth::id(),
                ['tenant_id' => $tenantId]
            );
        }

        flash('success', "Ristorante \"{$data['name']}\" creato con successo.");
        Response::redirect(url('admin/tenants'));
    }

    public function edit(Request $request): void
    {
        $id = (int)$request->param('id');
        $tenant = (new Tenant())->findById($id);

        if (!$tenant) {
            flash('danger', 'Ristorante non trovato.');
            Response::redirect(url('admin/tenants'));
        }

        $users = (new User())->findByTenant($id);
        $plans = (new Plan())->allActive();

        // Credit transactions (last 10)
        $db = Database::getInstance();
        $creditTx = $db->prepare(
            'SELECT ct.*, u.first_name AS assigned_first, u.last_name AS assigned_last
             FROM email_credit_transactions ct
             LEFT JOIN users u ON u.id = ct.assigned_by
             WHERE ct.tenant_id = :tid ORDER BY ct.created_at DESC LIMIT 10'
        );
        $creditTx->execute(['tid' => $id]);

        view('admin/tenants/edit', [
            'title'          => 'Modifica Ristorante',
            'activeMenu'     => 'tenants',
            'tenant'         => $tenant,
            'users'          => $users,
            'plans'          => $plans,
            'creditHistory'  => $creditTx->fetchAll(),
        ], 'admin');
    }

    public function update(Request $request): void
    {
        $id = (int)$request->param('id');
        $data = $request->all();

        $v = Validator::make($data)
            ->required('name', 'Nome')
            ->required('email', 'Email')
            ->email('email', 'Email');

        if ($v->fails()) {
            flash('danger', $v->firstError());
            Response::redirect(url("admin/tenants/{$id}/edit"));
        }

        $planId = (int)($data['plan_id'] ?? 0);
        $planModel = new Plan();
        $plan = $planModel->findById($planId);

        $tenantModel = new Tenant();
        $tenant = $tenantModel->findById($id);
        $oldPlanId = (int)($tenant['plan_id'] ?? 0);

        $tenantModel->update($id, [
            'name'           => $data['name'],
            'email'          => $data['email'],
            'phone'          => $data['phone'] ?? null,
            'address'        => $data['address'] ?? null,
            'plan_id'        => $planId ?: null,
            'table_duration' => $data['table_duration'] ?? 90,
            'time_step'      => $data['time_step'] ?? 30,
            'is_active'      => isset($data['is_active']) ? 1 : 0,
        ]);

        // Update or create subscription if plan changed
        if ($plan && $planId !== $oldPlanId) {
            $db = Database::getInstance();
            $existing = $db->prepare(
                "SELECT id FROM subscriptions WHERE tenant_id = :tid ORDER BY created_at DESC LIMIT 1"
            );
            $existing->execute(['tid' => $id]);
            $sub = $existing->fetch();

            if ($sub) {
                $db->prepare(
                    "UPDATE subscriptions SET plan_id = :pid, price = :price WHERE id = :sid"
                )->execute(['pid' => $planId, 'price' => $plan['price'], 'sid' => $sub['id']]);
            } else {
                $calc = Plan::calculatePrice($plan, 'annual', 0);
                $db->prepare(
                    "INSERT INTO subscriptions (tenant_id, plan_id, plan, price, billing_cycle, extra_discount, status, current_period_start, current_period_end)
                     VALUES (:tid, :pid, 'base', :price, 'annual', 0, 'active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 12 MONTH))"
                )->execute(['tid' => $id, 'pid' => $planId, 'price' => $calc['total']]);
            }
        }

        flash('success', 'Ristorante aggiornato con successo.');
        Response::redirect(url("admin/tenants/{$id}/edit"));
    }

    public function toggle(Request $request): void
    {
        $id = (int)$request->param('id');
        (new Tenant())->toggleActive($id);
        AuditLog::log(AuditLog::TENANT_TOGGLED, "Tenant ID: {$id}", Auth::id());
        flash('success', 'Stato aggiornato.');
        Response::redirect(url('admin/tenants'));
    }

    public function updateUser(Request $request): void
    {
        $tenantId = (int)$request->param('id');
        $userId = (int)$request->param('userId');
        $data = $request->all();

        $userModel = new User();
        $user = $userModel->findById($userId);

        if (!$user || (int)$user['tenant_id'] !== $tenantId) {
            flash('danger', 'Utente non trovato.');
            Response::redirect(url("admin/tenants/{$tenantId}/edit"));
        }

        $v = Validator::make($data)
            ->required('first_name', 'Nome')
            ->required('last_name', 'Cognome')
            ->required('email', 'Email')
            ->email('email', 'Email');

        if ($v->fails()) {
            flash('danger', $v->firstError());
            Response::redirect(url("admin/tenants/{$tenantId}/edit"));
        }

        // Check email uniqueness
        $existing = $userModel->findByEmail($data['email']);
        if ($existing && (int)$existing['id'] !== $userId) {
            flash('danger', 'Questa email è già utilizzata da un altro account.');
            Response::redirect(url("admin/tenants/{$tenantId}/edit"));
        }

        $userModel->update($userId, [
            'first_name' => trim($data['first_name']),
            'last_name'  => trim($data['last_name']),
            'email'      => trim($data['email']),
        ]);

        flash('success', 'Utente aggiornato.');
        Response::redirect(url("admin/tenants/{$tenantId}/edit"));
    }

    public function assignCredits(Request $request): void
    {
        $tenantId = (int)$request->param('id');
        $amount = (int)$request->input('credits_amount', 0);

        // Validazione: 0 esplicito non valido, range -10000 a 10000
        if ($amount === 0 || $amount < -10000 || $amount > 10000) {
            flash('danger', 'Inserisci un numero di crediti valido tra -10000 e 10000 (escluso 0).');
            Response::redirect(url("admin/tenants/{$tenantId}/edit"));
            return;
        }

        $tenantModel = new Tenant();
        $tenant = $tenantModel->findById($tenantId);
        if (!$tenant) {
            flash('danger', 'Ristorante non trovato.');
            Response::redirect(url('admin/tenants'));
            return;
        }

        // Validazione bilancio: il saldo non può scendere sotto 0
        $currentBalance = (int)($tenant['email_credits_balance'] ?? 0);
        if ($currentBalance + $amount < 0) {
            flash('danger', "Operazione non consentita: il saldo scenderebbe sotto zero. Saldo attuale: {$currentBalance}, richiesta: {$amount}.");
            Response::redirect(url("admin/tenants/{$tenantId}/edit"));
            return;
        }

        $tenantModel->addCredits($tenantId, $amount);

        // Description e flash dinamici in base al segno
        $isAdd = $amount > 0;
        $absAmount = abs($amount);
        $description = $isAdd
            ? "Assegnazione manuale di {$absAmount} crediti"
            : "Rimozione manuale di {$absAmount} crediti";

        // Log transaction (amount mantiene il segno per audit corretto)
        $db = Database::getInstance();
        $db->prepare(
            'INSERT INTO email_credit_transactions (tenant_id, amount, type, description, assigned_by, created_at)
             VALUES (:tid, :amount, :type, :desc, :by, NOW())'
        )->execute([
            'tid'    => $tenantId,
            'amount' => $amount,
            'type'   => 'assignment',
            'desc'   => $description,
            'by'     => Auth::id(),
        ]);

        AuditLog::log(
            AuditLog::EMAIL_CREDITS_ASSIGNED,
            $description . " a {$tenant['name']}",
            Auth::id()
        );

        $flashMsg = $isAdd
            ? "Assegnati {$absAmount} crediti email a {$tenant['name']}."
            : "Rimossi {$absAmount} crediti email da {$tenant['name']}.";
        flash('success', $flashMsg);
        Response::redirect(url("admin/tenants/{$tenantId}/edit"));
    }
}
