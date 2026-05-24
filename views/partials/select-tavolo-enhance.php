<?php
/**
 * Enhancement custom dropdown per <select name="table_option">.
 *
 * Sostituisce visivamente il <select> nativo con un componente custom:
 *   - icona per tipo (tavolo singolo / combinazione / azione)
 *   - sezioni raggruppate (Nessuno, Tavoli, Combinazioni, Azioni)
 *   - tag rosso "occupato" sulle opzioni non disponibili
 *   - selezione corrente evidenziata
 *
 * Il <select> nativo resta nel DOM (display:none) per il form submit; ad ogni
 * click viene sincronizzato `select.value` e dispatched l_evento `change`
 * (bubbles:true), così tutti i listener esistenti (multi-select modal,
 * conferma submit) continuano a funzionare senza modifiche.
 *
 * Auto-init: enhance tutti i `select[name="table_option"]` al DOMContentLoaded.
 * Idempotente: skip dei select già wrappati (data-tse-init).
 *
 * NB: il file va incluso UNA volta per pagina, dopo che i select sono nel DOM.
 */
?>
<style>
    .tse-wrap { position: relative; display: block; width: 100%; }
    .tse-native { position: absolute !important; opacity: 0 !important; pointer-events: none !important; height: 0 !important; width: 0 !important; padding: 0 !important; margin: 0 !important; border: 0 !important; }
    .tse-trigger { display: flex; align-items: center; gap: 10px; width: 100%; padding: 8px 12px; background: #fff; border: 1px solid #ced4da; border-radius: 6px; cursor: pointer; font-size: .875rem; color: #1a1d23; text-align: left; transition: border-color .12s, box-shadow .12s; }
    .tse-trigger:hover { border-color: #adb5bd; }
    .tse-trigger:focus-visible, .tse-wrap.open .tse-trigger { outline: none; border-color: var(--brand, #00844A); box-shadow: 0 0 0 .15rem rgba(0,132,74,.18); }
    .tse-trigger-ico { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 7px; background: var(--brand-light, #e6f4ed); color: var(--brand, #00844A); font-size: .9rem; flex-shrink: 0; }
    .tse-trigger-ico.tse-ico-combo { background: #e3f0fa; color: #1f6fa3; }
    .tse-trigger-ico.tse-ico-none  { background: #f0f1f3; color: #6c757d; }
    .tse-trigger-label { flex: 1; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.3; }
    .tse-trigger-chevron { color: #6c757d; font-size: .85rem; transition: transform .15s; flex-shrink: 0; }
    .tse-wrap.open .tse-trigger-chevron { transform: rotate(180deg); }

    /* position: fixed (impostata via JS al momento dell_apertura) per evitare
       di essere clippata da contenitori con overflow:hidden (es. .tm-pop dei
       popup mappa sala). top/left/width/max-height vengono calcolati in JS. */
    .tse-menu { position: fixed; background: #fff; border: 1px solid #e1e5ea; border-radius: 10px; box-shadow: 0 10px 28px rgba(0,0,0,.18); z-index: 2000; overflow-y: auto; padding: 4px; display: none; }
    .tse-wrap.open .tse-menu { display: block; }
    .tse-section-head { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #8893a1; padding: 8px 10px 4px; }
    .tse-section-head:first-child { padding-top: 4px; }
    .tse-item { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 7px; cursor: pointer; font-size: .85rem; color: #1a1d23; transition: background .1s; position: relative; }
    .tse-item:hover { background: #f4f6f8; }
    .tse-item.selected { background: var(--brand-light, #e6f4ed); box-shadow: inset 3px 0 0 var(--brand, #00844A); padding-left: 12px; }
    .tse-item.busy { color: #6c757d; }
    .tse-item.busy:hover { background: #fbf3f3; }
    .tse-item-ico { display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 6px; flex-shrink: 0; font-size: .82rem; }
    .tse-ico-single { background: var(--brand-light, #e6f4ed); color: var(--brand, #00844A); }
    .tse-ico-combo  { background: #e3f0fa; color: #1f6fa3; }
    .tse-ico-none   { background: #f0f1f3; color: #6c757d; }
    .tse-ico-action { background: var(--brand, #00844A); color: #fff; }
    .tse-item.busy .tse-item-ico { background: #f0f1f3; color: #adb5bd; }
    .tse-item-body { flex: 1; min-width: 0; line-height: 1.3; }
    .tse-item-main { font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tse-item-sub  { font-size: .7rem; color: #6c757d; margin-top: 1px; }
    .tse-item.selected .tse-item-main { color: var(--brand, #00844A); }
    .tse-tag-busy { font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: #b3261e; background: #fdecea; padding: 2px 7px; border-radius: 4px; flex-shrink: 0; }
    .tse-check { color: var(--brand, #00844A); font-size: 1rem; flex-shrink: 0; }
    .tse-sep { height: 1px; background: #eef0f2; margin: 4px 6px; }
    .tse-item.action { color: var(--brand, #00844A); font-weight: 600; }
    .tse-item.action:hover { background: var(--brand-light, #e6f4ed); }
</style>

<script nonce="<?= csp_nonce() ?>">
(function () {
    'use strict';

    var ICONS = {
        none:   '<i class="bi bi-dash-circle"></i>',
        single: '<i class="bi bi-square"></i>',
        combo:  '<i class="bi bi-grid-3x3-gap-fill"></i>',
        action: '<i class="bi bi-plus-lg"></i>',
        check:  '<i class="bi bi-check-lg"></i>',
        chevron:'<i class="bi bi-chevron-down"></i>'
    };

    // Parsing di una <option> nativa → tipo + status + label puliti
    function parseOption(opt) {
        var raw = (opt.textContent || '').trim();
        var val = opt.value || '';

        // Separatore (option disabled + value vuoto)
        if (opt.disabled && val === '') {
            return { type: 'separator' };
        }

        // Azioni speciali (Combina tavoli…)
        if (val === '__multi__') {
            return { type: 'action', label: raw.replace(/^[↔↕⇕⬍]\s*/, '').trim(), icon: 'action' };
        }

        // Nessun tavolo (value vuoto, non disabled)
        if (val === '') {
            return { type: 'none', label: raw.replace(/[—–-]/g, '').trim() || 'Nessun tavolo', icon: 'none' };
        }

        // Rilevamento stato "occupato"
        var busy = false;
        var busyDetail = '';
        // Pattern: "... · occupato" oppure "... · Tav. X occupato/occupati"
        var mFull = raw.match(/\s+·\s+(occupato|occupati)$/);
        var mPart = raw.match(/\s+·\s+(.+?\s+occupat[io])$/);
        var label = raw;
        if (mFull) {
            busy = true;
            busyDetail = '';
            label = raw.substring(0, raw.length - mFull[0].length);
        } else if (mPart) {
            busy = true;
            busyDetail = mPart[1].trim();
            label = raw.substring(0, raw.length - mPart[0].length);
        }

        // Rilevamento "(attuale)"
        var current = false;
        var mCur = label.match(/\s+\(attuale\)$/);
        if (mCur) {
            current = true;
            label = label.substring(0, label.length - mCur[0].length).trim();
        }

        // Tipo: combo se value contiene virgola, oppure se label contiene "+" / "—" (combinazione)
        var isCombo = val.indexOf(',') !== -1;
        return {
            type:       isCombo ? 'combo' : 'single',
            label:      label,
            icon:       isCombo ? 'combo' : 'single',
            busy:       busy,
            busyDetail: busyDetail,
            current:    current
        };
    }

    function enhance(select) {
        if (select.dataset.tseInit === '1') return;
        select.dataset.tseInit = '1';

        // Wrapper
        var wrap = document.createElement('div');
        wrap.className = 'tse-wrap';
        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(select);
        select.classList.add('tse-native');
        // Salviamo eventuali classi originali per leggibilità DOM (no-op funzionale)

        // Trigger
        var trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'tse-trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        wrap.appendChild(trigger);

        // Menu
        var menu = document.createElement('div');
        menu.className = 'tse-menu';
        menu.setAttribute('role', 'listbox');
        wrap.appendChild(menu);

        function refreshTrigger() {
            var curOpt = select.options[select.selectedIndex];
            var p = curOpt ? parseOption(curOpt) : { type: 'none', label: 'Nessun tavolo', icon: 'none' };
            var icoCls = 'tse-trigger-ico';
            if (p.type === 'combo')  icoCls += ' tse-ico-combo';
            else if (p.type === 'none' || p.type === 'separator') icoCls += ' tse-ico-none';
            trigger.innerHTML =
                '<span class="' + icoCls + '">' + (ICONS[p.icon || (p.type === 'combo' ? 'combo' : (p.type === 'none' ? 'none' : 'single'))]) + '</span>' +
                '<span class="tse-trigger-label">' + escapeHtml(p.label || 'Nessun tavolo') + (p.current ? ' <span style="font-weight:400;color:#6c757d;font-size:.78rem;">(attuale)</span>' : '') + '</span>' +
                '<span class="tse-trigger-chevron">' + ICONS.chevron + '</span>';
        }

        function buildMenu() {
            menu.innerHTML = '';
            var sections = { none: [], single: [], combo: [], action: [] };
            for (var i = 0; i < select.options.length; i++) {
                var opt = select.options[i];
                var p = parseOption(opt);
                if (p.type === 'separator') continue;
                sections[p.type].push({ index: i, value: opt.value, parsed: p });
            }

            // Sezione "Nessuno"
            if (sections.none.length > 0) {
                renderItems(sections.none, null);
            }
            // Sezione "Tavoli"
            if (sections.single.length > 0) {
                if (sections.none.length > 0) menu.appendChild(sep());
                renderItems(sections.single, 'Tavoli');
            }
            // Sezione "Combinazioni"
            if (sections.combo.length > 0) {
                if (sections.none.length + sections.single.length > 0) menu.appendChild(sep());
                renderItems(sections.combo, 'Combinazioni');
            }
            // Sezione "Azioni"
            if (sections.action.length > 0) {
                if (sections.none.length + sections.single.length + sections.combo.length > 0) menu.appendChild(sep());
                renderItems(sections.action, 'Azioni');
            }
        }

        function renderItems(items, headLabel) {
            if (headLabel) {
                var h = document.createElement('div');
                h.className = 'tse-section-head';
                h.textContent = headLabel;
                menu.appendChild(h);
            }
            items.forEach(function (it) {
                var row = document.createElement('div');
                var classes = ['tse-item'];
                if (it.parsed.type === 'action') classes.push('action');
                if (it.parsed.busy) classes.push('busy');
                if (it.index === select.selectedIndex) classes.push('selected');
                row.className = classes.join(' ');
                row.setAttribute('role', 'option');
                row.setAttribute('data-idx', String(it.index));

                var icoCls = 'tse-item-ico tse-ico-' + (it.parsed.icon || (it.parsed.type === 'combo' ? 'combo' : (it.parsed.type === 'action' ? 'action' : (it.parsed.type === 'none' ? 'none' : 'single'))));
                var subParts = [];
                if (it.parsed.current) subParts.push('attuale');
                if (it.parsed.busy && it.parsed.busyDetail) subParts.push(it.parsed.busyDetail);
                var subHtml = subParts.length ? '<div class="tse-item-sub">' + escapeHtml(subParts.join(' · ')) + '</div>' : '';

                row.innerHTML =
                    '<span class="' + icoCls + '">' + ICONS[it.parsed.icon] + '</span>' +
                    '<div class="tse-item-body"><div class="tse-item-main">' + escapeHtml(it.parsed.label) + '</div>' + subHtml + '</div>' +
                    (it.parsed.busy ? '<span class="tse-tag-busy">occupato</span>' : '') +
                    (it.index === select.selectedIndex && !it.parsed.busy ? '<span class="tse-check">' + ICONS.check + '</span>' : '');

                row.addEventListener('click', function () {
                    // L_opzione resta cliccabile anche se busy: il backend potrebbe
                    // permettere l_assegnazione (override manuale). Lasciamo decidere.
                    select.selectedIndex = it.index;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    refreshTrigger();
                    close();
                });

                menu.appendChild(row);
            });
        }

        function sep() {
            var s = document.createElement('div');
            s.className = 'tse-sep';
            return s;
        }

        var winHandlers = null;

        function positionMenu() {
            var r = trigger.getBoundingClientRect();
            var gap = 4;
            var maxMenuH = 380;
            var spaceBelow = window.innerHeight - r.bottom - gap - 8;
            var spaceAbove = r.top - gap - 8;
            // Dinamica intelligente: apre nella direzione con piu spazio.
            // Cosi nei popup con trigger in basso (mappa sala) si apre sopra,
            // mentre nei trigger in alto (es. scheda prenotazione header) sotto.
            var openUp = spaceAbove > spaceBelow;

            menu.style.left = Math.max(8, r.left) + 'px';
            // Larghezza: almeno quella del trigger, max 360px (per non diventare troppo larga
            // se il trigger viene messo in popup molto stretti)
            var w = Math.min(360, Math.max(r.width, 260));
            menu.style.width = w + 'px';
            // Se la width supererebbe il viewport, riduco
            if (parseFloat(menu.style.left) + w > window.innerWidth - 8) {
                menu.style.left = Math.max(8, window.innerWidth - w - 8) + 'px';
            }

            if (openUp) {
                menu.style.top = Math.max(8, r.top - Math.min(maxMenuH, spaceAbove) - gap) + 'px';
                menu.style.maxHeight = Math.min(maxMenuH, spaceAbove) + 'px';
            } else {
                menu.style.top = (r.bottom + gap) + 'px';
                menu.style.maxHeight = Math.min(maxMenuH, spaceBelow) + 'px';
            }
        }

        function open() {
            buildMenu();
            wrap.classList.add('open');
            trigger.setAttribute('aria-expanded', 'true');
            positionMenu();
            // Riposiziona su scroll/resize. Su scroll del popup (capture:true serve
            // a intercettare scroll su ancestor con overflow).
            winHandlers = {
                scroll: function () { positionMenu(); },
                resize: function () { positionMenu(); }
            };
            window.addEventListener('scroll', winHandlers.scroll, true);
            window.addEventListener('resize', winHandlers.resize);
        }

        function close() {
            wrap.classList.remove('open');
            trigger.setAttribute('aria-expanded', 'false');
            if (winHandlers) {
                window.removeEventListener('scroll', winHandlers.scroll, true);
                window.removeEventListener('resize', winHandlers.resize);
                winHandlers = null;
            }
        }

        function toggle() {
            if (wrap.classList.contains('open')) close();
            else open();
        }

        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            toggle();
        });

        // Click esterno chiude
        document.addEventListener('click', function (e) {
            if (!wrap.contains(e.target)) close();
        });

        // Esc chiude
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && wrap.classList.contains('open')) close();
        });

        // Riallinea trigger se il select cambia dall_esterno (modale che imposta value)
        select.addEventListener('change', function () {
            refreshTrigger();
        });

        refreshTrigger();
    }

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function init() {
        document.querySelectorAll('select[name="table_option"]').forEach(enhance);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
