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
                <input type="date" class="form-control" name="date" value="<?= e($date) ?>">
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
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="bi bi-search me-1"></i> Filtra
                </button>
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
                    <td class="text-end"><i class="bi bi-chevron-right text-muted"></i></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
