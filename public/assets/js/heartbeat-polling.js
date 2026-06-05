/**
 * Heartbeat polling per auto-refresh dashboard (Fase C).
 *
 * Pattern: il client interroga periodicamente un endpoint leggero che ritorna
 * { hash, count, last_updated_at }. Se l'hash differisce dall'ultimo conosciuto,
 * l'utente vede un banner "X modifiche · Aggiorna" e decide quando ricaricare —
 * NIENTE auto-refresh silenzioso (rompe form aperti, scroll, popup, edit in
 * corso). Standard di mercato (Gmail, Linear, GitHub, Slack).
 *
 * Ottimizzazioni built-in:
 *  - ETag/If-None-Match: 304 con payload zero se nulla e' cambiato
 *  - Pausa automatica quando il tab e' in background (document.hidden)
 *  - Backoff su errori di rete (raddoppia intervallo, max 5 min)
 *  - Stop() pulito per change-page / unload
 *
 * Uso:
 *   const hb = HeartbeatPolling.start({
 *       url: '/dashboard/heartbeat/reservations?date=2026-06-05',
 *       intervalMs: 60000,
 *       initialHash: 'abc123',  // hash render-time, dal server
 *       initialCount: 12,
 *       onChange: (data, prev) => { showBanner(data.count - prev.count); },
 *       onError: (err) => { console.warn('heartbeat error', err); }
 *   });
 *   // ...later: hb.stop();
 */
(function (global) {
    'use strict';

    const DEFAULT_INTERVAL = 60000;       // 60s
    const MAX_BACKOFF      = 300000;      // 5 min

    function start(config) {
        const url          = config.url;
        const interval     = config.intervalMs || DEFAULT_INTERVAL;
        const onChange     = typeof config.onChange === 'function' ? config.onChange : function () {};
        const onError      = typeof config.onError === 'function' ? config.onError : function () {};

        let currentHash    = config.initialHash || null;
        let currentCount   = typeof config.initialCount === 'number' ? config.initialCount : 0;
        let currentBackoff = interval;
        let timerId        = null;
        let stopped        = false;
        let inFlight       = false;

        function schedule(delay) {
            if (stopped) return;
            clearTimeout(timerId);
            timerId = setTimeout(tick, delay);
        }

        function tick() {
            if (stopped) return;
            if (document.hidden) {
                // Tab in background: rimanda al prossimo ciclo, non sprechiamo
                // ne' un round-trip ne' DB query. Quando il tab torna in primo
                // piano, l'event listener visibilitychange forzera' una tick().
                schedule(interval);
                return;
            }
            if (inFlight) {
                schedule(interval);
                return;
            }

            inFlight = true;

            const headers = { 'Accept': 'application/json' };
            if (currentHash) {
                headers['If-None-Match'] = '"' + currentHash + '"';
            }

            fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                headers: headers,
                cache: 'no-store'
            })
            .then(function (resp) {
                inFlight = false;
                currentBackoff = interval; // reset backoff su successo

                if (resp.status === 304) {
                    schedule(interval);
                    return;
                }
                if (!resp.ok) {
                    throw new Error('HTTP ' + resp.status);
                }
                return resp.json().then(function (data) {
                    if (!data || typeof data !== 'object' || !data.hash) {
                        schedule(interval);
                        return;
                    }
                    if (data.hash !== currentHash) {
                        const prev = { hash: currentHash, count: currentCount };
                        currentHash  = data.hash;
                        currentCount = typeof data.count === 'number' ? data.count : currentCount;
                        // Primo confronto: se non avevamo hash iniziale dal server
                        // adesso lo salviamo come baseline senza notificare.
                        if (prev.hash !== null) {
                            try { onChange(data, prev); } catch (e) { /* swallow */ }
                        }
                    }
                    schedule(interval);
                });
            })
            .catch(function (err) {
                inFlight = false;
                // Backoff esponenziale: raddoppia ad ogni errore consecutivo,
                // capped a 5 min, ritorna a normale al primo successo.
                currentBackoff = Math.min(currentBackoff * 2, MAX_BACKOFF);
                try { onError(err); } catch (e) { /* swallow */ }
                schedule(currentBackoff);
            });
        }

        function onVisibility() {
            if (!document.hidden && !stopped) {
                // Tab torna visibile: tick immediato per allineare lo stato.
                schedule(0);
            }
        }

        document.addEventListener('visibilitychange', onVisibility);
        schedule(interval);

        return {
            stop: function () {
                stopped = true;
                clearTimeout(timerId);
                document.removeEventListener('visibilitychange', onVisibility);
            },
            // Espone lo stato per debug / test
            getState: function () {
                return { hash: currentHash, count: currentCount, stopped: stopped };
            }
        };
    }

    /**
     * Auto-bind: ogni <div data-heartbeat-url="..." data-heartbeat-hash="..." data-heartbeat-count="..."
     *               data-heartbeat-banner="#id-banner"
     *               data-heartbeat-label="prenotazioni di oggi">
     * presente nel DOM avvia il polling alla DOMContentLoaded.
     *
     * Il banner referenziato deve avere la struttura .dh-refresh-banner con:
     *   - .dh-refresh-banner-text  (slot del messaggio)
     *   - button[data-heartbeat-reload]  (click = location.reload)
     *   - button[data-heartbeat-dismiss] (click = nasconde fino al prossimo cambio)
     *
     * Attributo data-heartbeat-label (opzionale):
     *   testo del dataset monitorato, viene appeso alla frase del banner.
     *   Default: "questa pagina". Esempi: "prenotazioni di oggi", "stato sala".
     */
    function autoBind() {
        const nodes = document.querySelectorAll('[data-heartbeat-url]');
        nodes.forEach(function (node) {
            const url    = node.getAttribute('data-heartbeat-url');
            const hash   = node.getAttribute('data-heartbeat-hash') || null;
            const count  = parseInt(node.getAttribute('data-heartbeat-count') || '0', 10);
            const interval = parseInt(node.getAttribute('data-heartbeat-interval') || '60000', 10);
            const bannerSel = node.getAttribute('data-heartbeat-banner');
            const label    = node.getAttribute('data-heartbeat-label') || 'questa pagina';
            if (!url || !bannerSel) return;

            const banner   = document.querySelector(bannerSel);
            if (!banner) return;
            const textEl   = banner.querySelector('.dh-refresh-banner-text');
            const reloadBtn = banner.querySelector('[data-heartbeat-reload]');
            const dismissBtn = banner.querySelector('[data-heartbeat-dismiss]');

            let dismissedHash = null; // se l'utente fa dismiss, restiamo nascosti
                                      // finche' l'hash non cambia ulteriormente

            function showBanner(data, prev) {
                if (dismissedHash === data.hash) return;
                const diff = Math.abs((data.count || 0) - (prev.count || 0));
                let msg;
                if (diff === 0) {
                    msg = '<strong>Modifiche disponibili</strong> su ' + label + '.';
                } else if (diff === 1) {
                    msg = '<strong>1 modifica</strong> su ' + label + '.';
                } else {
                    msg = '<strong>' + diff + ' modifiche</strong> su ' + label + '.';
                }
                if (textEl) textEl.innerHTML = msg;
                banner.classList.add('is-visible');
            }

            if (reloadBtn) {
                reloadBtn.addEventListener('click', function () {
                    location.reload();
                });
            }
            if (dismissBtn) {
                dismissBtn.addEventListener('click', function () {
                    banner.classList.remove('is-visible');
                    const st = poller.getState();
                    dismissedHash = st.hash;
                });
            }

            const poller = start({
                url: url,
                intervalMs: interval,
                initialHash: hash,
                initialCount: count,
                onChange: showBanner,
                onError: function () { /* silenzioso */ }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoBind);
    } else {
        autoBind();
    }

    global.HeartbeatPolling = { start: start };
})(window);
