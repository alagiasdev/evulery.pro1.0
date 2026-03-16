<?php
$settingsTabs = [
    ['url' => url('dashboard/settings'),                'icon' => 'bi-gear',  'label' => 'Generali',         'key' => 'settings'],
    ['url' => url('dashboard/settings/slots'),          'icon' => 'bi-clock', 'label' => 'Orari e Coperti',  'key' => 'slots'],
    ['url' => url('dashboard/settings/meal-categories'),'icon' => 'bi-tags',  'label' => 'Categorie Pasto',  'key' => 'meal-categories'],
    ['url' => url('dashboard/settings/deposit'),        'icon' => 'bi-cash',  'label' => 'Caparra',          'key' => 'deposit'],
    ['url' => url('dashboard/settings/domain'),         'icon' => 'bi-globe', 'label' => 'Dominio',          'key' => 'domain'],
];

// Category styling map
$catStyles = [
    'brunch'    => ['color' => '#FF9800', 'bg' => '#FFF3E0', 'text' => '#E65100', 'icon' => 'bi-cup-hot'],
    'pranzo'    => ['color' => '#4CAF50', 'bg' => '#E8F5E9', 'text' => '#2E7D32', 'icon' => 'bi-sun'],
    'aperitivo' => ['color' => '#FF9800', 'bg' => '#FFF3E0', 'text' => '#E65100', 'icon' => 'bi-cup-straw'],
    'cena'         => ['color' => '#5C6BC0', 'bg' => '#E8EAF6', 'text' => '#3949AB', 'icon' => 'bi-moon-stars'],
    'after_dinner' => ['color' => '#7E57C2', 'bg' => '#EDE7F6', 'text' => '#4527A0', 'icon' => 'bi-stars'],
];
$defaultStyle = ['color' => '#757575', 'bg' => '#F5F5F5', 'text' => '#616161', 'icon' => 'bi-clock'];

// Timeline calculations (9h-24h span to cover after_dinner)
$tlStart = 9; $tlEnd = 24; $tlSpan = $tlEnd - $tlStart;
?>

<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">Impostazioni</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1rem;">Configura il tuo ristorante</p>

<!-- Settings tabs -->
<div class="settings-tabs">
    <?php foreach ($settingsTabs as $tab): ?>
    <a href="<?= $tab['url'] ?>" class="settings-tab <?= $tab['key'] === 'meal-categories' ? 'active' : '' ?>">
        <i class="bi <?= $tab['icon'] ?>"></i> <?= $tab['label'] ?>
    </a>
    <?php endforeach; ?>
</div>

<form method="POST" action="<?= url('dashboard/settings/meal-categories') ?>">
    <?= csrf_field() ?>
    <div class="row g-4">
        <div class="col-lg-7">

            <p style="font-size:.82rem;color:#6c757d;margin-bottom:1rem;">
                Le categorie raggruppano gli orari nel widget di prenotazione (es. "Pranzo", "Cena").
            </p>

            <?php foreach ($categories as $i => $cat):
                $style = $catStyles[$cat['name']] ?? $defaultStyle;
                $isActive = (bool)$cat['is_active'];
                $borderColor = $isActive ? $style['color'] : '#9E9E9E';
                $iconBg = $isActive ? $style['bg'] : '#F5F5F5';
                $iconColor = $isActive ? $style['text'] : '#757575';
            ?>
            <div class="cat-card <?= $isActive ? '' : 'inactive' ?>" style="border-left-color:<?= $borderColor ?>;">
                <input type="hidden" name="categories[<?= $i ?>][name]" value="<?= e($cat['name']) ?>">
                <div class="cat-header" data-cat-toggle="<?= $i ?>">
                    <i class="bi bi-grip-vertical cat-drag"></i>
                    <div class="cat-icon" style="background:<?= $iconBg ?>;color:<?= $iconColor ?>;">
                        <i class="bi <?= $style['icon'] ?>"></i>
                    </div>
                    <div class="cat-info">
                        <div class="cat-name"><?= e($cat['display_name']) ?></div>
                        <div class="cat-key"><?= e($cat['name']) ?></div>
                    </div>
                    <div class="cat-time-range">
                        <span><?= substr($cat['start_time'], 0, 5) ?></span>
                        <i class="bi bi-arrow-right"></i>
                        <span><?= substr($cat['end_time'], 0, 5) ?></span>
                    </div>
                    <div class="cat-actions">
                        <input type="hidden" name="categories[<?= $i ?>][is_active]" value="0" id="cat-hidden-<?= $i ?>">
                        <div class="cat-toggle <?= $isActive ? 'on' : '' ?>" data-cat-active="<?= $i ?>"></div>
                    </div>
                </div>
                <!-- Edit form (hidden by default) -->
                <div class="cat-edit" style="display:none;" id="cat-edit-<?= $i ?>">
                    <div class="edit-grid">
                        <div>
                            <label class="edit-label">Nome visualizzato</label>
                            <input type="text" class="edit-input" name="categories[<?= $i ?>][display_name]" value="<?= e($cat['display_name']) ?>" required>
                        </div>
                        <div>
                            <label class="edit-label">Chiave (sistema)</label>
                            <input type="text" class="edit-input" value="<?= e($cat['name']) ?>" readonly style="background:#f8f9fa;color:#adb5bd;">
                        </div>
                        <div>
                            <label class="edit-label">Ora inizio</label>
                            <input type="time" class="edit-input" name="categories[<?= $i ?>][start_time]" value="<?= e(substr($cat['start_time'], 0, 5)) ?>" required>
                        </div>
                        <div>
                            <label class="edit-label">Ora fine</label>
                            <input type="time" class="edit-input" name="categories[<?= $i ?>][end_time]" value="<?= e(substr($cat['end_time'], 0, 5)) ?>" required>
                        </div>
                    </div>
                    <input type="hidden" name="categories[<?= $i ?>][sort_order]" value="<?= (int)$cat['sort_order'] ?>">
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Save bar -->
            <div class="save-bar">
                <span class="save-hint"><i class="bi bi-info-circle me-1"></i>Le categorie determinano come gli slot appaiono nel widget</span>
                <button type="submit" class="btn-save"><i class="bi bi-check-circle me-1"></i> Salva Categorie</button>
            </div>

        </div>

        <!-- Right: Timeline preview -->
        <div class="col-lg-5">

            <div class="timeline-preview">
                <div class="timeline-label"><i class="bi bi-eye me-1"></i> Anteprima giornata</div>
                <div class="timeline-bar">
                    <?php
                    $prevEnd = $tlStart;
                    foreach ($categories as $cat):
                        $cStart = (int)substr($cat['start_time'], 0, 2) + (int)substr($cat['start_time'], 3, 2) / 60;
                        $cEnd = (int)substr($cat['end_time'], 0, 2) + (int)substr($cat['end_time'], 3, 2) / 60;
                        $style = $catStyles[$cat['name']] ?? $defaultStyle;
                        $left = (($cStart - $tlStart) / $tlSpan) * 100;
                        $width = (($cEnd - $cStart) / $tlSpan) * 100;

                        // Gap before this category?
                        if ($cStart > $prevEnd) {
                            $gapLeft = (($prevEnd - $tlStart) / $tlSpan) * 100;
                            $gapWidth = (($cStart - $prevEnd) / $tlSpan) * 100;
                            if ($gapWidth > 2): ?>
                    <div class="timeline-gap" style="left:<?= round($gapLeft, 1) ?>%;width:<?= round($gapWidth, 1) ?>%;">
                        <?php if ($gapWidth > 8): ?><span class="gap-label">gap</span><?php endif; ?>
                    </div>
                    <?php endif;
                        }
                    ?>
                    <div class="timeline-segment" style="left:<?= round($left, 1) ?>%;width:<?= round($width, 1) ?>%;background:<?= $cat['is_active'] ? $style['color'] : '#BDBDBD' ?>;<?= $cat['is_active'] ? '' : 'opacity:.5;' ?>">
                        <?= e($cat['display_name']) ?>
                    </div>
                    <?php
                        $prevEnd = max($prevEnd, $cEnd);
                    endforeach; ?>
                </div>
                <div class="timeline-hours">
                    <?php for ($h = $tlStart; $h <= $tlEnd; $h += 2): ?>
                    <span class="timeline-hour"><?= sprintf('%02d', $h) ?></span>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Info card -->
            <div class="card" style="margin-top:.75rem;">
                <div class="tip-card">
                    <i class="bi bi-lightbulb" style="color:#FFC107;font-size:1rem;margin-top:.1rem;"></i>
                    <div>
                        <div class="tip-title">Come funzionano le categorie</div>
                        <ul style="font-size:.78rem;color:#6c757d;line-height:1.6;padding-left:1rem;margin:0;">
                            <li>Il widget mostra gli slot raggruppati per categoria (es. <strong>Pranzo</strong>, <strong>Cena</strong>)</li>
                            <li>Le categorie disattivate nascondono i relativi slot nella tabella <strong>Orari e Coperti</strong></li>
                            <li>Slot senza categoria finiscono nel gruppo <strong>"Altro"</strong></li>
                            <li>L'ordine qui determina l'ordine nel widget</li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>
</form>

<script>
// Toggle active/inactive
document.querySelectorAll('.cat-toggle').forEach(function(toggle) {
    toggle.addEventListener('click', function(e) {
        e.stopPropagation();
        this.classList.toggle('on');
        var card = this.closest('.cat-card');
        var idx = this.dataset.catActive;
        var hidden = document.getElementById('cat-hidden-' + idx);
        if (this.classList.contains('on')) {
            card.classList.remove('inactive');
            hidden.value = '1';
        } else {
            card.classList.add('inactive');
            hidden.value = '0';
        }
    });
});
// Initialize hidden values for active categories
document.querySelectorAll('.cat-toggle.on').forEach(function(toggle) {
    var idx = toggle.dataset.catActive;
    var hidden = document.getElementById('cat-hidden-' + idx);
    if (hidden) hidden.value = '1';
});
// Click card header to expand/collapse edit
document.querySelectorAll('.cat-header').forEach(function(header) {
    header.addEventListener('click', function(e) {
        if (e.target.closest('.cat-toggle') || e.target.closest('.cat-drag')) return;
        var idx = this.dataset.catToggle;
        var edit = document.getElementById('cat-edit-' + idx);
        if (edit) {
            edit.style.display = edit.style.display === 'none' ? 'block' : 'none';
        }
    });
});
</script>