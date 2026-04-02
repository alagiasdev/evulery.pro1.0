<?php
$settingsTabs = [
    ['url' => url('dashboard/settings'),                'icon' => 'bi-gear',       'label' => 'Generali',         'key' => 'settings'],
    ['url' => url('dashboard/settings/slots'),          'icon' => 'bi-clock',      'label' => 'Orari e Coperti',  'key' => 'slots'],
    ['url' => url('dashboard/settings/meal-categories'),'icon' => 'bi-tags',       'label' => 'Categorie Pasto',  'key' => 'meal-categories'],
    ['url' => url('dashboard/settings/closures'),       'icon' => 'bi-calendar-x', 'label' => 'Chiusure',         'key' => 'closures'],
    ['url' => url('dashboard/settings/promotions'),     'icon' => 'bi-percent',    'label' => 'Promozioni',       'key' => 'promotions'],
    ['url' => url('dashboard/settings/notifications'),  'icon' => 'bi-bell',       'label' => 'Notifiche',        'key' => 'settings-notifications'],
    ['url' => url('dashboard/settings/deposit'),        'icon' => 'bi-cash',       'label' => 'Caparra',          'key' => 'deposit'],
    ['url' => url('dashboard/settings/ordering'),       'icon' => 'bi-bag-check',  'label' => 'Ordini online',    'key' => 'settings-ordering'],
    ['url' => url('dashboard/settings/reviews'),       'icon' => 'bi-star',       'label' => 'Recensioni',       'key' => 'settings-reviews'],
    ['url' => url('dashboard/settings/domain'),         'icon' => 'bi-globe',      'label' => 'Dominio',          'key' => 'domain'],
];

$defaults = [
    'notif_title_new_reservation' => 'Nuova prenotazione',
    'notif_body_new_reservation'  => '{nome} — {data} ore {ora}, {coperti} persone',
    'notif_title_cancellation'    => 'Prenotazione cancellata',
    'notif_body_cancellation'     => '{nome} ({data} ore {ora}) — annullata {da}',
    'notif_title_deposit'         => 'Caparra ricevuta',
    'notif_body_deposit'          => '{nome} — €{importo}',
];
?>

<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">Impostazioni</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1rem;">Configura il tuo ristorante</p>

<!-- Settings tabs -->
<div class="settings-tabs-wrap"><div class="scroll-hint"><i class="bi bi-arrows"></i></div><div class="settings-tabs">
    <?php foreach ($settingsTabs as $tab): ?>
    <a href="<?= $tab['url'] ?>" class="settings-tab <?= $tab['key'] === 'settings-notifications' ? 'active' : '' ?>">
        <i class="bi <?= $tab['icon'] ?>"></i> <span class="tab-label"><?= $tab['label'] ?></span>
    </a>
    <?php endforeach; ?>
</div></div>

<form method="POST" action="<?= url('dashboard/settings/notifications') ?>">
    <?= csrf_field() ?>

    <!-- Email toggles (available for all plans) -->
    <div class="card section-card">
        <div class="section-header">
            <div class="section-icon" style="background:var(--brand);"><i class="bi bi-envelope"></i></div>
            <div>
                <div class="section-title">Notifiche Email</div>
                <div class="section-subtitle">Ricevi un'email quando si verificano questi eventi</div>
            </div>
        </div>
        <div class="form-body">
            <div class="row g-3">
                <div class="col-12 field-row">
                    <div style="display:flex;flex-direction:column;gap:.5rem;">
                        <div class="form-check form-switch" style="padding-left:2.5em;">
                            <input class="form-check-input" type="checkbox" name="notify_new_reservation" value="1"
                                   id="notify-new-res" <?= !empty($tenant['notify_new_reservation']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="notify-new-res" style="font-size:.82rem;font-weight:500;">
                                Nuova prenotazione
                            </label>
                        </div>
                        <div class="form-check form-switch" style="padding-left:2.5em;">
                            <input class="form-check-input" type="checkbox" name="notify_cancellation" value="1"
                                   id="notify-cancel" <?= !empty($tenant['notify_cancellation']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="notify-cancel" style="font-size:.82rem;font-weight:500;">
                                Cancellazione prenotazione
                            </label>
                        </div>
                    </div>
                    <div class="field-hint">Disponibili per tutti i piani.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Push templates -->
    <div class="card section-card" style="margin-top:1rem;">
        <div class="section-header">
            <div class="section-icon" style="background:var(--brand);"><i class="bi bi-bell"></i></div>
            <div>
                <div class="section-title">Template Notifiche Push</div>
                <div class="section-subtitle">Personalizza titolo e testo delle notifiche nel browser e nella campanella</div>
            </div>
        </div>
        <div class="form-body">

            <!-- Placeholders legend -->
            <div style="background:#f0faf4;border:1px solid #c8e6c9;border-radius:8px;padding:10px 14px;margin-bottom:1rem;font-size:.78rem;color:#495057;">
                <strong>Segnaposto disponibili:</strong>
                <code>{nome}</code> nome cliente &middot;
                <code>{data}</code> data prenotazione &middot;
                <code>{ora}</code> orario &middot;
                <code>{coperti}</code> numero persone &middot;
                <code>{importo}</code> importo caparra &middot;
                <code>{da}</code> chi ha cancellato
            </div>

            <!-- New reservation -->
            <div style="border:1px solid #e9ecef;border-radius:10px;padding:1rem;margin-bottom:1rem;">
                <div style="font-weight:600;font-size:.88rem;margin-bottom:.75rem;"><i class="bi bi-plus-circle me-1" style="color:var(--brand);"></i> Nuova prenotazione</div>
                <div class="row g-3">
                    <div class="col-md-4 field-row">
                        <label class="field-label">Titolo</label>
                        <input type="text" class="field-input" name="notif_title_new_reservation"
                               value="<?= e($tenant['notif_title_new_reservation'] ?? '') ?>"
                               placeholder="<?= e($defaults['notif_title_new_reservation']) ?>" maxlength="255">
                    </div>
                    <div class="col-md-8 field-row">
                        <label class="field-label">Testo</label>
                        <input type="text" class="field-input" name="notif_body_new_reservation"
                               value="<?= e($tenant['notif_body_new_reservation'] ?? '') ?>"
                               placeholder="<?= e($defaults['notif_body_new_reservation']) ?>" maxlength="500">
                    </div>
                </div>
            </div>

            <!-- Cancellation -->
            <div style="border:1px solid #e9ecef;border-radius:10px;padding:1rem;margin-bottom:1rem;">
                <div style="font-weight:600;font-size:.88rem;margin-bottom:.75rem;"><i class="bi bi-x-circle me-1" style="color:#dc3545;"></i> Cancellazione</div>
                <div class="row g-3">
                    <div class="col-md-4 field-row">
                        <label class="field-label">Titolo</label>
                        <input type="text" class="field-input" name="notif_title_cancellation"
                               value="<?= e($tenant['notif_title_cancellation'] ?? '') ?>"
                               placeholder="<?= e($defaults['notif_title_cancellation']) ?>" maxlength="255">
                    </div>
                    <div class="col-md-8 field-row">
                        <label class="field-label">Testo</label>
                        <input type="text" class="field-input" name="notif_body_cancellation"
                               value="<?= e($tenant['notif_body_cancellation'] ?? '') ?>"
                               placeholder="<?= e($defaults['notif_body_cancellation']) ?>" maxlength="500">
                    </div>
                </div>
            </div>

            <!-- Deposit received -->
            <div style="border:1px solid #e9ecef;border-radius:10px;padding:1rem;">
                <div style="font-weight:600;font-size:.88rem;margin-bottom:.75rem;"><i class="bi bi-cash-coin me-1" style="color:#E65100;"></i> Caparra ricevuta</div>
                <div class="row g-3">
                    <div class="col-md-4 field-row">
                        <label class="field-label">Titolo</label>
                        <input type="text" class="field-input" name="notif_title_deposit"
                               value="<?= e($tenant['notif_title_deposit'] ?? '') ?>"
                               placeholder="<?= e($defaults['notif_title_deposit']) ?>" maxlength="255">
                    </div>
                    <div class="col-md-8 field-row">
                        <label class="field-label">Testo</label>
                        <input type="text" class="field-input" name="notif_body_deposit"
                               value="<?= e($tenant['notif_body_deposit'] ?? '') ?>"
                               placeholder="<?= e($defaults['notif_body_deposit']) ?>" maxlength="500">
                    </div>
                </div>
            </div>

            <div class="field-hint" style="margin-top:.75rem;">Lascia vuoto per usare il testo predefinito (mostrato come placeholder).</div>
        </div>
    </div>

    <div style="margin-top:1rem;">
        <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Salva impostazioni</button>
    </div>
</form>
