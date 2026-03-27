<?php
// Segment badge helper (self-contained, reads tenant thresholds internally)
function getSegmentBadge(int $bookings): string {
    static $th = null;
    if ($th === null) {
        $t = tenant();
        $th = [
            'occ' => (int)($t['segment_occasionale'] ?? 2),
            'abi' => (int)($t['segment_abituale'] ?? 4),
            'vip' => (int)($t['segment_vip'] ?? 10),
        ];
    }
    if ($bookings >= $th['vip']) return '<span class="seg-badge-sm vip">VIP</span>';
    if ($bookings >= $th['abi']) return '<span class="seg-badge-sm abituale">Abituale</span>';
    if ($bookings >= $th['occ']) return '<span class="seg-badge-sm occasionale">Occasionale</span>';
    return '';
}

// Compute stats from reservations
$totalCount = count($reservations);
$confirmedCount = 0;
$pendingCount = 0;
$cancelledCount = 0;
$totalCovers = 0;
$pranzo = [];
$cena = [];

foreach ($reservations as $r) {
    $totalCovers += (int)$r['party_size'];
    if ($r['status'] === 'confirmed') $confirmedCount++;
    if ($r['status'] === 'pending') $pendingCount++;
    if ($r['status'] === 'cancelled') $cancelledCount++;

    $hour = (int)substr($r['reservation_time'], 0, 2);
    if ($hour < 16) {
        $pranzo[] = $r;
    } else {
        $cena[] = $r;
    }
}

$pranzoCovers = array_sum(array_map(fn($r) => (int)$r['party_size'], $pranzo));
$cenaCovers = array_sum(array_map(fn($r) => (int)$r['party_size'], $cena));

// Date chips: today, tomorrow, day after
$today = date('Y-m-d');
$DAYS_IT = ['Dom','Lun','Mar','Mer','Gio','Ven','Sab'];

$chipDates = [];
for ($i = 0; $i < 3; $i++) {
    $d = date('Y-m-d', strtotime("+{$i} days"));
    $dt = new DateTime($d);
    $dayName = $DAYS_IT[(int)$dt->format('w')];
    $label = $i === 0 ? 'Oggi' : ($i === 1 ? 'Domani' : 'Dopodomani');
    $chipDates[] = ['date' => $d, 'label' => $label, 'sub' => $dayName . ' ' . $dt->format('j/n')];
}
?>

<!-- Search bar (global) -->
<form method="GET" action="<?= url('dashboard/reservations') ?>" class="gs-form" id="global-search-form">
    <div class="gs-bar">
        <i class="bi bi-search gs-icon"></i>
        <input type="text" name="q" class="gs-input" placeholder="Cerca per nome, telefono o email..." value="<?= e($searchQuery) ?>" autocomplete="off">
        <?php if ($searchQuery !== ''): ?>
            <a href="<?= url('dashboard/reservations') ?>" class="gs-clear" title="Cancella ricerca"><i class="bi bi-x-lg"></i></a>
        <?php endif; ?>
    </div>
</form>

<?php if ($searchResults !== null): ?>
<!-- Search results -->
<div class="card" style="margin-bottom:1.25rem;">
    <div class="card-header">
        <h6><i class="bi bi-search me-1"></i> Risultati per "<?= e($searchQuery) ?>"</h6>
        <span style="font-size:.78rem;color:#6c757d;"><?= count($searchResults) ?> trovate</span>
    </div>
    <?php if (empty($searchResults)): ?>
        <div class="text-center text-muted py-4">
            <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:.5rem;color:#dee2e6;"></i>
            Nessuna prenotazione trovata per "<?= e($searchQuery) ?>".
        </div>
    <?php else: ?>
        <?php foreach ($searchResults as $sr): ?>
        <div class="res-row" data-url="<?= url("dashboard/reservations/{$sr['id']}") ?>">
            <div class="res-time"><?= format_date($sr['reservation_date'], 'd/m') ?></div>
            <div class="res-time" style="color:#6c757d;"><?= format_time($sr['reservation_time']) ?></div>
            <div class="status-dot <?= e($sr['status']) ?>" title="<?= status_label($sr['status']) ?>"></div>
            <div class="res-info">
                <div class="res-name"><?= e($sr['first_name'] . ' ' . $sr['last_name']) ?> <?= getSegmentBadge((int)$sr['total_bookings']) ?> <span class="res-id">#<?= (int)$sr['id'] ?></span></div>
                <div class="res-meta">
                    <i class="bi bi-telephone me-1"></i><?= e($sr['phone']) ?>
                    <?php if ($sr['email']): ?> &middot; <?= e($sr['email']) ?><?php endif; ?>
                </div>
            </div>
            <div class="res-right">
                <span class="res-pax"><i class="bi bi-people-fill me-1"></i><?= (int)$sr['party_size'] ?></span>
                <span class="res-status-label <?= e($sr['status']) ?>"><?= status_label($sr['status']) ?></span>
                <i class="bi bi-chevron-right text-muted" style="font-size:.75rem;"></i>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php else: ?>

<!-- Filter bar -->
<form method="GET" action="<?= url('dashboard/reservations') ?>" id="res-filter-form">
<div class="filter-bar">
    <!-- Row 1: Quick date chips + actions -->
    <div class="filter-row">
        <div class="date-chips">
            <?php foreach ($chipDates as $chip): ?>
            <a href="<?= url('dashboard/reservations?date=' . $chip['date'] . ($status ? '&status=' . e($status) : '') . ($source ? '&source=' . e($source) : '')) ?>"
               class="date-chip-sm <?= $date === $chip['date'] && !$isRange ? 'active' : '' ?>">
                <?= $chip['label'] ?> <span class="chip-day"><?= $chip['sub'] ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="date-chip-cal">
            <a href="#" class="date-chip-sm" id="res-cal-toggle"><i class="bi bi-calendar3"></i></a>
            <div class="home-cal-dropdown" id="res-cal-dropdown" style="display:none;">
                <div class="dr-cal-header">
                    <button type="button" class="dr-cal-nav" id="res-cal-prev"><i class="bi bi-chevron-left"></i></button>
                    <span class="dr-cal-month" id="res-cal-month"></span>
                    <button type="button" class="dr-cal-nav" id="res-cal-next"><i class="bi bi-chevron-right"></i></button>
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
                <div class="dr-cal-grid" id="res-cal-grid"></div>
            </div>
        </div>
        <div class="filter-actions">
            <?php if (tenant_can('export_csv')): ?>
            <button type="button" class="btn-filter btn-filter-outline" id="export-toggle" title="Esporta CSV">
                <i class="bi bi-download me-1"></i>CSV
            </button>
            <?php else: ?>
            <button type="button" class="btn-filter btn-filter-outline" disabled title="Esportazione CSV non inclusa nel tuo piano" style="opacity:.5;cursor:not-allowed;">
                <i class="bi bi-download me-1"></i>CSV <i class="bi bi-lock-fill" style="font-size:.65rem;"></i>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mobile: Filtri + CSV row -->
    <div class="filter-toolbar-mobile d-md-none">
        <button type="button" class="btn-filter btn-filter-outline" id="filter-toggle-btn">
            <i class="bi bi-funnel me-1"></i>Filtri<?php if ($status || $source): ?> <span class="filter-active-dot"></span><?php endif; ?>
        </button>
        <?php if (tenant_can('export_csv')): ?>
        <button type="button" class="btn-filter btn-filter-outline" id="export-toggle-mobile" title="Esporta CSV">
            <i class="bi bi-download me-1"></i>CSV
        </button>
        <?php else: ?>
        <button type="button" class="btn-filter btn-filter-outline" disabled style="opacity:.5;cursor:not-allowed;">
            <i class="bi bi-download me-1"></i>CSV <i class="bi bi-lock-fill" style="font-size:.65rem;"></i>
        </button>
        <?php endif; ?>
    </div>

    <!-- Row 2: Advanced filters -->
    <div class="filter-row filter-advanced<?= ($status || $source) ? ' show' : '' ?>" id="filter-advanced">
        <div class="filter-group">
            <label>Da</label>
            <input type="date" class="filter-input" name="date" value="<?= e($date) ?>" id="filter-date-from" style="width:auto;">
        </div>
        <div class="filter-group">
            <label>A</label>
            <input type="date" class="filter-input" name="date_to" value="<?= e($dateTo ?? $date) ?>" id="filter-date-to" style="width:auto;">
        </div>
        <div class="filter-divider"></div>
        <div class="filter-group">
            <label>Stato</label>
            <select class="filter-input" name="status" data-autosubmit>
                <option value="">Tutti</option>
                <option value="confirmed" <?= $status === 'confirmed' ? 'selected' : '' ?>>Confermate</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>In attesa</option>
                <option value="arrived" <?= $status === 'arrived' ? 'selected' : '' ?>>Arrivati</option>
                <option value="noshow" <?= $status === 'noshow' ? 'selected' : '' ?>>No-show</option>
                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Annullate</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Fonte</label>
            <select class="filter-input" name="source" data-autosubmit>
                <option value="">Tutte</option>
                <option value="widget" <?= ($source ?? '') === 'widget' ? 'selected' : '' ?>>Widget</option>
                <option value="phone" <?= ($source ?? '') === 'phone' ? 'selected' : '' ?>>Telefono</option>
                <option value="walkin" <?= ($source ?? '') === 'walkin' ? 'selected' : '' ?>>Walk-in</option>
                <option value="altro" <?= ($source ?? '') === 'altro' ? 'selected' : '' ?>>Altro</option>
            </select>
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn-filter btn-filter-primary"><i class="bi bi-search me-1"></i>Filtra</button>
            <a href="<?= url('dashboard/reservations') ?>" class="btn-filter btn-filter-reset"><i class="bi bi-x-lg"></i></a>
        </div>
    </div>
</div>
</form>

<!-- Export panel -->
<div class="export-panel" id="export-panel" style="display:none;">
    <div class="export-panel-inner">
        <div class="export-panel-title"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Esporta Prenotazioni</div>
        <div class="export-panel-form">
            <div class="export-field">
                <label class="export-label">Da</label>
                <input type="date" class="export-input" id="export-date-from" value="<?= e(date('Y-m-01')) ?>">
            </div>
            <div class="export-field">
                <label class="export-label">A</label>
                <input type="date" class="export-input" id="export-date-to" value="<?= e(date('Y-m-t')) ?>">
            </div>
            <div class="export-field">
                <label class="export-label">Stato</label>
                <select class="export-input" id="export-status">
                    <option value="">Tutti</option>
                    <option value="confirmed">Confermate</option>
                    <option value="arrived">Arrivati</option>
                    <option value="noshow">No-show</option>
                    <option value="cancelled">Annullate</option>
                    <option value="pending">In attesa</option>
                </select>
            </div>
            <div class="export-field export-field-actions">
                <a href="#" class="btn-filter btn-filter-primary" id="export-download"><i class="bi bi-download me-1"></i>Scarica CSV</a>
                <button type="button" class="btn-filter btn-filter-reset" id="export-close"><i class="bi bi-x-lg"></i></button>
            </div>
        </div>
        <div class="export-shortcuts">
            <button type="button" class="export-shortcut" data-period="month">Mese corrente</button>
            <button type="button" class="export-shortcut" data-period="last-month">Mese scorso</button>
            <button type="button" class="export-shortcut" data-period="week">Questa settimana</button>
            <button type="button" class="export-shortcut" data-period="year">Anno corrente</button>
        </div>
    </div>
</div>

<!-- Mini stats -->
<div class="stats-mini">
    <div class="stat-pill">
        <div class="sp-dot" style="background:#0dcaf0;"></div>
        <span class="sp-num" style="color:#0dcaf0;"><?= $totalCount ?></span>
        <span class="sp-label">Totale</span>
    </div>
    <div class="stat-pill">
        <div class="sp-dot" style="background:#198754;"></div>
        <span class="sp-num" style="color:#198754;"><?= $confirmedCount ?></span>
        <span class="sp-label">Confermate</span>
    </div>
    <div class="stat-pill">
        <div class="sp-dot" style="background:#ffc107;"></div>
        <span class="sp-num" style="color:#ffc107;"><?= $pendingCount ?></span>
        <span class="sp-label">In Attesa</span>
    </div>
    <div class="stat-pill">
        <div class="sp-dot" style="background:#dc3545;"></div>
        <span class="sp-num" style="color:#dc3545;"><?= $cancelledCount ?></span>
        <span class="sp-label">Annullate</span>
    </div>
    <div class="stat-pill">
        <div class="sp-dot" style="background:#0d6efd;"></div>
        <span class="sp-num" style="color:#0d6efd;"><?= $totalCovers ?></span>
        <span class="sp-label">Coperti</span>
    </div>
</div>

<!-- Reservation list -->
<div class="card">
    <?php if (empty($reservations)): ?>
    <div class="empty-state">
        <i class="bi bi-calendar-x"></i>
        <p>Nessuna prenotazione per <?= format_date($date, 'd/m/Y') ?></p>
    </div>
    <?php else: ?>

    <?php
    // Build redirect back URL preserving filters
    $redirectParams = 'date=' . e($date);
    if (!empty($dateTo) && $dateTo !== $date) $redirectParams .= '&date_to=' . e($dateTo);
    if ($status) $redirectParams .= '&status=' . e($status);
    if (!empty($source)) $redirectParams .= '&source=' . e($source);
    $redirectBack = 'dashboard/reservations?' . $redirectParams;

    // Render a single reservation row
    function renderResRow($r, $redirectBack, $showDate = false) {
        ?>
        <div class="res-row <?= $r['status'] === 'pending' ? 'is-pending' : '' ?>"
             data-url="<?= url("dashboard/reservations/{$r['id']}") ?>">
            <?php if ($showDate): ?>
            <span class="res-time"><?= format_date($r['reservation_date'], 'd/m') ?></span>
            <?php endif; ?>
            <span class="res-time"><?= format_time($r['reservation_time']) ?></span>
            <span class="status-dot <?= e($r['status']) ?>"></span>
            <div class="res-info">
                <div class="res-name"><?= e($r['first_name'] . ' ' . $r['last_name']) ?> <?= getSegmentBadge((int)($r['total_bookings'] ?? 0)) ?> <span class="res-id">#<?= (int)$r['id'] ?></span></div>
                <div class="res-contact">
                    <i class="bi bi-telephone me-1"></i><?= e($r['phone']) ?>
                    &nbsp;&middot;&nbsp;
                    <i class="bi bi-envelope me-1"></i><?= e($r['email']) ?>
                </div>
            </div>
            <div class="res-right">
                <span class="res-pax"><i class="bi bi-person-fill me-1"></i><?= (int)$r['party_size'] ?></span>
                <?php if (!empty($r['discount_percent'])): ?>
                <span style="background:#FFF3E0;color:#E65100;font-size:.65rem;font-weight:700;padding:1px 5px;border-radius:4px;">-<?= (int)$r['discount_percent'] ?>%</span>
                <?php endif; ?>
                <?php if ($r['status'] === 'pending'): ?>
                <form method="POST" action="<?= url("dashboard/reservations/{$r['id']}/status") ?>" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="status" value="confirmed">
                    <input type="hidden" name="redirect_back" value="<?= e($redirectBack) ?>">
                    <button type="submit" class="btn-action-sm btn-confirm" title="Conferma">
                        <i class="bi bi-check-circle"></i>
                    </button>
                </form>
                <?php endif; ?>
                <?php if (in_array($r['status'], ['confirmed', 'pending'])): ?>
                <form method="POST" action="<?= url("dashboard/reservations/{$r['id']}/status") ?>" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="status" value="arrived">
                    <input type="hidden" name="redirect_back" value="<?= e($redirectBack) ?>">
                    <button type="submit" class="btn-action-sm btn-arrived" title="Segna Arrivato">
                        <i class="bi bi-person-check"></i>
                    </button>
                </form>
                <?php endif; ?>
                <i class="bi bi-chevron-right" style="color:#d0d0d0;font-size:.7rem;"></i>
            </div>
        </div>
        <?php
    }

    if ($isRange):
        // Range mode: group by date
        $byDate = [];
        foreach ($reservations as $r) {
            $byDate[$r['reservation_date']][] = $r;
        }
        foreach ($byDate as $d => $rows):
            $dayCovers = array_sum(array_map(fn($r) => (int)$r['party_size'], $rows));
        ?>
        <div class="meal-divider">
            <i class="bi bi-calendar3"></i>
            <span><?= format_date($d, 'l d/m/Y') ?></span>
            <span class="meal-count"><?= count($rows) ?> pren. &middot; <?= $dayCovers ?> coperti</span>
        </div>
        <?php foreach ($rows as $r) { renderResRow($r, $redirectBack, false); } ?>
        <?php endforeach;
    else:
        // Single date mode: group by pranzo/cena
        function renderMealGroup($label, $icon, $rows, $covers, $redirectBack) {
            if (empty($rows)) return;
            ?>
            <div class="meal-divider">
                <i class="bi bi-<?= $icon ?>"></i>
                <span><?= $label ?></span>
                <span class="meal-count"><?= count($rows) ?> prenotazion<?= count($rows) === 1 ? 'e' : 'i' ?> &middot; <?= $covers ?> coperti</span>
            </div>
            <?php foreach ($rows as $r) { renderResRow($r, $redirectBack, false); }
        }

        renderMealGroup('Pranzo', 'sun', $pranzo, $pranzoCovers, $redirectBack);
        renderMealGroup('Cena', 'moon-stars', $cena, $cenaCovers, $redirectBack);
    endif;
    ?>

    <!-- Pagination info -->
    <div class="pagination-bar">
        <span class="pagination-info"><?= $totalCount ?> prenotazion<?= $totalCount === 1 ? 'e' : 'i' ?>
        <?php if ($isRange): ?>
            &middot; <?= format_date($date, 'd/m/Y') ?> &ndash; <?= format_date($dateTo, 'd/m/Y') ?>
        <?php else: ?>
            &middot; <?= format_date($date, 'd/m/Y') ?>
        <?php endif; ?>
        </span>
    </div>

    <?php endif; ?>
</div>

<?php endif; /* close searchResults === null */ ?>

<script nonce="<?= csp_nonce() ?>">
(function() {
    // Mobile: toggle advanced filters
    var filterBtn = document.getElementById('filter-toggle-btn');
    var filterPanel = document.getElementById('filter-advanced');
    if (filterBtn && filterPanel) {
        filterBtn.addEventListener('click', function() {
            filterPanel.classList.toggle('show');
            this.classList.toggle('active');
        });
    }

    // Skip filter/calendar JS when in search mode
    var toggle = document.getElementById('res-cal-toggle');
    if (!toggle) return;

    var MONTHS = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
    var selectedDate = '<?= e($date) ?>';
    var dropdown = document.getElementById('res-cal-dropdown');
    var grid = document.getElementById('res-cal-grid');
    var monthLabel = document.getElementById('res-cal-month');
    var dateFromInput = document.getElementById('filter-date-from');

    var sel = new Date(selectedDate + 'T00:00:00');
    var calMonth = sel.getMonth();
    var calYear = sel.getFullYear();

    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    function isoDate(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }

    function render() {
        monthLabel.textContent = MONTHS[calMonth] + ' ' + calYear;
        var first = new Date(calYear, calMonth, 1);
        var startDow = first.getDay() - 1;
        if (startDow < 0) startDow = 6;
        var days = new Date(calYear, calMonth + 1, 0).getDate();
        var today = new Date(); today.setHours(0,0,0,0);

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
                selectedDate = this.dataset.date;
                dateFromInput.value = selectedDate;
                document.getElementById('filter-date-to').value = selectedDate;
                document.getElementById('res-filter-form').submit();
            });
        });
    }

    toggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        if (dropdown.style.display === 'block') render();
    });

    document.getElementById('res-cal-prev').addEventListener('click', function(e) {
        e.stopPropagation();
        calMonth--;
        if (calMonth < 0) { calMonth = 11; calYear--; }
        render();
    });

    document.getElementById('res-cal-next').addEventListener('click', function(e) {
        e.stopPropagation();
        calMonth++;
        if (calMonth > 11) { calMonth = 0; calYear++; }
        render();
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('#res-cal-dropdown') && !e.target.closest('#res-cal-toggle')) {
            dropdown.style.display = 'none';
        }
    });
})();

// Export panel logic
(function() {
    var toggleBtn = document.getElementById('export-toggle');
    if (!toggleBtn) return;
    var panel = document.getElementById('export-panel');
    var closeBtn = document.getElementById('export-close');
    var downloadBtn = document.getElementById('export-download');
    var dateFrom = document.getElementById('export-date-from');
    var dateTo = document.getElementById('export-date-to');
    var statusSel = document.getElementById('export-status');
    var baseUrl = '<?= url("dashboard/reservations/export") ?>';

    toggleBtn.addEventListener('click', function() {
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    });
    var toggleMobile = document.getElementById('export-toggle-mobile');
    if (toggleMobile) {
        toggleMobile.addEventListener('click', function() {
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        });
    }
    closeBtn.addEventListener('click', function() { panel.style.display = 'none'; });

    function updateLink() {
        var url = baseUrl + '?date_from=' + dateFrom.value + '&date_to=' + dateTo.value;
        if (statusSel.value) url += '&status=' + statusSel.value;
        downloadBtn.href = url;
    }
    dateFrom.addEventListener('change', updateLink);
    dateTo.addEventListener('change', updateLink);
    statusSel.addEventListener('change', updateLink);
    updateLink();

    // Shortcut buttons
    document.querySelectorAll('.export-shortcut').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var now = new Date();
            var y = now.getFullYear(), m = now.getMonth();
            var from, to;
            switch(this.dataset.period) {
                case 'month':
                    from = new Date(y, m, 1);
                    to = new Date(y, m + 1, 0);
                    break;
                case 'last-month':
                    from = new Date(y, m - 1, 1);
                    to = new Date(y, m, 0);
                    break;
                case 'week':
                    var dow = now.getDay() || 7;
                    from = new Date(y, m, now.getDate() - dow + 1);
                    to = new Date(from); to.setDate(from.getDate() + 6);
                    break;
                case 'year':
                    from = new Date(y, 0, 1);
                    to = new Date(y, 11, 31);
                    break;
            }
            function iso(d) { return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'); }
            dateFrom.value = iso(from);
            dateTo.value = iso(to);
            updateLink();
        });
    });
})();
</script>