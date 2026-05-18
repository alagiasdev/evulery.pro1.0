<?php
/**
 * Mappa sala — modalità setup: posizionamento visivo dei tavoli (Fase 2.1).
 * Variabili: $tenant, $canUse, $tables, $areas
 */
// Posizione iniziale per i tavoli mai posizionati (griglia di fallback per area)
$areaCounters = [];
foreach ($tables as &$_t) {
    if ($_t['position_x'] !== null && $_t['position_y'] !== null) {
        $_t['_x'] = (int)$_t['position_x'];
        $_t['_y'] = (int)$_t['position_y'];
    } else {
        $area = (string)($_t['area'] ?? '');
        $i = $areaCounters[$area] ?? 0;
        $areaCounters[$area] = $i + 1;
        $_t['_x'] = 30 + ($i % 6) * 140;
        $_t['_y'] = 30 + intdiv($i, 6) * 120;
    }
}
unset($_t);
?>

<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">Impostazioni</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1rem;">Configura il tuo ristorante</p>

<?php $activeKey = 'settings-tables'; include __DIR__ . '/../../partials/settings-tabs.php'; ?>

<?php if (!$canUse): ?>
<?php $lockedTitle = 'La Gestione Tavoli'; include __DIR__ . '/../../partials/service-locked.php'; ?>
<?php else: ?>

<form method="POST" action="<?= url('dashboard/settings/tables/map') ?>" id="tm-map-form">
    <?= csrf_field() ?>
    <input type="hidden" name="positions" id="tm-map-positions">

    <div class="card tm-card">
        <div class="tm-head">
            <a href="<?= url('dashboard/settings/tables') ?>" class="tm-map-back"><i class="bi bi-arrow-left"></i> Elenco tavoli</a>
            <span class="tm-head-title"><i class="bi bi-grid-3x3 me-1"></i> Mappa sala</span>
            <?php if (count($areas) > 1): ?>
            <div class="tm-area-tabs">
                <?php foreach ($areas as $idx => $a): ?>
                <button type="button" class="tm-area-tab <?= $idx === 0 ? 'active' : '' ?>" data-area="<?= e($a) ?>"><?= e($a) ?></button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <button type="submit" class="btn-tm-new" style="margin-left:auto;"><i class="bi bi-check-circle me-1"></i> Salva posizioni</button>
        </div>

        <?php if (empty($tables)): ?>
        <div class="tm-empty">
            <i class="bi bi-grid-3x3"></i>
            <p>Nessun tavolo da posizionare. Aggiungi prima i tavoli dall'<a href="<?= url('dashboard/settings/tables') ?>">elenco tavoli</a>.</p>
        </div>
        <?php else: ?>
        <div class="tm-map-hint"><i class="bi bi-arrows-move me-1"></i> Trascina i tavoli per disporli come nella tua sala. Le posizioni si agganciano alla griglia.</div>
        <div class="tm-map-canvas" id="tm-map">
            <?php
            $firstArea = $areas[0] ?? '';
            foreach ($tables as $t):
                $tArea = (string)($t['area'] ?? '');
                // in modalità multi-area mostra solo la prima area all'avvio
                $hidden = (count($areas) > 1 && $tArea !== $firstArea);
            ?>
            <div class="tm-map-table <?= $t['shape'] === 'round' ? 'round' : 'square' ?><?= (int)$t['is_active'] ? '' : ' off' ?>"
                 data-id="<?= (int)$t['id'] ?>"
                 data-area="<?= e($tArea) ?>"
                 style="left:<?= (int)$t['_x'] ?>px; top:<?= (int)$t['_y'] ?>px;<?= $hidden ? 'display:none;' : '' ?>">
                <span class="tm-map-name"><?= e($t['name']) ?></span>
                <span class="tm-map-cap"><?= (int)$t['capacity'] ?>p</span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</form>

<?php if (!empty($tables)): ?>
<script nonce="<?= csp_nonce() ?>">
(function () {
    var GRID = 20;
    var canvas = document.getElementById('tm-map');
    var form = document.getElementById('tm-map-form');
    var posInput = document.getElementById('tm-map-positions');
    var dragEl = null, offX = 0, offY = 0;

    function snap(v) { return Math.round(v / GRID) * GRID; }

    function startDrag(el, clientX, clientY) {
        dragEl = el;
        var r = el.getBoundingClientRect();
        offX = clientX - r.left;
        offY = clientY - r.top;
        el.classList.add('dragging');
    }
    function moveDrag(clientX, clientY) {
        if (!dragEl) return;
        var cr = canvas.getBoundingClientRect();
        var x = snap(clientX - cr.left - offX);
        var y = snap(clientY - cr.top - offY);
        x = Math.max(0, Math.min(x, canvas.clientWidth - dragEl.offsetWidth));
        y = Math.max(0, Math.min(y, canvas.clientHeight - dragEl.offsetHeight));
        dragEl.style.left = x + 'px';
        dragEl.style.top = y + 'px';
    }
    function endDrag() {
        if (dragEl) dragEl.classList.remove('dragging');
        dragEl = null;
    }

    canvas.querySelectorAll('.tm-map-table').forEach(function (el) {
        el.addEventListener('mousedown', function (e) {
            e.preventDefault();
            startDrag(el, e.clientX, e.clientY);
        });
    });
    document.addEventListener('mousemove', function (e) {
        if (dragEl) moveDrag(e.clientX, e.clientY);
    });
    document.addEventListener('mouseup', endDrag);

    // Filtro area
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

    // Al salvataggio: raccoglie le posizioni di TUTTI i tavoli
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
})();
</script>
<?php endif; ?>

<?php endif; ?>
