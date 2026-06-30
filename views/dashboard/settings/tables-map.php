<?php
/**
 * Mappa sala — modalità setup (posizionamento) e operativa (stato sala).
 * Fase 2 (mappa) + Fase 3a (operativa a due pannelli: lista + mappa).
 * Variabili: $tenant, $canUse, $tables, $areas, $mode, $opDate, $opTime,
 *            $floorState, $reassignOptions, $currentMap,
 *            $dayReservations, $assignments, $heartbeat
 */
// Fase C — auto-refresh polling solo in modalita' operativa con servizio attivo
if (!empty($heartbeat)) {
    $pageScripts = ['js/heartbeat-polling.js'];
}
// Posizione iniziale per i tavoli mai posizionati: griglia di fallback con
// contatore GLOBALE — così due tavoli non posizionati non si sovrappongono mai.
$fallbackIdx = 0;
foreach ($tables as &$_t) {
    if ($_t['position_x'] !== null && $_t['position_y'] !== null) {
        $_t['_x'] = (int)$_t['position_x'];
        $_t['_y'] = (int)$_t['position_y'];
    } else {
        $_t['_x'] = 30 + ($fallbackIdx % 6) * 140;
        $_t['_y'] = 30 + intdiv($fallbackIdx, 6) * 120;
        $fallbackIdx++;
    }
}
unset($_t);

// Bounding box delle posizioni: serve a "spaccare" la canvas più larga del
// viewport quando i tavoli sono posizionati oltre il bordo (frequente su
// mobile dopo un setup fatto da desktop). Senza questo, lo scroll interno
// non si attiva e i tavoli a destra/in basso restano invisibili.
$canvasMaxX = 0;
$canvasMaxY = 0;
foreach ($tables as $_t) {
    if ($_t['_x'] > $canvasMaxX) $canvasMaxX = $_t['_x'];
    if ($_t['_y'] > $canvasMaxY) $canvasMaxY = $_t['_y'];
}
// 76px = lato tavolo, 32px = padding per non incollare lo scroll al bordo
$canvasContentW = $canvasMaxX + 76 + 32;
$canvasContentH = max(540, $canvasMaxY + 76 + 32);

$firstArea = $areas[0] ?? '';
$multiArea = count($areas) > 1;
// Area "principale" = la prima creata: niente pallino colore. Le aree
// aggiuntive ricevono il pallino solo se ce n'è più di una.
$primaryArea = $firstArea;
$areaHasDot = fn(string $a): bool => $multiArea && $a !== '' && $a !== $primaryArea;

// Gli slot del servizio (barra occupazione) arrivano dal controller in
// $serviceSlots / $meals / $currentMeal — solo orari REALMENTE configurati per
// il giorno, raggruppati per fascia (categoria pasto). Niente più ciclo fisso.
$setupUrl = url('dashboard/settings/tables/map');   // mappa setup (Impostazioni)
$opUrl    = url('dashboard/sala');                   // mappa operativa (sidebar "Sala")
$opBack   = 'dashboard/sala?date=' . urlencode($opDate) . '&time=' . urlencode($opTime);
?>

<?php if ($mode === 'setup'): ?>
<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">Impostazioni</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1rem;">Configura il tuo ristorante</p>
<?php $activeKey = 'settings-tables'; include __DIR__ . '/../../partials/settings-tabs.php'; ?>
<?php else: ?>
<?php
    $DAYS_IT   = ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'];
    $MONTHS_IT = ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'];
    $dtH = new DateTime($opDate);
    $opDateLabel = $DAYS_IT[(int)$dtH->format('w')] . ' ' . $dtH->format('j') . ' ' . $MONTHS_IT[(int)$dtH->format('n') - 1] . ' ' . $dtH->format('Y');
?>
<div style="display:flex; align-items:center; gap:.6rem; flex-wrap:wrap; margin-bottom:1rem;">
    <h2 style="font-size:1.35rem; font-weight:700; margin:0;"><i class="bi bi-grid-3x3 me-1" style="color:var(--brand);"></i> Sala</h2>
    <span class="dh-date-badge"><?= e($opDateLabel) ?></span>
</div>
<?php endif; ?>

<?php if (!empty($heartbeat)): ?>
<!--
    Fase C — auto-refresh banner per la sala in modalita' operativa.
    Pollia /heartbeat/floor che combina reservations + restaurant_tables.
    Visibile solo quando il servizio table_management e' attivo: in caso di
    revoca runtime l'endpoint ritorna 403 e il modulo entra in backoff
    silenzioso senza disturbare l'utente.
-->
<div id="dh-refresh-banner" class="dh-refresh-banner" role="status" aria-live="polite">
    <i class="bi bi-arrow-clockwise dh-refresh-banner-ic"></i>
    <div class="dh-refresh-banner-text"></div>
    <div class="dh-refresh-banner-actions">
        <button type="button" class="dh-refresh-banner-btn" data-heartbeat-reload>Aggiorna</button>
        <button type="button" class="dh-refresh-banner-dismiss" data-heartbeat-dismiss aria-label="Chiudi">&times;</button>
    </div>
</div>
<div
    data-heartbeat-url="<?= e($heartbeat['url']) ?>"
    data-heartbeat-hash="<?= e($heartbeat['hash']) ?>"
    data-heartbeat-count="<?= (int)$heartbeat['count'] ?>"
    data-heartbeat-banner="#dh-refresh-banner"
    data-heartbeat-label="sullo stato della sala"
    style="display:none"></div>
<?php endif; ?>

<?php if (!$canUse): ?>
<?php $lockedTitle = 'La Gestione Tavoli'; include __DIR__ . '/../../partials/service-locked.php'; ?>
<?php else: ?>

<div class="card tm-card">
    <div class="tm-head">
        <?php if ($mode === 'setup'): ?>
        <a href="<?= url('dashboard/settings/tables') ?>" class="tm-map-back"><i class="bi bi-arrow-left"></i> Elenco tavoli</a>
        <?php endif; ?>
        <span class="tm-head-title"><i class="bi bi-grid-3x3 me-1"></i> <?= $mode === 'operativa' ? 'Stato sala' : 'Mappa sala' ?></span>
        <div class="tm-mode-toggle">
            <a href="<?= $opUrl ?>" class="<?= $mode === 'operativa' ? 'active' : '' ?>"><i class="bi bi-eye"></i> Operativa</a>
            <?php if (!is_staff()): // Setup tavoli = owner; lo staff resta in Operativa ?>
            <a href="<?= $setupUrl ?>" class="<?= $mode === 'setup' ? 'active' : '' ?>"><i class="bi bi-pencil"></i> Setup</a>
            <?php endif; ?>
        </div>
        <?php if ($mode === 'setup' && !empty($tables)): ?>
        <button type="submit" form="tm-map-form" class="btn-tm-new" style="margin-left:auto;"><i class="bi bi-check-circle me-1"></i> Salva posizioni</button>
        <?php endif; ?>
    </div>

    <?php if (empty($tables)): ?>
    <div class="tm-empty">
        <i class="bi bi-grid-3x3"></i>
        <p>Nessun tavolo. Aggiungi prima i tavoli dall'<a href="<?= url('dashboard/settings/tables') ?>">elenco tavoli</a>.</p>
    </div>

    <?php elseif ($mode === 'operativa'): ?>
    <!-- ===== MODALITÀ OPERATIVA — Fase 3a: due pannelli ===== -->
    <?php
    // Etichetta tavolo per prenotazione (tavoli assegnati, anche combinazioni)
    $resTableLabel = [];
    foreach ($assignments as $rid => $ts) {
        $resTableLabel[(int)$rid] = implode(' + ', array_map(fn($x) => $x['name'], $ts));
    }
    // Range orario della fascia corrente: la lista a sinistra mostra solo le
    // prenotazioni di QUESTA fascia (come TheFork). 'all' o nessuna fascia = tutte.
    $mealStart = $mealEnd = null;
    $curMealLabel = 'Tutti i servizi';
    $curMealRange = '';
    foreach ($meals as $m) {
        if ($m['name'] === $currentMeal) {
            $mealStart = (int)substr($m['start'], 0, 2) * 60 + (int)substr($m['start'], 3, 2);
            $mealEnd   = (int)substr($m['end'], 0, 2) * 60 + (int)substr($m['end'], 3, 2);
            $curMealLabel = $m['label'];
            $curMealRange = $m['start'] . '–' . $m['end'];
            break;
        }
    }
    $inCurrentMeal = function ($r) use ($mealStart, $mealEnd) {
        if ($mealStart === null) return true; // "Tutti i servizi" o nessuna fascia
        $st = (int)substr((string)$r['reservation_time'], 0, 2) * 60 + (int)substr((string)$r['reservation_time'], 3, 2);
        return $st >= $mealStart && $st < $mealEnd;
    };

    // Raggruppa: "Da assegnare" (attive senza tavolo) + per ora di prenotazione.
    // Le prenotazioni fuori dalla fascia corrente sono escluse dalla lista; i
    // "da assegnare" in altre fasce vengono contati a parte per non perderli.
    $unassigned = [];
    $byHour = [];
    $unassignedOther = 0;
    foreach ($dayReservations as $r) {
        $rid = (int)$r['id'];
        $isUnassigned = !isset($assignments[$rid]) && in_array((string)$r['status'], ['confirmed', 'pending', 'arrived'], true);
        if (!$inCurrentMeal($r)) {
            if ($isUnassigned) $unassignedOther++;
            continue;
        }
        if ($isUnassigned) {
            $unassigned[] = $r;
        } else {
            $byHour[substr((string)$r['reservation_time'], 0, 2)][] = $r;
        }
    }
    ksort($byHour);
    $nUnassigned = count($unassigned);

    // Tavoli che a quest'ora fanno parte di una combinazione (= prenotazione
    // su 2+ tavoli). Ricevono alone blu + icona catenella in alto a destra.
    // Il "con quale altro tavolo" lo si vede aprendo il popup della prenotazione.
    $resTablesNow = [];
    foreach ($floorState as $tid => $occ) {
        $resTablesNow[(int)$occ['reservation_id']][] = (int)$tid;
    }
    $comboTableIds = [];
    foreach ($resTablesNow as $tids) {
        if (count($tids) < 2) continue;
        foreach ($tids as $tid) $comboTableIds[$tid] = true;
    }

    // Per ogni prenotazione del giorno, calcolo i tavoli "busy" — cioè quelli
    // occupati da ALTRE prenotazioni che si sovrappongono temporalmente.
    // Finestra di occupazione = table_duration + table_turnover_buffer (così
    // i 15 min di pulizia/cambio post-pasto sono considerati come "ancora busy").
    $tableWindow = max(15, (int)($tenant['table_duration'] ?? 90))
                 + max(0, (int)($tenant['table_turnover_buffer'] ?? 15));
    $busyByReservation = [];
    foreach ($dayReservations as $r) {
        $rid = (int)$r['id'];
        $rStart = strtotime($r['reservation_time']);
        $rEnd   = $rStart + $tableWindow * 60;
        $busy = [];
        foreach ($dayReservations as $other) {
            if ((int)$other['id'] === $rid) continue;
            if (!in_array((string)$other['status'], ['confirmed', 'pending', 'arrived'], true)) continue;
            $oStart = strtotime($other['reservation_time']);
            $oEnd   = $oStart + $tableWindow * 60;
            // Sovrapposizione standard a intervalli aperti: la prenotazione che
            // inizia esattamente quando l'altra finisce (back-to-back) NON e' in
            // conflitto. Il ristoratore controlla questa tolleranza via
            // tenant.table_turnover_buffer (settabile da Impostazioni > Tavoli).
            if (max($rStart, $oStart) < min($rEnd, $oEnd)) {
                foreach ($assignments[(int)$other['id']] ?? [] as $t) {
                    $busy[(int)$t['id']] = true;
                }
            }
        }
        $busyByReservation[$rid] = array_keys($busy);
    }

    // Render di una riga prenotazione (riusata in entrambi i gruppi)
    $renderRow = function (array $r) use ($assignments, $resTableLabel) {
        $rid     = (int)$r['id'];
        $surname = mb_strtoupper(trim((string)$r['last_name']));
        $given   = mb_strtolower(trim((string)$r['first_name']));
        $hasTable = isset($assignments[$rid]);
        $tableIds = $hasTable ? implode(',', array_map(fn($x) => (int)$x['id'], $assignments[$rid])) : '';
        $cnotes  = trim((string)($r['customer_notes'] ?? ''));
        $first   = (int)($r['total_bookings'] ?? 0) <= 1;
        ?>
        <div class="tm-res-row" data-pop="tm-pop-res-<?= $rid ?>" data-tables="<?= e($tableIds) ?>">
            <span class="tm-res-time"><?= e(substr((string)$r['reservation_time'], 0, 5)) ?></span>
            <div class="tm-res-info">
                <div class="tm-res-name"><strong><?= e($surname) ?></strong> <?= e($given) ?></div>
                <div class="tm-res-sub">
                    <span><?= (int)$r['party_size'] ?> persone</span>
                    <span class="tm-res-status s-<?= e((string)$r['status']) ?>"><?= e(status_label($r['status'])) ?></span>
                    <?php if ($first): ?><span>1ª visita</span><?php endif; ?>
                    <?php if ($cnotes !== ''): ?><span class="tm-res-chip"><i class="bi bi-chat-left-text"></i> Nota</span><?php endif; ?>
                </div>
            </div>
            <?php if ($hasTable): ?>
            <span class="tm-res-table set"><?= e($resTableLabel[$rid]) ?></span>
            <?php else: ?>
            <span class="tm-res-table none">Assegna</span>
            <?php endif; ?>
        </div>
        <?php
    };

    $prev = date('Y-m-d', strtotime($opDate . ' -1 day'));
    $next = date('Y-m-d', strtotime($opDate . ' +1 day'));
    // Chip data rapidi per la barra: oggi, domani, dopodomani
    $DAYS_IT = ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'];
    $salaChips = [];
    foreach (['Oggi', 'Domani', 'Dopodomani'] as $i => $lbl) {
        $d = date('Y-m-d', strtotime("+{$i} days"));
        $dt = new DateTime($d);
        $salaChips[] = ['date' => $d, 'label' => $lbl, 'sub' => $DAYS_IT[(int)$dt->format('w')] . ' ' . $dt->format('j/n')];
    }
    ?>
    <div class="tm-op-bar">
        <div class="date-chips tm-datebar">
            <a class="date-nav-arrow sm tm-arrow-prev" href="<?= $opUrl ?>?date=<?= $prev ?>&time=<?= e($opTime) ?>" title="Giorno precedente"><i class="bi bi-chevron-left"></i></a>
            <?php foreach ($salaChips as $chip): ?>
            <a href="<?= $opUrl ?>?date=<?= $chip['date'] ?>&time=<?= e($opTime) ?>" class="date-chip-sm tm-day <?= $opDate === $chip['date'] ? 'active' : '' ?>"><?= $chip['label'] ?> <span class="chip-day"><?= $chip['sub'] ?></span></a>
            <?php endforeach; ?>
            <a class="date-nav-arrow sm tm-arrow-next" href="<?= $opUrl ?>?date=<?= $next ?>&time=<?= e($opTime) ?>" title="Giorno successivo"><i class="bi bi-chevron-right"></i></a>
            <div class="date-chip-cal tm-cal">
                <a href="#" class="date-chip-sm" id="tm-cal-toggle" title="Scegli una data"><i class="bi bi-calendar3 me-1"></i>Altra data</a>
                <div class="home-cal-dropdown" id="tm-cal-dropdown" style="display:none;">
                    <div class="dr-cal-header">
                        <button type="button" class="dr-cal-nav" id="tm-cal-prev"><i class="bi bi-chevron-left"></i></button>
                        <span class="dr-cal-month" id="tm-cal-month"></span>
                        <button type="button" class="dr-cal-nav" id="tm-cal-next"><i class="bi bi-chevron-right"></i></button>
                    </div>
                    <div class="dr-cal-days-header">
                        <div class="dr-cal-day-name">lun</div>
                        <div class="dr-cal-day-name">mar</div>
                        <div class="dr-cal-day-name">mer</div>
                        <div class="dr-cal-day-name">gio</div>
                        <div class="dr-cal-day-name">ven</div>
                        <div class="dr-cal-day-name">sab</div>
                        <div class="dr-cal-day-name">dom</div>
                    </div>
                    <div class="dr-cal-grid" id="tm-cal-grid"></div>
                </div>
            </div>
        </div>
        <?php if ($multiArea): ?>
        <div class="tm-area-tabs" style="margin-left:8px;">
            <button type="button" class="tm-area-tab active" data-all="1"><i class="bi bi-grid-3x3-gap me-1"></i>Tutte le aree</button>
            <?php foreach ($areas as $a): ?>
            <button type="button" class="tm-area-tab" data-area="<?= e($a) ?>"><?php if ($areaHasDot($a)): ?><span class="tm-area-tab-dot" style="background:<?= e(area_color($a)) ?>;"></span><?php endif; ?><?= e($a) ?></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <span class="tm-op-legend">
            <span><span class="tm-dot" style="background:#E6F4ED;box-shadow:inset 0 0 0 1.5px #b3dec7;"></span> Libero</span>
            <span><span class="tm-dot" style="background:#00844A;"></span> Confermato</span>
            <span><span class="tm-dot" style="background:#cfe2ff;box-shadow:inset 0 0 0 2px #0EA5E9;"></span> Arrivato</span>
            <span><i class="bi bi-clock" style="color:#B26500;"></i> Prossima prenotazione</span>
        </span>
    </div>

    <?php
        // Selettore fascia servizio + barra occupazione (slot configurati).
        // Sostituisce lo "scorri-orari" fisso: mostra solo gli orari reali della
        // fascia, con coperti e tavoli per slot (come TheFork).
        $mealCoversNow = 0;
        foreach ($meals as $m) if ($m['name'] === $currentMeal) { $mealCoversNow = (int)$m['covers']; break; }
        // $allCovers arriva dal controller = totale coperti attivi del giorno
        // (include anche prenotazioni con orario fuori da ogni fascia).
        $maxCovers = 1;
        foreach ($serviceSlots as $ss) if ((int)$ss['covers'] > $maxCovers) $maxCovers = (int)$ss['covers'];
    ?>
    <div class="tm-svc-bar">
        <div class="tm-svc-row">
            <div class="tm-svc-left">
                <?php if (!empty($meals)): ?>
                <span class="tm-svc-microlabel">Servizio</span>
                <div class="tm-svc">
                    <button type="button" class="tm-svc-btn" id="tm-svc-btn" aria-haspopup="true" aria-expanded="false">
                        <i class="bi bi-clock-history"></i>
                        <span class="tm-svc-name"><?= e($curMealLabel) ?></span>
                        <?php if ($curMealRange !== ''): ?><span class="tm-svc-rng"><?= e($curMealRange) ?></span><?php endif; ?>
                        <span class="tm-svc-cnt"><?= (int)$mealCoversNow ?></span>
                        <i class="bi bi-chevron-down tm-svc-car"></i>
                    </button>
                    <div class="tm-svc-menu" id="tm-svc-menu">
                        <a href="<?= $opUrl ?>?date=<?= e($opDate) ?>&meal=all" class="tm-svc-item <?= $currentMeal === 'all' ? 'sel' : '' ?>">
                            <span class="tm-svc-lbl">Tutti i servizi</span>
                            <span class="tm-svc-r"><span class="tm-svc-cnt2"><?= (int)$allCovers ?></span><span class="tm-svc-radio"></span></span>
                        </a>
                        <div class="tm-svc-div"></div>
                        <?php foreach ($meals as $m): ?>
                        <a href="<?= $opUrl ?>?date=<?= e($opDate) ?>&meal=<?= e($m['name']) ?>" class="tm-svc-item <?= $currentMeal === $m['name'] ? 'sel' : '' ?>">
                            <span class="tm-svc-lbl"><?= e($m['label']) ?><small><?= e($m['start'] . '–' . $m['end']) ?></small></span>
                            <span class="tm-svc-r"><span class="tm-svc-cnt2"><?= (int)$m['covers'] ?></span><span class="tm-svc-radio"></span></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($nUnassigned > 0 || $unassignedOther > 0): ?>
                <span class="tm-svc-warn">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <?php if ($nUnassigned > 0): ?><?= $nUnassigned ?> da assegnare<?php endif; ?>
                    <?php if ($unassignedOther > 0): ?><span class="tm-svc-warn-other">+<?= $unassignedOther ?> in altri servizi</span><?php endif; ?>
                </span>
                <?php endif; ?>
            </div>
            <?php if (!empty($serviceSlots)): ?>
            <div class="tm-occ">
                <span class="tm-occ-now" id="tm-occ-now"></span>
            <?php
                foreach ($serviceSlots as $ss):
                    $cov = (int)$ss['covers'];
                    // Altezza barra: relativa al picco del giorno (leggibilità).
                    $h = $cov === 0 ? 3 : max(5, (int)round($cov / $maxCovers * 28));
                    // Colore: livello di riempimento assoluto vs capienza sala.
                    // verde <60% · ambra 60-85% · rosso >=85% (o oltre capienza).
                    $lv = '';
                    if ($roomCapacity > 0 && $cov > 0) {
                        $pct = $cov / $roomCapacity;
                        $lv = $pct >= 0.85 ? 'lv-high' : ($pct >= 0.60 ? 'lv-mid' : 'lv-low');
                    }
            ?>
            <a href="<?= $opUrl ?>?date=<?= e($opDate) ?>&time=<?= e($ss['time']) ?>&meal=<?= e($currentMeal) ?>"
               data-time="<?= e($ss['time']) ?>"
               class="tm-occ-col <?= $ss['time'] === $opTime ? 'on' : '' ?> <?= $lv ?>">
                <span class="tm-occ-wrap"><span class="tm-occ-bar<?= $cov === 0 ? ' zero' : '' ?> <?= $lv ?>" style="height:<?= $h ?>px"></span></span>
                <span class="tm-occ-time"><?= e($ss['time']) ?></span>
                <span class="tm-occ-cov"><i class="bi bi-people-fill"></i> <?= (int)$ss['covers'] ?><?php if ($roomCapacity > 0): ?>/<?= (int)$roomCapacity ?><?php endif; ?></span>
                <span class="tm-occ-tbl"><i class="bi bi-grid-fill"></i> <?= (int)$ss['tables'] ?>/<?= (int)$roomTables ?></span>
            </a>
            <?php endforeach; ?>
        </div>
            <?php else: ?>
            <div class="tm-occ-empty"><i class="bi bi-info-circle me-1"></i> Nessun orario configurato per questo giorno. Imposta gli orari in <a href="<?= url('dashboard/settings/slots') ?>">Orari e coperti</a>.</div>
            <?php endif; ?>
        </div><!-- /tm-svc-row -->
    </div>

    <!-- Tab (solo mobile): commuta lista / mappa. Default mobile = lista
         ospiti (piu' utile della mappa su schermo piccolo). Su desktop i
         due pannelli sono affiancati e show-map/show-list e' irrilevante. -->
    <div class="tm-vtabs">
        <button type="button" class="tm-vtab active" data-pane="list">Prenotazioni<?php if ($nUnassigned > 0): ?> <span class="tm-vtab-badge"><?= $nUnassigned ?></span><?php endif; ?></button>
        <button type="button" class="tm-vtab" data-pane="map">Mappa</button>
    </div>

    <div class="tm-twopane show-list" id="tm-twopane">
        <!-- Pannello sinistro: prenotazioni del giorno -->
        <div class="tm-pane-list">
            <?php
                // Righe effettivamente mostrate per la fascia corrente. Se 0,
                // distinguo "giorno vuoto" da "fascia vuota ma giorno con prenotazioni".
                $shownCount = $nUnassigned;
                foreach ($byHour as $rows) $shownCount += count($rows);
            ?>
            <?php if ($shownCount === 0): ?>
                <?php if (empty($dayReservations)): ?>
                <div class="tm-list-empty">
                    <i class="bi bi-calendar-x"></i>
                    <span>Nessuna prenotazione per questa data.</span>
                    <small><a href="<?= url('dashboard/reservations') ?>?upcoming=1">Vedi le prossime prenotazioni <i class="bi bi-arrow-right"></i></a></small>
                </div>
                <?php else: ?>
                <div class="tm-list-empty">
                    <i class="bi bi-clock-history"></i>
                    <span>Nessuna prenotazione per <strong><?= e($curMealLabel) ?></strong>.</span>
                    <small>Ci sono prenotazioni in altri servizi: cambia fascia dal menu in alto<?php if ($currentMeal !== 'all'): ?> o scegli <a href="<?= $opUrl ?>?date=<?= e($opDate) ?>&meal=all">Tutti i servizi</a><?php endif; ?>.</small>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <?php if ($nUnassigned > 0): ?>
                <div class="tm-list-group danger"><i class="bi bi-exclamation-triangle-fill"></i> Da assegnare &middot; <?= $nUnassigned ?></div>
                <?php foreach ($unassigned as $r) $renderRow($r); ?>
                <?php endif; ?>
                <?php foreach ($byHour as $hr => $rows): ?>
                <div class="tm-list-group"><i class="bi bi-clock"></i> Ore <?= e($hr) ?>:00</div>
                <?php foreach ($rows as $r) $renderRow($r); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pannello destro: mappa -->
        <div class="tm-pane-map">
            <div class="tm-map-canvas" id="tm-map">
                <div class="tm-map-spacer" aria-hidden="true" style="width:<?= (int)$canvasContentW ?>px; height:<?= (int)$canvasContentH ?>px;"></div>
                <?php foreach ($tables as $t): ?>
                <?php
                    // Tavoli archiviati (is_active=0) sono nascosti dalla mappa: il
                    // tavolo non c'e' piu' fisicamente nella sala, non ha senso renderizzarlo
                    // (lo storico delle prenotazioni passate vive nel DB ed e' indipendente
                    // dalla visualizzazione della mappa).
                    if ((int)$t['is_active'] === 0) continue;

                    $tArea = (string)($t['area'] ?? '');
                    $hidden = false; // "Tutte le aree" è la vista iniziale
                    $occ = $floorState[(int)$t['id']] ?? null;
                    $inCombo = isset($comboTableIds[(int)$t['id']]);

                    // Fase B + E: flag disponibilita' tavolo (migration 058)
                    $tManualOnly = (int)($t['is_bookable_online'] ?? 1) === 0;
                    $tBlocked    = (int)($t['is_blocked'] ?? 0) === 1;

                    // Classe di stato: tavolo bloccato HA precedenza visiva (e' fuori uso);
                    // poi libero / confermato / arrivato. Convenzione cromatica coerente
                    // con i pulsanti backend ("Conferma tavolo" verde, "Segna Arrivato" blu).
                    if ($tBlocked) {
                        $statusClass = 'tm-status-blocked';
                    } elseif (!$occ) {
                        $statusClass = 'tm-status-free';
                    } elseif (($occ['status'] ?? '') === 'arrived') {
                        $statusClass = 'tm-status-arrived';
                    } else {
                        $statusClass = 'tm-status-confirmed';
                    }
                    $extraClasses = '';
                    if ($tManualOnly) $extraClasses .= ' tm-only-manual';
                    if ($inCombo)    $extraClasses .= ' in-combo';

                    // Prossimo turno su questo tavolo DOPO l'ora selezionata: alimenta
                    // il badge "prossima prenotazione" (cognome troncato + posti, +N).
                    $opMinNow = (int)substr($opTime, 0, 2) * 60 + (int)substr($opTime, 3, 2);
                    $tFuture = [];
                    foreach ($tableTurns[(int)$t['id']] ?? [] as $turn) {
                        $tmm = (int)substr($turn['time'], 0, 2) * 60 + (int)substr($turn['time'], 3, 2);
                        if ($tmm > $opMinNow) $tFuture[] = $turn;
                    }
                    $nextTurn   = $tFuture[0] ?? null;
                    $extraTurns = max(0, count($tFuture) - 1);
                ?>
                <div class="tm-map-table <?= $t['shape'] === 'round' ? 'round' : 'square' ?> <?= $statusClass ?><?= $extraClasses ?>"
                     data-id="<?= (int)$t['id'] ?>" data-area="<?= e($tArea) ?>"
                     <?= $occ && !$tBlocked ? 'data-pop="tm-pop-res-' . (int)$occ['reservation_id'] . '"' : '' ?>
                     title="<?= $tBlocked ? e('Bloccato' . (!empty($t['block_reason']) ? ' — ' . $t['block_reason'] : '')) : ($tManualOnly ? 'Solo manuale (tavolo jolly)' : '') ?>"
                     style="left:<?= (int)$t['_x'] ?>px; top:<?= (int)$t['_y'] ?>px;<?= $hidden ? 'display:none;' : '' ?>">
                    <?php if ($areaHasDot($tArea)): ?><span class="tm-area-dot" style="background:<?= e(area_color($tArea)) ?>;"></span><?php endif; ?>
                    <?php if ($tBlocked): ?><span class="tm-table-lock" title="Tavolo bloccato"><i class="bi bi-lock-fill"></i></span><?php endif; ?>
                    <?php if ($inCombo && !$tBlocked): ?><span class="tm-combo-chain" title="In combinazione con altri tavoli"><i class="bi bi-link-45deg"></i></span><?php endif; ?>
                    <?php if ($occ): ?>
                    <span class="tm-map-tag" title="<?= e($t['name']) ?>"><?= e($t['name']) ?></span>
                    <span class="tm-map-name"><?= e($occ['name']) ?></span>
                    <span class="tm-map-cap"><?= (int)$occ['party'] ?>p &middot; <?= e($occ['time']) ?></span>
                    <?php else: ?>
                    <span class="tm-map-name"><?= e($t['name']) ?></span>
                    <span class="tm-map-cap"><?= format_capacity((int)($t['min_capacity'] ?? 1), (int)$t['capacity']) ?></span>
                    <?php endif; ?>
                    <?php if ($nextTurn && !$tBlocked):
                        $nsur      = mb_strtoupper(trim((string)$nextTurn['surname']));
                        // Badge compatto: cognome troncato a 4 lettere + punto (nome
                        // completo resta nel tooltip e nel popup al click).
                        $nsurShort = mb_strlen($nsur) > 4 ? mb_substr($nsur, 0, 4) . '.' : $nsur;
                    ?>
                    <span class="tm-next-badge" data-pop="tm-pop-res-<?= (int)$nextTurn['rid'] ?>"
                          title="<?= e(implode(' · ', array_map(fn($x) => substr((string)$x['time'], 0, 5) . ' ' . mb_strtoupper((string)$x['surname']) . ' ' . (int)$x['party'] . 'p', $tFuture))) ?>">
                        <i class="bi bi-clock"></i> <?= e(substr((string)$nextTurn['time'], 0, 5)) ?> &middot; <?= e($nsurShort) ?> <?= (int)$nextTurn['party'] ?>p<?php if ($extraTurns > 0): ?> <span class="tm-next-extra">+<?= $extraTurns ?></span><?php endif; ?>
                    </span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="tm-map-hint"><i class="bi bi-info-circle me-1"></i> Stato tavoli alle <?= e($opTime) ?>. Clicca un tavolo o una prenotazione per i dettagli.</div>
        </div>
    </div>

    <!-- Popup dettaglio per ogni prenotazione del giorno -->
    <?php foreach ($dayReservations as $r): ?>
    <?php
        $rid     = (int)$r['id'];
        $surname = mb_strtoupper(trim((string)$r['last_name']));
        $given   = mb_strtolower(trim((string)$r['first_name']));
        $first   = (int)($r['total_bookings'] ?? 0) <= 1;
        $cnotes  = trim((string)($r['customer_notes'] ?? ''));
        $curOpt  = $currentMap[$rid] ?? '';
    ?>
    <div class="tm-pop-overlay" id="tm-pop-res-<?= $rid ?>">
        <div class="tm-pop">
            <div class="tm-pop-head">
                <span><i class="bi bi-person me-1"></i> <?= e(trim($surname . ' ' . $given)) ?><?php if ($first): ?> <span class="tm-pop-badge">1ª visita</span><?php endif; ?></span>
                <button type="button" class="tm-pop-x" data-close>&times;</button>
            </div>
            <div class="tm-pop-body">
                <div class="tm-pop-meta"><?= (int)$r['party_size'] ?> persone &middot; <?= e(substr((string)$r['reservation_time'], 0, 5)) ?> &middot; <?= e(status_label($r['status'])) ?></div>
                <?php
                    // Sezione combinazione: se la prenotazione ha 2+ tavoli, mostro
                    // i pallini coi nomi così l'operatore vede subito "con quale
                    // altro tavolo" senza dover guardare la mappa (ricorda: niente
                    // più linea, l'info vive qui).
                    $resTables = $assignments[$rid] ?? [];
                ?>
                <?php if (count($resTables) >= 2): ?>
                <div class="tm-pop-combo">
                    <span class="tm-pop-combo-lbl"><i class="bi bi-link-45deg"></i> Combinazione di <?= count($resTables) ?> tavoli</span>
                    <div class="tm-pop-combo-list">
                        <?php foreach ($resTables as $rt): ?>
                        <span class="tm-pop-combo-chip"><?= e($rt['name']) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!empty($r['phone']) || !empty($r['email'])): ?>
                <div class="tm-pop-contact">
                    <?php if (!empty($r['phone'])): ?><a href="tel:<?= e(str_replace(' ', '', (string)$r['phone'])) ?>"><i class="bi bi-telephone"></i> <?= e($r['phone']) ?></a><?php endif; ?>
                    <?php if (!empty($r['email'])): ?><a href="mailto:<?= e($r['email']) ?>"><i class="bi bi-envelope"></i> <?= e($r['email']) ?></a><?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if ($cnotes !== ''): ?>
                <div class="tm-pop-note"><i class="bi bi-chat-left-text"></i> <span><strong>Note del cliente:</strong> <?= e($cnotes) ?></span></div>
                <?php endif; ?>
                <?php
                    // Transizioni di stato sensate per il flusso del servizio:
                    // pending -> confermata/arrivato/no-show; confermata -> arrivato/no-show.
                    // Da "arrivato"/"no-show" non si offrono azioni (correzioni dalla scheda).
                    $statusLabels = [
                        'confirmed' => ['Confermata', 'bi-check-circle'],
                        'arrived'   => ['Arrivato',   'bi-box-arrow-in-right'],
                        'noshow'    => ['No-show',    'bi-x-circle'],
                    ];
                    $statusNext = [
                        'pending'   => ['confirmed', 'arrived', 'noshow'],
                        'confirmed' => ['arrived', 'noshow'],
                    ];
                    $nextStates = $statusNext[(string)$r['status']] ?? [];
                ?>
                <?php if (!empty($nextStates)): ?>
                <form method="POST" action="<?= url('dashboard/reservations/' . $rid . '/status') ?>" class="tm-pop-status">
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect_back" value="<?= e($opBack) ?>">
                    <label class="tm-pop-label">Cambia stato</label>
                    <div class="tm-pop-sp-row">
                        <?php foreach ($nextStates as $sv): ?>
                        <button type="submit" name="status" value="<?= $sv ?>" class="tm-pop-sp sp-<?= $sv ?>"><i class="bi <?= $statusLabels[$sv][1] ?> me-1"></i><?= e($statusLabels[$sv][0]) ?></button>
                        <?php endforeach; ?>
                    </div>
                </form>
                <?php endif; ?>
                <?php if (in_array((string)$r['status'], ['pending', 'confirmed'], true)): ?>
                <?php /* Prenotazione non ancora servita: il tavolo si può ancora cambiare */ ?>
                <?php
                    // Se l'attuale assegnazione è una combinazione ad-hoc non
                    // presente in $reassignOptions (es. creata via "Combina tavoli"),
                    // la prependo come opzione "attuale" così il select la riflette.
                    $reassignKnown = array_column($reassignOptions, 'value');
                    $curIsAdHoc = ($curOpt !== '' && !in_array($curOpt, $reassignKnown, true));
                    $curAdHocLabel = $curIsAdHoc
                        ? implode(' + ', array_column($assignments[$rid] ?? [], 'name'))
                        : '';
                    // Set busy per questo specifico turno (per marcare le opzioni occupate)
                    $busySet = array_flip($busyByReservation[$rid] ?? []);
                    // Lookup id → nome tavolo per i suffissi "occupato" / "bloccato"
                    static $tableNamesById = null;
                    static $blockedTablesById = null;
                    if ($tableNamesById === null) {
                        $tableNamesById = [];
                        $blockedTablesById = [];
                        foreach ($tables as $tt) {
                            $tableNamesById[(int)$tt['id']] = $tt['name'];
                            if ((int)($tt['is_blocked'] ?? 0) === 1) {
                                $blockedTablesById[(int)$tt['id']] = $tt['name'];
                            }
                        }
                    }
                    // Warning per opzione "attuale" che contiene tavoli bloccati
                    // (succede se la prenotazione era assegnata PRIMA del blocco).
                    $curBlockedSuffix = '';
                    if ($curIsAdHoc && $curOpt !== '') {
                        $curIds = array_filter(array_map('intval', explode(',', (string)$curOpt)));
                        $curBlockedNames = [];
                        foreach ($curIds as $cid) {
                            if (isset($blockedTablesById[$cid])) $curBlockedNames[] = $blockedTablesById[$cid];
                        }
                        if (!empty($curBlockedNames)) {
                            $curBlockedSuffix = ' · ' . implode(', ', $curBlockedNames) . ' bloccat' . (count($curBlockedNames) > 1 ? 'i' : 'o');
                        }
                    }
                ?>
                <form method="POST" action="<?= url('dashboard/reservations/' . $rid . '/table') ?>" class="tm-pop-table-form" data-party-size="<?= (int)$r['party_size'] ?>" data-prev-value="<?= e($curOpt) ?>" data-res-label="<?= e(mb_strtoupper(trim((string)$r['last_name']))) ?>" data-busy-ids="<?= e(implode(',', $busyByReservation[$rid] ?? [])) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect_back" value="<?= e($opBack) ?>">
                    <label class="tm-pop-label">Tavolo assegnato</label>
                    <select name="table_option" class="tm-fi">
                        <option value="">&mdash; Nessun tavolo &mdash;</option>
                        <?php if ($curIsAdHoc): ?>
                        <option value="<?= e($curOpt) ?>" selected><?= e($curAdHocLabel . $curBlockedSuffix) ?> (attuale)</option>
                        <?php endif; ?>
                        <?php foreach ($reassignOptions as $o): ?>
                        <?php
                            // Per ogni opzione (singolo tavolo o combinazione) raccolgo i nomi
                            // dei tavoli che la compongono e che sono occupati in questo turno.
                            $valueIds = array_filter(array_map('intval', explode(',', (string)$o['value'])));
                            $totalParts = count($valueIds);
                            $busyNames = [];
                            foreach ($valueIds as $oid) {
                                if (isset($busySet[$oid])) $busyNames[] = $tableNamesById[$oid] ?? "T{$oid}";
                            }
                            $busyCount = count($busyNames);
                            $optLabel = $o['label'];
                            if ($busyCount > 0) {
                                if ($busyCount === $totalParts) {
                                    // Singolo busy, oppure tutti i tavoli del combo busy: suffisso generico
                                    $optLabel .= ' · occupato';
                                } else {
                                    // Combo con SOLO ALCUNI tavoli busy: specifica quali (es. "Tav. 4 occupato")
                                    $optLabel .= ' · ' . implode(', ', $busyNames) . ' occupat' . ($busyCount > 1 ? 'i' : 'o');
                                }
                            }
                        ?>
                        <option value="<?= e($o['value']) ?>" <?= $o['value'] === $curOpt ? 'selected' : '' ?>><?= e($optLabel) ?></option>
                        <?php endforeach; ?>
                        <?php if (!empty($tables) && count($tables) > 1): ?>
                        <option value="" disabled>──────────</option>
                        <option value="__multi__">↔ Combina tavoli…</option>
                        <?php endif; ?>
                    </select>
                    <div class="tm-pop-foot">
                        <a href="<?= url('dashboard/reservations/' . $rid) ?>" class="tm-pop-link">Apri scheda completa</a>
                        <button type="submit" class="btn-tm-new"><i class="bi bi-check me-1"></i> Salva</button>
                    </div>
                </form>
                <?php else: ?>
                <?php /* Cliente già arrivato / no-show: tavolo bloccato, solo lettura */ ?>
                <label class="tm-pop-label">Tavolo assegnato</label>
                <div class="tm-pop-table-ro"><?php if (!empty($resTableLabel[$rid])): ?><?= e($resTableLabel[$rid]) ?><?php else: ?>&mdash; Nessun tavolo &mdash;<?php endif; ?></div>
                <div class="tm-pop-foot">
                    <a href="<?= url('dashboard/reservations/' . $rid) ?>" class="tm-pop-link">Apri scheda completa</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <?php
        // Modale "Combina tavoli" (Fase A) — UNA volta sola per pagina, riusato
        // da tutti i popup tramite EvuleryCombineTables.open()
        // Filtra fuori i tavoli bloccati: non possono essere combinati manualmente
        // (sono fuori uso totale). I "solo manuale" (is_bookable_online=0) restano
        // selezionabili — l'operatore puo' assegnarli manualmente.
        $mmTables = array_values(array_filter($tables, fn($t) => (int)($t['is_blocked'] ?? 0) === 0));
        if (!empty($mmTables) && count($mmTables) > 1) {
            include __DIR__ . '/../../partials/tables-multiselect-modal.php';
        }
        // Enhancement dropdown custom (sostituisce visivamente il <select> nativo).
        // Da includere DOPO che tutti i select sono nel DOM. Idempotente.
        include __DIR__ . '/../../partials/select-tavolo-enhance.php';
    ?>
    <?php if (!empty($tables) && count($tables) > 1): ?>
    <script nonce="<?= csp_nonce() ?>">
    (function () {
        document.querySelectorAll('.tm-pop-table-form').forEach(function (form) {
            var sel = form.querySelector('select[name="table_option"]');
            if (!sel) return;
            sel.addEventListener('change', function () {
                if (sel.value !== '__multi__') return;
                var busyRaw = form.dataset.busyIds || '';
                var busyIds = busyRaw ? busyRaw.split(',').map(function (s) { return parseInt(s, 10); }).filter(Boolean) : [];
                window.EvuleryCombineTables.open({
                    form:          form,
                    partySize:     parseInt(form.dataset.partySize, 10) || 1,
                    previousValue: form.dataset.prevValue || '',
                    label:         form.dataset.resLabel || '',
                    busyIds:       busyIds
                });
            });
        });
    })();
    </script>
    <?php endif; ?>

    <?php else: ?>
    <!-- ===== MODALITÀ SETUP ===== -->
    <form method="POST" action="<?= url('dashboard/settings/tables/map') ?>" id="tm-map-form">
        <?= csrf_field() ?>
        <input type="hidden" name="positions" id="tm-map-positions">
    </form>
    <?php if ($multiArea): ?>
    <div class="tm-op-bar">
        <div class="tm-area-tabs">
            <button type="button" class="tm-area-tab active" data-all="1"><i class="bi bi-grid-3x3-gap me-1"></i>Tutte le aree</button>
            <?php foreach ($areas as $a): ?>
            <button type="button" class="tm-area-tab" data-area="<?= e($a) ?>"><?php if ($areaHasDot($a)): ?><span class="tm-area-tab-dot" style="background:<?= e(area_color($a)) ?>;"></span><?php endif; ?><?= e($a) ?></button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    <!-- Legenda stati permanenti del tavolo (Setup mode). I tavoli archiviati
         non appaiono qui — sono visibili solo in "Elenco tavoli > Archiviati". -->
    <div class="tm-setup-legend">
        <span class="tm-leg-item"><span class="tm-leg-swatch tm-leg-setup-active"></span> Attivo</span>
        <span class="tm-leg-item"><span class="tm-leg-swatch tm-leg-setup-manual"></span> Solo manuale (jolly)</span>
        <span class="tm-leg-item"><span class="tm-leg-swatch tm-leg-setup-blocked"></span> <i class="bi bi-lock-fill" style="font-size:.7rem;color:#b3261e;"></i> Bloccato</span>
        <span class="tm-leg-hint">— i tavoli <i class="bi bi-archive"></i> archiviati sono nascosti dalla mappa, gestiscili da <em>Elenco tavoli</em></span>
    </div>
    <div class="tm-map-hint"><i class="bi bi-arrows-move me-1"></i> Trascina i tavoli per disporli come nella tua sala. Le posizioni si agganciano alla griglia. Ricordati di salvare.</div>
    <div class="tm-map-canvas" id="tm-map">
        <div class="tm-map-spacer" aria-hidden="true" style="width:<?= (int)$canvasContentW ?>px; height:<?= (int)$canvasContentH ?>px;"></div>
        <?php foreach ($tables as $t): ?>
        <?php
            // Setup: i tavoli archiviati (is_active=0) NON appaiono nella mappa.
            // Sono accessibili solo dalla sezione "Archiviati" in lista tavoli.
            if ((int)$t['is_active'] === 0) continue;

            $tArea = (string)($t['area'] ?? '');
            $hidden = false; // "Tutte le aree" è la vista iniziale
            $tManualOnly = (int)($t['is_bookable_online'] ?? 1) === 0;
            $tBlocked    = (int)($t['is_blocked'] ?? 0) === 1;
            $extraClasses = '';
            if ($tBlocked)        $extraClasses .= ' tm-status-blocked';
            if ($tManualOnly && !$tBlocked) $extraClasses .= ' tm-only-manual';
        ?>
        <div class="tm-map-table <?= $t['shape'] === 'round' ? 'round' : 'square' ?><?= $extraClasses ?>"
             data-id="<?= (int)$t['id'] ?>" data-area="<?= e($tArea) ?>"
             title="<?= $tBlocked ? e('Bloccato' . (!empty($t['block_reason']) ? ' — ' . $t['block_reason'] : '')) : ($tManualOnly ? 'Solo manuale (tavolo jolly)' : '') ?>"
             style="left:<?= (int)$t['_x'] ?>px; top:<?= (int)$t['_y'] ?>px;<?= $hidden ? 'display:none;' : '' ?>">
            <?php if ($tBlocked): ?><span class="tm-table-lock" title="Tavolo bloccato"><i class="bi bi-lock-fill"></i></span><?php endif; ?>
            <?php if ($areaHasDot($tArea)): ?><span class="tm-area-dot" style="background:<?= e(area_color($tArea)) ?>;"></span><?php endif; ?>
            <span class="tm-map-name"><?= e($t['name']) ?></span>
            <span class="tm-map-cap"><?= format_capacity((int)($t['min_capacity'] ?? 1), (int)$t['capacity']) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($tables)): ?>
<script nonce="<?= csp_nonce() ?>">
(function () {
    var canvas = document.getElementById('tm-map');
    var mode = <?= json_encode($mode) ?>;

    // Filtro area (comune) — "Tutte le aree" mostra tutti i tavoli insieme
    document.querySelectorAll('.tm-area-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.tm-area-tab').forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');
            var all = tab.hasAttribute('data-all');
            var area = tab.getAttribute('data-area');
            canvas.querySelectorAll('.tm-map-table').forEach(function (el) {
                el.style.display = (all || el.getAttribute('data-area') === area) ? '' : 'none';
            });
        });
    });

    if (mode === 'setup') {
        var GRID = 20;
        var form = document.getElementById('tm-map-form');
        var posInput = document.getElementById('tm-map-positions');
        var dragEl = null, offX = 0, offY = 0;
        function snap(v) { return Math.round(v / GRID) * GRID; }
        canvas.querySelectorAll('.tm-map-table').forEach(function (el) {
            el.addEventListener('mousedown', function (e) {
                e.preventDefault();
                dragEl = el;
                var r = el.getBoundingClientRect();
                offX = e.clientX - r.left; offY = e.clientY - r.top;
                el.classList.add('dragging');
            });
        });
        document.addEventListener('mousemove', function (e) {
            if (!dragEl) return;
            var cr = canvas.getBoundingClientRect();
            var x = snap(e.clientX - cr.left - offX);
            var y = snap(e.clientY - cr.top - offY);
            x = Math.max(0, Math.min(x, canvas.clientWidth - dragEl.offsetWidth));
            y = Math.max(0, Math.min(y, canvas.clientHeight - dragEl.offsetHeight));
            dragEl.style.left = x + 'px';
            dragEl.style.top = y + 'px';
        });
        document.addEventListener('mouseup', function () {
            if (dragEl) dragEl.classList.remove('dragging');
            dragEl = null;
        });
        form.addEventListener('submit', function () {
            var pos = {};
            canvas.querySelectorAll('.tm-map-table').forEach(function (el) {
                pos[el.getAttribute('data-id')] = {
                    x: parseInt(el.style.left, 10) || 0,
                    y: parseInt(el.style.top, 10) || 0
                };
            });
            posInput.value = JSON.stringify(pos);
        });
    } else {
        // Operativa — Fase 3a
        // Calendario dropdown: scegliere una data salta a quel giorno (mantiene l'orario)
        (function () {
            var toggle = document.getElementById('tm-cal-toggle');
            if (!toggle) return;
            var MONTHS = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
            var selectedDate = <?= json_encode($opDate) ?>;
            var navBase = <?= json_encode($opUrl) ?> + '?date=';
            var navTime = '&time=' + <?= json_encode($opTime) ?>;
            var dropdown = document.getElementById('tm-cal-dropdown');
            var grid = document.getElementById('tm-cal-grid');
            var monthLabel = document.getElementById('tm-cal-month');
            var sel = new Date(selectedDate + 'T00:00:00');
            var calMonth = sel.getMonth(), calYear = sel.getFullYear();
            function pad(n) { return n < 10 ? '0' + n : '' + n; }
            function isoDate(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
            function render() {
                monthLabel.textContent = MONTHS[calMonth] + ' ' + calYear;
                var first = new Date(calYear, calMonth, 1);
                var startDow = first.getDay() - 1; if (startDow < 0) startDow = 6;
                var days = new Date(calYear, calMonth + 1, 0).getDate();
                var today = new Date(); today.setHours(0, 0, 0, 0);
                var html = '';
                for (var i = 0; i < startDow; i++) html += '<div class="dr-cal-cell dr-cal-empty"></div>';
                for (var d = 1; d <= days; d++) {
                    var dt = new Date(calYear, calMonth, d); dt.setHours(0, 0, 0, 0);
                    var ds = isoDate(dt);
                    var cls = 'dr-cal-cell';
                    if (dt.getTime() === today.getTime()) cls += ' dr-cal-today';
                    if (ds === selectedDate) cls += ' dr-cal-selected';
                    html += '<div class="' + cls + '" data-date="' + ds + '">' + d + '</div>';
                }
                grid.innerHTML = html;
                grid.querySelectorAll('.dr-cal-cell:not(.dr-cal-empty)').forEach(function (cell) {
                    cell.addEventListener('click', function () {
                        location.href = navBase + this.dataset.date + navTime;
                    });
                });
            }
            var isMobile = window.matchMedia('(max-width: 768px)').matches;
            var modalOverlay = null;
            function openCal() {
                if (isMobile) {
                    if (!modalOverlay) {
                        modalOverlay = document.createElement('div');
                        modalOverlay.className = 'cal-modal-overlay';
                        var box = document.createElement('div'); box.className = 'cal-modal-box';
                        modalOverlay.appendChild(box);
                        modalOverlay.addEventListener('click', function (e) { if (e.target === modalOverlay) closeCal(); });
                    }
                    modalOverlay.querySelector('.cal-modal-box').appendChild(dropdown);
                    document.body.appendChild(modalOverlay);
                }
                dropdown.style.display = 'block';
                render();
            }
            function closeCal() {
                dropdown.style.display = 'none';
                if (isMobile && modalOverlay && modalOverlay.parentNode) {
                    toggle.parentNode.appendChild(dropdown);
                    modalOverlay.remove();
                }
            }
            toggle.addEventListener('click', function (e) {
                e.preventDefault(); e.stopPropagation();
                dropdown.style.display === 'none' ? openCal() : closeCal();
            });
            document.getElementById('tm-cal-prev').addEventListener('click', function (e) {
                e.stopPropagation(); calMonth--; if (calMonth < 0) { calMonth = 11; calYear--; } render();
            });
            document.getElementById('tm-cal-next').addEventListener('click', function (e) {
                e.stopPropagation(); calMonth++; if (calMonth > 11) { calMonth = 0; calYear++; } render();
            });
            document.addEventListener('click', function (e) {
                if (!isMobile && !e.target.closest('#tm-cal-dropdown') && !e.target.closest('#tm-cal-toggle')) closeCal();
            });
        })();
        // Centra la barra occupazione sullo slot attivo (mobile: niente swipe manuale)
        var occ = document.querySelector('.tm-occ');
        var activeSlot = occ && occ.querySelector('.tm-occ-col.on');
        if (occ && activeSlot) {
            var sRect = occ.getBoundingClientRect();
            var aRect = activeSlot.getBoundingClientRect();
            occ.scrollLeft += (aRect.left - sRect.left) - (sRect.width - aRect.width) / 2;
        }
        // Indicatore "ora corrente": posiziona la linea sul punto del servizio
        // corrispondente all'adesso (interpolando tra gli slot). Se l'ora è fuori
        // dalla fascia mostrata, la linea resta nascosta.
        (function () {
            var line = document.getElementById('tm-occ-now');
            if (!occ || !line) return;
            var cols = Array.prototype.slice.call(occ.querySelectorAll('.tm-occ-col'));
            if (!cols.length) return;
            var toMin = function (s) { return parseInt(s.slice(0, 2), 10) * 60 + parseInt(s.slice(3, 5), 10); };
            var now = new Date();
            var nowMin = now.getHours() * 60 + now.getMinutes();
            var times = cols.map(function (c) { return toMin(c.getAttribute('data-time')); });
            var step = times.length > 1 ? (times[1] - times[0]) : 30;
            if (nowMin < times[0] || nowMin > times[times.length - 1] + step) return; // fuori fascia
            var center = function (c) { return c.offsetLeft + c.offsetWidth / 2; };
            var prevIdx = -1;
            for (var i = 0; i < times.length; i++) { if (times[i] <= nowMin) prevIdx = i; else break; }
            var pos;
            if (prevIdx < 0) {
                pos = center(cols[0]);
            } else if (prevIdx >= times.length - 1) {
                var c = cols[prevIdx];
                pos = center(c) + Math.min(1, (nowMin - times[prevIdx]) / step) * (c.offsetWidth / 2);
            } else {
                var cp = cols[prevIdx], cn = cols[prevIdx + 1];
                var frac = (nowMin - times[prevIdx]) / (times[prevIdx + 1] - times[prevIdx]);
                pos = center(cp) + (center(cn) - center(cp)) * frac;
            }
            line.style.left = pos + 'px';
            line.style.display = 'block';
        })();
        // Dropdown selettore fascia servizio
        (function () {
            var btn = document.getElementById('tm-svc-btn');
            var menu = document.getElementById('tm-svc-menu');
            if (!btn || !menu) return;
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var open = menu.classList.toggle('on');
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            document.addEventListener('click', function (e) {
                if (!e.target.closest('.tm-svc')) { menu.classList.remove('on'); btn.setAttribute('aria-expanded', 'false'); }
            });
        })();
        function openPop(id) {
            var ov = id && document.getElementById(id);
            if (ov) ov.style.display = 'flex';
        }
        function highlight(ids) {
            canvas.querySelectorAll('.tm-map-table').forEach(function (el) {
                el.classList.toggle('hl', ids.indexOf(el.getAttribute('data-id')) >= 0);
            });
        }
        // clic su tavolo occupato → popup + evidenzia
        canvas.querySelectorAll('.tm-map-table[data-pop]').forEach(function (el) {
            el.addEventListener('click', function () {
                highlight([el.getAttribute('data-id')]);
                openPop(el.getAttribute('data-pop'));
            });
        });
        // clic sul badge "prossima prenotazione" → popup di QUELLA prenotazione
        // (stopPropagation: non apre il popup dell'occupante corrente del tavolo)
        canvas.querySelectorAll('.tm-next-badge[data-pop]').forEach(function (b) {
            b.addEventListener('click', function (e) {
                e.stopPropagation();
                var tbl = b.closest('.tm-map-table');
                if (tbl) highlight([tbl.getAttribute('data-id')]);
                openPop(b.getAttribute('data-pop'));
            });
        });
        // clic su riga prenotazione → popup + evidenzia il suo tavolo sulla mappa
        document.querySelectorAll('.tm-res-row').forEach(function (row) {
            row.addEventListener('click', function () {
                var ids = (row.getAttribute('data-tables') || '').split(',').filter(Boolean);
                highlight(ids);
                openPop(row.getAttribute('data-pop'));
            });
        });
        // chiusura popup
        document.querySelectorAll('.tm-pop-overlay').forEach(function (ov) {
            ov.addEventListener('click', function (e) {
                if (e.target === ov || e.target.hasAttribute('data-close')) ov.style.display = 'none';
            });
        });
        // tab mobile: commuta lista / mappa
        var twopane = document.getElementById('tm-twopane');
        document.querySelectorAll('.tm-vtab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                document.querySelectorAll('.tm-vtab').forEach(function (t) { t.classList.remove('active'); });
                tab.classList.add('active');
                var pane = tab.getAttribute('data-pane');
                twopane.classList.toggle('show-map', pane === 'map');
                twopane.classList.toggle('show-list', pane === 'list');
            });
        });
    }
})();
</script>
<?php endif; ?>

<?php endif; ?>
