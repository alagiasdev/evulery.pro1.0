<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= e($customer['first_name'] . ' ' . $customer['last_name']) ?></h2>
    <a href="<?= url('dashboard/customers') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Torna alla lista
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Informazioni</h5></div>
            <div class="card-body">
                <p><strong>Email:</strong> <a href="mailto:<?= e($customer['email']) ?>"><?= e($customer['email']) ?></a></p>
                <p><strong>Telefono:</strong> <a href="tel:<?= e($customer['phone']) ?>"><?= e($customer['phone']) ?></a></p>
                <p><strong>Prenotazioni:</strong> <?= (int)$customer['total_bookings'] ?></p>
                <p><strong>No-show:</strong> <?= (int)$customer['total_noshow'] ?></p>
                <p class="mb-0"><strong>Cliente dal:</strong> <?= format_date($customer['created_at']) ?></p>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Storico Prenotazioni</h5></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Orario</th>
                            <th>Persone</th>
                            <th>Stato</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reservations)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Nessuna prenotazione.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($reservations as $r): ?>
                        <tr class="reservation-row" data-url="<?= url("dashboard/reservations/{$r['id']}") ?>">
                            <td><?= format_date($r['reservation_date']) ?></td>
                            <td><?= format_time($r['reservation_time']) ?></td>
                            <td><?= (int)$r['party_size'] ?> pax</td>
                            <td><span class="badge <?= status_badge($r['status']) ?>"><?= status_label($r['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
