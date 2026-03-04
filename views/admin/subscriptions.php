<h2 class="mb-4">Abbonamenti</h2>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Ristorante</th>
                    <th>Piano</th>
                    <th>Prezzo</th>
                    <th>Stato</th>
                    <th>Periodo</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subscriptions)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">Nessun abbonamento attivo.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($subscriptions as $s): ?>
                <tr>
                    <td><?= e($s['tenant_name']) ?></td>
                    <td><span class="badge bg-info"><?= e(ucfirst($s['plan'])) ?></span></td>
                    <td>&euro;<?= number_format($s['price'], 2) ?></td>
                    <td>
                        <span class="badge bg-<?= $s['status'] === 'active' ? 'success' : 'warning' ?>">
                            <?= e(ucfirst($s['status'])) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($s['current_period_start']): ?>
                            <?= format_date($s['current_period_start']) ?> - <?= format_date($s['current_period_end']) ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
