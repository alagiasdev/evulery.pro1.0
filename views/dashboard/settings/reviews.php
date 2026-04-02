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
    ['url' => url('dashboard/settings/reviews'),        'icon' => 'bi-star',          'label' => 'Recensioni',       'key' => 'settings-reviews'],
    ['url' => url('dashboard/settings/domain'),         'icon' => 'bi-globe',         'label' => 'Dominio',          'key' => 'domain'],
];
$reviewEnabled = (int)($tenant['review_enabled'] ?? 0);
$filterEnabled = (int)($tenant['review_filter_enabled'] ?? 1);
$platformLabel = $tenant['review_platform_label'] ?? '';
?>

<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">Impostazioni</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1rem;">Configura il tuo ristorante</p>

<!-- Settings tabs -->
<div class="settings-tabs-wrap"><div class="scroll-hint"><i class="bi bi-arrows"></i></div><div class="settings-tabs">
    <?php foreach ($settingsTabs as $tab): ?>
    <a href="<?= $tab['url'] ?>" class="settings-tab <?= $tab['key'] === 'settings-reviews' ? 'active' : '' ?>">
        <i class="bi <?= $tab['icon'] ?>"></i> <span class="tab-label"><?= $tab['label'] ?></span>
    </a>
    <?php endforeach; ?>
</div></div>

<?php if (!$canUse): ?>
    <?php $lockedTitle = 'Recensioni'; include BASE_PATH . '/views/partials/service-locked.php'; ?>
<?php else: ?>

<div class="row g-4">
    <!-- ===== LEFT COLUMN (form) ===== -->
    <div class="col-lg-7">
        <form method="POST" action="<?= url('dashboard/settings/reviews') ?>">
            <?= csrf_field() ?>

            <!-- 1. Master toggle -->
            <div class="card section-card mb-3">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div style="background:#FFF8E1; color:#F9A825; width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem;">
                            <i class="bi bi-star-fill"></i>
                        </div>
                        <div>
                            <div style="font-weight:700; font-size:.9rem;">Richiedi recensioni ai clienti</div>
                            <div style="font-size:.72rem; color:#6c757d;">Invia automaticamente una richiesta dopo la visita</div>
                        </div>
                    </div>
                    <div class="form-check form-switch" style="margin:0; padding-left:3rem;">
                        <input class="form-check-input" type="checkbox" name="review_enabled" value="1"
                               <?= $reviewEnabled ? 'checked' : '' ?>
                               style="width:2.5rem; height:1.25rem; cursor:pointer;"
                               id="rv-toggle-master">
                    </div>
                </div>
            </div>

            <!-- Content (toggled) -->
            <div id="rv-settings-body" style="<?= $reviewEnabled ? '' : 'display:none;' ?>">

                <!-- 2. Link Recensione -->
                <div class="card section-card mb-3">
                    <div class="card-header d-flex align-items-center gap-2" style="background:none; border-bottom:1px solid #f0f0f0; padding:.85rem 1.1rem;">
                        <div style="background:var(--brand, #00844A); color:#fff; width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.85rem;">
                            <i class="bi bi-link-45deg"></i>
                        </div>
                        <div>
                            <div style="font-size:.85rem; font-weight:700;">Link recensione</div>
                            <div style="font-size:.7rem; color:#6c757d;">Dove vuoi mandare i clienti a lasciare la recensione?</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info py-2" style="font-size:.75rem;">
                            <i class="bi bi-info-circle me-1"></i>
                            Inserisci il link diretto alla tua pagina recensioni. Pu&ograve; essere Google, TripAdvisor, TheFork, o qualsiasi altra piattaforma.
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-size:.78rem; font-weight:600;">URL pagina recensioni *</label>
                            <input type="url" name="review_url" class="form-control form-control-sm"
                                   value="<?= e($tenant['review_url'] ?? '') ?>"
                                   placeholder="https://g.page/r/CxxxxxXXXXX/review">
                            <div class="form-text" style="font-size:.68rem;">Copia il link dalla piattaforma dove vuoi ricevere le recensioni</div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label" style="font-size:.78rem; font-weight:600;">
                                Nome piattaforma <span style="font-weight:400; color:#adb5bd;">(opzionale)</span>
                            </label>
                            <input type="text" name="review_platform_label" class="form-control form-control-sm"
                                   value="<?= e($platformLabel) ?>"
                                   placeholder="Es: Google, TripAdvisor, TheFork...">
                            <div class="form-text" style="font-size:.68rem;">
                                Verr&agrave; mostrato nell'email: "Lascia una recensione su <strong><?= e($platformLabel ?: 'Google') ?></strong>"
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Tempistica -->
                <div class="card section-card mb-3">
                    <div class="card-header d-flex align-items-center gap-2" style="background:none; border-bottom:1px solid #f0f0f0; padding:.85rem 1.1rem;">
                        <div style="background:#7C4DFF; color:#fff; width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.85rem;">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div>
                            <div style="font-size:.85rem; font-weight:700;">Tempistica invio</div>
                            <div style="font-size:.7rem; color:#6c757d;">Quando inviare la richiesta dopo la visita</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" style="font-size:.78rem; font-weight:600;">Invia richiesta dopo</label>
                                <?php $delay = (int)($tenant['review_delay_hours'] ?? 2); ?>
                                <select name="review_delay_hours" class="form-select form-select-sm">
                                    <?php foreach ([1,2,3,4,6,12,24] as $h): ?>
                                    <option value="<?= $h ?>" <?= $delay === $h ? 'selected' : '' ?>>
                                        <?= $h === 24 ? '24 ore (giorno dopo)' : "{$h} or" . ($h === 1 ? 'a' : 'e') ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text" style="font-size:.68rem;">Dalla prenotazione (orario previsto). Raccomandiamo 2-3 ore</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" style="font-size:.78rem; font-weight:600;">Non inviare dopo le</label>
                                <?php $quiet = (int)($tenant['review_quiet_hour'] ?? 22); ?>
                                <select name="review_quiet_hour" class="form-select form-select-sm">
                                    <?php foreach ([21,22,23,0] as $qh): ?>
                                    <option value="<?= $qh ?>" <?= $quiet === $qh ? 'selected' : '' ?>>
                                        <?= $qh === 0 ? 'Nessun limite' : "{$qh}:00" ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text" style="font-size:.68rem;">Evita di disturbare di notte. Verr&agrave; inviata il giorno dopo alle 10:00</div>
                            </div>
                        </div>
                        <hr style="margin:.75rem 0;">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-light text-dark" style="font-size:.7rem;"><i class="bi bi-shield-check me-1" style="color:var(--brand, #00844A);"></i> Max 1 email per prenotazione</span>
                            <span class="badge bg-light text-dark" style="font-size:.7rem;"><i class="bi bi-calendar-check me-1" style="color:var(--brand, #00844A);"></i> Max 1 ogni 30 giorni per cliente</span>
                        </div>
                    </div>
                </div>

                <!-- 4. Filtro sentimento -->
                <div class="card section-card mb-3">
                    <div class="card-header d-flex align-items-center gap-2" style="background:none; border-bottom:1px solid #f0f0f0; padding:.85rem 1.1rem;">
                        <div style="background:#FF7043; color:#fff; width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.85rem;">
                            <i class="bi bi-emoji-smile"></i>
                        </div>
                        <div>
                            <div style="font-size:.85rem; font-weight:700;">Filtro sentimento</div>
                            <div style="font-size:.7rem; color:#6c757d;">Filtra i clienti insoddisfatti prima che lascino una recensione pubblica</div>
                        </div>
                        <div class="ms-auto">
                            <div class="form-check form-switch" style="margin:0; padding-left:2.5rem;">
                                <input class="form-check-input" type="checkbox" name="review_filter_enabled" value="1"
                                       <?= $filterEnabled ? 'checked' : '' ?>
                                       style="width:2.25rem; height:1.15rem; cursor:pointer;"
                                       id="rv-toggle-filter">
                            </div>
                        </div>
                    </div>
                    <div class="card-body" id="rv-filter-body" style="<?= $filterEnabled ? '' : 'display:none;' ?>">
                        <div class="alert alert-warning py-2" style="font-size:.75rem;">
                            <i class="bi bi-lightbulb me-1"></i>
                            Quando attivo, il cliente vede prima una pagina con le stelle. Se la valutazione &egrave; alta viene mandato alla piattaforma; se bassa, compila un feedback privato per te.
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" style="font-size:.78rem; font-weight:600;">Soglia recensione pubblica</label>
                                <?php $threshold = (int)($tenant['review_filter_threshold'] ?? 4); ?>
                                <select name="review_filter_threshold" class="form-select form-select-sm">
                                    <option value="3" <?= $threshold === 3 ? 'selected' : '' ?>>3+ stelle &rarr; piattaforma</option>
                                    <option value="4" <?= $threshold === 4 ? 'selected' : '' ?>>4+ stelle &rarr; piattaforma</option>
                                    <option value="5" <?= $threshold === 5 ? 'selected' : '' ?>>5 stelle &rarr; piattaforma</option>
                                </select>
                                <div class="form-text" style="font-size:.68rem;">Sotto questa soglia il cliente vede il form feedback privato</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" style="font-size:.78rem; font-weight:600;">Messaggio feedback privato</label>
                                <input type="text" name="review_filter_message" class="form-control form-control-sm"
                                       value="<?= e($tenant['review_filter_message'] ?? 'Ci dispiace! Dicci cosa possiamo migliorare') ?>"
                                       placeholder="Messaggio personalizzato...">
                                <div class="form-text" style="font-size:.68rem;">Mostrato ai clienti con valutazione bassa</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. Template email -->
                <div class="card section-card mb-3">
                    <div class="card-header d-flex align-items-center gap-2" style="background:none; border-bottom:1px solid #f0f0f0; padding:.85rem 1.1rem;">
                        <div style="background:#42A5F5; color:#fff; width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.85rem;">
                            <i class="bi bi-envelope-paper"></i>
                        </div>
                        <div>
                            <div style="font-size:.85rem; font-weight:700;">Personalizza email</div>
                            <div style="font-size:.7rem; color:#6c757d;">Modifica il messaggio inviato ai clienti</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" style="font-size:.78rem; font-weight:600;">Oggetto email</label>
                            <input type="text" name="review_email_subject" class="form-control form-control-sm"
                                   value="<?= e($tenant['review_email_subject'] ?? 'Come è andata da {ristorante}?') ?>">
                            <div class="form-text" style="font-size:.68rem;">Variabili: {ristorante}, {nome_cliente}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-size:.78rem; font-weight:600;">Messaggio</label>
                            <textarea name="review_email_body" class="form-control form-control-sm" rows="3" style="resize:vertical;"><?= e($tenant['review_email_body'] ?? "Ciao {nome_cliente},\n\ngrazie per aver cenato da {ristorante}! Ci farebbe piacere sapere come è stata la tua esperienza.") ?></textarea>
                            <div class="form-text" style="font-size:.68rem;">Variabili: {ristorante}, {nome_cliente}, {data_prenotazione}</div>
                        </div>
                        <div>
                            <label class="form-label" style="font-size:.78rem; font-weight:600;">Testo bottone CTA</label>
                            <input type="text" name="review_email_cta" class="form-control form-control-sm"
                                   value="<?= e($tenant['review_email_cta'] ?? 'Lascia una recensione') ?>"
                                   style="max-width:300px;">
                        </div>
                    </div>
                </div>

                <!-- Save -->
                <div class="d-flex justify-content-end gap-2 mb-4">
                    <a href="<?= url('dashboard/settings/reviews') ?>" class="btn btn-outline-secondary btn-sm" style="font-size:.8rem; padding:.45rem 1rem;">Annulla</a>
                    <button type="submit" class="btn btn-success btn-sm" style="font-size:.8rem; padding:.45rem 1.5rem;">
                        <i class="bi bi-check-lg"></i> Salva impostazioni
                    </button>
                </div>

            </div><!-- /rv-settings-body -->
        </form>
    </div>

    <!-- ===== RIGHT COLUMN ===== -->
    <div class="col-lg-5">
        <!-- How it works -->
        <div class="card section-card mb-3">
            <div class="card-body">
                <h6 style="font-size:.82rem; font-weight:700; margin-bottom:.75rem;">
                    <i class="bi bi-info-circle me-1" style="color:var(--brand, #00844A);"></i> Come funziona
                </h6>
                <?php
                $steps = [
                    ['Il cliente cena', 'la prenotazione passa in stato "Arrivato"'],
                    ['Dopo X ore', 'il sistema invia automaticamente l\'email'],
                    ['Filtro sentimento', 'se attivo, il cliente vota prima con le stelle'],
                    ['Recensione o feedback', 'positivi vanno sulla piattaforma, negativi restano privati'],
                ];
                foreach ($steps as $i => $s): ?>
                <div class="d-flex gap-2 mb-2">
                    <div style="width:24px; height:24px; border-radius:50%; background:var(--brand, #00844A); color:#fff; font-size:.68rem; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0;"><?= $i + 1 ?></div>
                    <div style="font-size:.75rem; color:#495057;"><strong style="color:#1a1d23;"><?= $s[0] ?></strong> &mdash; <?= $s[1] ?></div>
                </div>
                <?php endforeach; ?>
                <hr style="margin:.75rem 0;">
                <div style="font-size:.72rem; color:#6c757d;">
                    <i class="bi bi-shield-check" style="color:var(--brand, #00844A);"></i>
                    <strong>Privacy:</strong> Il cliente pu&ograve; disiscriversi in qualsiasi momento. Max 1 email per prenotazione, 1 ogni 30 giorni per cliente.
                </div>
            </div>
        </div>

        <!-- Embed / QR / NFC -->
        <?php
        $reviewDirectUrl = url($tenant['slug'] . '/review?source=qr');
        $reviewEmbedUrl  = url($tenant['slug'] . '/review?embed=1&source=embed');
        ?>
        <div class="card section-card mb-3">
            <div class="section-header">
                <div class="section-icon" style="background:#7C4DFF;"><i class="bi bi-qr-code"></i></div>
                <div>
                    <div class="section-title">Embed / QR / NFC</div>
                    <div class="section-subtitle">Raccogli recensioni anche senza email</div>
                </div>
            </div>
            <div class="form-body">
                <div class="link-label">Link diretto</div>
                <div class="link-box">
                    <span class="link-url" id="rv-direct-url"><?= e($reviewDirectUrl) ?></span>
                    <button type="button" class="link-copy" data-copy-target="rv-direct-url"><i class="bi bi-clipboard me-1"></i>Copia</button>
                </div>

                <div class="link-label" style="margin-top:1rem;">Codice Embed (iframe)</div>
                <div class="embed-code">
                    <button type="button" class="embed-copy" data-copy-text="&lt;iframe src=&quot;<?= e($reviewEmbedUrl) ?>&quot; width=&quot;100%&quot; height=&quot;500&quot; frameborder=&quot;0&quot;&gt;&lt;/iframe&gt;"><i class="bi bi-clipboard me-1"></i>Copia</button>
                    &lt;iframe src="<?= e($reviewEmbedUrl) ?>" width="100%" height="500" frameborder="0"&gt;&lt;/iframe&gt;
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php $pageScripts = ['js/settings-reviews.js']; ?>
