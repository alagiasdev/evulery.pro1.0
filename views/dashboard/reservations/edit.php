<!-- Summary Bar -->
<div class="dr-summary-bar" id="dr-summary-bar">
    <i class="bi bi-pencil-square"></i>
    <span class="dr-summary-item" id="dr-sum-date">--</span>
    <span class="dr-summary-divider">&bull;</span>
    <span class="dr-summary-item" id="dr-sum-party">--</span>
    <span class="dr-summary-divider">&bull;</span>
    <span class="dr-summary-item" id="dr-sum-time">--</span>
    <span class="dr-summary-divider">&bull;</span>
    <span class="dr-summary-item dr-filled" id="dr-sum-customer"><?= e($reservation['first_name'] . ' ' . $reservation['last_name']) ?></span>
</div>

<h2 class="mb-4">
    <i class="bi bi-pencil-square me-2"></i>Modifica Prenotazione #<?= (int)$reservation['id'] ?>
</h2>

<form method="POST" action="<?= url("dashboard/reservations/{$reservation['id']}/edit") ?>" id="dr-form">
    <?= csrf_field() ?>

    <div class="row">
    <!-- ===== COLONNA SINISTRA ===== -->
    <div class="col-lg-8">

        <!-- ===== CLIENTE (READ-ONLY) ===== -->
        <div class="card mb-3 dr-section">
            <div class="card-body">
                <h6 class="dr-section-label">
                    <i class="bi bi-person-lines-fill me-2"></i>Cliente
                </h6>
                <div class="row g-2">
                    <div class="col-6">
                        <div class="form-control-plaintext fw-bold"><?= e($reservation['first_name'] . ' ' . $reservation['last_name']) ?></div>
                    </div>
                    <div class="col-6">
                        <div class="form-control-plaintext text-muted">
                            <i class="bi bi-telephone me-1"></i><?= e($reservation['phone']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== DATA ===== -->
        <div class="card mb-3 dr-section" id="dr-section-date">
            <div class="card-body">
                <h6 class="dr-section-label">
                    <i class="bi bi-calendar3 me-2"></i>Data
                </h6>
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
                       value="<?= e($reservation['reservation_date']) ?>">
            </div>
        </div>

        <!-- ===== COPERTI ===== -->
        <div class="card mb-3 dr-section" id="dr-section-party">
            <div class="card-body">
                <h6 class="dr-section-label">
                    <i class="bi bi-people me-2"></i>Coperti
                </h6>
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
                       value="<?= (int)$reservation['party_size'] ?>">
            </div>
        </div>

        <!-- ===== NOTE ===== -->
        <div class="card mb-3 dr-section" id="dr-section-notes">
            <div class="card-body">
                <h6 class="dr-section-label">
                    <i class="bi bi-chat-text me-2"></i>Note
                </h6>
                <textarea class="form-control" name="customer_notes" id="dr-notes" rows="2"
                          placeholder="Allergie, intolleranze, seggiolone, richieste particolari..."><?= e($reservation['customer_notes'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- ===== SUBMIT ===== -->
        <div class="dr-submit-wrap">
            <div>
                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input" name="force_booking" id="dr-force" value="1">
                    <label class="form-check-label text-muted small" for="dr-force">
                        Forza modifica (ignora disponibilit&agrave;)
                    </label>
                </div>
                <button type="submit" class="btn btn-primary dr-submit-btn" id="dr-submit-btn">
                    <i class="bi bi-check-circle me-2"></i>Salva Modifiche
                </button>
                <a href="<?= url("dashboard/reservations/{$reservation['id']}") ?>" class="btn btn-outline-secondary ms-2">
                    Annulla
                </a>
            </div>
        </div>

    </div>

    <!-- ===== COLONNA DESTRA: ORARIO ===== -->
    <div class="col-lg-4">
        <div class="dr-sidebar-sticky">
            <div class="card dr-section" id="dr-section-time">
                <div class="card-body">
                    <h6 class="dr-section-label">
                        <i class="bi bi-clock me-2"></i>Orario
                    </h6>
                    <div id="dr-slots-container">
                        <p class="text-muted small">Seleziona data e coperti per vedere gli orari disponibili.</p>
                    </div>
                    <input type="hidden" name="reservation_time" id="dr-time-value"
                           value="<?= e(substr($reservation['reservation_time'], 0, 5)) ?>">
                    <noscript>
                        <div class="mt-2">
                            <label class="form-label">Orario *</label>
                            <input type="time" class="form-control" name="reservation_time"
                                   value="<?= e(substr($reservation['reservation_time'], 0, 5)) ?>" required>
                        </div>
                    </noscript>
                </div>
            </div>
        </div>
    </div>

    </div>
</form>

<script>
window.DR_CONFIG = {
    apiUrl: '<?= url('api/v1') ?>',
    tenantSlug: '<?= e($tenantSlug) ?>',
    editMode: true,
    preselected: {
        date: '<?= e($reservation['reservation_date']) ?>',
        time: '<?= e(substr($reservation['reservation_time'], 0, 5)) ?>',
        partySize: <?= (int)$reservation['party_size'] ?>
    }
};
</script>
