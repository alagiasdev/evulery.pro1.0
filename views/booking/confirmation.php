<?php
// Format reservation details if available
$hasDetails = !empty($reservation);
$MONTHS_IT = ['','Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
$DAYS_IT = ['Domenica','Lunedì','Martedì','Mercoledì','Giovedì','Venerdì','Sabato'];

if ($hasDetails) {
    $ts = strtotime($reservation['reservation_date']);
    $dateFormatted = $DAYS_IT[(int)date('w', $ts)] . ' ' . (int)date('d', $ts) . ' ' . $MONTHS_IT[(int)date('n', $ts)];
    $timeFormatted = substr($reservation['reservation_time'], 0, 5);
    $partySize = (int)$reservation['party_size'];
    $resId = (int)$reservation['id'];
    $depositAmount = $reservation['deposit_amount'] ? number_format((float)$reservation['deposit_amount'], 2, ',', '.') : null;
}
?>

<div class="bw-conf-card">
    <div class="bw-conf-header" id="bw-conf-header">
        <div class="bw-conf-check">
            <i class="bi bi-check-lg"></i>
        </div>
        <div class="bw-conf-title">Prenotazione Confermata!</div>
        <div class="bw-conf-subtitle">Riceverai una conferma via email</div>
    </div>

    <div class="bw-conf-body">
        <?php if ($hasDetails): ?>
        <!-- Riepilogo -->
        <div class="bw-conf-grid">
            <div class="bw-conf-detail">
                <div class="bw-conf-detail-icon"><i class="bi bi-calendar3"></i></div>
                <div class="bw-conf-detail-value"><?= e($dateFormatted) ?></div>
                <div class="bw-conf-detail-label">Data</div>
            </div>
            <div class="bw-conf-detail">
                <div class="bw-conf-detail-icon"><i class="bi bi-clock"></i></div>
                <div class="bw-conf-detail-value"><?= $timeFormatted ?></div>
                <div class="bw-conf-detail-label">Orario</div>
            </div>
            <div class="bw-conf-detail">
                <div class="bw-conf-detail-icon"><i class="bi bi-people"></i></div>
                <div class="bw-conf-detail-value"><?= $partySize ?> <?= $partySize === 1 ? 'persona' : 'persone' ?></div>
                <div class="bw-conf-detail-label">Coperti</div>
            </div>
            <div class="bw-conf-detail">
                <div class="bw-conf-detail-icon"><i class="bi bi-hash"></i></div>
                <div class="bw-conf-detail-value">#<?= $resId ?></div>
                <div class="bw-conf-detail-label">Prenotazione</div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($depositPaid) && $depositAmount): ?>
        <!-- Caparra pagata -->
        <div class="bw-conf-deposit">
            <div class="bw-conf-deposit-icon"><i class="bi bi-shield-check"></i></div>
            <div class="bw-conf-deposit-text">
                Caparra di <strong>&euro;<?= $depositAmount ?></strong> pagata con successo
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($tenant['booking_instructions'])): ?>
        <!-- Istruzioni ristorante -->
        <div class="bw-conf-instructions">
            <div class="bw-conf-instructions-title"><i class="bi bi-megaphone me-1"></i> Informazioni dal ristorante</div>
            <div class="bw-conf-instructions-text"><?= nl2br(e($tenant['booking_instructions'])) ?></div>
        </div>
        <?php endif; ?>

        <!-- Nota -->
        <div class="bw-conf-note">
            <i class="bi bi-info-circle"></i>
            <span>Controlla la tua email per il riepilogo completo con il link per gestire la prenotazione.</span>
        </div>

        <!-- Azioni -->
        <div class="bw-conf-actions">
            <?php if (!empty($tenant['menu_enabled'])): ?>
            <a href="<?= url(($tenant['slug'] ?? '') . '/menu') ?>" class="bw-conf-btn-primary">
                <i class="bi bi-book"></i> Consulta il menu
            </a>
            <?php endif; ?>
            <a href="<?= url($tenant['slug'] ?? '') ?>" class="bw-conf-btn-secondary">
                <i class="bi bi-plus-circle"></i> Prenota un altro tavolo
            </a>
        </div>
    </div>

    <div class="bw-conf-footer">
        &copy; <?= date('Y') ?> Evulery &middot; by alagias. - Soluzioni per il web
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
// Confetti animation
(function() {
    var h = document.getElementById('bw-conf-header');
    if (!h) return;
    var colors = ['#FFD700','#FF6B6B','#4ECDC4','#45B7D1','#96CEB4','#FFEAA7'];
    for (var i = 0; i < 12; i++) {
        var d = document.createElement('div');
        d.className = 'bw-confetti';
        d.style.left = (10 + Math.random() * 80) + '%';
        d.style.top = (20 + Math.random() * 40) + '%';
        d.style.background = colors[Math.floor(Math.random() * colors.length)];
        d.style.animationDelay = (Math.random() * .5) + 's';
        d.style.width = d.style.height = (4 + Math.random() * 4) + 'px';
        h.appendChild(d);
    }
})();
</script>
