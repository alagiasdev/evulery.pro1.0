<?php
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

<?php $activeKey = 'meal-categories'; include __DIR__ . '/../../partials/settings-tabs.php'; ?>

<form method="POST" action="<?= url('dashboard/settings/meal-categories') ?>">
    <?= csrf_field() ?>
    <div class="row g-4">
        <div class="col-lg-7">

            <p style="font-size:.82rem;color:#6c757d;margin-bottom:1rem;">
                Le categorie raggruppano gli orari nel widget di prenotazione (es. "Pranzo", "Cena").
            </p>

            <?php $timeStep = (int)($tenant['time_step'] ?? 30); ?>
            <div style="background:#FFF8E1; border-left:3px solid #FFB300; padding:10px 14px; border-radius:8px; margin-bottom:1rem; font-size:.78rem; color:#5D4037; line-height:1.5;">
                <i class="bi bi-info-circle me-1" style="color:#F57F17;"></i>
                <strong>Importante:</strong> gli orari delle categorie devono essere allineati allo <strong>step di prenotazione</strong> configurato in <a href="<?= url('dashboard/settings') ?>" style="color:#E65100; text-decoration:underline;">Generali</a> (attuale: <strong><?= $timeStep ?> minuti</strong>).
                <?php if ($timeStep === 60): ?>
                Con step <strong>60 min</strong>, usa solo orari pieni (es. 12:00, 19:00). Se imposti 11:30, lo slot delle 11:30 non sar&agrave; disponibile.
                <?php elseif ($timeStep === 30): ?>
                Con step <strong>30 min</strong>, puoi usare orari pieni o mezz'ora (es. 11:30, 12:00, 12:30).
                <?php else: ?>
                Con step <strong><?= $timeStep ?> min</strong>, allinea gli orari di inizio/fine ai multipli di <?= $timeStep ?> minuti.
                <?php endif; ?>
            </div>

            <?php
            $canAdvancedTurns = tenant_can('advanced_turns');
            $globalDuration = (int)($tenant['table_duration'] ?? 90);
            ?>
            <?php if (!$canAdvancedTurns): ?>
            <div class="cat-dur-locked">
                <i class="bi bi-lock-fill"></i>
                <span><strong>Durata tavolo per fascia</strong> (es. aperitivo più corto, cena più lunga, weekend diverso)
                è disponibile dal piano <strong>Professional</strong>. Con il piano attuale si usa la durata unica
                di <?= $globalDuration ?> min (Impostazioni → Generali).
                <a href="mailto:<?= e(env('SUPPORT_EMAIL', '')) ?>">Passa a Professional</a></span>
            </div>
            <?php endif; ?>
            <?php
            foreach ($categories as $i => $cat):
                $style = $catStyles[$cat['name']] ?? $defaultStyle;
                $isActive = (bool)$cat['is_active'];
                $borderColor = $isActive ? $style['color'] : '#9E9E9E';
                $iconBg = $isActive ? $style['bg'] : '#F5F5F5';
                $iconColor = $isActive ? $style['text'] : '#757575';
                $catDur     = $cat['duration_minutes'] !== null ? (int)$cat['duration_minutes'] : null;
                $catDurAlt  = $cat['duration_minutes_alt'] !== null ? (int)$cat['duration_minutes_alt'] : null;
                $catAltDays = [];
                if (!empty($cat['duration_alt_days'])) {
                    $dec = json_decode($cat['duration_alt_days'], true);
                    if (is_array($dec)) $catAltDays = array_map('intval', $dec);
                }
                $dayLabels = [1 => 'Lun', 2 => 'Mar', 3 => 'Mer', 4 => 'Gio', 5 => 'Ven', 6 => 'Sab', 7 => 'Dom'];
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
                    <?php if ($canAdvancedTurns && $catDur !== null): ?>
                    <span class="cat-dur-pill" title="Durata tavolo per questa fascia">
                        <i class="bi bi-hourglass-split"></i> <?= $catDur ?> min<?php
                        if ($catDurAlt !== null && !empty($catAltDays)):
                            $lbls = array_map(fn($d) => mb_strtolower($dayLabels[$d] ?? ''), $catAltDays);
                            ?><span class="cat-dur-alt"><?= implode(',', $lbls) ?> <?= $catDurAlt ?>'</span><?php endif; ?>
                    </span>
                    <?php endif; ?>
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

                    <?php if ($canAdvancedTurns): ?>
                    <!-- Durata per fascia (servizio advanced_turns) -->
                    <div class="cat-dur-block">
                        <div class="cat-dur-row">
                            <label class="edit-label" style="margin:0;">
                                <i class="bi bi-hourglass-split" style="color:#F57F17;"></i> Durata tavolo
                            </label>
                            <input type="number" class="edit-input cat-dur-input" name="categories[<?= $i ?>][duration_minutes]"
                                   value="<?= $catDur !== null ? $catDur : '' ?>" min="15" max="300" step="5"
                                   placeholder="<?= $globalDuration ?> (globale)">
                            <span class="cat-dur-unit">min · vuoto = durata globale (<?= $globalDuration ?>)</span>
                        </div>
                        <details class="cat-dur-ovr" <?= ($catDurAlt !== null && !empty($catAltDays)) ? 'open' : '' ?>>
                            <summary>Durata diversa in alcuni giorni <span class="cat-dur-hint">(es. weekend più corto)</span></summary>
                            <div class="cat-dur-ovr-body">
                                <label class="edit-label">Giorni con durata diversa</label>
                                <div class="cat-dur-days">
                                    <?php foreach ($dayLabels as $dnum => $dlbl): ?>
                                    <label class="cat-day-chk <?= in_array($dnum, $catAltDays, true) ? 'sel' : '' ?>">
                                        <input type="checkbox" name="categories[<?= $i ?>][duration_alt_days][]" value="<?= $dnum ?>"
                                               <?= in_array($dnum, $catAltDays, true) ? 'checked' : '' ?>>
                                        <?= $dlbl ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                                <label class="edit-label">Durata in questi giorni</label>
                                <div class="cat-dur-row">
                                    <input type="number" class="edit-input cat-dur-input" name="categories[<?= $i ?>][duration_minutes_alt]"
                                           value="<?= $catDurAlt !== null ? $catDurAlt : '' ?>" min="15" max="300" step="5" placeholder="es. 60">
                                    <span class="cat-dur-unit">min</span>
                                </div>
                            </div>
                        </details>
                    </div>
                    <?php endif; ?>
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

<script nonce="<?= csp_nonce() ?>">
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
