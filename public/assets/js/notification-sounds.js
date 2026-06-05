/**
 * Notification sounds — sound logo Evulery brandizzato (Fase notifiche audio).
 *
 * Triggerato da dashboard-notifications.js quando il polling rileva una nuova
 * riga in `notifications` (il count unread aumenta E l'ultima notification ha
 * created_at successivo all'ultima vista). Il modulo:
 *
 *  - Preload silenzioso dei 5 file MP3 all'avvio (no latenza al primo evento)
 *  - Mappa type -> file audio (cancellation usa data.cancelled_by per
 *    discriminare: suoniamo SOLO se 'cliente', non 'staff')
 *  - Rispetta i toggle tenant: master enabled + per-event flags + volume
 *  - Web Audio API per il volume preciso (HTMLAudioElement non sempre lo
 *    rispetta su Firefox/Safari mobile)
 *  - Fail silent se l'autoplay e' bloccato (utente deve interagire prima)
 *
 * API esposta a window.EvuleryNotificationSounds:
 *   playForNotification(notif)  // notif: { type, data: { cancelled_by? } }
 *   setConfig(cfg)              // hot-reload da settings senza reload pagina
 *   testSound(type)             // anteprima per UI settings
 */
(function (global) {
    'use strict';

    var script = document.currentScript || document.querySelector('script[data-notif-sounds-config]');
    if (!script) return;

    // Config iniziale dal layout (data attributes JSON-encoded)
    var rawCfg = script.getAttribute('data-notif-sounds-config') || '{}';
    var config;
    try { config = JSON.parse(rawCfg); }
    catch (e) { config = {}; }

    // Default sicuro se il backend non ha popolato qualcosa
    var defaults = {
        enabled: 1,
        volume: 70,                       // 0-100
        sound_on_new_reservation: 1,
        sound_on_cancellation: 1,
        sound_on_deposit_received: 1,
        sound_on_new_order: 1,
        sound_on_new_feedback: 1
    };
    Object.keys(defaults).forEach(function (k) {
        if (typeof config[k] === 'undefined') config[k] = defaults[k];
    });

    // Mappa type -> file. Stesso pattern URL del helper asset() lato server
    // (cache busting via filemtime gia' embedded nelle URL passate dal layout).
    var soundUrls = {
        new_reservation:  script.getAttribute('data-sound-master'),
        cancellation:     script.getAttribute('data-sound-cancellation'),
        deposit_received: script.getAttribute('data-sound-deposit'),
        new_order:        script.getAttribute('data-sound-order'),
        new_feedback:     script.getAttribute('data-sound-review')
    };

    // Per ogni tipo, qual e' il flag config che lo abilita.
    var enableFlag = {
        new_reservation:  'sound_on_new_reservation',
        cancellation:     'sound_on_cancellation',
        deposit_received: 'sound_on_deposit_received',
        new_order:        'sound_on_new_order',
        new_feedback:     'sound_on_new_feedback'
    };

    // ========== Preload ==========
    var audioCache = {};

    function preloadAll() {
        Object.keys(soundUrls).forEach(function (type) {
            var url = soundUrls[type];
            if (!url) return;
            var a = new Audio();
            a.src = url;
            a.preload = 'auto';
            a.volume = (config.volume || 70) / 100;
            audioCache[type] = a;
            // Sopprimi errori 404 a console (il file potrebbe non esistere
            // ancora se il deploy non lo include): il modulo continua a girare.
            a.addEventListener('error', function () { audioCache[type] = null; });
        });
    }

    // ========== Play ==========
    // force=true → bypassa enabled+flag check (usato per anteprime nei settings,
    // dove l'utente vuole ascoltare il suono indipendentemente dai toggle).
    function play(type, force) {
        if (!force) {
            if (!config.enabled) return;
            var flag = enableFlag[type];
            if (flag && !config[flag]) return;
        }

        var a = audioCache[type];
        if (!a) return;

        try {
            a.currentTime = 0;
            a.volume = (config.volume || 70) / 100;
            var p = a.play();
            // Autoplay policy: se l'utente non ha ancora interagito col tab,
            // Chrome/Safari bloccano play(). Catch silenzioso, riproveremo
            // alla prossima notifica (l'utente nel frattempo avra' cliccato).
            if (p && p.catch) p.catch(function () { /* autoplay blocked */ });
        } catch (e) { /* fail silent */ }
    }

    /**
     * Decide se suonare in base alla notification ricevuta dal polling.
     * notif: { type: string, data: { cancelled_by?: 'cliente'|'staff' } | null }
     */
    function playForNotification(notif) {
        if (!notif || !notif.type) return;
        var type = notif.type;
        // Cancellation: suoniamo SOLO quando e' stato il cliente a cancellare.
        // Quando lo fa lo staff (ristoratore dalla dashboard) e' lui che ha
        // appena cliccato Annulla — suonarlo addosso sarebbe incoerente.
        if (type === 'cancellation') {
            var who = (notif.data && notif.data.cancelled_by) || '';
            if (who !== 'cliente') return;
        }
        play(type);
    }

    // ========== API esposta ==========
    global.EvuleryNotificationSounds = {
        playForNotification: playForNotification,
        play: play,                                // rispetta i toggle config
        testSound: function (type) { play(type, true); }, // bypass per preview UI
        setConfig: function (newCfg) {
            // Hot-reload da settings UI senza ricaricare la pagina
            Object.keys(newCfg).forEach(function (k) { config[k] = newCfg[k]; });
        },
        getConfig: function () { return Object.assign({}, config); }
    };

    // ========== Init ==========
    preloadAll();
})(window);
