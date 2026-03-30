<?php $pageScripts = ['js/dashboard-orders.js']; ?>
<script nonce="<?= csp_nonce() ?>">window.DO_BASE = <?= json_encode(url('')) ?>;</script>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 style="font-size:1.35rem; font-weight:700; margin-bottom:0;">Ordini di oggi</h2>
        <small class="text-muted"><?= date('d/m/Y') ?></small>
    </div>
    <a href="<?= url('dashboard/orders/history') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-clock-history me-1"></i> Storico
    </a>
</div>

<!-- Stats cards -->
<div class="row g-2 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center p-2">
            <div class="do-stat-value" id="doStatTotal"><?= $stats['total_orders'] ?></div>
            <div class="do-stat-label">Ordini totali</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center p-2">
            <div class="do-stat-value" id="doStatRevenue">€ <?= number_format($stats['revenue'], 2, ',', '.') ?></div>
            <div class="do-stat-label">Incasso</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center p-2">
            <div class="do-stat-value" id="doStatTakeaway"><?= $stats['takeaway_count'] ?></div>
            <div class="do-stat-label">Asporto</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center p-2">
            <div class="do-stat-value" id="doStatDelivery"><?= $stats['delivery_count'] ?></div>
            <div class="do-stat-label">Consegna</div>
        </div>
    </div>
</div>

<!-- Kanban board -->
<div class="do-kanban" id="doKanban">
    <?php
    $columns = [
        'pending'   => ['label' => 'Nuovi',           'icon' => 'bi-bell-fill',      'color' => 'warning'],
        'accepted'  => ['label' => 'Accettati',        'icon' => 'bi-check-circle',   'color' => 'info'],
        'preparing' => ['label' => 'In preparazione',  'icon' => 'bi-fire',           'color' => 'primary'],
        'ready'     => ['label' => 'Pronti',           'icon' => 'bi-bag-check-fill', 'color' => 'success'],
    ];
    foreach ($columns as $status => $col):
        $orders = $kanban[$status] ?? [];
    ?>
    <div class="do-kanban-col">
        <div class="do-kanban-header do-kanban-header--<?= $col['color'] ?>">
            <i class="bi <?= $col['icon'] ?> me-1"></i>
            <?= $col['label'] ?>
            <span class="badge bg-white text-dark ms-auto do-count" data-status="<?= $status ?>"><?= count($orders) ?></span>
        </div>
        <div class="do-kanban-cards" data-status="<?= $status ?>">
            <?php foreach ($orders as $o): ?>
            <?php include BASE_PATH . '/views/dashboard/orders/_card.php'; ?>
            <?php endforeach; ?>
            <?php if (empty($orders)): ?>
            <div class="do-kanban-empty">Nessun ordine</div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Completed today (collapsed) -->
<?php if (!empty($completed)): ?>
<div class="mt-4">
    <h6 class="text-muted"><i class="bi bi-check-all me-1"></i> Completati/Chiusi oggi (<?= count($completed) ?>)</h6>
    <div class="table-responsive">
        <table class="table table-sm table-striped align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>Tipo</th>
                    <th>Totale</th>
                    <th>Stato</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($completed as $co): ?>
                <tr>
                    <td><strong><?= e($co['order_number']) ?></strong></td>
                    <td><?= e($co['customer_name']) ?></td>
                    <td><?= order_type_label($co['order_type']) ?></td>
                    <td>€ <?= number_format((float)$co['total'], 2, ',', '.') ?></td>
                    <td><?= order_status_badge($co['status']) ?></td>
                    <td><a href="<?= url("dashboard/orders/{$co['id']}") ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
