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
        deleteAllUrl: script.getAttribute('data-delete-all-url'),
        vapidUrl:     script.getAttribute('data-vapid-url'),
        subscribeUrl: script.getAttribute('data-subscribe-url'),
        csrf:         script.getAttribute('data-csrf')
    };

    var POLL_INTERVAL = 30000; // 30 seconds
    var pollTimer = null;
    // Tracker per il modulo audio: salviamo l'ID dell'ultima notifica vista
    // dal client. Suoniamo solo quando arriva una notifica con id maggiore.
    // Inizializzato al primo poll (baseline) per non suonare il "vecchio".
    var lastSeenId = null;

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
            .then(function (data) {
                updateBadge(data.count);

                // Modulo audio: scegliamo il suono solo per notifiche NUOVE
                // rispetto all'ultimo poll. La prima fetch dopo init definisce
                // la baseline (lastSeenId): cosi' al primo accesso non
                // suoniamo per notifiche pregresse.
                var latest = data.latest;
                if (latest && latest.id) {
                    if (lastSeenId === null) {
                        lastSeenId = latest.id; // baseline silenziosa
                    } else if (latest.id > lastSeenId) {
                        lastSeenId = latest.id;
                        if (window.EvuleryNotificationSounds) {
                            window.EvuleryNotificationSounds.playForNotification(latest);
                        }
                    }
                }
            })
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
            var deleteBtn = '<button class="notif-item-delete" data-delete-id="' + n.id + '" title="Elimina"><i class="bi bi-x-lg"></i></button>';
            html += '<div class="notif-item' + unread + '" data-id="' + n.id + '" data-url="' + escHtml(url) + '">'
                + '<div class="notif-item-icon ' + ico.cls + '"><i class="bi ' + ico.icon + '"></i></div>'
                + '<div class="notif-item-content">'
                + '<div class="notif-item-title">' + escHtml(n.title) + '</div>'
                + '<div class="notif-item-body">' + escHtml(n.body || '') + '</div>'
                + '<div class="notif-item-time">' + timeAgo(n.created_at) + '</div>'
                + '</div>'
                + '<div class="notif-item-actions">' + markBtn + deleteBtn + '</div>'
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

        // Click on delete button (single)
        notifList.querySelectorAll('.notif-item-delete').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var id = btn.getAttribute('data-delete-id');
                deleteNotification(id, function () { fetchRecent(); pollUnread(); });
            });
        });

        // Click on item → navigate
        notifList.querySelectorAll('.notif-item').forEach(function (el) {
            el.addEventListener('click', function (e) {
                if (e.target.closest('.notif-item-actions')) return;
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

    function deleteNotification(id, cb) {
        fetch(cfg.markReadUrl + '/' + id + '/delete', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: '_csrf=' + encodeURIComponent(cfg.csrf)
        }).then(function () {
            if (cb) cb();
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

    // Desktop bell — apre il dropdown notifiche, niente push subscribe qui:
    // l'iscrizione push e' un opt-in esplicito tramite banner home o pagina
    // /dashboard/settings/notifications.
    if (bellBtn) {
        bellBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            toggleDropdown();
        });
    }

    // Mobile bell — naviga a /dashboard/notifications. NON triggerare
    // subscribeToPush qui: l'unload immediato della pagina interrompe la
    // promise async di pushManager.subscribe() prima che completi, lasciando
    // il dispositivo senza subscription. Era il bug killer dei mobile.
    if (bellBtnMobile) {
        bellBtnMobile.addEventListener('click', function () {
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

    // Promise-based: l'API esposta a window.EvuleryPush.subscribe() permette
    // ai banner/CTA di sapere l'esito (permesso accordato / negato / errore)
    // e mostrare il feedback corretto all'utente.
    function subscribeToPush() {
        if (!swRegistration) {
            return Promise.reject(new Error('service-worker-not-registered'));
        }

        return swRegistration.pushManager.getSubscription().then(function (sub) {
            if (sub) {
                // Gia' subscribed: re-invia al server per sicurezza (potrebbe
                // essere stato eliminato dal DB lato server).
                sendSubscriptionToServer(sub);
                return { status: 'already-subscribed' };
            }

            if (Notification.permission === 'denied') {
                return { status: 'denied' };
            }

            return fetch(cfg.vapidUrl, { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.key) throw new Error('vapid-key-missing');
                    var vapidKey = urlBase64ToUint8Array(data.key);
                    return swRegistration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: vapidKey
                    });
                })
                .then(function (subscription) {
                    sendSubscriptionToServer(subscription);
                    return { status: 'subscribed' };
                })
                .catch(function (err) {
                    // Tipici: NotAllowedError (utente nega), AbortError, errori rete
                    var status = Notification.permission === 'denied' ? 'denied' : 'error';
                    return { status: status, error: err };
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

    // ========== Helper localStorage dismiss (usato da piu' banner) ==========
    // Pattern unificato: usato sia dal banner push (7gg) che dal banner iOS
    // (14gg). Try/catch perche' localStorage e' bloccato in Safari private mode.
    function isBannerDismissedRecently(key, maxAgeDays) {
        try {
            var ts = parseInt(localStorage.getItem(key) || '0', 10);
            if (!ts) return false;
            return (Date.now() - ts) / 86400000 < maxAgeDays;
        } catch (e) { return false; }
    }
    function rememberBannerDismissed(key) {
        try { localStorage.setItem(key, String(Date.now())); } catch (e) {}
    }

    // ========== Banner attivazione push (opt-in esplicito) ==========
    // Mostrato sulla home se: SW disponibile + tenant_can(push) +
    // permission != 'denied' + non gia' subscribed + non dismissed di recente.
    var BANNER_DISMISS_KEY = 'evulery_push_banner_dismissed_at';
    var BANNER_DISMISS_DAYS = 7;

    function maybeShowPushBanner() {
        var banner = document.getElementById('push-prompt-banner');
        if (!banner) return; // pagina non lo include

        // Non supporto Web Push → niente banner (es. Safari < 16.4 su iOS senza PWA)
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
        // Permesso gia' negato → vai nelle settings, non insistere qui
        if (Notification.permission === 'denied') return;
        // Dismissed di recente → rispettiamo la scelta dell'utente
        if (isBannerDismissedRecently(BANNER_DISMISS_KEY, BANNER_DISMISS_DAYS)) return;
        // Su iOS non installato vediamo gia' il banner "Aggiungi a schermata
        // Home" (maybeShowIosPwaBanner): qui evitiamo il doppio prompt e
        // un'attivazione che fallirebbe comunque (push iOS richiede PWA).
        if (isIOSDevice() && !isStandalonePWA()) return;

        // Aspettiamo la registration del SW per controllare lo stato sub
        var attempt = function () {
            if (!swRegistration) { setTimeout(attempt, 300); return; }
            swRegistration.pushManager.getSubscription().then(function (sub) {
                if (sub) return; // gia' iscritto, banner inutile
                // Permesso GIA' concesso ma manca la subscription sul dispositivo
                // (persa/scaduta o SW ri-registrato): NON mostrare il banner
                // "Attiva" — sarebbe assurdo chiedere di attivare cio' che per
                // l'utente e' gia' attivo. La ricreiamo in silenzio (il permesso
                // c'e', nessun prompt) e la risincronizziamo col server.
                if (Notification.permission === 'granted') {
                    subscribeToPush();
                    return;
                }
                banner.classList.add('is-visible');
            }).catch(function () { /* fail silent */ });
        };
        attempt();

        // Bind CTA: il click avvia subscribe SENZA navigare via dalla pagina.
        var activateBtn = banner.querySelector('[data-push-activate]');
        var dismissBtn  = banner.querySelector('[data-push-dismiss]');
        if (activateBtn) {
            activateBtn.addEventListener('click', function () {
                activateBtn.disabled = true;
                activateBtn.textContent = 'Attivazione…';
                subscribeToPush().then(function (res) {
                    if (res.status === 'subscribed' || res.status === 'already-subscribed') {
                        banner.classList.remove('is-visible');
                    } else if (res.status === 'denied') {
                        activateBtn.disabled = false;
                        activateBtn.textContent = 'Riprova';
                        var txt = banner.querySelector('.push-prompt-banner-text');
                        if (txt) txt.innerHTML = '<strong>Permesso negato.</strong> <small>Per riattivare le notifiche, cambia il permesso dalle impostazioni del browser e ricarica la pagina.</small>';
                    } else {
                        activateBtn.disabled = false;
                        activateBtn.textContent = 'Riprova';
                    }
                });
            });
        }
        if (dismissBtn) {
            dismissBtn.addEventListener('click', function () {
                rememberBannerDismissed(BANNER_DISMISS_KEY);
                banner.classList.remove('is-visible');
            });
        }
    }

    // ========== Banner iOS: installa PWA per ricevere le push ==========
    // Su iOS le Web Push funzionano SOLO da PWA installata via Safari, da
    // iOS 16.4+. Chrome/Edge/Firefox su iPhone sono wrapper WebKit e non
    // supportano push. Quindi se il ristoratore apre la dashboard su iPhone
    // senza "Aggiungi a schermata Home", non ricevera' MAI notifiche — qualunque
    // pulsante "Attiva" cliccato resta inutile.
    var IOS_BANNER_DISMISS_KEY = 'evulery_ios_pwa_banner_dismissed_at';
    var IOS_BANNER_DISMISS_DAYS = 14;

    function isIOSDevice() {
        var ua = navigator.userAgent || '';
        // iPad recenti si mascherano da MacOS Safari: distinguiamo via touch.
        var isIPad = /iPad/.test(ua) ||
                     (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        return /iPhone|iPod/.test(ua) || isIPad;
    }

    function isStandalonePWA() {
        return window.navigator.standalone === true ||
               window.matchMedia('(display-mode: standalone)').matches;
    }

    function maybeShowIosPwaBanner() {
        var banner = document.getElementById('ios-pwa-banner');
        if (!banner) return;
        if (!isIOSDevice()) return;
        if (isStandalonePWA()) return;             // gia' installata
        if (isBannerDismissedRecently(IOS_BANNER_DISMISS_KEY, IOS_BANNER_DISMISS_DAYS)) return;
        banner.classList.add('is-visible');

        var dismissBtn = banner.querySelector('[data-ios-pwa-dismiss]');
        if (dismissBtn) {
            dismissBtn.addEventListener('click', function () {
                rememberBannerDismissed(IOS_BANNER_DISMISS_KEY);
                banner.classList.remove('is-visible');
            });
        }
    }

    // ========== API globale per CTA esterne (pagina settings, ecc) ==========
    window.EvuleryPush = {
        subscribe: function () { return subscribeToPush(); },
        getStatus: function () {
            // Promise resolved con { supported, permission, subscribed }
            //
            // IMPORTANTE: usiamo navigator.serviceWorker.ready invece della
            // variabile locale swRegistration per evitare la race condition al
            // primo render della pagina settings, quando register() e' ancora
            // in volo e swRegistration e' null. .ready e' una Promise standard
            // che si risolve quando il SW e' installato e attivo, e cattura
            // anche eventuali SW registrati in chiamate precedenti.
            return new Promise(function (resolve) {
                var supported = ('serviceWorker' in navigator) && ('PushManager' in window);
                var permission = (typeof Notification !== 'undefined') ? Notification.permission : 'default';
                if (!supported) {
                    resolve({ supported: false, permission: permission, subscribed: false, ready: false });
                    return;
                }
                // Timeout di sicurezza: navigator.serviceWorker.ready NON si risolve
                // mai se il SW non si attiva (es. contesto non sicuro: http su IP/
                // dominio invece di https o localhost). Senza questo, la UI resta
                // sullo spinner "Verifica in corso…" all'infinito. Dopo 6s diamo
                // un esito "non pronto" cosi' la pagina mostra uno stato finale.
                var settled = false;
                var done = function (res) { if (!settled) { settled = true; resolve(res); } };
                setTimeout(function () {
                    done({ supported: true, permission: permission, subscribed: false, ready: false });
                }, 6000);
                navigator.serviceWorker.ready.then(function (reg) {
                    return reg.pushManager.getSubscription();
                }).then(function (sub) {
                    done({ supported: true, permission: permission, subscribed: !!sub, ready: true });
                }).catch(function () {
                    done({ supported: supported, permission: permission, subscribed: false, ready: true });
                });
            });
        }
    };

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

    // Register service worker (permission asked on user gesture, e.g. banner CTA)
    registerServiceWorker();
    maybeShowPushBanner();
    maybeShowIosPwaBanner();
})();
