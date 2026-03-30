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

<!-- ===== HEADER ===== -->
<header class="os-header">
    <div class="os-header-inner">
        <?php if ($tenantLogo): ?>
        <img src="<?= e($tenantLogo) ?>" alt="<?= e($tenantName) ?>" class="os-header-logo">
        <?php else: ?>
        <div class="os-header-initials" id="osHeaderInitials"></div>
        <?php endif; ?>
        <div class="os-header-text">
            <h1 class="os-header-name"><?= e($tenantName) ?></h1>
            <p class="os-header-sub" id="osHeaderStatus"><i class="bi bi-clock"></i> Caricamento...</p>
        </div>
        <?php if ($phone): ?>
        <a href="tel:<?= e($phone) ?>" class="os-header-phone" title="Chiama">
            <i class="bi bi-telephone-fill"></i>
        </a>
        <?php endif; ?>
    </div>
</header>

<!-- ===== MODE TOGGLE (takeaway / delivery) ===== -->
<div class="os-mode-bar" id="osModeBar" style="display:none;">
    <button class="os-mode-btn os-mode-active" data-mode="takeaway">
        <i class="bi bi-bag-fill"></i> Asporto
    </button>
    <button class="os-mode-btn" data-mode="delivery">
        <i class="bi bi-truck"></i> Consegna
    </button>
</div>

<!-- ===== DELIVERY INFO BAR ===== -->
<div class="os-delivery-bar" id="osDeliveryInfo" style="display:none;">
    <i class="bi bi-geo-alt-fill"></i>
    <span id="osDeliveryDesc"></span>
    <div class="os-delivery-pills" id="osDeliveryPills"></div>
</div>

<!-- ===== PROMO BANNER ===== -->
<div class="os-promo-banner" id="osPromoBanner" style="display:none;">
    <i class="bi bi-tag-fill"></i>
    <span id="osPromoText"></span>
</div>

<!-- ===== SEARCH ===== -->
<div class="os-search-wrap" id="osSearchWrap" style="display:none;">
    <i class="bi bi-search os-search-icon"></i>
    <input type="text" class="os-search-input" id="osSearchInput" placeholder="Cerca un piatto...">
</div>

<!-- ===== CATEGORY NAV ===== -->
<nav class="os-categories" id="osCategoryNav">
    <!-- Populated by JS -->
</nav>

<!-- ===== INFO CARD ===== -->
<div class="os-info-card" id="osInfoCard" style="display:none;">
    <div class="os-info-row" id="osInfoHours"><i class="bi bi-clock"></i> <span></span></div>
    <div class="os-info-row" id="osInfoPrep"><i class="bi bi-hourglass-split"></i> <span></span></div>
    <div class="os-info-row os-info-row--full" id="osInfoAddress"><i class="bi bi-geo-alt"></i> <span></span></div>
    <div class="os-info-row" id="osInfoPhone"><i class="bi bi-telephone"></i> <span></span></div>
    <div class="os-info-row" id="osInfoEmail"><i class="bi bi-envelope"></i> <span></span></div>
</div>

<!-- ===== LOADING ===== -->
<div class="os-loading" id="osLoading">
    <div class="os-spinner"></div>
    <p>Caricamento menu...</p>
</div>

<!-- ===== MENU ITEMS ===== -->
<main class="os-menu" id="osMenu" style="display:none;">
    <!-- Populated by JS -->
</main>

<!-- ===== EMPTY STATE ===== -->
<div class="os-empty" id="osEmpty" style="display:none;">
    <i class="bi bi-shop"></i>
    <p>Nessun piatto disponibile per ordinare al momento.</p>
</div>

<!-- ===== CHECKOUT PANEL (slides up) ===== -->
<div class="os-checkout-overlay" id="osCheckoutOverlay" style="display:none;">
    <div class="os-checkout-panel">
        <div class="os-checkout-header">
            <h2>Il tuo ordine</h2>
            <button class="os-checkout-close" id="osCheckoutClose"><i class="bi bi-x-lg"></i></button>
        </div>

        <!-- Cart items -->
        <div class="os-checkout-cart" id="osCheckoutCart">
            <!-- Populated by JS -->
        </div>

        <!-- Delivery section -->
        <div class="os-checkout-delivery" id="osCheckoutDelivery" style="display:none;">
            <h3>Indirizzo di consegna</h3>
            <input type="text" class="os-input" id="osDeliveryAddress" placeholder="Via e numero civico">
            <div class="os-input-row">
                <input type="text" class="os-input" id="osDeliveryCap" placeholder="CAP" maxlength="5">
                <span class="os-delivery-fee" id="osDeliveryFeeLabel"></span>
            </div>
            <textarea class="os-input" id="osDeliveryNotes" placeholder="Note per il corriere (citofono, piano...)" rows="2"></textarea>
        </div>

        <!-- Pickup time -->
        <div class="os-checkout-section">
            <h3>Orario di ritiro</h3>
            <select class="os-select" id="osPickupTime">
                <option value="">Seleziona orario...</option>
            </select>
        </div>

        <!-- Customer info -->
        <div class="os-checkout-section">
            <h3>I tuoi dati</h3>
            <input type="text" class="os-input" id="osCustomerName" placeholder="Nome e cognome *" required>
            <input type="tel" class="os-input" id="osCustomerPhone" placeholder="Telefono *" required>
            <input type="email" class="os-input" id="osCustomerEmail" placeholder="Email (opzionale)">
            <textarea class="os-input" id="osOrderNotes" placeholder="Note per il ristorante" rows="2"></textarea>
        </div>

        <!-- Payment method -->
        <div class="os-checkout-section" id="osPaymentSection">
            <h3>Pagamento</h3>
            <div class="os-payment-options" id="osPaymentOptions">
                <!-- Populated by JS -->
            </div>
        </div>

        <!-- Totals -->
        <div class="os-checkout-totals">
            <div class="os-total-row">
                <span>Subtotale</span>
                <span id="osSubtotal">€ 0,00</span>
            </div>
            <div class="os-total-row" id="osDeliveryFeeRow" style="display:none;">
                <span>Consegna</span>
                <span id="osDeliveryFeeTotal">€ 0,00</span>
            </div>
            <div class="os-total-row os-total-final">
                <span>Totale</span>
                <span id="osTotal">€ 0,00</span>
            </div>
        </div>

        <!-- Min amount warning -->
        <div class="os-min-warning" id="osMinWarning" style="display:none;">
            <i class="bi bi-exclamation-triangle"></i>
            <span id="osMinWarningText"></span>
        </div>

        <!-- Error message -->
        <div class="os-error" id="osError" style="display:none;"></div>

        <!-- Submit -->
        <button class="os-btn-submit" id="osSubmitOrder" disabled>
            <span id="osSubmitText">Invia ordine</span>
            <span class="os-btn-spinner" id="osSubmitSpinner" style="display:none;"></span>
        </button>
    </div>
</div>

<!-- ===== FLOATING CART BAR ===== -->
<div class="os-cart-bar" id="osCartBar" style="display:none;">
    <div class="os-cart-bar-inner">
        <div class="os-cart-info">
            <span class="os-cart-count" id="osCartCount">0</span>
            <span class="os-cart-label">articoli</span>
        </div>
        <button class="os-cart-btn" id="osCartBtn">
            Vedi ordine · <span id="osCartTotal">€ 0,00</span>
        </button>
    </div>
</div>

<!-- ===== ALLERGEN LEGEND ===== -->
<div class="os-allergen-legend" id="osAllergenLegend" style="display:none;">
    <div class="os-legend-title"><i class="bi bi-info-circle"></i> Legenda allergeni</div>
    <div class="os-legend-grid" id="osLegendGrid"></div>
</div>

<!-- ===== FOOTER ===== -->
<footer class="os-footer">
    <p>&copy; <?= date('Y') ?> Evulery &middot; by <a href="https://alagias.com" target="_blank" rel="noopener">alagias. - Soluzioni per il web</a></p>
</footer>

<script nonce="<?= csp_nonce() ?>">
    // Config injected server-side (no inline logic, just data)
    window.OS_CONFIG = {
        apiBaseUrl: <?= json_encode($apiBaseUrl) ?>,
        baseUrl: <?= json_encode(url('')) ?>,
        slug: <?= json_encode($slug) ?>,
        tenantName: <?= json_encode($tenantName) ?>,
    };
</script>
<script nonce="<?= csp_nonce() ?>" src="<?= asset('js/ordering-store.js') ?>"></script>

</body>
</html>
