<?php
/**
 * Settings tabs — barra di navigazione tra le pagine di /dashboard/settings/*.
 *
 * Centralizza l'array dei tab (prima duplicato in 12 view) e li raggruppa in
 * 3 cluster logici con mini-label e divider verticali.
 *
 * Uso:
 *   <?php $activeKey = 'settings-hub'; include __DIR__ . '/../partials/settings-tabs.php'; ?>
 *
 * Per aggiungere un nuovo tab: aggiungilo nel gruppo appropriato qui sotto e
 * passa la $activeKey corrispondente dalla view.
 */
$settingsGroups = settings_nav();   // single source of truth (app/Helpers/functions.php)
$activeKey = $activeKey ?? '';
?>
<div class="settings-tabs-wrap">
    <div class="scroll-hint"><i class="bi bi-arrows"></i></div>
    <div class="settings-tabs">
        <?php foreach ($settingsGroups as $groupLabel => $tabs): ?>
        <div class="settings-group">
            <div class="settings-group-header"><?= e($groupLabel) ?></div>
            <div class="settings-group-tabs">
                <?php foreach ($tabs as $tab): ?>
                <a href="<?= $tab['url'] ?>" class="settings-tab <?= $tab['key'] === $activeKey ? 'active' : '' ?>">
                    <i class="bi <?= $tab['icon'] ?>"></i> <span class="tab-label"><?= $tab['label'] ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
