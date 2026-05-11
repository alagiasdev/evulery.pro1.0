<?php

namespace App\Controllers\Reseller;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Models\DemoRequest;
use App\Models\ResellerProfile;
use PDO;

/**
 * Dashboard area reseller.
 * Mostra KPI (lead aperti, clienti attivi, maturato mese, lifetime),
 * lead da contattare, lead recenti, box performance.
 */
class DashboardController
{
    public function index(Request $request): void
    {
        $userId = Auth::id();
        $db = Database::getInstance();

        $profile = (new ResellerProfile())->findByUserId($userId)
            ?? [
                'commission_setup'        => ResellerProfile::DEFAULT_COMMISSION_SETUP,
                'commission_starter'      => ResellerProfile::DEFAULT_COMMISSION_STARTER,
                'commission_professional' => ResellerProfile::DEFAULT_COMMISSION_PROFESSIONAL,
                'commission_enterprise'   => ResellerProfile::DEFAULT_COMMISSION_ENTERPRISE,
            ];

        // --- Lead aperti (assigned to me, not customer/lost) ---
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM demo_requests
             WHERE assigned_reseller_id = :uid
               AND status NOT IN ('customer','lost')"
        );
        $stmt->execute(['uid' => $userId]);
        $openLeads = (int)$stmt->fetchColumn();

        // --- Clienti attivi (tenants acquisiti, attivi) ---
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM tenants
             WHERE acquired_by_reseller_id = :uid
               AND is_active = 1"
        );
        $stmt->execute(['uid' => $userId]);
        $activeClients = (int)$stmt->fetchColumn();

        // --- Maturato questo mese + Totale maturato (con breakdown) ---
        $commissions = $this->computeCommissions($userId, $profile);
        $monthEarned             = $commissions['monthEarned'];
        $lifetimeEarned          = $commissions['lifetimeEarned'];
        $lifetimeFromActivations = $commissions['lifetimeFromActivations'];
        $lifetimeFromLicenses    = $commissions['lifetimeFromLicenses'];

        // --- Lead da contattare (follow-up oggi/scaduti, max 5) ---
        $stmt = $db->prepare(
            "SELECT id, name, restaurant, status, next_followup_at,
                    DATEDIFF(next_followup_at, CURDATE()) AS days_diff
             FROM demo_requests
             WHERE assigned_reseller_id = :uid
               AND next_followup_at IS NOT NULL
               AND status NOT IN ('customer','lost')
               AND next_followup_at <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
             ORDER BY next_followup_at ASC
             LIMIT 5"
        );
        $stmt->execute(['uid' => $userId]);
        $toContact = $stmt->fetchAll();

        // --- Lead recenti (assegnati a me, ultimi 5) ---
        $stmt = $db->prepare(
            "SELECT id, name, restaurant, status, created_at
             FROM demo_requests
             WHERE assigned_reseller_id = :uid
             ORDER BY created_at DESC
             LIMIT 5"
        );
        $stmt->execute(['uid' => $userId]);
        $recentLeads = $stmt->fetchAll();

        // --- Performance (conversion rate, tempo medio chiusura) ---
        $stmt = $db->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'customer' THEN 1 ELSE 0 END) AS converted,
                SUM(CASE WHEN status IN ('demo_done','demo_scheduled','negotiating','customer') THEN 1 ELSE 0 END) AS reached_demo
             FROM demo_requests
             WHERE assigned_reseller_id = :uid"
        );
        $stmt->execute(['uid' => $userId]);
        $perf = $stmt->fetch() ?: ['total' => 0, 'converted' => 0, 'reached_demo' => 0];

        $convRate = ($perf['reached_demo'] > 0)
            ? round(((int)$perf['converted'] / (int)$perf['reached_demo']) * 100)
            : 0;

        // Tempo medio chiusura (giorni tra created_at e status_changed_at per customer)
        $stmt = $db->prepare(
            "SELECT AVG(DATEDIFF(status_changed_at, created_at)) AS avg_days
             FROM demo_requests
             WHERE assigned_reseller_id = :uid AND status = 'customer'"
        );
        $stmt->execute(['uid' => $userId]);
        $avgDays = (int)round((float)$stmt->fetchColumn());

        view('reseller/dashboard', [
            'title'           => 'Dashboard',
            'activeMenu'      => 'reseller-home',
            'userName'        => Auth::user()['name'] ?? '',
            'openLeads'       => $openLeads,
            'activeClients'   => $activeClients,
            'monthEarned'            => $monthEarned,
            'lifetimeEarned'         => $lifetimeEarned,
            'lifetimeFromActivations'=> $lifetimeFromActivations,
            'lifetimeFromLicenses'   => $lifetimeFromLicenses,
            'toContact'       => $toContact,
            'recentLeads'     => $recentLeads,
            'convRate'        => $convRate,
            'totalConverted'  => (int)$perf['converted'],
            'totalReachedDemo'=> (int)$perf['reached_demo'],
            'avgDays'         => $avgDays,
            'statuses'        => DemoRequest::STATUSES,
        ], 'reseller');
    }

    /**
     * Calcolo unico commissioni reseller. Ritorna:
     *   monthEarned             = setup di tenant attivati questo mese
     *                             + commissioni dei pagamenti incassati questo mese
     *   lifetimeEarned          = setup totali + commissioni licenze (storico)
     *   lifetimeFromActivations = sola somma setup
     *   lifetimeFromLicenses    = sola somma commissioni licenze
     *
     * Regola di business (decisa il 2026-05-11):
     *  - Setup una-tantum al primo go-live del tenant
     *  - Commissione licenza al PAGAMENTO del cliente (sia primo che rinnovi),
     *    quota in base a billing_cycle: 'annual' = piena, 'semiannual' = metà
     *  - Numero pagamenti completati = intdiv(mesi_dall'attivazione, billing_months) + 1
     */
    private function computeCommissions(int $userId, array $profile): array
    {
        $db = Database::getInstance();

        // Tenants attivi del reseller + subscription corrente (l'ultima per tenant)
        $stmt = $db->prepare(
            "SELECT t.id,
                    t.created_at,
                    p.name AS plan_name,
                    s.billing_cycle,
                    s.current_period_start,
                    TIMESTAMPDIFF(MONTH, t.created_at, CURDATE()) AS months_since_start
             FROM tenants t
             LEFT JOIN plans p ON p.id = t.plan_id
             LEFT JOIN subscriptions s ON s.id = (
                 SELECT MAX(id) FROM subscriptions WHERE tenant_id = t.id
             )
             WHERE t.acquired_by_reseller_id = :uid AND t.is_active = 1"
        );
        $stmt->execute(['uid' => $userId]);
        $rows = $stmt->fetchAll();

        $setupTotal       = 0.0;
        $setupThisMonth   = 0.0;
        $licenseTotal     = 0.0;
        $licenseThisMonth = 0.0;
        $currentYM = date('Y-m');
        $setupAmount = (float)$profile['commission_setup'];

        foreach ($rows as $r) {
            $annualCommission = $this->commissionForPlan($r['plan_name'] ?? '', $profile);
            $billingMonths = ($r['billing_cycle'] === 'semiannual') ? 6 : 12;
            $commissionPerPayment = $annualCommission * ($billingMonths / 12);

            // Setup
            $setupTotal += $setupAmount;
            if (substr((string)$r['created_at'], 0, 7) === $currentYM) {
                $setupThisMonth += $setupAmount;
            }

            // Pagamenti completati: ogni billing_months si genera un pagamento
            $months = max(0, (int)$r['months_since_start']);
            $numPayments = intdiv($months, $billingMonths) + 1;
            $licenseTotal += $commissionPerPayment * $numPayments;

            // Pagamento questo mese?
            // - se è il primo pagamento (created_at in questo mese)
            // - o se è un rinnovo: current_period_start in questo mese
            $createdYM = substr((string)$r['created_at'], 0, 7);
            $periodStartYM = $r['current_period_start']
                ? substr((string)$r['current_period_start'], 0, 7)
                : null;

            if ($createdYM === $currentYM || $periodStartYM === $currentYM) {
                $licenseThisMonth += $commissionPerPayment;
            }
        }

        return [
            'monthEarned'             => $setupThisMonth + $licenseThisMonth,
            'lifetimeEarned'          => $setupTotal + $licenseTotal,
            'lifetimeFromActivations' => $setupTotal,
            'lifetimeFromLicenses'    => $licenseTotal,
        ];
    }

    /**
     * Ritorna la commissione ANNUALE configurata per il piano dato.
     */
    private function commissionForPlan(string $planName, array $profile): float
    {
        return match (strtolower($planName)) {
            'starter'      => (float)$profile['commission_starter'],
            'professional' => (float)$profile['commission_professional'],
            'enterprise'   => (float)$profile['commission_enterprise'],
            default        => 0.0,
        };
    }
}
