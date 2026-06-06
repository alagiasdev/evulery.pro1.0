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

        <?php if ($isDelivery && !empty($ridersEnabled)): ?>
        <!--
            Rider assignment: badge se assegnato, pulsante tratteggiato altrimenti.
            Il dropdown viene aperto da JS (do-assign-trigger). Lo dropdown
            elenco rider e' renderizzato una sola volta in fondo a orders/index.php
            e usato/spostato dal JS al click.
        -->
        <div class="do-order-rider">
            <?php if (!empty($o['rider_name'])): ?>
                <button type="button" class="rider-badge-inline do-assign-trigger"
                        style="background:<?= e($o['rider_color_hex'] ?? '#6c757d') ?>;border:0;cursor:pointer;"
                        data-order-id="<?= (int)$o['id'] ?>"
                        data-current-rider="<?= (int)($o['rider_id'] ?? 0) ?>">
                    <span class="dot"></span> <?= e($o['rider_name']) ?>
                </button>
            <?php else: ?>
                <button type="button" class="rider-assign-empty do-assign-trigger"
                        data-order-id="<?= (int)$o['id'] ?>"
                        data-current-rider="0">
                    <i class="bi bi-plus-lg me-1"></i> Assegna rider
                </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <div class="do-order-card-timer <?= $isLate ? 'do-order-late' : '' ?>">
        <i class="bi bi-stopwatch me-1"></i> <?= $minutes ?> min
    </div>
    <div class="do-order-card-actions">
        <?php
            // Stampa contestuale per status:
            //  - accepted / preparing → "Stampa cucina" (ticket sintetico per la brigata)
            //  - ready → "Stampa ordine" (ricevuta completa per il cliente con sezione staccabile)
            $status = $o['status'] ?? '';
            $printType = match ($status) {
                'accepted', 'preparing' => 'kitchen',
                'ready'                  => 'receipt',
                default                  => null,
            };
            if ($printType): ?>
        <a href="<?= url("dashboard/orders/{$o['id']}/print/{$printType}") ?>" target="_blank"
           class="btn btn-sm <?= $printType === 'kitchen' ? 'do-print-btn-kitchen' : 'do-print-btn-receipt' ?>"
           title="<?= $printType === 'kitchen' ? 'Stampa ticket cucina' : 'Stampa ricevuta cliente' ?>">
            <?php if ($printType === 'kitchen'): ?>
                <i class="bi bi-fire"></i> Cucina
            <?php else: ?>
                <i class="bi bi-printer-fill"></i> Stampa
            <?php endif; ?>
        </a>
        <?php endif; ?>
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
