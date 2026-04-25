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
$settingsGroups = [
    'Operatività' => [
        ['url' => url('dashboard/settings'),                 'icon' => 'bi-gear',         'label' => 'Generali',        'key' => 'settings'],
        ['url' => url('dashboard/settings/slots'),           'icon' => 'bi-clock',        'label' => 'Orari e Coperti', 'key' => 'slots'],
        ['url' => url('dashboard/settings/meal-categories'), 'icon' => 'bi-tags',         'label' => 'Categorie Pasto', 'key' => 'meal-categories'],
        ['url' => url('dashboard/settings/closures'),        'icon' => 'bi-calendar-x',   'label' => 'Chiusure',        'key' => 'closures'],
    ],
    'Servizi clienti' => [
        ['url' => url('dashboard/settings/promotions'),      'icon' => 'bi-percent',      'label' => 'Promozioni',      'key' => 'promotions'],
        ['url' => url('dashboard/settings/deposit'),         'icon' => 'bi-cash',         'label' => 'Caparra',         'key' => 'deposit'],
        ['url' => url('dashboard/settings/ordering'),        'icon' => 'bi-bag-check',    'label' => 'Ordini online',   'key' => 'settings-ordering'],
        ['url' => url('dashboard/settings/reviews'),         'icon' => 'bi-star',         'label' => 'Recensioni',      'key' => 'settings-reviews'],
    ],
    'Brand' => [
        ['url' => url('dashboard/settings/notifications'),   'icon' => 'bi-bell',         'label' => 'Notifiche',          'key' => 'settings-notifications'],
        ['url' => url('dashboard/settings/hub'),             'icon' => 'bi-grid-3x3-gap', 'label' => 'Vetrina Digitale',   'key' => 'settings-hub'],
        ['url' => url('dashboard/settings/domain'),          'icon' => 'bi-globe',        'label' => 'Dominio',            'key' => 'domain'],
    ],
];
$activeKey = $activeKey ?? '';
?>
<div class="settings-tabs-wrap">
    <div class="scroll-hint"><i class="bi bi-arrows"></i></div>
    <div class="settings-tabs">
        <?php $first = true; foreach ($settingsGroups as $groupLabel => $tabs): ?>
            <?php if (!$first): ?>
            <div class="settings-tabs-divider" aria-hidden="true"></div>
            <?php endif; $first = false; ?>
            <span class="settings-tabs-grouplabel"><?= e($groupLabel) ?></span>
            <?php foreach ($tabs as $tab): ?>
            <a href="<?= $tab['url'] ?>" class="settings-tab <?= $tab['key'] === $activeKey ? 'active' : '' ?>">
                <i class="bi <?= $tab['icon'] ?>"></i> <span class="tab-label"><?= $tab['label'] ?></span>
            </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
</div>
