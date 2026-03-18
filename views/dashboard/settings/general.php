<?php
$settingsTabs = [
    ['url' => url('dashboard/settings'),                'icon' => 'bi-gear',  'label' => 'Generali',         'key' => 'settings'],
    ['url' => url('dashboard/settings/slots'),          'icon' => 'bi-clock', 'label' => 'Orari e Coperti',  'key' => 'slots'],
    ['url' => url('dashboard/settings/meal-categories'),'icon' => 'bi-tags',       'label' => 'Categorie Pasto',  'key' => 'meal-categories'],
    ['url' => url('dashboard/settings/closures'),       'icon' => 'bi-calendar-x', 'label' => 'Chiusure',         'key' => 'closures'],
    ['url' => url('dashboard/settings/deposit'),        'icon' => 'bi-cash',       'label' => 'Caparra',          'key' => 'deposit'],
    ['url' => url('dashboard/settings/domain'),         'icon' => 'bi-globe',      'label' => 'Dominio',          'key' => 'domain'],
];
$segOcc = (int)($tenant['segment_occasionale'] ?? 2);
$segAbi = (int)($tenant['segment_abituale'] ?? 4);
$segVip = (int)($tenant['segment_vip'] ?? 10);
$bookingUrl = url($tenant['slug']);
$embedUrl = url($tenant['slug'] . '?embed=1');
?>

<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">Impostazioni</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1rem;">Configura il tuo ristorante</p>

<!-- Settings tabs -->
<div class="settings-tabs">
    <?php foreach ($settingsTabs as $tab): ?>
    <a href="<?= $tab['url'] ?>" class="settings-tab <?= $tab['key'] === 'settings' ? 'active' : '' ?>">
        <i class="bi <?= $tab['icon'] ?>"></i> <?= $tab['label'] ?>
    </a>
    <?php endforeach; ?>
</div>

<form method="POST" action="<?= url('dashboard/settings') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="row g-4">
        <!-- Left: Form -->
        <div class="col-lg-7">

            <!-- Ristorante -->
            <div class="card section-card">
                <div class="section-header">
                    <div class="section-icon" style="background:var(--brand);"><i class="bi bi-shop"></i></div>
                    <div>
                        <div class="section-title">Ristorante</div>
                        <div class="section-subtitle">Informazioni di base del locale</div>
                    </div>
                </div>
                <div class="form-body">
                    <div class="row g-3">
                        <div class="col-md-6 field-row">
                            <label class="field-label">Nome ristorante *</label>
                            <input type="text" class="field-input" name="name" value="<?= e($tenant['name']) ?>" required>
                        </div>
                        <div class="col-md-6 field-row">
                            <label class="field-label">Email *</label>
                            <input type="email" class="field-input" name="email" value="<?= e($tenant['email']) ?>" required>
                        </div>
                        <div class="col-md-6 field-row">
                            <label class="field-label">Telefono</label>
                            <input type="text" class="field-input" name="phone" value="<?= e($tenant['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 field-row">
                            <label class="field-label">Indirizzo</label>
                            <input type="text" class="field-input" name="address" value="<?= e($tenant['address'] ?? '') ?>">
                        </div>
                        <div class="col-12 field-row">
                            <label class="field-label">Logo</label>
                            <div class="field-hint" style="margin-bottom:.5rem;">Visibile solo nella pagina di prenotazione (widget)</div>
                            <div class="logo-upload-area">
                                <?php if (!empty($tenant['logo_url'])): ?>
                                <div class="logo-preview" id="logo-preview">
                                    <img src="<?= e($tenant['logo_url']) ?>" alt="Logo" class="logo-preview-img">
                                    <div class="logo-preview-actions">
                                        <label class="logo-change-btn" title="Cambia logo">
                                            <i class="bi bi-pencil"></i>
                                            <input type="file" name="logo" accept="image/jpeg,image/png,image/webp,image/svg+xml" class="d-none" id="logo-input">
                                        </label>
                                        <button type="button" class="logo-remove-btn" title="Rimuovi logo" id="logo-remove-btn">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" name="remove_logo" id="remove-logo-input" value="">
                                <?php else: ?>
                                <label class="logo-dropzone" id="logo-dropzone">
                                    <i class="bi bi-image" style="font-size:1.5rem;color:#adb5bd;"></i>
                                    <span class="logo-dropzone-text">Clicca per caricare il logo</span>
                                    <span class="logo-dropzone-hint">JPG, PNG, WebP o SVG &middot; Max 2 MB</span>
                                    <input type="file" name="logo" accept="image/jpeg,image/png,image/webp,image/svg+xml" class="d-none" id="logo-input">
                                </label>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Prenotazioni -->
            <div class="card section-card">
                <div class="section-header">
                    <div class="section-icon" style="background:#5C6BC0;"><i class="bi bi-calendar-check"></i></div>
                    <div>
                        <div class="section-title">Prenotazioni</div>
                        <div class="section-subtitle">Parametri per la gestione degli slot</div>
                    </div>
                </div>
                <div class="form-body">
                    <div class="row g-3">
                        <div class="col-12 field-row">
                            <label class="field-label">Modalità conferma</label>
                            <div style="display:flex;gap:.5rem;margin-top:.25rem;">
                                <label style="display:flex;align-items:center;gap:.4rem;padding:.45rem .85rem;border:1.5px solid <?= ($tenant['confirmation_mode'] ?? 'auto') === 'auto' ? 'var(--brand)' : '#dee2e6' ?>;border-radius:8px;cursor:pointer;font-size:.82rem;font-weight:500;background:<?= ($tenant['confirmation_mode'] ?? 'auto') === 'auto' ? 'var(--brand-light)' : '#fff' ?>;">
                                    <input type="radio" name="confirmation_mode" value="auto" <?= ($tenant['confirmation_mode'] ?? 'auto') === 'auto' ? 'checked' : '' ?> style="accent-color:var(--brand);">
                                    <i class="bi bi-check-circle" style="color:var(--brand);"></i> Automatica
                                </label>
                                <label style="display:flex;align-items:center;gap:.4rem;padding:.45rem .85rem;border:1.5px solid <?= ($tenant['confirmation_mode'] ?? 'auto') === 'manual' ? '#E65100' : '#dee2e6' ?>;border-radius:8px;cursor:pointer;font-size:.82rem;font-weight:500;background:<?= ($tenant['confirmation_mode'] ?? 'auto') === 'manual' ? '#FFF3E0' : '#fff' ?>;">
                                    <input type="radio" name="confirmation_mode" value="manual" <?= ($tenant['confirmation_mode'] ?? 'auto') === 'manual' ? 'checked' : '' ?> style="accent-color:#E65100;">
                                    <i class="bi bi-hand-index" style="color:#E65100;"></i> Manuale
                                </label>
                            </div>
                            <div class="field-hint">
                                <strong>Automatica</strong>: le prenotazioni dal widget vengono confermate subito.
                                <strong>Manuale</strong>: restano "in attesa" finché non le approvi dalla dashboard.
                            </div>
                        </div>
                        <div class="col-md-6 field-row">
                            <label class="field-label">Durata tavolo</label>
                            <div class="input-suffix">
                                <input type="number" class="field-input" name="table_duration" value="<?= (int)$tenant['table_duration'] ?>" min="15" step="15">
                                <span class="suffix">minuti</span>
                            </div>
                            <div class="field-hint">Quanto tempo rimane occupato un tavolo dopo la prenotazione</div>
                        </div>
                        <div class="col-md-6 field-row">
                            <label class="field-label">Step orari</label>
                            <div class="input-suffix">
                                <input type="number" class="field-input" name="time_step" value="<?= (int)$tenant['time_step'] ?>" min="5" step="5">
                                <span class="suffix">minuti</span>
                            </div>
                            <div class="field-hint">Intervallo tra gli slot disponibili (15, 30, 60...)</div>
                        </div>
                        <div class="col-12 field-row">
                            <label class="field-label">Politica di cancellazione</label>
                            <textarea class="field-input field-textarea" name="cancellation_policy" rows="3"><?= e($tenant['cancellation_policy'] ?? '') ?></textarea>
                            <div class="field-hint">Visibile al cliente nella pagina di prenotazione</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Segmento Cliente -->
            <div class="card section-card">
                <div class="section-header">
                    <div class="section-icon" style="background:#E65100;"><i class="bi bi-people"></i></div>
                    <div>
                        <div class="section-title">Segmento Cliente</div>
                        <div class="section-subtitle">Soglie per la classificazione automatica dei clienti</div>
                    </div>
                </div>
                <div class="form-body">
                    <div class="row g-3">
                        <div class="col-md-4 field-row">
                            <label class="field-label">Occasionale (da)</label>
                            <div class="input-suffix">
                                <input type="number" class="field-input" name="segment_occasionale" value="<?= $segOcc ?>" min="1" max="100">
                                <span class="suffix">pren.</span>
                            </div>
                        </div>
                        <div class="col-md-4 field-row">
                            <label class="field-label">Abituale (da)</label>
                            <div class="input-suffix">
                                <input type="number" class="field-input" name="segment_abituale" value="<?= $segAbi ?>" min="2" max="200">
                                <span class="suffix">pren.</span>
                            </div>
                        </div>
                        <div class="col-md-4 field-row">
                            <label class="field-label">VIP (da)</label>
                            <div class="input-suffix">
                                <input type="number" class="field-input" name="segment_vip" value="<?= $segVip ?>" min="3" max="500">
                                <span class="suffix">pren.</span>
                            </div>
                        </div>
                    </div>
                    <div class="seg-preview">
                        <div class="seg-preview-item">
                            <span class="seg-preview-dot" style="background:#6c757d;"></span>
                            Nuovo <span style="color:#adb5bd;">&lt; <?= $segOcc ?></span>
                        </div>
                        <div class="seg-preview-item">
                            <span class="seg-preview-dot" style="background:#0dcaf0;"></span>
                            Occasionale <span style="color:#adb5bd;"><?= $segOcc ?> &ndash; <?= $segAbi - 1 ?></span>
                        </div>
                        <div class="seg-preview-item">
                            <span class="seg-preview-dot" style="background:#198754;"></span>
                            Abituale <span style="color:#adb5bd;"><?= $segAbi ?> &ndash; <?= $segVip - 1 ?></span>
                        </div>
                        <div class="seg-preview-item">
                            <span class="seg-preview-dot" style="background:#ffc107;"></span>
                            VIP <span style="color:#adb5bd;"><?= $segVip ?>+</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save bar -->
            <div class="save-bar">
                <span class="save-hint"><i class="bi bi-info-circle me-1"></i>Le modifiche saranno visibili immediatamente</span>
                <button type="submit" class="btn-save"><i class="bi bi-check-circle me-1"></i> Salva impostazioni</button>
            </div>

        </div>

        <!-- Right: Link & Embed -->
        <div class="col-lg-5">

            <div class="card section-card">
                <div class="section-header">
                    <div class="section-icon" style="background:#1565C0;"><i class="bi bi-link-45deg"></i></div>
                    <div>
                        <div class="section-title">Link Prenotazione</div>
                        <div class="section-subtitle">Condividi con i clienti</div>
                    </div>
                </div>
                <div class="form-body">
                    <div class="link-label">Pagina hosted</div>
                    <div class="link-box">
                        <span class="link-url" id="hosted-url"><?= e($bookingUrl) ?></span>
                        <button type="button" class="link-copy" data-copy-target="hosted-url"><i class="bi bi-clipboard me-1"></i>Copia</button>
                    </div>

                    <div class="link-label" style="margin-top:1rem;">Codice Embed (iframe)</div>
                    <div class="embed-code">
                        <button type="button" class="embed-copy" data-copy-text="&lt;iframe src=&quot;<?= e($embedUrl) ?>&quot; width=&quot;100%&quot; height=&quot;600&quot; frameborder=&quot;0&quot;&gt;&lt;/iframe&gt;"><i class="bi bi-clipboard me-1"></i>Copia</button>
                        &lt;iframe src="<?= e($embedUrl) ?>" width="100%" height="600" frameborder="0"&gt;&lt;/iframe&gt;
                    </div>
                </div>
            </div>

            <!-- Quick help -->
            <div class="card section-card">
                <div class="tip-card">
                    <i class="bi bi-lightbulb" style="color:#FFC107;font-size:1.1rem;margin-top:.1rem;"></i>
                    <div>
                        <div class="tip-title">Suggerimento</div>
                        <div class="tip-text">
                            La <strong>durata tavolo</strong> determina per quanto tempo un tavolo resta occupato dopo la prenotazione. Se imposti 90 min, una prenotazione delle 20:00 occupa il tavolo fino alle 21:30.
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</form>

<script nonce="<?= csp_nonce() ?>">
// Copy link buttons
document.querySelectorAll('[data-copy-target]').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        var targetId = this.dataset.copyTarget;
        var el = document.getElementById(targetId);
        if (el) {
            navigator.clipboard.writeText(el.textContent.trim()).then(function() {
                var orig = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Copiato!';
                setTimeout(function() { btn.innerHTML = orig; }, 1500);
            });
        }
    });
});
document.querySelectorAll('[data-copy-text]').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        navigator.clipboard.writeText(this.dataset.copyText).then(function() {
            var orig = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Copiato!';
            setTimeout(function() { btn.innerHTML = orig; }, 1500);
        });
    });
});

// Logo upload preview
(function() {
    var input = document.getElementById('logo-input');
    var removeBtn = document.getElementById('logo-remove-btn');
    var removeInput = document.getElementById('remove-logo-input');

    if (input) {
        input.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                var file = this.files[0];
                if (file.size > 2 * 1024 * 1024) {
                    alert('Il file supera il limite di 2 MB.');
                    this.value = '';
                    return;
                }
                // Show preview
                var reader = new FileReader();
                reader.onload = function(e) {
                    var area = document.querySelector('.logo-upload-area');
                    area.innerHTML = '<div class="logo-preview"><img src="' + e.target.result + '" alt="Preview" class="logo-preview-img"><div class="logo-dropzone-hint" style="margin-top:.5rem;">Salva per applicare</div></div>';
                    // Re-append the input (it was removed by innerHTML)
                    var newInput = document.createElement('input');
                    newInput.type = 'file'; newInput.name = 'logo'; newInput.id = 'logo-input';
                    newInput.accept = 'image/jpeg,image/png,image/webp,image/svg+xml';
                    newInput.className = 'd-none';
                    newInput.files = input.files;
                    area.appendChild(newInput);
                };
                reader.readAsDataURL(file);
            }
        });
    }

    if (removeBtn) {
        removeBtn.addEventListener('click', function() {
            removeInput.value = '1';
            var preview = document.getElementById('logo-preview');
            if (preview) {
                preview.innerHTML = '<div style="padding:1rem;text-align:center;color:#dc3545;font-size:.85rem;"><i class="bi bi-trash me-1"></i>Logo rimosso. Salva per applicare.</div>';
            }
        });
    }
})();
</script>