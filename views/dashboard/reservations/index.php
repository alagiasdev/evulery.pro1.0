<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Prenotazioni</h2>
    <a href="<?= url('dashboard/reservations/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Nuova
    </a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('dashboard/reservations') ?>" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Data</label>
                <div class="position-relative">
                    <button type="button" class="form-control text-start" id="res-cal-toggle">
                        <i class="bi bi-calendar3 me-2"></i><?= format_date($date, 'd/m/Y') ?>
                    </button>
                    <input type="hidden" name="date" id="res-date-value" value="<?= e($date) ?>">
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
            </div>
            <div class="col-md-3">
                <label class="form-label">Stato</label>
                <select class="form-select" name="status">
                    <option value="">Tutti</option>
                    <option value="confirmed" <?= $status === 'confirmed' ? 'selected' : '' ?>>Confermate</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>In attesa</option>
                    <option value="arrived" <?= $status === 'arrived' ? 'selected' : '' ?>>Arrivati</option>
                    <option value="noshow" <?= $status === 'noshow' ? 'selected' : '' ?>>No-show</option>
                    <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Annullate</option>
                </select>
            </div>
            <div class="col-md-2">
                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-outline-primary flex-grow-1">
                        <i class="bi bi-search me-1"></i> Filtra
                    </button>
                    <a href="<?= url('dashboard/reservations') ?>" class="btn btn-outline-secondary" title="Reset">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- List -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Orario</th>
                    <th>Cliente</th>
                    <th>Persone</th>
                    <th>Stato</th>
                    <th>Email</th>
                    <th>Telefono</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reservations)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">Nessuna prenotazione trovata.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($reservations as $r): ?>
                <tr class="reservation-row" data-url="<?= url("dashboard/reservations/{$r['id']}") ?>">
                    <td class="fw-semibold"><?= format_time($r['reservation_time']) ?></td>
                    <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td>
                    <td><?= (int)$r['party_size'] ?> pax</td>
                    <td><span class="badge <?= status_badge($r['status']) ?>"><?= status_label($r['status']) ?></span></td>
                    <td><?= e($r['email']) ?></td>
                    <td><?= e($r['phone']) ?></td>
                    <td class="text-end">
                        <?php if (in_array($r['status'], ['confirmed', 'pending'])): ?>
                        <form method="POST" action="<?= url("dashboard/reservations/{$r['id']}/status") ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="arrived">
                            <input type="hidden" name="redirect_back" value="dashboard/reservations?date=<?= e($date) ?><?= $status ? '&status=' . e($status) : '' ?>">
                            <button type="submit" class="btn btn-success btn-sm" title="Segna Arrivato">
                                <i class="bi bi-person-check"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                        <i class="bi bi-chevron-right text-muted ms-1"></i>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function() {
    var MONTHS = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
    var selectedDate = '<?= e($date) ?>';
    var dropdown = document.getElementById('res-cal-dropdown');
    var grid = document.getElementById('res-cal-grid');
    var monthLabel = document.getElementById('res-cal-month');
    var toggle = document.getElementById('res-cal-toggle');
    var hiddenInput = document.getElementById('res-date-value');

    var sel = new Date(selectedDate + 'T00:00:00');
    var calMonth = sel.getMonth();
    var calYear = sel.getFullYear();

    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    function isoDate(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
    function formatLabel(ds) {
        var d = new Date(ds + 'T00:00:00');
        return pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear();
    }

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
                hiddenInput.value = selectedDate;
                toggle.innerHTML = '<i class="bi bi-calendar3 me-2"></i>' + formatLabel(selectedDate);
                dropdown.style.display = 'none';
                hiddenInput.closest('form').submit();
            });
        });
    }

    toggle.addEventListener('click', function(e) {
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
</script>
