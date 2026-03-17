<?php
$settingsTabs = [
    ['url' => url('dashboard/settings'),                'icon' => 'bi-gear',         'label' => 'Generali',         'key' => 'settings'],
    ['url' => url('dashboard/settings/slots'),          'icon' => 'bi-clock',        'label' => 'Orari e Coperti',  'key' => 'slots'],
    ['url' => url('dashboard/settings/meal-categories'),'icon' => 'bi-tags',         'label' => 'Categorie Pasto',  'key' => 'meal-categories'],
    ['url' => url('dashboard/settings/closures'),       'icon' => 'bi-calendar-x',   'label' => 'Chiusure',         'key' => 'closures'],
    ['url' => url('dashboard/settings/deposit'),        'icon' => 'bi-cash',         'label' => 'Caparra',          'key' => 'deposit'],
    ['url' => url('dashboard/settings/domain'),         'icon' => 'bi-globe',        'label' => 'Dominio',          'key' => 'domain'],
];

$MONTHS_IT = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
$DAYS_IT = ['Lun','Mar','Mer','Gio','Ven','Sab','Dom'];

// Build set of already-closed dates for calendar highlights
$closedSet = [];
foreach (array_merge($upcoming, $past) as $c) {
    $closedSet[$c['override_date']] = true;
}
?>

<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">Impostazioni</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1rem;">Configura il tuo ristorante</p>

<!-- Settings tabs -->
<div class="settings-tabs">
    <?php foreach ($settingsTabs as $tab): ?>
    <a href="<?= $tab['url'] ?>" class="settings-tab <?= $tab['key'] === 'closures' ? 'active' : '' ?>">
        <i class="bi <?= $tab['icon'] ?>"></i> <?= $tab['label'] ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Info banner -->
<div class="info-banner">
    <div class="info-banner-icon" style="background:#FFF3E0; color:#E65100;">
        <i class="bi bi-calendar-x"></i>
    </div>
    <div class="info-banner-text">
        Gestisci i <strong>giorni di chiusura</strong>, ferie e festivi.
        Le date chiuse non saranno prenotabili dal widget.
    </div>
</div>

<div class="row g-4">
    <!-- Left: Add closure form -->
    <div class="col-lg-5">
        <div class="card" style="padding:1.25rem;">
            <div style="font-weight:600; font-size:.95rem; margin-bottom:1rem;">
                <i class="bi bi-plus-circle me-1" style="color:var(--brand);"></i> Aggiungi chiusura
            </div>

            <form method="POST" action="<?= url('dashboard/settings/closures') ?>" id="cl-form">
                <?= csrf_field() ?>
                <input type="hidden" name="date_from" id="cl-date-from">
                <input type="hidden" name="date_to" id="cl-date-to">

                <!-- Selection display -->
                <div class="cl-selection" id="cl-selection">
                    <div class="cl-sel-label">Seleziona date dal calendario:</div>
                    <div class="cl-sel-dates" id="cl-sel-dates">
                        <span class="cl-sel-placeholder">Nessuna data selezionata</span>
                    </div>
                    <div style="font-size:.72rem; color:#adb5bd; margin-top:.25rem;">
                        Clicca una data per giorno singolo. Clicca due date per un range.
                    </div>
                </div>

                <!-- Mini calendar -->
                <div style="margin-bottom:1rem;">
                    <div class="dr-cal-header" style="margin-bottom:.5rem;">
                        <button type="button" class="dr-cal-nav" id="cl-cal-prev"><i class="bi bi-chevron-left"></i></button>
                        <span class="dr-cal-month" id="cl-cal-month"></span>
                        <button type="button" class="dr-cal-nav" id="cl-cal-next"><i class="bi bi-chevron-right"></i></button>
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
                    <div class="dr-cal-grid" id="cl-cal-grid"></div>
                </div>

                <!-- Quick buttons -->
                <div style="margin-bottom:.85rem;">
                    <div style="font-size:.78rem; color:#6c757d; margin-bottom:.4rem;">Scorciatoie:</div>
                    <div class="cl-quick-btns">
                        <button type="button" class="cl-quick-btn" data-preset="today">Oggi</button>
                        <button type="button" class="cl-quick-btn" data-preset="tomorrow">Domani</button>
                        <button type="button" class="cl-quick-btn" data-preset="next-monday">Prossimo lunedi</button>
                        <button type="button" class="cl-quick-btn" data-preset="christmas">Natale</button>
                        <button type="button" class="cl-quick-btn" data-preset="newyear">Capodanno</button>
                        <button type="button" class="cl-quick-btn" data-preset="aug">Agosto (1-31)</button>
                    </div>
                </div>

                <div class="cl-form-group">
                    <label class="cl-label">Motivo <span style="color:#adb5bd; font-weight:400;">(opzionale)</span></label>
                    <input type="text" name="note" id="cl-note" class="cl-input" placeholder="Es. Ferie estive, Festa patronale..."
                           maxlength="255">
                </div>

                <button type="submit" class="btn-save" style="width:100%;" id="cl-submit" disabled>
                    <i class="bi bi-plus-circle me-1"></i> Aggiungi Chiusura
                </button>
            </form>
        </div>
    </div>

    <!-- Right: Closures list -->
    <div class="col-lg-7">
        <!-- Upcoming -->
        <div style="font-weight:600; font-size:.95rem; margin-bottom:.5rem;">
            <i class="bi bi-calendar-event me-1" style="color:var(--brand);"></i>
            Chiusure programmate
            <?php if (!empty($upcoming)): ?>
            <span class="cl-count"><?= count($upcoming) ?></span>
            <?php endif; ?>
        </div>

        <?php if (empty($upcoming)): ?>
        <div class="card" style="padding:2rem; text-align:center;">
            <i class="bi bi-calendar-check" style="font-size:2rem; color:#adb5bd;"></i>
            <p style="color:#6c757d; margin-top:.5rem; font-size:.88rem;">Nessuna chiusura programmata.</p>
        </div>
        <?php else: ?>
        <div class="card" style="padding:0;">
            <?php
            // Group by month
            $byMonth = [];
            foreach ($upcoming as $c) {
                $m = date('Y-m', strtotime($c['override_date']));
                $byMonth[$m][] = $c;
            }
            ?>
            <?php foreach ($byMonth as $monthKey => $items):
                $monthTs = strtotime($monthKey . '-01');
                $monthLabel = $MONTHS_IT[(int)date('n', $monthTs) - 1] . ' ' . date('Y', $monthTs);
            ?>
            <div class="cl-month-header"><?= $monthLabel ?></div>
            <?php foreach ($items as $c):
                $d = strtotime($c['override_date']);
                $dayName = $DAYS_IT[(int)date('N', $d) - 1];
                $dayNum = date('d', $d);
            ?>
            <div class="cl-item">
                <div class="cl-date-box">
                    <div class="cl-day-name"><?= $dayName ?></div>
                    <div class="cl-day-num"><?= $dayNum ?></div>
                </div>
                <div class="cl-info">
                    <div class="cl-date-full"><?= date('d', $d) ?> <?= $MONTHS_IT[(int)date('n', $d) - 1] ?> <?= date('Y', $d) ?></div>
                    <?php if ($c['note']): ?>
                    <div class="cl-note"><?= e($c['note']) ?></div>
                    <?php else: ?>
                    <div class="cl-note" style="color:#ccc;">Chiusura giornaliera</div>
                    <?php endif; ?>
                </div>
                <form method="POST" action="<?= url("dashboard/settings/closures/{$c['id']}/delete") ?>" style="margin:0;">
                    <?= csrf_field() ?>
                    <button type="submit" class="cl-delete-btn" title="Rimuovi chiusura">
                        <i class="bi bi-trash3"></i>
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Past closures (collapsed) -->
        <?php if (!empty($past)): ?>
        <div style="margin-top:1rem;">
            <button type="button" class="cl-toggle-past" id="cl-toggle-past">
                <i class="bi bi-clock-history me-1"></i> Chiusure passate (<?= count($past) ?>)
                <i class="bi bi-chevron-down" style="margin-left:auto;"></i>
            </button>
            <div id="cl-past-list" style="display:none;">
                <div class="card" style="padding:0; opacity:.6;">
                    <?php foreach (array_slice($past, 0, 20) as $c):
                        $d = strtotime($c['override_date']);
                        $dayName = $DAYS_IT[(int)date('N', $d) - 1];
                    ?>
                    <div class="cl-item cl-past">
                        <div class="cl-date-box">
                            <div class="cl-day-name"><?= $dayName ?></div>
                            <div class="cl-day-num"><?= date('d', $d) ?></div>
                        </div>
                        <div class="cl-info">
                            <div class="cl-date-full"><?= date('d', $d) ?> <?= $MONTHS_IT[(int)date('n', $d) - 1] ?> <?= date('Y', $d) ?></div>
                            <?php if ($c['note']): ?>
                            <div class="cl-note"><?= e($c['note']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function() {
    var MONTHS = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
    var closedDates = <?= json_encode(array_keys($closedSet)) ?>;
    var closedSet = {};
    closedDates.forEach(function(d) { closedSet[d] = true; });

    var calMonth = new Date().getMonth();
    var calYear = new Date().getFullYear();

    var dateFrom = null;
    var dateTo = null;

    var elGrid = document.getElementById('cl-cal-grid');
    var elMonth = document.getElementById('cl-cal-month');
    var elPrev = document.getElementById('cl-cal-prev');
    var elNext = document.getElementById('cl-cal-next');
    var elDateFrom = document.getElementById('cl-date-from');
    var elDateTo = document.getElementById('cl-date-to');
    var elSelDates = document.getElementById('cl-sel-dates');
    var elSubmit = document.getElementById('cl-submit');
    var elNote = document.getElementById('cl-note');

    function fmt(d) {
        return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
    }

    function fmtLabel(dateStr) {
        var p = dateStr.split('-');
        return parseInt(p[2]) + ' ' + MONTHS[parseInt(p[1])-1].substring(0,3) + ' ' + p[0];
    }

    function isInRange(dateStr) {
        if (!dateFrom || !dateTo) return false;
        return dateStr >= dateFrom && dateStr <= dateTo;
    }

    function updateSelection() {
        elDateFrom.value = dateFrom || '';
        elDateTo.value = dateTo || '';
        elSubmit.disabled = !dateFrom;

        if (!dateFrom) {
            elSelDates.innerHTML = '<span class="cl-sel-placeholder">Nessuna data selezionata</span>';
        } else if (!dateTo || dateTo === dateFrom) {
            elSelDates.innerHTML = '<span class="cl-sel-tag"><i class="bi bi-calendar-event me-1"></i>' + fmtLabel(dateFrom) + '</span>';
        } else {
            var d1 = new Date(dateFrom + 'T00:00:00');
            var d2 = new Date(dateTo + 'T00:00:00');
            var days = Math.round((d2 - d1) / 86400000) + 1;
            elSelDates.innerHTML = '<span class="cl-sel-tag"><i class="bi bi-calendar-range me-1"></i>' + fmtLabel(dateFrom) + ' &rarr; ' + fmtLabel(dateTo) + '</span>'
                + '<span class="cl-sel-days">' + days + ' giorni</span>';
        }
    }

    function renderCalendar() {
        elMonth.textContent = MONTHS[calMonth] + ' ' + calYear;

        var firstDay = new Date(calYear, calMonth, 1);
        var startDow = firstDay.getDay() - 1;
        if (startDow < 0) startDow = 6;
        var daysInMonth = new Date(calYear, calMonth + 1, 0).getDate();

        var today = new Date();
        today.setHours(0,0,0,0);
        var todayStr = fmt(today);

        var html = '';
        for (var i = 0; i < startDow; i++) {
            html += '<div class="dr-cal-cell dr-cal-empty"></div>';
        }

        for (var day = 1; day <= daysInMonth; day++) {
            var d = new Date(calYear, calMonth, day);
            d.setHours(0,0,0,0);
            var dateStr = fmt(d);

            var classes = 'dr-cal-cell';
            var isPast = d < today;
            var isClosed = !!closedSet[dateStr];

            if (dateStr === todayStr) classes += ' dr-cal-today';
            if (isPast) classes += ' dr-cal-disabled';

            // Highlight selection
            if (dateStr === dateFrom || dateStr === dateTo) {
                classes += ' dr-cal-selected';
            } else if (isInRange(dateStr)) {
                classes += ' cl-cal-in-range';
            }

            // Mark already-closed dates
            if (isClosed && !isPast) {
                classes += ' cl-cal-closed';
            }

            html += '<div class="' + classes + '" data-date="' + dateStr + '">' + day + '</div>';
        }

        elGrid.innerHTML = html;

        // Bind clicks
        elGrid.querySelectorAll('.dr-cal-cell:not(.dr-cal-disabled):not(.dr-cal-empty)').forEach(function(cell) {
            cell.addEventListener('click', function() {
                var clicked = this.dataset.date;

                if (!dateFrom || (dateFrom && dateTo)) {
                    // Start new selection
                    dateFrom = clicked;
                    dateTo = null;
                } else {
                    // Set end of range
                    if (clicked < dateFrom) {
                        dateTo = dateFrom;
                        dateFrom = clicked;
                    } else if (clicked === dateFrom) {
                        // Same date = single day
                        dateTo = null;
                    } else {
                        dateTo = clicked;
                    }
                }
                updateSelection();
                renderCalendar();
            });
        });

        // Nav state
        var nowMonth = today.getMonth();
        var nowYear = today.getFullYear();
        elPrev.disabled = (calYear === nowYear && calMonth <= nowMonth);
    }

    elPrev.addEventListener('click', function() {
        calMonth--;
        if (calMonth < 0) { calMonth = 11; calYear--; }
        renderCalendar();
    });

    elNext.addEventListener('click', function() {
        calMonth++;
        if (calMonth > 11) { calMonth = 0; calYear++; }
        renderCalendar();
    });

    // Quick preset buttons
    document.querySelectorAll('.cl-quick-btn[data-preset]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var today = new Date();
            var y = today.getFullYear();

            switch(this.dataset.preset) {
                case 'today':
                    dateFrom = fmt(today); dateTo = null;
                    break;
                case 'tomorrow':
                    var t = new Date(today); t.setDate(t.getDate()+1);
                    dateFrom = fmt(t); dateTo = null;
                    break;
                case 'next-monday':
                    var nm = new Date(today);
                    var dow = nm.getDay();
                    nm.setDate(nm.getDate() + (dow === 0 ? 1 : (8 - dow)));
                    dateFrom = fmt(nm); dateTo = null;
                    elNote.value = 'Chiusura settimanale';
                    break;
                case 'christmas':
                    dateFrom = y + '-12-25'; dateTo = y + '-12-26';
                    elNote.value = 'Natale e Santo Stefano';
                    calMonth = 11; calYear = y;
                    break;
                case 'newyear':
                    dateFrom = (y+1) + '-01-01'; dateTo = null;
                    elNote.value = 'Capodanno';
                    calMonth = 0; calYear = y+1;
                    break;
                case 'aug':
                    dateFrom = y + '-08-01'; dateTo = y + '-08-31';
                    elNote.value = 'Ferie estive';
                    calMonth = 7; calYear = y;
                    break;
            }
            updateSelection();
            renderCalendar();
        });
    });

    // Form validation
    document.getElementById('cl-form').addEventListener('submit', function(e) {
        if (!dateFrom) {
            e.preventDefault();
            alert('Seleziona almeno una data dal calendario.');
        }
    });

    // Toggle past closures
    var toggleBtn = document.getElementById('cl-toggle-past');
    var pastList = document.getElementById('cl-past-list');
    if (toggleBtn && pastList) {
        toggleBtn.addEventListener('click', function() {
            var hidden = pastList.style.display === 'none';
            pastList.style.display = hidden ? 'block' : 'none';
            var icon = this.querySelector('.bi-chevron-down, .bi-chevron-up');
            if (icon) icon.className = hidden ? 'bi bi-chevron-up' : 'bi bi-chevron-down';
        });
    }

    // Init
    renderCalendar();
    updateSelection();
})();
</script>