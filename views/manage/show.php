<?php
$status = $reservation['status'];
$canCancel = in_array($status, ['confirmed', 'pending']);
$isPast = strtotime($reservation['reservation_date']) < strtotime(date('Y-m-d'));

$statusLabels = [
    'confirmed' => 'Confermata',
    'pending'   => 'In attesa',
    'arrived'   => 'Arrivato',
    'noshow'    => 'No-show',
    'cancelled' => 'Annullata',
];
$statusLabel = $statusLabels[$status] ?? ucfirst($status);

$DAYS_IT = ['Domenica','Lunedì','Martedì','Mercoledì','Giovedì','Venerdì','Sabato'];
$MONTHS_IT = ['','Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
$ts = strtotime($reservation['reservation_date']);
$dateFormatted = $DAYS_IT[(int)date('w', $ts)] . ' ' . (int)date('j', $ts) . ' ' . $MONTHS_IT[(int)date('n', $ts)] . ' ' . date('Y', $ts);
$time = substr($reservation['reservation_time'], 0, 5);
?>

<div class="manage-card">
    <div class="manage-header" <?= $status === 'cancelled' ? 'style="background:#C62828;"' : ($status === 'pending' ? 'style="background:#E65100;"' : '') ?>>
        <?php if ($status === 'cancelled'): ?>
            <div style="font-size:2rem;margin-bottom:8px;"><i class="bi bi-x-circle"></i></div>
            <h1>Prenotazione Annullata</h1>
        <?php elseif ($status === 'pending'): ?>
            <div style="font-size:2rem;margin-bottom:8px;"><i class="bi bi-hourglass-split"></i></div>
            <h1>In Attesa di Conferma</h1>
            <p>Il ristorante confermerà la tua prenotazione a breve</p>
        <?php else: ?>
            <div style="font-size:2rem;margin-bottom:8px;"><i class="bi bi-check-circle"></i></div>
            <h1><?= e($reservation['tenant_name']) ?></h1>
            <p>La tua prenotazione</p>
        <?php endif; ?>
    </div>

    <div class="manage-body">
        <div style="text-align:center;margin-bottom:8px;">
            <span class="manage-status <?= e($status) ?>"><?= e($statusLabel) ?></span>
        </div>

        <div class="manage-detail">
            <div class="manage-detail-icon"><i class="bi bi-shop"></i></div>
            <div>
                <div class="manage-detail-label">Ristorante</div>
                <div class="manage-detail-value"><?= e($reservation['tenant_name']) ?></div>
            </div>
        </div>

        <div class="manage-detail">
            <div class="manage-detail-icon"><i class="bi bi-calendar3"></i></div>
            <div>
                <div class="manage-detail-label">Data</div>
                <div class="manage-detail-value"><?= e($dateFormatted) ?></div>
            </div>
        </div>

        <div class="manage-detail">
            <div class="manage-detail-icon"><i class="bi bi-clock"></i></div>
            <div>
                <div class="manage-detail-label">Orario</div>
                <div class="manage-detail-value"><?= e($time) ?></div>
            </div>
        </div>

        <div class="manage-detail">
            <div class="manage-detail-icon"><i class="bi bi-people"></i></div>
            <div>
                <div class="manage-detail-label">Persone</div>
                <div class="manage-detail-value"><?= (int)$reservation['party_size'] ?> <?= (int)$reservation['party_size'] === 1 ? 'persona' : 'persone' ?></div>
            </div>
        </div>

        <div class="manage-detail">
            <div class="manage-detail-icon"><i class="bi bi-person"></i></div>
            <div>
                <div class="manage-detail-label">Intestata a</div>
                <div class="manage-detail-value"><?= e($reservation['first_name'] . ' ' . $reservation['last_name']) ?></div>
            </div>
        </div>

        <?php if (!empty($reservation['customer_notes'])): ?>
        <div class="manage-note">
            <strong>Le tue note:</strong> <?= e($reservation['customer_notes']) ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($reservation['cancellation_policy']) && $canCancel): ?>
        <div class="manage-policy">
            <strong>Politica di cancellazione:</strong><br>
            <?= e($reservation['cancellation_policy']) ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($canCancel && !$isPast): ?>
    <div class="manage-actions">
        <form method="POST" action="<?= url("manage/{$token}/cancel") ?>" id="cancel-form">
            <?= csrf_field() ?>
            <button type="submit" class="manage-btn manage-btn-cancel" id="cancel-btn">
                <i class="bi bi-x-circle me-1"></i> Annulla prenotazione
            </button>
        </form>
    </div>
    <?php endif; ?>

    <?php if (!empty($reservation['tenant_address']) || !empty($reservation['tenant_phone'])): ?>
    <div class="manage-footer" style="font-size:.8rem;color:#495057;">
        <strong><?= e($reservation['tenant_name']) ?></strong><br>
        <?php if (!empty($reservation['tenant_address'])): ?>
            <?= e($reservation['tenant_address']) ?><br>
        <?php endif; ?>
        <?php if (!empty($reservation['tenant_phone'])): ?>
            <a href="tel:<?= e($reservation['tenant_phone']) ?>" style="color:#00844A;text-decoration:none;"><?= e($reservation['tenant_phone']) ?></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="manage-footer">
        &copy; <?= date('Y') ?> Evulery &middot; by alagias. - Soluzioni per il web
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
document.getElementById('cancel-form')?.addEventListener('submit', function(e) {
    if (!confirm('Sei sicuro di voler annullare la prenotazione?')) {
        e.preventDefault();
    }
});
</script>