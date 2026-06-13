<?php

namespace App\Services;

use App\Core\Database;
use App\Models\MealCategory;
use App\Models\Tenant;
use PDO;

/**
 * Risolve la durata di occupazione tavolo per una prenotazione, in base
 * alla fascia oraria (categoria pasto) e al giorno della settimana.
 *
 * Regola (in ordine):
 *  1. Trova la categoria ATTIVA il cui intervallo contiene l'orario di INIZIO.
 *  2. Se la categoria ha duration_minutes valorizzato:
 *     - se il giorno della prenotazione e' fra i duration_alt_days e
 *       duration_minutes_alt e' valorizzato -> usa l'alternativa
 *     - altrimenti -> usa duration_minutes (base)
 *  3. Se nessuna categoria match, o categoria senza durata custom
 *     -> fallback a tenants.table_duration (durata globale, oggi 90).
 *
 * Il risultato viene "congelato" su reservations.duration_minutes alla
 * creazione (snapshot): cambi successivi alle fasce non spostano le
 * prenotazioni gia' prese.
 *
 * Nota gating: NON controlla il piano. Il servizio 'advanced_turns' gata
 * solo la UI di configurazione; se i campi durata sono valorizzati nel DB
 * vengono applicati comunque (grandfathering al downgrade — scelta di
 * prodotto). Quindi questo resolver e' puro: (tenant, data, ora) -> minuti.
 */
class MealDurationResolver
{
    private PDO $db;
    private MealCategory $categoryModel;

    /** Cache per-tenant delle categorie attive (evita query ripetute in loop). */
    private array $categoryCache = [];
    /** Cache per-tenant della durata globale. */
    private array $globalCache = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->categoryModel = new MealCategory();
    }

    /**
     * Durata in minuti per una prenotazione a (date, time) del tenant.
     *
     * @param string $date YYYY-MM-DD
     * @param string $time HH:MM o HH:MM:SS
     */
    public function resolve(int $tenantId, string $date, string $time): int
    {
        $global = $this->globalDuration($tenantId);

        $categories = $this->activeCategories($tenantId);
        if (empty($categories)) {
            return $global;
        }

        $cat = $this->categoryModel->categorizeTime($categories, substr($time, 0, 5));
        if (!$cat || empty($cat['duration_minutes'])) {
            return $global;
        }

        // Giorno ISO: 1=Lun ... 7=Dom
        $dow = (int)date('N', strtotime($date));
        $altDays = $this->parseAltDays($cat['duration_alt_days'] ?? null);

        if (!empty($cat['duration_minutes_alt']) && in_array($dow, $altDays, true)) {
            return (int)$cat['duration_minutes_alt'];
        }

        return (int)$cat['duration_minutes'];
    }

    /** Durata globale del tenant (fallback), con cache. */
    private function globalDuration(int $tenantId): int
    {
        if (!isset($this->globalCache[$tenantId])) {
            $stmt = $this->db->prepare('SELECT table_duration FROM tenants WHERE id = :id');
            $stmt->execute(['id' => $tenantId]);
            $val = (int)$stmt->fetchColumn();
            $this->globalCache[$tenantId] = $val > 0 ? $val : 90;
        }
        return $this->globalCache[$tenantId];
    }

    /** Categorie attive del tenant, con cache. */
    private function activeCategories(int $tenantId): array
    {
        if (!isset($this->categoryCache[$tenantId])) {
            $this->categoryCache[$tenantId] = $this->categoryModel->findActiveByTenant($tenantId);
        }
        return $this->categoryCache[$tenantId];
    }

    /**
     * Parsing robusto di duration_alt_days: accetta JSON ("[6,7]") o CSV
     * ("6,7"). Ritorna array di interi 1-7 validi.
     */
    private function parseAltDays(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            $decoded = explode(',', $raw);
        }
        $days = [];
        foreach ($decoded as $d) {
            $d = (int)$d;
            if ($d >= 1 && $d <= 7) {
                $days[] = $d;
            }
        }
        return array_values(array_unique($days));
    }
}
