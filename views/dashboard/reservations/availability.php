<?php
$giorniIt = [1 => 'Lun', 2 => 'Mar', 3 => 'Mer', 4 => 'Gio', 5 => 'Ven', 6 => 'Sab', 7 => 'Dom'];
$tomorrow = date('Y-m-d', strtotime($today . ' +1 day'));
?>
<div class="av-page">

    <div class="page-back">
        <a href="<?= url('dashboard/reservations') ?>"><i class="bi bi-arrow-left"></i> Torna alle prenotazioni</a>
    </div>

    <div class="av-head">
        <h1>Disponibilità online</h1>
        <p class="av-sub">Ferma le prenotazioni <strong>online</strong> per un giorno o un servizio quando sei pieno. Il locale resta aperto: le prenotazioni già ricevute non vengono toccate e puoi sempre aggiungerne a mano.</p>
    </div>

    <!-- Form: chiudi una data / intervallo (calendario Evulery, selezione singola o range) -->
    <div class="av-card">
        <div class="av-card-h"><i class="bi bi-slash-circle"></i> Chiudi le prenotazioni online</div>
        <form method="POST" action="<?= url('dashboard/reservations/availability/close') ?>" class="av-form" id="av-form">
            <?= csrf_field() ?>
            <input type="hidden" name="date_from" id="av-date-from">
            <input type="hidden" name="date_to" id="av-date-to">
            <div class="av-cal-block">
                <div class="av-cal-side">
                    <div class="av-cal-sel">
                        <div class="av-cal-sel-label">Date selezionate</div>
                        <div id="av-sel-dates"><span class="av-sel-placeholder">Clicca una data (o due per un intervallo).</span></div>
                    </div>
                    <div class="dr-cal-header">
                        <button type="button" class="dr-cal-nav" id="av-cal-prev"><i class="bi bi-chevron-left"></i></button>
                        <span class="dr-cal-month" id="av-cal-month"></span>
                        <button type="button" class="dr-cal-nav" id="av-cal-next"><i class="bi bi-chevron-right"></i></button>
                    </div>
                    <div class="dr-cal-days-header">
                        <div class="dr-cal-day-name">lun</div>
                        <div class="dr-cal-day-name">mar</div>
                        <div class="dr-cal-day-name">mer</div>
                        <div class="dr-cal-day-name">gio</div>
                        <div class="dr-cal-day-name">ven</div>
                        <div class="dr-cal-day-name">sab</div>
                        <div class="dr-cal-day-name">dom</div>
                    </div>
                    <div class="dr-cal-grid" id="av-cal-grid"></div>
                </div>
                <div class="av-cal-right">
                    <label class="av-fg">
                        <span class="av-lbl">Ambito</span>
                        <select name="scope" class="av-inp">
                            <option value="day">Tutto il giorno</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat['name']) ?>">Solo <?= e($cat['display_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button type="submit" class="av-btn-close" id="av-submit" disabled><i class="bi bi-check-lg"></i> Chiudi le prenotazioni</button>
                    <p class="av-hint">
                        <i class="bi bi-info-circle"></i>
                        Le fasce dell'ambito valgono per gli orari che esistono davvero nella data scelta. Le date già chiuse sono evidenziate.
                    </p>
                </div>
            </div>
        </form>
    </div>

    <!-- Elenco date con online chiuso -->
    <div class="av-card">
        <div class="av-card-h"><i class="bi bi-calendar-x"></i> Date con prenotazioni online chiuse</div>
        <?php if (empty($closedDates)): ?>
        <div class="av-empty">
            <i class="bi bi-calendar3"></i>
            <div class="av-empty-t">Nessuna data con prenotazioni online chiuse</div>
            <div class="av-empty-s">Quando sei al completo, chiudi una data qui sopra.</div>
        </div>
        <?php else: ?>
        <div class="av-sub-count"><?= count($closedDates) ?> <?= count($closedDates) === 1 ? 'data' : 'date' ?> · dalla più vicina</div>
        <?php foreach ($closedDates as $cd): ?>
            <?php
                $wd  = $giorniIt[(int)date('N', strtotime($cd['date']))] ?? '';
                $rel = $cd['date'] === $today ? 'Oggi' : ($cd['date'] === $tomorrow ? 'Domani' : '');
            ?>
        <div class="av-row">
            <a href="<?= url('dashboard/reservations?date=' . e($cd['date'])) ?>" class="av-date-link" title="Vedi le prenotazioni di questo giorno">
                <?php if ($rel !== ''): ?><span class="av-rel <?= $rel === 'Oggi' ? 'today' : 'tomorrow' ?>"><?= $rel ?></span><?php endif; ?>
                <span class="av-date"><?= $wd ?> <?= format_date($cd['date'], 'd M') ?><small><?= format_date($cd['date'], 'Y') ?></small></span>
                <i class="bi bi-chevron-right av-date-arrow"></i>
            </a>
            <span class="av-pill"><?= e($cd['label']) ?></span>
            <span class="av-meta"><?= (int)$cd['slots'] ?> orari<?= $cd['whole'] ? '' : ' bloccati' ?></span>
            <span class="av-spacer"></span>
            <form method="POST" action="<?= url('dashboard/reservations/availability/reopen') ?>" class="av-reopen-form">
                <?= csrf_field() ?>
                <input type="hidden" name="date" value="<?= e($cd['date']) ?>">
                <button type="submit" class="av-reopen"><i class="bi bi-arrow-counterclockwise"></i> Riapri</button>
            </form>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Distinzione dalla chiusura straordinaria -->
    <div class="av-note-diff">
        <span class="av-note-ic"><i class="bi bi-exclamation-octagon"></i></span>
        <div>
            <strong>Non è una chiusura.</strong> Qui il locale resta <strong>aperto</strong> (il cliente vede “tutto prenotato”). Per un imprevisto che chiude davvero — guasto, ferie, emergenza — usa
            <a href="<?= url('dashboard/emergency-closure') ?>">Chiusura straordinaria</a> (il cliente vede “chiuso”).
        </div>
    </div>

</div>

<script nonce="<?= csp_nonce() ?>">
(function() {
    var MONTHS = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
    var closedSet = {};
    (<?= json_encode(array_column($closedDates, 'date')) ?>).forEach(function(d) { closedSet[d] = true; });

    var calMonth = new Date().getMonth();
    var calYear = new Date().getFullYear();
    var dateFrom = null, dateTo = null;

    var elGrid  = document.getElementById('av-cal-grid');
    var elMonth = document.getElementById('av-cal-month');
    var elPrev  = document.getElementById('av-cal-prev');
    var elNext  = document.getElementById('av-cal-next');
    var elFrom  = document.getElementById('av-date-from');
    var elTo    = document.getElementById('av-date-to');
    var elSel   = document.getElementById('av-sel-dates');
    var elSubmit = document.getElementById('av-submit');
    if (!elGrid) return;

    function fmt(d) {
        return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
    }
    function fmtLabel(s) {
        var p = s.split('-');
        return parseInt(p[2]) + ' ' + MONTHS[parseInt(p[1])-1].substring(0,3) + ' ' + p[0];
    }
    function isInRange(s) { return dateFrom && dateTo && s >= dateFrom && s <= dateTo; }

    function updateSelection() {
        elFrom.value = dateFrom || '';
        elTo.value = dateTo || '';
        elSubmit.disabled = !dateFrom;
        if (!dateFrom) {
            elSel.innerHTML = '<span class="av-sel-placeholder">Clicca una data (o due per un intervallo).</span>';
        } else if (!dateTo || dateTo === dateFrom) {
            elSel.innerHTML = '<span class="cl-sel-tag"><i class="bi bi-calendar-event me-1"></i>' + fmtLabel(dateFrom) + '</span>';
        } else {
            var d1 = new Date(dateFrom + 'T00:00:00'), d2 = new Date(dateTo + 'T00:00:00');
            var days = Math.round((d2 - d1) / 86400000) + 1;
            elSel.innerHTML = '<span class="cl-sel-tag"><i class="bi bi-calendar-range me-1"></i>' + fmtLabel(dateFrom) + ' &rarr; ' + fmtLabel(dateTo) + '</span>'
                + '<span class="cl-sel-days">' + days + ' giorni</span>';
        }
    }

    function renderCalendar() {
        elMonth.textContent = MONTHS[calMonth] + ' ' + calYear;
        var firstDay = new Date(calYear, calMonth, 1);
        var startDow = firstDay.getDay() - 1; if (startDow < 0) startDow = 6;
        var daysInMonth = new Date(calYear, calMonth + 1, 0).getDate();
        var today = new Date(); today.setHours(0,0,0,0);
        var todayStr = fmt(today);

        var html = '';
        for (var i = 0; i < startDow; i++) html += '<div class="dr-cal-cell dr-cal-empty"></div>';
        for (var day = 1; day <= daysInMonth; day++) {
            var d = new Date(calYear, calMonth, day); d.setHours(0,0,0,0);
            var s = fmt(d);
            var classes = 'dr-cal-cell';
            var isPast = d < today;
            if (s === todayStr) classes += ' dr-cal-today';
            if (isPast) classes += ' dr-cal-disabled';
            if (s === dateFrom || s === dateTo) classes += ' dr-cal-selected';
            else if (isInRange(s)) classes += ' cl-cal-in-range';
            if (closedSet[s] && !isPast) classes += ' cl-cal-closed';
            html += '<div class="' + classes + '" data-date="' + s + '">' + day + '</div>';
        }
        elGrid.innerHTML = html;

        elGrid.querySelectorAll('.dr-cal-cell:not(.dr-cal-disabled):not(.dr-cal-empty)').forEach(function(cell) {
            cell.addEventListener('click', function() {
                var c = this.dataset.date;
                if (!dateFrom || (dateFrom && dateTo)) {
                    dateFrom = c; dateTo = null;
                } else if (c < dateFrom) {
                    dateTo = dateFrom; dateFrom = c;
                } else if (c === dateFrom) {
                    dateTo = null;
                } else {
                    dateTo = c;
                }
                updateSelection();
                renderCalendar();
            });
        });

        var nowMonth = today.getMonth(), nowYear = today.getFullYear();
        elPrev.disabled = (calYear === nowYear && calMonth <= nowMonth);
    }

    elPrev.addEventListener('click', function() { calMonth--; if (calMonth < 0) { calMonth = 11; calYear--; } renderCalendar(); });
    elNext.addEventListener('click', function() { calMonth++; if (calMonth > 11) { calMonth = 0; calYear++; } renderCalendar(); });

    document.getElementById('av-form').addEventListener('submit', function(e) {
        if (!dateFrom) { e.preventDefault(); alert('Seleziona almeno una data dal calendario.'); }
    });

    updateSelection();
    renderCalendar();
})();
</script>
