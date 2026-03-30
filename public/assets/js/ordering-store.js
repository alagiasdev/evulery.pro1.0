/**
 * ordering-store.js — Public ordering store logic
 * Cart (localStorage), checkout, delivery toggle, API calls
 * Depends on: window.OS_CONFIG = { apiBaseUrl, slug, tenantName }
 */
(function () {
    'use strict';

    var CFG = window.OS_CONFIG || {};
    var API = CFG.apiBaseUrl || '';
    var BASE = CFG.baseUrl || '';
    var SLUG = CFG.slug || '';
    var CART_KEY = 'evulery_cart_' + SLUG;

    // Allergen map (icons, colors, Italian labels)
    var ALLERGEN_ICONS = {
        gluten:'G', crustaceans:'Cr', eggs:'U', fish:'P', peanuts:'Ar', soy:'S', milk:'L',
        nuts:'Fg', celery:'Se', mustard:'Sn', sesame:'Ss', sulphites:'So', lupin:'Lu', molluscs:'Mo'
    };
    var ALLERGEN_COLORS = {
        gluten:'#D84315', crustaceans:'#E65100', eggs:'#F9A825', fish:'#0277BD', peanuts:'#8D6E63',
        soy:'#558B2F', milk:'#1565C0', nuts:'#6D4C41', celery:'#2E7D32', mustard:'#F57F17',
        sesame:'#795548', sulphites:'#7B1FA2', lupin:'#AD1457', molluscs:'#00838F'
    };
    var ALLERGEN_LABELS = {
        gluten:'Glutine', crustaceans:'Crostacei', eggs:'Uova', fish:'Pesce', peanuts:'Arachidi',
        soy:'Soia', milk:'Latte', nuts:'Frutta a guscio', celery:'Sedano', mustard:'Senape',
        sesame:'Sesamo', sulphites:'Anidride solforosa', lupin:'Lupini', molluscs:'Molluschi'
    };

    // State
    var rawMenu = [];      // raw API response (hierarchical)
    var sections = [];     // flattened: [{name, items: [...]}]
    var allItems = [];     // flat list of all items for lookup
    var settings = {};     // ordering settings from API
    var slots = [];        // pickup slots
    var deliveryZones = [];
    var promotion = null;  // active promo {id, name, discount_percent}
    var tenantInfo = {};   // tenant details from API
    var cart = loadCart();  // { itemId: {id, name, price, qty, notes} }
    var orderMode = 'takeaway';
    var selectedPayment = 'cash';
    var deliveryFee = 0;

    // DOM refs
    var $loading = document.getElementById('osLoading');
    var $menu = document.getElementById('osMenu');
    var $empty = document.getElementById('osEmpty');
    var $catNav = document.getElementById('osCategoryNav');
    var $modeBar = document.getElementById('osModeBar');
    var $deliveryInfo = document.getElementById('osDeliveryInfo');
    var $deliveryDesc = document.getElementById('osDeliveryDesc');
    var $cartBar = document.getElementById('osCartBar');
    var $cartCount = document.getElementById('osCartCount');
    var $cartTotal = document.getElementById('osCartTotal');
    var $cartBtn = document.getElementById('osCartBtn');
    var $checkoutOverlay = document.getElementById('osCheckoutOverlay');
    var $checkoutClose = document.getElementById('osCheckoutClose');
    var $checkoutCart = document.getElementById('osCheckoutCart');
    var $checkoutDelivery = document.getElementById('osCheckoutDelivery');
    var $pickupTime = document.getElementById('osPickupTime');
    var $paymentOptions = document.getElementById('osPaymentOptions');
    var $subtotal = document.getElementById('osSubtotal');
    var $deliveryFeeRow = document.getElementById('osDeliveryFeeRow');
    var $deliveryFeeTotal = document.getElementById('osDeliveryFeeTotal');
    var $total = document.getElementById('osTotal');
    var $minWarning = document.getElementById('osMinWarning');
    var $minWarningText = document.getElementById('osMinWarningText');
    var $error = document.getElementById('osError');
    var $submitOrder = document.getElementById('osSubmitOrder');
    var $submitText = document.getElementById('osSubmitText');
    var $submitSpinner = document.getElementById('osSubmitSpinner');

    // ===== INIT =====
    fetchMenu();

    // ===== API =====
    function fetchMenu() {
        fetch(API + '/tenants/' + SLUG + '/order-menu')
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (!json.success) {
                    showEmpty();
                    return;
                }
                rawMenu = json.data.menu || [];
                settings = json.data.settings || {};
                slots = json.data.slots || [];
                deliveryZones = json.data.delivery_zones || [];
                promotion = json.data.promotion || null;
                tenantInfo = json.data.tenant || {};
                flattenMenu();
                initUI();
            })
            .catch(function () { showEmpty(); });
    }

    function showEmpty() {
        $loading.style.display = 'none';
        $empty.style.display = 'block';
    }

    // Flatten hierarchical menu (parent categories + subcategories) into flat sections
    function flattenMenu() {
        sections = [];
        allItems = [];
        rawMenu.forEach(function (cat) {
            // Items directly in parent category
            if (cat.items && cat.items.length > 0) {
                sections.push({ name: cat.name, items: cat.items });
                cat.items.forEach(function (it) { allItems.push(it); });
            }
            // Subcategories
            if (cat.subcategories && cat.subcategories.length > 0) {
                cat.subcategories.forEach(function (sub) {
                    if (sub.items && sub.items.length > 0) {
                        sections.push({ name: sub.name, items: sub.items });
                        sub.items.forEach(function (it) { allItems.push(it); });
                    }
                });
            }
        });
    }

    // ===== UI INIT =====
    function initUI() {
        $loading.style.display = 'none';

        if (sections.length === 0) {
            $empty.style.display = 'block';
            return;
        }

        // Header status (open/close time)
        renderHeaderStatus();

        // Header initials (fallback when no logo)
        var initEl = document.getElementById('osHeaderInitials');
        if (initEl && tenantInfo.name) {
            var words = tenantInfo.name.split(' ');
            initEl.textContent = words.length >= 2
                ? (words[0][0] + words[1][0]).toUpperCase()
                : tenantInfo.name.substring(0, 2).toUpperCase();
        }

        // Mode bar
        if (settings.ordering_mode === 'both') {
            $modeBar.style.display = 'flex';
        } else {
            orderMode = settings.ordering_mode || 'takeaway';
        }

        // Promo banner
        renderPromoBanner();

        // Search bar
        initSearch();

        renderCategoryNav();

        // Info card
        renderInfoCard();

        renderMenu();
        renderAllergenLegend();
        renderPickupSlots();
        renderPaymentOptions();
        updateCartUI();

        $menu.style.display = 'block';

        // Mode toggle
        $modeBar.addEventListener('click', function (e) {
            var btn = e.target.closest('.os-mode-btn');
            if (!btn) return;
            orderMode = btn.getAttribute('data-mode');
            $modeBar.querySelectorAll('.os-mode-btn').forEach(function (b) {
                b.classList.toggle('os-mode-active', b === btn);
            });
            updateDeliveryInfo();
            updateCartUI();
        });
        updateDeliveryInfo();

        // Cart bar → open checkout
        $cartBtn.addEventListener('click', openCheckout);

        // Checkout close
        $checkoutClose.addEventListener('click', closeCheckout);
        $checkoutOverlay.addEventListener('click', function (e) {
            if (e.target === $checkoutOverlay) closeCheckout();
        });

        // Submit order
        $submitOrder.addEventListener('click', submitOrder);
    }

    // ===== DELIVERY INFO =====
    function updateDeliveryInfo() {
        if (orderMode !== 'delivery' || !isDeliveryAvailable()) {
            $deliveryInfo.style.display = 'none';
            $checkoutDelivery.style.display = 'none';
            return;
        }
        var desc = settings.delivery_description || '';
        var fee = settings.delivery_fee || 0;
        var minAmt = settings.delivery_min_amount || 0;

        // Description text
        $deliveryDesc.textContent = desc || 'Consegna a domicilio';

        // Pills
        var $pills = document.getElementById('osDeliveryPills');
        var pillsHtml = '';
        if (minAmt > 0) {
            pillsHtml += '<span class="os-delivery-pill">Min. \u20ac' + formatPrice(minAmt) + '</span>';
        }
        if (fee > 0) {
            pillsHtml += '<span class="os-delivery-pill">\u20ac' + formatPrice(fee) + ' spedizione</span>';
        } else if (settings.delivery_mode !== 'zones') {
            pillsHtml += '<span class="os-delivery-pill">Spedizione gratuita</span>';
        }
        if ($pills) $pills.innerHTML = pillsHtml;

        $deliveryInfo.style.display = 'flex';
        $checkoutDelivery.style.display = 'block';
    }

    function isDeliveryAvailable() {
        return settings.ordering_mode === 'delivery' || settings.ordering_mode === 'both';
    }

    // ===== HEADER STATUS =====
    function renderHeaderStatus() {
        var $status = document.getElementById('osHeaderStatus');
        if (!$status) return;

        var h = settings.today_hours;
        if (!h || !h.open || !h.close) {
            $status.innerHTML = '<i class="bi bi-clock"></i> Chiuso oggi';
            return;
        }

        var now = new Date();
        var parts = h.close.split(':');
        var closeTime = new Date();
        closeTime.setHours(parseInt(parts[0], 10), parseInt(parts[1], 10), 0);

        var openParts = h.open.split(':');
        var openTime = new Date();
        openTime.setHours(parseInt(openParts[0], 10), parseInt(openParts[1], 10), 0);

        if (now >= openTime && now < closeTime) {
            $status.innerHTML = '<i class="bi bi-clock"></i> Aperto \u00b7 Chiude alle ' + h.close;
        } else if (now < openTime) {
            $status.innerHTML = '<i class="bi bi-clock"></i> Apre alle ' + h.open;
        } else {
            $status.innerHTML = '<i class="bi bi-clock"></i> Chiuso \u00b7 Riapre domani';
        }
    }

    // ===== PROMO BANNER =====
    function renderPromoBanner() {
        var $banner = document.getElementById('osPromoBanner');
        var $text = document.getElementById('osPromoText');
        if (!$banner || !$text || !promotion) return;

        $text.innerHTML = '<strong>-' + promotion.discount_percent + '%</strong> su tutti i piatti \u00b7 ' + escapeHtml(promotion.name);
        $banner.style.display = 'flex';
    }

    // Calculate discounted price
    function getDiscountedPrice(price) {
        if (!promotion || !promotion.discount_percent) return price;
        return Math.round(price * (1 - promotion.discount_percent / 100) * 100) / 100;
    }

    // ===== SEARCH =====
    function initSearch() {
        var $wrap = document.getElementById('osSearchWrap');
        var $input = document.getElementById('osSearchInput');
        if (!$wrap || !$input) return;
        $wrap.style.display = 'block';

        $input.addEventListener('input', function () {
            var q = $input.value.trim().toLowerCase();
            var items = $menu.querySelectorAll('.os-item');
            var sectionEls = $menu.querySelectorAll('.os-section');

            if (!q) {
                // Show all
                items.forEach(function (el) { el.style.display = ''; });
                sectionEls.forEach(function (el) { el.style.display = ''; });
                // Reset category filter
                $catNav.querySelectorAll('.os-cat-pill').forEach(function (p) {
                    p.classList.toggle('os-cat-active', p.getAttribute('data-cat') === 'all');
                });
                return;
            }

            // Reset category to "all" during search
            $catNav.querySelectorAll('.os-cat-pill').forEach(function (p) {
                p.classList.remove('os-cat-active');
            });

            sectionEls.forEach(function (sec) {
                var hasVisible = false;
                sec.querySelectorAll('.os-item').forEach(function (el) {
                    var id = parseInt(el.getAttribute('data-item-id'), 10);
                    var item = findMenuItem(id);
                    var match = item && (
                        item.name.toLowerCase().indexOf(q) !== -1 ||
                        (item.description && item.description.toLowerCase().indexOf(q) !== -1)
                    );
                    el.style.display = match ? '' : 'none';
                    if (match) hasVisible = true;
                });
                sec.style.display = hasVisible ? '' : 'none';
            });
        });
    }

    // ===== INFO CARD =====
    function renderInfoCard() {
        var $card = document.getElementById('osInfoCard');
        if (!$card) return;

        var hasContent = false;

        // Hours
        var $hours = document.getElementById('osInfoHours');
        var h = settings.today_hours;
        if (h && h.open && h.close) {
            $hours.querySelector('span').textContent = h.open + ' \u2013 ' + h.close;
            hasContent = true;
        } else {
            $hours.style.display = 'none';
        }

        // Prep time
        var $prep = document.getElementById('osInfoPrep');
        if (settings.ordering_prep_minutes) {
            $prep.querySelector('span').textContent = '~' + settings.ordering_prep_minutes + ' min';
            hasContent = true;
        } else {
            $prep.style.display = 'none';
        }

        // Address
        var $addr = document.getElementById('osInfoAddress');
        if (tenantInfo.address) {
            $addr.querySelector('span').textContent = tenantInfo.address;
            hasContent = true;
        } else {
            $addr.style.display = 'none';
        }

        // Phone
        var $phone = document.getElementById('osInfoPhone');
        if (tenantInfo.phone) {
            $phone.querySelector('span').innerHTML = '<a href="tel:' + escapeAttr(tenantInfo.phone) + '">' + escapeHtml(tenantInfo.phone) + '</a>';
            hasContent = true;
        } else {
            $phone.style.display = 'none';
        }

        // Email
        var $email = document.getElementById('osInfoEmail');
        if (tenantInfo.email) {
            $email.querySelector('span').innerHTML = '<a href="mailto:' + escapeAttr(tenantInfo.email) + '">' + escapeHtml(tenantInfo.email) + '</a>';
            hasContent = true;
        } else {
            $email.style.display = 'none';
        }

        if (hasContent) $card.style.display = 'block';
    }

    // ===== CATEGORY NAV =====
    function renderCategoryNav() {
        var html = '<button class="os-cat-pill os-cat-active" data-cat="all">Tutto</button>';
        sections.forEach(function (section) {
            html += '<button class="os-cat-pill" data-cat="' + escapeAttr(section.name) + '">'
                + escapeHtml(section.name) + '</button>';
        });
        $catNav.innerHTML = html;

        $catNav.addEventListener('click', function (e) {
            var pill = e.target.closest('.os-cat-pill');
            if (!pill) return;
            $catNav.querySelectorAll('.os-cat-pill').forEach(function (p) {
                p.classList.remove('os-cat-active');
            });
            pill.classList.add('os-cat-active');

            // Clear search on category click
            var $searchInput = document.getElementById('osSearchInput');
            if ($searchInput) $searchInput.value = '';

            // Show all items in sections, then filter by category
            $menu.querySelectorAll('.os-item').forEach(function (el) { el.style.display = ''; });
            var cat = pill.getAttribute('data-cat');
            document.querySelectorAll('.os-section').forEach(function (sec) {
                sec.style.display = (cat === 'all' || sec.getAttribute('data-cat') === cat) ? '' : 'none';
            });
        });
    }

    // ===== MENU RENDER =====
    function renderMenu() {
        var html = '';
        sections.forEach(function (section) {
            html += '<div class="os-section" data-cat="' + escapeAttr(section.name) + '">';
            html += '<div class="os-section-title">' + escapeHtml(section.name)
                + ' <span class="os-section-count">' + section.items.length + '</span></div>';
            section.items.forEach(function (item) {
                html += renderItem(item);
            });
            html += '</div>';
        });
        $menu.innerHTML = html;

        // Add to cart events
        $menu.addEventListener('click', function (e) {
            var addBtn = e.target.closest('.os-add-btn');
            if (addBtn) {
                var id = parseInt(addBtn.getAttribute('data-id'), 10);
                addToCart(id);
                return;
            }
            var plusBtn = e.target.closest('.os-qty-btn:not(.os-qty-btn--minus)');
            if (plusBtn) {
                var id = parseInt(plusBtn.getAttribute('data-id'), 10);
                addToCart(id);
                return;
            }
            var minusBtn = e.target.closest('.os-qty-btn--minus');
            if (minusBtn) {
                var id = parseInt(minusBtn.getAttribute('data-id'), 10);
                removeFromCart(id);
                return;
            }
        });
    }

    function renderAllergens(allergens) {
        if (!allergens || !allergens.length) return '';
        var html = '<div class="os-item-allergens">';
        allergens.forEach(function (key) {
            var label = ALLERGEN_LABELS[key] || key;
            html += '<span class="os-allergen-tag os-at-' + escapeAttr(key) + '">'
                + '<span class="os-allergen-tag-dot"></span>'
                + escapeHtml(label)
                + '</span>';
        });
        html += '</div>';
        return html;
    }

    function renderAllergenLegend() {
        var $legend = document.getElementById('osAllergenLegend');
        var $grid = document.getElementById('osLegendGrid');
        if (!$legend || !$grid) return;

        var html = '';
        // All 14 EU-mandated allergens, always shown
        var order = ['gluten','crustaceans','eggs','fish','peanuts','soy','milk','nuts','celery','mustard','sesame','sulphites','lupin','molluscs'];
        order.forEach(function (key) {
            var label = ALLERGEN_LABELS[key] || key;
            html += '<span class="os-allergen-tag os-at-' + escapeAttr(key) + '">'
                + '<span class="os-allergen-tag-dot"></span>'
                + escapeHtml(label)
                + '</span>';
        });
        $grid.innerHTML = html;
        $legend.style.display = 'block';
    }

    function renderItem(item) {
        var inCart = cart[item.id];
        var qty = inCart ? inCart.qty : 0;
        var img = item.image_url
            ? '<img src="' + escapeAttr(item.image_url) + '" class="os-item-img" alt="' + escapeAttr(item.name) + '">'
            : '<div class="os-item-img-placeholder"><i class="bi bi-egg-fried"></i></div>';

        var controls = qty > 0
            ? '<div class="os-qty-controls">'
            + '<button class="os-qty-btn os-qty-btn--minus" data-id="' + item.id + '">\u2212</button>'
            + '<span class="os-qty-value">' + qty + '</span>'
            + '<button class="os-qty-btn" data-id="' + item.id + '">+</button>'
            + '</div>'
            : '<button class="os-add-btn" data-id="' + item.id + '"><i class="bi bi-plus"></i></button>';

        var desc = item.description
            ? '<div class="os-item-desc">' + escapeHtml(item.description) + '</div>'
            : '';

        var allergensHtml = renderAllergens(item.allergens);

        // Price with promo badge inline (badge right next to price)
        var originalPrice = parseFloat(item.price);
        var discountedPrice = getDiscountedPrice(originalPrice);
        var priceHtml;
        if (promotion && discountedPrice < originalPrice) {
            priceHtml = '<span class="os-item-price">\u20ac' + formatPrice(discountedPrice)
                + ' <span class="os-item-price-old">\u20ac' + formatPrice(originalPrice) + '</span>'
                + ' <span class="os-item-promo-badge">-' + promotion.discount_percent + '%</span>'
                + '</span>';
        } else {
            priceHtml = '<span class="os-item-price">\u20ac' + formatPrice(originalPrice) + '</span>';
        }

        return '<div class="os-item" data-item-id="' + item.id + '">'
            + img
            + '<div class="os-item-body">'
            + '<div class="os-item-name">' + escapeHtml(item.name) + '</div>'
            + desc
            + allergensHtml
            + '<div class="os-item-footer">'
            + priceHtml
            + controls
            + '</div>'
            + '</div>'
            + '</div>';
    }

    // ===== CART =====
    function loadCart() {
        try {
            return JSON.parse(localStorage.getItem(CART_KEY)) || {};
        } catch (e) { return {}; }
    }

    function saveCart() {
        localStorage.setItem(CART_KEY, JSON.stringify(cart));
    }

    function findMenuItem(id) {
        for (var i = 0; i < allItems.length; i++) {
            if (allItems[i].id === id) return allItems[i];
        }
        return null;
    }

    function addToCart(id) {
        if (cart[id]) {
            cart[id].qty++;
        } else {
            var item = findMenuItem(id);
            if (!item) return;
            var price = getDiscountedPrice(parseFloat(item.price));
            cart[id] = { id: item.id, name: item.name, price: price, qty: 1, notes: '' };
        }
        saveCart();
        updateCartUI();
        updateItemUI(id);
    }

    function removeFromCart(id) {
        if (!cart[id]) return;
        cart[id].qty--;
        if (cart[id].qty <= 0) delete cart[id];
        saveCart();
        updateCartUI();
        updateItemUI(id);
    }

    function updateItemUI(id) {
        var el = $menu.querySelector('[data-item-id="' + id + '"]');
        if (!el) return;
        var item = findMenuItem(id);
        if (!item) return;
        var footer = el.querySelector('.os-item-footer');
        var inCart = cart[id];
        var qty = inCart ? inCart.qty : 0;
        var controls = qty > 0
            ? '<div class="os-qty-controls">'
            + '<button class="os-qty-btn os-qty-btn--minus" data-id="' + id + '">\u2212</button>'
            + '<span class="os-qty-value">' + qty + '</span>'
            + '<button class="os-qty-btn" data-id="' + id + '">+</button>'
            + '</div>'
            : '<button class="os-add-btn" data-id="' + id + '"><i class="bi bi-plus"></i></button>';

        var originalPrice = parseFloat(item.price);
        var discountedPrice = getDiscountedPrice(originalPrice);
        var priceHtml;
        if (promotion && discountedPrice < originalPrice) {
            priceHtml = '<span class="os-item-price">\u20ac' + formatPrice(discountedPrice)
                + ' <span class="os-item-price-old">\u20ac' + formatPrice(originalPrice) + '</span>'
                + ' <span class="os-item-promo-badge">-' + promotion.discount_percent + '%</span>'
                + '</span>';
        } else {
            priceHtml = '<span class="os-item-price">\u20ac' + formatPrice(originalPrice) + '</span>';
        }
        footer.innerHTML = priceHtml + controls;
    }

    function getCartSubtotal() {
        var total = 0;
        Object.keys(cart).forEach(function (id) { total += cart[id].price * cart[id].qty; });
        return total;
    }

    function getCartCount() {
        var count = 0;
        Object.keys(cart).forEach(function (id) { count += cart[id].qty; });
        return count;
    }

    function calculateDeliveryFee() {
        if (orderMode !== 'delivery') return 0;
        if (settings.delivery_mode === 'zones') {
            var cap = (document.getElementById('osDeliveryCap') || {}).value || '';
            if (!cap) return 0;
            for (var i = 0; i < deliveryZones.length; i++) {
                var codes = deliveryZones[i].postal_codes;
                if (Array.isArray(codes) && codes.indexOf(cap) !== -1) {
                    return deliveryZones[i].fee;
                }
            }
            return 0;
        }
        return settings.delivery_fee || 0;
    }

    function updateCartUI() {
        var count = getCartCount();
        var subtotal = getCartSubtotal();
        deliveryFee = calculateDeliveryFee();
        var total = subtotal + deliveryFee;

        // Cart bar
        if (count > 0) {
            $cartBar.style.display = 'block';
            $cartCount.textContent = count;
            $cartTotal.textContent = '€' + formatPrice(total);
        } else {
            $cartBar.style.display = 'none';
        }

        // Checkout totals
        $subtotal.textContent = '€' + formatPrice(subtotal);
        if (orderMode === 'delivery' && deliveryFee > 0) {
            $deliveryFeeRow.style.display = '';
            $deliveryFeeTotal.textContent = '€' + formatPrice(deliveryFee);
        } else {
            $deliveryFeeRow.style.display = 'none';
        }
        $total.textContent = '€' + formatPrice(total);

        // Min amount warning
        var minAmount = getMinAmount();
        if (minAmount > 0 && subtotal < minAmount && count > 0) {
            $minWarning.style.display = 'flex';
            $minWarningText.textContent = 'Ordine minimo: €' + formatPrice(minAmount) + '. Mancano €' + formatPrice(minAmount - subtotal);
            $submitOrder.disabled = true;
        } else {
            $minWarning.style.display = 'none';
            $submitOrder.disabled = count === 0;
        }
    }

    function getMinAmount() {
        if (orderMode === 'delivery') {
            if (settings.delivery_mode === 'zones') {
                var cap = (document.getElementById('osDeliveryCap') || {}).value || '';
                for (var i = 0; i < deliveryZones.length; i++) {
                    var codes = deliveryZones[i].postal_codes;
                    if (Array.isArray(codes) && codes.indexOf(cap) !== -1) {
                        return deliveryZones[i].min_amount || 0;
                    }
                }
                return settings.delivery_min_amount || 0;
            }
            return settings.delivery_min_amount || 0;
        }
        return settings.ordering_min_amount || 0;
    }

    // ===== PICKUP SLOTS =====
    function renderPickupSlots() {
        var html = '<option value="">Seleziona orario...</option>';
        if (slots.length === 0) {
            html += '<option value="" disabled>Nessuno slot disponibile oggi</option>';
        }
        slots.forEach(function (s) {
            html += '<option value="' + escapeAttr(s.datetime) + '">'
                + escapeHtml(s.time)
                + (s.available <= 3 ? ' (ultimi ' + s.available + ' posti)' : '')
                + '</option>';
        });
        $pickupTime.innerHTML = html;
    }

    // ===== PAYMENT =====
    function renderPaymentOptions() {
        var methods = (settings.ordering_payment_methods || 'cash').split(',');
        var html = '';
        methods.forEach(function (m, i) {
            var selected = i === 0 ? ' os-pay-selected' : '';
            if (i === 0) selectedPayment = m;
            if (m === 'cash') {
                html += '<div class="os-pay-option' + selected + '" data-method="cash">'
                    + '<span class="os-pay-radio"></span>'
                    + '<i class="bi bi-cash-stack os-pay-icon"></i>'
                    + '<div class="os-pay-label">Pago al ritiro<small>Contanti o carta al locale</small></div>'
                    + '</div>';
            } else if (m === 'stripe') {
                html += '<div class="os-pay-option' + selected + '" data-method="stripe">'
                    + '<span class="os-pay-radio"></span>'
                    + '<i class="bi bi-credit-card-2-back os-pay-icon"></i>'
                    + '<div class="os-pay-label">Paga ora con carta<small>Visa, Mastercard, Apple Pay</small></div>'
                    + '</div>';
            }
        });
        $paymentOptions.innerHTML = html;

        $paymentOptions.addEventListener('click', function (e) {
            var opt = e.target.closest('.os-pay-option');
            if (!opt) return;
            $paymentOptions.querySelectorAll('.os-pay-option').forEach(function (o) {
                o.classList.remove('os-pay-selected');
            });
            opt.classList.add('os-pay-selected');
            selectedPayment = opt.getAttribute('data-method');
        });
    }

    // ===== CHECKOUT =====
    function openCheckout() {
        renderCheckoutCart();
        updateDeliveryInfo();
        updateCartUI();
        $error.style.display = 'none';
        $checkoutOverlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeCheckout() {
        $checkoutOverlay.style.display = 'none';
        document.body.style.overflow = '';
    }

    function renderCheckoutCart() {
        var html = '';
        Object.keys(cart).forEach(function (id) {
            var item = cart[id];
            var hasNotes = item.notes && item.notes.trim();
            html += '<div class="os-cart-item">'
                + '<span class="os-cart-item-qty">' + item.qty + '</span>'
                + '<div class="os-cart-item-detail">'
                + '<span class="os-cart-item-name">' + escapeHtml(item.name) + '</span>'
                + (hasNotes
                    ? '<div class="os-cart-item-note-preview"><i class="bi bi-chat-left-text"></i> ' + escapeHtml(item.notes) + '</div>'
                    : '')
                + '<button class="os-cart-item-note-toggle' + (hasNotes ? ' has-note' : '') + '" data-id="' + id + '" title="Aggiungi nota">'
                + '<i class="bi bi-pencil-square"></i> '
                + (hasNotes ? 'Modifica nota' : 'Aggiungi nota')
                + '</button>'
                + '<input type="text" class="os-cart-item-note-input" data-id="' + id + '" placeholder="Es: senza carciofi, aggiunta patatine..." value="' + escapeAttr(item.notes || '') + '" style="display:none;" maxlength="200">'
                + '</div>'
                + '<span class="os-cart-item-price">\u20ac' + formatPrice(item.price * item.qty) + '</span>'
                + '<button class="os-cart-item-remove" data-id="' + id + '" title="Rimuovi"><i class="bi bi-trash"></i></button>'
                + '</div>';
        });
        if (!html) html = '<div style="text-align:center;color:#8a8a8a;padding:.75rem;">Carrello vuoto</div>';
        $checkoutCart.innerHTML = html;

        // Event delegation for checkout cart
        $checkoutCart.addEventListener('click', function (e) {
            // Remove item
            var btn = e.target.closest('.os-cart-item-remove');
            if (btn) {
                var id = btn.getAttribute('data-id');
                delete cart[id];
                saveCart();
                renderCheckoutCart();
                updateCartUI();
                updateItemUI(parseInt(id, 10));
                if (getCartCount() === 0) closeCheckout();
                return;
            }
            // Toggle note input
            var noteBtn = e.target.closest('.os-cart-item-note-toggle');
            if (noteBtn) {
                var id = noteBtn.getAttribute('data-id');
                var input = $checkoutCart.querySelector('.os-cart-item-note-input[data-id="' + id + '"]');
                if (input) {
                    var show = input.style.display === 'none';
                    input.style.display = show ? 'block' : 'none';
                    if (show) input.focus();
                }
            }
        });

        // Save note on input change
        $checkoutCart.addEventListener('input', function (e) {
            if (e.target.classList.contains('os-cart-item-note-input')) {
                var id = e.target.getAttribute('data-id');
                if (cart[id]) {
                    cart[id].notes = e.target.value;
                    saveCart();
                    // Update preview
                    var item = e.target.closest('.os-cart-item');
                    var preview = item ? item.querySelector('.os-cart-item-note-preview') : null;
                    var toggle = item ? item.querySelector('.os-cart-item-note-toggle') : null;
                    if (e.target.value.trim()) {
                        if (!preview) {
                            preview = document.createElement('div');
                            preview.className = 'os-cart-item-note-preview';
                            var toggleEl = item.querySelector('.os-cart-item-note-toggle');
                            if (toggleEl) toggleEl.parentNode.insertBefore(preview, toggleEl);
                        }
                        preview.innerHTML = '<i class="bi bi-chat-left-text"></i> ' + escapeHtml(e.target.value);
                        if (toggle) { toggle.classList.add('has-note'); toggle.innerHTML = '<i class="bi bi-pencil-square"></i> Modifica nota'; }
                    } else {
                        if (preview) preview.remove();
                        if (toggle) { toggle.classList.remove('has-note'); toggle.innerHTML = '<i class="bi bi-pencil-square"></i> Aggiungi nota'; }
                    }
                }
            }
        });
    }

    // CAP input → recalculate delivery fee
    document.addEventListener('input', function (e) {
        if (e.target.id === 'osDeliveryCap') {
            deliveryFee = calculateDeliveryFee();
            updateCartUI();
            // Show fee label
            var label = document.getElementById('osDeliveryFeeLabel');
            if (label) {
                if (settings.delivery_mode === 'zones') {
                    // Zone mode: match CAP against delivery_zones
                    if (deliveryFee > 0) {
                        label.textContent = '+ €' + formatPrice(deliveryFee) + ' consegna';
                        label.style.color = '#198754';
                    } else if (e.target.value.length === 5) {
                        label.textContent = 'Zona non coperta';
                        label.style.color = '#dc3545';
                    } else {
                        label.textContent = '';
                    }
                } else {
                    // Simple mode: fee fisso, CAP sempre accettato
                    if (deliveryFee > 0) {
                        label.textContent = '+ €' + formatPrice(deliveryFee) + ' consegna';
                        label.style.color = '#198754';
                    } else {
                        label.textContent = e.target.value.length === 5 ? 'Consegna gratuita' : '';
                        label.style.color = '#198754';
                    }
                }
            }
        }
    });

    // ===== SUBMIT ORDER =====
    function submitOrder() {
        $error.style.display = 'none';

        // Validate
        var name = (document.getElementById('osCustomerName') || {}).value || '';
        var phone = (document.getElementById('osCustomerPhone') || {}).value || '';
        var email = (document.getElementById('osCustomerEmail') || {}).value || '';
        var notes = (document.getElementById('osOrderNotes') || {}).value || '';
        var pickup = $pickupTime.value;

        if (!name.trim()) return showError('Inserisci il tuo nome.');
        if (!phone.trim()) return showError('Inserisci il tuo numero di telefono.');

        var items = [];
        Object.keys(cart).forEach(function (id) {
            var entry = {
                menu_item_id: parseInt(id, 10),
                item_name: cart[id].name,
                quantity: cart[id].qty,
            };
            if (cart[id].notes && cart[id].notes.trim()) {
                entry.notes = cart[id].notes.trim();
            }
            items.push(entry);
        });

        if (items.length === 0) return showError('Il carrello è vuoto.');

        var body = {
            customer_name: name.trim(),
            customer_phone: phone.trim(),
            customer_email: email.trim() || undefined,
            order_type: orderMode,
            payment_method: selectedPayment,
            pickup_time: pickup || undefined,
            items: items,
            notes: notes.trim() || undefined,
        };

        // Delivery fields
        if (orderMode === 'delivery') {
            var addr = (document.getElementById('osDeliveryAddress') || {}).value || '';
            var cap = (document.getElementById('osDeliveryCap') || {}).value || '';
            var dNotes = (document.getElementById('osDeliveryNotes') || {}).value || '';
            if (!addr.trim()) return showError('Inserisci l\'indirizzo di consegna.');
            if (!cap.trim()) return showError('Inserisci il CAP di consegna.');
            body.delivery_address = addr.trim();
            body.delivery_cap = cap.trim();
            body.delivery_notes = dNotes.trim() || undefined;
        }

        // Disable button
        $submitOrder.disabled = true;
        $submitText.textContent = 'Invio in corso...';
        $submitSpinner.style.display = 'inline-block';

        fetch(API + '/tenants/' + SLUG + '/orders', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(body),
        })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (!json.success) {
                    showError(json.message || 'Errore durante l\'invio dell\'ordine.');
                    resetSubmit();
                    return;
                }

                // Clear cart
                cart = {};
                saveCart();

                // Redirect
                if (json.data && json.data.stripe_checkout_url) {
                    window.location.href = json.data.stripe_checkout_url;
                } else {
                    var orderNum = json.data ? json.data.order_number : '';
                    window.location.href = BASE + '/' + SLUG + '/order/success?order=' + encodeURIComponent(orderNum);
                }
            })
            .catch(function () {
                showError('Errore di rete. Riprova.');
                resetSubmit();
            });
    }

    function showError(msg) {
        $error.textContent = msg;
        $error.style.display = 'block';
        $error.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function resetSubmit() {
        $submitOrder.disabled = getCartCount() === 0;
        $submitText.textContent = 'Invia ordine';
        $submitSpinner.style.display = 'none';
    }

    // ===== HELPERS =====
    function formatPrice(n) {
        return parseFloat(n).toFixed(2).replace('.', ',');
    }

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function escapeAttr(s) {
        return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
})();
