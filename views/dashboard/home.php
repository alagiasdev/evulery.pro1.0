<?php
$isToday = ($date === date('Y-m-d'));
$DAYS_IT = ['Dom','Lun','Mar','Mer','Gio','Ven','Sab'];
$MONTHS_IT = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];

// Time-based greeting
$hour = (int)date('H');
if ($hour < 13) $greeting = 'Buongiorno';
elseif ($hour < 18) $greeting = 'Buon pomeriggio';
else $greeting = 'Buonasera';

// Format selected date
$dateObj = new DateTime($date);
$dayName = $DAYS_IT[(int)$dateObj->format('w')];
$formattedDate = $dayName . ' ' . $dateObj->format('d') . ' ' . $MONTHS_IT[(int)$dateObj->format('n') - 1] . ' ' . $dateObj->format('Y');

// Meal categorization
$byMeal = ['pranzo' => [], 'cena' => []];
$mealCovers = ['pranzo' => 0, 'cena' => 0];
foreach ($reservations as $r) {
    $meal = ((int)substr($r['reservation_time'], 0, 2) < 16) ? 'pranzo' : 'cena';
    $byMeal[$meal][] = $r;
    $mealCovers[$meal] += (int)$r['party_size'];
}

// Trend calculations
$coversDiff = (int)$stats['covers'] - (int)$lastWeekStats['covers'];
$confirmedDiff = (int)$stats['confirmed'] - (int)$lastWeekStats['confirmed'];

// Source labels & colors
$sourceLabels = ['widget' => 'Widget online', 'dashboard' => 'Dashboard', 'phone' => 'Telefono', 'walkin' => 'Walk-in'];
$sourceColors = ['widget' => 'var(--brand)', 'dashboard' => '#6f42c1', 'phone' => '#0d6efd', 'walkin' => '#fd7e14'];
?>

<!-- Header with greeting -->
<div class="dh-header">
    <div class="dh-greeting">
        <h1><?= e($greeting) ?>, <?= e($userName) ?></h1>
        <p><?= e($tenantName) ?> &middot; <?= $formattedDate ?></p>
    </div>
    <div class="dh-actions">
        <a href="<?= url('dashboard/reservations/create') ?>" class="btn btn-brand-solid">
            <i class="bi bi-plus-lg"></i> Nuova Prenotazione
        </a>
    </div>
</div>

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
        <div class="home-cal-dropdown" id="home-cal-dropdown" style="display:none;">
            <div class="dr-cal-header">
                <button type="button" class="dr-cal-nav" id="home-cal-prev"><i class="bi bi-chevron-left"></i></button>
                <span class="dr-cal-month" id="home-cal-month"></span>
                <button type="button" class="dr-cal-nav" id="home-cal-next"><i class="bi bi-chevron-right"></i></button>
            </div>
            <div class="dr-cal-days-header">
                <div class="dr-cal-day-name">lun</div><div class="dr-cal-day-name">mar</div><div class="dr-cal-day-name">mer</div>
                <div class="dr-cal-day-name">gio</div><div class="dr-cal-day-name">ven</div><div class="dr-cal-day-name">sab</div>
                <div class="dr-cal-day-name">dom</div>
            </div>
            <div class="dr-cal-grid" id="home-cal-grid"></div>
        </div>
    </div>
</div>

<!-- Stat cards -->
<div class="dh-stat-cards">
    <div class="dh-stat-card">
        <div class="dh-stat-icon blue"><i class="bi bi-people-fill"></i></div>
        <div>
            <div class="dh-stat-value"><?= (int)$stats['covers'] ?></div>
            <div class="dh-stat-label">Coperti totali</div>
            <?php if ($coversDiff !== 0): ?>
            <div class="dh-stat-trend <?= $coversDiff > 0 ? 'up' : 'down' ?>">
                <i class="bi bi-arrow-<?= $coversDiff > 0 ? 'up' : 'down' ?>"></i>
                <?= ($coversDiff > 0 ? '+' : '') . $coversDiff ?> vs sett. scorsa
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="dh-stat-card">
        <div class="dh-stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
        <div>
            <div class="dh-stat-value"><?= (int)$stats['confirmed'] ?></div>
            <div class="dh-stat-label">Confermate</div>
            <?php if ($confirmedDiff !== 0): ?>
            <div class="dh-stat-trend <?= $confirmedDiff > 0 ? 'up' : 'down' ?>">
                <i class="bi bi-arrow-<?= $confirmedDiff > 0 ? 'up' : 'down' ?>"></i>
                <?= ($confirmedDiff > 0 ? '+' : '') . $confirmedDiff ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="dh-stat-card">
        <div class="dh-stat-icon orange"><i class="bi bi-clock-fill"></i></div>
        <div>
            <div class="dh-stat-value"><?= (int)$stats['pending'] ?></div>
            <div class="dh-stat-label">In attesa</div>
        </div>
    </div>
    <div class="dh-stat-card">
        <div class="dh-stat-icon cyan"><i class="bi bi-person-check-fill"></i></div>
        <div>
            <div class="dh-stat-value"><?= (int)$stats['arrived'] ?></div>
            <div class="dh-stat-label">Arrivati</div>
        </div>
    </div>
    <div class="dh-stat-card">
        <div class="dh-stat-icon red"><i class="bi bi-person-x-fill"></i></div>
        <div>
            <div class="dh-stat-value"><?= (int)$stats['noshow'] ?></div>
            <div class="dh-stat-label">No-show</div>
        </div>
    </div>
</div>

<!-- Main grid -->
<div class="dash-grid">

    <!-- LEFT COLUMN -->
    <div>

        <!-- Prossimi in arrivo -->
        <?php if ($isToday && !empty($nextArrivals)): ?>
        <div class="card">
            <div class="card-header">
                <h6><i class="bi bi-clock-history me-1"></i> Prossimi in arrivo</h6>
                <span style="font-size:.72rem;color:var(--gray-600);">Ordine cronologico</span>
            </div>
            <div class="dh-next-list">
                <?php foreach ($nextArrivals as $arr):
                    $arrHour = (int)substr($arr['reservation_time'], 0, 2);
                    $isEvening = ($arrHour >= 18);
                ?>
                <a href="<?= url("dashboard/reservations/{$arr['id']}") ?>" class="dh-next-item<?= $isEvening ? ' dh-next-evening' : '' ?>">
                    <div class="dh-next-time"><?= format_time($arr['reservation_time']) ?></div>
                    <div class="dh-next-info">
                        <div class="dh-next-name"><?= e($arr['first_name'] . ' ' . $arr['last_name']) ?></div>
                        <div class="dh-next-meta"><i class="bi bi-telephone me-1"></i><?= e($arr['phone']) ?></div>
                    </div>
                    <div class="dh-next-pax"><i class="bi bi-people-fill"></i> <?= (int)$arr['party_size'] ?></div>
                    <div class="dh-next-countdown" data-time="<?= e($arr['reservation_time']) ?>">
                        <?php if ($isEvening): ?>
                            <i class="bi bi-moon me-1"></i>stasera
                        <?php else: ?>
                            <i class="bi bi-hourglass-split me-1"></i>...
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Riepilogo servizi (Pranzo/Cena con capienza) -->
        <div class="card">
            <div class="card-header">
                <h6><i class="bi bi-bar-chart me-1"></i> Riepilogo servizi</h6>
                <a href="<?= url('dashboard/reservations?date=' . $date) ?>" class="btn btn-sm btn-brand-outline" style="font-size:.78rem;">
                    Vedi tutte <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="dh-meal-split">
                <?php foreach (['pranzo' => ['bi-sun', 'Pranzo'], 'cena' => ['bi-moon-stars', 'Cena']] as $mealKey => [$mealIcon, $mealName]):
                    $cap = $mealCapacity[$mealKey];
                    $booked = $cap['booked'];
                    $maxCap = $cap['capacity'];
                    $count = $cap['count'];
                    $pct = $maxCap > 0 ? round(($booked / $maxCap) * 100) : 0;
                    $barClass = 'ok';
                    if ($pct > 100) $barClass = 'over';
                    elseif ($pct > 80) $barClass = 'warn';
                    $available = $maxCap - $booked;
                ?>
                <div class="dh-meal-half">
                    <div class="dh-meal-title"><i class="bi <?= $mealIcon ?>"></i> <?= $mealName ?></div>
                    <?php if ($maxCap > 0): ?>
                        <div><span class="dh-meal-big"><?= $booked ?></span> <span class="dh-meal-cap">/ <?= $maxCap ?></span></div>
                        <div class="dh-meal-sub">coperti prenotati &middot; <?= $count ?> prenotazioni</div>
                        <div class="dh-cap-bar"><div class="dh-cap-fill <?= $barClass ?>" style="width:<?= min(100, $pct) ?>%;"></div></div>
                        <div class="dh-cap-info">
                            <?php if ($available >= 0): ?>
                                <span class="dh-cap-text"><strong><?= $available ?></strong> coperti disponibili</span>
                            <?php else: ?>
                                <span class="dh-overbooking-badge"><i class="bi bi-exclamation-triangle-fill"></i> <?= $available ?> overbooking</span>
                            <?php endif; ?>
                            <span class="dh-cap-text<?= $pct > 100 ? ' dh-cap-over' : '' ?>"><?= $pct ?>%</span>
                        </div>
                    <?php else: ?>
                        <div class="dh-meal-sub" style="margin-top:8px;">Nessuno slot configurato</div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Confronto settimana -->
        <?php
        $thisCovers = (int)$stats['covers'];
        $lastCovers = (int)$lastWeekStats['covers'];
        $maxBar = max($thisCovers, $lastCovers, 1);
        $weekDiff = $lastCovers > 0 ? round((($thisCovers - $lastCovers) / $lastCovers) * 100) : ($thisCovers > 0 ? 100 : 0);
        ?>
        <div class="card">
            <div class="card-header">
                <h6><i class="bi bi-graph-up me-1"></i> Confronto settimana</h6>
                <span style="font-size:.72rem;color:var(--gray-600);">vs <?= strtolower($DAYS_IT[(int)date('w', strtotime($lastWeekDate))]) ?> scorso</span>
            </div>
            <div class="dh-week-compare">
                <div class="dh-wc-row">
                    <div class="dh-wc-label">Sett. scorsa</div>
                    <div class="dh-wc-bar-wrap"><div class="dh-wc-bar" style="width:<?= $maxBar > 0 ? round(($lastCovers/$maxBar)*100) : 0 ?>%;background:var(--gray-500);"></div></div>
                    <div class="dh-wc-num"><?= $lastCovers ?></div>
                </div>
                <div class="dh-wc-row">
                    <div class="dh-wc-label">Questa sett.</div>
                    <div class="dh-wc-bar-wrap"><div class="dh-wc-bar" style="width:<?= $maxBar > 0 ? round(($thisCovers/$maxBar)*100) : 0 ?>%;background:var(--brand);"></div></div>
                    <div class="dh-wc-num" style="color:var(--brand);"><?= $thisCovers ?></div>
                </div>
                <?php if ($weekDiff !== 0): ?>
                <div style="text-align:center;margin-top:8px;">
                    <span style="font-size:.85rem;font-weight:700;color:<?= $weekDiff > 0 ? 'var(--brand)' : 'var(--red)' ?>;">
                        <i class="bi bi-arrow-<?= $weekDiff > 0 ? 'up' : 'down' ?>"></i> <?= ($weekDiff > 0 ? '+' : '') . $weekDiff ?>%
                    </span>
                    <span style="font-size:.72rem;color:var(--gray-600);margin-left:6px;">rispetto alla settimana precedente</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- No-show + Fonte (2 colonne) -->
        <div class="dh-two-col">
            <!-- No-show rate -->
            <div class="card">
                <div class="card-header">
                    <h6><i class="bi bi-person-x me-1"></i> No-show rate</h6>
                    <span style="font-size:.68rem;color:var(--gray-600);">ultimi 30 gg</span>
                </div>
                <div class="dh-noshow-wrap">
                    <?php
                    $nsRate = $noshow['rate'];
                    $nsClass = 'ok';
                    if ($nsRate >= 15) $nsClass = 'bad';
                    elseif ($nsRate >= 5) $nsClass = 'warn';
                    ?>
                    <div class="dh-noshow-header">
                        <div class="dh-noshow-pct <?= $nsClass ?>"><?= $nsRate ?>%</div>
                        <div class="dh-noshow-label"><?= $noshow['noshow'] ?> su <?= $noshow['total'] ?> prenotazioni</div>
                    </div>
                    <div class="dh-noshow-bar">
                        <div class="dh-noshow-fill <?= $nsClass ?>" style="width:<?= min(100, $nsRate) ?>%;"></div>
                    </div>
                    <div class="dh-noshow-detail">
                        <i class="bi bi-info-circle me-1"></i>
                        Sotto il 5% = ottimo. Media settore: 15-20%.
                    </div>
                </div>
            </div>

            <!-- Fonte prenotazioni -->
            <div class="card">
                <div class="card-header">
                    <h6><i class="bi bi-pie-chart me-1"></i> Fonte</h6>
                    <span style="font-size:.68rem;color:var(--gray-600);">ultimi 30 gg</span>
                </div>
                <div class="dh-source-list">
                    <?php if (empty($sources['items'])): ?>
                        <div class="text-center text-muted py-3" style="font-size:.85rem;">Nessun dato disponibile.</div>
                    <?php else: ?>
                        <?php foreach ($sources['items'] as $src):
                            $srcLabel = $sourceLabels[$src['source']] ?? ucfirst($src['source']);
                            $srcColor = $sourceColors[$src['source']] ?? 'var(--gray-500)';
                        ?>
                        <div class="dh-source-row">
                            <div class="dh-source-dot" style="background:<?= $srcColor ?>;"></div>
                            <div class="dh-source-name"><?= e($srcLabel) ?></div>
                            <div class="dh-source-bar-wrap"><div class="dh-source-bar" style="width:<?= $src['pct'] ?>%;background:<?= $srcColor ?>;"></div></div>
                            <div class="dh-source-num"><?= $src['count'] ?></div>
                            <div class="dh-source-pct"><?= $src['pct'] ?>%</div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <!-- RIGHT SIDEBAR -->
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
                            <span class="badge" style="background:var(--brand-light);color:var(--brand);"><?= (int)$u['count'] ?> pren.</span>
                            <span class="badge bg-light text-dark border"><?= (int)$u['covers'] ?> cop.</span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick actions -->
        <div class="card">
            <div class="card-header">
                <h6><i class="bi bi-lightning me-1"></i> Azioni rapide</h6>
            </div>
            <div class="dh-qa-grid">
                <a href="<?= url('dashboard/reservations/create') ?>" class="dh-qa-btn">
                    <i class="bi bi-plus-circle" style="color:var(--brand);"></i>
                    Nuova Prenotazione
                </a>
                <a href="<?= url('dashboard/customers') ?>" class="dh-qa-btn">
                    <i class="bi bi-people" style="color:#0d6efd;"></i>
                    Clienti
                </a>
                <a href="<?= url('dashboard/reservations') ?>" class="dh-qa-btn">
                    <i class="bi bi-download" style="color:#fd7e14;"></i>
                    Esporta CSV
                </a>
                <a href="<?= url('dashboard/settings') ?>" class="dh-qa-btn">
                    <i class="bi bi-gear" style="color:var(--gray-600);"></i>
                    Impostazioni
                </a>
            </div>
        </div>

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

    // Chip click
    chips.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            window.location = baseUrl + '?date=' + quickDates[parseInt(this.dataset.offset)];
        });
    });

    // Calendar dropdown
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

    // Mini calendar (sidebar)
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

    // Countdown for "prossimi in arrivo"
    function updateCountdowns() {
        var now = new Date();
        document.querySelectorAll('.dh-next-countdown[data-time]').forEach(function(el) {
            var time = el.dataset.time;
            var h = parseInt(time.substring(0, 2));
            var m = parseInt(time.substring(3, 5));
            if (h >= 18) return; // "stasera" stays static
            var target = new Date(now);
            target.setHours(h, m, 0, 0);
            var diff = Math.floor((target - now) / 60000);
            if (diff <= 0) {
                el.innerHTML = '<i class="bi bi-check-circle me-1"></i>ora';
                el.classList.add('dh-next-now');
            } else if (diff < 60) {
                el.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>tra ' + diff + ' min';
            } else {
                var hours = Math.floor(diff / 60);
                var mins = diff % 60;
                el.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>tra ' + hours + 'h ' + (mins > 0 ? mins + 'm' : '');
            }
        });
    }
    updateCountdowns();
    setInterval(updateCountdowns, 60000);
})();
</script>