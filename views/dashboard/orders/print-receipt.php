<?php
// Vista standalone — Ricevuta cliente completa.
// Variabili: $tenant, $order, $items, $footerUrl, $footerLabel
$isDelivery = ($order['order_type'] ?? '') === 'delivery';
$pickupTime = !empty($order['pickup_time']) ? date('H:i', strtotime($order['pickup_time'])) : null;
$createdAt  = !empty($order['created_at']) ? date('d/m/Y H:i', strtotime($order['created_at'])) : '';
$paymentLabel = match ($order['payment_method'] ?? '') {
    'stripe' => 'Pagato online',
    'cash'   => 'Contanti',
    default  => '—',
};
$paymentStatusLabel = match ($order['payment_status'] ?? '') {
    'paid'     => 'Pagato',
    'refunded' => 'Rimborsato',
    default    => 'Da incassare',
};

// Subtotale = totale - delivery_fee (l'ordine non lo memorizza esplicitamente)
$total       = (float)($order['total'] ?? 0);
$deliveryFee = (float)($order['delivery_fee'] ?? 0);
$discount    = (float)($order['discount_amount'] ?? 0);
$subtotal    = $total - $deliveryFee + $discount;
?><!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title><?= e($title ?? 'Ricevuta') ?></title>
<style>
    @page { size: 80mm auto; margin: 0; }
    @media print {
        body { margin: 0; }
        .no-print { display: none !important; }
    }
    body {
        margin: 0;
        padding: 6mm;
        background: #f5f6f8;
        font-family: "Courier New", "Roboto Mono", monospace;
        color: #000;
        font-size: 11px;
        line-height: 1.4;
    }
    .receipt {
        background: #fff;
        max-width: 76mm;
        margin: 0 auto;
        padding: 4mm 5mm;
    }
    @media print {
        body { padding: 0; background: #fff; }
        .receipt { box-shadow: none; margin: 0; padding: 0; max-width: none; }
    }

    .rcp-center { text-align: center; }
    .rcp-bold { font-weight: 700; }
    .rcp-lg { font-size: 14px; }
    .rcp-hr { border: 0; border-top: 1px dashed #555; margin: 8px 0; }
    .rcp-row { display: flex; justify-content: space-between; gap: 8px; }
    .rcp-section-title {
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-size: 10px;
        margin: 8px 0 4px;
        background: #000;
        color: #fff;
        padding: 3px 6px;
    }
    .rcp-item { margin-bottom: 6px; }
    .rcp-item-name { display: flex; justify-content: space-between; font-weight: 700; }
    .rcp-item-detail { font-size: 10px; color: #444; padding-left: 8px; }
    .rcp-note { font-style: italic; font-size: 10px; padding-left: 8px; color: #333; }
    .rcp-total {
        font-size: 14px;
        font-weight: 700;
        margin-top: 6px;
        padding-top: 6px;
        border-top: 1px solid #000;
    }
    .rcp-footer {
        text-align: center;
        font-size: 10px;
        margin-top: 10px;
        padding-top: 6px;
        border-top: 1px dashed #555;
    }

    /* Linea di taglio: il rider taglia qui, fa firmare la meta' sotto */
    .rcp-tear-line {
        display: flex;
        align-items: center;
        gap: 6px;
        margin: 12px 0 8px;
        font-size: 11px;
        color: #555;
    }
    .rcp-tear-line .dashes { flex: 1; border-top: 1px dashed #555; }
    .rcp-confirm-title {
        font-weight: 700;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 6px;
    }
    .rcp-confirm-text { font-size: 10px; line-height: 1.5; margin-bottom: 10px; }
    .rcp-signature-row { margin-top: 8px; }
    .rcp-signature-label { font-size: 10px; color: #444; margin-bottom: 2px; }
    .rcp-signature-line {
        border-bottom: 1px solid #000;
        height: 22px;
        margin-bottom: 8px;
    }

    .toolbar {
        max-width: 76mm;
        margin: 0 auto 8mm;
        text-align: center;
    }
    .toolbar button {
        background: #00844A; color: #fff; border: 0;
        padding: 10px 22px; font-size: 14px; font-weight: 700;
        border-radius: 6px; cursor: pointer; margin: 0 4px;
    }
    .toolbar button.secondary {
        background: #fff; color: #495057; border: 1px solid #d8dde3;
    }
</style>
</head>
<body>
<div class="toolbar no-print">
    <button type="button" id="btnPrint">🖨 Stampa</button>
    <button type="button" class="secondary" id="btnClose">Chiudi</button>
</div>
<div class="receipt">
    <div class="rcp-center rcp-bold rcp-lg"><?= e($tenant['name'] ?? '') ?></div>
    <?php if (!empty($tenant['address'])): ?>
        <div class="rcp-center" style="font-size:10px;"><?= e($tenant['address']) ?></div>
    <?php endif; ?>
    <?php if (!empty($tenant['phone'])): ?>
        <div class="rcp-center" style="font-size:10px;"><?= e($tenant['phone']) ?></div>
    <?php endif; ?>

    <hr class="rcp-hr">

    <div class="rcp-section-title">
        Ordine #<?= e($order['order_number']) ?>
        · <?= $isDelivery ? 'Delivery' : 'Asporto' ?>
    </div>
    <div class="rcp-row"><span>Ricevuto:</span><span><?= e($createdAt) ?></span></div>
    <?php if ($pickupTime): ?>
        <div class="rcp-row"><span><?= $isDelivery ? 'Consegna entro:' : 'Ritiro entro:' ?></span><span><?= e($pickupTime) ?></span></div>
    <?php endif; ?>
    <div class="rcp-row"><span>Pagamento:</span><span><strong><?= e($paymentLabel) ?> · <?= e($paymentStatusLabel) ?></strong></span></div>

    <?php if (!empty($order['customer_name'])): ?>
    <div class="rcp-section-title">Cliente</div>
    <div><strong><?= e($order['customer_name']) ?></strong></div>
    <?php if (!empty($order['customer_phone'])): ?>
        <div><?= e($order['customer_phone']) ?></div>
    <?php endif; ?>
    <?php if (!empty($order['customer_email'])): ?>
        <div style="font-size:10px;"><?= e($order['customer_email']) ?></div>
    <?php endif; ?>
    <?php endif; ?>

    <?php if ($isDelivery && !empty($order['delivery_address'])): ?>
    <div class="rcp-section-title">Indirizzo consegna</div>
    <div><?= e($order['delivery_address']) ?></div>
    <?php if (!empty($order['delivery_cap'])): ?>
        <div><?= e($order['delivery_cap']) ?></div>
    <?php endif; ?>
    <?php if (!empty($order['delivery_notes'])): ?>
        <div style="font-style:italic;font-size:10px;margin-top:4px;">Note: <?= e($order['delivery_notes']) ?></div>
    <?php endif; ?>
    <?php endif; ?>

    <div class="rcp-section-title">Articoli</div>
    <?php foreach ($items as $item): ?>
        <?php $lineTotal = (int)$item['quantity'] * (float)$item['unit_price']; ?>
        <div class="rcp-item">
            <div class="rcp-item-name">
                <span><?= (int)$item['quantity'] ?>× <?= e($item['item_name']) ?></span>
                <span>€ <?= number_format($lineTotal, 2, ',', '.') ?></span>
            </div>
            <?php if (!empty($item['notes'])): ?>
                <div class="rcp-note">! <?= e($item['notes']) ?></div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <hr class="rcp-hr">
    <div class="rcp-row"><span>Subtotale:</span><span>€ <?= number_format($subtotal, 2, ',', '.') ?></span></div>
    <?php if ($discount > 0): ?>
        <div class="rcp-row"><span>Sconto:</span><span>-€ <?= number_format($discount, 2, ',', '.') ?></span></div>
    <?php endif; ?>
    <?php if ($isDelivery): ?>
        <div class="rcp-row"><span>Consegna:</span><span>€ <?= number_format($deliveryFee, 2, ',', '.') ?></span></div>
    <?php endif; ?>
    <div class="rcp-row rcp-total"><span>TOTALE:</span><span>€ <?= number_format($total, 2, ',', '.') ?></span></div>

    <?php if (!empty($footerUrl)): ?>
    <div class="rcp-footer">
        Visita il nostro sito<br>
        <strong><?= e($footerLabel) ?></strong><br>
        <span style="color:#666;font-size:9px;">Powered by Evulery</span>
    </div>
    <?php else: ?>
    <div class="rcp-footer">
        <span style="color:#666;font-size:9px;">Powered by Evulery</span>
    </div>
    <?php endif; ?>

    <!--
        Tear-off: il rider taglia qui, fa firmare la meta' sotto al cliente
        come prova di consegna, riporta la meta' firmata al ristorante.
    -->
    <div class="rcp-tear-line">
        <span>✂</span>
        <span class="dashes"></span>
    </div>

    <div>
        <div class="rcp-confirm-title">Conferma di consegna</div>
        <div class="rcp-confirm-text">
            Confermo di aver ricevuto l'ordine <strong>#<?= e($order['order_number']) ?></strong> da <strong><?= e($tenant['name'] ?? '') ?></strong> in data <strong><?= date('d/m/Y', strtotime($order['created_at'] ?? 'now')) ?></strong> per un totale di <strong>€ <?= number_format($total, 2, ',', '.') ?></strong>.
        </div>

        <div class="rcp-signature-row">
            <div class="rcp-signature-label">Nome cliente:</div>
            <div class="rcp-signature-line"></div>
        </div>
        <div class="rcp-signature-row">
            <div class="rcp-signature-label">Firma:</div>
            <div class="rcp-signature-line"></div>
        </div>
        <div style="font-size:9px;color:#444;margin-top:6px;text-align:center;">Da consegnare al rider come prova di ricezione</div>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
    document.getElementById('btnPrint').addEventListener('click', function () { window.print(); });
    document.getElementById('btnClose').addEventListener('click', function () { window.close(); });
    window.addEventListener('load', function () {
        setTimeout(function () { window.print(); }, 300);
    });
</script>
</body>
</html>
