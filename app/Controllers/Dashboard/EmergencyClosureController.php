<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Models\EmergencyClosure;
use App\Models\MealCategory;
use App\Services\EmergencyClosureService;

/**
 * Chiusura straordinaria (emergenze). Flusso a 2 step server-rendered:
 *   index   → form (date, ambito, messaggio)
 *   preview → anteprima prenotazioni interessate + scelta modalita'
 *   apply   → esecuzione (blocco + annulla/sospendi + email)
 * + riapertura/chiusura definitiva di un evento attivo.
 */
class EmergencyClosureController
{
    public function index(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $tenant   = TenantResolver::current();
        $meals    = (new MealCategory())->findActiveByTenant($tenantId);
        $active   = (new EmergencyClosure())->findActiveByTenant($tenantId);

        view('dashboard/emergency-closure/index', [
            'title'      => 'Chiusura straordinaria',
            'activeMenu' => 'reservations',
            'tenant'     => $tenant,
            'meals'      => $meals,
            'active'     => $active,
            'today'      => date('Y-m-d'),
        ], 'dashboard');
    }

    public function preview(Request $request): void
    {
        $tenantId = Auth::tenantId();
        [$params, $error] = $this->resolveParams($request);
        if ($error) {
            flash('danger', $error);
            Response::redirect(url('dashboard/emergency-closure'));
            return;
        }

        $affected = (new EmergencyClosureService())->affectedReservations(
            $tenantId,
            $params['date_from'],
            $params['date_to'],
            $params['time_from'],
            $params['time_to']
        );

        $covers = array_sum(array_map(fn($r) => (int)$r['party_size'], $affected));

        view('dashboard/emergency-closure/preview', [
            'title'      => 'Chiusura straordinaria — conferma',
            'activeMenu' => 'reservations',
            'params'     => $params,
            'affected'   => $affected,
            'covers'     => $covers,
        ], 'dashboard');
    }

    public function apply(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $tenant   = TenantResolver::current();
        $mode     = $request->input('mode') === 'suspend' ? 'suspend' : 'cancel';

        [$params, $error] = $this->resolveParams($request);
        if ($error) {
            flash('danger', $error);
            Response::redirect(url('dashboard/emergency-closure'));
            return;
        }

        try {
            $result = (new EmergencyClosureService())->apply($tenantId, $tenant, $params, $mode, Auth::id());
        } catch (\Throwable $e) {
            app_log('EmergencyClosure apply error: ' . $e->getMessage(), 'error');
            flash('danger', 'Si è verificato un errore durante la chiusura. Riprova.');
            Response::redirect(url('dashboard/emergency-closure'));
            return;
        }

        $n = $result['affected'];
        if ($mode === 'suspend') {
            flash('success', $n > 0
                ? "Servizio sospeso. {$n} " . ($n === 1 ? 'prenotazione avvisata' : 'prenotazioni avvisate') . ". Quando sai com'è andata, riapri o chiudi definitivamente dal banner."
                : 'Servizio sospeso e nuove prenotazioni bloccate (nessuna prenotazione esistente nel periodo).');
        } else {
            flash('success', $n > 0
                ? "Chiusura applicata. {$n} " . ($n === 1 ? 'prenotazione annullata e avvisata' : 'prenotazioni annullate e avvisate') . "."
                : 'Chiusura applicata e nuove prenotazioni bloccate (nessuna prenotazione esistente nel periodo).');
        }

        Response::redirect(url('dashboard/reservations') . '?date=' . urlencode($params['date_from']));
    }

    public function reopen(Request $request): void
    {
        $tenantId  = Auth::tenantId();
        $tenant    = TenantResolver::current();
        $closureId = (int)$request->input('closure_id');

        try {
            $res = (new EmergencyClosureService())->reopen($tenantId, $tenant, $closureId);
        } catch (\Throwable $e) {
            app_log('EmergencyClosure reopen error: ' . $e->getMessage(), 'error');
            flash('danger', 'Errore durante la riapertura. Riprova.');
            Response::redirect(url('dashboard/reservations'));
            return;
        }

        $msg = 'Servizio riaperto.';
        if ($res['recovered'] > 0) {
            $msg .= " {$res['recovered']} " . ($res['recovered'] === 1 ? 'prenotazione recuperata e confermata' : 'prenotazioni recuperate e confermate') . '.';
        }
        if ($res['lost'] > 0) {
            $msg .= " {$res['lost']} " . ($res['lost'] === 1 ? 'prenotazione non recuperabile (servizio già passato): annullata con scuse' : 'prenotazioni non recuperabili (servizio già passato): annullate con scuse') . '.';
        }
        flash('success', $msg);
        Response::redirect(url('dashboard/reservations'));
    }

    public function close(Request $request): void
    {
        $tenantId  = Auth::tenantId();
        $tenant    = TenantResolver::current();
        $closureId = (int)$request->input('closure_id');

        try {
            $res = (new EmergencyClosureService())->closeDefinitive($tenantId, $tenant, $closureId);
        } catch (\Throwable $e) {
            app_log('EmergencyClosure close error: ' . $e->getMessage(), 'error');
            flash('danger', 'Errore durante la chiusura. Riprova.');
            Response::redirect(url('dashboard/reservations'));
            return;
        }

        $n = $res['cancelled'];
        flash('success', $n > 0
            ? "Chiusura confermata. {$n} " . ($n === 1 ? 'prenotazione annullata con scuse' : 'prenotazioni annullate con scuse') . '.'
            : 'Chiusura confermata.');
        Response::redirect(url('dashboard/reservations'));
    }

    /**
     * Valida e normalizza i parametri della chiusura. Ritorna [params, error].
     */
    private function resolveParams(Request $request): array
    {
        $tenantId = Auth::tenantId();
        $dateFrom = (string)$request->input('date_from');
        $dateTo   = (string)$request->input('date_to');
        $scope    = (string)$request->input('scope', 'full');
        $message  = trim((string)$request->input('message', ''));
        if ($message !== '') {
            $message = mb_substr($message, 0, 500);
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) || strtotime($dateFrom) === false) {
            return [null, 'Indica una data di inizio valida.'];
        }
        if ($dateTo === '' || strtotime($dateTo) === false) {
            $dateTo = $dateFrom;
        }
        if ($dateTo < $dateFrom) {
            return [null, 'La data di fine non può essere prima della data di inizio.'];
        }
        if ((strtotime($dateTo) - strtotime($dateFrom)) / 86400 > 90) {
            return [null, 'Il periodo massimo è di 90 giorni.'];
        }

        $timeFrom = null;
        $timeTo   = null;
        $scopeLabel = 'Giorno intero';

        if (str_starts_with($scope, 'meal:')) {
            $mealId = (int)substr($scope, 5);
            $meal = null;
            foreach ((new MealCategory())->findActiveByTenant($tenantId) as $m) {
                if ((int)$m['id'] === $mealId) { $meal = $m; break; }
            }
            if (!$meal) {
                return [null, 'Fascia di servizio non valida.'];
            }
            $timeFrom = substr($meal['start_time'], 0, 8);
            $timeTo   = substr($meal['end_time'], 0, 8);
            $scopeLabel = 'Solo ' . ($meal['display_name'] ?? $meal['name']);
        } elseif ($scope === 'custom') {
            $tf = (string)$request->input('time_from');
            $tt = (string)$request->input('time_to');
            if (!preg_match('/^\d{2}:\d{2}/', $tf) || !preg_match('/^\d{2}:\d{2}/', $tt)) {
                return [null, 'Indica un orario di inizio e fine validi.'];
            }
            if ($tt <= $tf) {
                return [null, "L'orario di fine deve essere successivo all'inizio."];
            }
            $timeFrom = substr($tf, 0, 5) . ':00';
            $timeTo   = substr($tt, 0, 5) . ':00';
            $scopeLabel = 'Dalle ' . substr($tf, 0, 5) . ' alle ' . substr($tt, 0, 5);
        }

        return [[
            'date_from'   => $dateFrom,
            'date_to'     => $dateTo,
            'time_from'   => $timeFrom,
            'time_to'     => $timeTo,
            'scope'       => $scope,
            'scope_label' => $scopeLabel,
            'message'     => $message,
        ], null];
    }
}
