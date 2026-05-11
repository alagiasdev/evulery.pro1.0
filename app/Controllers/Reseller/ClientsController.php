<?php

namespace App\Controllers\Reseller;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Models\ResellerProfile;
use App\Services\CommissionCalculator;

/**
 * Lista clienti acquisiti dal reseller.
 * Vede solo tenants con acquired_by_reseller_id = current_user_id.
 * Dati visibili: nome, piano, billing_cycle, stato abbonamento, data attivazione,
 * commissione annuale, link al sito pubblico.
 */
class ClientsController
{
    public function index(Request $request): void
    {
        $userId = Auth::id();
        $db = Database::getInstance();

        $profile = (new ResellerProfile())->findByUserId($userId) ?? CommissionCalculator::defaultProfile();

        $stmt = $db->prepare(
            "SELECT t.id, t.name, t.slug, t.is_active, t.created_at,
                    p.name AS plan_name,
                    s.billing_cycle,
                    s.current_period_end,
                    s.status AS sub_status
             FROM tenants t
             LEFT JOIN plans p ON p.id = t.plan_id
             LEFT JOIN subscriptions s ON s.id = (
                 SELECT MAX(id) FROM subscriptions WHERE tenant_id = t.id
             )
             WHERE t.acquired_by_reseller_id = :uid
             ORDER BY t.created_at DESC"
        );
        $stmt->execute(['uid' => $userId]);
        $clients = $stmt->fetchAll();

        // Arricchisco con commissione annuale e stato calcolato
        foreach ($clients as &$c) {
            $c['annual_commission'] = CommissionCalculator::commissionForPlan($c['plan_name'] ?? '', $profile);
            $c['status_label']      = $this->resolveStatus($c);
        }
        unset($c);

        $stats = [
            'total'  => count($clients),
            'active' => count(array_filter($clients, fn($c) => (int)$c['is_active'] === 1)),
            'annual_recurring' => array_sum(array_map(
                fn($c) => (int)$c['is_active'] === 1 ? (float)$c['annual_commission'] : 0,
                $clients
            )),
        ];

        view('reseller/clients/index', [
            'title'      => 'I miei clienti',
            'activeMenu' => 'reseller-clients',
            'clients'    => $clients,
            'stats'      => $stats,
        ], 'reseller');
    }

    // commissionForPlan() spostata in App\Services\CommissionCalculator (2026-05-11)

    private function resolveStatus(array $c): array
    {
        if ((int)$c['is_active'] === 0) {
            return ['key' => 'inactive', 'label' => 'Disattivato', 'class' => 'rs-st-inactive'];
        }
        if (!empty($c['current_period_end']) && strtotime($c['current_period_end']) < time()) {
            return ['key' => 'expired', 'label' => 'Scaduto', 'class' => 'rs-st-expired'];
        }
        if (($c['sub_status'] ?? '') === 'trialing') {
            return ['key' => 'trial', 'label' => 'Prova', 'class' => 'rs-st-trial'];
        }
        return ['key' => 'active', 'label' => 'Attivo', 'class' => 'rs-st-active'];
    }
}
