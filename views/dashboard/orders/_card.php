<?php
// $o = order array (used in kanban column)
$isDelivery = ($o['order_type'] ?? '') === 'delivery';
$minutes = (int)((time() - strtotime($o['created_at'])) / 60);
$isLate = $minutes > 30;
?>
<div class="do-order-card" data-order-id="<?= $o['id'] ?>">
    <div class="do-order-card-header">
        <strong><?= e($o['order_number']) ?></strong>
        <span class="do-order-type <?= $isDelivery ? 'do-order-type--delivery' : '' ?>">
            <i class="bi <?= $isDelivery ? 'bi-truck' : 'bi-bag' ?>"></i>
            <?= order_type_label($o['order_type']) ?>
        </span>
    </div>
    <div class="do-order-card-body">
        <div class="do-order-customer"><i class="bi bi-person me-1"></i> <?= e($o['customer_name']) ?></div>
        <?php if ($o['pickup_time']): ?>
        <div class="do-order-time"><i class="bi bi-clock me-1"></i> <?= date('H:i', strtotime($o['pickup_time'])) ?></div>
        <?php endif; ?>
        <?php if ($isDelivery && $o['delivery_address']): ?>
        <div class="do-order-address"><i class="bi bi-geo-alt me-1"></i> <?= e(mb_substr($o['delivery_address'], 0, 40)) ?></div>
        <?php endif; ?>
        <div class="do-order-total">€ <?= number_format((float)$o['total'], 2, ',', '.') ?></div>
    </div>
    <div class="do-order-card-timer <?= $isLate ? 'do-order-late' : '' ?>">
        <i class="bi bi-stopwatch me-1"></i> <?= $minutes ?> min
    </div>
    <div class="do-order-card-actions">
        <?php $transitions = (new \App\Models\Order())->getValidTransitions($o['status']); ?>
        <?php foreach ($transitions as $next): ?>
            <?php if ($next === 'rejected'): ?>
            <button class="btn btn-sm btn-outline-danger do-status-btn" data-order-id="<?= $o['id'] ?>" data-status="rejected" title="Rifiuta">
                <i class="bi bi-x-circle"></i>
            </button>
            <?php elseif ($next === 'cancelled'): ?>
            <button class="btn btn-sm btn-outline-danger do-status-btn" data-order-id="<?= $o['id'] ?>" data-status="cancelled" title="Annulla">
                <i class="bi bi-x-lg"></i>
            </button>
            <?php else: ?>
            <button class="btn btn-sm btn-success do-status-btn" data-order-id="<?= $o['id'] ?>" data-status="<?= $next ?>">
                <?= order_status_label($next) ?> <i class="bi bi-arrow-right"></i>
            </button>
            <?php endif; ?>
        <?php endforeach; ?>
        <a href="<?= url("dashboard/orders/{$o['id']}") ?>" class="btn btn-sm btn-outline-secondary" title="Dettaglio">
            <i class="bi bi-eye"></i>
        </a>
    </div>
</div>
