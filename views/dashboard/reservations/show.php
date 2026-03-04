<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Prenotazione #<?= (int)$reservation['id'] ?></h2>
    <a href="<?= url('dashboard/reservations?date=' . $reservation['reservation_date']) ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Torna alla lista
    </a>
</div>

<div class="row g-4">
    <!-- Main Info -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?= e($reservation['first_name'] . ' ' . $reservation['last_name']) ?></h5>
                <span class="badge <?= status_badge($reservation['status']) ?> fs-6"><?= status_label($reservation['status']) ?></span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <strong>Data</strong><br>
                        <?= format_date($reservation['reservation_date'], 'd/m/Y') ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Orario</strong><br>
                        <?= format_time($reservation['reservation_time']) ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Persone</strong><br>
                        <?= (int)$reservation['party_size'] ?> pax
                    </div>
                    <div class="col-md-4">
                        <strong>Telefono</strong><br>
                        <a href="tel:<?= e($reservation['phone']) ?>"><?= e($reservation['phone']) ?></a>
                    </div>
                    <div class="col-md-4">
                        <strong>Email</strong><br>
                        <a href="mailto:<?= e($reservation['email']) ?>"><?= e($reservation['email']) ?></a>
                    </div>
                    <div class="col-md-4">
                        <strong>Fonte</strong><br>
                        <?= e(ucfirst($reservation['source'])) ?>
                    </div>
                    <?php if ($reservation['deposit_required']): ?>
                    <div class="col-md-4">
                        <strong>Caparra</strong><br>
                        &euro;<?= number_format($reservation['deposit_amount'], 2) ?>
                        <?= $reservation['deposit_paid'] ? '<span class="badge bg-success">Pagata</span>' : '<span class="badge bg-warning">Non pagata</span>' ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <?php if (!in_array($reservation['status'], ['cancelled', 'noshow', 'arrived'])): ?>
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Azioni</h5></div>
            <div class="card-body d-flex gap-2 flex-wrap">
                <a href="<?= url("dashboard/reservations/{$reservation['id']}/edit") ?>" class="btn btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i> Modifica
                </a>
                <?php if ($reservation['status'] === 'pending'): ?>
                <form method="POST" action="<?= url("dashboard/reservations/{$reservation['id']}/status") ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="status" value="confirmed">
                    <button class="btn btn-success"><i class="bi bi-check-circle me-1"></i> Conferma</button>
                </form>
                <?php endif; ?>

                <?php if (in_array($reservation['status'], ['confirmed', 'pending'])): ?>
                <form method="POST" action="<?= url("dashboard/reservations/{$reservation['id']}/status") ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="status" value="arrived">
                    <button class="btn btn-primary"><i class="bi bi-person-check me-1"></i> Segna Arrivato</button>
                </form>
                <form method="POST" action="<?= url("dashboard/reservations/{$reservation['id']}/status") ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="status" value="noshow">
                    <button class="btn btn-danger"><i class="bi bi-person-x me-1"></i> No-Show</button>
                </form>
                <form method="POST" action="<?= url("dashboard/reservations/{$reservation['id']}/status") ?>" onsubmit="return confirm('Sei sicuro di voler annullare?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="status" value="cancelled">
                    <button class="btn btn-outline-danger"><i class="bi bi-x-circle me-1"></i> Annulla</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Notes -->
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Note Interne</h5></div>
            <div class="card-body">
                <form method="POST" action="<?= url("dashboard/reservations/{$reservation['id']}/notes") ?>">
                    <?= csrf_field() ?>
                    <textarea class="form-control mb-2" name="internal_notes" rows="3"><?= e($reservation['internal_notes'] ?? '') ?></textarea>
                    <button type="submit" class="btn btn-sm btn-outline-primary">Salva note</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Customer Info -->
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0">Storico Cliente</h6></div>
            <div class="card-body">
                <p class="mb-1"><strong>Prenotazioni totali:</strong> <?= (int)$reservation['total_bookings'] ?></p>
                <p class="mb-3"><strong>No-show:</strong> <?= (int)$reservation['total_noshow'] ?></p>

                <?php if (!empty($history)): ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach (array_slice($history, 0, 10) as $h): ?>
                    <li class="mb-1">
                        <small>
                            <?= format_date($h['reservation_date']) ?> -
                            <span class="badge <?= status_badge($h['status']) ?> badge-sm"><?= status_label($h['status']) ?></span>
                            <?= (int)$h['party_size'] ?> pax
                        </small>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Log -->
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Cronologia</h6></div>
            <div class="card-body">
                <?php if (empty($logs)): ?>
                    <p class="text-muted mb-0">Nessuna attivita registrata.</p>
                <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($logs as $log): ?>
                    <li class="mb-2 pb-2 border-bottom">
                        <small class="text-muted"><?= format_date($log['created_at'], 'd/m/Y H:i') ?></small><br>
                        <?php if ($log['old_status']): ?>
                            <?= status_label($log['old_status']) ?> &rarr;
                        <?php endif; ?>
                        <strong><?= status_label($log['new_status']) ?></strong>
                        <?php if ($log['first_name']): ?>
                            <br><small class="text-muted">da <?= e($log['first_name'] . ' ' . $log['last_name']) ?></small>
                        <?php endif; ?>
                        <?php if ($log['note']): ?>
                            <br><small><?= e($log['note']) ?></small>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
