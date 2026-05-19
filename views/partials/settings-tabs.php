<?php
/**
 * Settings sub-nav — navigazione DENTRO una macro-area delle Impostazioni.
 *
 * Mostra solo i tab del gruppo a cui appartiene la pagina corrente (ricavato
 * da $activeKey) + un link "← Impostazioni" per tornare all'hub e cambiare
 * area. Sorgente unica della navigazione: settings_nav() (functions.php).
 *
 * Uso:
 *   <?php $activeKey = 'slots'; include __DIR__ . '/../partials/settings-tabs.php'; ?>
 */
$settingsGroups = settings_nav();
$activeKey = $activeKey ?? '';

// Macro-area della pagina corrente: mostriamo solo i tab di quel gruppo.
$currentGroup = null;
foreach ($settingsGroups as $groupLabel => $groupTabs) {
    foreach ($groupTabs as $t) {
        if ($t['key'] === $activeKey) { $currentGroup = $groupLabel; break 2; }
    }
}
$tabs = $currentGroup !== null ? $settingsGroups[$currentGroup] : [];
?>
<div class="settings-subnav">
    <a href="<?= url('dashboard/settings') ?>" class="settings-back"><i class="bi bi-arrow-left"></i> Impostazioni</a>
    <?php if ($currentGroup !== null): ?>
    <span class="settings-area"><?= e($currentGroup) ?></span>
    <?php endif; ?>
</div>
<div class="settings-tabs-wrap">
    <div class="settings-tabs">
        <?php foreach ($tabs as $tab): ?>
        <a href="<?= $tab['url'] ?>" class="settings-tab <?= $tab['key'] === $activeKey ? 'active' : '' ?>">
            <i class="bi <?= $tab['icon'] ?>"></i> <span class="tab-label"><?= $tab['label'] ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</div>
