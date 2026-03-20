<?php
$menuTabs = [
    ['url' => url('dashboard/menu'),             'icon' => 'bi-egg-fried', 'label' => 'Piatti',     'key' => 'piatti'],
    ['url' => url('dashboard/menu/categories'),   'icon' => 'bi-folder',    'label' => 'Categorie',  'key' => 'categorie'],
    ['url' => url('dashboard/menu/appearance'),   'icon' => 'bi-palette',   'label' => 'Aspetto',    'key' => 'aspetto'],
];
?>

<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">
    <i class="bi bi-book me-1" style="color:var(--brand);"></i> Menu Digitale
</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1rem;">Gestisci il menu del tuo ristorante</p>

<!-- Tabs -->
<div class="settings-tabs">
    <?php foreach ($menuTabs as $tab): ?>
    <a href="<?= $tab['url'] ?>" class="settings-tab <?= $tab['key'] === 'categorie' ? 'active' : '' ?>">
        <i class="bi <?= $tab['icon'] ?>"></i> <?= $tab['label'] ?>
    </a>
    <?php endforeach; ?>
</div>

<div style="max-width:700px;">
    <?php
        $totalCats = 0;
        foreach ($hierarchy as $p) { $totalCats += 1 + count($p['children']); }
    ?>

    <?php if (empty($hierarchy)): ?>
    <div class="card" style="padding:2.5rem; text-align:center;">
        <i class="bi bi-folder" style="font-size:2.5rem; color:#dee2e6;"></i>
        <p style="color:#6c757d; margin-top:.75rem; font-size:.88rem;">Nessuna categoria. Creane una per iniziare ad aggiungere piatti.</p>
    </div>
    <?php else: ?>

    <div class="dm-cat-accordion">
        <?php foreach ($hierarchy as $parent): ?>
        <?php
            $parentCount = $counts[(int)$parent['id']] ?? 0;
            $childTotal = 0;
            foreach ($parent['children'] as $child) {
                $childTotal += $counts[(int)$child['id']] ?? 0;
            }
            $totalItems = $parentCount + $childTotal;
        ?>
        <div class="dm-cat-card" data-cat-id="<?= (int)$parent['id'] ?>">
            <div class="dm-cat-header" data-toggle-cat>
                <div class="dm-cat-expand"><i class="bi bi-chevron-right"></i></div>
                <div class="dm-cat-icon"><i class="bi <?= e($parent['icon'] ?? 'bi-list') ?>"></i></div>
                <div class="dm-cat-name"><?= e($parent['name']) ?></div>
                <div class="dm-cat-badge"><?= $totalItems ?> piatt<?= $totalItems === 1 ? 'o' : 'i' ?></div>
                <div class="dm-cat-actions">
                    <button type="button" class="dm-cat-action-btn" data-edit-cat="<?= (int)$parent['id'] ?>"
                            data-cat-name="<?= e($parent['name']) ?>" data-cat-desc="<?= e($parent['description'] ?? '') ?>"
                            data-cat-icon="<?= e($parent['icon'] ?? 'bi-list') ?>" title="Modifica">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <form method="POST" action="<?= url("dashboard/menu/categories/{$parent['id']}/delete") ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="dm-cat-action-btn dm-cat-action-danger" title="Elimina"
                                data-confirm="Eliminare la categoria &quot;<?= e($parent['name']) ?>&quot;<?= !empty($parent['children']) ? ' e tutte le sue sottocategorie' : '' ?>?">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div class="dm-cat-body">
                <?php if (!empty($parent['children'])): ?>
                <div class="dm-subcat-list">
                    <?php foreach ($parent['children'] as $child): ?>
                    <div class="dm-subcat-row">
                        <div class="dm-subcat-dot"></div>
                        <div class="dm-subcat-icon"><i class="bi <?= e($child['icon'] ?? 'bi-list') ?>"></i></div>
                        <div class="dm-subcat-name"><?= e($child['name']) ?></div>
                        <div class="dm-subcat-count"><?= $counts[(int)$child['id']] ?? 0 ?> piatt<?= ($counts[(int)$child['id']] ?? 0) === 1 ? 'o' : 'i' ?></div>
                        <div class="dm-subcat-actions">
                            <button type="button" class="dm-cat-action-btn" data-edit-cat="<?= (int)$child['id'] ?>"
                                    data-cat-name="<?= e($child['name']) ?>" data-cat-desc="<?= e($child['description'] ?? '') ?>"
                                    data-cat-icon="<?= e($child['icon'] ?? 'bi-list') ?>" title="Modifica">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="POST" action="<?= url("dashboard/menu/categories/{$child['id']}/delete") ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="dm-cat-action-btn dm-cat-action-danger" title="Elimina"
                                        data-confirm="Eliminare la sottocategoria &quot;<?= e($child['name']) ?>&quot;?">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php elseif ($parentCount === 0): ?>
                <div class="dm-subcat-empty">Nessuna sottocategoria</div>
                <?php endif; ?>

                <!-- Inline add subcategory -->
                <div class="dm-subcat-add-trigger" data-show-subcat-form>
                    <i class="bi bi-plus-circle"></i> Aggiungi sottocategoria
                </div>
                <div class="dm-subcat-add-form" style="display:none;">
                    <form method="POST" action="<?= url('dashboard/menu/categories') ?>" class="dm-inline-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="parent_id" value="<?= (int)$parent['id'] ?>">
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="Nome sottocategoria..." required maxlength="100">
                        <button type="submit" class="dm-inline-btn-add"><i class="bi bi-check-lg"></i></button>
                        <button type="button" class="dm-inline-btn-cancel" data-hide-subcat-form>Annulla</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php endif; ?>

    <!-- Add new category (dashed trigger → inline form) -->
    <div class="dm-new-cat-trigger" id="newCatTrigger">
        <i class="bi bi-plus-circle"></i>
        <span>Aggiungi categoria</span>
    </div>

    <div class="dm-new-cat-form" id="newCatForm" style="display:none;">
        <div style="font-weight:600; font-size:.88rem; margin-bottom:.75rem; display:flex; align-items:center; gap:.4rem;">
            <i class="bi bi-plus-circle" style="color:var(--brand);"></i> Nuova categoria
        </div>
        <form method="POST" action="<?= url('dashboard/menu/categories') ?>">
            <?= csrf_field() ?>
            <div style="display:flex; gap:.5rem; margin-bottom:.5rem;">
                <div style="flex:1;">
                    <label class="form-label fw-semibold" style="font-size:.75rem; margin-bottom:.2rem;">Nome *</label>
                    <input type="text" name="name" class="form-control form-control-sm" placeholder="es. Contorni" required maxlength="100">
                </div>
                <div style="flex:1;">
                    <label class="form-label fw-semibold" style="font-size:.75rem; margin-bottom:.2rem;">Descrizione</label>
                    <input type="text" name="description" class="form-control form-control-sm" placeholder="opzionale" maxlength="500">
                </div>
            </div>
            <div style="margin-bottom:.5rem;">
                <label class="form-label fw-semibold" style="font-size:.75rem; margin-bottom:.2rem;">Icona</label>
                <div class="dm-icon-grid-mini">
                    <?php foreach ($categoryIcons as $iconClass => $iconLabel): ?>
                    <label class="dm-icon-option" title="<?= e($iconLabel) ?>">
                        <input type="radio" name="icon" value="<?= e($iconClass) ?>" <?= $iconClass === 'bi-list' ? 'checked' : '' ?>>
                        <span class="dm-icon-box"><i class="bi <?= e($iconClass) ?>"></i></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div style="display:flex; gap:.5rem; justify-content:flex-end;">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="newCatCancel">Annulla</button>
                <button type="submit" class="btn btn-sm btn-save">
                    <i class="bi bi-plus me-1"></i> Crea categoria
                </button>
            </div>
        </form>
    </div>

    <div style="color:#6c757d; font-size:.75rem; margin-top:.75rem; font-style:italic;">
        <i class="bi bi-info-circle me-1"></i> Le sottocategorie sono opzionali. Le categorie senza piatti non appaiono nel menu pubblico.
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCatModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" id="editCatForm">
                <?= csrf_field() ?>
                <div class="modal-header" style="padding:.75rem 1rem;">
                    <h6 class="modal-title">Modifica categoria</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:.75rem 1rem;">
                    <div class="mb-2">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">Nome *</label>
                        <input type="text" name="name" id="editCatName" class="form-control form-control-sm" required maxlength="100">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">Descrizione</label>
                        <input type="text" name="description" id="editCatDesc" class="form-control form-control-sm" maxlength="500">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">Icona</label>
                        <div class="dm-icon-grid-mini" id="editCatIconGrid">
                            <?php foreach ($categoryIcons as $iconClass => $iconLabel): ?>
                            <label class="dm-icon-option" title="<?= e($iconLabel) ?>">
                                <input type="radio" name="icon" value="<?= e($iconClass) ?>">
                                <span class="dm-icon-box"><i class="bi <?= e($iconClass) ?>"></i></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="padding:.5rem 1rem;">
                    <button type="submit" class="btn btn-sm btn-save">Salva</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
(function() {
    // Confirm delete
    document.querySelectorAll('[data-confirm]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm)) { e.preventDefault(); }
        });
    });

    // Accordion toggle
    document.querySelectorAll('[data-toggle-cat]').forEach(function(header) {
        header.addEventListener('click', function(e) {
            // Don't toggle when clicking action buttons
            if (e.target.closest('.dm-cat-actions')) return;
            this.closest('.dm-cat-card').classList.toggle('open');
        });
    });

    // Edit category modal
    document.querySelectorAll('[data-edit-cat]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            var id = this.dataset.editCat;
            var icon = this.dataset.catIcon || 'bi-list';
            document.getElementById('editCatForm').action = '<?= url('dashboard/menu/categories/') ?>' + id + '/update';
            document.getElementById('editCatName').value = this.dataset.catName;
            document.getElementById('editCatDesc').value = this.dataset.catDesc;
            var radios = document.querySelectorAll('#editCatIconGrid input[type="radio"]');
            radios.forEach(function(r) { r.checked = (r.value === icon); });
            new bootstrap.Modal(document.getElementById('editCatModal')).show();
        });
    });

    // Inline subcategory form: show
    document.querySelectorAll('[data-show-subcat-form]').forEach(function(trigger) {
        trigger.addEventListener('click', function() {
            var form = this.nextElementSibling;
            this.style.display = 'none';
            form.style.display = '';
            form.querySelector('input[name="name"]').focus();
        });
    });

    // Inline subcategory form: cancel
    document.querySelectorAll('[data-hide-subcat-form]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var formWrap = this.closest('.dm-subcat-add-form');
            formWrap.style.display = 'none';
            formWrap.previousElementSibling.style.display = '';
        });
    });

    // New category: show/hide form
    var trigger = document.getElementById('newCatTrigger');
    var form = document.getElementById('newCatForm');
    var cancel = document.getElementById('newCatCancel');
    if (trigger && form) {
        trigger.addEventListener('click', function() {
            trigger.style.display = 'none';
            form.style.display = '';
            form.querySelector('input[name="name"]').focus();
        });
    }
    if (cancel) {
        cancel.addEventListener('click', function() {
            form.style.display = 'none';
            trigger.style.display = '';
        });
    }

    // Auto-open card after subcategory creation (via ?open=ID)
    var params = new URLSearchParams(window.location.search);
    var openId = params.get('open');
    if (openId) {
        var card = document.querySelector('.dm-cat-card[data-cat-id="' + openId + '"]');
        if (card) {
            card.classList.add('open');
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
})();
</script>
