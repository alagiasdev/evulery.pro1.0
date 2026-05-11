<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\CreditRechargeRequest;
use App\Models\Tenant;
use App\Services\AuditLog;
use App\Services\MailService;

/**
 * Admin: gestione ricariche crediti richieste dai reseller.
 *
 * Workflow:
 *  - GET  /admin/credit-requests              → lista (filtro stato)
 *  - POST /admin/credit-requests/{id}/approve → approva + accredita + email
 *  - POST /admin/credit-requests/{id}/reject  → rifiuta + email con motivo
 */
class CreditRequestsController
{
    public function index(Request $request): void
    {
        $model = new CreditRechargeRequest();
        $status = $request->query('status', '') ?: null;

        $list = $model->listAll($status);
        $counts = $model->countByStatus();

        view('admin/credit-requests/index', [
            'title'       => 'Ricariche crediti',
            'activeMenu'  => 'credit-requests',
            'requests'    => $list,
            'counts'      => $counts,
            'statuses'    => CreditRechargeRequest::STATUSES,
            'filterStatus'=> $status,
        ], 'admin');
    }

    public function approve(Request $request): void
    {
        $id = (int)$request->param('id');
        $adminId = Auth::id();
        $model = new CreditRechargeRequest();
        $notes = trim($request->input('notes', '')) ?: null;

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            // 1) Lock di riga sulla richiesta + verifica stato pending atomicamente
            $stmt = $db->prepare(
                'SELECT r.id, r.tenant_id, r.credits_requested, r.status, t.is_active AS tenant_active
                 FROM credit_recharge_requests r
                 LEFT JOIN tenants t ON t.id = r.tenant_id
                 WHERE r.id = :id FOR UPDATE'
            );
            $stmt->execute(['id' => $id]);
            $req = $stmt->fetch();
            if (!$req || $req['status'] !== 'pending') {
                $db->rollBack();
                flash('danger', 'Richiesta non trovata o già processata.');
                Response::redirect(url('admin/credit-requests'));
                return;
            }
            if ((int)$req['tenant_active'] !== 1) {
                $db->rollBack();
                flash('danger', 'Impossibile approvare: il cliente non è attivo.');
                Response::redirect(url('admin/credit-requests'));
                return;
            }

            // 2) Marca approvata
            if (!$model->approve($id, $adminId, $notes)) {
                throw new \RuntimeException('Approve update failed');
            }
            // 3) Accredita crediti al tenant
            (new Tenant())->addCredits((int)$req['tenant_id'], (int)$req['credits_requested']);
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            app_log('Credit request approve error: ' . $e->getMessage());
            flash('danger', 'Errore durante l\'approvazione. Riprova.');
            Response::redirect(url('admin/credit-requests'));
            return;
        }

        // Riprende il record completo (incl. tenant_name + reseller email + saldo) per audit + email
        $req = $model->findById($id);

        AuditLog::log(
            AuditLog::EMAIL_CREDITS_ASSIGNED,
            "Ricarica approvata: +{$req['credits_requested']} crediti a {$req['tenant_name']} (reseller #{$req['reseller_id']})",
            $adminId,
            (int)$req['tenant_id']
        );

        try {
            $reseller = [
                'email'      => $req['reseller_email'] ?? null,
                'first_name' => $req['reseller_first_name'] ?? '',
            ];
            if ($reseller['email']) {
                MailService::sendCreditRequestApproved($reseller, $req);
            }
        } catch (\Throwable $e) {
            app_log('Credit approved email error: ' . $e->getMessage());
        }

        flash('success', "Ricarica approvata: +{$req['credits_requested']} crediti accreditati a {$req['tenant_name']}.");
        Response::redirect(url('admin/credit-requests'));
    }

    public function reject(Request $request): void
    {
        $id = (int)$request->param('id');
        $adminId = Auth::id();
        $model = new CreditRechargeRequest();
        $req = $model->findById($id);

        if (!$req || $req['status'] !== 'pending') {
            flash('danger', 'Richiesta non trovata o già processata.');
            Response::redirect(url('admin/credit-requests'));
            return;
        }

        $reason = trim($request->input('reason', ''));
        if ($reason === '') {
            flash('danger', 'Indica un motivo per il rifiuto.');
            Response::redirect(url('admin/credit-requests'));
            return;
        }

        if (!$model->reject($id, $adminId, $reason)) {
            flash('danger', 'Impossibile rifiutare la richiesta.');
            Response::redirect(url('admin/credit-requests'));
            return;
        }

        try {
            $reseller = [
                'email'      => $req['reseller_email'] ?? null,
                'first_name' => $req['reseller_first_name'] ?? '',
            ];
            if ($reseller['email']) {
                MailService::sendCreditRequestRejected($reseller, $req, $reason);
            }
        } catch (\Throwable $e) {
            app_log('Credit rejected email error: ' . $e->getMessage());
        }

        flash('success', "Richiesta rifiutata. Email inviata al reseller.");
        Response::redirect(url('admin/credit-requests'));
    }
}
