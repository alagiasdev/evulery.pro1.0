<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\MealCategory;
use App\Models\SlotOverride;
use App\Services\AuditLog;
use App\Services\AvailabilityService;

/**
 * Pagina "Disponibilità online": ferma le prenotazioni ONLINE per un giorno o un
 * intervallo di giorni (uno o più servizi), senza chiudere il locale. Riusa il
 * motore già collaudato di "Chiudi le prenotazioni" (SlotOverride::markFull/
 * unmarkFull con override per-slot max_covers=0, disgiunto dalle chiusure).
 */
class AvailabilityBlocksController
{
    public function index(Request $request): void
    {
        $tenantId = (int) Auth::tenantId();
        $svc = new AvailabilityService();

        // Elenco date con online chiuso (future), arricchite con l'ambito.
        // Deploy-safe: la pagina non deve rompersi se qualcosa va storto.
        $closedDates = [];
        try {
            foreach ((new SlotOverride())->fullDates($tenantId) as $row) {
                $date  = $row['override_date'];
                $state = $svc->getDayFullState($tenantId, $date);
                $label = !empty($state['whole_day_full'])
                    ? 'Tutto il giorno'
                    : implode(', ', $state['full_labels'] ?: ['Alcuni orari']);
                $closedDates[] = [
                    'date'  => $date,
                    'label' => $label,
                    'slots' => (int) $row['slots'],
                    'whole' => !empty($state['whole_day_full']),
                ];
            }
        } catch (\Throwable $e) {
            app_log('Disponibilità online — lista non disponibile: ' . $e->getMessage(), 'warning');
        }

        // Fasce del ristorante per la tendina "Ambito".
        $categories = [];
        try {
            $categories = (new MealCategory())->findActiveByTenant($tenantId);
        } catch (\Throwable $e) {
            app_log('Disponibilità online — categorie non disponibili: ' . $e->getMessage(), 'warning');
        }

        view('dashboard/reservations/availability', [
            'title'       => 'Disponibilità online',
            'activeMenu'  => 'reservations',
            'closedDates' => $closedDates,
            'categories'  => $categories,
            'today'       => date('Y-m-d'),
        ], 'dashboard');
    }

    /**
     * Chiude le prenotazioni online. date_from obbligatoria; date_to facoltativa
     * (se presente e diversa = intervallo). scope: 'day' o nome fascia.
     */
    public function close(Request $request): void
    {
        $tenantId = (int) Auth::tenantId();
        $scope = (string) $request->input('scope', 'day');
        $from  = (string) $request->input('date_from', '');
        $to    = (string) $request->input('date_to', '');
        if ($to === '') {
            $to = $from;
        }

        if (!$this->validDate($from) || !$this->validDate($to)) {
            flash('danger', 'Data non valida.');
            Response::redirect(url('dashboard/reservations/availability'));
            return;
        }
        if ($to < $from) {
            [$from, $to] = [$to, $from]; // ordina l'intervallo
        }

        $start = new \DateTime($from);
        $end   = new \DateTime($to);
        // Tetto di sicurezza: niente intervalli abnormi.
        if ((int) $start->diff($end)->days > 90) {
            flash('warning', 'Intervallo troppo ampio (massimo 90 giorni).');
            Response::redirect(url('dashboard/reservations/availability'));
            return;
        }

        $svc      = new AvailabilityService();
        $override = new SlotOverride();
        $daysClosed = 0;
        $slotsClosed = 0;

        $cursor = clone $start;
        while ($cursor <= $end) {
            $d = $cursor->format('Y-m-d');
            $times = $this->scopeTimes($svc, $tenantId, $d, $scope);
            if (!empty($times)) {
                $n = $override->markFull($tenantId, $d, $times);
                if ($n > 0) {
                    $daysClosed++;
                    $slotsClosed += $n;
                }
            }
            $cursor->modify('+1 day');
        }

        AuditLog::log('availability_closed', "Online chiuso {$from}..{$to} scope={$scope} ({$slotsClosed} orari)", Auth::id(), $tenantId);
        if ($slotsClosed > 0) {
            flash('success', "Prenotazioni online chiuse ({$daysClosed} " . ($daysClosed === 1 ? 'giorno' : 'giorni') . "). Puoi comunque aggiungere prenotazioni a mano.");
        } else {
            flash('warning', 'Nessun orario da chiudere per questa selezione (verifica data e ambito).');
        }
        Response::redirect(url('dashboard/reservations/availability'));
    }

    /** Riapre le prenotazioni online di una data (rimuove tutti i blocchi al-completo). */
    public function reopen(Request $request): void
    {
        $tenantId = (int) Auth::tenantId();
        $date = (string) $request->input('date', '');
        if (!$this->validDate($date)) {
            flash('danger', 'Data non valida.');
            Response::redirect(url('dashboard/reservations/availability'));
            return;
        }
        $n = (new SlotOverride())->unmarkFull($tenantId, $date, null);
        AuditLog::log('availability_reopened', "Online riaperto {$date} ({$n} orari)", Auth::id(), $tenantId);
        flash('success', 'Prenotazioni online riaperte.');
        Response::redirect(url('dashboard/reservations/availability'));
    }

    /** Orari (HH:MM:SS) di uno scope ('day' o nome fascia) per una data. */
    private function scopeTimes(AvailabilityService $svc, int $tenantId, string $date, string $scope): array
    {
        $state = $svc->getDayFullState($tenantId, $date);
        if ($scope === 'day') {
            return $state['whole_day_times'];
        }
        foreach ($state['services'] as $sv) {
            if ($sv['name'] === $scope) {
                return $sv['times'];
            }
        }
        return [];
    }

    private function validDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d !== false && $d->format('Y-m-d') === $date;
    }
}
