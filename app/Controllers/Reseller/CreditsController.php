<?php

namespace App\Controllers\Reseller;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\CreditRechargeRequest;

/**
 * Ricariche crediti email — lato reseller.
 *
 * Workflow:
 *  - Reseller crea una richiesta per un suo cliente (Tenant.acquired_by_reseller_id = me)
 *  - Status iniziale: pending
 *  - Admin approva/rifiuta da /admin/credit-requests
 */
class CreditsController
{
    private const MIN_CREDITS = 100;
    private const MAX_CREDITS = 50000;
    private const STEP = 100;

    public function index(Request $request): void
    {
        $userId = Auth::id();
        $model = new CreditRechargeRequest();

        $requests = $model->listByReseller($userId);
        $counts   = $model->countByStatus($userId);
        $totalApproved = $model->sumApprovedCredits($userId);

        view('reseller/credits/index', [
            'title'         => 'Ricariche crediti',
            'activeMenu'    => 'reseller-credits',
            'requests'      => $requests,
            'counts'        => $counts,
            'totalApproved' => $totalApproved,
            'statuses'      => CreditRechargeRequest::STATUSES,
        ], 'reseller');
    }

    public function create(Request $request): void
    {
        $clients = $this->myClients();

        view('reseller/credits/create', [
            'title'      => 'Nuova richiesta ricarica',
            'activeMenu' => 'reseller-credits',
            'clients'    => $clients,
            'minCredits' => self::MIN_CREDITS,
            'maxCredits' => self::MAX_CREDITS,
            'step'       => self::STEP,
        ], 'reseller');
    }

    public function store(Request $request): void
    {
        $userId = Auth::id();
        $data = $request->all();

        $tenantId = (int)($data['tenant_id'] ?? 0);
        $credits  = (int)($data['credits'] ?? 0);
        $notes    = trim($data['notes'] ?? '') ?: null;

        // Verifica che il tenant sia effettivamente del reseller
        $isMine = $this->isMyTenant($userId, $tenantId);
        if (!$isMine) {
            flash('danger', 'Cliente non valido o non assegnato a te.');
            Response::redirect(url('reseller/credits/create'));
            return;
        }

        if ($credits < self::MIN_CREDITS || $credits > self::MAX_CREDITS) {
            flash('danger', 'Quantità non valida. Min ' . self::MIN_CREDITS . ', max ' . self::MAX_CREDITS . '.');
            Response::redirect(url('reseller/credits/create'));
            return;
        }
        if ($credits % self::STEP !== 0) {
            flash('danger', 'La quantità deve essere multiplo di ' . self::STEP . '.');
            Response::redirect(url('reseller/credits/create'));
            return;
        }

        (new CreditRechargeRequest())->create($userId, $tenantId, $credits, $notes);

        flash('success', 'Richiesta inviata. L\'amministratore ti risponderà a breve.');
        Response::redirect(url('reseller/credits'));
    }

    /**
     * Tenants assegnati al reseller corrente, con saldo crediti corrente.
     */
    private function myClients(): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT id, name, slug, email_credits_balance
             FROM tenants
             WHERE acquired_by_reseller_id = :uid AND is_active = 1
             ORDER BY name"
        );
        $stmt->execute(['uid' => Auth::id()]);
        return $stmt->fetchAll();
    }

    private function isMyTenant(int $userId, int $tenantId): bool
    {
        if ($tenantId <= 0) return false;
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM tenants
             WHERE id = :tid AND acquired_by_reseller_id = :uid AND is_active = 1"
        );
        $stmt->execute(['tid' => $tenantId, 'uid' => $userId]);
        return (int)$stmt->fetchColumn() === 1;
    }
}
