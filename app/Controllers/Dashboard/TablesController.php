<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Models\Reservation;
use App\Models\Table;
use App\Models\Tenant;
use App\Services\AuditLog;
use App\Services\TableAssigner;

/**
 * Gestione Tavoli — pagina Impostazioni > Tavoli.
 * CRUD tavoli, riordino priorità, combinazioni. Servizio gatato `table_management`.
 */
class TablesController
{
    public function index(Request $request): void
    {
        $tenant = TenantResolver::current();
        $canUse = tenant_can('table_management');

        $tables = $combinations = $areas = [];
        $comboMap = [];
        if ($canUse) {
            $model = new Table();
            $tables = $model->findByTenant((int)$tenant['id']);
            $combinations = $model->allCombinations((int)$tenant['id']);
            $areas = $model->areas((int)$tenant['id']);
            // Mappa tableId => [altri id combinabili] per la view/modale
            foreach ($combinations as $c) {
                $a = (int)$c['table_a_id'];
                $b = (int)$c['table_b_id'];
                $comboMap[$a][] = $b;
                $comboMap[$b][] = $a;
            }
        }

        view('dashboard/settings/tables', [
            'title'        => 'Tavoli',
            'activeMenu'   => 'settings-tables',
            'tenant'       => $tenant,
            'canUse'       => $canUse,
            'tables'       => $tables,
            'areas'        => $areas,
            'comboMap'     => $comboMap,
        ], 'dashboard');
    }

    public function store(Request $request): void
    {
        if (gate_service('table_management', url('dashboard/settings/tables'))) return;

        $tenantId = Auth::tenantId();
        $data = $this->validated($request);
        if ($data === null) return;

        $model = new Table();
        $id = $model->create($tenantId, $data);
        $model->setCombinations($tenantId, $id, $data['combinable']);

        AuditLog::log(AuditLog::SETTINGS_UPDATED, "Tavolo creato: {$data['name']}", Auth::id(), $tenantId);
        flash('success', 'Tavolo aggiunto.');
        Response::redirect(url('dashboard/settings/tables'));
    }

    public function update(Request $request): void
    {
        if (gate_service('table_management', url('dashboard/settings/tables'))) return;

        $tenantId = Auth::tenantId();
        $id = (int)$request->param('id');
        $table = (new Table())->findById($id);
        if (!$table || (int)$table['tenant_id'] !== (int)$tenantId) {
            flash('danger', 'Tavolo non trovato.');
            Response::redirect(url('dashboard/settings/tables'));
            return;
        }

        $data = $this->validated($request);
        if ($data === null) return;

        $model = new Table();
        $model->update($id, $tenantId, $data);
        $model->setCombinations($tenantId, $id, $data['combinable']);

        AuditLog::log(AuditLog::SETTINGS_UPDATED, "Tavolo aggiornato: {$data['name']}", Auth::id(), $tenantId);
        flash('success', 'Tavolo aggiornato.');
        Response::redirect(url('dashboard/settings/tables'));
    }

    public function destroy(Request $request): void
    {
        if (gate_service('table_management', url('dashboard/settings/tables'))) return;

        $tenantId = Auth::tenantId();
        $id = (int)$request->param('id');
        (new Table())->delete($id, $tenantId);

        AuditLog::log(AuditLog::SETTINGS_UPDATED, "Tavolo #{$id} eliminato", Auth::id(), $tenantId);
        flash('success', 'Tavolo eliminato.');
        Response::redirect(url('dashboard/settings/tables'));
    }

    public function toggle(Request $request): void
    {
        if (gate_service('table_management', url('dashboard/settings/tables'))) return;

        $tenantId = Auth::tenantId();
        $id = (int)$request->param('id');
        (new Table())->toggleActive($id, $tenantId);

        flash('success', 'Stato tavolo aggiornato.');
        Response::redirect(url('dashboard/settings/tables'));
    }

    public function reorder(Request $request): void
    {
        if (gate_service('table_management', url('dashboard/settings/tables'))) return;

        $tenantId = Auth::tenantId();
        $raw = (string)$request->input('order', '');
        $ids = array_filter(array_map('intval', explode(',', $raw)));
        if (!empty($ids)) {
            (new Table())->reorder($tenantId, $ids);
        }

        flash('success', 'Ordine di priorità aggiornato.');
        Response::redirect(url('dashboard/settings/tables'));
    }

    /** Mappa sala da Impostazioni > Tavoli — default modalità setup. */
    public function map(Request $request): void
    {
        $this->renderMap($request, $request->query('mode') === 'operativa' ? 'operativa' : 'setup');
    }

    /** Pagina "Sala" dalla sidebar — default modalità operativa. */
    public function sala(Request $request): void
    {
        $this->renderMap($request, $request->query('mode') === 'setup' ? 'setup' : 'operativa');
    }

    /** Mappa sala — modalità setup (posizionamento) o operativa (stato sala). */
    private function renderMap(Request $request, string $mode): void
    {
        $tenant = TenantResolver::current();
        $canUse = tenant_can('table_management');

        $tables = $areas = $floorState = $reassignOptions = $currentMap = [];
        $dayReservations = $assignments = [];
        $opDate = (string)$request->query('date', date('Y-m-d'));
        $opTime = (string)$request->query('time', $this->defaultOpTime());

        if ($canUse) {
            $model = new Table();
            $tables = $model->findByTenant((int)$tenant['id']);
            $areas = $model->areas((int)$tenant['id']);

            if ($mode === 'operativa') {
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $opDate)) $opDate = date('Y-m-d');
                if (!preg_match('/^\d{2}:\d{2}$/', $opTime)) $opTime = '20:00';
                $assigner = new TableAssigner();
                $floorState = $assigner->floorState((int)$tenant['id'], $opDate, $opTime);
                $reassignOptions = $assigner->allTableOptions((int)$tenant['id']);

                // Fase 3a — elenco prenotazioni del giorno (escluse le annullate)
                // + mappa dei tavoli assegnati a ciascuna.
                $dayReservations = array_values(array_filter(
                    (new Reservation())->findByTenantAndDate((int)$tenant['id'], $opDate),
                    fn($r) => ($r['status'] ?? '') !== 'cancelled'
                ));
                $assignments = $assigner->assignmentsFor(array_column($dayReservations, 'id'));
                foreach ($assignments as $rid => $ts) {
                    $ids = array_map(fn($x) => (int)$x['id'], $ts);
                    sort($ids);
                    $currentMap[(int)$rid] = implode(',', $ids);
                }
            }
        }

        view('dashboard/settings/tables-map', [
            'title'           => $mode === 'operativa' ? 'Sala' : 'Mappa sala',
            'activeMenu'      => $mode === 'operativa' ? 'sala' : 'settings-tables',
            'tenant'          => $tenant,
            'canUse'          => $canUse,
            'tables'          => $tables,
            'areas'           => $areas,
            'mode'            => $mode,
            'opDate'          => $opDate,
            'opTime'          => $opTime,
            'floorState'      => $floorState,
            'reassignOptions' => $reassignOptions,
            'currentMap'      => $currentMap,
            'dayReservations' => $dayReservations,
            'assignments'     => $assignments,
        ], 'dashboard');
    }

    /**
     * Slot orario di default per la modalità operativa: arrotonda l'ora
     * corrente ai 30 minuti, limitato alla fascia 12:00–23:30 dello
     * scorri-orari. Fuori fascia (notte/mattina) si aggancia all'estremo
     * più vicino, così si apre sempre vicino "all'adesso".
     */
    private function defaultOpTime(): string
    {
        $min = (int)date('G') * 60 + (int)date('i');
        $min = (int)(round($min / 30) * 30);
        $min = max(12 * 60, min(23 * 60 + 30, $min));
        return sprintf('%02d:%02d', intdiv($min, 60), $min % 60);
    }

    /** Salva le posizioni dei tavoli sulla mappa (JSON: {id:{x,y}}). */
    public function savePositions(Request $request): void
    {
        if (gate_service('table_management', url('dashboard/settings/tables'))) return;

        $tenantId = Auth::tenantId();
        $positions = json_decode((string)$request->input('positions', ''), true);

        $clean = [];
        if (is_array($positions)) {
            foreach ($positions as $id => $p) {
                if (is_array($p) && isset($p['x'], $p['y']) && (int)$id > 0) {
                    $clean[(int)$id] = ['x' => max(0, (int)$p['x']), 'y' => max(0, (int)$p['y'])];
                }
            }
        }
        if (!empty($clean)) {
            (new Table())->updatePositions($tenantId, $clean);
        }

        AuditLog::log(AuditLog::SETTINGS_UPDATED, 'Mappa sala aggiornata', Auth::id(), $tenantId);
        flash('success', 'Mappa sala salvata.');
        Response::redirect(url('dashboard/settings/tables/map'));
    }

    /** Salva le impostazioni di auto-assegnazione del tenant. */
    public function updateAutoAssign(Request $request): void
    {
        if (gate_service('table_management', url('dashboard/settings/tables'))) return;

        $tenantId = Auth::tenantId();
        $auto = !empty($request->input('table_auto_assign')) ? 1 : 0;
        $buffer = (int)$request->input('table_turnover_buffer', 15);
        if (!in_array($buffer, [0, 5, 10, 15, 20, 30], true)) {
            $buffer = 15;
        }

        (new Tenant())->update($tenantId, [
            'table_auto_assign'     => $auto,
            'table_turnover_buffer' => $buffer,
        ]);
        TenantResolver::setCurrent((new Tenant())->findById($tenantId));

        AuditLog::log(AuditLog::SETTINGS_UPDATED, 'Impostazioni auto-assegnazione tavoli', Auth::id(), $tenantId);
        flash('success', 'Impostazioni auto-assegnazione aggiornate.');
        Response::redirect(url('dashboard/settings/tables'));
    }

    /**
     * Valida e normalizza i dati del form tavolo.
     * Ritorna l'array pulito, oppure null dopo aver fatto redirect su errore.
     */
    private function validated(Request $request): ?array
    {
        $d = $request->all();
        $name = trim((string)($d['name'] ?? ''));
        $capacity = (int)($d['capacity'] ?? 0);

        if ($name === '') {
            flash('danger', 'Il nome del tavolo è obbligatorio.');
            Response::redirect(url('dashboard/settings/tables'));
            return null;
        }
        if ($capacity < 1 || $capacity > 30) {
            flash('danger', 'La capacità deve essere tra 1 e 30 posti.');
            Response::redirect(url('dashboard/settings/tables'));
            return null;
        }

        $combinable = [];
        foreach ((array)($d['combinable'] ?? []) as $cid) {
            $cid = (int)$cid;
            if ($cid > 0) $combinable[] = $cid;
        }

        return [
            'name'          => mb_substr($name, 0, 60),
            'capacity'      => $capacity,
            'area'          => mb_substr(trim((string)($d['area'] ?? '')), 0, 60),
            'shape'         => ($d['shape'] ?? 'square') === 'round' ? 'round' : 'square',
            'internal_note' => mb_substr(trim((string)($d['internal_note'] ?? '')), 0, 255),
            'is_active'     => !empty($d['is_active']) ? 1 : 0,
            'combinable'    => $combinable,
        ];
    }
}
