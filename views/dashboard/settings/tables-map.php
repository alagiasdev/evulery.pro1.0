<?php
/**
 * Mappa sala — modalità setup (posizionamento) e operativa (stato sala).
 * Fase 2 (mappa) + Fase 3a (operativa a due pannelli: lista + mappa).
 * Variabili: $tenant, $canUse, $tables, $areas, $mode, $opDate, $opTime,
 *            $floorState, $reassignOptions, $currentMap,
 *            $dayReservations, $assignments
 */
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

$firstArea = $areas[0] ?? '';
$multiArea = count($areas) > 1;
// Area "principale" = la prima creata: niente pallino colore. Le aree
// aggiuntive ricevono il pallino solo se ce n'è più di una.
$primaryArea = $firstArea;
$areaHasDot = fn(string $a): bool => $multiArea && $a !== '' && $a !== $primaryArea;

// Slot orari dello scorri-orari (operativa)
$slots = [];
for ($m = 12 * 60; $m <= 23 * 60 + 30; $m += 30) {
    $slots[] = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
}
$setupUrl = url('dashboard/settings/tables/map');   // mappa setup (Impostazioni)
$opUrl    = url('dashboard/sala');                   // mappa operativa (sidebar "Sala")
$opBack   = 'dashboard/sala?date=' . urlencode($opDate) . '&time=' . urlencode($opTime);
?>

<?php if ($mode === 'setup'): ?>
<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">Impostazioni</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1rem;">Configura il tuo ristorante</p>
<?php $activeKey = 'settings-tables'; include __DIR__ . '/../../partials/settings-tabs.php'; ?>
<?php else: ?>
<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:1rem;"><i class="bi bi-grid-3x3 me-1" style="color:var(--brand);"></i> Sala</h2>
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
            <a href="<?= $setupUrl ?>" class="<?= $mode === 'setup' ? 'active' : '' ?>"><i class="bi bi-pencil"></i> Setup</a>
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
    // Raggruppa: "Da assegnare" (attive senza tavolo) + per ora di prenotazione
    $unassigned = [];
    $byHour = [];
    foreach ($dayReservations as $r) {
        $rid = (int)$r['id'];
        if (!isset($assignments[$rid]) && in_array((string)$r['status'], ['confirmed', 'pending', 'arrived'], true)) {
            $unassigned[] = $r;
        } else {
            $byHour[substr((string)$r['reservation_time'], 0, 2)][] = $r;
        }
    }
    ksort($byHour);
    $nUnassigned = count($unassigned);

    // Connettore tavoli combinati: prenotazioni che occupano 2 tavoli a
    // quest'ora. Barra tra i centri dei due tavoli (tavolo = 76x76).
    $tablePos = [];
    foreach ($tables as $t) {
        $tablePos[(int)$t['id']] = ['x' => (int)$t['_x'], 'y' => (int)$t['_y'], 'area' => (string)($t['area'] ?? '')];
    }
    $resTablesNow = [];
    foreach ($floorState as $tid => $occ) {
        $resTablesNow[(int)$occ['reservation_id']][] = (int)$tid;
    }
    $comboBars = [];
    $comboTableIds = [];   // tavoli che fanno parte di una combinazione attiva
    foreach ($resTablesNow as $tids) {
        if (count($tids) < 2) continue;            // combinazioni = coppie
        [$ta, $tb] = [$tids[0], $tids[1]];
        if (!isset($tablePos[$ta], $tablePos[$tb])) continue;
        $comboTableIds[$ta] = $comboTableIds[$tb] = true;
        $c1x = $tablePos[$ta]['x'] + 38; $c1y = $tablePos[$ta]['y'] + 38;
        $c2x = $tablePos[$tb]['x'] + 38; $c2y = $tablePos[$tb]['y'] + 38;
        $dx = $c2x - $c1x; $dy = $c2y - $c1y;
        $comboBars[] = [
            'left'  => $c1x,
            'top'   => $c1y,
            'width' => sqrt($dx * $dx + $dy * $dy),
            'angle' => rad2deg(atan2($dy, $dx)),
            'bx'    => ($c1x + $c2x) / 2,
            'by'    => ($c1y + $c2y) / 2,
            'area'  => $tablePos[$ta]['area'],
            'party' => (int)($floorState[$ta]['party'] ?? 0),
        ];
    }

    // Slot della fascia oraria con almeno una prenotazione attiva: una
    // prenotazione "occupa" lo slot da inizio a inizio+durata tavolo.
    $slotDuration = max(15, (int)($tenant['table_duration'] ?? 90));
    $slotHasRes = [];
    foreach ($dayReservations as $r) {
        if (!in_array((string)$r['status'], ['confirmed', 'pending', 'arrived'], true)) continue;
        $st = (int)substr((string)$r['reservation_time'], 0, 2) * 60 + (int)substr((string)$r['reservation_time'], 3, 2);
        foreach ($slots as $s) {
            $sm = (int)substr($s, 0, 2) * 60 + (int)substr($s, 3, 2);
            if ($sm >= $st && $sm < $st + $slotDuration) {
                $slotHasRes[$s] = true;
            }
        }
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
    ?>
    <div class="tm-op-bar">
        <a class="tm-op-arrow" href="<?= $opUrl ?>?date=<?= $prev ?>&time=<?= e($opTime) ?>"><i class="bi bi-chevron-left"></i></a>
        <span class="tm-op-date"><?= e(date('D d/m/Y', strtotime($opDate))) ?></span>
        <a class="tm-op-arrow" href="<?= $opUrl ?>?date=<?= $next ?>&time=<?= e($opTime) ?>"><i class="bi bi-chevron-right"></i></a>
        <?php if ($multiArea): ?>
        <div class="tm-area-tabs" style="margin-left:8px;">
            <button type="button" class="tm-area-tab active" data-all="1"><i class="bi bi-grid-3x3-gap me-1"></i>Tutte le aree</button>
            <?php foreach ($areas as $a): ?>
            <button type="button" class="tm-area-tab" data-area="<?= e($a) ?>"><?php if ($areaHasDot($a)): ?><span class="tm-area-tab-dot" style="background:<?= e(area_color($a)) ?>;"></span><?php endif; ?><?= e($a) ?></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <span class="tm-op-legend">
            <span><span class="tm-dot" style="background:#198754;"></span> Libero</span>
            <span><span class="tm-dot" style="background:#0d6efd;"></span> Occupato</span>
        </span>
    </div>

    <!-- Tab (solo mobile): commuta lista / mappa -->
    <div class="tm-vtabs">
        <button type="button" class="tm-vtab" data-pane="list">Prenotazioni<?php if ($nUnassigned > 0): ?> <span class="tm-vtab-badge"><?= $nUnassigned ?></span><?php endif; ?></button>
        <button type="button" class="tm-vtab active" data-pane="map">Mappa</button>
    </div>

    <div class="tm-twopane show-map" id="tm-twopane">
        <!-- Pannello sinistro: prenotazioni del giorno -->
        <div class="tm-pane-list">
            <?php if (empty($dayReservations)): ?>
            <div class="tm-list-empty"><i class="bi bi-calendar-x me-1"></i> Nessuna prenotazione per questa data.</div>
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
            <div class="tm-scrub">
                <?php foreach ($slots as $s): ?>
                <a href="<?= $opUrl ?>?date=<?= e($opDate) ?>&time=<?= $s ?>"
                   class="tm-scrub-slot <?= $s === $opTime ? 'active' : '' ?>"><?= $s ?><span class="tm-scrub-dot<?= isset($slotHasRes[$s]) ? '' : ' empty' ?>"></span></a>
                <?php endforeach; ?>
            </div>
            <div class="tm-map-canvas" id="tm-map">
                <?php /* Connettori tavoli combinati — dietro i tavoli */ ?>
                <?php foreach ($comboBars as $cb): ?>
                <div class="tm-combo-bar" data-area="<?= e($cb['area']) ?>"
                     style="left:<?= $cb['left'] ?>px; top:<?= $cb['top'] ?>px; width:<?= round($cb['width'], 1) ?>px; transform:rotate(<?= round($cb['angle'], 2) ?>deg);"></div>
                <?php endforeach; ?>
                <?php foreach ($tables as $t): ?>
                <?php
                    $tArea = (string)($t['area'] ?? '');
                    $hidden = false; // "Tutte le aree" è la vista iniziale
                    $occ = $floorState[(int)$t['id']] ?? null;
                ?>
                <div class="tm-map-table <?= $t['shape'] === 'round' ? 'round' : 'square' ?> <?= $occ ? 'busy' : 'freev' ?>"
                     data-id="<?= (int)$t['id'] ?>" data-area="<?= e($tArea) ?>"
                     <?= $occ ? 'data-pop="tm-pop-res-' . (int)$occ['reservation_id'] . '"' : '' ?>
                     style="left:<?= (int)$t['_x'] ?>px; top:<?= (int)$t['_y'] ?>px;<?= $hidden ? 'display:none;' : '' ?>">
                    <?php if ($areaHasDot($tArea)): ?><span class="tm-area-dot" style="background:<?= e(area_color($tArea)) ?>;"></span><?php endif; ?>
                    <?php if ($occ): ?>
                    <span class="tm-map-name"><?= e($occ['name']) ?></span>
                    <?php // Tavolo in combinazione: niente party sul tavolo (è della coppia → sulla pastiglia) ?>
                    <span class="tm-map-cap"><?php if (!isset($comboTableIds[(int)$t['id']])): ?><?= (int)$occ['party'] ?>p &middot; <?php endif; ?><?= e($occ['time']) ?></span>
                    <?php else: ?>
                    <span class="tm-map-name"><?= e($t['name']) ?></span>
                    <span class="tm-map-cap"><?= (int)$t['capacity'] ?>p</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php /* Pastiglia catena al centro di ogni combinazione — sopra i tavoli */ ?>
                <?php foreach ($comboBars as $cb): ?>
                <div class="tm-combo-badge" data-area="<?= e($cb['area']) ?>"
                     style="left:<?= round($cb['bx'], 1) ?>px; top:<?= round($cb['by'], 1) ?>px;"><i class="bi bi-link-45deg"></i> <?= (int)$cb['party'] ?>p</div>
                <?php endforeach; ?>
            </div>
            <div class="tm-map-hint"><i class="bi bi-info-circle me-1"></i> Tavoli blu = occupati alle <?= e($opTime) ?>. Clicca un tavolo o una prenotazione per i dettagli.</div>
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
                <form method="POST" action="<?= url('dashboard/reservations/' . $rid . '/table') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect_back" value="<?= e($opBack) ?>">
                    <label class="tm-pop-label">Tavolo assegnato</label>
                    <select name="table_option" class="tm-fi">
                        <option value="">&mdash; Nessun tavolo &mdash;</option>
                        <?php foreach ($reassignOptions as $o): ?>
                        <option value="<?= e($o['value']) ?>" <?= $o['value'] === $curOpt ? 'selected' : '' ?>><?= e($o['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="tm-pop-foot">
                        <a href="<?= url('dashboard/reservations/' . $rid) ?>" class="tm-pop-link">Apri scheda completa</a>
                        <button type="submit" class="btn-tm-new"><i class="bi bi-check me-1"></i> Salva</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

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
    <div class="tm-map-hint"><i class="bi bi-arrows-move me-1"></i> Trascina i tavoli per disporli come nella tua sala. Le posizioni si agganciano alla griglia. Ricordati di salvare.</div>
    <div class="tm-map-canvas" id="tm-map">
        <?php foreach ($tables as $t): ?>
        <?php
            $tArea = (string)($t['area'] ?? '');
            $hidden = false; // "Tutte le aree" è la vista iniziale
        ?>
        <div class="tm-map-table <?= $t['shape'] === 'round' ? 'round' : 'square' ?><?= (int)$t['is_active'] ? '' : ' off' ?>"
             data-id="<?= (int)$t['id'] ?>" data-area="<?= e($tArea) ?>"
             style="left:<?= (int)$t['_x'] ?>px; top:<?= (int)$t['_y'] ?>px;<?= $hidden ? 'display:none;' : '' ?>">
            <?php if ($areaHasDot($tArea)): ?><span class="tm-area-dot" style="background:<?= e(area_color($tArea)) ?>;"></span><?php endif; ?>
            <span class="tm-map-name"><?= e($t['name']) ?></span>
            <span class="tm-map-cap"><?= (int)$t['capacity'] ?>p</span>
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
            canvas.querySelectorAll('.tm-map-table, .tm-combo-bar, .tm-combo-badge').forEach(function (el) {
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
        // Centra lo scorri-orari sullo slot attivo: niente swipe manuale
        var scrub = document.querySelector('.tm-scrub');
        var activeSlot = scrub && scrub.querySelector('.tm-scrub-slot.active');
        if (scrub && activeSlot) {
            var sRect = scrub.getBoundingClientRect();
            var aRect = activeSlot.getBoundingClientRect();
            scrub.scrollLeft += (aRect.left - sRect.left) - (sRect.width - aRect.width) / 2;
        }
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
