<?php

namespace App\Controllers\Reseller;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Models\ResellerProfile;

/**
 * Pagina commissioni dettagliata.
 * Mostra: KPI (maturato mese / annua attesa / totale), storico mensile
 * ultimi 12 mesi, breakdown per cliente, prossimi pagamenti attesi.
 *
 * Tutti i calcoli sono on-the-fly dalla schedule teorica derivata da
 * tenants.created_at + subscriptions.billing_cycle. Nessuna tabella
 * storico — coerente con la logica MVP del DashboardController.
 */
class CommissionsController
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

        // Stessa source-of-truth del DashboardController: filtra tenant attivi,
        // include months_since_start calcolato da MySQL (TIMESTAMPDIFF) per
        // garantire calcolo numPayments allineato.
        $stmt = $db->prepare(
            "SELECT t.id, t.name, t.slug, t.is_active, t.created_at,
                    p.name AS plan_name,
                    s.billing_cycle,
                    s.status AS sub_status,
                    TIMESTAMPDIFF(MONTH, t.created_at, CURDATE()) AS months_since_start
             FROM tenants t
             LEFT JOIN plans p ON p.id = t.plan_id
             LEFT JOIN subscriptions s ON s.id = (
                 SELECT MAX(id) FROM subscriptions WHERE tenant_id = t.id
             )
             WHERE t.acquired_by_reseller_id = :uid AND t.is_active = 1
             ORDER BY t.created_at DESC"
        );
        $stmt->execute(['uid' => $userId]);
        $tenants = $stmt->fetchAll();

        $schedule    = $this->buildMonthlySchedule($tenants, $profile);
        $breakdown   = $this->buildClientBreakdown($tenants, $profile);
        $upcoming    = $this->buildUpcomingPayments($tenants, $profile);
        $kpis        = $this->computeKpis($schedule, $tenants, $profile);

        // Solo ultimi 12 mesi
        $last12 = array_slice($schedule, 0, 12, true);

        view('reseller/commissions/index', [
            'title'      => 'Le mie commissioni',
            'activeMenu' => 'reseller-commissions',
            'profile'    => $profile,
            'kpis'       => $kpis,
            'history'    => $last12,
            'breakdown'  => $breakdown,
            'upcoming'   => $upcoming,
        ], 'reseller');
    }

    /**
     * Schedule mensile: per ogni tenant calcola le date di setup e dei
     * pagamenti licenza, raggruppa per mese (YYYY-MM).
     * Ritorna array ordinato DESC (più recente prima).
     */
    private function buildMonthlySchedule(array $tenants, array $profile): array
    {
        $schedule = [];
        $todayMonth = strtotime(date('Y-m-01'));
        $setupAmount = (float)$profile['commission_setup'];

        foreach ($tenants as $t) {
            // Skip tenant senza piano o senza subscription: niente maturazione possibile
            if (empty($t['plan_name']) || empty($t['billing_cycle'])) {
                continue;
            }
            $annualCommission = $this->commissionForPlan($t['plan_name'], $profile);
            $billingMonths = ($t['billing_cycle'] === 'semiannual') ? 6 : 12;
            $perPayment = $annualCommission * ($billingMonths / 12);

            $startMonth = date('Y-m', strtotime($t['created_at']));
            // Setup nel mese di attivazione
            $this->ensureMonth($schedule, $startMonth);
            $schedule[$startMonth]['setup'] += $setupAmount;
            $schedule[$startMonth]['rows'][] = [
                'tenant' => $t['name'],
                'type'   => 'setup',
                'amount' => $setupAmount,
            ];

            // Numero pagamenti completati (stessa formula del DashboardController)
            $monthsSince = max(0, (int)$t['months_since_start']);
            $numPayments = intdiv($monthsSince, $billingMonths) + 1;

            // Distribuisce i pagamenti nei mesi corretti
            $startTs = strtotime($t['created_at']);
            for ($i = 0; $i < $numPayments; $i++) {
                $paymentTs = strtotime(date('Y-m-d', $startTs) . " +" . ($i * $billingMonths) . " months");
                $ym = date('Y-m', $paymentTs);
                $this->ensureMonth($schedule, $ym);
                $schedule[$ym]['licenses'] += $perPayment;
                $schedule[$ym]['rows'][] = [
                    'tenant' => $t['name'],
                    'type'   => 'license',
                    'amount' => $perPayment,
                ];
            }
        }

        // Totale per mese
        foreach ($schedule as &$m) {
            $m['total'] = $m['setup'] + $m['licenses'];
        }
        unset($m);

        krsort($schedule);
        return $schedule;
    }

    private function ensureMonth(array &$schedule, string $ym): void
    {
        if (!isset($schedule[$ym])) {
            $schedule[$ym] = [
                'setup'    => 0.0,
                'licenses' => 0.0,
                'total'    => 0.0,
                'rows'     => [],
            ];
        }
    }

    /**
     * Per ogni cliente attivo: piano, billing, attivato il, setup riconosciuto,
     * licenze maturate finora, prossima licenza, totale maturato dal cliente.
     */
    private function buildClientBreakdown(array $tenants, array $profile): array
    {
        $setupAmount = (float)$profile['commission_setup'];
        $out = [];

        foreach ($tenants as $t) {
            $hasPlan = !empty($t['plan_name']) && !empty($t['billing_cycle']);
            $annualCommission = $hasPlan ? $this->commissionForPlan($t['plan_name'], $profile) : 0.0;
            $billingMonths = ($t['billing_cycle'] === 'semiannual') ? 6 : 12;
            $perPayment = $annualCommission * ($billingMonths / 12);

            $createdTs = strtotime($t['created_at']);
            // Stessa formula del DashboardController (MySQL TIMESTAMPDIFF)
            $monthsSince = max(0, (int)$t['months_since_start']);
            $numPayments = $hasPlan ? intdiv($monthsSince, $billingMonths) + 1 : 0;

            $licensesEarned = $perPayment * $numPayments;
            // Setup riconosciuto solo se il tenant ha effettivamente un piano
            $effectiveSetup = $hasPlan ? $setupAmount : 0.0;
            $totalEarned = $effectiveSetup + $licensesEarned;

            // Prossimo pagamento atteso: created_at + (numPayments * billing_months)
            $nextTs = $hasPlan
                ? strtotime(date('Y-m-d', $createdTs) . " +" . ($numPayments * $billingMonths) . " months")
                : null;

            $out[] = [
                'id'              => $t['id'],
                'name'            => $t['name'],
                'slug'            => $t['slug'],
                'plan'            => $t['plan_name'] ?? '',
                'billing_cycle'   => $t['billing_cycle'] ?? null,
                'created_at'      => $t['created_at'],
                'annual'          => $annualCommission,
                'per_payment'     => $perPayment,
                'num_payments'    => $numPayments,
                'setup_amount'    => $effectiveSetup,
                'licenses_earned' => $licensesEarned,
                'total_earned'    => $totalEarned,
                'next_payment_at' => $nextTs ? date('Y-m-d', $nextTs) : null,
            ];
        }

        return $out;
    }

    /**
     * Prossimi pagamenti attesi nei prossimi 12 mesi, ordinati per data.
     */
    private function buildUpcomingPayments(array $tenants, array $profile): array
    {
        $today = strtotime(date('Y-m-d'));
        $horizon = strtotime('+12 months');
        $upcoming = [];

        foreach ($tenants as $t) {
            if (empty($t['plan_name']) || empty($t['billing_cycle'])) {
                continue;
            }
            $annualCommission = $this->commissionForPlan($t['plan_name'], $profile);
            $billingMonths = ($t['billing_cycle'] === 'semiannual') ? 6 : 12;
            $perPayment = $annualCommission * ($billingMonths / 12);

            $createdTs = strtotime($t['created_at']);
            $cursor = $createdTs;
            // Avanzo finché non supero oggi
            while ($cursor <= $today) {
                $cursor = strtotime(date('Y-m-d', $cursor) . " +{$billingMonths} months");
            }
            // Ora $cursor è il prossimo pagamento atteso
            while ($cursor <= $horizon) {
                $upcoming[] = [
                    'tenant_name' => $t['name'],
                    'amount'      => $perPayment,
                    'date'        => date('Y-m-d', $cursor),
                ];
                $cursor = strtotime(date('Y-m-d', $cursor) . " +{$billingMonths} months");
            }
        }

        usort($upcoming, fn($a, $b) => strcmp($a['date'], $b['date']));
        return $upcoming;
    }

    /**
     * KPI principali: maturato mese corrente, annua attesa (12 mesi forward),
     * totale maturato finora.
     */
    private function computeKpis(array $schedule, array $tenants, array $profile): array
    {
        $currentYM = date('Y-m');
        $monthEarned = $schedule[$currentYM]['total'] ?? 0.0;

        // Totale maturato = somma di tutti i mesi
        $lifetime = 0.0;
        foreach ($schedule as $m) {
            $lifetime += $m['total'];
        }

        // Annua attesa = somma commissioni annuali per piano (12 mesi forward, no setup)
        // Solo tenant con sub attiva o trial (canceled/past_due esclusi)
        $annualExpected = 0.0;
        foreach ($tenants as $t) {
            if (empty($t['plan_name']) || empty($t['billing_cycle'])) continue;
            if (isset($t['sub_status']) && !in_array($t['sub_status'], ['active', 'trialing'], true)) continue;
            $annualExpected += $this->commissionForPlan($t['plan_name'], $profile);
        }

        return [
            'monthEarned'    => $monthEarned,
            'annualExpected' => $annualExpected,
            'lifetime'       => $lifetime,
            'activeClients'  => count($tenants),
        ];
    }

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
