<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#00844A">
    <title><?= e($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    <link href="<?= asset('css/ordering.css') ?>" rel="stylesheet">
</head>
<body>

<?php
$hasOrder = !empty($order);
$orderType = $hasOrder ? $order['order_type'] : 'takeaway';
$isDelivery = $orderType === 'delivery';
$statusLabel = $hasOrder && $order['status'] === 'accepted' ? 'Ordine confermato!' : 'Ordine ricevuto!';
$statusSubtitle = $hasOrder && $order['status'] === 'accepted'
    ? 'Il ristorante ha confermato il tuo ordine.'
    : 'Il ristorante ha ricevuto il tuo ordine e ti confermer&agrave; a breve.';
?>

<div class="os-conf-card">
    <div class="os-conf-header" id="osConfHeader">
        <div class="os-conf-check">
            <i class="bi bi-check-lg"></i>
        </div>
        <div class="os-conf-title"><?= $statusLabel ?></div>
        <div class="os-conf-subtitle"><?= $statusSubtitle ?></div>
    </div>

    <div class="os-conf-body">
        <?php if ($hasOrder): ?>
        <!-- Dettagli ordine -->
        <div class="os-conf-grid">
            <div class="os-conf-detail">
                <div class="os-conf-detail-icon"><i class="bi bi-receipt"></i></div>
                <div class="os-conf-detail-value"><?= e($order['order_number']) ?></div>
                <div class="os-conf-detail-label">N. Ordine</div>
            </div>
            <div class="os-conf-detail">
                <div class="os-conf-detail-icon"><i class="bi bi-<?= $isDelivery ? 'truck' : 'bag' ?>"></i></div>
                <div class="os-conf-detail-value"><?= $isDelivery ? 'Consegna' : 'Asporto' ?></div>
                <div class="os-conf-detail-label">Tipo</div>
            </div>
            <div class="os-conf-detail">
                <div class="os-conf-detail-icon"><i class="bi bi-currency-euro"></i></div>
                <div class="os-conf-detail-value">&euro;<?= number_format((float)$order['total'], 2, ',', '.') ?></div>
                <div class="os-conf-detail-label">Totale</div>
            </div>
            <?php if ($order['pickup_time']): ?>
            <div class="os-conf-detail">
                <div class="os-conf-detail-icon"><i class="bi bi-clock"></i></div>
                <div class="os-conf-detail-value"><?= date('H:i', strtotime($order['pickup_time'])) ?></div>
                <div class="os-conf-detail-label"><?= $isDelivery ? 'Consegna' : 'Ritiro' ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Riepilogo piatti -->
        <?php if (!empty($orderItems)): ?>
        <div class="os-conf-items">
            <div class="os-conf-items-title"><i class="bi bi-list-check me-1"></i> Riepilogo ordine</div>
            <?php foreach ($orderItems as $item): ?>
            <div class="os-conf-item-row">
                <span class="os-conf-item-qty"><?= (int)$item['quantity'] ?>x</span>
                <span class="os-conf-item-name"><?= e($item['item_name']) ?></span>
                <span class="os-conf-item-price">&euro;<?= number_format((float)$item['unit_price'] * (int)$item['quantity'], 2, ',', '.') ?></span>
            </div>
            <?php endforeach; ?>
            <?php if ($isDelivery && (float)$order['delivery_fee'] > 0): ?>
            <div class="os-conf-item-row os-conf-item-fee">
                <span class="os-conf-item-qty"><i class="bi bi-truck"></i></span>
                <span class="os-conf-item-name">Consegna</span>
                <span class="os-conf-item-price">&euro;<?= number_format((float)$order['delivery_fee'], 2, ',', '.') ?></span>
            </div>
            <?php endif; ?>
            <div class="os-conf-item-row os-conf-item-total">
                <span></span>
                <span class="os-conf-item-name"><strong>Totale</strong></span>
                <span class="os-conf-item-price"><strong>&euro;<?= number_format((float)$order['total'], 2, ',', '.') ?></strong></span>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($isDelivery && !empty($order['delivery_address'])): ?>
        <!-- Indirizzo consegna -->
        <div class="os-conf-address">
            <div class="os-conf-address-icon"><i class="bi bi-geo-alt-fill"></i></div>
            <div class="os-conf-address-text">
                <strong>Indirizzo di consegna</strong><br>
                <?= e($order['delivery_address']) ?>
                <?php if ($order['delivery_cap']): ?> - <?= e($order['delivery_cap']) ?><?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php elseif ($orderNumber): ?>
        <!-- Fallback: solo numero ordine -->
        <div class="os-conf-grid">
            <div class="os-conf-detail">
                <div class="os-conf-detail-icon"><i class="bi bi-receipt"></i></div>
                <div class="os-conf-detail-value"><?= e($orderNumber) ?></div>
                <div class="os-conf-detail-label">N. Ordine</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Pagamento -->
        <?php if ($hasOrder): ?>
        <div class="os-conf-note">
            <i class="bi bi-<?= $order['payment_method'] === 'stripe' ? 'credit-card' : 'cash-coin' ?>"></i>
            <span>
                Pagamento: <strong><?= $order['payment_method'] === 'stripe' ? 'Carta (online)' : 'Contanti al ritiro' ?></strong>
                <?php if ($order['payment_method'] === 'stripe' && $order['payment_status'] === 'paid'): ?>
                    &mdash; <span style="color:#198754">Pagato</span>
                <?php endif; ?>
            </span>
        </div>
        <?php endif; ?>

        <!-- Contatto -->
        <?php if ($phone): ?>
        <div class="os-conf-note">
            <i class="bi bi-telephone"></i>
            <span>Per informazioni: <a href="tel:<?= e($phone) ?>"><?= e($phone) ?></a></span>
        </div>
        <?php endif; ?>

        <!-- Azioni -->
        <div class="os-conf-actions">
            <a href="<?= url($slug . '/order') ?>" class="os-conf-btn-primary">
                <i class="bi bi-bag-plus"></i> Ordina ancora
            </a>
            <?php if (!empty($tenant['menu_enabled'])): ?>
            <a href="<?= url($slug . '/menu') ?>" class="os-conf-btn-secondary">
                <i class="bi bi-book"></i> Consulta il menu
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="os-conf-footer">
        &copy; <?= date('Y') ?> Evulery &middot; by alagias. - Soluzioni per il web
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
// Confetti animation
(function() {
    var h = document.getElementById('osConfHeader');
    if (!h) return;
    var colors = ['#FFD700','#FF6B6B','#4ECDC4','#45B7D1','#96CEB4','#FFEAA7'];
    for (var i = 0; i < 12; i++) {
        var d = document.createElement('div');
        d.className = 'os-confetti';
        d.style.left = (10 + Math.random() * 80) + '%';
        d.style.top = (20 + Math.random() * 40) + '%';
        d.style.background = colors[Math.floor(Math.random() * colors.length)];
        d.style.animationDelay = (Math.random() * .5) + 's';
        d.style.width = d.style.height = (4 + Math.random() * 4) + 'px';
        h.appendChild(d);
    }
})();
</script>

</body>
</html>
