/**
 * Dashboard Notifications — polling, dropdown, push subscription
 */
(function () {
    'use strict';

    // Config from script tag data attributes
    var script = document.currentScript || document.querySelector('script[data-unread-url]');
    if (!script) return;

    var cfg = {
        unreadUrl:    script.getAttribute('data-unread-url'),
        recentUrl:    script.getAttribute('data-recent-url'),
        markReadUrl:  script.getAttribute('data-mark-read-url'),  // base: .../notifications
        markAllUrl:   script.getAttribute('data-mark-all-url'),
        vapidUrl:     script.getAttribute('data-vapid-url'),
        subscribeUrl: script.getAttribute('data-subscribe-url'),
        csrf:         script.getAttribute('data-csrf')
    };

    var POLL_INTERVAL = 30000; // 30 seconds
    var pollTimer = null;

    // ========== DOM refs ==========
    var bellBtn       = document.getElementById('notif-bell-btn');
    var bellBtnMobile = document.getElementById('notif-bell-btn-mobile');
    var badge         = document.getElementById('notif-badge');
    var badgeMobile   = document.getElementById('notif-badge-mobile');
    var dropdown      = document.getElementById('notif-dropdown');
    var notifList     = document.getElementById('notif-list');
    var markAllBtn    = document.getElementById('notif-mark-all');

    // ========== Badge update ==========
    function updateBadge(count) {
        var n = parseInt(count, 10) || 0;
        [badge, badgeMobile].forEach(function (el) {
            if (!el) return;
            if (n > 0) {
                el.textContent = n > 99 ? '99+' : n;
                el.style.display = '';
            } else {
                el.style.display = 'none';
            }
        });
    }

    function pollUnread() {
        fetch(cfg.unreadUrl, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) { updateBadge(data.count); })
            .catch(function () { /* silent */ });
    }

    // ========== Time ago ==========
    function timeAgo(dateStr) {
        var now = Date.now();
        var then = new Date(dateStr).getTime();
        var diff = Math.floor((now - then) / 1000);
        if (diff < 60) return 'ora';
        if (diff < 3600) return Math.floor(diff / 60) + ' min fa';
        if (diff < 86400) return Math.floor(diff / 3600) + ' ore fa';
        if (diff < 604800) return Math.floor(diff / 86400) + ' giorni fa';
        return new Date(dateStr).toLocaleDateString('it-IT');
    }

    // ========== Icon class by type ==========
    function iconForType(type) {
        switch (type) {
            case 'new_reservation': return { cls: 'notif-item-icon--new', icon: 'bi-calendar-plus' };
            case 'cancellation':    return { cls: 'notif-item-icon--cancel', icon: 'bi-calendar-x' };
            case 'deposit_received': return { cls: 'notif-item-icon--deposit', icon: 'bi-cash-coin' };
            default:                return { cls: 'notif-item-icon--default', icon: 'bi-bell' };
        }
    }

    // ========== Render dropdown items ==========
    function renderNotifications(items) {
        if (!notifList) return;
        if (!items || items.length === 0) {
            notifList.innerHTML = '<div class="notif-empty">Nessuna notifica</div>';
            return;
        }
        var html = '';
        items.forEach(function (n) {
            var ico = iconForType(n.type);
            var unread = !n.read_at ? ' notif-item--unread' : '';
            var dataJson = n.data || {};
            var url = dataJson.url || '#';
            var markBtn = !n.read_at
                ? '<button class="notif-item-mark" data-mark-id="' + n.id + '" title="Segna come letta"><i class="bi bi-check2"></i></button>'
                : '';
            html += '<div class="notif-item' + unread + '" data-id="' + n.id + '" data-url="' + escHtml(url) + '">'
                + '<div class="notif-item-icon ' + ico.cls + '"><i class="bi ' + ico.icon + '"></i></div>'
                + '<div class="notif-item-content">'
                + '<div class="notif-item-title">' + escHtml(n.title) + '</div>'
                + '<div class="notif-item-body">' + escHtml(n.body || '') + '</div>'
                + '<div class="notif-item-time">' + timeAgo(n.created_at) + '</div>'
                + '</div>'
                + markBtn
                + '</div>';
        });
        notifList.innerHTML = html;

        // Click on mark-read button (single)
        notifList.querySelectorAll('.notif-item-mark').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var id = btn.getAttribute('data-mark-id');
                markRead(id, function () { fetchRecent(); });
            });
        });

        // Click on item → navigate
        notifList.querySelectorAll('.notif-item').forEach(function (el) {
            el.addEventListener('click', function (e) {
                if (e.target.closest('.notif-item-mark')) return;
                var id = el.getAttribute('data-id');
                var url = el.getAttribute('data-url');
                markRead(id, function () {
                    if (url && url !== '#') window.location = url;
                });
            });
        });
    }

    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // ========== Fetch recent ==========
    function fetchRecent() {
        fetch(cfg.recentUrl, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) { renderNotifications(data.notifications || []); })
            .catch(function () { /* silent */ });
    }

    // ========== Mark read ==========
    function markRead(id, cb) {
        fetch(cfg.markReadUrl + '/' + id + '/read', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: '_csrf=' + encodeURIComponent(cfg.csrf)
        }).then(function () {
            if (cb) cb();
            pollUnread();
        }).catch(function () { if (cb) cb(); });
    }

    function markAllRead() {
        fetch(cfg.markAllUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
            body: '_csrf=' + encodeURIComponent(cfg.csrf)
        }).then(function () {
            updateBadge(0);
            fetchRecent();
        }).catch(function () { /* silent */ });
    }

    // ========== Dropdown toggle ==========
    function toggleDropdown() {
        if (!dropdown) return;
        var isOpen = dropdown.classList.contains('open');
        if (isOpen) {
            dropdown.classList.remove('open');
        } else {
            fetchRecent();
            dropdown.classList.add('open');
        }
    }

    function closeDropdown() {
        if (dropdown) dropdown.classList.remove('open');
    }

    // Desktop bell
    if (bellBtn) {
        bellBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            toggleDropdown();
            // Trigger push permission on first click (requires user gesture)
            subscribeToPush();
        });
    }

    // Mobile bell — navigate to notifications page
    if (bellBtnMobile) {
        bellBtnMobile.addEventListener('click', function () {
            // Trigger push permission on first click (requires user gesture)
            subscribeToPush();
            window.location = cfg.markReadUrl; // -> /dashboard/notifications
        });
    }

    // Mark all
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            markAllRead();
        });
    }

    // Close dropdown on outside click
    document.addEventListener('click', function (e) {
        if (dropdown && !dropdown.contains(e.target) && e.target !== bellBtn) {
            closeDropdown();
        }
    });

    // ========== Push Subscription ==========
    var swRegistration = null;

    function registerServiceWorker() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;

        navigator.serviceWorker.register('/sw-push.js').then(function (reg) {
            swRegistration = reg;
            // If already subscribed, nothing more to do
            reg.pushManager.getSubscription().then(function (sub) {
                if (sub) return;
                // Not subscribed yet — will subscribe on first bell click
            });
        }).catch(function (err) {
            console.warn('Service worker registration failed:', err);
        });
    }

    function subscribeToPush() {
        if (!swRegistration) return;

        // Check if already subscribed
        swRegistration.pushManager.getSubscription().then(function (sub) {
            if (sub) return; // already done

            // Check permission status
            if (Notification.permission === 'denied') return;

            // Get VAPID key and subscribe (triggered by user gesture)
            fetch(cfg.vapidUrl, { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.key) return;
                    var vapidKey = urlBase64ToUint8Array(data.key);
                    swRegistration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: vapidKey
                    }).then(function (subscription) {
                        sendSubscriptionToServer(subscription);
                    }).catch(function (err) {
                        console.warn('Push subscription failed:', err);
                    });
                });
        });
    }

    function sendSubscriptionToServer(subscription) {
        var key = subscription.getKey('p256dh');
        var auth = subscription.getKey('auth');

        var params = new URLSearchParams();
        params.append('_csrf', cfg.csrf);
        params.append('endpoint', subscription.endpoint);
        params.append('p256dh', btoa(String.fromCharCode.apply(null, new Uint8Array(key))));
        params.append('auth', btoa(String.fromCharCode.apply(null, new Uint8Array(auth))));

        fetch(cfg.subscribeUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        }).catch(function () { /* silent */ });
    }

    function urlBase64ToUint8Array(base64String) {
        var padding = '='.repeat((4 - base64String.length % 4) % 4);
        var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        var rawData = atob(base64);
        var outputArray = new Uint8Array(rawData.length);
        for (var i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    // ========== Init ==========
    pollUnread();
    pollTimer = setInterval(pollUnread, POLL_INTERVAL);

    // Pause polling when tab is hidden, resume when visible
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            clearInterval(pollTimer);
            pollTimer = null;
        } else {
            pollUnread();
            pollTimer = setInterval(pollUnread, POLL_INTERVAL);
        }
    });

    // Register service worker (permission asked on first bell click)
    registerServiceWorker();
})();
