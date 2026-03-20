<?php
$menuTabs = [
    ['url' => url('dashboard/menu'),             'icon' => 'bi-egg-fried', 'label' => 'Piatti',     'key' => 'piatti'],
    ['url' => url('dashboard/menu/categories'),   'icon' => 'bi-folder',    'label' => 'Categorie',  'key' => 'categorie'],
    ['url' => url('dashboard/menu/appearance'),   'icon' => 'bi-palette',   'label' => 'Aspetto',    'key' => 'aspetto'],
];
$menuUrl = url($tenant['slug'] . '/menu');
$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($menuUrl);
$isMenuEnabled = (bool)($tenant['menu_enabled'] ?? false);
?>

<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">
    <i class="bi bi-book me-1" style="color:var(--brand);"></i> Menu Digitale
</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1rem;">Gestisci il menu del tuo ristorante</p>

<!-- Tabs -->
<div class="settings-tabs">
    <?php foreach ($menuTabs as $tab): ?>
    <a href="<?= $tab['url'] ?>" class="settings-tab <?= $tab['key'] === 'aspetto' ? 'active' : '' ?>">
        <i class="bi <?= $tab['icon'] ?>"></i> <?= $tab['label'] ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Menu Toggle + Public Link -->
<div class="card" style="padding:1rem; margin-bottom:1.25rem;">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <form method="POST" action="<?= url('dashboard/menu/toggle') ?>" class="d-inline">
                <?= csrf_field() ?>
                <button type="submit" class="promo-toggle-btn" title="<?= $isMenuEnabled ? 'Disattiva menu pubblico' : 'Attiva menu pubblico' ?>">
                    <div class="promo-toggle <?= $isMenuEnabled ? 'promo-toggle-on' : '' ?>">
                        <div class="promo-toggle-knob"></div>
                    </div>
                </button>
            </form>
            <div>
                <div style="font-weight:600; font-size:.88rem;"><?= $isMenuEnabled ? 'Menu pubblico attivo' : 'Menu pubblico disattivato' ?></div>
                <?php if ($isMenuEnabled): ?>
                <a href="<?= e($menuUrl) ?>" target="_blank" style="font-size:.78rem; color:var(--brand);">
                    <?= e($menuUrl) ?> <i class="bi bi-box-arrow-up-right"></i>
                </a>
                <?php else: ?>
                <span style="font-size:.78rem; color:#adb5bd;">Attiva per rendere visibile il menu ai clienti</span>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($isMenuEnabled): ?>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-copy="<?= e($menuUrl) ?>" title="Copia link">
                <i class="bi bi-clipboard"></i> Copia link
            </button>
            <a href="<?= e($qrUrl) ?>" download="menu-qr-<?= e($tenant['slug']) ?>.png" class="btn btn-sm btn-outline-secondary" title="Scarica QR Code">
                <i class="bi bi-qr-code"></i> QR Code
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <!-- Left: Config form -->
    <div class="col-lg-7">
        <div class="card" style="padding:1.25rem;">
            <div style="font-weight:600; font-size:.95rem; margin-bottom:1rem;">
                <i class="bi bi-palette me-1" style="color:var(--brand);"></i> Personalizzazione
            </div>
            <form method="POST" action="<?= url('dashboard/menu/settings') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Tagline / Descrizione breve</label>
                    <input type="text" name="menu_tagline" class="form-control form-control-sm" maxlength="200"
                           value="<?= e($tenant['menu_tagline'] ?? '') ?>" placeholder="es. Cucina italiana d'autore dal 1987">
                    <div style="font-size:.72rem; color:#6c757d; margin-top:.2rem;">Appare sotto il nome del ristorante nell'header</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Orari di apertura</label>
                    <input type="text" name="opening_hours" class="form-control form-control-sm" maxlength="500"
                           value="<?= e($tenant['opening_hours'] ?? '') ?>" placeholder="es. 12:00 – 15:00 / 19:00 – 23:00">
                    <div style="font-size:.72rem; color:#6c757d; margin-top:.2rem;">Mostrato nell'header della pagina menu</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Immagine hero (sfondo header)</label>
                    <?php if (!empty($tenant['menu_hero_image'])): ?>
                    <div style="margin-bottom:.5rem; background:#f0f2f5; border-radius:8px; padding:.5rem; display:flex; align-items:center; gap:.75rem;">
                        <img src="<?= e($tenant['menu_hero_image']) ?>" alt="" style="width:120px; height:50px; border-radius:6px; object-fit:cover;">
                        <div>
                            <label class="d-flex align-items-center gap-1" style="font-size:.72rem; color:#dc3545; cursor:pointer;">
                                <input type="checkbox" name="remove_hero_image" value="1"> Rimuovi immagine
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="menu_hero_image" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                    <div style="font-size:.72rem; color:#6c757d; margin-top:.2rem;">JPG, PNG o WebP. Max 5MB. Consigliata: 1200x400px. Senza immagine viene usato uno sfondo scuro.</div>
                </div>
                <button type="submit" class="btn btn-sm btn-save"><i class="bi bi-check-lg me-1"></i> Salva impostazioni</button>
            </form>
        </div>
    </div>

    <!-- Right: QR + Preview -->
    <div class="col-lg-5">
        <?php if ($isMenuEnabled): ?>
        <div class="card" style="padding:1.25rem; margin-bottom:1rem;">
            <div style="font-weight:600; font-size:.95rem; margin-bottom:1rem;">
                <i class="bi bi-qr-code me-1" style="color:var(--brand);"></i> QR Code
            </div>
            <div style="text-align:center; padding:.5rem;">
                <img src="<?= e($qrUrl) ?>" alt="QR Code Menu" style="width:160px; height:160px; border-radius:8px; margin-bottom:.75rem;">
                <p style="font-size:.75rem; color:#6c757d; margin-bottom:.75rem;">Stampa o condividi questo QR code per permettere ai clienti di consultare il menu dal tavolo.</p>
                <a href="<?= e($qrUrl) ?>" download="menu-qr-<?= e($tenant['slug']) ?>.png" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-download me-1"></i> Scarica QR Code
                </a>
            </div>
        </div>
        <?php endif; ?>

        <div class="card" style="padding:1.25rem;">
            <div style="font-weight:600; font-size:.95rem; margin-bottom:1rem;">
                <i class="bi bi-eye me-1" style="color:var(--brand);"></i> Anteprima header
            </div>
            <div style="text-align:center;">
                <?php $hasHero = !empty($tenant['menu_hero_image']); ?>
                <div style="width:100%; height:180px; border-radius:10px; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#fff; padding:1rem; position:relative; overflow:hidden;
                    <?php if ($hasHero): ?>background:url('<?= e($tenant['menu_hero_image']) ?>') center/cover;<?php else: ?>background:linear-gradient(135deg, #1a1d23 0%, #2d3748 100%);<?php endif; ?>">
                    <?php if ($hasHero): ?>
                    <div style="position:absolute; inset:0; background:rgba(0,0,0,.65);"></div>
                    <?php endif; ?>
                    <div style="position:relative; z-index:2;">
                        <?php if (!empty($tenant['logo_url'])): ?>
                        <img src="<?= e($tenant['logo_url']) ?>" alt="" style="width:40px; height:40px; border-radius:10px; object-fit:cover; margin-bottom:.5rem; border:1px solid rgba(255,255,255,.15);">
                        <?php endif; ?>
                        <div style="font-weight:700; font-size:.9rem;"><?= e($tenant['name']) ?></div>
                        <?php if (!empty($tenant['menu_tagline'])): ?>
                        <div style="font-size:.7rem; color:rgba(255,255,255,.5); font-style:italic;"><?= e($tenant['menu_tagline']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($tenant['opening_hours'])): ?>
                        <div style="font-size:.62rem; color:rgba(255,255,255,.35); margin-top:.35rem;">
                            <i class="bi bi-clock"></i> <?= e($tenant['opening_hours']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($isMenuEnabled): ?>
                <a href="<?= e($menuUrl) ?>" target="_blank" style="font-size:.78rem; color:var(--brand); display:block; margin-top:.75rem;">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Apri pagina menu
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
(function() {
    // Copy link
    document.querySelectorAll('[data-copy]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            navigator.clipboard.writeText(this.dataset.copy).then(function() {
                btn.innerHTML = '<i class="bi bi-check"></i> Copiato!';
                setTimeout(function() { btn.innerHTML = '<i class="bi bi-clipboard"></i> Copia link'; }, 2000);
            });
        });
    });
})();
</script>
