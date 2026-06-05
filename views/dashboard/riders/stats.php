<?php
// Variabili: $stats (array per rider con total/completed/cancelled/avg_minutes/total_value),
//            $kpi (aggregati globali), $dateFrom, $dateTo
$MONTHS_IT = ['', 'Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'];
$fromTs = strtotime($dateFrom);
$toTs   = strtotime($dateTo);
$periodLabel = (int)date('Y', $fromTs) === (int)date('Y', $toTs)
    ? sprintf('%d %s — %d %s %d', (int)date('j', $fromTs), $MONTHS_IT[(int)date('n', $fromTs)], (int)date('j', $toTs), $MONTHS_IT[(int)date('n', $toTs)], (int)date('Y', $toTs))
    : sprintf('%s — %s', date('d/m/Y', $fromTs), date('d/m/Y', $toTs));
?>

<div class="rd-page-header">
    <div>
        <h1>Statistiche Rider</h1>
        <p class="rd-page-sub">Performance per rider · <?= e($periodLabel) ?></p>
    </div>
    <div class="rd-page-actions">
        <a href="<?= url('dashboard/riders') ?>" class="btn btn-outline-success">
            <i class="bi bi-arrow-left me-1"></i> Anagrafica
        </a>
    </div>
</div>

<!-- Range selector -->
<form method="GET" action="<?= url('dashboard/riders/stats') ?>" class="rd-range-form">
    <div class="rd-range-quick">
        <?php
            $today = date('Y-m-d');
            $monthStart = date('Y-m-01');
            $sevenAgo = date('Y-m-d', strtotime('-7 days'));
            $thirtyAgo = date('Y-m-d', strtotime('-30 days'));
            $ranges = [
                'Mese corrente' => [$monthStart, $today],
                'Ultimi 7 giorni' => [$sevenAgo, $today],
                'Ultimi 30 giorni' => [$thirtyAgo, $today],
            ];
        ?>
        <?php foreach ($ranges as $label => $r): ?>
            <?php $active = ($dateFrom === $r[0] && $dateTo === $r[1]); ?>
            <a href="<?= url('dashboard/riders/stats') . '?from=' . $r[0] . '&to=' . $r[1] ?>"
               class="rd-range-chip <?= $active ? 'rd-range-chip--active' : '' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>
    <!--
        Range custom: due trigger Da/A che aprono il calendario custom
        Evulery (pattern .dr-cal-* riusato da home.php / reservations).
        Niente input nativi del browser per coerenza con il resto della
        dashboard. Bottone "Oggi" dentro il calendario per selezione rapida.
    -->
    <div class="rd-range-custom">
        <input type="hidden" name="from" id="rd-stats-from-input" value="<?= e($dateFrom) ?>">
        <input type="hidden" name="to"   id="rd-stats-to-input"   value="<?= e($dateTo) ?>">

        <button type="button" class="rd-date-trigger" id="rd-stats-from-trigger" data-target="from">
            <i class="bi bi-calendar3"></i>
            <span class="rd-date-trigger-label">Da</span>
            <span class="rd-date-trigger-value" id="rd-stats-from-label"><?= date('d/m/Y', strtotime($dateFrom)) ?></span>
        </button>
        <span style="font-size:.78rem;color:#6c757d;">→</span>
        <button type="button" class="rd-date-trigger" id="rd-stats-to-trigger" data-target="to">
            <i class="bi bi-calendar3"></i>
            <span class="rd-date-trigger-label">A</span>
            <span class="rd-date-trigger-value" id="rd-stats-to-label"><?= date('d/m/Y', strtotime($dateTo)) ?></span>
        </button>
        <button type="submit" class="btn btn-sm btn-outline-success">Applica</button>

        <!-- Calendario Evulery condiviso, posizionato dal JS sotto il trigger cliccato -->
        <div class="rd-date-cal" id="rd-stats-cal" style="display:none;">
            <div class="dr-cal-header">
                <button type="button" class="dr-cal-nav" id="rd-cal-prev"><i class="bi bi-chevron-left"></i></button>
                <span class="dr-cal-month" id="rd-cal-month"></span>
                <button type="button" class="dr-cal-nav" id="rd-cal-next"><i class="bi bi-chevron-right"></i></button>
            </div>
            <div class="dr-cal-days-header">
                <div class="dr-cal-day-name">lun</div><div class="dr-cal-day-name">mar</div><div class="dr-cal-day-name">mer</div>
                <div class="dr-cal-day-name">gio</div><div class="dr-cal-day-name">ven</div><div class="dr-cal-day-name">sab</div>
                <div class="dr-cal-day-name">dom</div>
            </div>
            <div class="dr-cal-grid" id="rd-cal-grid"></div>
            <div class="rd-cal-footer">
                <button type="button" class="rd-cal-today-btn" id="rd-cal-today">
                    <i class="bi bi-calendar-event me-1"></i> Oggi
                </button>
            </div>
        </div>
    </div>
</form>

<script nonce="<?= csp_nonce() ?>">
(function () {
    var MONTHS = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
    var fromInput  = document.getElementById('rd-stats-from-input');
    var toInput    = document.getElementById('rd-stats-to-input');
    var fromLabel  = document.getElementById('rd-stats-from-label');
    var toLabel    = document.getElementById('rd-stats-to-label');
    var fromTrigger = document.getElementById('rd-stats-from-trigger');
    var toTrigger   = document.getElementById('rd-stats-to-trigger');
    var dropdown   = document.getElementById('rd-stats-cal');
    var grid       = document.getElementById('rd-cal-grid');
    var monthLabel = document.getElementById('rd-cal-month');
    var prevBtn    = document.getElementById('rd-cal-prev');
    var nextBtn    = document.getElementById('rd-cal-next');
    var todayBtn   = document.getElementById('rd-cal-today');

    var today = new Date(); today.setHours(0,0,0,0);
    var currentTarget = null;          // 'from' | 'to'
    var calMonth, calYear;

    function isoDate(d) {
        var m = d.getMonth() + 1, day = d.getDate();
        return d.getFullYear() + '-' + (m < 10 ? '0' + m : m) + '-' + (day < 10 ? '0' + day : day);
    }
    function formatIt(iso) {
        var p = iso.split('-');
        return p[2] + '/' + p[1] + '/' + p[0];
    }
    function currentSelected() {
        return currentTarget === 'from' ? fromInput.value : toInput.value;
    }
    function renderCal() {
        monthLabel.textContent = MONTHS[calMonth] + ' ' + calYear;
        var first = new Date(calYear, calMonth, 1);
        var startDow = first.getDay() - 1; if (startDow < 0) startDow = 6;  // settimana inizia lunedi'
        var days = new Date(calYear, calMonth + 1, 0).getDate();
        var selectedIso = currentSelected();
        var html = '';
        for (var i = 0; i < startDow; i++) html += '<div class="dr-cal-cell dr-cal-empty"></div>';
        for (var d = 1; d <= days; d++) {
            var dt = new Date(calYear, calMonth, d); dt.setHours(0,0,0,0);
            var ds = isoDate(dt);
            var cls = 'dr-cal-cell';
            if (dt.getTime() === today.getTime()) cls += ' dr-cal-today';
            if (ds === selectedIso) cls += ' dr-cal-selected';
            html += '<div class="' + cls + '" data-date="' + ds + '">' + d + '</div>';
        }
        grid.innerHTML = html;
        grid.querySelectorAll('.dr-cal-cell:not(.dr-cal-empty)').forEach(function (cell) {
            cell.addEventListener('click', function () { pick(this.getAttribute('data-date')); });
        });
    }
    function open(target) {
        currentTarget = target;
        var trigger = (target === 'from') ? fromTrigger : toTrigger;
        var iso = currentSelected();
        var d = iso ? new Date(iso + 'T00:00:00') : today;
        calMonth = d.getMonth();
        calYear  = d.getFullYear();
        // Posizionamento under-trigger
        var rect = trigger.getBoundingClientRect();
        dropdown.style.position = 'fixed';
        dropdown.style.top  = (rect.bottom + 6) + 'px';
        dropdown.style.left = rect.left + 'px';
        dropdown.style.display = 'block';
        renderCal();
    }
    function close() { dropdown.style.display = 'none'; currentTarget = null; }
    function pick(iso) {
        if (currentTarget === 'from') {
            fromInput.value = iso;
            fromLabel.textContent = formatIt(iso);
        } else if (currentTarget === 'to') {
            toInput.value = iso;
            toLabel.textContent = formatIt(iso);
        }
        close();
    }

    fromTrigger.addEventListener('click', function (e) { e.stopPropagation(); currentTarget === 'from' ? close() : open('from'); });
    toTrigger.addEventListener('click',   function (e) { e.stopPropagation(); currentTarget === 'to'   ? close() : open('to');   });
    prevBtn.addEventListener('click', function (e) { e.stopPropagation(); calMonth--; if (calMonth < 0) { calMonth = 11; calYear--; } renderCal(); });
    nextBtn.addEventListener('click', function (e) { e.stopPropagation(); calMonth++; if (calMonth > 11) { calMonth = 0;  calYear++; } renderCal(); });
    todayBtn.addEventListener('click', function (e) { e.stopPropagation(); pick(isoDate(today)); });
    document.addEventListener('click', function (e) {
        if (!e.target.closest('#rd-stats-cal') &&
            !e.target.closest('#rd-stats-from-trigger') &&
            !e.target.closest('#rd-stats-to-trigger')) close();
    });
})();
</script>

<!-- KPI cards -->
<div class="rd-kpi-grid">
    <div class="rd-kpi-card">
        <div class="rd-kpi-label">Consegne totali</div>
        <div class="rd-kpi-value"><?= (int)$kpi['total'] ?></div>
        <div class="rd-kpi-sub"><?= (int)$kpi['completed'] ?> completate · <?= (int)$kpi['cancelled'] ?> annullate</div>
    </div>
    <div class="rd-kpi-card">
        <div class="rd-kpi-label">Tempo medio consegna</div>
        <div class="rd-kpi-value">
            <?php if ($kpi['avg_minutes'] !== null): ?>
                <?= (int)$kpi['avg_minutes'] ?> <span style="font-size:.7em;color:#6c757d;font-weight:500;">min</span>
            <?php else: ?>
                <span style="color:#adb5bd;">—</span>
            <?php endif; ?>
        </div>
        <div class="rd-kpi-sub">dal momento dell'assegnazione</div>
    </div>
    <div class="rd-kpi-card">
        <div class="rd-kpi-label">Valore gestito</div>
        <div class="rd-kpi-value">€ <?= number_format((float)$kpi['total_value'], 0, ',', '.') ?></div>
        <div class="rd-kpi-sub">totale ordini completati</div>
    </div>
    <div class="rd-kpi-card">
        <div class="rd-kpi-label">% completate</div>
        <div class="rd-kpi-value">
            <?php if ($kpi['completion_rate'] !== null): ?>
                <?= (int)$kpi['completion_rate'] ?>%
            <?php else: ?>
                <span style="color:#adb5bd;">—</span>
            <?php endif; ?>
        </div>
        <div class="rd-kpi-sub">completate / totali</div>
    </div>
</div>

<!-- Tabella per rider -->
<?php
$activeRiders = array_filter($stats, fn($s) => (int)$s['is_active'] === 1 || (int)$s['total'] > 0);
?>
<?php if (empty($activeRiders)): ?>
<div class="card" style="padding:2rem;text-align:center;">
    <i class="bi bi-graph-up" style="font-size:2rem;color:#adb5bd;"></i>
    <h2 style="font-size:1rem;margin:.75rem 0 .25rem;">Nessun dato in questo periodo</h2>
    <p style="font-size:.82rem;color:#6c757d;margin:0;">Aggiungi rider e assegna ordini per vedere le statistiche.</p>
</div>
<?php else: ?>
<div class="card rd-card d-none d-md-block">
    <table class="rd-table">
        <thead>
            <tr>
                <th style="width:25%;">Rider</th>
                <th>Consegne</th>
                <th>Completate</th>
                <th>Annullate</th>
                <th>Tempo medio</th>
                <th>Valore gestito</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($activeRiders as $s): ?>
            <tr class="<?= (int)$s['is_active'] === 0 ? 'rd-row-inactive' : '' ?>">
                <td>
                    <div class="rd-name">
                        <span class="rd-dot" style="background:<?= e($s['color_hex']) ?>;"></span>
                        <?= e($s['name']) ?>
                        <?php if ((int)$s['is_active'] === 0): ?>
                            <span class="rd-status rd-status--inactive" style="margin-left:.4rem;">Archiviato</span>
                        <?php endif; ?>
                    </div>
                </td>
                <td><strong><?= (int)$s['total'] ?></strong></td>
                <td><?= (int)$s['completed'] ?></td>
                <td><?= (int)$s['cancelled'] ?></td>
                <td>
                    <?php if ($s['avg_minutes'] !== null): ?>
                        <?= (int)$s['avg_minutes'] ?> min
                    <?php else: ?>
                        <span class="rd-empty">—</span>
                    <?php endif; ?>
                </td>
                <td>€ <?= number_format((float)$s['total_value'], 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Mobile cards -->
<div class="d-md-none">
    <?php foreach ($activeRiders as $s): ?>
    <div class="card rd-card-m">
        <div class="rd-card-m-head">
            <div class="rd-name">
                <span class="rd-dot" style="background:<?= e($s['color_hex']) ?>;"></span>
                <?= e($s['name']) ?>
            </div>
            <strong><?= (int)$s['total'] ?> ordini</strong>
        </div>
        <div class="rd-card-m-meta">
            <span><?= (int)$s['completed'] ?> ok · <?= (int)$s['cancelled'] ?> ann.</span>
            <span><?= $s['avg_minutes'] !== null ? ((int)$s['avg_minutes'] . ' min') : '—' ?></span>
            <span><strong>€ <?= number_format((float)$s['total_value'], 0, ',', '.') ?></strong></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="rd-stats-note">
    <i class="bi bi-info-circle"></i>
    <strong>Tempo medio consegna</strong> = minuti dal momento in cui hai assegnato l'ordine al rider fino al passaggio in stato "completato". Non include il tempo in preparazione (responsabilità della cucina, non del rider).
</div>
