<?php
$DAYS_IT = ['Lun','Mar','Mer','Gio','Ven','Sab','Dom'];
$old = $_SESSION['_flash']['old_input'] ?? [];
$allPromos = array_merge($active, $inactive);

function describePromotion(array $p, array $daysIt): string {
    $parts = [];
    if ($p['days_of_week'] !== null && $p['days_of_week'] !== '') {
        $dayNames = array_map(fn($d) => $daysIt[(int)$d] ?? '?', explode(',', $p['days_of_week']));
        $parts[] = implode(', ', $dayNames);
    }
    if ($p['time_from'] && $p['time_to']) {
        $parts[] = substr($p['time_from'], 0, 5) . "\u{2013}" . substr($p['time_to'], 0, 5);
    }
    if ($p['date_from']) {
        $from = date('d/m/Y', strtotime($p['date_from']));
        if ($p['date_to'] && $p['date_to'] !== $p['date_from']) {
            $parts[] = $from . ' \u{2013} ' . date('d/m/Y', strtotime($p['date_to']));
        } else {
            $parts[] = $from;
        }
    }
    if (empty($parts) && $p['type'] === 'recurring') {
        $parts[] = 'Tutti i giorni';
    }
    return implode(' · ', $parts);
}

function promoTypeIcon(string $type): string {
    return match($type) {
        'recurring', 'time_slot' => 'bi-arrow-repeat',
        'specific_date'          => 'bi-calendar-event',
        default                  => 'bi-percent',
    };
}
function promoTypeLabel(string $type): string {
    return match($type) {
        'recurring', 'time_slot' => 'Ricorrente',
        'specific_date'          => 'Data specifica',
        default                  => $type,
    };
}
function promoBadgeColor(string $type): string {
    return match($type) {
        'recurring', 'time_slot' => 'promo-badge--orange',
        'specific_date'          => 'promo-badge--blue',
        default                  => 'promo-badge--orange',
    };
}
?>

<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">Impostazioni</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1rem;">Configura il tuo ristorante</p>

<?php $activeKey = 'promotions'; include __DIR__ . '/../../partials/settings-tabs.php'; ?>

<?php if (!($canUsePromotions ?? true)): ?>
<?php $lockedTitle = 'Le promozioni'; include __DIR__ . '/../../partials/service-locked.php'; ?>
<?php else: ?>

<!-- KPI Cards -->
<div class="promo-kpi-row">
    <div class="promo-kpi-card">
        <div class="promo-kpi-value" style="color:var(--brand);"><?= count($active) ?></div>
        <div class="promo-kpi-label">Promozioni attive</div>
    </div>
    <div class="promo-kpi-card">
        <div class="promo-kpi-value" style="color:#1a1d23;"><?= (int)$discountedLast30 ?></div>
        <div class="promo-kpi-label">Prenotazioni con sconto (30gg)</div>
    </div>
    <div class="promo-kpi-card">
        <?php if ($growthPercent !== null): ?>
        <div class="promo-kpi-value" style="color:<?= $growthPercent >= 0 ? '#E65100' : '#dc3545' ?>;"><?= $growthPercent >= 0 ? '+' : '' ?><?= $growthPercent ?>%</div>
        <?php else: ?>
        <div class="promo-kpi-value" style="color:#adb5bd;">&mdash;</div>
        <?php endif; ?>
        <div class="promo-kpi-label">Aumento prenotazioni fasce promo</div>
    </div>
</div>

<!-- ========== PROMO LIST (full width, top) ========== -->
<div class="promo-section-card">
    <div class="promo-section-header">
        <div class="promo-section-icon" style="background:#E65100;"><i class="bi bi-megaphone"></i></div>
        <div>
            <div class="promo-section-title">Le tue promozioni</div>
            <div class="promo-section-subtitle">Gestisci sconti ricorrenti e occasionali</div>
        </div>
        <a href="#create-section" class="btn-save" style="margin-left:auto; font-size:.78rem; padding:.4rem 1rem;"
           data-scroll-target="create-section">
            <i class="bi bi-plus-circle me-1"></i> Nuova promozione
        </a>
    </div>

    <?php if (empty($allPromos)): ?>
    <div style="text-align:center; padding:2.5rem 1rem;">
        <i class="bi bi-percent" style="font-size:2.5rem; color:#dee2e6;"></i>
        <p style="color:#6c757d; margin-top:.75rem; font-size:.88rem; margin-bottom:0;">Nessuna promozione creata.<br>Clicca "Nuova promozione" per crearne una.</p>
    </div>
    <?php else: ?>
    <?php foreach ($allPromos as $p):
        $isActive = (bool)$p['is_active'];
        $promoBookings = $bookingsPerPromo[(int)$p['id']] ?? 0;
    ?>
    <div class="promo-item <?= !$isActive ? 'promo-item-inactive' : '' ?>">
        <div class="promo-item-badge <?= promoBadgeColor($p['type']) ?>">-<?= (int)$p['discount_percent'] ?>%</div>
        <div class="promo-item-info">
            <div class="promo-item-name"><?= e($p['name']) ?></div>
            <div class="promo-item-meta">
                <span class="promo-meta-item"><i class="bi <?= promoTypeIcon($p['type']) ?>"></i> <?= promoTypeLabel($p['type']) ?></span>
                <?php $desc = describePromotion($p, $DAYS_IT); if ($desc): ?>
                <span class="promo-meta-item"><i class="bi bi-calendar-range"></i> <?= e($desc) ?></span>
                <?php endif; ?>
                <?php $at = $p['applies_to'] ?? 'all'; if ($at !== 'all'): ?>
                <span class="promo-meta-item" style="color:<?= $at === 'orders' ? '#E65100' : '#1565C0' ?>;">
                    <i class="bi <?= $at === 'orders' ? 'bi-bag' : 'bi-calendar-check' ?>"></i>
                    <?= $at === 'orders' ? 'Solo ordini' : 'Solo prenotazioni' ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="promo-item-stats">
            <div class="promo-stats-val"><?= $promoBookings ?></div>
            <div class="promo-stats-label">prenotazioni</div>
        </div>
        <div class="promo-item-actions">
            <form method="POST" action="<?= url("dashboard/settings/promotions/{$p['id']}/toggle") ?>" class="d-inline">
                <?= csrf_field() ?>
                <button type="submit" class="promo-toggle-btn" title="<?= $isActive ? 'Disattiva' : 'Attiva' ?>">
                    <div class="promo-toggle <?= $isActive ? 'promo-toggle-on' : '' ?>">
                        <div class="promo-toggle-knob"></div>
                    </div>
                </button>
            </form>
            <div class="promo-action-btns">
                <a href="<?= url("dashboard/settings/promotions/{$p['id']}/edit") ?>" class="promo-action-btn" title="Modifica">
                    <i class="bi bi-pencil"></i>
                </a>
                <form method="POST" action="<?= url("dashboard/settings/promotions/{$p['id']}/delete") ?>" style="display:inline;">
                    <?= csrf_field() ?>
                    <button type="submit" class="promo-action-btn promo-action-btn--del" title="Elimina"
                            data-confirm="Sei sicuro di voler eliminare questa promozione?">
                        <i class="bi bi-trash3"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ========== CREATE FORM + PREVIEW (below, 7+5) ========== -->
<div id="create-section"></div>
<div class="row g-4" style="margin-top:.5rem;">
    <!-- Left: Create form -->
    <div class="col-lg-7">
        <div class="promo-section-card">
            <div class="promo-section-header">
                <div class="promo-section-icon" style="background:var(--brand);"><i class="bi bi-plus-circle"></i></div>
                <div>
                    <div class="promo-section-title">Nuova promozione</div>
                    <div class="promo-section-subtitle">Definisci sconto, giorni e fascia oraria</div>
                </div>
            </div>

            <div class="promo-form-body">
                <form method="POST" action="<?= url('dashboard/settings/promotions') ?>" id="promo-form">
                    <?= csrf_field() ?>

                    <!-- Tipo -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">Tipo di promozione *</label>
                        <div class="promo-type-selector">
                            <label class="promo-type-option">
                                <input type="radio" name="type" value="recurring" <?= ($old['type'] ?? 'recurring') === 'recurring' ? 'checked' : '' ?>>
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

                    <!-- Nome -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">Nome promozione *</label>
                        <input type="text" name="name" class="form-control form-control-sm" required maxlength="100"
                               placeholder="Es: Martedi Sconto, Early Bird, Lancio menu estivo..." value="<?= e($old['name'] ?? '') ?>">
                        <div class="promo-field-hint">Visibile ai clienti nella pagina pubblica delle offerte</div>
                    </div>

                    <!-- Descrizione -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">Descrizione <span class="text-muted fw-normal">(opzionale)</span></label>
                        <textarea name="description" class="form-control form-control-sm" rows="2" maxlength="280"
                                  placeholder="Es. Sconto valido sul totale del conto, esclusi vini pregiati e bevande alcoliche."
                                  data-promo-desc><?= e($old['description'] ?? '') ?></textarea>
                        <div class="promo-field-hint d-flex justify-content-between">
                            <span>Mostrata sotto il nome nella pagina /promo</span>
                            <span data-promo-desc-counter>0/280</span>
                        </div>
                    </div>

                    <!-- Sconto -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">Sconto % *</label>
                        <select name="discount_percent" class="form-select form-select-sm" required style="max-width:180px;">
                            <option value="">Seleziona...</option>
                            <?php foreach ([5,10,15,20,25,30,40,50] as $pct): ?>
                            <option value="<?= $pct ?>" <?= ($old['discount_percent'] ?? '') == $pct ? 'selected' : '' ?>>-<?= $pct ?>%</option>
                            <?php endforeach; ?>
                        </select>
                        <div class="promo-field-hint">Lo sconto si applica al conto (prenotazioni) o ai prezzi (ordini online)</div>
                    </div>

                    <!-- Si applica a -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">Si applica a</label>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php
                            $hasOrdering = tenant_can('online_ordering');
                            $appliesToOptions = [
                                'all'          => ['icon' => 'bi-grid', 'label' => 'Tutto', 'desc' => 'Prenotazioni + Ordini'],
                                'reservations' => ['icon' => 'bi-calendar-check', 'label' => 'Prenotazioni', 'desc' => 'Solo widget prenotazione'],
                                'orders'       => ['icon' => 'bi-bag', 'label' => 'Ordini online', 'desc' => 'Solo store ordini'],
                            ];
                            if (!$hasOrdering) {
                                unset($appliesToOptions['all'], $appliesToOptions['orders']);
                            }
                            foreach ($appliesToOptions as $val => $opt):
                            ?>
                            <label class="promo-type-option" style="flex:1; min-width:120px;">
                                <input type="radio" name="applies_to" value="<?= $val ?>" <?= ($old['applies_to'] ?? ($hasOrdering ? 'all' : 'reservations')) === $val ? 'checked' : '' ?>>
                                <span class="promo-type-card" style="padding:.5rem .6rem;">
                                    <i class="bi <?= $opt['icon'] ?>"></i>
                                    <span style="font-size:.78rem;"><?= $opt['label'] ?></span>
                                    <small><?= $opt['desc'] ?></small>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Giorni (recurring) -->
                    <div class="mb-3 promo-field" id="field-days">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">Giorni della settimana</label>
                        <div class="promo-days-grid">
                            <?php foreach ($DAYS_IT as $i => $dayLabel): ?>
                            <label class="promo-day-chip">
                                <input type="checkbox" name="days[]" value="<?= $i ?>"
                                       <?= in_array((string)$i, $old['days'] ?? []) ? 'checked' : '' ?>>
                                <span><?= $dayLabel ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Fascia oraria -->
                    <div class="mb-3 promo-field" id="field-time">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">Fascia oraria</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="time" name="time_from" class="form-control form-control-sm" value="<?= e($old['time_from'] ?? '') ?>">
                            <span style="font-size:.82rem; color:#6c757d;">&mdash;</span>
                            <input type="time" name="time_to" class="form-control form-control-sm" value="<?= e($old['time_to'] ?? '') ?>">
                        </div>
                        <div class="promo-field-hint">Lascia vuoto per tutto il giorno</div>
                    </div>

                    <!-- Date (specific_date) -->
                    <div class="mb-3 promo-field" id="field-dates" style="display:none;">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">Date</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($old['date_from'] ?? '') ?>">
                            <span style="font-size:.82rem; color:#6c757d;">&mdash;</span>
                            <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($old['date_to'] ?? '') ?>">
                        </div>
                        <div class="promo-field-hint">Data fine opzionale (per range)</div>
                    </div>

                    <!-- Submit -->
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-top:1.25rem; padding-top:1rem; border-top:1px solid #f0f0f0;">
                        <span style="font-size:.72rem; color:#adb5bd;"><i class="bi bi-info-circle me-1"></i>La promozione sara visibile immediatamente nel widget</span>
                        <button type="submit" class="btn-save" style="padding:.45rem 1.25rem;">
                            <i class="bi bi-check-circle me-1"></i> Crea promozione
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right: Preview panel -->
    <div class="col-lg-5">
        <div class="promo-preview-panel">
            <div class="promo-preview-title"><i class="bi bi-eye me-1"></i> Anteprima widget</div>
            <div class="promo-preview-label">Come vedra il cliente nel widget di prenotazione:</div>

            <div style="margin-top:1rem;">
                <div class="promo-preview-group-label">Turno Cena</div>
                <div class="promo-preview-slots">
                    <div class="promo-preview-slot promo-preview-slot--discount">
                        18:00
                        <span class="promo-preview-discount">-20%</span>
                    </div>
                    <div class="promo-preview-slot promo-preview-slot--discount">
                        18:30
                        <span class="promo-preview-discount">-20%</span>
                    </div>
                    <div class="promo-preview-slot promo-preview-slot--discount">
                        19:00
                        <span class="promo-preview-discount">-20%</span>
                    </div>
                    <div class="promo-preview-slot">19:30</div>
                    <div class="promo-preview-slot">20:00</div>
                    <div class="promo-preview-slot">20:30</div>
                    <div class="promo-preview-slot">21:00</div>
                </div>
            </div>

            <div class="promo-preview-note">
                <i class="bi bi-info-circle me-1"></i>
                Il badge sconto appare automaticamente sugli slot interessati dalla promozione.
            </div>
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

    document.querySelectorAll('[data-confirm]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm)) { e.preventDefault(); }
        });
    });

    // Smooth scroll to create section
    document.querySelectorAll('[data-scroll-target]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            var target = document.getElementById(this.dataset.scrollTarget);
            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

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

<?php endif; ?>
