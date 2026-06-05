<?php
// Variabili: $riders (con orders_this_month iniettato), $palette (array di hex)
$defaultColor = '#dc3545';
?>

<div class="rd-page-header">
    <div>
        <h1>Rider</h1>
        <p class="rd-page-sub">Gestisci i fattorini per le consegne</p>
    </div>
    <div class="rd-page-actions">
        <a href="<?= url('dashboard/riders/stats') ?>" class="btn btn-outline-success">
            <i class="bi bi-bar-chart me-1"></i> Statistiche
        </a>
        <button type="button" class="btn btn-success" data-rd-open="" data-rd-name="" data-rd-phone="" data-rd-color="<?= e($defaultColor) ?>" data-rd-active="1">
            <i class="bi bi-plus-lg me-1"></i> Nuovo rider
        </button>
    </div>
</div>

<?php if (empty($riders)): ?>
<div class="card" style="padding:2rem;text-align:center;">
    <i class="bi bi-bicycle" style="font-size:2.5rem;color:#adb5bd;"></i>
    <h2 style="font-size:1.05rem;margin:.75rem 0 .25rem;">Nessun rider configurato</h2>
    <p style="font-size:.82rem;color:#6c757d;margin-bottom:1rem;">Aggiungi il primo rider per iniziare a tracciare le consegne.</p>
    <div>
        <button type="button" class="btn btn-success" data-rd-open="" data-rd-name="" data-rd-phone="" data-rd-color="<?= e($defaultColor) ?>" data-rd-active="1">
            <i class="bi bi-plus-lg me-1"></i> Aggiungi rider
        </button>
    </div>
</div>
<?php else: ?>

<!-- Desktop table -->
<div class="card rd-card d-none d-md-block">
    <table class="rd-table">
        <thead>
            <tr>
                <th style="width:30%;">Nome</th>
                <th>Telefono</th>
                <th>Ordini mese</th>
                <th>Stato</th>
                <th style="width:160px;text-align:right;">Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($riders as $r): ?>
            <tr class="<?= (int)$r['is_active'] === 0 ? 'rd-row-inactive' : '' ?>">
                <td>
                    <div class="rd-name">
                        <span class="rd-dot" style="background:<?= e($r['color_hex']) ?>;"></span>
                        <?= e($r['name']) ?>
                    </div>
                </td>
                <td>
                    <?php if (!empty($r['phone'])): ?>
                        <a href="tel:<?= e($r['phone']) ?>" class="rd-phone"><?= e($r['phone']) ?></a>
                    <?php else: ?>
                        <span class="rd-empty">—</span>
                    <?php endif; ?>
                </td>
                <td><strong><?= (int)$r['orders_this_month'] ?></strong></td>
                <td>
                    <?php if ((int)$r['is_active'] === 1): ?>
                        <span class="rd-status rd-status--active">Attivo</span>
                    <?php else: ?>
                        <span class="rd-status rd-status--inactive">Archiviato</span>
                    <?php endif; ?>
                </td>
                <td style="text-align:right;">
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            data-rd-open="<?= (int)$r['id'] ?>"
                            data-rd-name="<?= e($r['name']) ?>"
                            data-rd-phone="<?= e($r['phone'] ?? '') ?>"
                            data-rd-color="<?= e($r['color_hex']) ?>"
                            data-rd-active="<?= (int)$r['is_active'] ?>">
                        <i class="bi bi-pencil"></i> Modifica
                    </button>
                    <form method="POST" action="<?= url('dashboard/riders/' . (int)$r['id'] . '/toggle') ?>" style="display:inline;"
                          data-confirm="<?= (int)$r['is_active'] === 1 ? 'Archiviare questo rider? Non potrà più ricevere nuovi ordini.' : 'Riattivare questo rider?' ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            <?php if ((int)$r['is_active'] === 1): ?>
                                <i class="bi bi-archive"></i> Archivia
                            <?php else: ?>
                                <i class="bi bi-arrow-clockwise"></i> Riattiva
                            <?php endif; ?>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Mobile cards -->
<div class="d-md-none">
    <?php foreach ($riders as $r): ?>
    <div class="card rd-card-m <?= (int)$r['is_active'] === 0 ? 'rd-row-inactive' : '' ?>">
        <div class="rd-card-m-head">
            <div class="rd-name">
                <span class="rd-dot" style="background:<?= e($r['color_hex']) ?>;"></span>
                <?= e($r['name']) ?>
            </div>
            <?php if ((int)$r['is_active'] === 1): ?>
                <span class="rd-status rd-status--active">Attivo</span>
            <?php else: ?>
                <span class="rd-status rd-status--inactive">Archiviato</span>
            <?php endif; ?>
        </div>
        <div class="rd-card-m-meta">
            <span><?php if (!empty($r['phone'])): ?><i class="bi bi-telephone me-1"></i><?= e($r['phone']) ?><?php else: ?><span class="rd-empty">Nessun telefono</span><?php endif; ?></span>
            <span><strong><?= (int)$r['orders_this_month'] ?></strong> ordini mese</span>
        </div>
        <div class="rd-card-m-actions">
            <button type="button" class="btn btn-sm btn-outline-secondary"
                    data-rd-open="<?= (int)$r['id'] ?>"
                    data-rd-name="<?= e($r['name']) ?>"
                    data-rd-phone="<?= e($r['phone'] ?? '') ?>"
                    data-rd-color="<?= e($r['color_hex']) ?>"
                    data-rd-active="<?= (int)$r['is_active'] ?>">
                <i class="bi bi-pencil"></i> Modifica
            </button>
            <form method="POST" action="<?= url('dashboard/riders/' . (int)$r['id'] . '/toggle') ?>" style="display:inline;"
                  data-confirm="<?= (int)$r['is_active'] === 1 ? 'Archiviare questo rider?' : 'Riattivare?' ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-outline-secondary">
                    <?php if ((int)$r['is_active'] === 1): ?>
                        <i class="bi bi-archive"></i> Archivia
                    <?php else: ?>
                        <i class="bi bi-arrow-clockwise"></i> Riattiva
                    <?php endif; ?>
                </button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Modale crea/modifica rider -->
<div class="rd-modal-overlay" id="rd-modal" style="display:none;">
    <div class="rd-modal">
        <div class="rd-modal-head">
            <h3 class="rd-modal-title" id="rd-modal-title">Nuovo rider</h3>
            <button type="button" class="rd-modal-close" data-rd-close aria-label="Chiudi">&times;</button>
        </div>
        <form method="POST" id="rd-modal-form" action="<?= url('dashboard/riders') ?>">
            <?= csrf_field() ?>
            <div class="rd-field">
                <label class="rd-field-label" for="rd-name">Nome <span style="color:#dc3545;">*</span></label>
                <input type="text" class="form-control" id="rd-name" name="name" required maxlength="100" placeholder="Es. Mario Rossi">
            </div>
            <div class="rd-field">
                <label class="rd-field-label" for="rd-phone">Telefono (opzionale)</label>
                <input type="tel" class="form-control" id="rd-phone" name="phone" maxlength="30" placeholder="+39 333 444 5566">
                <div class="rd-field-hint">Mostrato sulla board pubblica per contatti urgenti.</div>
            </div>
            <div class="rd-field">
                <label class="rd-field-label">Colore badge</label>
                <div class="rd-color-picker" id="rd-color-picker">
                    <?php foreach ($palette as $color): ?>
                    <div class="rd-color-swatch" data-color="<?= e($color) ?>" style="background:<?= e($color) ?>;"></div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="rd-color" name="color_hex" value="<?= e($defaultColor) ?>">
                <div class="rd-field-hint">Identifica gli ordini di questo rider a colpo d'occhio.</div>
            </div>
            <div class="rd-toggle-row">
                <div>
                    <div style="font-size:.85rem;font-weight:600;">Attivo</div>
                    <div style="font-size:.72rem;color:#6c757d;">Riceve nuove assegnazioni</div>
                </div>
                <label class="rd-switch">
                    <input type="checkbox" name="is_active" value="1" id="rd-active" checked>
                    <span class="rd-switch-slider"></span>
                </label>
            </div>
            <div class="rd-modal-actions">
                <button type="button" class="btn btn-outline-secondary" data-rd-close>Annulla</button>
                <button type="submit" class="btn btn-success" id="rd-submit-btn">Salva rider</button>
            </div>
        </form>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
(function () {
    var modal = document.getElementById('rd-modal');
    var form  = document.getElementById('rd-modal-form');
    var title = document.getElementById('rd-modal-title');
    var nameInput  = document.getElementById('rd-name');
    var phoneInput = document.getElementById('rd-phone');
    var colorInput = document.getElementById('rd-color');
    var activeInput = document.getElementById('rd-active');
    var palette = document.getElementById('rd-color-picker');
    var baseAction = '<?= url("dashboard/riders") ?>';

    function selectSwatch(color) {
        colorInput.value = color;
        palette.querySelectorAll('.rd-color-swatch').forEach(function (s) {
            s.classList.toggle('selected', s.getAttribute('data-color') === color);
        });
    }
    palette.querySelectorAll('.rd-color-swatch').forEach(function (s) {
        s.addEventListener('click', function () { selectSwatch(s.getAttribute('data-color')); });
    });

    document.querySelectorAll('[data-rd-open]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-rd-open');
            if (id && id !== '') {
                title.textContent = 'Modifica rider';
                form.action = baseAction + '/' + id + '/update';
            } else {
                title.textContent = 'Nuovo rider';
                form.action = baseAction;
            }
            nameInput.value  = btn.getAttribute('data-rd-name') || '';
            phoneInput.value = btn.getAttribute('data-rd-phone') || '';
            selectSwatch(btn.getAttribute('data-rd-color') || '<?= e($defaultColor) ?>');
            activeInput.checked = btn.getAttribute('data-rd-active') !== '0';
            modal.style.display = 'flex';
            setTimeout(function () { nameInput.focus(); }, 50);
        });
    });

    document.querySelectorAll('[data-rd-close]').forEach(function (btn) {
        btn.addEventListener('click', function () { modal.style.display = 'none'; });
    });
    modal.addEventListener('click', function (e) {
        if (e.target === modal) modal.style.display = 'none';
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.style.display === 'flex') modal.style.display = 'none';
    });
})();
</script>
