<?php
$settingsTabs = [
    ['url' => url('dashboard/settings'),                'icon' => 'bi-gear',          'label' => 'Generali',         'key' => 'settings'],
    ['url' => url('dashboard/settings/slots'),          'icon' => 'bi-clock',         'label' => 'Orari e Coperti',  'key' => 'slots'],
    ['url' => url('dashboard/settings/meal-categories'),'icon' => 'bi-tags',          'label' => 'Categorie Pasto',  'key' => 'meal-categories'],
    ['url' => url('dashboard/settings/closures'),       'icon' => 'bi-calendar-x',    'label' => 'Chiusure',         'key' => 'closures'],
    ['url' => url('dashboard/settings/promotions'),     'icon' => 'bi-percent',       'label' => 'Promozioni',       'key' => 'promotions'],
    ['url' => url('dashboard/settings/notifications'),  'icon' => 'bi-bell',          'label' => 'Notifiche',        'key' => 'settings-notifications'],
    ['url' => url('dashboard/settings/deposit'),        'icon' => 'bi-cash',          'label' => 'Caparra',          'key' => 'deposit'],
    ['url' => url('dashboard/settings/ordering'),       'icon' => 'bi-bag-check',     'label' => 'Ordini online',    'key' => 'settings-ordering'],
    ['url' => url('dashboard/settings/reviews'),       'icon' => 'bi-star',       'label' => 'Recensioni',       'key' => 'settings-reviews'],
    ['url' => url('dashboard/settings/domain'),         'icon' => 'bi-globe',         'label' => 'Dominio',          'key' => 'domain'],
];
$orderingHours = json_decode($tenant['ordering_hours'] ?? '{}', true) ?: [];
$dayNames = ['1' => 'Lunedì', '2' => 'Martedì', '3' => 'Mercoledì', '4' => 'Giovedì', '5' => 'Venerdì', '6' => 'Sabato', '7' => 'Domenica'];
$payments = explode(',', $tenant['ordering_payment_methods'] ?? 'cash');
$showDelivery = in_array($tenant['ordering_mode'] ?? '', ['delivery', 'both']);
$isZones = ($tenant['delivery_mode'] ?? '') === 'zones';
?>

<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">Impostazioni</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1rem;">Configura il tuo ristorante</p>

<!-- Settings tabs -->
<div class="settings-tabs-wrap"><div class="scroll-hint"><i class="bi bi-arrows"></i></div><div class="settings-tabs">
    <?php foreach ($settingsTabs as $tab): ?>
    <a href="<?= $tab['url'] ?>" class="settings-tab <?= $tab['key'] === 'settings-ordering' ? 'active' : '' ?>">
        <i class="bi <?= $tab['icon'] ?>"></i> <span class="tab-label"><?= $tab['label'] ?></span>
    </a>
    <?php endforeach; ?>
</div></div>

<?php if (!$canUse): ?>
    <?php $lockedTitle = 'Ordini online'; include BASE_PATH . '/views/partials/service-locked.php'; ?>
<?php else: ?>

<div class="row g-4">
    <!-- ===== LEFT COLUMN ===== -->
    <div class="col-lg-7">
        <form method="POST" action="<?= url('dashboard/settings/ordering') ?>">
            <?= csrf_field() ?>

            <!-- 1. Master toggle -->
            <div class="card section-card mb-3">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="section-icon" style="background:var(--brand-light, #e8f5e9); color:var(--brand, #00844A); width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem;">
                            <i class="bi bi-bag-check"></i>
                        </div>
                        <div>
                            <div style="font-weight:700; font-size:.9rem;">Abilita ordini online</div>
                            <div style="font-size:.72rem; color:#6c757d;">I clienti possono ordinare dal tuo store</div>
                            <?php if ($tenant['ordering_enabled']): ?>
                            <div style="font-size:.72rem; color:var(--brand, #00844A); margin-top:2px;"><i class="bi bi-link-45deg"></i> /<?= e($tenant['slug']) ?>/order</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-check form-switch" style="font-size:1.4rem;">
                        <input class="form-check-input" type="checkbox" id="ordering_enabled" name="ordering_enabled" value="1"
                            <?= $tenant['ordering_enabled'] ? 'checked' : '' ?>>
                    </div>
                </div>
            </div>

            <!-- 2. Modalità ordini -->
            <div class="card section-card mb-3">
                <div class="section-header">
                    <div class="section-icon" style="background:var(--brand, #00844A);"><i class="bi bi-sliders"></i></div>
                    <div>
                        <div class="section-title">Modalit&agrave; ordini</div>
                        <div class="section-subtitle">Tipo di servizio, tempi e limiti</div>
                    </div>
                </div>
                <div class="form-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="field-label">Tipo di servizio</label>
                            <select class="form-select form-select-sm" name="ordering_mode" id="orderingMode">
                                <option value="takeaway" <?= ($tenant['ordering_mode'] ?? '') === 'takeaway' ? 'selected' : '' ?>>Solo asporto</option>
                                <option value="delivery" <?= ($tenant['ordering_mode'] ?? '') === 'delivery' ? 'selected' : '' ?>>Solo consegna</option>
                                <option value="both" <?= ($tenant['ordering_mode'] ?? '') === 'both' ? 'selected' : '' ?>>Asporto + Consegna</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label">Ordine minimo</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">&euro;</span>
                                <input type="number" class="form-control" name="ordering_min_amount" step="0.50" min="0"
                                    value="<?= number_format((float)($tenant['ordering_min_amount'] ?? 0), 2, '.', '') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="field-label">Tempo preparazione</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control" name="ordering_prep_minutes" min="5" max="180"
                                    value="<?= (int)($tenant['ordering_prep_minutes'] ?? 30) ?>">
                                <span class="input-group-text">min</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Intervallo slot</label>
                            <select class="form-select form-select-sm" name="ordering_pickup_interval">
                                <?php foreach ([10, 15, 20, 30, 60] as $int): ?>
                                <option value="<?= $int ?>" <?= (int)($tenant['ordering_pickup_interval'] ?? 15) === $int ? 'selected' : '' ?>><?= $int ?> min</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Max per slot</label>
                            <input type="number" class="form-control form-control-sm" name="ordering_max_per_slot" min="1" max="100"
                                value="<?= (int)($tenant['ordering_max_per_slot'] ?? 10) ?>">
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="ordering_auto_accept" name="ordering_auto_accept" value="1"
                                <?= !empty($tenant['ordering_auto_accept']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ordering_auto_accept" style="font-size:.8rem;">Accetta ordini automaticamente</label>
                        </div>
                        <div style="font-size:.68rem; color:#adb5bd; margin-top:.15rem;">Se attivo, gli ordini vengono confermati senza intervento manuale</div>
                    </div>
                </div>
            </div>

            <!-- 3. Orari ordini -->
            <div class="card section-card mb-3">
                <div class="section-header">
                    <div class="section-icon" style="background:#1565C0;"><i class="bi bi-clock"></i></div>
                    <div>
                        <div class="section-title">Orari ordini</div>
                        <div class="section-subtitle">Fasce orarie in cui accetti ordini. Se vuoti, usa gli orari di apertura</div>
                    </div>
                </div>
                <div class="form-body">
                    <?php foreach ($dayNames as $dayNum => $dayName): ?>
                    <?php
                        $dayOpen = $orderingHours[$dayNum]['open'] ?? '';
                        $dayClose = $orderingHours[$dayNum]['close'] ?? '';
                    ?>
                    <div class="row g-2 align-items-center mb-2">
                        <div class="col-3 col-md-3">
                            <span style="font-size:.8rem; font-weight:600;"><?= $dayName ?></span>
                        </div>
                        <div class="col-4 col-md-3">
                            <input type="time" class="form-control form-control-sm" name="oh_open_<?= $dayNum ?>" value="<?= e($dayOpen) ?>" placeholder="Apertura">
                        </div>
                        <div class="col-1 text-center" style="font-size:.75rem; color:#adb5bd;">&ndash;</div>
                        <div class="col-4 col-md-3">
                            <input type="time" class="form-control form-control-sm" name="oh_close_<?= $dayNum ?>" value="<?= e($dayClose) ?>" placeholder="Chiusura">
                        </div>
                        <div class="col-md-2 d-none d-md-block">
                            <?php if ($dayOpen && $dayClose): ?>
                            <span class="badge bg-success" style="font-size:.65rem;">Attivo</span>
                            <?php else: ?>
                            <span class="badge bg-secondary" style="font-size:.65rem;">Chiuso</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div style="font-size:.68rem; color:#adb5bd; margin-top:.5rem;">
                        <i class="bi bi-info-circle me-1"></i> Lascia vuoti i campi per i giorni in cui non accetti ordini. Se tutti vuoti, verranno usati gli orari di apertura del ristorante.
                    </div>
                    <input type="hidden" name="ordering_hours" id="orderingHoursJson" value="">
                </div>
            </div>

            <!-- 4. Pagamenti -->
            <div class="card section-card mb-3">
                <div class="section-header">
                    <div class="section-icon" style="background:#5C6BC0;"><i class="bi bi-credit-card"></i></div>
                    <div>
                        <div class="section-title">Metodi di pagamento</div>
                        <div class="section-subtitle">Come i clienti possono pagare</div>
                    </div>
                </div>
                <div class="form-body">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="payment_cash" name="payment_cash" value="1"
                            <?= in_array('cash', $payments) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="payment_cash" style="font-size:.82rem;"><i class="bi bi-cash me-1 text-success"></i> Contanti al ritiro</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="payment_stripe" name="payment_stripe" value="1"
                            <?= in_array('stripe', $payments) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="payment_stripe" style="font-size:.82rem;"><i class="bi bi-stripe me-1" style="color:#635BFF;"></i> Carta online (Stripe)</label>
                    </div>
                    <?php if (empty($tenant['stripe_sk'])): ?>
                    <div style="font-size:.68rem; color:#adb5bd; margin-top:.5rem;">Per pagamenti online, configura Stripe nella sezione <a href="<?= url('dashboard/settings/deposit') ?>" style="color:var(--brand, #00844A);">Caparra</a>.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 5. Consegna -->
            <div class="card section-card mb-3" id="deliverySettings" style="<?= $showDelivery ? '' : 'display:none' ?>">
                <div class="section-header">
                    <div class="section-icon" style="background:#E65100;"><i class="bi bi-truck"></i></div>
                    <div>
                        <div class="section-title">Consegna a domicilio</div>
                        <div class="section-subtitle">Tariffe, zone e limiti per la delivery</div>
                    </div>
                </div>
                <div class="form-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="field-label">Modalit&agrave;</label>
                            <select class="form-select form-select-sm" name="delivery_mode" id="deliveryMode">
                                <option value="simple" <?= ($tenant['delivery_mode'] ?? '') === 'simple' ? 'selected' : '' ?>>Tariffa fissa</option>
                                <option value="zones" <?= ($tenant['delivery_mode'] ?? '') === 'zones' ? 'selected' : '' ?>>Zone per CAP</option>
                            </select>
                        </div>
                        <div class="col-md-4" id="simpleDeliveryFee">
                            <label class="field-label">Costo consegna</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">&euro;</span>
                                <input type="number" class="form-control" name="delivery_fee" step="0.50" min="0"
                                    value="<?= number_format((float)($tenant['delivery_fee'] ?? 0), 2, '.', '') ?>">
                            </div>
                            <div style="font-size:.65rem; color:#adb5bd;">Usato se tariffa fissa</div>
                        </div>
                        <div class="col-md-4" id="simpleDeliveryMin">
                            <label class="field-label">Min ordine consegna</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">&euro;</span>
                                <input type="number" class="form-control" name="delivery_min_amount" step="0.50" min="0"
                                    value="<?= number_format((float)($tenant['delivery_min_amount'] ?? 0), 2, '.', '') ?>">
                            </div>
                            <div style="font-size:.65rem; color:#adb5bd;">Usato se tariffa fissa</div>
                        </div>
                    </div>
                    <div class="mt-3" id="simpleDeliveryDesc">
                        <label class="field-label">Descrizione consegna</label>
                        <textarea class="form-control form-control-sm" name="delivery_description" rows="2" placeholder="Es: Consegniamo in zona Milano centro"><?= e($tenant['delivery_description'] ?? '') ?></textarea>
                        <div style="font-size:.65rem; color:#adb5bd;">Visibile ai clienti nello store</div>
                    </div>
                </div>
            </div>

            <!-- 6. Board fattorino -->
            <div class="card section-card mb-3" id="deliveryBoardSettings" style="<?= $showDelivery ? '' : 'display:none' ?>">
                <div class="section-header">
                    <div class="section-icon" style="background:#E65100;"><i class="bi bi-truck"></i></div>
                    <div>
                        <div class="section-title">Board Fattorino</div>
                        <div class="section-subtitle">Link e PIN per il fattorino</div>
                    </div>
                </div>
                <div class="form-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="delivery_board_enabled" name="delivery_board_enabled" value="1"
                            <?= !empty($tenant['delivery_board_enabled']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="delivery_board_enabled" style="font-size:.82rem;">Abilita board consegne</label>
                    </div>

                    <?php if (!empty($tenant['delivery_board_token'])): ?>
                    <?php $boardUrl = url("delivery/{$tenant['delivery_board_token']}"); ?>
                    <div style="background:#f8f9fa; border-radius:10px; padding:.75rem; margin-bottom:.75rem;">
                        <div style="font-size:.7rem; font-weight:600; color:#6c757d; margin-bottom:.25rem;"><i class="bi bi-link-45deg me-1"></i> Link board</div>
                        <div style="font-size:.78rem; word-break:break-all; margin-bottom:.5rem;"><?= e($boardUrl) ?></div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-copy="<?= e($boardUrl) ?>" style="font-size:.72rem;">
                            <i class="bi bi-clipboard"></i> Copia link
                        </button>
                    </div>

                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="field-label">PIN accesso</label>
                            <input type="text" class="form-control form-control-sm" name="delivery_board_pin"
                                value="<?= e($tenant['delivery_board_pin'] ?? '') ?>" maxlength="6"
                                pattern="[0-9]*" inputmode="numeric" placeholder="Es: 4821">
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnGeneratePin" style="font-size:.72rem;">
                                <i class="bi bi-shuffle me-1"></i> Genera PIN casuale
                            </button>
                        </div>
                    </div>
                    <div style="font-size:.65rem; color:#adb5bd; margin-top:.5rem;">
                        <i class="bi bi-info-circle me-1"></i> Condividi link e PIN con il fattorino. Puoi rigenerare il link cliccando "Rigenera token" nel salvataggio.
                    </div>
                    <?php else: ?>
                    <div style="font-size:.78rem; color:#6c757d;">
                        <i class="bi bi-info-circle me-1"></i> Salva le impostazioni con "Board fattorino" abilitata per generare il link.
                    </div>
                    <?php endif; ?>

                    <div class="mt-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="regenerate_token" name="regenerate_token" value="1">
                            <label class="form-check-label" for="regenerate_token" style="font-size:.75rem; color:#6c757d;">Rigenera token (il link cambier&agrave;)</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save bar -->
            <div class="save-bar">
                <span class="save-hint"><i class="bi bi-info-circle me-1"></i>Le modifiche saranno visibili immediatamente</span>
                <button type="submit" class="btn-save"><i class="bi bi-check-circle me-1"></i> Salva impostazioni</button>
            </div>
        </form>

        <!-- Zone di consegna (separate form, inside left column) -->
        <?php if ($showDelivery && $isZones): ?>
        <div class="card section-card mt-4">
            <div class="section-header">
                <div class="section-icon" style="background:#E65100;"><i class="bi bi-geo-alt"></i></div>
                <div>
                    <div class="section-title">Zone di consegna</div>
                    <div class="section-subtitle">Ogni zona ha CAP, costo e minimo ordine specifici</div>
                </div>
            </div>
            <div class="form-body">
                <!-- Existing zones -->
                <?php if (!empty($deliveryZones)): ?>
                <?php foreach ($deliveryZones as $zone): ?>
                <div class="d-flex align-items-center gap-2 py-2" style="border-bottom:1px solid #f0f0f0;">
                    <div style="min-width:90px;">
                        <div style="font-weight:600; font-size:.82rem;"><?= e($zone['name']) ?></div>
                    </div>
                    <div style="flex:1; font-size:.72rem; color:#6c757d;"><?= e(implode(', ', $zone['postal_codes'])) ?></div>
                    <div style="font-size:.82rem; font-weight:600; white-space:nowrap;">&euro; <?= number_format((float)$zone['fee'], 2, ',', '.') ?></div>
                    <div style="font-size:.72rem; color:#6c757d; white-space:nowrap;">min &euro;<?= number_format((float)$zone['min_amount'], 2, ',', '.') ?></div>
                    <?php if ($zone['is_active']): ?>
                    <span class="badge bg-success" style="font-size:.65rem;">Attiva</span>
                    <?php else: ?>
                    <span class="badge bg-secondary" style="font-size:.65rem;">No</span>
                    <?php endif; ?>
                    <form method="POST" action="<?= url("dashboard/settings/ordering/zones/{$zone['id']}/delete") ?>" class="d-inline" data-confirm="Eliminare questa zona?">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-danger" style="padding:2px 6px;"><i class="bi bi-trash" style="font-size:.72rem;"></i></button>
                    </form>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <p style="font-size:.78rem; color:#adb5bd; margin-bottom:.75rem;">Nessuna zona configurata. Aggiungi la prima zona di consegna.</p>
                <?php endif; ?>

                <!-- Add zone form -->
                <div style="background:#f8f9fa; border-radius:10px; padding:1rem; margin-top:.75rem;">
                    <div style="font-size:.78rem; font-weight:600; margin-bottom:.5rem;"><i class="bi bi-plus-circle me-1"></i> Aggiungi zona</div>
                    <form method="POST" action="<?= url('dashboard/settings/ordering/zones') ?>">
                        <?= csrf_field() ?>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label style="font-size:.7rem; font-weight:600; color:#495057; display:block; margin-bottom:.2rem;">Nome</label>
                                <input type="text" class="form-control form-control-sm" name="name" placeholder="Es: Centro" required>
                            </div>
                            <div class="col-md-3">
                                <label style="font-size:.7rem; font-weight:600; color:#495057; display:block; margin-bottom:.2rem;">CAP</label>
                                <input type="text" class="form-control form-control-sm" name="postal_codes" placeholder="00186, 00187..." required>
                            </div>
                            <div class="col-md-2">
                                <label style="font-size:.7rem; font-weight:600; color:#495057; display:block; margin-bottom:.2rem;">Costo (&euro;)</label>
                                <input type="number" class="form-control form-control-sm" name="fee" step="0.50" min="0" placeholder="3.00">
                            </div>
                            <div class="col-md-2">
                                <label style="font-size:.7rem; font-weight:600; color:#495057; display:block; margin-bottom:.2rem;">Min (&euro;)</label>
                                <input type="number" class="form-control form-control-sm" name="min_amount" step="0.50" min="0" placeholder="15.00">
                            </div>
                            <div class="col-md-2">
                                <input type="hidden" name="is_active" value="1">
                                <button type="submit" class="btn btn-sm btn-success w-100"><i class="bi bi-plus-lg"></i> Aggiungi</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ===== RIGHT COLUMN ===== -->
    <div class="col-lg-5">
        <div class="card section-card" style="position:sticky; top:1rem;">
            <div class="card-body">
                <div style="font-size:.82rem; font-weight:700; margin-bottom:.75rem;"><i class="bi bi-lightbulb me-1" style="color:#FFC107;"></i> Come funziona</div>
                <div class="d-flex gap-2 mb-2">
                    <div style="width:24px; height:24px; border-radius:50%; background:var(--brand, #00844A); color:#fff; font-size:.68rem; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0;">1</div>
                    <div style="font-size:.75rem; color:#495057;"><strong>Abilita</strong> gli ordini online e scegli il tipo di servizio</div>
                </div>
                <div class="d-flex gap-2 mb-2">
                    <div style="width:24px; height:24px; border-radius:50%; background:var(--brand, #00844A); color:#fff; font-size:.68rem; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0;">2</div>
                    <div style="font-size:.75rem; color:#495057;">Nel <strong>Menu</strong>, abilita "Ordinabile online" sui piatti che vuoi vendere</div>
                </div>
                <div class="d-flex gap-2 mb-2">
                    <div style="width:24px; height:24px; border-radius:50%; background:var(--brand, #00844A); color:#fff; font-size:.68rem; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0;">3</div>
                    <div style="font-size:.75rem; color:#495057;">I clienti ordinano da <strong>/<?= e($tenant['slug']) ?>/order</strong> e tu gestisci dalla sezione <strong>Ordini</strong></div>
                </div>
                <div class="d-flex gap-2 mb-3">
                    <div style="width:24px; height:24px; border-radius:50%; background:var(--brand, #00844A); color:#fff; font-size:.68rem; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0;">4</div>
                    <div style="font-size:.75rem; color:#495057;">Accetta/rifiuta, segui la <strong>preparazione</strong> e segna come completato</div>
                </div>
                <hr>
                <div style="font-size:.72rem; color:#6c757d;">
                    <div class="mb-1"><i class="bi bi-clock me-1"></i> <strong>Slot:</strong> generati dagli orari ordini + tempo di preparazione</div>
                    <div class="mb-1"><i class="bi bi-truck me-1"></i> <strong>Zone CAP:</strong> ogni zona ha costo e minimo indipendenti</div>
                    <div><i class="bi bi-bell me-1"></i> <strong>Notifiche:</strong> email e campanella per ogni nuovo ordine</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<script nonce="<?= csp_nonce() ?>">
document.addEventListener('DOMContentLoaded', function() {
    var modeSelect = document.getElementById('orderingMode');
    var deliverySettings = document.getElementById('deliverySettings');
    var deliveryModeSelect = document.getElementById('deliveryMode');
    var simpleFields = ['simpleDeliveryFee', 'simpleDeliveryMin', 'simpleDeliveryDesc'];

    var deliveryBoardSettings = document.getElementById('deliveryBoardSettings');

    if (modeSelect) {
        modeSelect.addEventListener('change', function() {
            var show = this.value === 'delivery' || this.value === 'both';
            if (deliverySettings) deliverySettings.style.display = show ? '' : 'none';
            if (deliveryBoardSettings) deliveryBoardSettings.style.display = show ? '' : 'none';
        });
    }

    // Generate random PIN
    var btnGeneratePin = document.getElementById('btnGeneratePin');
    if (btnGeneratePin) {
        btnGeneratePin.addEventListener('click', function() {
            var pin = String(Math.floor(1000 + Math.random() * 9000));
            var pinInput = document.querySelector('[name="delivery_board_pin"]');
            if (pinInput) pinInput.value = pin;
        });
    }

    // Copy link
    document.querySelectorAll('[data-copy]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var self = this;
            navigator.clipboard.writeText(self.dataset.copy).then(function() {
                self.innerHTML = '<i class="bi bi-check"></i> Copiato!';
                setTimeout(function() { self.innerHTML = '<i class="bi bi-clipboard"></i> Copia link'; }, 2000);
            });
        });
    });

    if (deliveryModeSelect) {
        deliveryModeSelect.addEventListener('change', function() {
            var isSimple = this.value === 'simple';
            simpleFields.forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.style.display = isSimple ? '' : 'none';
            });
        });
        // Init visibility
        if (deliveryModeSelect.value === 'zones') {
            simpleFields.forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.style.display = 'none';
            });
        }
    }

    // Serialize ordering_hours JSON before submit
    var form = document.querySelector('form[action*="settings/ordering"]');
    if (form) {
        form.addEventListener('submit', function() {
            var hours = {};
            for (var d = 1; d <= 7; d++) {
                var open = form.querySelector('[name="oh_open_' + d + '"]');
                var close = form.querySelector('[name="oh_close_' + d + '"]');
                if (open && close && open.value && close.value) {
                    hours[d] = { open: open.value, close: close.value };
                }
            }
            var hidden = document.getElementById('orderingHoursJson');
            if (hidden) hidden.value = JSON.stringify(hours);
        });
    }
});
</script>
