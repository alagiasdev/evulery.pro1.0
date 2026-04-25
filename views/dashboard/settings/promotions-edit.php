<?php
$DAYS_IT = ['Lun','Mar','Mer','Gio','Ven','Sab','Dom'];
$old = $_SESSION['_flash']['old_input'] ?? $promo;
$existingDays = [];
if (!empty($old['days_of_week'])) {
    $existingDays = is_array($old['days_of_week'])
        ? $old['days_of_week']
        : explode(',', $old['days_of_week']);
}
if (isset($old['days'])) {
    $existingDays = $old['days'];
}
?>

<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">Impostazioni</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1rem;">Configura il tuo ristorante</p>

<?php $activeKey = 'promotions'; include __DIR__ . '/../../partials/settings-tabs.php'; ?>

<!-- Back link -->
<div class="page-back" style="margin-bottom:1rem;">
    <a href="<?= url('dashboard/settings/promotions') ?>">
        <i class="bi bi-arrow-left"></i> Torna alle promozioni
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card" style="padding:1.25rem;">
            <div style="font-weight:600; font-size:.95rem; margin-bottom:1rem;">
                <i class="bi bi-pencil me-1" style="color:var(--brand);"></i> Modifica promozione
            </div></div>

<form method="POST" action="<?= url("dashboard/settings/promotions/{$promo['id']}/update") ?>" id="promo-form">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Nome promozione *</label>
                    <input type="text" name="name" class="form-control form-control-sm" required maxlength="100"
                           placeholder="Es. Pranzo infrasettimanale" value="<?= e($old['name'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Descrizione <span class="text-muted fw-normal">(opzionale)</span></label>
                    <textarea name="description" class="form-control form-control-sm" rows="2" maxlength="280"
                              placeholder="Es. Sconto valido sul totale del conto, esclusi vini pregiati e bevande alcoliche."
                              data-promo-desc><?= e($old['description'] ?? '') ?></textarea>
                    <div class="d-flex justify-content-between" style="font-size:.72rem; color:#6c757d; margin-top:.25rem;">
                        <span>Mostrata sotto il nome nella pagina /promo</span>
                        <span data-promo-desc-counter>0/280</span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Sconto % *</label>
                    <select name="discount_percent" class="form-select form-select-sm" required>
                        <option value="">Seleziona...</option>
                        <?php foreach ([5,10,15,20,25,30,40,50] as $pct): ?>
                        <option value="<?= $pct ?>" <?= ($old['discount_percent'] ?? '') == $pct ? 'selected' : '' ?>>-<?= $pct ?>%</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Si applica a -->
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Si applica a</label>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php
                        $hasOrdering = tenant_can('online_ordering');
                        $appliesToOptions = [
                            'all'          => ['icon' => 'bi-grid', 'label' => 'Tutto'],
                            'reservations' => ['icon' => 'bi-calendar-check', 'label' => 'Solo prenotazioni'],
                            'orders'       => ['icon' => 'bi-bag', 'label' => 'Solo ordini'],
                        ];
                        if (!$hasOrdering) {
                            unset($appliesToOptions['all'], $appliesToOptions['orders']);
                        }
                        $currentApplies = $old['applies_to'] ?? 'all';
                        if (!$hasOrdering && ($currentApplies === 'all' || $currentApplies === 'orders')) {
                            $currentApplies = 'reservations';
                        }
                        foreach ($appliesToOptions as $val => $opt):
                        ?>
                        <label class="promo-type-option" style="flex:1; min-width:100px;">
                            <input type="radio" name="applies_to" value="<?= $val ?>" <?= $currentApplies === $val ? 'checked' : '' ?>>
                            <span class="promo-type-card" style="padding:.45rem .5rem;">
                                <i class="bi <?= $opt['icon'] ?>"></i>
                                <span style="font-size:.75rem;"><?= $opt['label'] ?></span>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Tipo *</label>
                    <div class="promo-type-selector">
                        <label class="promo-type-option">
                            <input type="radio" name="type" value="recurring" <?= in_array($old['type'] ?? '', ['recurring', 'time_slot']) ? 'checked' : '' ?>>
                            <span class="promo-type-card">
                                <i class="bi bi-arrow-repeat"></i>
                                <span>Ricorrente</span>
                                <small>Giorni e/o fascia oraria</small>
                            </span>
                        </label>
                        <label class="promo-type-option">
                            <input type="radio" name="type" value="specific_date" <?= ($old['type'] ?? '') === 'specific_date' ? 'checked' : '' ?>>
                            <span class="promo-type-card">
                                <i class="bi bi-calendar-event"></i>
                                <span>Data specifica</span>
                                <small>Evento singolo</small>
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Conditional: Days -->
                <div class="mb-3 promo-field" id="field-days">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Giorni della settimana</label>
                    <div class="promo-days-grid">
                        <?php foreach ($DAYS_IT as $i => $dayLabel): ?>
                        <label class="promo-day-chip">
                            <input type="checkbox" name="days[]" value="<?= $i ?>"
                                   <?= in_array((string)$i, $existingDays) ? 'checked' : '' ?>>
                            <span><?= $dayLabel ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Conditional: Time range -->
                <div class="mb-3 promo-field" id="field-time" style="display:none;">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Fascia oraria</label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="time" name="time_from" class="form-control form-control-sm" value="<?= e(substr($old['time_from'] ?? '', 0, 5)) ?>">
                        <span style="font-size:.82rem; color:#6c757d;">—</span>
                        <input type="time" name="time_to" class="form-control form-control-sm" value="<?= e(substr($old['time_to'] ?? '', 0, 5)) ?>">
                    </div>
                    <div class="promo-field-hint">Lascia vuoto per tutto il giorno</div>
                </div>

                <!-- Conditional: Date range -->
                <div class="mb-3 promo-field" id="field-dates" style="display:none;">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Date</label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($old['date_from'] ?? '') ?>">
                        <span style="font-size:.82rem; color:#6c757d;">—</span>
                        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($old['date_to'] ?? '') ?>">
                    </div>
                    <div class="promo-field-hint">Data fine opzionale (per range)</div>
                </div>

                <div class="d-flex gap-2">
                    <a href="<?= url('dashboard/settings/promotions') ?>" class="btn btn-outline-secondary" style="flex:1;">Annulla</a>
                    <button type="submit" class="btn-save" style="flex:2;">
                        <i class="bi bi-check-lg me-1"></i> Salva Modifiche
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
(function() {
    var typeRadios = document.querySelectorAll('input[name="type"]');
    var fieldDays = document.getElementById('field-days');
    var fieldTime = document.getElementById('field-time');
    var fieldDates = document.getElementById('field-dates');

    function updateFields() {
        var type = document.querySelector('input[name="type"]:checked');
        if (!type) return;
        var val = type.value;

        fieldDays.style.display = (val === 'recurring') ? 'block' : 'none';
        fieldTime.style.display = (val === 'recurring' || val === 'specific_date') ? 'block' : 'none';
        fieldDates.style.display = (val === 'specific_date') ? 'block' : 'none';
    }

    typeRadios.forEach(function(r) { r.addEventListener('change', updateFields); });
    updateFields();

    // Counter caratteri descrizione promo
    var descTextarea = document.querySelector('[data-promo-desc]');
    var descCounter = document.querySelector('[data-promo-desc-counter]');
    if (descTextarea && descCounter) {
        function updateDescCounter() {
            descCounter.textContent = descTextarea.value.length + '/280';
        }
        descTextarea.addEventListener('input', updateDescCounter);
        updateDescCounter();
    }
})();
</script>
