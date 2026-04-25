<?php
$settingsTabs = [
    ['url' => url('dashboard/settings'),                'icon' => 'bi-gear',         'label' => 'Generali',         'key' => 'settings'],
    ['url' => url('dashboard/settings/slots'),          'icon' => 'bi-clock',        'label' => 'Orari e Coperti',  'key' => 'slots'],
    ['url' => url('dashboard/settings/meal-categories'),'icon' => 'bi-tags',         'label' => 'Categorie Pasto',  'key' => 'meal-categories'],
    ['url' => url('dashboard/settings/closures'),       'icon' => 'bi-calendar-x',   'label' => 'Chiusure',         'key' => 'closures'],
    ['url' => url('dashboard/settings/promotions'),     'icon' => 'bi-percent',      'label' => 'Promozioni',       'key' => 'promotions'],
    ['url' => url('dashboard/settings/notifications'),  'icon' => 'bi-bell',         'label' => 'Notifiche',        'key' => 'settings-notifications'],
    ['url' => url('dashboard/settings/deposit'),        'icon' => 'bi-cash',         'label' => 'Caparra',          'key' => 'deposit'],
    ['url' => url('dashboard/settings/ordering'),       'icon' => 'bi-bag-check',    'label' => 'Ordini online',    'key' => 'settings-ordering'],
    ['url' => url('dashboard/settings/reviews'),        'icon' => 'bi-star',         'label' => 'Recensioni',       'key' => 'settings-reviews'],
    ['url' => url('dashboard/settings/hub'),            'icon' => 'bi-grid-3x3-gap', 'label' => 'Vetrina Digitale', 'key' => 'settings-hub'],
    ['url' => url('dashboard/settings/domain'),         'icon' => 'bi-globe',        'label' => 'Dominio',          'key' => 'domain'],
];

$slug = $tenant['slug'] ?? '';
$publicHubUrl = url($slug . '/hub');
$enabled = !empty($settings['enabled']);
?>

<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">Impostazioni</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1rem;">Configura il tuo ristorante</p>

<!-- Settings tabs -->
<div class="settings-tabs-wrap"><div class="scroll-hint"><i class="bi bi-arrows"></i></div><div class="settings-tabs">
    <?php foreach ($settingsTabs as $tab): ?>
    <a href="<?= $tab['url'] ?>" class="settings-tab <?= $tab['key'] === 'settings-hub' ? 'active' : '' ?>">
        <i class="bi <?= $tab['icon'] ?>"></i> <span class="tab-label"><?= $tab['label'] ?></span>
    </a>
    <?php endforeach; ?>
</div></div>

<?php if (!($canUseHub ?? true)): ?>
<?php $lockedTitle = 'La Vetrina Digitale'; include __DIR__ . '/../../partials/service-locked.php'; ?>
<?php else: ?>

<div class="info-banner">
    <div class="info-banner-icon" style="background:var(--brand-light); color:var(--brand);">
        <i class="bi bi-info-circle"></i>
    </div>
    <div class="info-banner-text">
        La <strong>Vetrina Digitale</strong> &egrave; una pagina pubblica con tutte le azioni che i tuoi clienti possono fare: prenotare, vedere il menu, recensire, contattarti. Stampa il QR code e mettilo al tavolo, in vetrina, sui volantini.
    </div>
</div>

<form method="POST" action="<?= url('dashboard/settings/hub') ?>" id="hub-form" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="row g-3">

        <!-- LEFT COLUMN -->
        <div class="col-lg-7">

            <!-- Master enable toggle -->
            <div class="card hub-card hub-master-card <?= $enabled ? 'enabled' : 'disabled' ?>" id="hub-master-card">
                <div class="hub-toggle-row">
                    <div style="flex:1;">
                        <div class="hub-toggle-label"><i class="bi bi-grid-3x3-gap me-1" style="color:var(--brand);"></i> Attiva la Vetrina Digitale</div>
                        <div class="hub-toggle-sub">Disattivata, chi visita il link pubblico vede una pagina con CTA "Prenota un tavolo".</div>
                    </div>
                    <label class="hub-switch">
                        <input type="checkbox" name="enabled" id="hub-enabled-toggle" value="1" <?= $enabled ? 'checked' : '' ?>>
                        <span class="hub-switch-slider"></span>
                    </label>
                </div>
            </div>

            <!-- Config block (greyed when hub disabled) -->
            <div class="hub-config-block <?= $enabled ? '' : 'hub-greyed' ?>" id="hub-config-block">

            <!-- Identità -->
            <div class="card hub-card">
                <div class="hub-card-head">
                    <h5><i class="bi bi-person-badge me-1" style="color:var(--brand);"></i> Identit&agrave;</h5>
                </div>
                <div class="hub-field">
                    <label>Sottotitolo (sotto il nome del ristorante)</label>
                    <input type="text" name="subtitle" value="<?= e($settings['subtitle'] ?? '') ?>" placeholder="Es. Trattoria moderna · Roma" maxlength="150">
                </div>
                <div class="hub-grid-2">
                    <!-- Logo uploader -->
                    <div class="hub-field" style="margin-bottom:0;">
                        <label>Logo</label>
                        <div class="hub-uploader" data-field="logo">
                            <?php if (!empty($settings['logo_url'])): ?>
                            <div class="hub-uploader-preview">
                                <img src="<?= e($settings['logo_url']) ?>" alt="Logo" class="hub-uploader-thumb logo">
                                <button type="button" class="hub-uploader-remove" data-target="logo">
                                    <i class="bi bi-trash"></i> Rimuovi
                                </button>
                            </div>
                            <?php endif; ?>
                            <label class="hub-uploader-drop" for="hub-logo-file">
                                <i class="bi bi-cloud-upload"></i>
                                <span class="hub-uploader-text">Trascina qui o <strong>sfoglia</strong></span>
                                <span class="hub-uploader-meta">PNG/JPG/WebP · 200x200 min · max 2 MB</span>
                            </label>
                            <input type="file" id="hub-logo-file" name="logo" accept="image/jpeg,image/png,image/webp" hidden>
                            <input type="checkbox" name="logo_remove" value="1" id="hub-logo-remove" hidden>
                        </div>
                    </div>
                    <!-- Cover uploader -->
                    <div class="hub-field" style="margin-bottom:0;">
                        <label>Copertina</label>
                        <div class="hub-uploader" data-field="cover">
                            <?php if (!empty($settings['cover_url'])): ?>
                            <div class="hub-uploader-preview">
                                <img src="<?= e($settings['cover_url']) ?>" alt="Cover" class="hub-uploader-thumb cover">
                                <button type="button" class="hub-uploader-remove" data-target="cover">
                                    <i class="bi bi-trash"></i> Rimuovi
                                </button>
                            </div>
                            <?php endif; ?>
                            <label class="hub-uploader-drop" for="hub-cover-file">
                                <i class="bi bi-image"></i>
                                <span class="hub-uploader-text">Trascina qui o <strong>sfoglia</strong></span>
                                <span class="hub-uploader-meta">1200x400 consigliato · max 2 MB · se vuoto usa gradient della palette</span>
                            </label>
                            <input type="file" id="hub-cover-file" name="cover" accept="image/jpeg,image/png,image/webp" hidden>
                            <input type="checkbox" name="cover_remove" value="1" id="hub-cover-remove" hidden>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stili e colori -->
            <div class="card hub-card">
                <div class="hub-card-head">
                    <h5><i class="bi bi-droplet me-1" style="color:var(--brand);"></i> Stili e colori</h5>
                </div>
                <div class="hub-field">
                    <label>Palette (6 temi curati)</label>
                    <div class="hub-palette-grid">
                        <?php foreach ($palettes as $pkey => $palette): ?>
                        <label class="hub-palette-card <?= ($settings['palette'] ?? 'evulery_green') === $pkey ? 'selected' : '' ?>">
                            <input type="radio" name="palette" value="<?= e($pkey) ?>" <?= ($settings['palette'] ?? 'evulery_green') === $pkey ? 'checked' : '' ?>>
                            <div class="hub-palette-swatch">
                                <div style="background:<?= e($palette['primary']) ?>;"></div>
                                <div style="background:<?= e($palette['accent']) ?>;"></div>
                                <div style="background:<?= e($palette['dark']) ?>;"></div>
                            </div>
                            <div class="hub-palette-name"><?= e($palette['name']) ?></div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if ($isEnterprise):
                    $customEnabled = !empty($settings['custom_colors_enabled']);
                ?>
                <div class="hub-enterprise-section">
                    <span class="hub-enterprise-badge"><i class="bi bi-stars"></i> Enterprise</span>

                    <!-- Master toggle: attiva colori personalizzati (esclude la palette preset) -->
                    <div class="hub-toggle-row" style="margin-bottom:.85rem;">
                        <div style="flex:1;">
                            <div class="hub-toggle-label"><i class="bi bi-palette me-1"></i> Attiva colori personalizzati</div>
                            <div class="hub-toggle-sub">Se attivo, la Vetrina usa i tuoi colori e ignora la palette preset selezionata sopra.</div>
                        </div>
                        <label class="hub-switch">
                            <input type="checkbox" name="custom_colors_enabled" value="1" id="hub-custom-colors-toggle" <?= $customEnabled ? 'checked' : '' ?>>
                            <span class="hub-switch-slider"></span>
                        </label>
                    </div>

                    <div class="hub-custom-colors-block <?= $customEnabled ? '' : 'is-disabled' ?>" id="hub-custom-colors-block">
                        <div class="hub-grid-2">
                            <div class="hub-field">
                                <label>Primario <span style="color:#adb5bd; font-weight:normal;">(CTA, icone)</span></label>
                                <div class="hub-color-row">
                                    <input type="color" name="custom_primary" value="<?= e($settings['custom_primary'] ?? '#00844A') ?>">
                                    <input type="text" value="<?= e($settings['custom_primary'] ?? '#00844A') ?>" data-color-text>
                                </div>
                            </div>
                            <div class="hub-field">
                                <label>Scuro <span style="color:#adb5bd; font-weight:normal;">(gradiente cover)</span></label>
                                <div class="hub-color-row">
                                    <input type="color" name="custom_dark" value="<?= e($settings['custom_dark'] ?? '#006837') ?>">
                                    <input type="text" value="<?= e($settings['custom_dark'] ?? '#006837') ?>" data-color-text>
                                </div>
                            </div>
                            <div class="hub-field">
                                <label>Accento <span style="color:#adb5bd; font-weight:normal;">(hover, badge)</span></label>
                                <div class="hub-color-row">
                                    <input type="color" name="custom_accent" value="<?= e($settings['custom_accent'] ?? '#E8F5E9') ?>">
                                    <input type="text" value="<?= e($settings['custom_accent'] ?? '#E8F5E9') ?>" data-color-text>
                                </div>
                            </div>
                            <div class="hub-field">
                                <label>Sfondo</label>
                                <div class="hub-color-row">
                                    <input type="color" name="custom_bg" value="<?= e($settings['custom_bg'] ?? '#FFFFFF') ?>">
                                    <input type="text" value="<?= e($settings['custom_bg'] ?? '#FFFFFF') ?>" data-color-text>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="hub-field" style="margin-top:.85rem;">
                        <label>Font del titolo ristorante</label>
                        <select name="custom_font" class="form-select form-select-sm">
                            <option value="">— predefinito —</option>
                            <?php foreach ($fonts as $fkey => $fname): ?>
                            <option value="<?= e($fkey) ?>" <?= ($settings['custom_font'] ?? '') === $fkey ? 'selected' : '' ?>><?= e($fname) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="hub-toggle-row" style="margin-top:.75rem; padding-top:.75rem; border-top:1px solid #FFE082;">
                        <div style="flex:1;">
                            <div class="hub-toggle-label"><i class="bi bi-eye-slash me-1"></i> White-label</div>
                            <div class="hub-toggle-sub">Rimuove "Powered by Evulery" dal footer della Vetrina</div>
                        </div>
                        <label class="hub-switch">
                            <input type="checkbox" name="hide_branding" value="1" <?= !empty($settings['hide_branding']) ? 'checked' : '' ?>>
                            <span class="hub-switch-slider"></span>
                        </label>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Azioni disponibili -->
            <div class="card hub-card">
                <div class="hub-card-head">
                    <h5><i class="bi bi-list-check me-1" style="color:var(--brand);"></i> Azioni disponibili</h5>
                    <span class="hub-card-meta">Trascina per riordinare</span>
                </div>

                <ul class="hub-actions-list" id="hub-actions-list">
                    <?php foreach ($actions as $action):
                        $isPreset = $action['action_type'] === 'preset';
                        $presetDef = $isPreset ? ($presets[$action['preset_key']] ?? null) : null;
                        $label = $isPreset ? ($presetDef['label'] ?? $action['preset_key']) : $action['custom_label'];
                        $icon = $isPreset ? ($presetDef['icon'] ?? 'bi-link-45deg') : ($action['custom_icon'] ?: 'bi-link-45deg');
                        $sub = $isPreset ? ($presetDef['sub'] ?? null) : $action['custom_url'];
                        $locked = $isPreset && !empty($presetDef['locked_position']);
                    ?>
                    <li class="hub-action-item" data-id="<?= (int)$action['id'] ?>" data-locked="<?= $locked ? '1' : '0' ?>">
                        <span class="hub-drag-handle <?= $locked ? 'locked' : '' ?>">
                            <i class="bi <?= $locked ? 'bi-lock-fill' : 'bi-grip-vertical' ?>"></i>
                        </span>
                        <span class="hub-action-icon">
                            <i class="bi <?= e($icon) ?>"></i>
                        </span>
                        <div class="hub-action-text">
                            <div class="hub-action-label"><?= e($label) ?></div>
                            <?php if ($sub): ?>
                            <div class="hub-action-sub"><?= e($sub) ?></div>
                            <?php endif; ?>
                            <?php if (!$isPreset): ?>
                            <div class="hub-action-sub" style="color:var(--brand);"><i class="bi bi-link-45deg"></i> <?= e($action['custom_url']) ?></div>
                            <?php endif; ?>
                        </div>
                        <?php if (!$isPreset): ?>
                        <button type="button" class="hub-action-delete" data-delete-url="<?= url('dashboard/settings/hub/links/' . (int)$action['id'] . '/delete') ?>" title="Elimina link">
                            <i class="bi bi-trash"></i>
                        </button>
                        <?php endif; ?>
                        <label class="hub-switch hub-switch--sm">
                            <input type="checkbox" name="action_active[]" value="<?= (int)$action['id'] ?>" <?= $action['is_active'] ? 'checked' : '' ?> <?= $locked ? 'disabled' : '' ?>>
                            <span class="hub-switch-slider"></span>
                        </label>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <?php if (empty($actions)): ?>
                <div class="hub-empty">
                    <i class="bi bi-info-circle me-1"></i>
                    Salva una volta per inizializzare le azioni preset.
                </div>
                <?php endif; ?>

                <div class="hub-locked-note">
                    <i class="bi bi-lock me-1"></i>
                    "Prenota un tavolo" &egrave; sempre la prima azione e non pu&ograve; essere disattivata o riordinata.
                </div>
            </div>

            <!-- Custom links (Enterprise) -->
            <div class="card hub-card">
                <div class="hub-card-head">
                    <h5><i class="bi bi-link-45deg me-1" style="color:var(--brand);"></i> Link personalizzati
                        <?php if (!$isEnterprise): ?>
                        <span class="hub-enterprise-tag">Enterprise</span>
                        <?php endif; ?>
                    </h5>
                </div>
                <?php if ($isEnterprise): ?>
                <div class="hub-add-link-row">
                    <input type="text" id="custom-link-label" placeholder="Es. Eventi privati" maxlength="100">
                    <input type="url" id="custom-link-url" placeholder="https://...">
                    <select id="custom-link-icon">
                        <option value="bi-link-45deg">🔗 Link generico</option>
                        <option value="bi-calendar-event">📅 Evento</option>
                        <option value="bi-envelope">✉️ Email</option>
                        <option value="bi-newspaper">📰 Newsletter</option>
                        <option value="bi-stars">⭐ VIP / Premium</option>
                        <option value="bi-camera-video">🎥 Video / Tour</option>
                        <option value="bi-cup-hot">☕ Brunch / Caffè</option>
                        <option value="bi-balloon">🎈 Festa / Compleanno</option>
                        <option value="bi-music-note-beamed">🎵 Musica / Eventi live</option>
                    </select>
                    <button type="button" class="btn btn-sm btn-success" id="add-custom-link-btn">
                        <i class="bi bi-plus"></i> Aggiungi
                    </button>
                </div>
                <div class="hub-hint">I link personalizzati appaiono nella lista azioni sopra. Sono illimitati nel piano Enterprise.</div>
                <?php else: ?>
                <p style="font-size:.82rem; color:#6c757d; margin:0;">
                    Aggiungi link personalizzati (Eventi privati, Newsletter, Iscrizione VIP, ecc.) con il piano <strong>Enterprise</strong>.
                </p>
                <?php endif; ?>
            </div>

            <!-- Social footer -->
            <div class="card hub-card">
                <div class="hub-card-head">
                    <h5><i class="bi bi-share me-1" style="color:var(--brand);"></i> Social e contatti (footer)</h5>
                    <span class="hub-card-meta">Lascia vuoto se non hai l'account</span>
                </div>
                <div class="hub-grid-3">
                    <div class="hub-field">
                        <label><i class="bi bi-instagram" style="color:#E1306C;"></i> Instagram</label>
                        <input type="url" name="instagram_url" value="<?= e($settings['instagram_url'] ?? '') ?>" placeholder="https://instagram.com/...">
                    </div>
                    <div class="hub-field">
                        <label><i class="bi bi-facebook" style="color:#1877F2;"></i> Facebook</label>
                        <input type="url" name="facebook_url" value="<?= e($settings['facebook_url'] ?? '') ?>" placeholder="https://facebook.com/...">
                    </div>
                    <div class="hub-field">
                        <label><i class="bi bi-tiktok" style="color:#000;"></i> TikTok</label>
                        <input type="url" name="tiktok_url" value="<?= e($settings['tiktok_url'] ?? '') ?>" placeholder="https://tiktok.com/@...">
                    </div>
                    <div class="hub-field">
                        <label><i class="bi bi-twitter-x" style="color:#000;"></i> X (Twitter)</label>
                        <input type="url" name="twitter_url" value="<?= e($settings['twitter_url'] ?? '') ?>" placeholder="https://x.com/...">
                    </div>
                    <div class="hub-field">
                        <label><i class="bi bi-youtube" style="color:#FF0000;"></i> YouTube</label>
                        <input type="url" name="youtube_url" value="<?= e($settings['youtube_url'] ?? '') ?>" placeholder="https://youtube.com/@...">
                    </div>
                    <div class="hub-field">
                        <label><i class="bi bi-whatsapp" style="color:#25D366;"></i> WhatsApp</label>
                        <input type="tel" name="whatsapp_number" value="<?= e($settings['whatsapp_number'] ?? '') ?>" placeholder="+39 333 1234567">
                    </div>
                </div>
            </div>

            </div><!-- /.hub-config-block -->

            <!-- Save bar (always active, also when hub disabled — needed to re-enable) -->
            <div class="card hub-card" style="padding: 0;">
                <div class="save-bar">
                    <span class="save-hint">
                        <i class="bi bi-info-circle me-1"></i>
                        Le modifiche sono visibili immediatamente sulla Vetrina pubblica
                    </span>
                    <button type="submit" class="btn-save"><i class="bi bi-check-circle me-1"></i> Salva Vetrina</button>
                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN — Sticky URL + QR + Preview -->
        <div class="col-lg-5">
            <div class="hub-sticky <?= $enabled ? '' : 'hub-greyed' ?>" id="hub-sticky-block">

                <!-- URL share card -->
                <div class="hub-vurl-card">
                    <div class="hub-vurl-label"><i class="bi bi-link-45deg"></i> La tua Vetrina &egrave; online</div>
                    <div class="hub-vurl-url" id="hub-public-url"><?= e($publicHubUrl) ?></div>
                    <div class="hub-vurl-hint">Inseriscilo nella bio Instagram, sul biglietto da visita, sui volantini.</div>
                    <div class="hub-vurl-actions">
                        <button type="button" class="hub-vurl-btn primary" id="hub-copy-url"><i class="bi bi-clipboard"></i> Copia</button>
                        <a href="<?= e($publicHubUrl) ?>" target="_blank" rel="noopener" class="hub-vurl-btn"><i class="bi bi-box-arrow-up-right"></i> Apri</a>
                    </div>
                </div>

                <!-- QR card -->
                <div class="hub-qr-card">
                    <h6><i class="bi bi-qr-code me-1"></i> QR Code della tua Vetrina</h6>
                    <div id="hub-qr-canvas" data-url="<?= e($publicHubUrl) ?>"></div>
                    <div class="hub-qr-url"><?= e($publicHubUrl) ?></div>
                    <div class="hub-qr-actions">
                        <button type="button" class="hub-qr-btn" id="hub-qr-download-png"><i class="bi bi-download me-1"></i> PNG</button>
                        <button type="button" class="hub-qr-btn" id="hub-qr-print"><i class="bi bi-printer me-1"></i> Stampa</button>
                    </div>
                </div>

                <!-- Preview mock -->
                <div class="hub-preview-card">
                    <h6><i class="bi bi-phone me-1"></i> Anteprima della Vetrina</h6>
                    <div class="hub-preview-phone">
                        <div class="hub-preview-screen">
                            <div class="hub-preview-notch"></div>
                            <div class="ppm-cover" id="ppm-cover"></div>
                            <div class="ppm-logo">N</div>
                            <div class="ppm-body">
                                <div class="ppm-name">Nuok</div>
                                <div class="ppm-tag">Trattoria moderna · Roma</div>
                                <div class="ppm-cta" id="ppm-cta">
                                    <i class="bi bi-calendar-check"></i>
                                    <div class="ppm-cta-label">PRENOTA UN TAVOLO</div>
                                </div>
                                <div class="ppm-row"><span class="ppm-row-icon"><i class="bi bi-journal-richtext"></i></span> Guarda il menu</div>
                                <div class="ppm-row"><span class="ppm-row-icon"><i class="bi bi-star"></i></span> Lascia una recensione</div>
                                <div class="ppm-row"><span class="ppm-row-icon"><i class="bi bi-gift"></i></span> Offerte del momento</div>
                                <div class="ppm-row"><span class="ppm-row-icon"><i class="bi bi-whatsapp"></i></span> WhatsApp</div>
                                <div class="ppm-row"><span class="ppm-row-icon"><i class="bi bi-geo-alt"></i></span> Come raggiungerci</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</form>

<!-- Hidden form for adding custom links -->
<?php if ($isEnterprise): ?>
<form method="POST" action="<?= url('dashboard/settings/hub/links') ?>" id="add-custom-link-form" style="display:none;">
    <?= csrf_field() ?>
    <input type="hidden" name="label" id="add-link-label">
    <input type="hidden" name="url" id="add-link-url">
    <input type="hidden" name="icon" id="add-link-icon">
</form>
<?php endif; ?>

<!-- Hidden delete forms for custom links -->
<?php foreach ($actions as $action):
    if ($action['action_type'] !== 'custom') continue;
?>
<form method="POST" action="<?= url('dashboard/settings/hub/links/' . (int)$action['id'] . '/delete') ?>" id="delete-link-<?= (int)$action['id'] ?>" style="display:none;">
    <?= csrf_field() ?>
</form>
<?php endforeach; ?>

<!-- Inject CSRF token for AJAX reorder -->
<input type="hidden" id="csrf-token" value="<?= e(csrf_token()) ?>">
<input type="hidden" id="hub-reorder-url" value="<?= url('dashboard/settings/hub/reorder') ?>">

<?php endif; ?>
