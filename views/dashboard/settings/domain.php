<?php
$settingsTabs = [
    ['url' => url('dashboard/settings'),                'icon' => 'bi-gear',  'label' => 'Generali',         'key' => 'settings'],
    ['url' => url('dashboard/settings/slots'),          'icon' => 'bi-clock', 'label' => 'Orari e Coperti',  'key' => 'slots'],
    ['url' => url('dashboard/settings/meal-categories'),'icon' => 'bi-tags',       'label' => 'Categorie Pasto',  'key' => 'meal-categories'],
    ['url' => url('dashboard/settings/closures'),       'icon' => 'bi-calendar-x', 'label' => 'Chiusure',         'key' => 'closures'],
    ['url' => url('dashboard/settings/promotions'),     'icon' => 'bi-percent',    'label' => 'Promozioni',       'key' => 'promotions'],
    ['url' => url('dashboard/settings/notifications'),  'icon' => 'bi-bell',       'label' => 'Notifiche',        'key' => 'settings-notifications'],
    ['url' => url('dashboard/settings/deposit'),        'icon' => 'bi-cash',       'label' => 'Caparra',          'key' => 'deposit'],
    ['url' => url('dashboard/settings/ordering'),       'icon' => 'bi-bag-check',  'label' => 'Ordini online',    'key' => 'settings-ordering'],
    ['url' => url('dashboard/settings/reviews'),       'icon' => 'bi-star',       'label' => 'Recensioni',       'key' => 'settings-reviews'],
    ['url' => url('dashboard/settings/domain'),         'icon' => 'bi-globe',      'label' => 'Dominio',          'key' => 'domain'],
];

$customDomain = $tenant['custom_domain'] ?? '';
$domainStatus = $tenant['domain_status'] ?? 'none';
// Always read cname target from env (single source of truth); fallback to tenant column for backward compat
$cnameTarget = env('CUSTOM_DOMAIN_CNAME_TARGET')
    ?: parse_url((string)env('APP_URL', ''), PHP_URL_HOST)
    ?: ($tenant['cname_target'] ?? 'dash.evulery.it');
// A record target: explicit env or resolved via cached DNS lookup (handled in controller; here we try env only)
$aTarget = env('CUSTOM_DOMAIN_A_TARGET');
if (!$aTarget) {
    // Best-effort DNS resolution (shared cache with controller)
    $cached = \App\Core\Cache::get('custom_domain_a_target');
    if ($cached) {
        $aTarget = $cached;
    } else {
        $rec = @dns_get_record($cnameTarget, DNS_A);
        if ($rec && isset($rec[0]['ip'])) {
            $aTarget = $rec[0]['ip'];
            \App\Core\Cache::put('custom_domain_a_target', $aTarget, 86400);
        }
    }
}
$hostedUrl = url($tenant['slug']);
$isActive = in_array($domainStatus, ['active', 'linked'], true);  // 'linked' = legacy
$isDnsOk = $domainStatus === 'dns_ok';
$isPending = $domainStatus === 'dns_pending';

// Determine if user is using a subdomain or apex: influences the "Host" field shown
$domainParts = $customDomain ? explode('.', $customDomain) : [];
$isSubdomain = count($domainParts) >= 3;
$hostForDns = $isSubdomain ? $domainParts[0] : '@';
?>

<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">Impostazioni</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1rem;">Configura il tuo ristorante</p>

<!-- Settings tabs -->
<div class="settings-tabs-wrap"><div class="scroll-hint"><i class="bi bi-arrows"></i></div><div class="settings-tabs">
    <?php foreach ($settingsTabs as $tab): ?>
    <a href="<?= $tab['url'] ?>" class="settings-tab <?= $tab['key'] === 'domain' ? 'active' : '' ?>">
        <i class="bi <?= $tab['icon'] ?>"></i> <span class="tab-label"><?= $tab['label'] ?></span>
    </a>
    <?php endforeach; ?>
</div></div>

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
                        <?php if ($isActive): ?>
                        <div class="status-icon" style="background:var(--brand-light);color:var(--brand);">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <?php elseif ($isDnsOk): ?>
                        <div class="status-icon" style="background:#E3F2FD;color:#1565C0;">
                            <i class="bi bi-gear-wide-connected"></i>
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
                    <?php if ($isActive): ?>
                    <span class="dns-status-badge" style="background:var(--brand-light);color:var(--brand);">
                        <i class="bi bi-check-circle-fill me-1"></i>Attivo
                    </span>
                    <?php elseif ($isDnsOk): ?>
                    <span class="dns-status-badge" style="background:#E3F2FD;color:#1565C0;">
                        <i class="bi bi-gear-wide-connected me-1"></i>Attivazione in corso
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

                <?php if ($isDnsOk): ?>
                <div style="background:#E3F2FD; border:1px solid #90CAF9; border-radius:8px; padding:.75rem 1rem; margin:.85rem 0; font-size:.85rem; color:#0D47A1;">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    <strong>DNS verificato correttamente.</strong> Stiamo configurando il tuo dominio sul nostro server: riceverai una email quando sarà pienamente attivo (di solito entro 24h). Non devi fare altro.
                </div>
                <?php endif; ?>

                <?php if ($cnameTarget): ?>
                <?php
                    $step2Class = ($isDnsOk || $isActive) ? 'done' : 'current';
                    $step3Class = $isActive ? 'done' : (($isDnsOk) ? 'current' : '');
                    $step4Class = $isActive ? 'done' : '';
                    $stepDoneIcon = '<i class="bi bi-check-lg" style="font-size:.6rem;"></i>';
                ?>
                <div class="dns-steps">
                    <div class="dns-step done">
                        <div class="dns-num"><?= $stepDoneIcon ?></div>
                        <div class="dns-content">
                            <div class="dns-title">Dominio registrato</div>
                            <div class="dns-desc"><?= e($customDomain) ?> salvato nel sistema</div>
                        </div>
                    </div>

                    <div class="dns-step <?= $step2Class ?>">
                        <div class="dns-num"><?= ($isDnsOk || $isActive) ? $stepDoneIcon : '2' ?></div>
                        <div class="dns-content">
                            <div class="dns-title">Configura il record DNS</div>
                            <div class="dns-desc">Scegli <strong>uno dei due</strong> tipi di record. Entrambi funzionano.</div>

                            <!-- Option A: CNAME (recommended for subdomains) -->
                            <div class="dns-record" style="margin-top:.75rem;">
                                <div class="dns-record-row" style="background:var(--brand-light); border-radius:6px 6px 0 0; padding:6px 10px; font-weight:700; color:var(--brand-dark);">
                                    <span>Opzione A — CNAME <?= $isSubdomain ? '(consigliato per sottodomini)' : '' ?></span>
                                </div>
                                <div class="dns-record-row">
                                    <span class="dns-label">Tipo</span>
                                    <span class="dns-value">CNAME</span>
                                </div>
                                <div class="dns-record-row">
                                    <span class="dns-label">Host</span>
                                    <span class="dns-value"><?= e($hostForDns) ?></span>
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

                            <?php if ($aTarget): ?>
                            <!-- Option B: A record (required for apex, good for Aruba) -->
                            <div class="dns-record" style="margin-top:.75rem;">
                                <div class="dns-record-row" style="background:#FFF3E0; border-radius:6px 6px 0 0; padding:6px 10px; font-weight:700; color:#E65100;">
                                    <span>Opzione B — Record A <?= !$isSubdomain ? '(unica opzione per il dominio apex)' : '(alternativo)' ?></span>
                                </div>
                                <div class="dns-record-row">
                                    <span class="dns-label">Tipo</span>
                                    <span class="dns-value">A</span>
                                </div>
                                <div class="dns-record-row">
                                    <span class="dns-label">Host</span>
                                    <span class="dns-value"><?= e($hostForDns) ?></span>
                                </div>
                                <div class="dns-record-row">
                                    <span class="dns-label">Valore</span>
                                    <span class="dns-value"><?= e($aTarget) ?></span>
                                    <button type="button" class="dns-copy" data-copy-text="<?= e($aTarget) ?>"><i class="bi bi-clipboard me-1"></i>Copia</button>
                                </div>
                                <div class="dns-record-row">
                                    <span class="dns-label">TTL</span>
                                    <span class="dns-value">3600 (o Auto)</span>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div style="margin-top:.6rem; font-size:.78rem; color:#6c757d;">
                                <i class="bi bi-info-circle me-1"></i>
                                Se il tuo registrar (es. Aruba) non permette CNAME su un nome host specifico o sull'apex del dominio, usa l'<strong>Opzione B</strong>.
                            </div>
                        </div>
                    </div>

                    <div class="dns-step <?= $step3Class ?>">
                        <div class="dns-num"><?= $isActive ? $stepDoneIcon : '3' ?></div>
                        <div class="dns-content">
                            <div class="dns-title">Attivazione tecnica</div>
                            <div class="dns-desc">Dopo la verifica DNS, aggiungiamo il tuo dominio al nostro server. Entro 24h ricevi conferma via email.</div>
                        </div>
                    </div>

                    <div class="dns-step <?= $step4Class ?>">
                        <div class="dns-num"><?= $isActive ? $stepDoneIcon : '4' ?></div>
                        <div class="dns-content">
                            <div class="dns-title">Certificato SSL (HTTPS)</div>
                            <div class="dns-desc">Emissione automatica del certificato HTTPS. Quando ricevi l'email, torna qui e clicca "Verifica" per completare.</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Save / Verify -->
            <div class="save-bar">
                <span class="save-hint">
                    <?php if ($isActive): ?>
                        <i class="bi bi-check-circle-fill me-1" style="color:var(--brand);"></i>Dominio attivo
                    <?php elseif ($isDnsOk): ?>
                        <i class="bi bi-info-circle me-1"></i>Attivazione in corso. Quando ricevi l'email clicca "Verifica"
                    <?php else: ?>
                        <i class="bi bi-info-circle me-1"></i>Dopo aver configurato il DNS, clicca Verifica
                    <?php endif; ?>
                </span>
                <div style="display:flex;gap:.5rem;">
                    <?php if ($customDomain && !$isActive): ?>
                    <button type="submit" formaction="<?= url('dashboard/settings/domain/verify') ?>" class="btn-verify">
                        <?php if ($isDnsOk): ?>
                            <i class="bi bi-shield-check me-1"></i> Verifica SSL
                        <?php else: ?>
                            <i class="bi bi-arrow-repeat me-1"></i> Verifica DNS
                        <?php endif; ?>
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
                <?php if ($isActive): ?>
                <span class="url-status" style="color:var(--brand);"><i class="bi bi-check-circle-fill"></i> Attivo</span>
                <?php elseif ($isDnsOk): ?>
                <span class="url-status" style="color:#1565C0;"><i class="bi bi-gear-wide-connected"></i> Attivazione</span>
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
                    <div class="tip-title">Serve aiuto?</div>
                    <div class="tip-text">
                        Leggi la <a href="<?= url('dashboard/help/dominio') ?>" style="color:#1565C0; font-weight:600;">guida completa al dominio personalizzato</a>
                        con istruzioni step-by-step per Aruba, Register.it, GoDaddy, Namecheap e FAQ sui problemi comuni.
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