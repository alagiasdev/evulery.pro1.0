<?php
$settingsTabs = [
    ['url' => url('dashboard/settings'),                'icon' => 'bi-gear',  'label' => 'Generali',         'key' => 'settings'],
    ['url' => url('dashboard/settings/slots'),          'icon' => 'bi-clock', 'label' => 'Orari e Coperti',  'key' => 'slots'],
    ['url' => url('dashboard/settings/meal-categories'),'icon' => 'bi-tags',       'label' => 'Categorie Pasto',  'key' => 'meal-categories'],
    ['url' => url('dashboard/settings/closures'),       'icon' => 'bi-calendar-x', 'label' => 'Chiusure',         'key' => 'closures'],
    ['url' => url('dashboard/settings/promotions'),     'icon' => 'bi-percent',    'label' => 'Promozioni',       'key' => 'promotions'],
    ['url' => url('dashboard/settings/notifications'),  'icon' => 'bi-bell',       'label' => 'Notifiche',        'key' => 'settings-notifications'],
    ['url' => url('dashboard/settings/deposit'),        'icon' => 'bi-cash',       'label' => 'Caparra',          'key' => 'deposit'],
    ['url' => url('dashboard/settings/domain'),         'icon' => 'bi-globe',      'label' => 'Dominio',          'key' => 'domain'],
];

$timeStep = (int)$tenant['time_step'];
$endHour = 23;
$times = [];
for ($h = $startHour; $h <= $endHour; $h++) {
    for ($m = 0; $m < 60; $m += $timeStep) {
        $times[] = sprintf('%02d:%02d', $h, $m);
    }
}

// Build category ranges for meal dividers and inactive detection
$activeCats = [];
$inactiveRanges = [];
foreach ($allCategories as $cat) {
    $catStart = (int)substr($cat['start_time'], 0, 2) * 60 + (int)substr($cat['start_time'], 3, 2);
    $catEnd = (int)substr($cat['end_time'], 0, 2) * 60 + (int)substr($cat['end_time'], 3, 2);
    if ($cat['is_active']) {
        $activeCats[] = ['name' => $cat['display_name'], 'start' => $catStart, 'end' => $catEnd, 'start_time' => substr($cat['start_time'], 0, 5), 'end_time' => substr($cat['end_time'], 0, 5)];
    } else {
        $inactiveRanges[] = ['name' => $cat['display_name'], 'start' => $catStart, 'end' => $catEnd, 'start_time' => substr($cat['start_time'], 0, 5), 'end_time' => substr($cat['end_time'], 0, 5)];
    }
}

function getInactiveCategoryName(string $time, array $ranges): ?string {
    $parts = explode(':', $time);
    $mins = (int)$parts[0] * 60 + (int)$parts[1];
    foreach ($ranges as $r) {
        if ($mins >= $r['start'] && $mins < $r['end']) return $r['name'];
    }
    return null;
}

function getCategoryForTime(string $time, array $cats): ?array {
    $parts = explode(':', $time);
    $mins = (int)$parts[0] * 60 + (int)$parts[1];
    foreach ($cats as $c) {
        if ($mins >= $c['start'] && $mins < $c['end']) return $c;
    }
    return null;
}

function getInactiveCategoryForTime(string $time, array $ranges): ?array {
    $parts = explode(':', $time);
    $mins = (int)$parts[0] * 60 + (int)$parts[1];
    foreach ($ranges as $r) {
        if ($mins >= $r['start'] && $mins < $r['end']) return $r;
    }
    return null;
}

// Detect phantom slots
$timesSet = array_flip($times);
$phantomTimes = [];
foreach ($slotsByDay as $daySlots) {
    foreach ($daySlots as $s) {
        $t = substr($s['slot_time'], 0, 5);
        if (!isset($timesSet[$t]) && !isset($phantomTimes[$t])) {
            $phantomTimes[$t] = true;
        }
    }
}
if (!empty($phantomTimes)) {
    $times = array_unique(array_merge($times, array_keys($phantomTimes)));
    sort($times);
}

$DAYS_SHORT = ['Lun','Mar','Mer','Gio','Ven','Sab','Dom'];
?>

<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">Impostazioni</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1rem;">Configura il tuo ristorante</p>

<!-- Settings tabs -->
<div class="settings-tabs">
    <?php foreach ($settingsTabs as $tab): ?>
    <a href="<?= $tab['url'] ?>" class="settings-tab <?= $tab['key'] === 'slots' ? 'active' : '' ?>">
        <i class="bi <?= $tab['icon'] ?>"></i> <?= $tab['label'] ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Info banner -->
<div class="info-banner">
    <div class="info-banner-icon" style="background:var(--brand-light); color:var(--brand);">
        <i class="bi bi-info-circle"></i>
    </div>
    <div class="info-banner-text">
        Imposta i <strong>coperti massimi</strong> per ogni fascia oraria e giorno della settimana.
        Lascia <strong>0</strong> per chiudere una fascia.
        <div style="margin-top:.35rem;">
            <span class="info-pill"><i class="bi bi-clock"></i> Step: <?= $timeStep ?> min</span>
            <span class="info-pill" style="margin-left:.25rem;"><i class="bi bi-hourglass-split"></i> Durata tavolo: <?= (int)$tenant['table_duration'] ?> min</span>
        </div>
    </div>
</div>

<?php if (!empty($phantomTimes)): ?>
<div style="display:flex;align-items:center;gap:.5rem;background:#FFF8E1;border:1px solid #FFE082;border-radius:10px;padding:.6rem 1rem;margin-bottom:1rem;">
    <i class="bi bi-exclamation-triangle" style="color:#F57F17;font-size:1rem;"></i>
    <span style="font-size:.8rem;color:#5D4037;">
        Trovati <strong><?= count($phantomTimes) ?></strong> orari non standard (da un vecchio step). Sono evidenziati in giallo. Clicca <strong>Salva</strong> per rimuoverli.
    </span>
</div>
<?php endif; ?>

<!-- Main grid card -->
<form method="POST" action="<?= url('dashboard/settings/slots') ?>">
    <?= csrf_field() ?>
    <div class="card">
        <!-- Toolbar -->
        <div class="slots-toolbar">
            <div class="toolbar-group">
                <span class="tb-label">Compila tutti:</span>
                <button type="button" class="tb-btn" data-fill="20">20</button>
                <button type="button" class="tb-btn" data-fill="30">30</button>
                <button type="button" class="tb-btn" data-fill="40">40</button>
                <button type="button" class="tb-btn" data-fill="50">50</button>
                <button type="button" class="tb-btn" data-fill="0" style="color:#dc3545;border-color:#dc3545;">Azzera</button>
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table class="slots-grid">
                <thead>
                    <tr>
                        <th>Orario</th>
                        <?php for ($day = 0; $day < 7; $day++): ?>
                        <th><?= $DAYS_SHORT[$day] ?></th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $shownActiveDividers = [];
                    $shownInactiveDividers = [];
                    foreach ($times as $time):
                        $isPhantom = isset($phantomTimes[$time]);
                        $inactiveCat = getInactiveCategoryName($time, $inactiveRanges);
                        $isInactive = ($inactiveCat !== null);
                        $rowClass = '';
                        if ($isPhantom) $rowClass = 'row-phantom';
                        elseif ($isInactive) $rowClass = 'row-inactive';

                        // Show divider for the first slot that falls within a category
                        $activeCatHit = getCategoryForTime($time, $activeCats);
                        $inactiveCatHit = getInactiveCategoryForTime($time, $inactiveRanges);
                        $showActiveDivider = $activeCatHit && !isset($shownActiveDividers[$activeCatHit['name']]);
                        $showInactiveDivider = $inactiveCatHit && !isset($shownInactiveDividers[$inactiveCatHit['name']]);
                        if ($showActiveDivider) $shownActiveDividers[$activeCatHit['name']] = true;
                        if ($showInactiveDivider) $shownInactiveDividers[$inactiveCatHit['name']] = true;
                    ?>
                    <?php if ($showActiveDivider): ?>
                    <tr>
                        <td colspan="8" style="padding:0;">
                            <div class="meal-divider">
                                <span class="meal-divider-icon" style="color:var(--brand);">&#9679;</span>
                                <span class="meal-divider-name"><?= e($activeCatHit['name']) ?></span>
                                <span class="meal-divider-range"><?= $activeCatHit['start_time'] ?> &ndash; <?= $activeCatHit['end_time'] ?></span>
                            </div>
                        </td>
                    </tr>
                    <?php elseif ($showInactiveDivider): ?>
                    <tr>
                        <td colspan="8" style="padding:0;">
                            <div class="meal-divider" style="background:#f8f8f8; opacity:.6;">
                                <span class="meal-divider-icon" style="color:#adb5bd;">&#9676;</span>
                                <span class="meal-divider-name" style="color:#adb5bd;"><?= e($inactiveCatHit['name']) ?></span>
                                <span class="meal-divider-range"><?= $inactiveCatHit['start_time'] ?> &ndash; <?= $inactiveCatHit['end_time'] ?></span>
                                <span class="inactive-tag">DISATTIVATO</span>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr<?= $rowClass ? ' class="' . $rowClass . '"' : '' ?>>
                        <td<?= $isInactive ? ' style="color:#ccc;"' : '' ?>>
                            <?= $time ?>
                            <?php if ($isPhantom): ?>
                                <i class="bi bi-exclamation-triangle" style="color:#F57F17;font-size:.7rem;"></i>
                            <?php elseif ($isInactive): ?>
                                <span class="inactive-tag"><?= e($inactiveCat) ?></span>
                            <?php endif; ?>
                        </td>
                        <?php for ($day = 0; $day < 7; $day++):
                            $currentVal = 0;
                            if (isset($slotsByDay[$day])) {
                                foreach ($slotsByDay[$day] as $s) {
                                    if (substr($s['slot_time'], 0, 5) === $time && $s['is_active']) {
                                        $currentVal = (int)$s['max_covers'];
                                        break;
                                    }
                                }
                            }
                            $isDisabled = $isPhantom || $isInactive;
                            $inputClass = 'slot-input';
                            if (!$isDisabled) {
                                $inputClass .= ($currentVal > 0) ? ' has-value' : ' zero';
                            }
                        ?>
                        <td>
                            <input type="number" class="<?= $inputClass ?>"
                                   <?= $isDisabled ? '' : 'name="slots[' . $day . '][' . $time . ']"' ?>
                                   value="<?= $isInactive ? 0 : $currentVal ?>"
                                   min="0" max="200"
                                   <?php if ($isPhantom): ?>disabled title="Verrà rimosso al salvataggio"
                                   <?php elseif ($isInactive): ?>readonly tabindex="-1" title="Categoria &quot;<?= e($inactiveCat) ?>&quot; disattivata"
                                   <?php endif; ?>>
                        </td>
                        <?php endfor; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Save bar -->
        <div class="save-bar" style="border-radius:0 0 12px 12px; border-top:1px solid #e9ecef; box-shadow:none;">
            <span class="save-hint">
                <i class="bi bi-info-circle me-1"></i>
                <?php if (!empty($phantomTimes)): ?>
                    <?= count($phantomTimes) ?> orari phantom verranno rimossi al salvataggio
                <?php else: ?>
                    Le modifiche saranno visibili immediatamente
                <?php endif; ?>
            </span>
            <button type="submit" class="btn-save"><i class="bi bi-check-circle me-1"></i> Salva Configurazione</button>
        </div>
    </div>
</form>

<script nonce="<?= csp_nonce() ?>">
// Fill all buttons
document.querySelectorAll('.tb-btn[data-fill]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var val = this.dataset.fill;
        document.querySelectorAll('.slot-input:not([disabled]):not([readonly])').forEach(function(input) {
            input.value = val;
            input.className = 'slot-input ' + (parseInt(val) > 0 ? 'has-value' : 'zero');
        });
    });
});
// Input styling on change
document.querySelectorAll('.slot-input:not([disabled]):not([readonly])').forEach(function(input) {
    input.addEventListener('change', function() {
        var v = parseInt(this.value) || 0;
        this.className = 'slot-input ' + (v > 0 ? 'has-value' : 'zero');
    });
});
</script>