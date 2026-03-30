<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 style="font-size:1.35rem; font-weight:700; margin-bottom:0;">Storico Ordini</h2>
        <small class="text-muted"><?= $total ?> ordini trovati</small>
    </div>
    <a href="<?= url('dashboard/orders') ?>" class="btn btn-outline-success btn-sm">
        <i class="bi bi-kanban me-1"></i> Kanban
    </a>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="<?= url('dashboard/orders/history') ?>" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label" style="font-size:.78rem;">Stato</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Tutti</option>
                    <?php foreach (['pending','accepted','preparing','ready','completed','cancelled','rejected'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= order_status_label($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-size:.78rem;">Tipo</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">Tutti</option>
                    <option value="takeaway" <?= ($filters['order_type'] ?? '') === 'takeaway' ? 'selected' : '' ?>>Asporto</option>
                    <option value="delivery" <?= ($filters['order_type'] ?? '') === 'delivery' ? 'selected' : '' ?>>Consegna</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-size:.78rem;">Da</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= e($filters['date_from'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-size:.78rem;">A</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= e($filters['date_to'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-size:.78rem;">Cerca</label>
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Nome, #ordine, tel..." value="<?= e($filters['search'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-success btn-sm w-100"><i class="bi bi-search me-1"></i> Filtra</button>
            </div>
        </form>
    </div>
</div>

<!-- Orders table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-sm table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Data</th>
                    <th>Cliente</th>
                    <th>Tipo</th>
                    <th>Totale</th>
                    <th>Pagamento</th>
                    <th>Stato</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Nessun ordine trovato.</td></tr>
                <?php endif; ?>
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td><strong><?= e($o['order_number']) ?></strong></td>
                    <td><?= date('d/m H:i', strtotime($o['created_at'])) ?></td>
                    <td><?= e($o['customer_name']) ?></td>
                    <td>
                        <i class="bi <?= $o['order_type'] === 'delivery' ? 'bi-truck' : 'bi-bag' ?>"></i>
                        <?= order_type_label($o['order_type']) ?>
                    </td>
                    <td>€ <?= number_format((float)$o['total'], 2, ',', '.') ?></td>
                    <td>
                        <?= $o['payment_method'] === 'stripe' ? '<i class="bi bi-credit-card"></i>' : '<i class="bi bi-cash"></i>' ?>
                        <?php if ($o['payment_status'] === 'paid'): ?>
                        <span class="badge bg-success">Pagato</span>
                        <?php endif; ?>
                    </td>
                    <td><?= order_status_badge($o['status']) ?></td>
                    <td>
                        <a href="<?= url("dashboard/orders/{$o['id']}") ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php
$currentOffset = (int)($filters['offset'] ?? 0);
$limit = (int)($filters['limit'] ?? 30);
$hasMore = ($currentOffset + $limit) < $total;
$hasPrev = $currentOffset > 0;
?>
<?php if ($hasPrev || $hasMore): ?>
<nav class="mt-3 d-flex justify-content-between">
    <?php if ($hasPrev): ?>
    <a href="<?= url('dashboard/orders/history') ?>?offset=<?= max(0, $currentOffset - $limit) ?>&status=<?= e($filters['status'] ?? '') ?>&type=<?= e($filters['order_type'] ?? '') ?>&from=<?= e($filters['date_from'] ?? '') ?>&to=<?= e($filters['date_to'] ?? '') ?>&q=<?= e($filters['search'] ?? '') ?>"
       class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-left"></i> Precedenti</a>
    <?php else: ?><span></span><?php endif; ?>
    <?php if ($hasMore): ?>
    <a href="<?= url('dashboard/orders/history') ?>?offset=<?= $currentOffset + $limit ?>&status=<?= e($filters['status'] ?? '') ?>&type=<?= e($filters['order_type'] ?? '') ?>&from=<?= e($filters['date_from'] ?? '') ?>&to=<?= e($filters['date_to'] ?? '') ?>&q=<?= e($filters['search'] ?? '') ?>"
       class="btn btn-outline-secondary btn-sm">Successivi <i class="bi bi-chevron-right"></i></a>
    <?php endif; ?>
</nav>
<?php endif; ?>
