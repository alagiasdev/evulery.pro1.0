<?php
/**
 * Modale "Combina tavoli" — multi-select ad-hoc dei tavoli da assegnare
 * a una prenotazione. Riusabile in:
 *   - views/dashboard/reservations/show.php
 *   - views/dashboard/settings/tables-map.php (popup operativa)
 *
 * Variabili attese dal chiamante:
 *   $mmTables  array  Lista tavoli attivi: [{id, name, capacity, min_capacity}, ...]
 *
 * Uso lato JS (in ogni form che vuole offrire la combinazione):
 *   1. Aggiungere nel <select name="table_option"> una option:
 *        <option value="__multi__">↔ Combina tavoli…</option>
 *   2. Su 'change' del select, se valore = "__multi__", chiamare:
 *        EvuleryCombineTables.open({
 *          form:          <HTMLFormElement>,
 *          partySize:     <int>,
 *          currentValue:  '<id1,id2,...>',
 *          busyIds:       [<int>, ...]   // opzionale
 *        });
 *   3. Su conferma: il modale popola l'input "table_option" con "id1,id2,..."
 *      e fa submit del form. Su cancel: ripristina il valore precedente.
 */
?>
<div id="emct-overlay" class="emct-overlay" hidden aria-hidden="true">
    <div class="emct-modal" role="dialog" aria-labelledby="emct-title">
        <div class="emct-head">
            <span id="emct-title" class="emct-title">
                <i class="bi bi-grid-3x3-gap"></i> Combina tavoli
            </span>
            <button type="button" class="emct-x" data-emct-close aria-label="Chiudi">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="emct-body">
            <div id="emct-sumbar" class="emct-sumbar emct-empty">
                <div class="emct-sumico"><i class="bi bi-dash-circle"></i></div>
                <div>
                    <div class="emct-sumnum"><span id="emct-sumcur">0</span> / <strong id="emct-sumtgt">0</strong></div>
                </div>
                <div id="emct-sumdesc" class="emct-sumdesc">Seleziona i tavoli da combinare.</div>
            </div>
            <div id="emct-list" class="emct-list"></div>
        </div>
        <div class="emct-foot">
            <button type="button" class="emct-btn emct-btn-ghost" data-emct-close>Annulla</button>
            <button type="button" class="emct-btn emct-btn-brand" id="emct-confirm" disabled>
                <i class="bi bi-check2"></i> Assegna combinazione
            </button>
        </div>
    </div>
</div>

<style>
    .emct-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.55); z-index: 1050; display: flex; align-items: center; justify-content: center; padding: 20px; }
    .emct-overlay[hidden] { display: none; }
    .emct-modal { background: #fff; border-radius: 12px; width: 520px; max-width: 100%; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 10px 36px rgba(0,0,0,.25); overflow: hidden; }
    .emct-head { display: flex; align-items: center; padding: 14px 20px; border-bottom: 1px solid #e9ecef; }
    .emct-title { font-size: .95rem; font-weight: 700; flex: 1; color: #1a1d23; display: flex; align-items: center; gap: 6px; }
    .emct-title i { color: var(--brand, #00844A); }
    .emct-x { width: 30px; height: 30px; border-radius: 7px; border: none; background: #f0f1f3; cursor: pointer; color: #6c757d; }
    .emct-body { padding: 18px 20px; overflow-y: auto; }
    .emct-foot { display: flex; justify-content: flex-end; gap: 8px; padding: 14px 20px; border-top: 1px solid #e9ecef; background: #fafbfc; }

    .emct-sumbar { display: flex; align-items: center; gap: 14px; padding: 12px 14px; border-radius: 10px; margin-bottom: 14px; transition: background .15s, border-color .15s; border: 1px solid #e9ecef; background: #fafbfc; }
    .emct-sumbar.emct-empty { background: #fafbfc; border-color: #e9ecef; }
    .emct-sumbar.emct-warn  { background: #fff8e0; border-color: #ffe082; }
    .emct-sumbar.emct-ok    { background: #eef7f1; border-color: #cfe8d6; }
    .emct-sumico { width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.05rem; flex-shrink: 0; background: #eef0f2; color: #6c757d; }
    .emct-sumbar.emct-warn .emct-sumico { background: #fff3cd; color: #b8860b; }
    .emct-sumbar.emct-ok   .emct-sumico { background: #d1e7dd; color: var(--brand, #00844A); }
    .emct-sumnum { font-size: 1.5rem; font-weight: 800; line-height: 1; color: #1a1d23; }
    .emct-sumnum strong { color: var(--brand, #00844A); }
    .emct-sumbar.emct-warn .emct-sumnum strong { color: #b8860b; }
    .emct-sumdesc { font-size: .78rem; color: #6c757d; flex: 1; line-height: 1.45; }
    .emct-sumdesc strong { color: #1a1d23; }

    .emct-list { display: flex; flex-direction: column; gap: 6px; }
    .emct-row { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border: 1.5px solid #e9ecef; border-radius: 10px; cursor: pointer; transition: border-color .12s, background .12s; user-select: none; }
    .emct-row:hover { border-color: #c4d4cc; }
    .emct-row.on { border-color: var(--brand, #00844A); background: var(--brand-light, #e6f4ed); }
    .emct-row.busy { opacity: .55; cursor: not-allowed; background: #fafbfc; }
    .emct-chk { width: 22px; height: 22px; border-radius: 6px; border: 1.5px solid #e9ecef; display: flex; align-items: center; justify-content: center; font-size: .85rem; color: transparent; flex-shrink: 0; background: #fff; }
    .emct-row.on .emct-chk { background: var(--brand, #00844A); border-color: var(--brand, #00844A); color: #fff; }
    .emct-name { font-weight: 700; font-size: .86rem; color: #1a1d23; flex-shrink: 0; min-width: 60px; }
    .emct-cap { font-size: .7rem; font-weight: 700; background: #eef0f2; color: #5b6470; padding: 2px 7px; border-radius: 5px; flex-shrink: 0; }
    .emct-status { flex: 1; font-size: .72rem; color: #6c757d; text-align: right; }
    .emct-status.free { color: var(--brand, #00844A); }
    .emct-status.busy { color: #b3261e; }

    .emct-btn { padding: 8px 14px; border-radius: 8px; font-size: .78rem; font-weight: 600; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; }
    .emct-btn-brand { background: var(--brand, #00844A); color: #fff; }
    .emct-btn-brand:disabled { background: #c4d4cc; cursor: not-allowed; }
    .emct-btn-ghost { background: #fff; color: #6c757d; border: 1px solid #e9ecef; }
</style>

<script nonce="<?= csp_nonce() ?>">
window.EvuleryCombineTables = (function () {
    var TABLES = <?= json_encode(array_map(function ($t) {
        return [
            'id'       => (int)$t['id'],
            'name'     => $t['name'],
            'capacity' => (int)$t['capacity'],
        ];
    }, $mmTables ?? []), JSON_UNESCAPED_UNICODE | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG) ?>;

    var overlay = document.getElementById('emct-overlay');
    var listEl  = document.getElementById('emct-list');
    var sumBar  = document.getElementById('emct-sumbar');
    var sumCur  = document.getElementById('emct-sumcur');
    var sumTgt  = document.getElementById('emct-sumtgt');
    var sumDesc = document.getElementById('emct-sumdesc');
    var btnOk   = document.getElementById('emct-confirm');
    var titleEl = document.getElementById('emct-title');

    var current = {
        form:         null,
        partySize:    0,
        previousVal:  '',
        selected:     {},
        busy:         {}
    };

    function render() {
        listEl.innerHTML = '';
        TABLES.forEach(function (t) {
            var isBusy = !!current.busy[t.id];
            var isOn   = !!current.selected[t.id];

            var row = document.createElement('div');
            row.className = 'emct-row' + (isOn ? ' on' : '') + (isBusy ? ' busy' : '');
            row.setAttribute('data-id', String(t.id));

            row.innerHTML =
                '<div class="emct-chk"><i class="bi bi-check-lg"></i></div>' +
                '<span class="emct-name">' + escapeHtml(t.name) + '</span>' +
                '<span class="emct-cap">' + t.capacity + 'p</span>' +
                '<span class="emct-status ' + (isBusy ? 'busy' : 'free') + '">' +
                    (isBusy ? 'occupato' : 'libero') +
                '</span>';

            if (!isBusy) {
                row.addEventListener('click', function () {
                    if (current.selected[t.id]) delete current.selected[t.id];
                    else current.selected[t.id] = true;
                    render();
                    refreshSum();
                });
            }

            listEl.appendChild(row);
        });
        refreshSum();
    }

    function refreshSum() {
        var ids = Object.keys(current.selected).map(Number);
        var sum = 0;
        ids.forEach(function (id) {
            var t = TABLES.find(function (x) { return x.id === id; });
            if (t) sum += t.capacity;
        });
        sumCur.textContent = String(sum);
        sumTgt.textContent = String(current.partySize);

        sumBar.classList.remove('emct-empty', 'emct-warn', 'emct-ok');
        if (ids.length === 0) {
            sumBar.classList.add('emct-empty');
            sumDesc.innerHTML = 'Seleziona i tavoli da combinare. Servono <strong>almeno ' + current.partySize + ' posti</strong> per il gruppo.';
            btnOk.disabled = true;
            btnOk.innerHTML = '<i class="bi bi-check2"></i> Assegna combinazione';
        } else if (sum < current.partySize) {
            sumBar.classList.add('emct-warn');
            sumDesc.innerHTML = '<strong>' + (current.partySize - sum) + ' posti in meno</strong> rispetto al gruppo. Aggiungi un altro tavolo o conferma comunque.';
            btnOk.disabled = false;
            btnOk.innerHTML = '<i class="bi bi-check2"></i> Assegna comunque';
        } else {
            sumBar.classList.add('emct-ok');
            sumDesc.innerHTML = '<strong>' + sum + ' posti</strong> per ' + current.partySize + ' persone — pronto a salvare.';
            btnOk.disabled = false;
            btnOk.innerHTML = '<i class="bi bi-check2"></i> Assegna combinazione';
        }
    }

    function open(opts) {
        if (!opts || !opts.form) return;
        current.form        = opts.form;
        current.partySize   = Math.max(1, parseInt(opts.partySize, 10) || 0);
        current.previousVal = String(opts.previousValue || '');
        current.selected    = {};
        current.busy        = {};
        (opts.busyIds || []).forEach(function (id) { current.busy[Number(id)] = true; });

        var label = opts.label ? ' per ' + opts.label : '';
        titleEl.innerHTML = '<i class="bi bi-grid-3x3-gap"></i> Combina tavoli' + escapeHtml(label) +
                            ' (' + current.partySize + ' ' + (current.partySize === 1 ? 'persona' : 'persone') + ')';

        render();
        overlay.hidden = false;
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function close(restorePrevious) {
        overlay.hidden = true;
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        if (restorePrevious && current.form) {
            var sel = current.form.querySelector('select[name="table_option"]');
            if (sel) {
                sel.value = current.previousVal;
                // Dispatch 'change' (bubbles) per ri-sincronizzare il dropdown custom
                // (select-tavolo-enhance.php). Non innesca riapertura: il listener
                // multi-select controlla `sel.value === '__multi__'` e qui il valore
                // ripristinato è quello PRECEDENTE (mai __multi__).
                sel.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
    }

    function confirm() {
        if (!current.form) return;
        var ids = Object.keys(current.selected).map(Number).filter(Boolean);
        if (ids.length === 0) return;
        ids.sort(function (a, b) { return a - b; });
        // Setta valore nel select (o crea un option dinamico se "valore non in lista")
        var sel = current.form.querySelector('select[name="table_option"]');
        if (sel) {
            var val = ids.join(',');
            var exists = Array.prototype.some.call(sel.options, function (o) { return o.value === val; });
            if (!exists) {
                var newOpt = document.createElement('option');
                newOpt.value = val;
                var names = ids.map(function (id) {
                    var t = TABLES.find(function (x) { return x.id === id; });
                    return t ? t.name : '?';
                });
                newOpt.textContent = names.join(' + ') + ' — combinazione';
                sel.appendChild(newOpt);
            }
            sel.value = val;
        }
        close(false);
        current.form.submit();
    }

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    // Close handlers
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) close(true);
    });
    overlay.querySelectorAll('[data-emct-close]').forEach(function (b) {
        b.addEventListener('click', function () { close(true); });
    });
    document.addEventListener('keydown', function (e) {
        if (!overlay.hidden && e.key === 'Escape') close(true);
    });
    btnOk.addEventListener('click', confirm);

    return { open: open, close: close };
})();
</script>
