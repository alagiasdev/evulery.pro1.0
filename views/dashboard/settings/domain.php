<?php
$settingsTabs = [
    ['url' => url('dashboard/settings'),                'icon' => 'bi-gear',  'label' => 'Generali',         'key' => 'settings'],
    ['url' => url('dashboard/settings/slots'),          'icon' => 'bi-clock', 'label' => 'Orari e Coperti',  'key' => 'slots'],
    ['url' => url('dashboard/settings/meal-categories'),'icon' => 'bi-tags',       'label' => 'Categorie Pasto',  'key' => 'meal-categories'],
    ['url' => url('dashboard/settings/closures'),       'icon' => 'bi-calendar-x', 'label' => 'Chiusure',         'key' => 'closures'],
    ['url' => url('dashboard/settings/promotions'),     'icon' => 'bi-percent',    'label' => 'Promozioni',       'key' => 'promotions'],
    ['url' => url('dashboard/settings/deposit'),        'icon' => 'bi-cash',       'label' => 'Caparra',          'key' => 'deposit'],
    ['url' => url('dashboard/settings/domain'),         'icon' => 'bi-globe',      'label' => 'Dominio',          'key' => 'domain'],
];

$customDomain = $tenant['custom_domain'] ?? '';
$domainStatus = $tenant['domain_status'] ?? 'none';
$cnameTarget = $tenant['cname_target'] ?? '';
$hostedUrl = url($tenant['slug']);
$isLinked = $domainStatus === 'linked';
$isPending = $domainStatus === 'dns_pending';
?>

<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">Impostazioni</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1rem;">Configura il tuo ristorante</p>

<!-- Settings tabs -->
<div class="settings-tabs">
    <?php foreach ($settingsTabs as $tab): ?>
    <a href="<?= $tab['url'] ?>" class="settings-tab <?= $tab['key'] === 'domain' ? 'active' : '' ?>">
        <i class="bi <?= $tab['icon'] ?>"></i> <?= $tab['label'] ?>
    </a>
    <?php endforeach; ?>
</div>

<?php if (!($canUseDomain ?? true)): ?>
<?php $lockedTitle = 'Il dominio personalizzato'; include __DIR__ . '/../../partials/service-locked.php'; ?>
<?php else: ?>

<div class="row g-4">
    <!-- Left column -->
    <div class="col-lg-7">

        <!-- Domain input -->
        <form method="POST" action="<?= url('dashboard/settings/domain') ?>" id="domain-form">
            <?= csrf_field() ?>
            <div class="domain-input-wrap">
                <div style="font-size:.88rem;font-weight:700;color:#212529;margin-bottom:.75rem;">
                    <i class="bi bi-globe2 me-1" style="color:var(--brand);"></i> Dominio personalizzato
                </div>
                <div class="domain-field">
                    <span class="domain-prefix">https://</span>
                    <input type="text" class="domain-input" name="custom_domain"
                           value="<?= e($customDomain) ?>"
                           placeholder="prenota.tuoristorante.it">
                </div>
                <div class="field-hint">Inserisci il sottodominio che vuoi usare per la pagina di prenotazione. Lascia vuoto per rimuoverlo.</div>
            </div>

            <?php if ($customDomain): ?>
            <!-- Status card -->
            <div class="status-card">
                <div class="status-header">
                    <div class="status-left">
                        <?php if ($isLinked): ?>
                        <div class="status-icon" style="background:var(--brand-light);color:var(--brand);">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <?php elseif ($isPending): ?>
                        <div class="status-icon" style="background:#FFF8E1;color:#F57F17;">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <?php else: ?>
                        <div class="status-icon" style="background:#f0f0f0;color:#6c757d;">
                            <i class="bi bi-gear"></i>
                        </div>
                        <?php endif; ?>
                        <div>
                            <div class="dns-status-title">Configurazione DNS</div>
                            <div class="dns-status-sub"><?= e($customDomain) ?></div>
                        </div>
                    </div>
                    <?php if ($isLinked): ?>
                    <span class="dns-status-badge" style="background:var(--brand-light);color:var(--brand);">
                        <i class="bi bi-check-circle-fill me-1"></i>Collegato
                    </span>
                    <?php elseif ($isPending): ?>
                    <span class="dns-status-badge" style="background:#FFF3E0;color:#E65100;">
                        <i class="bi bi-clock-history me-1"></i>In attesa DNS
                    </span>
                    <?php else: ?>
                    <span class="dns-status-badge" style="background:#f0f0f0;color:#6c757d;">
                        Non configurato
                    </span>
                    <?php endif; ?>
                </div>

                <?php if ($cnameTarget): ?>
                <div class="dns-steps">
                    <div class="dns-step done">
                        <div class="dns-num"><i class="bi bi-check-lg" style="font-size:.6rem;"></i></div>
                        <div class="dns-content">
                            <div class="dns-title">Dominio registrato</div>
                            <div class="dns-desc"><?= e($customDomain) ?> salvato nel sistema</div>
                        </div>
                    </div>

                    <div class="dns-step <?= $isLinked ? 'done' : 'current' ?>">
                        <div class="dns-num"><?= $isLinked ? '<i class="bi bi-check-lg" style="font-size:.6rem;"></i>' : '2' ?></div>
                        <div class="dns-content">
                            <div class="dns-title">Configura il record CNAME</div>
                            <div class="dns-desc">Vai nel pannello DNS del tuo provider e crea questo record:</div>
                            <div class="dns-record">
                                <div class="dns-record-row">
                                    <span class="dns-label">Tipo</span>
                                    <span class="dns-value">CNAME</span>
                                </div>
                                <div class="dns-record-row">
                                    <span class="dns-label">Host</span>
                                    <span class="dns-value"><?= e(explode('.', $customDomain)[0]) ?></span>
                                </div>
                                <div class="dns-record-row">
                                    <span class="dns-label">Punta a</span>
                                    <span class="dns-value"><?= e($cnameTarget) ?></span>
                                    <button type="button" class="dns-copy" data-copy-text="<?= e($cnameTarget) ?>"><i class="bi bi-clipboard me-1"></i>Copia</button>
                                </div>
                                <div class="dns-record-row">
                                    <span class="dns-label">TTL</span>
                                    <span class="dns-value">3600 (o Auto)</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="dns-step <?= $isLinked ? 'done' : '' ?>">
                        <div class="dns-num"><?= $isLinked ? '<i class="bi bi-check-lg" style="font-size:.6rem;"></i>' : '3' ?></div>
                        <div class="dns-content">
                            <div class="dns-title">Verifica propagazione</div>
                            <div class="dns-desc">La propagazione DNS pu&ograve; richiedere fino a 48 ore. Clicca "Verifica" per controllare.</div>
                        </div>
                    </div>

                    <div class="dns-step <?= $isLinked ? 'done' : '' ?>">
                        <div class="dns-num"><?= $isLinked ? '<i class="bi bi-check-lg" style="font-size:.6rem;"></i>' : '4' ?></div>
                        <div class="dns-content">
                            <div class="dns-title">Certificato SSL</div>
                            <div class="dns-desc">Emissione automatica del certificato HTTPS dopo la verifica DNS</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Save / Verify -->
            <div class="save-bar">
                <span class="save-hint"><i class="bi bi-info-circle me-1"></i>Dopo aver configurato il DNS, clicca Verifica</span>
                <div style="display:flex;gap:.5rem;">
                    <?php if ($customDomain && !$isLinked): ?>
                    <button type="submit" formaction="<?= url('dashboard/settings/domain/verify') ?>" class="btn-verify">
                        <i class="bi bi-arrow-repeat me-1"></i> Verifica DNS
                    </button>
                    <?php endif; ?>
                    <button type="submit" class="btn-save"><i class="bi bi-check-circle me-1"></i> Salva</button>
                </div>
            </div>
        </form>

    </div>

    <!-- Right column -->
    <div class="col-lg-5">

        <!-- Browser preview -->
        <div class="preview-card">
            <div class="preview-header"><i class="bi bi-window me-1"></i> Anteprima</div>
            <div class="browser-bar">
                <div class="browser-dots">
                    <div class="browser-dot" style="background:#FF5F57;"></div>
                    <div class="browser-dot" style="background:#FEBC2E;"></div>
                    <div class="browser-dot" style="background:#28C840;"></div>
                </div>
                <div class="browser-url">
                    <i class="bi bi-lock-fill"></i>
                    <?= $customDomain ? e($customDomain) : e(parse_url($hostedUrl, PHP_URL_HOST) . parse_url($hostedUrl, PHP_URL_PATH)) ?>
                </div>
            </div>
            <div class="preview-body">
                <div class="preview-widget">
                    <i class="bi bi-calendar-check" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
                    Widget di prenotazione<br>
                    <span style="font-size:.72rem;"><?= e($tenant['name']) ?></span>
                </div>
            </div>
        </div>

        <!-- Current URLs -->
        <div class="url-card">
            <div style="font-size:.78rem;font-weight:700;color:#adb5bd;text-transform:uppercase;margin-bottom:.5rem;">
                <i class="bi bi-link-45deg me-1"></i> URL attivi
            </div>
            <div class="url-row">
                <span class="url-label-text">Evulery</span>
                <span class="url-value"><?= e($hostedUrl) ?></span>
                <span class="url-status" style="color:var(--brand);"><i class="bi bi-check-circle-fill"></i> Attivo</span>
            </div>
            <?php if ($customDomain): ?>
            <div class="url-row">
                <span class="url-label-text">Personalizzato</span>
                <span class="url-value"><?= e($customDomain) ?></span>
                <?php if ($isLinked): ?>
                <span class="url-status" style="color:var(--brand);"><i class="bi bi-check-circle-fill"></i> Attivo</span>
                <?php else: ?>
                <span class="url-status" style="color:#F57F17;"><i class="bi bi-clock-fill"></i> Pending</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Help card -->
        <div class="card" style="margin-top:.75rem;border-left:4px solid #1565C0;border-radius:0 12px 12px 0;">
            <div class="tip-card">
                <i class="bi bi-question-circle" style="color:#1565C0;font-size:1rem;margin-top:.1rem;"></i>
                <div>
                    <div class="tip-title">Dove configuro il DNS?</div>
                    <div class="tip-text">
                        Accedi al pannello di controllo del provider dove hai acquistato il dominio
                        (es. GoDaddy, Aruba, Register.it, Cloudflare) e cerca la sezione <strong>DNS</strong> o <strong>Zone DNS</strong>.
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
// Copy buttons
document.querySelectorAll('[data-copy-text]').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        var text = this.dataset.copyText;
        navigator.clipboard.writeText(text).then(function() {
            var orig = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Copiato!';
            setTimeout(function() { btn.innerHTML = orig; }, 1500);
        });
    });
});
</script>

<?php endif; ?>