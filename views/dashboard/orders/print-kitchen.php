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
    .toolbar .hint {
        margin-top: 12px;
        padding: 10px 14px;
        background: #fff8e1;
        border: 1px solid #ffe082;
        border-left: 4px solid #f59e0b;
        border-radius: 6px;
        font-size: 12px;
        line-height: 1.5;
        text-align: left;
        color: #5b4a05;
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
    }
    .toolbar .hint em { font-style: normal; background: #fff; padding: 1px 6px; border-radius: 3px; }
    .toolbar .hint strong { color: #4a3c0a; }
    .toolbar .hint-dismiss {
        display: inline-block;
        margin-top: 8px;
        background: transparent;
        border: 0;
        color: #856404;
        font-size: 11px;
        text-decoration: underline;
        cursor: pointer;
        padding: 0;
        font-family: inherit;
    }
    .toolbar .hint-dismiss:hover { color: #4a3c0a; }
    .toolbar .hint-show {
        background: #fff;
        color: #6c757d;
        border: 1px solid #d8dde3;
        /* Stessa altezza/border-radius dei pulsanti Stampa/Chiudi
           (padding-y 10px + font 14px ~= 40px totali; border-radius 6px) */
        width: 40px;
        height: 40px;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
        padding: 0;
        margin: 0 4px;
        vertical-align: middle;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .toolbar .hint-show:hover { color: #00844A; border-color: #00844A; }
</style>
</head>
<body>
<div class="toolbar no-print">
    <button type="button" id="btnPrint">🖨 Stampa</button>
    <button type="button" class="secondary" id="btnClose">Chiudi</button>
    <button type="button" class="hint-show" id="btnHintShow" title="Mostra suggerimento stampa termica" aria-label="Suggerimento">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
            <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
        </svg>
    </button>
    <div class="hint" id="hintBox" style="display:none;">
        💡 <strong>Stampante termica 80mm?</strong> Nel dialog Chrome → <em>Altre impostazioni</em> → <em>Formato carta</em> → seleziona <strong>80×297mm</strong> oppure <em>"Personalizzato"</em> con larghezza 80mm.
        <br><button type="button" class="hint-dismiss" id="btnHintHide">Nascondi suggerimento</button>
    </div>
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

    // Hint stampante termica: CHIUSO di default, riapribile via icona 💡
    // sempre presente nella toolbar. La preferenza "aperto" e' salvata in
    // localStorage condivisa tra kitchen + receipt (chiave evulery_print_hint_open).
    // Default chiuso: dopo aver capito la prima volta non ricompare ad ogni stampa.
    var HINT_KEY = 'evulery_print_hint_open';
    var hintBox  = document.getElementById('hintBox');
    var showBtn  = document.getElementById('btnHintShow');
    var hideBtn  = document.getElementById('btnHintHide');
    function setHintOpen(open) {
        hintBox.style.display = open ? '' : 'none';
        try { localStorage.setItem(HINT_KEY, open ? '1' : '0'); } catch (e) {}
    }
    hideBtn.addEventListener('click', function () { setHintOpen(false); });
    showBtn.addEventListener('click', function () {
        setHintOpen(hintBox.style.display === 'none');
    });
    try {
        // Apri solo se l'utente l'aveva esplicitamente aperto in precedenza
        if (localStorage.getItem(HINT_KEY) === '1') setHintOpen(true);
    } catch (e) {}
</script>
</body>
</html>
