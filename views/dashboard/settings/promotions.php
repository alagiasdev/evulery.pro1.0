<?php
$settingsTabs = [
    ['url' => url('dashboard/settings'),                'icon' => 'bi-gear',         'label' => 'Generali',         'key' => 'settings'],
    ['url' => url('dashboard/settings/slots'),          'icon' => 'bi-clock',        'label' => 'Orari e Coperti',  'key' => 'slots'],
    ['url' => url('dashboard/settings/meal-categories'),'icon' => 'bi-tags',         'label' => 'Categorie Pasto',  'key' => 'meal-categories'],
    ['url' => url('dashboard/settings/closures'),       'icon' => 'bi-calendar-x',   'label' => 'Chiusure',         'key' => 'closures'],
    ['url' => url('dashboard/settings/promotions'),     'icon' => 'bi-percent',      'label' => 'Promozioni',       'key' => 'promotions'],
    ['url' => url('dashboard/settings/notifications'),  'icon' => 'bi-bell',       'label' => 'Notifiche',        'key' => 'settings-notifications'],
    ['url' => url('dashboard/settings/deposit'),        'icon' => 'bi-cash',         'label' => 'Caparra',          'key' => 'deposit'],
    ['url' => url('dashboard/settings/domain'),         'icon' => 'bi-globe',        'label' => 'Dominio',          'key' => 'domain'],
];

$DAYS_IT = ['Lun','Mar','Mer','Gio','Ven','Sab','Dom'];
$old = $_SESSION['_flash']['old_input'] ?? [];
$allPromos = array_merge($active, $inactive);

// Helper to describe a promotion in human-readable form
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
        'recurring'     => 'bi-arrow-repeat',
        'time_slot'     => 'bi-clock',
        'specific_date' => 'bi-calendar-event',
        default         => 'bi-percent',
    };
}
function promoTypeLabel(string $type): string {
    return match($type) {
        'recurring'     => 'Ricorrente',
        'time_slot'     => 'Fascia oraria',
        'specific_date' => 'Data specifica',
        default         => $type,
    };
}
?>

<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">Impostazioni</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1rem;">Configura il tuo ristorante</p>

<!-- Settings tabs -->
<div class="settings-tabs">
    <?php foreach ($settingsTabs as $tab): ?>
    <a href="<?= $tab['url'] ?>" class="settings-tab <?= $tab['key'] === 'promotions' ? 'active' : '' ?>">
        <i class="bi <?= $tab['icon'] ?>"></i> <?= $tab['label'] ?>
    </a>
    <?php endforeach; ?>
</div>

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
        <div class="promo-kpi-label">Prenotazioni con sconto (ultimi 30gg)</div>
    </div>
    <div class="promo-kpi-card">
        <?php if ($growthPercent !== null): ?>
        <div class="promo-kpi-value" style="color:<?= $growthPercent >= 0 ? '#E65100' : '#dc3545' ?>;"><?= $growthPercent >= 0 ? '+' : '' ?><?= $growthPercent ?>%</div>
        <?php else: ?>
        <div class="promo-kpi-value" style="color:#adb5bd;">—</div>
        <?php endif; ?>
        <div class="promo-kpi-label">Aumento prenotazioni nelle fasce promo</div>
    </div>
</div>

<div class="row g-4">
    <!-- Left: Create form -->
    <div class="col-lg-5">
        <div class="card" style="padding:1.25rem;">
            <div style="font-weight:600; font-size:.95rem; margin-bottom:1rem;">
                <i class="bi bi-plus-circle me-1" style="color:var(--brand);"></i> Nuova promozione
            </div>

            <form method="POST" action="<?= url('dashboard/settings/promotions') ?>" id="promo-form">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Nome promozione *</label>
                    <input type="text" name="name" class="form-control form-control-sm" required maxlength="100"
                           placeholder="Es. Pranzo infrasettimanale, Early Bird Cena" value="<?= e($old['name'] ?? '') ?>">
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

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Tipo *</label>
                    <div class="promo-type-selector">
                        <label class="promo-type-option">
                            <input type="radio" name="type" value="recurring" <?= ($old['type'] ?? 'recurring') === 'recurring' ? 'checked' : '' ?>>
                            <span class="promo-type-card">
                                <i class="bi bi-arrow-repeat"></i>
                                <span>Ricorrente</span>
                                <small>Ogni settimana</small>
                            </span>
                        </label>
                        <label class="promo-type-option">
                            <input type="radio" name="type" value="time_slot" <?= ($old['type'] ?? '') === 'time_slot' ? 'checked' : '' ?>>
                            <span class="promo-type-card">
                                <i class="bi bi-clock"></i>
                                <span>Fascia oraria</span>
                                <small>Ore specifiche</small>
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

                <!-- Conditional: Days (recurring + time_slot) -->
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

                <!-- Conditional: Time range -->
                <div class="mb-3 promo-field" id="field-time" style="display:none;">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Fascia oraria</label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="time" name="time_from" class="form-control form-control-sm" value="<?= e($old['time_from'] ?? '') ?>">
                        <span style="font-size:.82rem; color:#6c757d;">—</span>
                        <input type="time" name="time_to" class="form-control form-control-sm" value="<?= e($old['time_to'] ?? '') ?>">
                    </div>
                    <div class="promo-field-hint">Lascia vuoto per tutto il giorno</div>
                </div>

                <!-- Conditional: Date range (specific_date) -->
                <div class="mb-3 promo-field" id="field-dates" style="display:none;">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Date</label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($old['date_from'] ?? '') ?>">
                        <span style="font-size:.82rem; color:#6c757d;">—</span>
                        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($old['date_to'] ?? '') ?>">
                    </div>
                    <div class="promo-field-hint">Data fine opzionale (per range)</div>
                </div>

                <button type="submit" class="btn-save" style="width:100%;">
                    <i class="bi bi-plus-circle me-1"></i> Crea Promozione
                </button>
            </form>
        </div>
    </div>

    <!-- Right: Promotions list -->
    <div class="col-lg-7">
        <div style="font-weight:600; font-size:.95rem; margin-bottom:.75rem;">
            <i class="bi bi-list-ul me-1" style="color:var(--brand);"></i>
            Le tue promozioni
        </div>

        <?php if (empty($allPromos)): ?>
        <div class="card" style="padding:2.5rem; text-align:center;">
            <i class="bi bi-percent" style="font-size:2.5rem; color:#dee2e6;"></i>
            <p style="color:#6c757d; margin-top:.75rem; font-size:.88rem; margin-bottom:0;">Nessuna promozione creata.<br>Usa il form a sinistra per crearne una.</p>
        </div>
        <?php else: ?>
        <div class="card" style="padding:0;">
            <?php foreach ($allPromos as $p):
                $isActive = (bool)$p['is_active'];
            ?>
            <div class="promo-item <?= !$isActive ? 'promo-item-inactive' : '' ?>">
                <div class="promo-item-badge" <?= !$isActive ? 'style="background:#adb5bd;"' : '' ?>>-<?= (int)$p['discount_percent'] ?>%</div>
                <div class="promo-item-info">
                    <div class="promo-item-name"><?= e($p['name']) ?></div>
                    <div class="promo-item-meta">
                        <i class="bi <?= promoTypeIcon($p['type']) ?>"></i> <?= promoTypeLabel($p['type']) ?>
                        <?php $desc = describePromotion($p, $DAYS_IT); if ($desc): ?>
                         · <?= e($desc) ?>
                        <?php endif; ?>
                    </div>
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
                    <a href="<?= url("dashboard/settings/promotions/{$p['id']}/edit") ?>" class="btn btn-sm btn-outline-secondary" title="Modifica">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <form method="POST" action="<?= url("dashboard/settings/promotions/{$p['id']}/delete") ?>" style="display:inline;">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Elimina"
                                data-confirm="Sei sicuro di voler eliminare questa promozione?">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
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

        fieldDays.style.display = (val === 'recurring' || val === 'time_slot') ? 'block' : 'none';
        fieldTime.style.display = (val === 'time_slot' || val === 'recurring' || val === 'specific_date') ? 'block' : 'none';
        fieldDates.style.display = (val === 'specific_date') ? 'block' : 'none';

        var timeHint = fieldTime.querySelector('.promo-field-hint');
        if (timeHint) {
            timeHint.textContent = (val === 'time_slot') ? 'Obbligatorio per fascia oraria' : 'Lascia vuoto per tutto il giorno';
        }

        var daysLabel = fieldDays.querySelector('.form-label');
        if (daysLabel) {
            daysLabel.textContent = (val === 'recurring') ? 'Giorni della settimana *' : 'Giorni della settimana (opzionale)';
        }
    }

    typeRadios.forEach(function(r) { r.addEventListener('change', updateFields); });

    document.querySelectorAll('[data-confirm]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm)) { e.preventDefault(); }
        });
    });

    updateFields();
})();
</script>

<?php endif; ?>
