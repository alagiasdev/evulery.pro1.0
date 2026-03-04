<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Clienti</h2>
</div>

<!-- Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('dashboard/customers') ?>" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Cerca cliente</label>
                <input type="text" class="form-control" name="q" value="<?= e($search ?? '') ?>" placeholder="Nome, cognome, email o telefono...">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="bi bi-search me-1"></i> Cerca
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Telefono</th>
                    <th>Prenotazioni</th>
                    <th>No-show</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">Nessun cliente trovato.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($customers as $c): ?>
                <tr class="reservation-row" onclick="window.location='<?= url("dashboard/customers/{$c['id']}") ?>'">
                    <td class="fw-semibold"><?= e($c['first_name'] . ' ' . $c['last_name']) ?></td>
                    <td><?= e($c['email']) ?></td>
                    <td><?= e($c['phone']) ?></td>
                    <td><?= (int)$c['total_bookings'] ?></td>
                    <td>
                        <?php if ($c['total_noshow'] > 0): ?>
                            <span class="badge bg-danger"><?= (int)$c['total_noshow'] ?></span>
                        <?php else: ?>
                            0
                        <?php endif; ?>
                    </td>
                    <td class="text-end"><i class="bi bi-chevron-right text-muted"></i></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
