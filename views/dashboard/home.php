<?php $isToday = ($date === date('Y-m-d')); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0"><?= $isToday ? 'Oggi' : format_date($date, 'd/m/Y') ?></h2>
    <a href="<?= url('dashboard/reservations/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Nuova Prenotazione
    </a>
</div>

<!-- Quick Date Buttons -->
<div class="dr-date-grid mb-4" id="home-date-grid">
    <a href="#" class="dr-date-btn" data-offset="0">
        <span class="dr-date-label">Oggi</span>
        <span class="dr-date-sub" id="home-date-0"></span>
    </a>
    <a href="#" class="dr-date-btn" data-offset="1">
        <span class="dr-date-label">Domani</span>
        <span class="dr-date-sub" id="home-date-1"></span>
    </a>
    <a href="#" class="dr-date-btn" data-offset="2">
        <span class="dr-date-label">Dopodomani</span>
        <span class="dr-date-sub" id="home-date-2"></span>
    </a>
    <div class="position-relative">
        <a href="#" class="dr-date-btn" id="home-cal-toggle">
            <i class="bi bi-calendar3"></i>
            <span class="dr-date-label">Altra data</span>
            <span class="dr-date-sub" id="home-date-other"></span>
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

<!-- Stats -->
<?php
$dashCards = [
    ['label' => 'Coperti Previsti',    'value' => (int)$stats['covers'],    'color' => '#0d6efd'],
    ['label' => 'Confermate',          'value' => (int)$stats['confirmed'], 'color' => '#198754'],
    ['label' => 'In Attesa',           'value' => (int)$stats['pending'],   'color' => '#ffc107'],
    ['label' => 'Totale Prenotazioni', 'value' => (int)$stats['total'],     'color' => '#0dcaf0'],
];
?>
<div class="row g-3 mb-4">
    <?php foreach ($dashCards as $dc): ?>
    <div class="col-6 col-md-3">
        <div class="card text-center h-100" style="border-top: 3px solid <?= $dc['color'] ?>;">
            <div class="card-body py-2 px-1">
                <div class="fw-bold" style="color: <?= $dc['color'] ?>; font-size: clamp(1.2rem, 4vw, 1.75rem);"><?= $dc['value'] ?></div>
                <div class="text-muted" style="font-size: clamp(0.65rem, 2vw, 0.85rem);"><?= $dc['label'] ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <!-- Reservations List -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Prenotazioni <?= $isToday ? 'di oggi' : 'del ' . format_date($date, 'd/m/Y') ?></h5>
                <a href="<?= url('dashboard/reservations?date=' . $date) ?>" class="btn btn-sm btn-outline-primary">
                    Vedi tutte <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Orario</th>
                            <th>Cliente</th>
                            <th>Persone</th>
                            <th>Stato</th>
                            <th>Telefono</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reservations)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Nessuna prenotazione per questa data.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($reservations as $r): ?>
                        <tr class="reservation-row" data-url="<?= url("dashboard/reservations/{$r['id']}") ?>">
                            <td class="fw-semibold"><?= format_time($r['reservation_time']) ?></td>
                            <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td>
                            <td><?= (int)$r['party_size'] ?> pax</td>
                            <td><span class="badge <?= status_badge($r['status']) ?>"><?= status_label($r['status']) ?></span></td>
                            <td><a href="tel:<?= e($r['phone']) ?>" onclick="event.stopPropagation()"><?= e($r['phone']) ?></a></td>
                            <td class="text-end"><i class="bi bi-chevron-right text-muted"></i></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Upcoming days -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Prossimi 7 giorni</h5></div>
            <div class="card-body">
                <?php if (empty($upcoming)): ?>
                    <p class="text-muted mb-0">Nessuna prenotazione in arrivo.</p>
                <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($upcoming as $u): ?>
                    <li class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <a href="<?= url('dashboard?date=' . $u['reservation_date']) ?>" class="text-decoration-none">
                            <strong><?= format_date($u['reservation_date'], 'D d/m') ?></strong>
                        </a>
                        <div>
                            <span class="badge bg-primary"><?= (int)$u['count'] ?> pren.</span>
                            <span class="badge bg-outline-secondary text-dark"><?= (int)$u['covers'] ?> cop.</span>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
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

    // DOM refs
    var dropdown = document.getElementById('home-cal-dropdown');
    var grid = document.getElementById('home-cal-grid');
    var monthLabel = document.getElementById('home-cal-month');
    var toggle = document.getElementById('home-cal-toggle');

    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    function isoDate(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }

    // === Quick date buttons: populate sub-labels and mark active ===
    var today = new Date(); today.setHours(0,0,0,0);
    var quickDates = [];
    var matchedQuick = -1;

    for (var i = 0; i <= 2; i++) {
        var d = new Date(today);
        d.setDate(d.getDate() + i);
        quickDates.push(isoDate(d));

        // Populate sub-label (e.g. "Ven 6/3")
        var subEl = document.getElementById('home-date-' + i);
        if (subEl) subEl.textContent = DAYS_SHORT[d.getDay()] + ' ' + d.getDate() + '/' + (d.getMonth() + 1);

        if (isoDate(d) === selectedDate) matchedQuick = i;
    }

    // Mark active button
    var quickBtns = document.querySelectorAll('#home-date-grid .dr-date-btn[data-offset]');
    var otherSub = document.getElementById('home-date-other');

    if (matchedQuick >= 0) {
        quickBtns[matchedQuick].classList.add('active');
    } else {
        // "Altra data" is active — show selected date
        toggle.classList.add('active');
        if (otherSub) {
            var sel = new Date(selectedDate + 'T00:00:00');
            otherSub.textContent = DAYS_SHORT[sel.getDay()] + ' ' + sel.getDate() + '/' + (sel.getMonth() + 1);
        }
    }

    // Quick button click → navigate
    quickBtns.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var offset = parseInt(this.dataset.offset);
            window.location = baseUrl + '?date=' + quickDates[offset];
        });
    });

    // === Calendar for "Altra data" ===
    var selDate = new Date(selectedDate + 'T00:00:00');
    var calMonth = selDate.getMonth();
    var calYear = selDate.getFullYear();

    function renderCal() {
        monthLabel.textContent = MONTHS[calMonth] + ' ' + calYear;

        var first = new Date(calYear, calMonth, 1);
        var startDow = first.getDay() - 1;
        if (startDow < 0) startDow = 6;
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
            cell.addEventListener('click', function() {
                window.location = baseUrl + '?date=' + this.dataset.date;
            });
        });
    }

    toggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        if (dropdown.style.display === 'block') renderCal();
    });

    document.getElementById('home-cal-prev').addEventListener('click', function(e) {
        e.stopPropagation();
        calMonth--;
        if (calMonth < 0) { calMonth = 11; calYear--; }
        renderCal();
    });

    document.getElementById('home-cal-next').addEventListener('click', function(e) {
        e.stopPropagation();
        calMonth++;
        if (calMonth > 11) { calMonth = 0; calYear++; }
        renderCal();
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('#home-cal-dropdown') && !e.target.closest('#home-cal-toggle')) {
            dropdown.style.display = 'none';
        }
    });
})();
</script>
