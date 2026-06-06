<?php
// Vista standalone (no layout dashboard) — Ticket cucina sintetico.
// Variabili: $tenant, $order, $items
$type = ($order['order_type'] ?? '') === 'delivery' ? 'CONSEGNA' : 'ASPORTO';
$pickupTime = !empty($order['pickup_time']) ? date('H:i', strtotime($order['pickup_time'])) : null;
$createdAt = !empty($order['created_at']) ? date('d/m/Y H:i', strtotime($order['created_at'])) : '';
?><!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title><?= e($title ?? 'Ticket cucina') ?></title>
<style>
    /* Stampa termica 80mm: la stampante adatta l'altezza al contenuto. */
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
        font-size: 12px;
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
    .kt-restaurant {
        text-align: center;
        font-size: 12px;
        margin-bottom: 4px;
    }
    .kt-order-number {
        text-align: center;
        font-size: 32px;
        font-weight: 900;
        letter-spacing: .08em;
        background: #000;
        color: #fff;
        padding: 10px 0;
        margin: 8px 0;
    }
    .kt-meta {
        display: flex;
        justify-content: space-between;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 8px;
        padding-bottom: 6px;
        border-bottom: 2px solid #000;
    }
    .kt-customer-block {
        margin: 8px 0;
        padding: 6px 8px;
        border: 1px solid #000;
    }
    .kt-customer-label {
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #555;
        margin-bottom: 2px;
    }
    .kt-customer-name {
        font-size: 14px;
        font-weight: 700;
    }
    .kt-section-title {
        font-weight: 700;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .05em;
        margin: 10px 0 4px;
    }
    .kt-item {
        font-size: 14px;
        font-weight: 700;
        margin-bottom: 4px;
    }
    .kt-item-detail {
        font-size: 12px;
        font-weight: 400;
        padding-left: 12px;
        color: #333;
    }
    /* Note in REVERSE VIDEO: la termica b/n stampa sfondo nero + testo
       bianco riempiendo l'area di punti neri. Standard ESC/POS. */
    .kt-item-note {
        font-size: 12px;
        font-weight: 700;
        padding: 3px 8px;
        margin: 2px 0 4px 8px;
        display: inline-block;
        background: #000;
        color: #fff;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .kt-footer {
        text-align: center;
        font-size: 10px;
        margin-top: 12px;
        padding-top: 6px;
        border-top: 1px dashed #555;
        color: #666;
    }
    .toolbar {
        max-width: 76mm;
        margin: 0 auto 8mm;
        text-align: center;
    }
    .toolbar button {
        background: #00844A;
        color: #fff;
        border: 0;
        padding: 10px 22px;
        font-size: 14px;
        font-weight: 700;
        border-radius: 6px;
        cursor: pointer;
        margin: 0 4px;
    }
    .toolbar button.secondary {
        background: #fff;
        color: #495057;
        border: 1px solid #d8dde3;
    }
</style>
</head>
<body>
<div class="toolbar no-print">
    <button type="button" id="btnPrint">🖨 Stampa</button>
    <button type="button" class="secondary" id="btnClose">Chiudi</button>
</div>
<div class="receipt">
    <div class="kt-restaurant"><?= e($tenant['name'] ?? '') ?></div>

    <div class="kt-order-number">#<?= e($order['order_number']) ?></div>

    <div class="kt-meta">
        <span><?= e($type) ?></span>
        <span><?php if ($pickupTime): ?>⏰ <?= e($pickupTime) ?><?php else: ?>ASAP<?php endif; ?></span>
    </div>

    <?php if (!empty($order['customer_name'])): ?>
    <div class="kt-customer-block">
        <div class="kt-customer-label">Cliente</div>
        <div class="kt-customer-name"><?= e($order['customer_name']) ?></div>
    </div>
    <?php endif; ?>

    <div class="kt-section-title">Articoli</div>
    <?php foreach ($items as $item): ?>
        <div class="kt-item">
            <?= (int)$item['quantity'] ?>× <?= e($item['item_name']) ?>
        </div>
        <?php if (!empty($item['notes'])): ?>
            <div class="kt-item-note">⚠ <?= e($item['notes']) ?></div>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if (!empty($order['notes'])): ?>
        <div class="kt-section-title">Note ordine</div>
        <div class="kt-item-note">⚠ <?= e($order['notes']) ?></div>
    <?php endif; ?>

    <div class="kt-footer">
        Stampato il <?= e($createdAt) ?><br>
        <span style="font-size:9px;">Powered by Evulery</span>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
    // Auto-apertura dialog stampa al caricamento (~300ms dopo per dare
    // tempo al rendering). L'utente puo' annullare e usare la toolbar.
    document.getElementById('btnPrint').addEventListener('click', function () { window.print(); });
    document.getElementById('btnClose').addEventListener('click', function () { window.close(); });
    window.addEventListener('load', function () {
        setTimeout(function () { window.print(); }, 300);
    });
</script>
</body>
</html>
