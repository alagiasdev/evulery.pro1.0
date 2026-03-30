<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#E65100">
    <title><?= e($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    <link href="<?= asset('css/delivery-board.css') ?>" rel="stylesheet">
</head>
<body>

<?php if ($page === 'pin'): ?>
<!-- ===== PIN SCREEN ===== -->
<div class="db-pin-screen">
    <div class="db-pin-card">
        <?php if (!empty($tenant['logo_url'])): ?>
        <img src="<?= e($tenant['logo_url']) ?>" alt="" class="db-pin-logo">
        <?php else: ?>
        <div class="db-pin-icon"><i class="bi bi-truck"></i></div>
        <?php endif; ?>
        <h1 class="db-pin-title"><?= e($tenant['name']) ?></h1>
        <p class="db-pin-subtitle">Board Consegne</p>

        <?php if (!empty($error)): ?>
        <div class="db-pin-error">
            <i class="bi bi-exclamation-circle me-1"></i> <?= e($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= url("delivery/{$token}/auth") ?>">
            <?= csrf_field() ?>
            <label class="db-pin-label">Inserisci il PIN di accesso</label>
            <input type="tel" name="pin" class="db-pin-input" maxlength="6" autocomplete="off" autofocus
                   placeholder="- - - -" inputmode="numeric" pattern="[0-9]*">
            <button type="submit" class="db-pin-submit">
                <i class="bi bi-unlock me-1"></i> Accedi
            </button>
        </form>
    </div>
    <div class="db-pin-footer">&copy; <?= date('Y') ?> Evulery &middot; by alagias. - Soluzioni per il web</div>
</div>

<?php else: ?>
<!-- ===== DELIVERY BOARD ===== -->
<header class="db-header">
    <div class="db-header-inner">
        <?php if (!empty($tenant['logo_url'])): ?>
        <img src="<?= e($tenant['logo_url']) ?>" alt="" class="db-header-logo">
        <?php endif; ?>
        <div class="db-header-text">
            <h1 class="db-header-name"><?= e($tenant['name']) ?></h1>
            <p class="db-header-sub"><i class="bi bi-truck me-1"></i> Board Consegne</p>
        </div>
        <div class="db-header-count" id="dbOrderCount"><?= count($orders) ?></div>
    </div>
</header>

<main class="db-main">
    <?php if (empty($orders)): ?>
    <div class="db-empty">
        <i class="bi bi-inbox"></i>
        <p>Nessuna consegna in corso</p>
        <span>Le nuove consegne appariranno automaticamente</span>
    </div>
    <?php else: ?>
    <div class="db-orders" id="dbOrders">
        <?php foreach ($orders as $order): ?>
        <div class="db-order-card" data-id="<?= $order['id'] ?>" data-status="<?= e($order['status']) ?>">
            <div class="db-order-top">
                <span class="db-order-number"><?= e($order['order_number']) ?></span>
                <?php
                    $statusClass = match($order['status']) {
                        'ready' => 'db-status-ready',
                        'preparing' => 'db-status-preparing',
                        default => 'db-status-accepted',
                    };
                    $statusLabel = match($order['status']) {
                        'ready' => 'Pronto',
                        'preparing' => 'In preparazione',
                        'accepted' => 'Accettato',
                        default => $order['status'],
                    };
                ?>
                <span class="db-status-badge <?= $statusClass ?>"><?= $statusLabel ?></span>
            </div>

            <?php if ($order['pickup_time']): ?>
            <div class="db-order-time">
                <i class="bi bi-clock"></i> Consegna: <strong><?= date('H:i', strtotime($order['pickup_time'])) ?></strong>
            </div>
            <?php endif; ?>

            <div class="db-order-address">
                <i class="bi bi-geo-alt-fill"></i>
                <div>
                    <div class="db-address-text"><?= e($order['delivery_address']) ?></div>
                    <?php if ($order['delivery_cap']): ?>
                    <span class="db-address-cap">CAP <?= e($order['delivery_cap']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="db-order-customer">
                <div class="db-customer-info">
                    <i class="bi bi-person-fill"></i> <?= e($order['customer_name']) ?>
                </div>
                <div class="db-customer-actions">
                    <a href="tel:<?= e($order['customer_phone']) ?>" class="db-action-btn db-action-call" title="Chiama">
                        <i class="bi bi-telephone-fill"></i>
                    </a>
                    <?php
                        $waNum = preg_replace('/[^0-9]/', '', $order['customer_phone']);
                        if (str_starts_with($waNum, '0')) $waNum = '39' . substr($waNum, 1);
                        elseif (!str_starts_with($waNum, '39') && strlen($waNum) <= 10) $waNum = '39' . $waNum;
                    ?>
                    <a href="https://wa.me/<?= e($waNum) ?>" target="_blank" rel="noopener" class="db-action-btn db-action-wa" title="WhatsApp">
                        <i class="bi bi-whatsapp"></i>
                    </a>
                </div>
            </div>

            <?php if ($order['delivery_notes']): ?>
            <div class="db-order-notes">
                <i class="bi bi-chat-left-text"></i> <?= e($order['delivery_notes']) ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($orderItems[$order['id']])): ?>
            <div class="db-order-items">
                <div class="db-items-label"><i class="bi bi-receipt"></i> Articoli</div>
                <?php foreach ($orderItems[$order['id']] as $item): ?>
                <div class="db-item-row">
                    <span class="db-item-qty"><?= (int)$item['quantity'] ?>x</span>
                    <span class="db-item-name"><?= e($item['item_name']) ?></span>
                    <?php if ($item['notes']): ?>
                    <div class="db-item-note"><i class="bi bi-pencil-square"></i> <?= e($item['notes']) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="db-order-total">
                Totale: <strong>&euro;<?= number_format((float)$order['total'], 2, ',', '.') ?></strong>
                <span class="db-payment-badge"><?= $order['payment_method'] === 'stripe' ? 'Pagato online' : 'Contanti' ?></span>
            </div>

            <?php if ($order['status'] === 'ready'): ?>
            <form method="POST" action="<?= url("delivery/{$token}/complete/{$order['id']}") ?>" class="db-complete-form">
                <?= csrf_field() ?>
                <button type="submit" class="db-complete-btn" data-confirm="Confermi la consegna di <?= e($order['order_number']) ?>?">
                    <i class="bi bi-check-circle-fill me-1"></i> Consegnato
                </button>
            </form>
            <?php else: ?>
            <div class="db-waiting-badge">
                <i class="bi bi-hourglass-split me-1"></i> In attesa — non ancora pronto
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>

<div class="db-footer">
    <span>Ultimo aggiornamento: <span id="dbLastUpdate"><?= date('H:i:s') ?></span></span>
    <span>&copy; <?= date('Y') ?> Evulery</span>
</div>

<script nonce="<?= csp_nonce() ?>">
(function() {
    // Auto-refresh ogni 30 secondi
    var refreshInterval = 30000;
    var token = '<?= e($token) ?>';
    var currentCount = <?= count($orders) ?>;
    var notifSound = null;

    try {
        notifSound = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdH2Xqq+ck3ZjYHaCm6+4sJuAdWJlcYCVqbWtmpB6ZmRxg5mqtq6bj3pnZHCClKixr52Qe2hkb4KUqLGunpB7aGNvgpOnsK6dkHtoY2+Ck6ewr52Qe2hjb4KTp7CvnZB7aGNwgpOnsK+dkHtoY2+Ck6ewrp2QfGhjb4KTp7CvnpB7aGNvgpOnsK6dkHtoY2+Ck6ewr52QfGhjb4GTpq+tnY97aGRvgpSosK+ekHtoY2+ClKixr56Qe2hjb4KUqLGvnpB8aGNvgpSnsK+ekHxoY2+ClKiwr56QfGhjb4KUp7CvnpB8');
    } catch(e) {}

    function refreshBoard() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '<?= url("delivery/" . e($token) . "/board") ?>');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    // Nuovi ordini? Suona notifica
                    if (data.count > currentCount && notifSound) {
                        try { notifSound.play(); } catch(e) {}
                    }
                    currentCount = data.count;
                    document.getElementById('dbOrderCount').textContent = data.count;
                    document.getElementById('dbLastUpdate').textContent = new Date().toLocaleTimeString('it-IT');

                    // Reload page per semplicità se i dati sono cambiati
                    if (data.count !== <?= count($orders) ?>) {
                        location.reload();
                    }
                    // Aggiorna anche se gli stati sono cambiati
                    var currentCards = document.querySelectorAll('.db-order-card');
                    var needsReload = false;
                    if (currentCards.length !== data.orders.length) needsReload = true;
                    if (!needsReload) {
                        data.orders.forEach(function(order, i) {
                            if (currentCards[i] && currentCards[i].dataset.status !== order.status) {
                                needsReload = true;
                            }
                        });
                    }
                    if (needsReload) location.reload();
                } catch(e) {}
            }
        };
        xhr.send();
    }

    setInterval(refreshBoard, refreshInterval);

    // Confirm consegna
    document.querySelectorAll('[data-confirm]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm)) e.preventDefault();
        });
    });
})();
</script>

<?php endif; ?>

</body>
</html>
