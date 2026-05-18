<?php
/**
 * Impostazioni > Tavoli — lista tavoli per area + priorità (Fase 1).
 * Variabili: $tenant, $canUse, $tables, $areas, $comboMap
 */
$activeTables = array_filter($tables, fn($t) => (int)$t['is_active'] === 1);
$totCovers = array_sum(array_map(fn($t) => (int)$t['is_active'] === 1 ? (int)$t['capacity'] : 0, $tables));
$comboCount = 0;
foreach ($comboMap as $ids) { $comboCount += count($ids); }
$comboCount = (int)($comboCount / 2);

// Dati tavoli per il modale (JS)
$jsTables = [];
foreach ($tables as $t) {
    $jsTables[] = [
        'id'         => (int)$t['id'],
        'name'       => $t['name'],
        'capacity'   => (int)$t['capacity'],
        'area'       => $t['area'] ?? '',
        'shape'      => $t['shape'],
        'note'       => $t['internal_note'] ?? '',
        'active'     => (int)$t['is_active'],
        'combinable' => array_values(array_unique(array_map('intval', $comboMap[(int)$t['id']] ?? []))),
    ];
}
?>

<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">Impostazioni</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1rem;">Configura il tuo ristorante</p>

<?php $activeKey = 'settings-tables'; include __DIR__ . '/../../partials/settings-tabs.php'; ?>

<?php if (!$canUse): ?>
<?php $lockedTitle = 'La Gestione Tavoli'; include __DIR__ . '/../../partials/service-locked.php'; ?>
<?php else: ?>

<?php $autoOn = !empty($tenant['table_auto_assign']); ?>
<!-- Master toggle: assegnazione automatica -->
<form method="POST" action="<?= url('dashboard/settings/tables/auto-assign') ?>" id="tm-autoassign-form">
    <?= csrf_field() ?>
    <div class="master-toggle <?= $autoOn ? 'enabled' : 'disabled' ?>" id="tm-master">
        <div class="mt-left">
            <div class="mt-icon" style="background:var(--brand-light);color:var(--brand);">
                <i class="bi bi-magic"></i>
            </div>
            <div>
                <div class="mt-title">Assegnazione automatica dei tavoli</div>
                <div class="mt-desc">Alla prenotazione il sistema assegna il primo tavolo libero in ordine di priorità. Puoi sempre cambiarlo a mano.</div>
            </div>
        </div>
        <div class="toggle-big <?= $autoOn ? 'on' : 'off' ?>" id="tm-main-toggle"></div>
        <input type="hidden" name="table_auto_assign" id="tm-auto-input" value="<?= $autoOn ? '1' : '' ?>">
    </div>
    <div class="tm-card <?= $autoOn ? '' : 'disabled-look' ?>" id="tm-buffer-box" style="margin-top:.75rem;">
        <div class="tm-opt-row">
            <div class="tm-opt-txt">
                <div class="tm-opt-name">Buffer di pulizia / turnover</div>
                <div class="tm-opt-desc">Minuti liberi tra due turni sullo stesso tavolo prima di poterlo riassegnare. 0 = turni back-to-back.</div>
            </div>
            <select name="table_turnover_buffer" class="tm-fi" style="width:120px;flex-shrink:0;">
                <?php foreach ([0, 5, 10, 15, 20, 30] as $b): ?>
                <option value="<?= $b ?>" <?= (int)($tenant['table_turnover_buffer'] ?? 15) === $b ? 'selected' : '' ?>><?= $b ?> min</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="tm-opt-foot">
            <button type="submit" class="btn-tm-new"><i class="bi bi-check-circle me-1"></i> Salva impostazioni</button>
        </div>
    </div>
</form>

<div class="card tm-card" style="margin-top:16px;">
    <div class="tm-head">
        <span class="tm-head-title">Tavoli</span>
        <div class="tm-area-tabs">
            <button type="button" class="tm-area-tab active" data-area="">Tutte le aree</button>
            <?php foreach ($areas as $a): ?>
            <button type="button" class="tm-area-tab" data-area="<?= e($a) ?>"><?= e($a) ?></button>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn-tm-new" id="tm-btn-new"><i class="bi bi-plus-lg"></i> Nuovo tavolo</button>
    </div>

    <div class="tm-stats">
        <div class="tm-stat"><div class="tm-stat-num"><?= count($tables) ?></div><div class="tm-stat-label">Tavoli</div></div>
        <div class="tm-stat"><div class="tm-stat-num"><?= count($activeTables) ?></div><div class="tm-stat-label">Attivi</div></div>
        <div class="tm-stat"><div class="tm-stat-num"><?= $totCovers ?></div><div class="tm-stat-label">Coperti</div></div>
        <div class="tm-stat"><div class="tm-stat-num"><?= $comboCount ?></div><div class="tm-stat-label">Combinazioni</div></div>
    </div>

    <?php if (empty($tables)): ?>
    <div class="tm-empty">
        <i class="bi bi-grid-3x3"></i>
        <p>Nessun tavolo configurato. Aggiungi il primo tavolo per attivare l'assegnazione automatica.</p>
    </div>
    <?php else: ?>
    <div class="tm-prio-hint">
        <i class="bi bi-sort-down"></i>
        L'ordine = priorità di auto-assegnazione: i tavoli più in alto si riempiono per primi.
        Trascina la maniglia per riordinare (solo in "Tutte le aree").
    </div>
    <div id="tm-list">
        <?php $rank = 0; foreach ($tables as $t): ?>
        <?php
            $isActive = (int)$t['is_active'] === 1;
            if ($isActive) $rank++;
            $combo = $comboMap[(int)$t['id']] ?? [];
        ?>
        <div class="tm-row<?= $isActive ? '' : ' tm-row-off' ?>" data-id="<?= (int)$t['id'] ?>" data-area="<?= e($t['area'] ?? '') ?>" draggable="true">
            <i class="bi bi-grip-vertical tm-drag" title="Trascina per riordinare"></i>
            <span class="tm-rank"><?= $isActive ? $rank : '—' ?></span>
            <span class="tm-cap"><?= (int)$t['capacity'] ?>p</span>
            <div class="tm-info">
                <div class="tm-name"><?= e($t['name']) ?><?= $isActive ? '' : ' <span class="tm-tag tm-tag-off">disattivato</span>' ?></div>
                <div class="tm-meta">
                    <?php if (!empty($t['area'])): ?><span><i class="bi bi-geo-alt"></i> <?= e($t['area']) ?></span><?php endif; ?>
                    <?php if (!empty($t['internal_note'])): ?><span><?= e($t['internal_note']) ?></span><?php endif; ?>
                    <?php if (!empty($combo)): ?><span class="tm-tag tm-tag-combo">↔ <?= count($combo) ?> combinabili</span><?php endif; ?>
                </div>
            </div>
            <button type="button" class="tm-act tm-edit" data-id="<?= (int)$t['id'] ?>" title="Modifica"><i class="bi bi-pencil"></i></button>
            <form method="POST" action="<?= url('dashboard/settings/tables/' . (int)$t['id'] . '/toggle') ?>" class="d-inline">
                <?= csrf_field() ?>
                <button type="submit" class="tm-act" title="<?= $isActive ? 'Disattiva' : 'Attiva' ?>"><i class="bi <?= $isActive ? 'bi-toggle-on' : 'bi-toggle-off' ?>"></i></button>
            </form>
            <form method="POST" action="<?= url('dashboard/settings/tables/' . (int)$t['id'] . '/delete') ?>" class="d-inline" data-confirm="Eliminare il tavolo «<?= e($t['name']) ?>»?">
                <?= csrf_field() ?>
                <button type="submit" class="tm-act tm-act-danger" title="Elimina"><i class="bi bi-trash3"></i></button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Barra salva ordine (compare dopo un riordino) -->
<form method="POST" action="<?= url('dashboard/settings/tables/reorder') ?>" id="tm-reorder-form" style="display:none;">
    <?= csrf_field() ?>
    <input type="hidden" name="order" id="tm-reorder-order">
    <div class="tm-reorder-bar">
        <span><i class="bi bi-info-circle me-1"></i> Hai cambiato l'ordine di priorità.</span>
        <button type="submit" class="btn-tm-new"><i class="bi bi-check-circle me-1"></i> Salva ordine</button>
    </div>
</form>

<!-- Modale aggiungi/modifica tavolo -->
<div class="tm-modal-overlay" id="tm-modal" style="display:none;">
    <div class="tm-modal">
        <form method="POST" id="tm-form" action="<?= url('dashboard/settings/tables') ?>">
            <?= csrf_field() ?>
            <div class="tm-modal-head">
                <span class="tm-modal-title" id="tm-modal-title"><i class="bi bi-plus-circle me-1"></i> Nuovo tavolo</span>
                <button type="button" class="tm-modal-x" id="tm-modal-close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="tm-modal-body">
                <div class="tm-fg">
                    <label class="tm-fl">Nome tavolo</label>
                    <input type="text" class="tm-fi" name="name" id="tm-f-name" maxlength="60" placeholder="es. Tavolo 1, Finestra A" required>
                </div>
                <div class="tm-frow">
                    <div class="tm-fg">
                        <label class="tm-fl">Posti (capacità)</label>
                        <input type="number" class="tm-fi" name="capacity" id="tm-f-capacity" min="1" max="30" value="2" required>
                    </div>
                    <div class="tm-fg">
                        <label class="tm-fl">Area</label>
                        <input type="text" class="tm-fi" name="area" id="tm-f-area" list="tm-areas" maxlength="60" placeholder="es. Sala Interna">
                        <datalist id="tm-areas">
                            <?php foreach ($areas as $a): ?><option value="<?= e($a) ?>"><?php endforeach; ?>
                        </datalist>
                        <div class="tm-fhint">Scegli un'area esistente o scrivine una nuova.</div>
                    </div>
                </div>
                <div class="tm-fg">
                    <label class="tm-fl">Forma <span style="text-transform:none;font-weight:400;color:#adb5bd;">— usata nella mappa sala</span></label>
                    <div class="tm-shape-sel">
                        <label class="tm-shape-opt"><input type="radio" name="shape" value="square" checked> <i class="bi bi-square"></i> Quadrato</label>
                        <label class="tm-shape-opt"><input type="radio" name="shape" value="round"> <i class="bi bi-circle"></i> Rotondo</label>
                    </div>
                </div>
                <div class="tm-fg">
                    <label class="tm-fl">Combinabile con <span style="text-transform:none;font-weight:400;color:#adb5bd;">— per gruppi grandi</span></label>
                    <div class="tm-combo-list" id="tm-f-combinable">
                        <span class="tm-combo-empty">Aggiungi altri tavoli per poterli combinare.</span>
                    </div>
                </div>
                <div class="tm-frow">
                    <div class="tm-fg">
                        <label class="tm-fl">Nota interna</label>
                        <input type="text" class="tm-fi" name="internal_note" id="tm-f-note" maxlength="255" placeholder="es. vicino finestra">
                    </div>
                    <div class="tm-fg">
                        <label class="tm-fl">Stato</label>
                        <select class="tm-fi" name="is_active" id="tm-f-active">
                            <option value="1">Attivo</option>
                            <option value="0">Disattivato</option>
                        </select>
                    </div>
                </div>
                <div class="tm-info-box" id="tm-prio-note" style="display:none;">
                    <i class="bi bi-info-circle me-1"></i> La priorità si imposta trascinando il tavolo nella lista.
                </div>
            </div>
            <div class="tm-modal-foot">
                <button type="button" class="btn-tm-cancel" id="tm-modal-cancel">Annulla</button>
                <button type="submit" class="btn-tm-new"><i class="bi bi-check-circle me-1"></i> Salva tavolo</button>
            </div>
        </form>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
(function () {
    var TABLES = <?= json_encode($jsTables, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var baseAction = <?= json_encode(url('dashboard/settings/tables')) ?>;

    // ---- Master toggle: assegnazione automatica ----
    var tmMaster = document.getElementById('tm-master');
    var tmToggle = document.getElementById('tm-main-toggle');
    var tmAutoInput = document.getElementById('tm-auto-input');
    var tmBufferBox = document.getElementById('tm-buffer-box');
    if (tmToggle) {
        tmToggle.addEventListener('click', function () {
            var on = !tmToggle.classList.contains('on');
            tmToggle.classList.toggle('on', on);
            tmToggle.classList.toggle('off', !on);
            tmMaster.classList.toggle('enabled', on);
            tmMaster.classList.toggle('disabled', !on);
            tmAutoInput.value = on ? '1' : '';
            tmBufferBox.classList.toggle('disabled-look', !on);
        });
    }

    // ---- Area filter ----
    var areaTabs = document.querySelectorAll('.tm-area-tab');
    var rows = function () { return document.querySelectorAll('#tm-list .tm-row'); };
    var currentArea = '';
    areaTabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            areaTabs.forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');
            currentArea = tab.getAttribute('data-area');
            rows().forEach(function (r) {
                var show = (currentArea === '' || r.getAttribute('data-area') === currentArea);
                r.style.display = show ? '' : 'none';
            });
        });
    });

    // ---- Drag & drop reorder (solo "Tutte le aree") ----
    var list = document.getElementById('tm-list');
    var reorderForm = document.getElementById('tm-reorder-form');
    var reorderInput = document.getElementById('tm-reorder-order');
    var dragEl = null;

    if (list) {
        list.querySelectorAll('.tm-row').forEach(function (row) {
            row.addEventListener('dragstart', function (e) {
                if (currentArea !== '') { e.preventDefault(); return; }
                dragEl = row;
                row.classList.add('tm-dragging');
                e.dataTransfer.effectAllowed = 'move';
            });
            row.addEventListener('dragend', function () {
                row.classList.remove('tm-dragging');
                dragEl = null;
                syncReorder();
            });
            row.addEventListener('dragover', function (e) {
                if (!dragEl || dragEl === row) return;
                e.preventDefault();
                var rect = row.getBoundingClientRect();
                var after = (e.clientY - rect.top) > rect.height / 2;
                list.insertBefore(dragEl, after ? row.nextSibling : row);
            });
        });
    }

    function syncReorder() {
        var ids = [];
        list.querySelectorAll('.tm-row').forEach(function (r) { ids.push(r.getAttribute('data-id')); });
        reorderInput.value = ids.join(',');
        reorderForm.style.display = '';
        // ricalcola i numeri di rango visibili
        var rank = 0;
        list.querySelectorAll('.tm-row').forEach(function (r) {
            var badge = r.querySelector('.tm-rank');
            if (!r.classList.contains('tm-row-off')) { rank++; badge.textContent = rank; }
            else { badge.textContent = '—'; }
        });
    }

    // ---- Modale ----
    var modal = document.getElementById('tm-modal');
    var form = document.getElementById('tm-form');
    var fName = document.getElementById('tm-f-name');
    var fCap = document.getElementById('tm-f-capacity');
    var fArea = document.getElementById('tm-f-area');
    var fNote = document.getElementById('tm-f-note');
    var fActive = document.getElementById('tm-f-active');
    var comboBox = document.getElementById('tm-f-combinable');
    var modalTitle = document.getElementById('tm-modal-title');
    var prioNote = document.getElementById('tm-prio-note');

    function buildCombinable(excludeId, checkedIds) {
        if (TABLES.length <= (excludeId ? 1 : 0)) {
            comboBox.innerHTML = '<span class="tm-combo-empty">Aggiungi altri tavoli per poterli combinare.</span>';
            return;
        }
        var html = '';
        TABLES.forEach(function (t) {
            if (t.id === excludeId) return;
            var on = checkedIds.indexOf(t.id) !== -1;
            html += '<label class="tm-combo-chk' + (on ? ' on' : '') + '">' +
                '<input type="checkbox" name="combinable[]" value="' + t.id + '"' + (on ? ' checked' : '') + '> ' +
                escapeHtml(t.name) + '</label>';
        });
        comboBox.innerHTML = html || '<span class="tm-combo-empty">Aggiungi altri tavoli per poterli combinare.</span>';
        comboBox.querySelectorAll('.tm-combo-chk input').forEach(function (cb) {
            cb.addEventListener('change', function () {
                cb.closest('.tm-combo-chk').classList.toggle('on', cb.checked);
            });
        });
    }

    function openModal(table) {
        if (table) {
            modalTitle.innerHTML = '<i class="bi bi-pencil-square me-1"></i> Modifica ' + escapeHtml(table.name);
            form.action = baseAction + '/' + table.id;
            fName.value = table.name;
            fCap.value = table.capacity;
            fArea.value = table.area;
            fNote.value = table.note;
            fActive.value = String(table.active);
            form.querySelector('input[name="shape"][value="' + (table.shape === 'round' ? 'round' : 'square') + '"]').checked = true;
            buildCombinable(table.id, table.combinable || []);
            prioNote.style.display = '';
        } else {
            modalTitle.innerHTML = '<i class="bi bi-plus-circle me-1"></i> Nuovo tavolo';
            form.action = baseAction;
            fName.value = ''; fCap.value = 2; fArea.value = ''; fNote.value = '';
            fActive.value = '1';
            form.querySelector('input[name="shape"][value="square"]').checked = true;
            buildCombinable(null, []);
            prioNote.style.display = 'none';
        }
        modal.style.display = 'flex';
    }
    function closeModal() { modal.style.display = 'none'; }

    document.getElementById('tm-btn-new').addEventListener('click', function () { openModal(null); });
    document.getElementById('tm-modal-close').addEventListener('click', closeModal);
    document.getElementById('tm-modal-cancel').addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });

    document.querySelectorAll('.tm-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = parseInt(btn.getAttribute('data-id'), 10);
            var t = TABLES.filter(function (x) { return x.id === id; })[0];
            if (t) openModal(t);
        });
    });

    function escapeHtml(s) {
        var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML;
    }
})();
</script>

<?php endif; ?>
