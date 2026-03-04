<div class="bw-widget" id="booking-widget">

    <!-- Social Proof Banner -->
    <div class="bw-social-proof" id="social-proof" style="display:none;">
        <span class="bw-fire">&#128293;</span>
        <span id="social-proof-text"></span>
    </div>

    <!-- Step Progress Bar -->
    <div class="bw-progress" id="step-progress">
        <div class="bw-progress-step active" data-step="1" id="prog-1">
            <span class="bw-step-icon"><i class="bi bi-calendar3"></i></span>
            <span class="bw-step-label">Data</span>
            <span class="bw-pill" id="pill-date" style="display:none;"></span>
        </div>
        <div class="bw-progress-divider"><i class="bi bi-chevron-right"></i></div>
        <div class="bw-progress-step" data-step="2" id="prog-2">
            <span class="bw-step-icon"><i class="bi bi-clock"></i></span>
            <span class="bw-step-label">Orario</span>
            <span class="bw-pill" id="pill-time" style="display:none;"></span>
        </div>
        <div class="bw-progress-divider"><i class="bi bi-chevron-right"></i></div>
        <div class="bw-progress-step" data-step="3" id="prog-3">
            <span class="bw-step-icon"><i class="bi bi-people"></i></span>
            <span class="bw-step-label">Persone</span>
            <span class="bw-pill" id="pill-party" style="display:none;"></span>
        </div>
        <div class="bw-progress-divider"><i class="bi bi-chevron-right"></i></div>
        <div class="bw-progress-step" data-step="4" id="prog-4">
            <span class="bw-step-icon"><i class="bi bi-person-lines-fill"></i></span>
            <span class="bw-step-label">Dati</span>
        </div>
    </div>

    <!-- Step 1: Calendar -->
    <div class="bw-step" id="step-1">
        <div class="bw-calendar" id="calendar-widget">
            <div class="bw-cal-header">
                <button type="button" class="bw-cal-nav" id="cal-prev">&#8249;</button>
                <span class="bw-cal-month" id="cal-month-label"></span>
                <button type="button" class="bw-cal-nav" id="cal-next">&#8250;</button>
            </div>
            <div class="bw-cal-days-header" id="cal-days-header"></div>
            <div class="bw-cal-grid" id="cal-grid"></div>
        </div>
    </div>

    <!-- Step 2: Time Slots Grouped -->
    <div class="bw-step" id="step-2" style="display:none;">
        <button type="button" class="bw-back" id="btn-back-1">
            <i class="bi bi-chevron-left"></i> Indietro
        </button>
        <h5 class="bw-step-title">Scegli l'orario</h5>
        <div id="grouped-slots-container">
            <div class="bw-loading-inline"><div class="spinner-border spinner-border-sm"></div> Caricamento orari...</div>
        </div>
    </div>

    <!-- Step 3: Party Size -->
    <div class="bw-step" id="step-3" style="display:none;">
        <button type="button" class="bw-back" id="btn-back-2">
            <i class="bi bi-chevron-left"></i> Indietro
        </button>
        <h5 class="bw-step-title">Numero di persone</h5>
        <div class="bw-party-grid" id="party-grid"></div>
        <div class="bw-more-options" id="party-more-toggle">
            <a href="#" id="party-more-link">Opzioni per piu persone <i class="bi bi-plus"></i></a>
        </div>
        <div class="bw-party-grid bw-party-extended" id="party-extended" style="display:none;"></div>
    </div>

    <!-- Step 4: Contact Form -->
    <div class="bw-step" id="step-4" style="display:none;">
        <button type="button" class="bw-back" id="btn-back-3">
            <i class="bi bi-chevron-left"></i> Indietro
        </button>
        <h5 class="bw-step-title">I tuoi dati</h5>
        <div class="bw-form">
            <div class="bw-form-row">
                <div class="bw-form-group">
                    <label for="booking-first-name">Nome *</label>
                    <input type="text" id="booking-first-name" placeholder="Mario" autocomplete="given-name">
                </div>
                <div class="bw-form-group">
                    <label for="booking-last-name">Cognome *</label>
                    <input type="text" id="booking-last-name" placeholder="Rossi" autocomplete="family-name">
                </div>
            </div>
            <div class="bw-form-group">
                <label for="booking-phone">Telefono *</label>
                <input type="tel" id="booking-phone" placeholder="+39 333 1234567" autocomplete="tel">
            </div>
            <div class="bw-form-group">
                <label for="booking-email">Email *</label>
                <input type="email" id="booking-email" placeholder="mario@email.it" autocomplete="email">
            </div>
            <div class="bw-form-group">
                <label for="booking-notes">Note e richieste</label>
                <textarea id="booking-notes" class="bw-textarea" rows="3" placeholder="Es: Allergie, intolleranze, seggiolone, compleanno, richieste particolari..."></textarea>
            </div>

            <?php if (!empty($tenant['cancellation_policy'])): ?>
            <div class="bw-policy">
                <i class="bi bi-info-circle"></i>
                <?= e($tenant['cancellation_policy']) ?>
            </div>
            <?php endif; ?>

            <?php if ($tenant['deposit_enabled'] && $tenant['deposit_amount']): ?>
            <div class="bw-deposit-info">
                <i class="bi bi-credit-card"></i>
                Caparra richiesta: <strong>&euro;<?= number_format($tenant['deposit_amount'], 2) ?></strong>
            </div>
            <?php endif; ?>

            <button type="button" class="bw-submit" id="btn-submit">
                Conferma Prenotazione
            </button>
        </div>
    </div>

    <!-- Confirmation -->
    <div class="bw-step bw-confirmation" id="step-confirm" style="display:none;">
        <div class="bw-confirm-icon">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        <h3>Prenotazione Confermata!</h3>
        <div id="confirmation-details"></div>
        <a href="<?= url($slug) ?>" class="bw-btn-secondary">Nuova prenotazione</a>
    </div>

    <!-- Loading Overlay -->
    <div class="bw-loading-overlay" id="loading" style="display:none;">
        <div class="spinner-border text-success"></div>
        <p>Elaborazione in corso...</p>
    </div>

    <!-- Error -->
    <div class="bw-error" id="error-container" style="display:none;">
        <div class="bw-error-inner">
            <i class="bi bi-exclamation-triangle"></i>
            <span id="error-message"></span>
        </div>
    </div>
</div>

<script>
window.BOOKING_CONFIG = {
    slug: '<?= e($slug) ?>',
    apiUrl: '<?= url('api/v1') ?>',
    depositEnabled: <?= $tenant['deposit_enabled'] ? 'true' : 'false' ?>,
    depositAmount: <?= $tenant['deposit_amount'] ? number_format($tenant['deposit_amount'], 2, '.', '') : '0' ?>,
    advanceMin: <?= (int)($tenant['booking_advance_min'] ?? 0) ?>,
    advanceMax: <?= (int)($tenant['booking_advance_max'] ?? 60) ?>
};
</script>
