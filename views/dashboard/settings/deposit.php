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

$depositEnabled = (bool)$tenant['deposit_enabled'];
$depositAmount = $tenant['deposit_amount'] ? number_format((float)$tenant['deposit_amount'], 2, ',', '.') : '0,00';
$depositAmountRaw = $tenant['deposit_amount'] ? number_format((float)$tenant['deposit_amount'], 2, '.', '') : '';
$depositMode = $tenant['deposit_mode'] ?? 'per_table';
$stripeConnected = $stripeConnected ?? false;
$connectConfigured = $connectConfigured ?? false;
$stripeAccountId = $tenant['stripe_account_id'] ?? null;
$stripeConnectAt = $tenant['stripe_connect_at'] ?? null;
?>

<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">Impostazioni</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1rem;">Configura il tuo ristorante</p>

<!-- Settings tabs -->
<div class="settings-tabs">
    <?php foreach ($settingsTabs as $tab): ?>
    <a href="<?= $tab['url'] ?>" class="settings-tab <?= $tab['key'] === 'deposit' ? 'active' : '' ?>">
        <i class="bi <?= $tab['icon'] ?>"></i> <?= $tab['label'] ?>
    </a>
    <?php endforeach; ?>
</div>

<?php if (!($canUseDeposit ?? true)): ?>
<?php $lockedTitle = 'La caparra'; include __DIR__ . '/../../partials/service-locked.php'; ?>
<?php else: ?>

<form method="POST" action="<?= url('dashboard/settings/deposit') ?>">
    <?= csrf_field() ?>
    <div class="row g-4">
        <!-- Left column -->
        <div class="col-lg-7">

            <!-- Master toggle -->
            <div class="master-toggle <?= $depositEnabled ? 'enabled' : 'disabled' ?> <?= !$stripeConnected ? 'disabled-look' : '' ?>" id="master-toggle">
                <div class="mt-left">
                    <div class="mt-icon" style="background:var(--brand-light);color:var(--brand);">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <div class="mt-title">Richiedi caparra</div>
                        <div class="mt-desc">
                            <?php if (!$stripeConnected): ?>
                                <span style="color:#E65100;">Collega prima il tuo account Stripe per attivare la caparra</span>
                            <?php else: ?>
                                I clienti dovranno versare una caparra per confermare la prenotazione online
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="toggle-big <?= $depositEnabled ? 'on' : 'off' ?>" id="main-toggle" <?= !$stripeConnected ? 'style="pointer-events:none;opacity:.4;"' : '' ?>></div>
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
                <div class="flow-steps">
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
                            <div class="flow-step-desc">Pagamento sicuro su pagina Stripe con carta/Apple Pay/Google Pay</div>
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

            <!-- Stripe Connect -->
            <?php if ($stripeConnected): ?>
            <div class="card" style="margin-top:.75rem;border-left:4px solid var(--brand);border-radius:0 12px 12px 0;">
                <div style="padding:1rem 1.25rem;">
                    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem;">
                        <i class="bi bi-check-circle-fill" style="color:var(--brand);font-size:1.1rem;"></i>
                        <span style="font-size:.88rem;font-weight:700;color:var(--brand);">Account Stripe collegato</span>
                    </div>
                    <div style="font-size:.78rem;color:#6c757d;margin-bottom:.5rem;">
                        <div>Account: <code style="font-size:.75rem;"><?= e(substr($stripeAccountId, 0, 8)) ?>...<?= e(substr($stripeAccountId, -4)) ?></code></div>
                        <?php if ($stripeConnectAt): ?>
                        <div>Collegato il: <?= date('d/m/Y H:i', strtotime($stripeConnectAt)) ?></div>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:.78rem;color:#6c757d;margin-bottom:.75rem;">
                        I pagamenti delle caparre vengono accreditati direttamente sul tuo conto Stripe.
                    </div>
                    <form method="POST" action="<?= url('dashboard/settings/stripe/disconnect') ?>" style="margin:0;" id="stripe-disconnect-form">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-danger btn-sm" style="font-size:.75rem;">
                            <i class="bi bi-x-circle me-1"></i> Disconnetti Stripe
                        </button>
                    </form>
                </div>
            </div>
            <?php elseif ($connectConfigured): ?>
            <div class="card" style="margin-top:.75rem;border:2px dashed var(--brand);border-radius:12px;">
                <div style="padding:1.25rem;text-align:center;">
                    <div style="font-size:2rem;color:#635BFF;margin-bottom:.5rem;">
                        <i class="bi bi-credit-card-2-front"></i>
                    </div>
                    <div style="font-size:.88rem;font-weight:700;color:#495057;margin-bottom:.35rem;">Collega il tuo account Stripe</div>
                    <div style="font-size:.78rem;color:#6c757d;margin-bottom:1rem;">
                        Per ricevere i pagamenti delle caparre direttamente sul tuo conto, collega il tuo account Stripe.
                    </div>
                    <a href="<?= url('dashboard/settings/stripe/connect') ?>" class="btn btn-success btn-sm" style="font-size:.82rem;">
                        <i class="bi bi-link-45deg me-1"></i> Collega Stripe
                    </a>
                    <div style="font-size:.7rem;color:#adb5bd;margin-top:.75rem;">
                        Verrai reindirizzato a Stripe per autorizzare la connessione. <br>I tuoi dati bancari non vengono mai condivisi con noi.
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card" style="margin-top:.75rem;">
                <div style="padding:1rem 1.25rem;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem;">
                        <span style="font-size:.82rem;font-weight:600;color:#495057;">Pagamento gestito da</span>
                        <span class="stripe-badge"><i class="bi bi-credit-card-2-front"></i> Stripe</span>
                    </div>
                    <div style="font-size:.78rem;color:#6c757d;">
                        Stripe Connect non &egrave; ancora configurato sulla piattaforma. Contatta il supporto.
                    </div>
                </div>
            </div>
            <?php endif; ?>

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
    document.getElementById('deposit-enabled-input').value = enabled ? '1' : '';
});

// Mode toggle
document.querySelectorAll('.mode-option').forEach(function(el) {
    el.addEventListener('click', function() {
        document.querySelectorAll('.mode-option').forEach(function(o) { o.classList.remove('active'); });
        this.classList.add('active');
        this.querySelector('input[type="radio"]').checked = true;
        updatePreview();
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

// Stripe disconnect confirmation
var disconnectForm = document.getElementById('stripe-disconnect-form');
if (disconnectForm) {
    disconnectForm.addEventListener('submit', function(e) {
        if (!confirm('Sei sicuro di voler disconnettere il tuo account Stripe?\n\nLa caparra verrà disattivata automaticamente.')) {
            e.preventDefault();
        }
    });
}
</script>

<?php endif; ?>