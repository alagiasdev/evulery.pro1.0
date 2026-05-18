<?php
/**
 * Mappa sala — modalità setup (posizionamento) e operativa (stato sala). Fase 2.
 * Variabili: $tenant, $canUse, $tables, $areas, $mode, $opDate, $opTime,
 *            $floorState, $reassignOptions, $currentMap
 */
// Posizione iniziale per i tavoli mai posizionati (griglia di fallback per area)
$areaCounters = [];
foreach ($tables as &$_t) {
    if ($_t['position_x'] !== null && $_t['position_y'] !== null) {
        $_t['_x'] = (int)$_t['position_x'];
        $_t['_y'] = (int)$_t['position_y'];
    } else {
        $a = (string)($_t['area'] ?? '');
        $i = $areaCounters[$a] ?? 0;
        $areaCounters[$a] = $i + 1;
        $_t['_x'] = 30 + ($i % 6) * 140;
        $_t['_y'] = 30 + intdiv($i, 6) * 120;
    }
}
unset($_t);

$firstArea = $areas[0] ?? '';
$multiArea = count($areas) > 1;

// Slot orari dello scorri-orari (operativa)
$slots = [];
for ($m = 12 * 60; $m <= 23 * 60 + 30; $m += 30) {
    $slots[] = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
}
$mapBase = url('dashboard/settings/tables/map');
$opBack  = 'dashboard/settings/tables/map?mode=operativa&date=' . urlencode($opDate) . '&time=' . urlencode($opTime);
?>

<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">Impostazioni</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1rem;">Configura il tuo ristorante</p>

<?php $activeKey = 'settings-tables'; include __DIR__ . '/../../partials/settings-tabs.php'; ?>

<?php if (!$canUse): ?>
<?php $lockedTitle = 'La Gestione Tavoli'; include __DIR__ . '/../../partials/service-locked.php'; ?>
<?php else: ?>

<div class="card tm-card">
    <div class="tm-head">
        <a href="<?= url('dashboard/settings/tables') ?>" class="tm-map-back"><i class="bi bi-arrow-left"></i> Elenco tavoli</a>
        <span class="tm-head-title"><i class="bi bi-grid-3x3 me-1"></i> Mappa sala</span>
        <div class="tm-mode-toggle">
            <a href="<?= $mapBase ?>?mode=setup" class="<?= $mode === 'setup' ? 'active' : '' ?>"><i class="bi bi-pencil"></i> Setup</a>
            <a href="<?= $mapBase ?>?mode=operativa" class="<?= $mode === 'operativa' ? 'active' : '' ?>"><i class="bi bi-eye"></i> Operativa</a>
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
    <!-- ===== MODALITÀ OPERATIVA ===== -->
    <div class="tm-op-bar">
        <?php
        $prev = date('Y-m-d', strtotime($opDate . ' -1 day'));
        $next = date('Y-m-d', strtotime($opDate . ' +1 day'));
        ?>
        <a class="tm-op-arrow" href="<?= $mapBase ?>?mode=operativa&date=<?= $prev ?>&time=<?= e($opTime) ?>"><i class="bi bi-chevron-left"></i></a>
        <span class="tm-op-date"><?= e(date('D d/m/Y', strtotime($opDate))) ?></span>
        <a class="tm-op-arrow" href="<?= $mapBase ?>?mode=operativa&date=<?= $next ?>&time=<?= e($opTime) ?>"><i class="bi bi-chevron-right"></i></a>
        <?php if ($multiArea): ?>
        <div class="tm-area-tabs" style="margin-left:8px;">
            <?php foreach ($areas as $idx => $a): ?>
            <button type="button" class="tm-area-tab <?= $idx === 0 ? 'active' : '' ?>" data-area="<?= e($a) ?>"><?= e($a) ?></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <span class="tm-op-legend">
            <span><span class="tm-dot" style="background:#198754;"></span> Libero</span>
            <span><span class="tm-dot" style="background:#0d6efd;"></span> Occupato</span>
        </span>
    </div>
    <div class="tm-scrub">
        <?php foreach ($slots as $s): ?>
        <a href="<?= $mapBase ?>?mode=operativa&date=<?= e($opDate) ?>&time=<?= $s ?>"
           class="tm-scrub-slot <?= $s === $opTime ? 'active' : '' ?>"><?= $s ?></a>
        <?php endforeach; ?>
    </div>
    <div class="tm-map-canvas" id="tm-map">
        <?php foreach ($tables as $t): ?>
        <?php
            $tArea = (string)($t['area'] ?? '');
            $hidden = ($multiArea && $tArea !== $firstArea);
            $occ = $floorState[(int)$t['id']] ?? null;
        ?>
        <div class="tm-map-table <?= $t['shape'] === 'round' ? 'round' : 'square' ?> <?= $occ ? 'busy' : 'freev' ?>"
             data-id="<?= (int)$t['id'] ?>" data-area="<?= e($tArea) ?>"
             <?= $occ ? 'data-pop="tm-pop-' . (int)$t['id'] . '"' : '' ?>
             style="left:<?= (int)$t['_x'] ?>px; top:<?= (int)$t['_y'] ?>px;<?= $hidden ? 'display:none;' : '' ?>">
            <span class="tm-map-name"><?= e($t['name']) ?></span>
            <?php if ($occ): ?>
            <span class="tm-map-cap"><?= e($occ['name']) ?></span>
            <?php else: ?>
            <span class="tm-map-cap"><?= (int)$t['capacity'] ?>p</span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="tm-map-hint"><i class="bi bi-info-circle me-1"></i> Tavoli blu = occupati alle <?= e($opTime) ?>. Clicca un tavolo occupato per vedere la prenotazione o spostarla.</div>

    <!-- Popup per i tavoli occupati -->
    <?php foreach ($floorState as $tableId => $occ): ?>
    <div class="tm-pop-overlay" id="tm-pop-<?= (int)$tableId ?>">
        <div class="tm-pop">
            <div class="tm-pop-head">
                <span><i class="bi bi-person me-1"></i> <?= e($occ['name']) ?></span>
                <button type="button" class="tm-pop-x" data-close>&times;</button>
            </div>
            <div class="tm-pop-body">
                <div class="tm-pop-meta"><?= (int)$occ['party'] ?> persone &middot; <?= e($occ['time']) ?> &middot; <?= e(status_label($occ['status'])) ?></div>
                <form method="POST" action="<?= url('dashboard/reservations/' . (int)$occ['reservation_id'] . '/table') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect_back" value="<?= e($opBack) ?>">
                    <label class="tm-pop-label">Sposta su un altro tavolo</label>
                    <select name="table_option" class="tm-fi">
                        <option value="">&mdash; Nessun tavolo &mdash;</option>
                        <?php $cur = $currentMap[(int)$occ['reservation_id']] ?? ''; ?>
                        <?php foreach ($reassignOptions as $o): ?>
                        <option value="<?= e($o['value']) ?>" <?= $o['value'] === $cur ? 'selected' : '' ?>><?= e($o['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="tm-pop-foot">
                        <a href="<?= url('dashboard/reservations/' . (int)$occ['reservation_id']) ?>" class="tm-pop-link">Apri prenotazione</a>
                        <button type="submit" class="btn-tm-new"><i class="bi bi-check me-1"></i> Sposta</button>
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
            <?php foreach ($areas as $idx => $a): ?>
            <button type="button" class="tm-area-tab <?= $idx === 0 ? 'active' : '' ?>" data-area="<?= e($a) ?>"><?= e($a) ?></button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    <div class="tm-map-hint"><i class="bi bi-arrows-move me-1"></i> Trascina i tavoli per disporli come nella tua sala. Le posizioni si agganciano alla griglia. Ricordati di salvare.</div>
    <div class="tm-map-canvas" id="tm-map">
        <?php foreach ($tables as $t): ?>
        <?php
            $tArea = (string)($t['area'] ?? '');
            $hidden = ($multiArea && $tArea !== $firstArea);
        ?>
        <div class="tm-map-table <?= $t['shape'] === 'round' ? 'round' : 'square' ?><?= (int)$t['is_active'] ? '' : ' off' ?>"
             data-id="<?= (int)$t['id'] ?>" data-area="<?= e($tArea) ?>"
             style="left:<?= (int)$t['_x'] ?>px; top:<?= (int)$t['_y'] ?>px;<?= $hidden ? 'display:none;' : '' ?>">
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

    // Filtro area (comune)
    document.querySelectorAll('.tm-area-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.tm-area-tab').forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');
            var area = tab.getAttribute('data-area');
            canvas.querySelectorAll('.tm-map-table').forEach(function (el) {
                el.style.display = (el.getAttribute('data-area') === area) ? '' : 'none';
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
        // Operativa: clic su tavolo occupato → popup
        canvas.querySelectorAll('.tm-map-table[data-pop]').forEach(function (el) {
            el.addEventListener('click', function () {
                var ov = document.getElementById(el.getAttribute('data-pop'));
                if (ov) ov.style.display = 'flex';
            });
        });
        document.querySelectorAll('.tm-pop-overlay').forEach(function (ov) {
            ov.addEventListener('click', function (e) {
                if (e.target === ov || e.target.hasAttribute('data-close')) ov.style.display = 'none';
            });
        });
    }
})();
</script>
<?php endif; ?>

<?php endif; ?>
