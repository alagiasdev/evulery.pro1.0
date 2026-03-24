<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Plan;
use App\Models\Service;
use App\Services\AuditLog;

class SubscriptionsController
{
    // ─── Tab: Abbonamenti ───────────────────────────────────────

    public function index(Request $request): void
    {
        $db = Database::getInstance();

        // KPI — MRR: prezzo effettivo del ciclo / mesi del ciclo
        $activeSubs = $db->query(
            "SELECT p.price, p.billing_months_semi, p.billing_months_annual,
                    s.billing_cycle, s.extra_discount
             FROM subscriptions s JOIN plans p ON p.id = s.plan_id
             WHERE s.status = 'active'"
        )->fetchAll();

        $mrr = 0;
        foreach ($activeSubs as $as) {
            $calc = Plan::calculatePrice($as, $as['billing_cycle'] ?? 'annual', (float)($as['extra_discount'] ?? 0));
            $mrr += $calc['monthly'];
        }

        $activeCount = (int)$db->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'active'")->fetchColumn();
        $trialCount  = (int)$db->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'trialing'")->fetchColumn();

        $expiringCount = (int)$db->query(
            "SELECT COUNT(*) FROM subscriptions
             WHERE status = 'active' AND current_period_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)"
        )->fetchColumn();

        // Filter
        $filter = $request->query('filter', '');
        $where  = '';
        if ($filter === 'active') {
            $where = " AND s.status = 'active'";
        } elseif ($filter === 'trialing') {
            $where = " AND s.status = 'trialing'";
        } elseif ($filter === 'expiring') {
            $where = " AND s.status = 'active' AND s.current_period_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
        } elseif ($filter === 'cancelled') {
            $where = " AND s.status IN ('cancelled','past_due')";
        }

        $subscriptions = $db->query(
            "SELECT s.*, t.name as tenant_name, t.slug as tenant_slug,
                    p.name as plan_name, p.color as plan_color, p.price as plan_price,
                    p.billing_months_semi, p.billing_months_annual
             FROM subscriptions s
             JOIN tenants t ON t.id = s.tenant_id
             LEFT JOIN plans p ON p.id = s.plan_id
             WHERE 1=1 {$where}
             ORDER BY s.created_at DESC"
        )->fetchAll();

        $plans = (new Plan())->allActive();

        view('admin/subscriptions/index', [
            'title'          => 'Abbonamenti',
            'activeMenu'     => 'subscriptions',
            'activeTab'      => 'subscriptions',
            'mrr'            => $mrr,
            'activeCount'    => $activeCount,
            'trialCount'     => $trialCount,
            'expiringCount'  => $expiringCount,
            'subscriptions'  => $subscriptions,
            'plans'          => $plans,
            'filter'         => $filter,
        ], 'admin');
    }

    public function changePlan(Request $request): void
    {
        $subId  = (int)$request->param('id');
        $planId = (int)$request->input('plan_id', 0);

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM subscriptions WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $subId]);
        $sub = $stmt->fetch();

        if (!$sub) {
            flash('danger', 'Abbonamento non trovato.');
            Response::redirect(url('admin/subscriptions'));
            return;
        }

        $plan = (new Plan())->findById($planId);
        if (!$plan) {
            flash('danger', 'Piano non valido.');
            Response::redirect(url('admin/subscriptions'));
            return;
        }

        $status        = $request->input('status', 'active');
        $periodStart   = $request->input('period_start', '') ?: null;
        $periodEnd     = $request->input('period_end', '') ?: null;
        $emailCredits  = (int)$request->input('email_credits', 0);
        $smsCredits    = (int)$request->input('sms_credits', 0);
        $billingCycle  = $request->input('billing_cycle', 'annual');
        $extraDiscount = (float)$request->input('extra_discount', 0);

        // Validate status
        $validStatuses = ['active', 'trialing', 'past_due', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            $status = 'active';
        }

        // Validate billing cycle
        if (!in_array($billingCycle, ['semiannual', 'annual'])) {
            $billingCycle = 'annual';
        }

        // Clamp extra discount 0-100
        $extraDiscount = max(0, min(100, $extraDiscount));

        // Calculate effective price for the cycle
        $calc = Plan::calculatePrice($plan, $billingCycle, $extraDiscount);

        // Update subscription
        $db->prepare(
            "UPDATE subscriptions
             SET plan_id = :pid, price = :price, billing_cycle = :bc,
                 extra_discount = :ed, status = :status,
                 current_period_start = :ps, current_period_end = :pe,
                 email_credits = :ec, sms_credits = :sc
             WHERE id = :id"
        )->execute([
            'pid'    => $planId,
            'price'  => $calc['total'],
            'bc'     => $billingCycle,
            'ed'     => $extraDiscount,
            'status' => $status,
            'ps'     => $periodStart,
            'pe'     => $periodEnd,
            'ec'     => $emailCredits,
            'sc'     => $smsCredits,
            'id'     => $subId,
        ]);

        // Update tenant plan_id + sync email credits balance
        $db->prepare("UPDATE tenants SET plan_id = :pid, email_credits_balance = :ecb WHERE id = :tid")
            ->execute(['pid' => $planId, 'ecb' => $emailCredits, 'tid' => $sub['tenant_id']]);

        // Log credit transaction if credits changed
        $oldCredits = (int)($sub['email_credits'] ?? 0);
        if ($emailCredits !== $oldCredits) {
            $diff = $emailCredits - $oldCredits;
            $db->prepare(
                'INSERT INTO email_credit_transactions (tenant_id, amount, type, description, assigned_by, created_at)
                 VALUES (:tid, :amount, :type, :desc, :by, NOW())'
            )->execute([
                'tid'    => $sub['tenant_id'],
                'amount' => $diff,
                'type'   => 'assignment',
                'desc'   => "Modifica da abbonamento: {$oldCredits} → {$emailCredits}",
                'by'     => Auth::id(),
            ]);
        }

        AuditLog::log(AuditLog::SUBSCRIPTION_CHANGED, "Tenant: {$sub['tenant_id']}, Piano: {$plan['name']}, Ciclo: {$billingCycle}", Auth::id());

        flash('success', "Abbonamento aggiornato.");
        Response::redirect(url('admin/subscriptions'));
    }

    // ─── Tab: Piani ─────────────────────────────────────────────

    public function plans(Request $request): void
    {
        $planModel = new Plan();
        $plans     = $planModel->allWithServices();
        $services  = (new Service())->allActive();

        view('admin/subscriptions/plans', [
            'title'     => 'Piani',
            'activeMenu'=> 'subscriptions',
            'activeTab' => 'plans',
            'plans'     => $plans,
            'services'  => $services,
            'editId'    => null,
        ], 'admin');
    }

    public function storePlan(Request $request): void
    {
        $name  = trim($request->input('name', ''));
        $price = (float)$request->input('price', 0);
        $color = trim($request->input('color', '#1565C0'));
        $desc  = trim($request->input('description', ''));
        $svcIds = $request->input('services', []);

        if ($name === '' || $price < 0) {
            flash('danger', 'Nome e prezzo sono obbligatori.');
            Response::redirect(url('admin/subscriptions/plans'));
            return;
        }

        // Generate slug
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $slug = trim($slug, '-');

        $planModel = new Plan();

        // Check unique slug
        if ($planModel->findBySlug($slug)) {
            $slug .= '-' . time();
        }

        $billingMonthsSemi   = max(1, min(6, (int)$request->input('billing_months_semi', 5)));
        $billingMonthsAnnual = max(1, min(12, (int)$request->input('billing_months_annual', 10)));

        $id = $planModel->create([
            'name'                 => $name,
            'slug'                 => $slug,
            'description'          => $desc ?: null,
            'price'                => $price,
            'color'                => $color,
            'billing_months_semi'  => $billingMonthsSemi,
            'billing_months_annual'=> $billingMonthsAnnual,
        ]);

        if (!empty($svcIds) && is_array($svcIds)) {
            $planModel->syncServices($id, array_map('intval', $svcIds));
        }

        AuditLog::log(AuditLog::PLAN_CREATED, "Piano: {$name} (ID: {$id})", Auth::id());

        flash('success', "Piano \"{$name}\" creato.");
        Response::redirect(url('admin/subscriptions/plans'));
    }

    public function editPlan(Request $request): void
    {
        $id = (int)$request->param('id');
        $planModel = new Plan();
        $plan = $planModel->findById($id);

        if (!$plan) {
            flash('danger', 'Piano non trovato.');
            Response::redirect(url('admin/subscriptions/plans'));
            return;
        }

        $plans    = $planModel->allWithServices();
        $services = (new Service())->allActive();
        $editServiceIds = $planModel->getServiceIds($id);

        view('admin/subscriptions/plans', [
            'title'          => 'Modifica Piano',
            'activeMenu'     => 'subscriptions',
            'activeTab'      => 'plans',
            'plans'          => $plans,
            'services'       => $services,
            'editId'         => $id,
            'editPlan'       => $plan,
            'editServiceIds' => $editServiceIds,
        ], 'admin');
    }

    public function updatePlan(Request $request): void
    {
        $id = (int)$request->param('id');
        $planModel = new Plan();
        $plan = $planModel->findById($id);

        if (!$plan) {
            flash('danger', 'Piano non trovato.');
            Response::redirect(url('admin/subscriptions/plans'));
            return;
        }

        $name  = trim($request->input('name', ''));
        $price = (float)$request->input('price', 0);
        $color = trim($request->input('color', '#1565C0'));
        $desc  = trim($request->input('description', ''));
        $svcIds = $request->input('services', []);

        if ($name === '' || $price < 0) {
            flash('danger', 'Nome e prezzo sono obbligatori.');
            Response::redirect(url("admin/subscriptions/plans/{$id}/edit"));
            return;
        }

        $billingMonthsSemi   = max(1, min(6, (int)$request->input('billing_months_semi', 5)));
        $billingMonthsAnnual = max(1, min(12, (int)$request->input('billing_months_annual', 10)));

        // Slug only if name changed
        $data = [
            'name'                 => $name,
            'description'          => $desc ?: null,
            'price'                => $price,
            'color'                => $color,
            'billing_months_semi'  => $billingMonthsSemi,
            'billing_months_annual'=> $billingMonthsAnnual,
        ];

        if ($name !== $plan['name']) {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
            $slug = trim($slug, '-');
            $existing = $planModel->findBySlug($slug);
            if ($existing && (int)$existing['id'] !== $id) {
                $slug .= '-' . time();
            }
            $data['slug'] = $slug;
        }

        $planModel->update($id, $data);
        $planModel->syncServices($id, is_array($svcIds) ? array_map('intval', $svcIds) : []);

        AuditLog::log(AuditLog::PLAN_UPDATED, "Piano: {$name} (ID: {$id})", Auth::id());

        flash('success', "Piano \"{$name}\" aggiornato.");
        Response::redirect(url('admin/subscriptions/plans'));
    }

    public function duplicatePlan(Request $request): void
    {
        $id = (int)$request->param('id');
        $planModel = new Plan();
        $newId = $planModel->duplicate($id);

        if ($newId) {
            flash('success', 'Piano duplicato. Modificalo per personalizzarlo.');
        } else {
            flash('danger', 'Piano non trovato.');
        }
        Response::redirect(url('admin/subscriptions/plans'));
    }

    public function deletePlan(Request $request): void
    {
        $id = (int)$request->param('id');
        $planModel = new Plan();
        $plan = $planModel->findById($id);

        if (!$plan) {
            flash('danger', 'Piano non trovato.');
            Response::redirect(url('admin/subscriptions/plans'));
            return;
        }

        if ($plan['is_default']) {
            flash('danger', 'Non puoi eliminare il piano predefinito.');
            Response::redirect(url('admin/subscriptions/plans'));
            return;
        }

        if ($planModel->hasSubscriptions($id)) {
            flash('danger', 'Non puoi eliminare un piano con abbonamenti attivi. Sposta prima i tenant a un altro piano.');
            Response::redirect(url('admin/subscriptions/plans'));
            return;
        }

        $planModel->delete($id);
        AuditLog::log(AuditLog::PLAN_DELETED, "Piano: {$plan['name']} (ID: {$id})", Auth::id());

        flash('success', "Piano \"{$plan['name']}\" eliminato.");
        Response::redirect(url('admin/subscriptions/plans'));
    }

    // ─── Tab: Servizi ───────────────────────────────────────────

    public function services(Request $request): void
    {
        $services = (new Service())->allWithPlans();

        view('admin/subscriptions/services', [
            'title'     => 'Servizi',
            'activeMenu'=> 'subscriptions',
            'activeTab' => 'services',
            'services'  => $services,
        ], 'admin');
    }

    public function storeService(Request $request): void
    {
        $key  = trim($request->input('key', ''));
        $name = trim($request->input('name', ''));
        $desc = trim($request->input('description', ''));

        if ($key === '' || $name === '') {
            flash('danger', 'Chiave e nome sono obbligatori.');
            Response::redirect(url('admin/subscriptions/services'));
            return;
        }

        // Sanitize key
        $key = strtolower(preg_replace('/[^a-z0-9_]+/i', '_', $key));

        $svcModel = new Service();
        if ($svcModel->findByKey($key)) {
            flash('danger', "Un servizio con chiave \"{$key}\" esiste già.");
            Response::redirect(url('admin/subscriptions/services'));
            return;
        }

        $svcModel->create([
            'key'         => $key,
            'name'        => $name,
            'description' => $desc ?: null,
        ]);

        AuditLog::log(AuditLog::SERVICE_CREATED, "Servizio: {$name}", Auth::id());

        flash('success', "Servizio \"{$name}\" creato.");
        Response::redirect(url('admin/subscriptions/services'));
    }

    public function updateService(Request $request): void
    {
        $id = (int)$request->param('id');
        $svcModel = new Service();
        $svc = $svcModel->findById($id);

        if (!$svc) {
            flash('danger', 'Servizio non trovato.');
            Response::redirect(url('admin/subscriptions/services'));
            return;
        }

        $name = trim($request->input('name', ''));
        $desc = trim($request->input('description', ''));

        if ($name === '') {
            flash('danger', 'Il nome è obbligatorio.');
            Response::redirect(url('admin/subscriptions/services'));
            return;
        }

        $svcModel->update($id, [
            'name'        => $name,
            'description' => $desc ?: null,
        ]);

        AuditLog::log(AuditLog::SERVICE_UPDATED, "Servizio: {$name} (ID: {$id})", Auth::id());

        flash('success', "Servizio \"{$name}\" aggiornato.");
        Response::redirect(url('admin/subscriptions/services'));
    }

    public function deleteService(Request $request): void
    {
        $id = (int)$request->param('id');
        $svcModel = new Service();
        $svc = $svcModel->findById($id);

        if (!$svc) {
            flash('danger', 'Servizio non trovato.');
            Response::redirect(url('admin/subscriptions/services'));
            return;
        }

        if (!$svcModel->delete($id)) {
            flash('danger', "Non puoi eliminare \"{$svc['name']}\": è associato a uno o più piani.");
            Response::redirect(url('admin/subscriptions/services'));
            return;
        }

        AuditLog::log(AuditLog::SERVICE_DELETED, "Servizio: {$svc['name']} (ID: {$id})", Auth::id());

        flash('success', "Servizio \"{$svc['name']}\" eliminato.");
        Response::redirect(url('admin/subscriptions/services'));
    }
}
