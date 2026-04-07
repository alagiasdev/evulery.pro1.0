/**
 * dashboard-orders.js — Dashboard order kanban polling + status actions
 * Loaded via $pageScripts on orders/index.php
 */
(function () {
    'use strict';

    var POLL_INTERVAL = 15000; // 15s
    var pollTimer = null;
    var previousCount = -1;
    var soundEnabled = true;
    var BASE = (window.DO_BASE || '').replace(/\/+$/, '');

    // CSRF token from meta or form
    var csrfToken = '';
    var csrfInput = document.querySelector('input[name="_csrf"]');
    if (csrfInput) csrfToken = csrfInput.value;
    if (!csrfToken) {
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) csrfToken = csrfMeta.getAttribute('content');
    }

    // ===== STATUS CHANGE (button click) =====
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.do-status-btn');
        if (!btn) return;
        e.preventDefault();

        var orderId = btn.getAttribute('data-order-id');
        var newStatus = btn.getAttribute('data-status');

        if (newStatus === 'rejected') {
            var reason = prompt('Motivo del rifiuto (opzionale):');
            if (reason === null) return; // Cancelled
            changeStatus(orderId, newStatus, reason);
        } else if (newStatus === 'cancelled') {
            if (!confirm('Annullare questo ordine?')) return;
            changeStatus(orderId, newStatus, '');
        } else {
            changeStatus(orderId, newStatus, '');
        }
    });

    function changeStatus(orderId, status, reason) {
        var formData = new FormData();
        formData.append('status', status);
        formData.append('_csrf', csrfToken);
        if (reason) formData.append('rejected_reason', reason);

        fetch(BASE + '/dashboard/orders/' + orderId + '/status', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData,
        })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (json.success) {
                    refreshKanban();
                } else {
                    alert('Errore: ' + (json.message || 'operazione fallita'));
                }
            })
            .catch(function () {
                alert('Errore di rete.');
            });
    }

    // ===== KANBAN POLLING =====
    function refreshKanban() {
        fetch(BASE + '/dashboard/orders/api/kanban', {
            headers: { 'Accept': 'application/json' },
        })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (!json.success) return;
                updateKanbanDOM(json.kanban || {});
                updateCompletedTable(json.completed || []);
            })
            .catch(function () { /* silent */ });

        // Stats
        fetch(BASE + '/dashboard/orders/api/stats', {
            headers: { 'Accept': 'application/json' },
        })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (!json.success) return;
                var s = json.stats || {};
                setText('doStatTotal', s.total_orders || 0);
                setText('doStatRevenue', '€ ' + formatPrice(s.revenue || 0));
                setText('doStatTakeaway', s.takeaway_count || 0);
                setText('doStatDelivery', s.delivery_count || 0);
            })
            .catch(function () { /* silent */ });
    }

    function updateKanbanDOM(kanban) {
        var statuses = ['pending', 'accepted', 'preparing', 'ready'];
        var totalPending = (kanban['pending'] || []).length;

        statuses.forEach(function (status) {
            var container = document.querySelector('.do-kanban-cards[data-status="' + status + '"]');
            if (!container) return;

            var orders = kanban[status] || [];
            var countBadge = document.querySelector('.do-count[data-status="' + status + '"]');
            if (countBadge) countBadge.textContent = orders.length;

            if (orders.length === 0) {
                container.innerHTML = '<div class="do-kanban-empty">Nessun ordine</div>';
                return;
            }

            var html = '';
            orders.forEach(function (o) {
                html += renderOrderCard(o);
            });
            container.innerHTML = html;
        });

        // New order sound
        if (previousCount >= 0 && totalPending > previousCount && soundEnabled) {
            playNotificationSound();
            showNewOrderAlert(kanban['pending'] || []);
        }
        previousCount = totalPending;
    }

    function renderOrderCard(o) {
        var isDelivery = o.order_type === 'delivery';
        var minutes = Math.floor((Date.now() / 1000 - new Date(o.created_at).getTime() / 1000) / 60);
        var isLate = minutes > 30;

        var typeClass = isDelivery ? ' do-order-type--delivery' : '';
        var typeIcon = isDelivery ? 'bi-truck' : 'bi-bag';
        var typeLabel = isDelivery ? 'Consegna' : 'Asporto';

        var pickupHtml = '';
        if (o.pickup_time) {
            var t = new Date(o.pickup_time);
            pickupHtml = '<div class="do-order-time"><i class="bi bi-clock me-1"></i> '
                + pad(t.getHours()) + ':' + pad(t.getMinutes()) + '</div>';
        }

        var addressHtml = '';
        if (isDelivery && o.delivery_address) {
            var addr = o.delivery_address.length > 40 ? o.delivery_address.substring(0, 40) + '…' : o.delivery_address;
            addressHtml = '<div class="do-order-address"><i class="bi bi-geo-alt me-1"></i> ' + escapeHtml(addr) + '</div>';
        }

        var transitions = getTransitions(o.status);
        var actionsHtml = '';
        transitions.forEach(function (next) {
            if (next === 'rejected') {
                actionsHtml += '<button class="btn btn-sm btn-outline-danger do-status-btn" data-order-id="' + o.id + '" data-status="rejected" title="Rifiuta"><i class="bi bi-x-circle"></i></button>';
            } else if (next === 'cancelled') {
                actionsHtml += '<button class="btn btn-sm btn-outline-danger do-status-btn" data-order-id="' + o.id + '" data-status="cancelled" title="Annulla"><i class="bi bi-x-lg"></i></button>';
            } else {
                actionsHtml += '<button class="btn btn-sm btn-success do-status-btn" data-order-id="' + o.id + '" data-status="' + next + '">' + getStatusLabel(next) + ' <i class="bi bi-arrow-right"></i></button>';
            }
        });
        actionsHtml += '<a href="' + BASE + '/dashboard/orders/' + o.id + '" class="btn btn-sm btn-outline-secondary" title="Dettaglio"><i class="bi bi-eye"></i></a>';

        return '<div class="do-order-card" data-order-id="' + o.id + '">'
            + '<div class="do-order-card-header">'
            + '<strong>' + escapeHtml(o.order_number) + '</strong>'
            + '<span class="do-order-type' + typeClass + '"><i class="bi ' + typeIcon + '"></i> ' + typeLabel + '</span>'
            + '</div>'
            + '<div class="do-order-card-body">'
            + '<div class="do-order-customer"><i class="bi bi-person me-1"></i> ' + escapeHtml(o.customer_name) + '</div>'
            + pickupHtml
            + addressHtml
            + '<div class="do-order-total">€ ' + formatPrice(o.total) + '</div>'
            + '</div>'
            + '<div class="do-order-card-timer' + (isLate ? ' do-order-late' : '') + '">'
            + '<i class="bi bi-stopwatch me-1"></i> ' + minutes + ' min'
            + '</div>'
            + '<div class="do-order-card-actions">' + actionsHtml + '</div>'
            + '</div>';
    }

    function updateCompletedTable(completed) {
        // Simple: we won't re-render the completed table on poll since it's less critical
        // The full page refresh on status change handles this
    }

    function getTransitions(status) {
        switch (status) {
            case 'pending': return ['accepted', 'rejected'];
            case 'accepted': return ['preparing', 'cancelled'];
            case 'preparing': return ['ready', 'cancelled'];
            case 'ready': return ['completed'];
            default: return [];
        }
    }

    function getStatusLabel(status) {
        switch (status) {
            case 'pending': return 'In attesa';
            case 'accepted': return 'Accettato';
            case 'preparing': return 'In preparazione';
            case 'ready': return 'Pronto';
            case 'completed': return 'Completato';
            case 'cancelled': return 'Annullato';
            case 'rejected': return 'Rifiutato';
            default: return status;
        }
    }

    // ===== AUDIO NOTIFICATION =====
    function playNotificationSound() {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.type = 'sine';
            osc.frequency.value = 880;
            gain.gain.value = 0.3;
            osc.start();
            osc.frequency.setValueAtTime(880, ctx.currentTime);
            osc.frequency.setValueAtTime(1100, ctx.currentTime + 0.1);
            osc.frequency.setValueAtTime(880, ctx.currentTime + 0.2);
            gain.gain.setValueAtTime(0.3, ctx.currentTime + 0.3);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
            osc.stop(ctx.currentTime + 0.5);
        } catch (e) { /* Audio API not available */ }
    }

    function showNewOrderAlert(pendingOrders) {
        if (!pendingOrders.length) return;
        var latest = pendingOrders[pendingOrders.length - 1];
        var alertEl = document.createElement('div');
        alertEl.className = 'do-alert';
        alertEl.innerHTML = '<i class="bi bi-bell-fill"></i> '
            + '<span>Nuovo ordine ' + escapeHtml(latest.order_number) + '!</span> '
            + '<button class="do-alert-close"><i class="bi bi-x"></i></button>';
        document.body.appendChild(alertEl);

        alertEl.querySelector('.do-alert-close').addEventListener('click', function () {
            alertEl.remove();
        });

        setTimeout(function () { alertEl.remove(); }, 5000);
    }

    // ===== START POLLING =====
    function startPolling() {
        // Initial count
        var pending = document.querySelectorAll('.do-kanban-cards[data-status="pending"] .do-order-card');
        previousCount = pending.length;

        pollTimer = setInterval(refreshKanban, POLL_INTERVAL);
    }

    startPolling();

    // ===== HELPERS =====
    function setText(id, text) {
        var el = document.getElementById(id);
        if (el) el.textContent = text;
    }

    function formatPrice(n) {
        return parseFloat(n || 0).toFixed(2).replace('.', ',');
    }

    function pad(n) { return n < 10 ? '0' + n : String(n); }

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }
})();
