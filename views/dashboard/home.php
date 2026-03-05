<?php $isToday = ($date === date('Y-m-d')); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <h2 class="mb-0"><?= $isToday ? 'Oggi' : format_date($date, 'd/m/Y') ?></h2>
        <form method="GET" action="<?= url('dashboard') ?>" class="d-flex align-items-center gap-2">
            <input type="date" class="form-control form-control-sm" name="date" value="<?= e($date) ?>" onchange="this.form.submit()">
            <?php if (!$isToday): ?>
                <a href="<?= url('dashboard') ?>" class="btn btn-sm btn-outline-secondary">Oggi</a>
            <?php endif; ?>
        </form>
    </div>
    <a href="<?= url('dashboard/reservations/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Nuova Prenotazione
    </a>
</div>

<!-- Stats -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stat-card border-primary">
            <div class="card-body">
                <h6 class="text-muted mb-1">Coperti Previsti</h6>
                <h3 class="mb-0"><?= (int)$stats['covers'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card border-success">
            <div class="card-body">
                <h6 class="text-muted mb-1">Confermate</h6>
                <h3 class="mb-0"><?= (int)$stats['confirmed'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card border-warning">
            <div class="card-body">
                <h6 class="text-muted mb-1">In Attesa</h6>
                <h3 class="mb-0"><?= (int)$stats['pending'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card border-info">
            <div class="card-body">
                <h6 class="text-muted mb-1">Totale Prenotazioni</h6>
                <h3 class="mb-0"><?= (int)$stats['total'] ?></h3>
            </div>
        </div>
    </div>
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
