<?php
$settingsTabs = [
    ['url' => url('dashboard/settings'),                'icon' => 'bi-gear',  'label' => 'Generali',         'key' => 'settings'],
    ['url' => url('dashboard/settings/slots'),          'icon' => 'bi-clock', 'label' => 'Orari e Coperti',  'key' => 'slots'],
    ['url' => url('dashboard/settings/meal-categories'),'icon' => 'bi-tags',       'label' => 'Categorie Pasto',  'key' => 'meal-categories'],
    ['url' => url('dashboard/settings/closures'),       'icon' => 'bi-calendar-x', 'label' => 'Chiusure',         'key' => 'closures'],
    ['url' => url('dashboard/settings/deposit'),        'icon' => 'bi-cash',       'label' => 'Caparra',          'key' => 'deposit'],
    ['url' => url('dashboard/settings/domain'),         'icon' => 'bi-globe',      'label' => 'Dominio',          'key' => 'domain'],
];

$depositEnabled = (bool)$tenant['deposit_enabled'];
$depositAmount = $tenant['deposit_amount'] ? number_format((float)$tenant['deposit_amount'], 2, ',', '.') : '0,00';
$depositAmountRaw = $tenant['deposit_amount'] ? number_format((float)$tenant['deposit_amount'], 2, '.', '') : '';
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

                    <div class="amount-preview" id="amount-preview">
                        <div>
                            <div class="ap-amount" id="ap-value">&euro; <?= $depositAmount ?></div>
                            <div class="ap-label">per prenotazione</div>
                        </div>
                        <div>
                            <div class="ap-desc">
                                Il cliente paga l'importo al momento della prenotazione online.
                                L'importo viene scalato dal conto finale o rimborsato in caso di cancellazione nei tempi previsti.
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

            <!-- Stripe info -->
            <div class="card" style="margin-top:.75rem;">
                <div style="padding:1rem 1.25rem;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem;">
                        <span style="font-size:.82rem;font-weight:600;color:#495057;">Pagamento gestito da</span>
                        <span class="stripe-badge"><i class="bi bi-credit-card-2-front"></i> Stripe</span>
                    </div>
                    <ul style="font-size:.78rem;color:#6c757d;line-height:1.6;padding-left:1rem;margin:0;">
                        <li>Pagamenti PCI-DSS compliant</li>
                        <li>Supporta Visa, Mastercard, AMEX, Apple Pay, Google Pay</li>
                        <li>I dati della carta non transitano mai dal tuo server</li>
                        <li>Rimborsi gestibili dalla dashboard Stripe</li>
                    </ul>
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
    document.getElementById('deposit-enabled-input').value = enabled ? '1' : '';
});
// Amount preview update
document.getElementById('amount-input').addEventListener('input', function() {
    var val = parseFloat(this.value) || 0;
    var formatted = val.toFixed(2).replace('.', ',');
    document.getElementById('ap-value').textContent = '\u20AC ' + formatted;
});
</script>