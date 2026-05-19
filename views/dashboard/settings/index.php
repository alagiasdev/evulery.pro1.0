<?php
/**
 * Impostazioni — hub a griglia. Una card per gruppo, ogni voce porta alla
 * sua pagina. Sorgente unica della navigazione: settings_nav().
 */
$groups = settings_nav();
$groupMeta = [
    'Operatività'      => ['icon' => 'bi-sliders',   'sub' => 'Come funziona il servizio'],
    'Servizi clienti'  => ['icon' => 'bi-people',    'sub' => 'Cosa offri a chi prenota'],
    'Brand'            => ['icon' => 'bi-megaphone', 'sub' => 'Come ti presenti online'],
];
?>

<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">Impostazioni</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1.25rem;">Configura il tuo ristorante — scegli un'area</p>

<div class="set-grid">
    <?php foreach ($groups as $label => $items): ?>
    <div class="set-card">
        <div class="set-card-head">
            <span class="set-card-ic"><i class="bi <?= e($groupMeta[$label]['icon'] ?? 'bi-gear') ?>"></i></span>
            <span>
                <span class="set-card-tit"><?= e($label) ?></span>
                <span class="set-card-sub"><?= e($groupMeta[$label]['sub'] ?? '') ?></span>
            </span>
        </div>
        <?php foreach ($items as $it): ?>
        <?php $locked = !empty($it['service']) && !tenant_can($it['service']); ?>
        <a class="set-item" href="<?= $it['url'] ?>">
            <span class="set-item-ic"><i class="bi <?= e($it['icon']) ?>"></i></span>
            <span class="set-item-body">
                <span class="set-item-name">
                    <?= e($it['label']) ?>
                    <?php if ($locked): ?><span class="set-lock" title="Non incluso nel tuo piano"><i class="bi bi-lock-fill"></i></span><?php endif; ?>
                </span>
                <span class="set-item-desc"><?= e($it['desc'] ?? '') ?></span>
            </span>
            <i class="bi bi-chevron-right set-item-arrow"></i>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
</div>
