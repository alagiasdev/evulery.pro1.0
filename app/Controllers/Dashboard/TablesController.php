<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Models\Reservation;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\TimeSlot;
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
        $capacityCheck = null;
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

            // Fase 3c — coerenza coperti/tavoli: confronta i posti totali dei
            // tavoli attivi col limite coperti più alto fra gli slot attivi.
            // Con la capacità elastica usiamo SEMPRE il massimo: rappresenta
            // il "potenziale" della sala, che è ciò che lo slot deve coprire.
            $seats = 0;
            $seatsMin = 0;
            foreach ($tables as $t) {
                if (!(int)$t['is_active']) continue;
                $seats    += (int)$t['capacity'];
                $seatsMin += (int)($t['min_capacity'] ?? 1);
            }
            $peak = 0;
            foreach ((new TimeSlot())->findAllByTenant((int)$tenant['id']) as $s) {
                if ((int)$s['is_active'] && (int)$s['max_covers'] > $peak) {
                    $peak = (int)$s['max_covers'];
                }
            }
            if ($seats > 0 && $peak > 0) {
                $capacityCheck = ['seats' => $seats, 'peak' => $peak];
            }
        }

        view('dashboard/settings/tables', [
            'title'         => 'Tavoli',
            'activeMenu'    => 'settings-tables',
            'tenant'        => $tenant,
            'canUse'        => $canUse,
            'tables'        => $tables,
            'areas'         => $areas,
            'comboMap'      => $comboMap,
            'capacityCheck' => $capacityCheck,
            'seatsMin'      => $seatsMin ?? 0,
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

    /**
     * Toggle rapido dalla lista tavoli: blocca/sblocca il tavolo (Fase E).
     * Azione frequente (sedia rotta, evento privato, lavori). Per la
     * disattivazione permanente (rara), usare il select "Stato" nel modale.
     */
    public function toggle(Request $request): void
    {
        if (gate_service('table_management', url('dashboard/settings/tables'))) return;

        $tenantId = Auth::tenantId();
        $id = (int)$request->param('id');
        $nowBlocked = (new Table())->toggleBlocked($id, $tenantId);

        flash('success', $nowBlocked ? 'Tavolo bloccato.' : 'Tavolo sbloccato.');
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
        // `min_capacity` opzionale: assente o vuoto → ricade su capacity
        // (comportamento rigido di default, identico al pre-min-max).
        $hasMin = isset($d['min_capacity']) && $d['min_capacity'] !== '';
        $minCapacity = $hasMin ? (int)$d['min_capacity'] : $capacity;

        if ($name === '') {
            flash('danger', 'Il nome del tavolo è obbligatorio.');
            Response::redirect(url('dashboard/settings/tables'));
            return null;
        }
        if ($capacity < 1 || $capacity > 30) {
            flash('danger', 'I posti massimi devono essere tra 1 e 30.');
            Response::redirect(url('dashboard/settings/tables'));
            return null;
        }
        if ($minCapacity < 1 || $minCapacity > $capacity) {
            flash('danger', 'I posti minimi devono essere compresi fra 1 e i posti massimi.');
            Response::redirect(url('dashboard/settings/tables'));
            return null;
        }

        $combinable = [];
        foreach ((array)($d['combinable'] ?? []) as $cid) {
            $cid = (int)$cid;
            if ($cid > 0) $combinable[] = $cid;
        }

        // Fase B + E (migration 058): flag disponibilità tavolo.
        // Il modale invia sempre entrambi i campi tramite pattern hidden+checkbox.
        // block_reason: ignorato (vuoto) se blocked=0 — il Model lo NULLifica in DB.
        $isBookableOnline = !empty($d['is_bookable_online']) ? 1 : 0;
        $isBlocked = !empty($d['is_blocked']) ? 1 : 0;
        $blockReason = $isBlocked
            ? mb_substr(trim((string)($d['block_reason'] ?? '')), 0, 255)
            : '';

        return [
            'name'               => mb_substr($name, 0, 60),
            'capacity'           => $capacity,
            'min_capacity'       => $minCapacity,
            'area'               => mb_substr(trim((string)($d['area'] ?? '')), 0, 60),
            'shape'              => ($d['shape'] ?? 'square') === 'round' ? 'round' : 'square',
            'internal_note'      => mb_substr(trim((string)($d['internal_note'] ?? '')), 0, 255),
            'is_active'          => !empty($d['is_active']) ? 1 : 0,
            'is_bookable_online' => $isBookableOnline,
            'is_blocked'         => $isBlocked,
            'block_reason'       => $blockReason,
            'combinable'         => $combinable,
        ];
    }
}
