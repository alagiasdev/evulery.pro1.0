<!-- Summary Bar -->
<div class="dr-summary-bar" id="dr-summary-bar">
    <i class="bi bi-lightning-charge"></i>
    <span class="dr-summary-item" id="dr-sum-date">--</span>
    <span class="dr-summary-dot">&bull;</span>
    <span class="dr-summary-item" id="dr-sum-party">--</span>
    <span class="dr-summary-dot">&bull;</span>
    <span class="dr-summary-item" id="dr-sum-time">--</span>
    <span class="dr-summary-dot">&bull;</span>
    <span class="dr-summary-item" id="dr-sum-customer">--</span>
</div>

<div class="page-back">
    <a href="<?= url('dashboard/reservations') ?>">
        <i class="bi bi-arrow-left"></i> Torna alle prenotazioni
    </a>
</div>

<form method="POST" action="<?= url('dashboard/reservations') ?>" id="dr-form">
    <?= csrf_field() ?>

    <div class="dr-form-grid">

        <!-- ===== LEFT COLUMN ===== -->
        <div>

            <!-- 1. Data -->
            <div class="section-card">
                <div class="section-body">
                    <div class="dr-section-label">
                        <span class="section-num">1</span>
                        <i class="bi bi-calendar3"></i> Data
                    </div>
                    <div class="dr-date-grid">
                        <button type="button" class="dr-date-btn dr-date-quick" data-offset="0">
                            <span class="dr-date-label">Oggi</span>
                            <span class="dr-date-sub" id="dr-date-oggi"></span>
                        </button>
                        <button type="button" class="dr-date-btn dr-date-quick" data-offset="1">
                            <span class="dr-date-label">Domani</span>
                            <span class="dr-date-sub" id="dr-date-domani"></span>
                        </button>
                        <button type="button" class="dr-date-btn dr-date-quick" data-offset="2">
                            <span class="dr-date-label">Dopodomani</span>
                            <span class="dr-date-sub" id="dr-date-dopodomani"></span>
                        </button>
                        <button type="button" class="dr-date-btn dr-date-other-btn" id="dr-date-other">
                            <i class="bi bi-calendar3"></i>
                            <span class="dr-date-label">Altra data</span>
                            <span class="dr-date-sub"></span>
                        </button>
                    </div>
                    <!-- Mini Calendar -->
                    <div class="dr-calendar-wrap" id="dr-calendar-wrap" style="display:none">
                        <div class="dr-cal-header">
                            <button type="button" class="dr-cal-nav" id="dr-cal-prev"><i class="bi bi-chevron-left"></i></button>
                            <span class="dr-cal-month" id="dr-cal-month-label"></span>
                            <button type="button" class="dr-cal-nav" id="dr-cal-next"><i class="bi bi-chevron-right"></i></button>
                        </div>
                        <div class="dr-cal-days-header">
                            <div class="dr-cal-day-name">lun</div>
                            <div class="dr-cal-day-name">mar</div>
                            <div class="dr-cal-day-name">mer</div>
                            <div class="dr-cal-day-name">gio</div>
                            <div class="dr-cal-day-name">ven</div>
                            <div class="dr-cal-day-name">sab</div>
                            <div class="dr-cal-day-name">dom</div>
                        </div>
                        <div class="dr-cal-grid" id="dr-cal-grid"></div>
                    </div>
                    <input type="hidden" name="reservation_date" id="dr-date-value"
                           value="<?= old('reservation_date', date('Y-m-d')) ?>">
                </div>
            </div>

            <!-- 2. Coperti -->
            <div class="section-card">
                <div class="section-body">
                    <div class="dr-section-label">
                        <span class="section-num">2</span>
                        <i class="bi bi-people"></i> Coperti
                    </div>
                    <div class="dr-party-grid" id="dr-party-grid">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                        <button type="button" class="dr-party-btn" data-size="<?= $i ?>"><?= $i ?></button>
                        <?php endfor; ?>
                    </div>
                    <div class="dr-party-more-options">
                        <a href="#" id="dr-party-more-link">Opzioni per pi&ugrave; persone <i class="bi bi-plus"></i></a>
                    </div>
                    <div class="dr-party-extended" id="dr-party-extended" style="display:none">
                        <?php for ($i = 13; $i <= 20; $i++): ?>
                        <button type="button" class="dr-party-btn" data-size="<?= $i ?>"><?= $i ?></button>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="party_size" id="dr-party-value"
                           value="<?= old('party_size', '2') ?>">
                </div>
            </div>

            <!-- 3. Cliente -->
            <div class="section-card">
                <div class="section-body">
                    <div class="dr-section-label">
                        <span class="section-num">3</span>
                        <i class="bi bi-person-lines-fill"></i> Cliente
                    </div>

                    <!-- Ricerca -->
                    <div class="dr-customer-search" style="margin-bottom:.75rem;">
                        <div class="dr-search-input-wrap">
                            <i class="bi bi-search dr-search-icon"></i>
                            <input type="text" class="dr-search-input" id="dr-customer-q"
                                   placeholder="Cerca per nome o telefono..." autocomplete="off">
                        </div>
                        <div class="dr-customer-results" id="dr-customer-results" style="display:none;"></div>
                    </div>

                    <!-- Badge segmento -->
                    <div id="dr-customer-badge" style="display:none;" class="mb-3"></div>

                    <!-- Campi manuali -->
                    <div class="dr-customer-fields" id="dr-customer-fields">
                        <input type="text" class="dr-field-input" name="first_name"
                               id="dr-first-name" placeholder="Nome *"
                               value="<?= old('first_name') ?>" required>
                        <input type="text" class="dr-field-input" name="last_name"
                               id="dr-last-name" placeholder="Cognome *"
                               value="<?= old('last_name') ?>" required>
                        <input type="tel" class="dr-field-input" name="phone"
                               id="dr-phone" placeholder="Telefono *"
                               value="<?= old('phone') ?>" required>
                        <input type="email" class="dr-field-input" name="email"
                               id="dr-email" placeholder="Email *"
                               value="<?= old('email') ?>" required>
                    </div>
                </div>
            </div>

            <!-- 4. Sorgente -->
            <div class="section-card">
                <div class="section-body">
                    <div class="dr-section-label">
                        <span class="section-num">4</span>
                        <i class="bi bi-telephone"></i> Sorgente
                    </div>
                    <div class="dr-source-grid">
                        <button type="button" class="dr-source-btn active" data-source="phone">
                            <i class="bi bi-telephone-fill"></i>
                            <span>Telefono</span>
                        </button>
                        <button type="button" class="dr-source-btn" data-source="walkin">
                            <i class="bi bi-door-open"></i>
                            <span>Walk-in</span>
                        </button>
                        <button type="button" class="dr-source-btn" data-source="altro">
                            <i class="bi bi-three-dots"></i>
                            <span>Altro</span>
                        </button>
                    </div>
                    <input type="hidden" name="source" id="dr-source-value" value="phone">
                </div>
            </div>

            <!-- 5. Note + Submit -->
            <div class="section-card">
                <div class="section-body">
                    <div class="dr-section-label">
                        <span class="section-num">5</span>
                        <i class="bi bi-chat-text"></i> Note
                    </div>
                    <textarea class="dr-notes-area" name="customer_notes" id="dr-notes" rows="2"
                              placeholder="Allergie, intolleranze, seggiolone, richieste particolari..."><?= old('customer_notes') ?></textarea>
                </div>

                <!-- Submit -->
                <div class="dr-submit-section">
                    <div class="dr-force-check">
                        <input type="checkbox" name="force_booking" id="dr-force" value="1">
                        <label for="dr-force">Forza prenotazione (ignora disponibilit&agrave;)</label>
                    </div>
                    <div>
                        <button type="submit" class="dr-btn-submit" id="dr-submit-btn">
                            <i class="bi bi-check-circle me-2"></i>Salva Prenotazione
                        </button>
                        <a href="<?= url('dashboard/reservations') ?>" class="dr-btn-cancel">Annulla</a>
                    </div>
                </div>
            </div>

        </div>

        <!-- ===== RIGHT COLUMN: TIME SLOTS ===== -->
        <div>
            <div class="dr-slots-card">
                <div class="dr-slots-header">
                    <span class="section-num"><i class="bi bi-clock" style="font-size:.7rem;"></i></span>
                    <h6>Orario disponibile</h6>
                </div>
                <div id="dr-slots-container">
                    <div class="dr-slots-empty-state">
                        <p>Seleziona data e coperti per vedere gli orari disponibili.</p>
                    </div>
                </div>
                <input type="hidden" name="reservation_time" id="dr-time-value"
                       value="<?= old('reservation_time') ?>">
                <noscript>
                    <div style="padding:1rem;">
                        <label style="font-size:.82rem;font-weight:600;">Orario *</label>
                        <input type="time" class="dr-field-input" name="reservation_time"
                               value="<?= old('reservation_time', '19:00') ?>" required>
                    </div>
                </noscript>
            </div>
        </div>

    </div>
</form>

<script>
window.DR_CONFIG = {
    apiUrl: '<?= url('api/v1') ?>',
    tenantSlug: '<?= e($tenantSlug) ?>',
    customerSearchUrl: '<?= url('dashboard/customers/search/json') ?>'
    <?php if (!empty($prefillCustomer)): ?>
    , prefillCustomer: {
        firstName: <?= json_encode($prefillCustomer['first_name']) ?>,
        lastName: <?= json_encode($prefillCustomer['last_name']) ?>,
        email: <?= json_encode($prefillCustomer['email']) ?>,
        phone: <?= json_encode($prefillCustomer['phone']) ?>,
        notes: <?= json_encode($prefillCustomer['notes'] ?? '') ?>
    }
    <?php endif; ?>
};
</script>