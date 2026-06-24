<?php
$menuTabs = [
    ['url' => url('dashboard/menu'),             'icon' => 'bi-egg-fried', 'label' => 'Voci',       'key' => 'piatti'],
    ['url' => url('dashboard/menu/categories'),   'icon' => 'bi-folder',    'label' => 'Categorie',  'key' => 'categorie'],
    ['url' => url('dashboard/menu/appearance'),   'icon' => 'bi-palette',   'label' => 'Aspetto',    'key' => 'aspetto'],
];
$menuUrl = url($tenant['slug'] . '/menu');
$qrData  = urlencode($menuUrl);
$qrUrl   = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . $qrData; // anteprima
$qrUrlHd  = 'https://api.qrserver.com/v1/create-qr-code/?size=1000x1000&margin=16&ecc=M&format=png&data=' . $qrData; // download stampa
$qrUrlSvg = 'https://api.qrserver.com/v1/create-qr-code/?size=1000x1000&margin=16&ecc=M&format=svg&data=' . $qrData; // vettoriale
$isMenuEnabled = (bool)($tenant['menu_enabled'] ?? false);
?>

<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">
    <i class="bi bi-book me-1" style="color:var(--brand);"></i> Menù Digitale
</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1rem;">Gestisci il menù del tuo ristorante</p>

<!-- Tabs -->
<div class="settings-tabs">
    <?php foreach ($menuTabs as $tab): ?>
    <a href="<?= $tab['url'] ?>" class="settings-tab <?= $tab['key'] === 'piatti' ? 'active' : '' ?>">
        <i class="bi <?= $tab['icon'] ?>"></i> <?= $tab['label'] ?>
    </a>
    <?php endforeach; ?>
</div>

<?php if (!($canUseMenu ?? true)): ?>
<?php $lockedTitle = 'Il menu digitale'; include __DIR__ . '/../../partials/service-locked.php'; ?>
<?php else: ?>

<!-- KPI Cards -->
<div class="dh-stat-cards" style="grid-template-columns: repeat(4, 1fr);">
    <div class="dh-stat-card">
        <div class="dh-stat-icon green"><i class="bi bi-egg-fried"></i></div>
        <div>
            <div class="dh-stat-value"><?= (int)($stats['total'] ?? 0) ?></div>
            <div class="dh-stat-label">Voci totali</div>
        </div>
    </div>
    <div class="dh-stat-card">
        <div class="dh-stat-icon blue"><i class="bi bi-check-circle-fill"></i></div>
        <div>
            <div class="dh-stat-value"><?= (int)($stats['available'] ?? 0) ?></div>
            <div class="dh-stat-label">Disponibili</div>
        </div>
    </div>
    <div class="dh-stat-card">
        <div class="dh-stat-icon orange"><i class="bi bi-star-fill"></i></div>
        <div>
            <div class="dh-stat-value"><?= (int)($stats['specials'] ?? 0) ?></div>
            <div class="dh-stat-label">In evidenza</div>
        </div>
    </div>
    <div class="dh-stat-card">
        <div class="dh-stat-icon cyan"><i class="bi bi-folder-fill"></i></div>
        <div>
            <div class="dh-stat-value"><?= count($categories) ?></div>
            <div class="dh-stat-label">Categorie</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left: Items list -->
    <div class="col-lg-8">
        <!-- Action bar -->
        <div class="d-flex align-items-center justify-content-between" style="margin-bottom:.75rem;">
            <div style="font-weight:600; font-size:.95rem;">
                <i class="bi bi-list-ul me-1" style="color:var(--brand);"></i> Tutte le voci
            </div>
            <?php if (!empty($categories)): ?>
            <a href="<?= url('dashboard/menu/items/create') ?>" class="btn btn-sm btn-save">
                <i class="bi bi-plus-circle me-1"></i> Nuova voce
            </a>
            <?php endif; ?>
        </div>

        <?php if (empty($categories)): ?>
        <div class="card" style="padding:2.5rem; text-align:center;">
            <i class="bi bi-book" style="font-size:2.5rem; color:#dee2e6;"></i>
            <p style="color:#6c757d; margin-top:.75rem; font-size:.88rem; margin-bottom:.75rem;">
                Crea prima una categoria nella tab <strong>Categorie</strong> per iniziare.
            </p>
            <a href="<?= url('dashboard/menu/categories') ?>" class="btn btn-sm btn-save" style="display:inline-block; width:auto; margin:0 auto;">
                <i class="bi bi-folder me-1"></i> Vai a Categorie
            </a>
        </div>
        <?php else: ?>
            <?php $hasItems = false; ?>
            <div class="card" style="overflow:hidden;">
                <?php foreach ($hierarchy as $parent): ?>
                    <?php
                        // Collect items: parent's own + subcategory items
                        $parentItems = $itemsByCategory[(int)$parent['id']] ?? [];
                        $childrenWithItems = [];
                        foreach ($parent['children'] as $child) {
                            $childItems = $itemsByCategory[(int)$child['id']] ?? [];
                            if (!empty($childItems)) {
                                $childrenWithItems[] = ['cat' => $child, 'items' => $childItems];
                            }
                        }
                        $totalParentItems = count($parentItems);
                        foreach ($childrenWithItems as $cw) { $totalParentItems += count($cw['items']); }
                        if ($totalParentItems > 0) $hasItems = true;
                    ?>
                    <?php if ($totalParentItems > 0): ?>
                    <div class="dm-admin-cat-group-header">
                        <?= menu_icon($parent['icon'] ?? 'bi-list') ?> <?= e($parent['name']) ?>
                        <span class="dm-admin-cat-group-count"><?= $totalParentItems ?> <?= !empty($parent['is_wine']) ? ('etichett' . ($totalParentItems === 1 ? 'a' : 'e')) : ('piatt' . ($totalParentItems === 1 ? 'o' : 'i')) ?></span>
                    </div>

                    <?php // Items directly in parent (no subcategory) ?>
                    <?php foreach ($parentItems as $item): ?>
                    <?php include __DIR__ . '/_item_row.php'; ?>
                    <?php endforeach; ?>

                    <?php // Subcategory groups ?>
                    <?php foreach ($childrenWithItems as $cw): ?>
                    <div class="dm-admin-subcat-group-header">
                        <i class="bi bi-arrow-return-right" style="color:#ccc; font-size:.65rem;"></i>
                        <i class="bi <?= e($cw['cat']['icon'] ?? 'bi-list') ?>"></i> <?= e($cw['cat']['name']) ?>
                        <span class="dm-admin-cat-group-count"><?= count($cw['items']) ?></span>
                    </div>
                    <?php foreach ($cw['items'] as $item): ?>
                    <?php include __DIR__ . '/_item_row.php'; ?>
                    <?php endforeach; ?>
                    <?php endforeach; ?>

                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <?php if (!$hasItems): ?>
            <div class="card" style="padding:2.5rem; text-align:center;">
                <i class="bi bi-egg-fried" style="font-size:2.5rem; color:#dee2e6;"></i>
                <p style="color:#6c757d; margin-top:.75rem; font-size:.88rem; margin-bottom:.75rem;">Nessuna voce nel menù.</p>
                <a href="<?= url('dashboard/menu/items/create') ?>" class="btn btn-sm btn-save" style="display:inline-block; width:auto; margin:0 auto;">
                    <i class="bi bi-plus-circle me-1"></i> Aggiungi la prima voce
                </a>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Right: CTA sidebar -->
    <div class="col-lg-4">
        <!-- View Menu CTA -->
        <div class="card dm-admin-cta-card">
            <div class="dm-admin-cta-preview">
                <?php if (!empty($tenant['menu_hero_image'])): ?>
                <div class="dm-admin-cta-bg" style="background-image:url('<?= e($tenant['menu_hero_image']) ?>');"></div>
                <?php endif; ?>
                <div class="dm-admin-cta-overlay">
                    <?php if (!empty($tenant['logo_url'])): ?>
                    <img src="<?= e($tenant['logo_url']) ?>" alt="" class="dm-admin-cta-logo">
                    <?php endif; ?>
                    <div class="dm-admin-cta-name"><?= e($tenant['name']) ?></div>
                    <?php if (!empty($tenant['menu_tagline'])): ?>
                    <div class="dm-admin-cta-tagline"><?= e($tenant['menu_tagline']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div style="padding:1rem;">
                <?php if ($isMenuEnabled): ?>
                <a href="<?= e($menuUrl) ?>" target="_blank" class="btn btn-sm btn-save w-100" style="margin-bottom:.65rem;">
                    <i class="bi bi-eye me-1"></i> Vedi menù pubblico
                </a>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" data-copy="<?= e($menuUrl) ?>">
                        <i class="bi bi-clipboard"></i> Copia
                    </button>
                    <a href="<?= e($qrUrlHd) ?>" download="menu-qr-<?= e($tenant['slug']) ?>.png" class="btn btn-sm btn-outline-secondary flex-fill" title="QR PNG alta risoluzione">
                        <i class="bi bi-qr-code"></i> PNG
                    </a>
                    <a href="<?= e($qrUrlSvg) ?>" download="menu-qr-<?= e($tenant['slug']) ?>.svg" class="btn btn-sm btn-outline-secondary flex-fill" title="QR vettoriale (grande formato)">
                        SVG
                    </a>
                </div>
                <div style="font-size:.72rem; color:#adb5bd; margin-top:.5rem; text-align:center; word-break:break-all;">
                    <?= e($menuUrl) ?>
                </div>
                <?php else: ?>
                <div style="text-align:center; padding:.5rem 0;">
                    <i class="bi bi-eye-slash" style="font-size:1.5rem; color:#dee2e6;"></i>
                    <p style="font-size:.82rem; color:#6c757d; margin:.5rem 0;">Menù pubblico disattivato</p>
                    <a href="<?= url('dashboard/menu/appearance') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-palette me-1"></i> Attiva in Aspetto
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($isMenuEnabled): ?>
        <!-- QR Preview -->
        <div class="card" style="padding:1rem; margin-top:.75rem; text-align:center;">
            <img src="<?= e($qrUrl) ?>" alt="QR Code" style="width:120px; height:120px; border-radius:6px; margin:0 auto .5rem;">
            <div style="font-size:.72rem; color:#6c757d;">Scansiona per vedere il menù</div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
(function() {
    document.querySelectorAll('[data-confirm]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm)) { e.preventDefault(); }
        });
    });
    document.querySelectorAll('[data-copy]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var self = this;
            navigator.clipboard.writeText(self.dataset.copy).then(function() {
                self.innerHTML = '<i class="bi bi-check"></i> Copiato!';
                setTimeout(function() { self.innerHTML = '<i class="bi bi-clipboard"></i> Copia link'; }, 2000);
            });
        });
    });
})();
</script>

<?php endif; ?>
