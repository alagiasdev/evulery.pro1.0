<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Models\MealCategory;
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
        // Dati "servizio" per la vista operativa (barra slot configurati + fasce).
        // Inizializzati qui così la view non incappa in variabili indefinite
        // nemmeno in modalità setup o quando il servizio è gatato.
        $meals = $serviceSlots = $tableTurns = $daySlots = [];
        $currentMeal = 'all';
        $roomCapacity = $roomTables = $allCovers = 0;
        $opDate = (string)$request->query('date', date('Y-m-d'));
        // L'utente ha cliccato uno slot preciso (?time=)? Se no, il default-ora
        // verrà posizionato sul "best slot" della fascia (vedi modalità operativa).
        $rawTime = $request->query('time');
        $hasExplicitTime = is_string($rawTime) && preg_match('/^\d{2}:\d{2}$/', $rawTime);
        $opTime = $hasExplicitTime ? $rawTime : $this->defaultOpTime();

        // Fase C — heartbeat solo per modalita' operativa (setup e' editing
        // di posizione/disponibilita', non ha senso pollare in real-time).
        $heartbeat = null;

        if ($canUse) {
            $model = new Table();
            $tables = $model->findByTenant((int)$tenant['id']);
            $areas = $model->areas((int)$tenant['id']);

            if ($mode === 'operativa') {
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $opDate)) $opDate = date('Y-m-d');
                $assigner = new TableAssigner();

                // Prenotazioni del giorno (escluse annullate) + assegnazioni tavoli.
                // Calcolate PRIMA dei dati servizio: buildServiceData le usa per i
                // coperti per slot e per i turni per tavolo (badge mappa).
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

                // Fascia "ricordata": se l'utente sceglie un servizio (?meal=…) lo
                // salvo in sessione, così tornando in Sala dopo un giro su altre
                // pagine ritrova l'ultima fascia consultata invece del default a
                // ora corrente. Se la fascia salvata non è valida per il giorno
                // mostrato, buildServiceData ricade comunque sul default.
                $reqMeal = (string)$request->query('meal', '');
                if ($reqMeal !== '') {
                    \App\Core\Session::set('sala_meal', $reqMeal);
                } else {
                    $reqMeal = (string)\App\Core\Session::get('sala_meal', '');
                }

                // Dati servizio: slot configurati per fascia + coperti/tavoli per
                // slot + turni per tavolo + best slot per il default-ora.
                $svc = $this->buildServiceData(
                    $tenant, $opDate, $opTime,
                    $reqMeal !== '' ? $reqMeal : null,
                    $dayReservations, $assignments, $tables
                );
                $meals        = $svc['meals'];
                $currentMeal  = $svc['currentMeal'];
                $serviceSlots = $svc['serviceSlots'];
                $tableTurns   = $svc['tableTurns'];
                $roomCapacity = $svc['roomCapacity'];
                $roomTables   = $svc['roomTables'];
                $daySlots     = $svc['daySlots'];
                $allCovers    = $svc['allCovers'];

                // Default-ora intelligente: se l'utente non ha cliccato uno slot
                // preciso, posiziono l'ora sul "best slot" della fascia corrente
                // (ora corrente se in servizio, altrimenti primo slot con
                // prenotazioni) — così non si apre su una sala vuota se c'è gente.
                if (!$hasExplicitTime && !empty($svc['currentBest'])) {
                    $opTime = $svc['currentBest'];
                }
                if (!preg_match('/^\d{2}:\d{2}$/', $opTime)) $opTime = '20:00';

                // Stato sala all'ora definitiva (ora che $opTime è stabilito)
                $floorState = $assigner->floorState((int)$tenant['id'], $opDate, $opTime);
                $reassignOptions = $assigner->allTableOptions((int)$tenant['id']);

                $hb = \App\Services\HeartbeatService::forFloor((int)$tenant['id'], $opDate);
                $heartbeat = [
                    'hash'  => $hb['hash'],
                    'count' => $hb['count'],
                    'url'   => url('dashboard/heartbeat/floor') . '?date=' . urlencode($opDate),
                ];
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
            'heartbeat'       => $heartbeat,
            'meals'           => $meals,
            'currentMeal'     => $currentMeal,
            'serviceSlots'    => $serviceSlots,
            'tableTurns'      => $tableTurns,
            'roomCapacity'    => $roomCapacity,
            'roomTables'      => $roomTables,
            'daySlots'        => $daySlots,
            'allCovers'       => $allCovers,
        ], 'dashboard');
    }

    /**
     * Costruisce i dati "servizio" per la mappa operativa:
     *  - slot REALMENTE configurati per il giorno (TimeSlot, non più fissi 12–23:30)
     *  - fasce servizio = categorie pasto attive che hanno almeno uno slot quel
     *    giorno, con i coperti prenotati per fascia
     *  - fascia di default in base all'ora corrente
     *  - slot della fascia corrente con coperti + tavoli occupati (barra stile TheFork)
     *  - turni per tavolo (per i badge "prossima prenotazione" sulla mappa)
     *  - capienza totale sala
     *
     * Riusa MealCategory + TimeSlot già esistenti: nessuna query nuova oltre a
     * queste due letture. Durata occupazione = snapshot reservations.duration_minutes
     * (per-fascia, già calcolato in fase di prenotazione) con fallback tenant.table_duration.
     *
     * @return array{meals:array,currentMeal:string,serviceSlots:array,tableTurns:array,roomCapacity:int,roomTables:int,daySlots:array}
     */
    private function buildServiceData(
        array $tenant,
        string $opDate,
        string $opTime,
        ?string $reqMeal,
        array $dayReservations,
        array $assignments,
        array $tables
    ): array {
        $tenantId   = (int)$tenant['id'];
        $defaultDur = max(15, (int)($tenant['table_duration'] ?? 90));
        $toMin = fn(string $hhmm): int => (int)substr($hhmm, 0, 2) * 60 + (int)substr($hhmm, 3, 2);

        // 1) Slot configurati per il giorno (0=Lun … 6=Dom)
        $dow      = (int)date('N', strtotime($opDate)) - 1;
        $rawSlots = (new TimeSlot())->findByTenantAndDay($tenantId, $dow);
        $daySlots = array_map(fn($s) => substr((string)$s['slot_time'], 0, 5), $rawSlots);

        // 2) Categorie pasto attive
        $cats = (new MealCategory())->findActiveByTenant($tenantId);

        // 3) Prenotazioni attive (per i coperti)
        $active = array_values(array_filter(
            $dayReservations,
            fn($r) => in_array((string)$r['status'], ['confirmed', 'pending', 'arrived'], true)
        ));

        // Coperti + tavoli occupati a un dato minuto
        $coversAt = function (int $slotMin) use ($active, $assignments, $defaultDur, $toMin): array {
            $covers = 0; $tbls = [];
            foreach ($active as $r) {
                $st  = $toMin(substr((string)$r['reservation_time'], 0, 5));
                $dur = (int)($r['duration_minutes'] ?? 0) ?: $defaultDur;
                if ($slotMin >= $st && $slotMin < $st + $dur) {
                    $covers += (int)$r['party_size'];
                    foreach ($assignments[(int)$r['id']] ?? [] as $t) {
                        $tbls[(int)$t['id']] = true;
                    }
                }
            }
            return ['covers' => $covers, 'tables' => count($tbls)];
        };

        // 4) Fasce: solo categorie con almeno uno slot configurato nel loro range
        $meals = [];
        foreach ($cats as $c) {
            $cs = $toMin(substr((string)$c['start_time'], 0, 5));
            $ce = $toMin(substr((string)$c['end_time'], 0, 5));
            $catSlots = array_values(array_filter($daySlots, fn($hhmm) => $toMin($hhmm) >= $cs && $toMin($hhmm) < $ce));
            if (empty($catSlots)) continue; // non offerta in questo giorno

            $mealCovers = 0;
            foreach ($active as $r) {
                $st = $toMin(substr((string)$r['reservation_time'], 0, 5));
                if ($st >= $cs && $st < $ce) $mealCovers += (int)$r['party_size'];
            }
            $meals[] = [
                'name'   => (string)$c['name'],
                'label'  => (string)$c['display_name'],
                'start'  => substr((string)$c['start_time'], 0, 5),
                'end'    => substr((string)$c['end_time'], 0, 5),
                'slots'  => $catSlots,
                'covers' => $mealCovers,
                'best'   => $this->bestSlot($catSlots, $coversAt, $toMin),
            ];
        }

        // 5) Fascia di default
        $opMin = $toMin($opTime);
        $currentMeal = null;
        if ($reqMeal === 'all') {
            $currentMeal = 'all';
        } elseif ($reqMeal !== null && $reqMeal !== '') {
            foreach ($meals as $m) if ($m['name'] === $reqMeal) { $currentMeal = $reqMeal; break; }
        }
        if ($currentMeal === null) {
            foreach ($meals as $m) if ($opMin >= $toMin($m['start']) && $opMin < $toMin($m['end'])) { $currentMeal = $m['name']; break; }
        }
        if ($currentMeal === null) {
            foreach ($meals as $m) if ($m['covers'] > 0) { $currentMeal = $m['name']; break; }
        }
        if ($currentMeal === null) {
            $currentMeal = $meals[0]['name'] ?? 'all';
        }

        // 6) Slot della fascia corrente con coperti + tavoli
        $curSlots = $daySlots;
        if ($currentMeal !== 'all') {
            foreach ($meals as $m) if ($m['name'] === $currentMeal) { $curSlots = $m['slots']; break; }
        }
        $serviceSlots = [];
        foreach ($curSlots as $hhmm) {
            $c = $coversAt($toMin($hhmm));
            $serviceSlots[] = ['time' => $hhmm, 'covers' => $c['covers'], 'tables' => $c['tables']];
        }

        // 7) Turni per tavolo (badge "prossima prenotazione")
        $tableTurns = [];
        foreach ($active as $r) {
            foreach ($assignments[(int)$r['id']] ?? [] as $t) {
                $tableTurns[(int)$t['id']][] = [
                    'time'    => substr((string)$r['reservation_time'], 0, 5),
                    'surname' => (string)$r['last_name'],
                    'party'   => (int)$r['party_size'],
                    'rid'     => (int)$r['id'],
                ];
            }
        }
        foreach ($tableTurns as &$turns) {
            usort($turns, fn($a, $b) => strcmp($a['time'], $b['time']));
        }
        unset($turns);

        // 8) Capienza sala (tavoli attivi)
        $roomCapacity = $roomTables = 0;
        foreach ($tables as $t) {
            if ((int)($t['is_active'] ?? 1) === 0) continue;
            $roomTables++;
            $roomCapacity += (int)$t['capacity'];
        }

        // 9) Best slot della fascia corrente (default-ora alla apertura pagina)
        $currentBest = null;
        if ($currentMeal === 'all') {
            $currentBest = $this->bestSlot($daySlots, $coversAt, $toMin);
        } else {
            foreach ($meals as $m) if ($m['name'] === $currentMeal) { $currentBest = $m['best']; break; }
        }

        return [
            'meals'        => $meals,
            'currentMeal'  => $currentMeal,
            'currentBest'  => $currentBest,
            'serviceSlots' => $serviceSlots,
            'tableTurns'   => $tableTurns,
            'roomCapacity' => $roomCapacity,
            'roomTables'   => $roomTables,
            'daySlots'     => $daySlots,
            // Totale coperti del giorno = somma di TUTTE le prenotazioni attive
            // (non la somma per-fascia): include anche eventuali prenotazioni con
            // orario fuori da ogni fascia, così "Tutti i servizi" è sempre coerente.
            'allCovers'    => array_sum(array_map(fn($r) => (int)$r['party_size'], $active)),
        ];
    }

    /**
     * "Best slot" di una fascia per il default-ora:
     *  - se l'ora corrente cade dentro la fascia → lo slot configurato più vicino
     *    (≤ ora corrente), così durante il servizio si apre sull'adesso;
     *  - altrimenti il primo slot con prenotazioni (non apri su sala vuota se c'è
     *    gente prenotata);
     *  - altrimenti il primo slot configurato.
     */
    private function bestSlot(array $slots, callable $coversAt, callable $toMin): ?string
    {
        if (empty($slots)) return null;
        $nowMin = (int)date('G') * 60 + (int)date('i');
        $first  = $toMin($slots[0]);
        $last   = $toMin($slots[count($slots) - 1]);
        if ($nowMin >= $first && $nowMin <= $last) {
            $best = $slots[0];
            foreach ($slots as $s) {
                if ($toMin($s) <= $nowMin) $best = $s; else break;
            }
            return $best;
        }
        foreach ($slots as $s) {
            if ($coversAt($toMin($s))['covers'] > 0) return $s;
        }
        return $slots[0];
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
