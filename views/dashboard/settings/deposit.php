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
    ["url" => url("dashboard/settings/hub"),            "icon" => "bi-grid-3x3-gap", "label" => "Vetrina Digitale", "key" => "settings-hub"],
    ['url' => url('dashboard/settings/domain'),         'icon' => 'bi-globe',      'label' => 'Dominio',          'key' => 'domain'],
];

$depositEnabled = (bool)$tenant['deposit_enabled'];
$depositAmount = $tenant['deposit_amount'] ? number_format((float)$tenant['deposit_amount'], 2, ',', '.') : '0,00';
$depositAmountRaw = $tenant['deposit_amount'] ? number_format((float)$tenant['deposit_amount'], 2, '.', '') : '';
$depositMode = $tenant['deposit_mode'] ?? 'per_table';
$depositType = $tenant['deposit_type'] ?? 'info';
$bankInfo = $tenant['deposit_bank_info'] ?? '';
$paymentLink = $tenant['deposit_payment_link'] ?? '';
?>

<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">Impostazioni</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1rem;">Configura il tuo ristorante</p>

<!-- Settings tabs -->
<div class="settings-tabs-wrap"><div class="scroll-hint"><i class="bi bi-arrows"></i></div><div class="settings-tabs">
    <?php foreach ($settingsTabs as $tab): ?>
    <a href="<?= $tab['url'] ?>" class="settings-tab <?= $tab['key'] === 'deposit' ? 'active' : '' ?>">
        <i class="bi <?= $tab['icon'] ?>"></i> <span class="tab-label"><?= $tab['label'] ?></span>
    </a>
    <?php endforeach; ?>
</div></div>

<?php if (!($canUseDeposit ?? true)): ?>
<?php $lockedTitle = 'La caparra'; include __DIR__ . '/../../partials/service-locked.php'; ?>
<?php else: ?>

<form method="POST" action="<?= url('dashboard/settings/deposit') ?>">
    <?= csrf_field() ?>
    <div class="row g-4">
        <!-- Left column -->
        <div class="col-lg-7">

            <!-- Master toggle -->
            <div class="master-toggle <?= $depositEnabled ? 'enabled' : 'disabled' ?>" id="master-toggle">
                <div class="mt-left">
                    <div class="mt-icon" style="background:var(--brand-light);color:var(--brand);">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <div class="mt-title">Richiedi caparra</div>
                        <div class="mt-desc">I clienti dovranno versare una caparra per confermare la prenotazione online</div>
                    </div>
                </div>
                <div class="toggle-big <?= $depositEnabled ? 'on' : 'off' ?>" id="main-toggle"></div>
                <input type="hidden" name="deposit_enabled" id="deposit-enabled-input" value="<?= $depositEnabled ? '1' : '' ?>">
            </div>

            <!-- Amount config -->
            <div class="config-section <?= $depositEnabled ? '' : 'disabled-look' ?>" id="config-section">
                <div class="section-header">
                    <div class="section-icon" style="background:var(--brand);"><i class="bi bi-currency-euro"></i></div>
                    <div>
                        <div class="section-title">Importo</div>
                        <div class="section-subtitle">Quanto deve versare il cliente per confermare</div>
                    </div>
                </div>
                <div class="config-body">
                    <div style="max-width:280px;">
                        <label class="field-label">Importo caparra</label>
                        <div class="input-with-icon">
                            <span class="input-icon">&euro;</span>
                            <input type="number" class="field-input" name="deposit_amount" id="amount-input"
                                   value="<?= $depositAmountRaw ?>"
                                   min="1" step="0.50" placeholder="es. 10.00">
                        </div>
                        <div class="field-hint">Minimo &euro;1.00 &middot; Incrementi di &euro;0.50</div>
                    </div>

                    <!-- Deposit mode selector -->
                    <div style="margin-top:1.25rem;">
                        <label class="field-label">Modalit&agrave; calcolo</label>
                        <div style="display:flex;gap:.5rem;margin-top:.35rem;">
                            <label class="mode-option <?= $depositMode === 'per_table' ? 'active' : '' ?>">
                                <input type="radio" name="deposit_mode" value="per_table" <?= $depositMode === 'per_table' ? 'checked' : '' ?> style="display:none;">
                                <i class="bi bi-table me-1"></i> Per tavolo
                                <span class="mode-hint">Importo fisso indipendentemente dal numero di persone</span>
                            </label>
                            <label class="mode-option <?= $depositMode === 'per_person' ? 'active' : '' ?>">
                                <input type="radio" name="deposit_mode" value="per_person" <?= $depositMode === 'per_person' ? 'checked' : '' ?> style="display:none;">
                                <i class="bi bi-people me-1"></i> Per persona
                                <span class="mode-hint">Importo moltiplicato per il numero di coperti</span>
                            </label>
                        </div>
                    </div>

                    <!-- Min party size threshold -->
                    <div style="margin-top:1.25rem;">
                        <label class="field-label">Soglia minima coperti</label>
                        <div style="display:flex;align-items:center;gap:.5rem;margin-top:.35rem;">
                            <select name="deposit_min_party_size" class="form-select form-select-sm" style="max-width:180px;" id="min-party-select">
                                <option value="" <?= empty($tenant['deposit_min_party_size']) ? 'selected' : '' ?>>Sempre (tutti)</option>
                                <?php for ($i = 2; $i <= 20; $i++): ?>
                                <option value="<?= $i ?>" <?= ((int)($tenant['deposit_min_party_size'] ?? 0)) === $i ? 'selected' : '' ?>>Da <?= $i ?> persone in su</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="field-hint">La caparra viene richiesta solo quando il numero di coperti raggiunge la soglia</div>
                    </div>

                    <div class="amount-preview" id="amount-preview">
                        <div>
                            <div class="ap-amount" id="ap-value">&euro; <?= $depositAmount ?></div>
                            <div class="ap-label" id="ap-label"><?= $depositMode === 'per_person' ? 'per persona' : 'per tavolo (fisso)' ?></div>
                        </div>
                        <div>
                            <div class="ap-desc" id="ap-desc">
                                <?php if ($depositMode === 'per_person'): ?>
                                    Esempio: con 4 coperti il cliente paga &euro;<?= $depositAmountRaw ? number_format((float)$depositAmountRaw * 4, 2, ',', '.') : '0,00' ?>
                                <?php else: ?>
                                    Il cliente paga l'importo fisso al momento della prenotazione online.
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Deposit type selector -->
            <div class="config-section <?= $depositEnabled ? '' : 'disabled-look' ?>" id="type-section">
                <div class="section-header">
                    <div class="section-icon" style="background:#635BFF;"><i class="bi bi-wallet2"></i></div>
                    <div>
                        <div class="section-title">Metodo di pagamento</div>
                        <div class="section-subtitle">Come il cliente versa la caparra</div>
                    </div>
                </div>
                <div class="config-body">
                    <div style="display:flex;flex-direction:column;gap:.5rem;">
                        <label class="deposit-type-option <?= $depositType === 'info' ? 'active' : '' ?>">
                            <input type="radio" name="deposit_type" value="info" <?= $depositType === 'info' ? 'checked' : '' ?> style="display:none;">
                            <div class="dto-header">
                                <i class="bi bi-bank me-2"></i>
                                <span class="dto-title">Bonifico bancario</span>
                            </div>
                            <span class="dto-desc">Mostra IBAN e coordinate al cliente. Conferma manuale.</span>
                        </label>
                        <label class="deposit-type-option <?= $depositType === 'link' ? 'active' : '' ?>">
                            <input type="radio" name="deposit_type" value="link" <?= $depositType === 'link' ? 'checked' : '' ?> style="display:none;">
                            <div class="dto-header">
                                <i class="bi bi-link-45deg me-2"></i>
                                <span class="dto-title">Link di pagamento</span>
                            </div>
                            <span class="dto-desc">PayPal, SumUp, Satispay... Il cliente clicca il link. Conferma manuale.</span>
                        </label>
                        <label class="deposit-type-option <?= $depositType === 'stripe' ? 'active' : '' ?>">
                            <input type="radio" name="deposit_type" value="stripe" <?= $depositType === 'stripe' ? 'checked' : '' ?> style="display:none;">
                            <div class="dto-header">
                                <i class="bi bi-credit-card me-2"></i>
                                <span class="dto-title">Stripe integrato</span>
                            </div>
                            <span class="dto-desc">Pagamento con carta, Apple Pay, Google Pay. Conferma automatica.</span>
                        </label>
                    </div>

                    <!-- Config per tipo: Info -->
                    <div class="deposit-type-config" id="config-info" style="<?= $depositType === 'info' ? '' : 'display:none;' ?>margin-top:1rem;">
                        <label class="field-label">Coordinate bancarie</label>
                        <textarea class="field-input" name="deposit_bank_info" rows="3"
                                  placeholder="Es: IBAN: IT60X0542811101000000123456&#10;Intestatario: Ristorante Da Mario&#10;Causale: Caparra prenotazione"><?= e($bankInfo) ?></textarea>
                        <div class="field-hint">Queste informazioni saranno mostrate al cliente dopo la prenotazione</div>
                    </div>

                    <!-- Config per tipo: Link -->
                    <div class="deposit-type-config" id="config-link" style="<?= $depositType === 'link' ? '' : 'display:none;' ?>margin-top:1rem;">
                        <label class="field-label">URL pagamento</label>
                        <input type="text" class="field-input" name="deposit_payment_link"
                               value="<?= e($paymentLink) ?>"
                               placeholder="https://paypal.me/tuoristorante">
                        <div class="field-hint">PayPal.me, SumUp, Satispay o qualsiasi link/email di pagamento</div>
                    </div>

                    <!-- Config per tipo: Stripe -->
                    <div class="deposit-type-config" id="config-stripe" style="<?= $depositType === 'stripe' ? '' : 'display:none;' ?>margin-top:1rem;">
                        <div style="background:#f8f6ff;border:1px solid #e8e5f0;border-radius:8px;padding:.75rem;margin-bottom:.75rem;">
                            <div style="font-size:.78rem;color:#635BFF;font-weight:600;margin-bottom:.25rem;">
                                <i class="bi bi-info-circle me-1"></i> Come ottenere le chiavi
                            </div>
                            <div style="font-size:.73rem;color:#6c757d;">
                                Vai su <strong>dashboard.stripe.com</strong> &rarr; Sviluppatori &rarr; Chiavi API.<br>
                                Per il Webhook Secret: Sviluppatori &rarr; Webhook &rarr; Aggiungi endpoint.
                            </div>
                        </div>

                        <div style="margin-bottom:.75rem;">
                            <label class="field-label">Secret Key</label>
                            <input type="password" class="field-input" name="stripe_sk"
                                   value="<?= $stripeSkMasked ? e($stripeSkMasked) : '' ?>"
                                   placeholder="sk_live_..." autocomplete="off">
                            <?php if ($stripeSkMasked): ?>
                            <div class="field-hint" style="color:var(--brand);"><i class="bi bi-check-circle me-1"></i>Chiave configurata: <?= e($stripeSkMasked) ?></div>
                            <?php endif; ?>
                        </div>

                        <div style="margin-bottom:.75rem;">
                            <label class="field-label">Publishable Key</label>
                            <input type="text" class="field-input" name="stripe_pk"
                                   value="<?= $stripePkMasked ? e($stripePkMasked) : '' ?>"
                                   placeholder="pk_live_..." autocomplete="off">
                            <?php if ($stripePkMasked): ?>
                            <div class="field-hint" style="color:var(--brand);"><i class="bi bi-check-circle me-1"></i>Chiave configurata: <?= e($stripePkMasked) ?></div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label class="field-label">Webhook Secret</label>
                            <input type="password" class="field-input" name="stripe_wh_secret"
                                   value="<?= $stripeWhMasked ? e($stripeWhMasked) : '' ?>"
                                   placeholder="whsec_..." autocomplete="off">
                            <?php if ($stripeWhMasked): ?>
                            <div class="field-hint" style="color:var(--brand);"><i class="bi bi-check-circle me-1"></i>Secret configurato: <?= e($stripeWhMasked) ?></div>
                            <?php endif; ?>
                            <div class="field-hint">
                                Endpoint webhook: <code style="font-size:.7rem;"><?= url('api/v1/stripe/webhook') ?></code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save bar -->
            <div class="save-bar">
                <span class="save-hint"><i class="bi bi-info-circle me-1"></i>Le modifiche si applicano alle nuove prenotazioni</span>
                <button type="submit" class="btn-save"><i class="bi bi-check-circle me-1"></i> Salva</button>
            </div>

        </div>

        <!-- Right column -->
        <div class="col-lg-5">

            <!-- Payment flow -->
            <div class="flow-card">
                <div class="flow-title"><i class="bi bi-diagram-3 me-1"></i> Flusso pagamento</div>

                <!-- Flow for info/link -->
                <div class="flow-steps" id="flow-manual" style="<?= $depositType === 'stripe' ? 'display:none;' : '' ?>">
                    <div class="flow-step">
                        <div class="flow-step-line">
                            <div class="flow-dot" style="background:var(--brand);">1</div>
                            <div class="flow-connector"></div>
                        </div>
                        <div class="flow-content">
                            <div class="flow-step-title">Il cliente compila il form</div>
                            <div class="flow-step-desc">Sceglie data, orario, coperti e inserisce i dati</div>
                        </div>
                    </div>
                    <div class="flow-step">
                        <div class="flow-step-line">
                            <div class="flow-dot" style="background:#FF9800;">2</div>
                            <div class="flow-connector"></div>
                        </div>
                        <div class="flow-content">
                            <div class="flow-step-title" id="flow-manual-step2-title"><?= $depositType === 'link' ? 'Link di pagamento' : 'Coordinate bancarie' ?></div>
                            <div class="flow-step-desc" id="flow-manual-step2-desc"><?= $depositType === 'link' ? 'Il cliente viene indirizzato alla piattaforma di pagamento' : 'Il cliente vede IBAN e istruzioni per il bonifico' ?></div>
                        </div>
                    </div>
                    <div class="flow-step">
                        <div class="flow-step-line">
                            <div class="flow-dot" style="background:var(--brand);"><i class="bi bi-check-lg" style="font-size:.7rem;"></i></div>
                        </div>
                        <div class="flow-content">
                            <div class="flow-step-title">Conferma manuale</div>
                            <div class="flow-step-desc">Verifichi il pagamento e confermi la prenotazione dalla dashboard</div>
                        </div>
                    </div>
                </div>

                <!-- Flow for stripe -->
                <div class="flow-steps" id="flow-stripe" style="<?= $depositType === 'stripe' ? '' : 'display:none;' ?>">
                    <div class="flow-step">
                        <div class="flow-step-line">
                            <div class="flow-dot" style="background:var(--brand);">1</div>
                            <div class="flow-connector"></div>
                        </div>
                        <div class="flow-content">
                            <div class="flow-step-title">Il cliente compila il form</div>
                            <div class="flow-step-desc">Sceglie data, orario, coperti e inserisce i dati</div>
                        </div>
                    </div>
                    <div class="flow-step">
                        <div class="flow-step-line">
                            <div class="flow-dot" style="background:#635BFF;">2</div>
                            <div class="flow-connector"></div>
                        </div>
                        <div class="flow-content">
                            <div class="flow-step-title">Redirect a Stripe Checkout</div>
                            <div class="flow-step-desc">Pagamento sicuro con carta, Apple Pay, Google Pay</div>
                        </div>
                    </div>
                    <div class="flow-step">
                        <div class="flow-step-line">
                            <div class="flow-dot" style="background:#FF9800;">3</div>
                            <div class="flow-connector"></div>
                        </div>
                        <div class="flow-content">
                            <div class="flow-step-title">Conferma automatica</div>
                            <div class="flow-step-desc">Webhook Stripe notifica il sistema &rarr; prenotazione confermata</div>
                        </div>
                    </div>
                    <div class="flow-step">
                        <div class="flow-step-line">
                            <div class="flow-dot" style="background:var(--brand);"><i class="bi bi-check-lg" style="font-size:.7rem;"></i></div>
                        </div>
                        <div class="flow-content">
                            <div class="flow-step-title">Prenotazione attiva</div>
                            <div class="flow-step-desc">Il cliente riceve email di conferma con riepilogo</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tip -->
            <div class="card" style="margin-top:.75rem;border-left:4px solid #FFC107;border-radius:0 12px 12px 0;">
                <div class="tip-card">
                    <i class="bi bi-lightbulb" style="color:#FFC107;font-size:1rem;margin-top:.1rem;"></i>
                    <div>
                        <div class="tip-title">Suggerimento</div>
                        <div class="tip-text">
                            La caparra riduce significativamente i <strong>no-show</strong>. Consigliamo un importo tra <strong>&euro;5</strong> e <strong>&euro;15</strong> a persona.
                            Ricorda di aggiornare la <strong>politica di cancellazione</strong> in Impostazioni &gt; Generali.
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</form>

<script nonce="<?= csp_nonce() ?>">
// Master toggle
document.getElementById('main-toggle').addEventListener('click', function() {
    this.classList.toggle('on');
    this.classList.toggle('off');
    var enabled = this.classList.contains('on');
    var mt = document.getElementById('master-toggle');
    mt.classList.toggle('enabled', enabled);
    mt.classList.toggle('disabled', !enabled);
    document.getElementById('config-section').classList.toggle('disabled-look', !enabled);
    document.getElementById('type-section').classList.toggle('disabled-look', !enabled);
    document.getElementById('deposit-enabled-input').value = enabled ? '1' : '';
});

// Mode toggle (per_table/per_person)
document.querySelectorAll('.mode-option').forEach(function(el) {
    el.addEventListener('click', function() {
        document.querySelectorAll('.mode-option').forEach(function(o) { o.classList.remove('active'); });
        this.classList.add('active');
        this.querySelector('input[type="radio"]').checked = true;
        updatePreview();
    });
});

// Deposit type toggle
document.querySelectorAll('.deposit-type-option').forEach(function(el) {
    el.addEventListener('click', function() {
        document.querySelectorAll('.deposit-type-option').forEach(function(o) { o.classList.remove('active'); });
        this.classList.add('active');
        this.querySelector('input[type="radio"]').checked = true;
        var type = this.querySelector('input[type="radio"]').value;

        // Show/hide config sections
        document.querySelectorAll('.deposit-type-config').forEach(function(c) { c.style.display = 'none'; });
        var target = document.getElementById('config-' + type);
        if (target) target.style.display = '';

        // Show/hide flow diagrams
        if (type === 'stripe') {
            document.getElementById('flow-stripe').style.display = '';
            document.getElementById('flow-manual').style.display = 'none';
        } else {
            document.getElementById('flow-stripe').style.display = 'none';
            document.getElementById('flow-manual').style.display = '';
            var step2Title = document.getElementById('flow-manual-step2-title');
            var step2Desc = document.getElementById('flow-manual-step2-desc');
            if (type === 'link') {
                step2Title.textContent = 'Link di pagamento';
                step2Desc.textContent = 'Il cliente viene indirizzato alla piattaforma di pagamento';
            } else {
                step2Title.textContent = 'Coordinate bancarie';
                step2Desc.textContent = 'Il cliente vede IBAN e istruzioni per il bonifico';
            }
        }
    });
});

// Amount preview update
function updatePreview() {
    var val = parseFloat(document.getElementById('amount-input').value) || 0;
    var formatted = val.toFixed(2).replace('.', ',');
    var mode = document.querySelector('input[name="deposit_mode"]:checked').value;
    document.getElementById('ap-value').textContent = '\u20AC ' + formatted;
    if (mode === 'per_person') {
        document.getElementById('ap-label').textContent = 'per persona';
        var example = (val * 4).toFixed(2).replace('.', ',');
        document.getElementById('ap-desc').textContent = 'Esempio: con 4 coperti il cliente paga \u20AC' + example;
    } else {
        document.getElementById('ap-label').textContent = 'per tavolo (fisso)';
        document.getElementById('ap-desc').textContent = 'Il cliente paga l\'importo fisso al momento della prenotazione online.';
    }
}
document.getElementById('amount-input').addEventListener('input', updatePreview);
</script>

<?php endif; ?>
