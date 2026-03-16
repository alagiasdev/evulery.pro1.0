<?php
$isToday = ($date === date('Y-m-d'));
$DAYS_IT = ['Dom','Lun','Mar','Mer','Gio','Ven','Sab'];

// Meal categorization helper
function categorizeMeal(string $time): string {
    $h = (int)substr($time, 0, 2);
    return ($h < 16) ? 'pranzo' : 'cena';
}

// Group reservations by meal
$byMeal = ['pranzo' => [], 'cena' => []];
$mealCovers = ['pranzo' => 0, 'cena' => 0];
foreach ($reservations as $r) {
    $meal = categorizeMeal($r['reservation_time']);
    $byMeal[$meal][] = $r;
    $mealCovers[$meal] += (int)$r['party_size'];
}
?>

<!-- Date strip -->
<div class="date-strip" id="home-date-strip">
    <a href="#" class="date-chip" data-offset="0">
        <span class="chip-label">Oggi</span>
        <span class="chip-sub" id="home-chip-0"></span>
    </a>
    <a href="#" class="date-chip" data-offset="1">
        <span class="chip-label">Domani</span>
        <span class="chip-sub" id="home-chip-1"></span>
    </a>
    <a href="#" class="date-chip" data-offset="2">
        <span class="chip-label">Dopodomani</span>
        <span class="chip-sub" id="home-chip-2"></span>
    </a>
    <div class="date-chip-cal">
        <a href="#" class="date-chip" id="home-cal-toggle">
            <i class="bi bi-calendar3"></i>
            <span class="chip-label">Altra data</span>
            <span class="chip-sub" id="home-chip-other"></span>
        </a>
        <!-- Mini Calendar Dropdown -->
        <div class="home-cal-dropdown" id="home-cal-dropdown" style="display:none;">
            <div class="dr-cal-header">
                <button type="button" class="dr-cal-nav" id="home-cal-prev"><i class="bi bi-chevron-left"></i></button>
                <span class="dr-cal-month" id="home-cal-month"></span>
                <button type="button" class="dr-cal-nav" id="home-cal-next"><i class="bi bi-chevron-right"></i></button>
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
            <div class="dr-cal-grid" id="home-cal-grid"></div>
        </div>
    </div>
</div>

<!-- Capacity banner -->
<div class="capacity-banner">
    <div class="cap-stats">
        <div class="cap-item">
            <div class="cap-dot" style="background:#0d6efd;"></div>
            <div>
                <div class="cap-num" style="color:#0d6efd;"><?= (int)$stats['covers'] ?></div>
                <div class="cap-label">Coperti</div>
            </div>
        </div>
        <div class="cap-divider"></div>
        <div class="cap-item">
            <div class="cap-dot" style="background:#198754;"></div>
            <div>
                <div class="cap-num" style="color:#198754;"><?= (int)$stats['confirmed'] ?></div>
                <div class="cap-label">Confermate</div>
            </div>
        </div>
        <div class="cap-divider"></div>
        <div class="cap-item">
            <div class="cap-dot" style="background:#ffc107;"></div>
            <div>
                <div class="cap-num" style="color:#ffc107;"><?= (int)$stats['pending'] ?></div>
                <div class="cap-label">In Attesa</div>
            </div>
        </div>
        <div class="cap-divider"></div>
        <div class="cap-item">
            <div class="cap-dot" style="background:#0dcaf0;"></div>
            <div>
                <div class="cap-num" style="color:#0dcaf0;"><?= (int)$stats['total'] ?></div>
                <div class="cap-label">Totale</div>
            </div>
        </div>
    </div>
    <?php if ((int)$stats['covers'] > 0): ?>
    <div class="cap-divider"></div>
    <div class="cap-bar-wrap">
        <div class="cap-bar-header">
            <span class="cap-bar-title"><i class="bi bi-pie-chart me-1"></i>Coperti</span>
            <span class="cap-bar-pct"><?= (int)$stats['covers'] ?> previsti</span>
        </div>
        <div class="cap-bar">
            <div class="cap-bar-fill" style="width: <?= min(100, (int)$stats['covers']) ?>%;"></div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Main grid -->
<div class="dash-grid">

    <!-- Left: Reservations -->
    <div>
        <div class="card">
            <div class="card-header">
                <h6>Prenotazioni <?= $isToday ? 'di oggi' : 'del ' . format_date($date, 'd/m/Y') ?></h6>
                <a href="<?= url('dashboard/reservations?date=' . $date) ?>" class="btn btn-sm btn-brand-outline" style="font-size:.78rem;">
                    Vedi tutte <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

            <?php if (empty($reservations)): ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-calendar-x" style="font-size:2rem;display:block;margin-bottom:.5rem;color:#dee2e6;"></i>
                    Nessuna prenotazione per questa data.
                </div>
            <?php else: ?>
                <?php foreach (['pranzo' => ['bi-sun', 'Pranzo'], 'cena' => ['bi-moon-stars', 'Cena']] as $mealKey => [$mealIcon, $mealName]): ?>
                    <?php if (!empty($byMeal[$mealKey])): ?>
                    <div class="meal-section">
                        <div class="meal-label">
                            <i class="bi <?= $mealIcon ?>"></i> <?= $mealName ?>
                            <span class="meal-count"><?= count($byMeal[$mealKey]) ?> prenotazioni &middot; <?= $mealCovers[$mealKey] ?> coperti</span>
                        </div>
                        <?php foreach ($byMeal[$mealKey] as $r): ?>
                        <div class="res-row" data-url="<?= url("dashboard/reservations/{$r['id']}") ?>">
                            <div class="res-time"><?= format_time($r['reservation_time']) ?></div>
                            <div class="status-dot <?= e($r['status']) ?>" title="<?= status_label($r['status']) ?>"></div>
                            <div class="res-info">
                                <div class="res-name"><?= e($r['first_name'] . ' ' . $r['last_name']) ?></div>
                                <div class="res-meta">
                                    <i class="bi bi-telephone me-1"></i>
                                    <a href="tel:<?= e($r['phone']) ?>" style="color:inherit;text-decoration:none;"><?= e($r['phone']) ?></a>
                                </div>
                            </div>
                            <div class="res-right">
                                <span class="res-pax"><i class="bi bi-people-fill me-1"></i><?= (int)$r['party_size'] ?></span>
                                <i class="bi bi-chevron-right text-muted" style="font-size:.75rem;"></i>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right sidebar -->
    <div class="side-panel">

        <!-- Mini calendar -->
        <div class="card">
            <div class="mini-cal" id="home-mini-cal">
                <div class="cal-nav">
                    <button type="button" id="mini-cal-prev"><i class="bi bi-chevron-left"></i></button>
                    <span id="mini-cal-month"></span>
                    <button type="button" id="mini-cal-next"><i class="bi bi-chevron-right"></i></button>
                </div>
                <div class="cal-grid" id="mini-cal-grid">
                    <div class="cal-day-name">L</div><div class="cal-day-name">M</div><div class="cal-day-name">M</div>
                    <div class="cal-day-name">G</div><div class="cal-day-name">V</div><div class="cal-day-name">S</div><div class="cal-day-name">D</div>
                </div>
            </div>
        </div>

        <!-- Upcoming days -->
        <div class="card">
            <div class="card-header">
                <h6><i class="bi bi-calendar-week me-1"></i> Prossimi giorni</h6>
            </div>
            <div class="upcoming-list">
                <?php if (empty($upcoming)): ?>
                    <div class="text-center text-muted py-3" style="font-size:.85rem;">Nessuna prenotazione in arrivo.</div>
                <?php else: ?>
                    <?php foreach ($upcoming as $u):
                        $uDate = new DateTime($u['reservation_date']);
                        $dayLabel = $DAYS_IT[(int)$uDate->format('w')] . ' ' . $uDate->format('d/m');
                    ?>
                    <a href="<?= url('dashboard?date=' . $u['reservation_date']) ?>" class="upcoming-row">
                        <span class="upcoming-day"><?= $dayLabel ?></span>
                        <div class="upcoming-badges">
                            <span class="badge" style="background:var(--brand);"><?= (int)$u['count'] ?> pren.</span>
                            <span class="badge bg-light text-dark border"><?= (int)$u['covers'] ?> cop.</span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick action -->
        <a href="<?= url('dashboard/reservations/create') ?>" class="quick-action-card">
            <i class="bi bi-plus-circle d-block"></i>
            <span class="qa-label">Nuova Prenotazione</span>
        </a>

    </div>
</div>

<script>
(function() {
    var MONTHS = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
    var DAYS_SHORT = ['Dom','Lun','Mar','Mer','Gio','Ven','Sab'];
    var selectedDate = '<?= e($date) ?>';
    var baseUrl = '<?= url('dashboard') ?>';

    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    function isoDate(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }

    var today = new Date(); today.setHours(0,0,0,0);
    var quickDates = [];
    var matchedQuick = -1;

    // Populate date chips
    for (var i = 0; i <= 2; i++) {
        var d = new Date(today);
        d.setDate(d.getDate() + i);
        quickDates.push(isoDate(d));
        var subEl = document.getElementById('home-chip-' + i);
        if (subEl) subEl.textContent = DAYS_SHORT[d.getDay()] + ' ' + d.getDate() + '/' + (d.getMonth() + 1);
        if (isoDate(d) === selectedDate) matchedQuick = i;
    }

    // Mark active chip
    var chips = document.querySelectorAll('#home-date-strip .date-chip[data-offset]');
    var toggle = document.getElementById('home-cal-toggle');
    var otherSub = document.getElementById('home-chip-other');

    if (matchedQuick >= 0) {
        chips[matchedQuick].classList.add('active');
    } else {
        toggle.classList.add('active');
        if (otherSub) {
            var sel = new Date(selectedDate + 'T00:00:00');
            otherSub.textContent = DAYS_SHORT[sel.getDay()] + ' ' + sel.getDate() + '/' + (sel.getMonth() + 1);
        }
    }

    // Chip click → navigate
    chips.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            window.location = baseUrl + '?date=' + quickDates[parseInt(this.dataset.offset)];
        });
    });

    // === Calendar dropdown for "Altra data" ===
    var dropdown = document.getElementById('home-cal-dropdown');
    var grid = document.getElementById('home-cal-grid');
    var monthLabel = document.getElementById('home-cal-month');
    var selDate = new Date(selectedDate + 'T00:00:00');
    var calMonth = selDate.getMonth();
    var calYear = selDate.getFullYear();

    function renderCal() {
        monthLabel.textContent = MONTHS[calMonth] + ' ' + calYear;
        var first = new Date(calYear, calMonth, 1);
        var startDow = first.getDay() - 1; if (startDow < 0) startDow = 6;
        var days = new Date(calYear, calMonth + 1, 0).getDate();
        var html = '';
        for (var i = 0; i < startDow; i++) html += '<div class="dr-cal-cell dr-cal-empty"></div>';
        for (var d = 1; d <= days; d++) {
            var dt = new Date(calYear, calMonth, d); dt.setHours(0,0,0,0);
            var ds = isoDate(dt);
            var cls = 'dr-cal-cell';
            if (dt.getTime() === today.getTime()) cls += ' dr-cal-today';
            if (ds === selectedDate) cls += ' dr-cal-selected';
            html += '<div class="' + cls + '" data-date="' + ds + '">' + d + '</div>';
        }
        grid.innerHTML = html;
        grid.querySelectorAll('.dr-cal-cell:not(.dr-cal-empty)').forEach(function(cell) {
            cell.addEventListener('click', function() { window.location = baseUrl + '?date=' + this.dataset.date; });
        });
    }

    toggle.addEventListener('click', function(e) {
        e.preventDefault(); e.stopPropagation();
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        if (dropdown.style.display === 'block') renderCal();
    });
    document.getElementById('home-cal-prev').addEventListener('click', function(e) {
        e.stopPropagation(); calMonth--; if (calMonth < 0) { calMonth = 11; calYear--; } renderCal();
    });
    document.getElementById('home-cal-next').addEventListener('click', function(e) {
        e.stopPropagation(); calMonth++; if (calMonth > 11) { calMonth = 0; calYear++; } renderCal();
    });
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#home-cal-dropdown') && !e.target.closest('#home-cal-toggle'))
            dropdown.style.display = 'none';
    });

    // === Mini calendar (sidebar) ===
    var mcGrid = document.getElementById('mini-cal-grid');
    var mcMonth = document.getElementById('mini-cal-month');
    var mcMo = selDate.getMonth(), mcYr = selDate.getFullYear();

    var upcomingDates = {};
    <?php foreach ($upcoming as $u): ?>
    upcomingDates['<?= $u['reservation_date'] ?>'] = true;
    <?php endforeach; ?>

    function renderMiniCal() {
        mcMonth.textContent = MONTHS[mcMo] + ' ' + mcYr;
        var first = new Date(mcYr, mcMo, 1);
        var startDow = first.getDay() - 1; if (startDow < 0) startDow = 6;
        var days = new Date(mcYr, mcMo + 1, 0).getDate();
        var existing = mcGrid.querySelectorAll('.cal-day');
        existing.forEach(function(el) { el.remove(); });
        var html = '';
        for (var i = 0; i < startDow; i++) html += '<div class="cal-day empty">.</div>';
        for (var d = 1; d <= days; d++) {
            var dt = new Date(mcYr, mcMo, d); dt.setHours(0,0,0,0);
            var ds = isoDate(dt);
            var cls = 'cal-day';
            if (dt.getTime() === today.getTime()) cls += ' today';
            if (ds === selectedDate) cls += ' selected';
            if (upcomingDates[ds]) cls += ' has-res';
            html += '<div class="' + cls + '" data-date="' + ds + '">' + d + '</div>';
        }
        mcGrid.insertAdjacentHTML('beforeend', html);
        mcGrid.querySelectorAll('.cal-day:not(.empty)').forEach(function(cell) {
            cell.addEventListener('click', function() { window.location = baseUrl + '?date=' + this.dataset.date; });
        });
    }
    renderMiniCal();

    document.getElementById('mini-cal-prev').addEventListener('click', function() {
        mcMo--; if (mcMo < 0) { mcMo = 11; mcYr--; } renderMiniCal();
    });
    document.getElementById('mini-cal-next').addEventListener('click', function() {
        mcMo++; if (mcMo > 11) { mcMo = 0; mcYr++; } renderMiniCal();
    });
})();
</script>