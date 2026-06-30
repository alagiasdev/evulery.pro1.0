<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

/**
 * Restringe l'accesso degli utenti con ruolo 'staff' (collaboratori del
 * ristoratore) ad un sottoinsieme operativo del gestionale.
 *
 * Modello ALLOW-LIST (fail-safe): ciò che non è esplicitamente permesso è
 * bloccato lato server. La middleware è INERTE per ogni altro ruolo
 * (owner/super_admin/reseller) → impatto zero sugli utenti esistenti finché
 * non viene creato il primo staff.
 *
 * La sicurezza è qui, NON nei nascondimenti UI della sidebar.
 */
class StaffMiddleware
{
    /** Aree in cui lo staff può entrare (prefisso path, qualsiasi metodo). */
    private const ALLOWED_PREFIXES = [
        '/dashboard/reservations',
        '/dashboard/sala',
        '/dashboard/customers',   // sola lettura (i non-GET sono bloccati sotto)
        '/dashboard/orders',
        '/dashboard/notifications',
        '/dashboard/help',
        '/dashboard/profile',
        '/dashboard/heartbeat',          // polling auto-refresh di Prenotazioni/Sala/Home
        '/dashboard/suspended',          // pagina abbonamento scaduto (evita loop di redirect)
        '/dashboard/stop-impersonation', // recovery: admin che impersona uno staff
    ];

    /**
     * Azioni sensibili (soldi / distruttive / export-dati / import) bloccate
     * SEMPRE, anche dentro le aree permesse. Match per suffisso del path.
     */
    private const BLOCKED_SUFFIXES = [
        '/delete', '/delete-all', '/bulk-delete-imported',
        '/guarantee-charge', '/guarantee-waive',
        '/deposit-paid', '/deposit-refunded', '/request-deposit',
        '/import', '/export', '/csv',
    ];

    public function handle(Request $request): void
    {
        // Inerte per tutti i ruoli tranne 'staff'.
        if (Auth::role() !== 'staff') {
            return;
        }

        $path = rtrim($request->uri(), '/') ?: '/'; // es. /dashboard/customers/5/notes
        if (!self::isAllowedForStaff($path, strtoupper($request->method()))) {
            $this->deny();
        }
    }

    /**
     * Decisione pura (testabile): lo staff può accedere a $path con $method?
     */
    public static function isAllowedForStaff(string $path, string $method): bool
    {
        // 0) Notifiche: gestione COMPLETA (lettura + eliminazione). Sono
        // housekeeping operativo non sensibile → lo staff gestisce la propria
        // campanella. Precede i blocchi sotto (es. il suffisso /delete).
        if (str_starts_with($path, '/dashboard/notifications')) {
            return true;
        }

        // 1) Azioni sensibili (soldi/distruttive/export/import) bloccate ovunque.
        foreach (self::BLOCKED_SUFFIXES as $suffix) {
            if (str_ends_with($path, $suffix)) {
                return false;
            }
        }

        // 2) Clienti in SOLA LETTURA: nessuna scrittura (POST/PUT/DELETE).
        if (str_starts_with($path, '/dashboard/customers') && $method !== 'GET') {
            return false;
        }

        // 3) Allow-list: home esatta oppure un prefisso permesso.
        if ($path === '/dashboard') {
            return true;
        }
        foreach (self::ALLOWED_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        // 4) Tutto il resto è vietato (fail-safe).
        return false;
    }

    private function deny(): void
    {
        flash('warning', 'Non hai i permessi per questa sezione.');
        Response::redirect(url('dashboard'));
    }
}
