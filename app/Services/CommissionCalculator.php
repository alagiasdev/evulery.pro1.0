<?php

namespace App\Services;

use App\Models\ResellerProfile;

/**
 * CommissionCalculator — logica condivisa per il calcolo commissioni reseller.
 *
 * Centralizza le funzioni primitive usate da DashboardController, CommissionsController
 * e ClientsController dell'area /reseller. Tutti i calcoli sono PURI (input → output, no DB,
 * no side effect): l'I/O resta nei controller. Le formule qui DEVONO essere unica fonte
 * di verità per evitare divergenze tra dashboard e pagina commissioni.
 *
 * Regole di business (decise il 2026-05-11):
 *  - Commissioni in reseller_profiles sono ANNUALI per piano
 *  - Setup è una-tantum al go-live del tenant
 *  - Pagamento al reseller in unica soluzione al pagamento del cliente
 *    (annual = commissione_annuale, semiannual = commissione_annuale / 2)
 *  - Numero pagamenti completati = intdiv(mesi_dall'attivazione, billing_months) + 1
 *    dove billing_months = 6 (semiannual) o 12 (annual)
 */
class CommissionCalculator
{
    /**
     * Ritorna la commissione ANNUALE configurata per il piano dato.
     * Plan name non riconosciuto → 0.0 (es. tenant senza piano).
     */
    public static function commissionForPlan(string $planName, array $profile): float
    {
        return match (strtolower($planName)) {
            'starter'      => (float)($profile['commission_starter']      ?? 0),
            'professional' => (float)($profile['commission_professional'] ?? 0),
            'enterprise'   => (float)($profile['commission_enterprise']   ?? 0),
            default        => 0.0,
        };
    }

    /**
     * Mesi tra due pagamenti del cliente in base al billing_cycle.
     * Default 12 (annual) per resilienza se il valore è null/sconosciuto.
     */
    public static function billingMonthsFor(?string $billingCycle): int
    {
        return $billingCycle === 'semiannual' ? 6 : 12;
    }

    /**
     * Commissione che il reseller incassa AD OGNI pagamento del cliente,
     * in base al piano e al billing_cycle.
     */
    public static function commissionPerPayment(string $planName, ?string $billingCycle, array $profile): float
    {
        $annual = self::commissionForPlan($planName, $profile);
        $months = self::billingMonthsFor($billingCycle);
        return $annual * ($months / 12);
    }

    /**
     * Numero di pagamenti già effettuati dal cliente (incluso quello iniziale),
     * dato il numero di mesi trascorsi dal go-live e il billing_cycle.
     */
    public static function paymentsCompleted(int $monthsSinceStart, ?string $billingCycle): int
    {
        $months = self::billingMonthsFor($billingCycle);
        return intdiv(max(0, $monthsSinceStart), $months) + 1;
    }

    /**
     * Fallback profilo commissioni con valori di default (se reseller non ha
     * ancora un record in reseller_profiles).
     */
    public static function defaultProfile(): array
    {
        return [
            'commission_setup'        => ResellerProfile::DEFAULT_COMMISSION_SETUP,
            'commission_starter'      => ResellerProfile::DEFAULT_COMMISSION_STARTER,
            'commission_professional' => ResellerProfile::DEFAULT_COMMISSION_PROFESSIONAL,
            'commission_enterprise'   => ResellerProfile::DEFAULT_COMMISSION_ENTERPRISE,
        ];
    }
}
