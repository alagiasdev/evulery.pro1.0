<?php
$isDelivery = $order['order_type'] === 'delivery';
$transitions = (new \App\Models\Order())->getValidTransitions($order['status']);
$waNum = preg_replace('/[^0-9]/', '', $order['customer_phone']);
if (str_starts_with($waNum, '0')) $waNum = '39' . substr($waNum, 1);
elseif (!str_starts_with($waNum, '39') && strlen($waNum) <= 10) $waNum = '39' . $waNum;

// Status badge helper (pill, same pattern as reservations)
$statusColors = [
    'pending'   => 'pending',
    'accepted'  => 'accepted',
    'preparing' => 'preparing',
    'ready'     => 'ready',
    'completed' => 'completed',
    'cancelled' => 'cancelled',
    'rejected'  => 'rejected',
];
$statusClass = $statusColors[$order['status']] ?? 'pending';
?>

<!-- Back -->
<div class="page-back">
    <a href="<?= url('dashboard/orders') ?>">
        <i class="bi bi-arrow-left"></i> Torna agli ordini
    </a>
</div>

<!-- Hero card -->
<div class="hero-card">
    <div class="hero-top">
        <div style="display:flex; align-items:center; gap:.5rem; flex-wrap:wrap;">
            <span class="hero-name"><?= e($order['order_number']) ?></span>
            <span class="do-detail-type do-detail-type--<?= $isDelivery ? 'delivery' : 'takeaway' ?>">
                <i class="bi <?= $isDelivery ? 'bi-truck' : 'bi-bag' ?>"></i><?= order_type_label($order['order_type']) ?>
            </span>
            <?php if ($order['payment_status'] === 'paid'): ?>
            <span class="do-detail-pay do-detail-pay--paid"><i class="bi bi-check-circle"></i>Pagato</span>
            <?php elseif ($order['payment_method'] === 'stripe'): ?>
            <span class="do-detail-pay do-detail-pay--stripe"><i class="bi bi-credit-card"></i>Carta</span>
            <?php else: ?>
            <span class="do-detail-pay do-detail-pay--cash"><i class="bi bi-cash"></i>Contanti</span>
            <?php endif; ?>
        </div>
        <span class="do-detail-status do-detail-status--<?= $statusClass ?>"><?= order_status_label($order['status']) ?></span>
    </div>

    <div class="hero-details">
        <div>
            <div class="detail-label"><i class="bi bi-person me-1"></i>Cliente</div>
            <div class="detail-value"><?= e($order['customer_name']) ?></div>
        </div>
        <div>
            <div class="detail-label"><i class="bi bi-telephone me-1"></i>Telefono</div>
            <div class="detail-value"><a href="tel:<?= e($order['customer_phone']) ?>"><?= e($order['customer_phone']) ?></a></div>
        </div>
        <div>
            <div class="detail-label"><i class="bi bi-whatsapp me-1"></i>WhatsApp</div>
            <div class="detail-value"><a href="https://wa.me/<?= e($waNum) ?>" target="_blank" rel="noopener">Chatta</a></div>
        </div>
        <?php if ($order['customer_email']): ?>
        <div>
            <div class="detail-label"><i class="bi bi-envelope me-1"></i>Email</div>
            <div class="detail-value"><a href="mailto:<?= e($order['customer_email']) ?>"><?= e($order['customer_email']) ?></a></div>
        </div>
        <?php endif; ?>
        <div>
            <div class="detail-label"><i class="bi bi-currency-euro me-1"></i>Totale</div>
            <div class="detail-value" style="font-weight:700; color:var(--brand);">&euro; <?= number_format((float)$order['total'], 2, ',', '.') ?></div>
        </div>
        <?php if ($order['pickup_time']): ?>
        <div>
            <div class="detail-label"><i class="bi bi-clock me-1"></i><?= $isDelivery ? 'Consegna' : 'Ritiro' ?></div>
            <div class="detail-value"><?= date('H:i', strtotime($order['pickup_time'])) ?></div>
        </div>
        <?php endif; ?>
        <div>
            <div class="detail-label"><i class="bi bi-calendar3 me-1"></i>Ricevuto</div>
            <div class="detail-value"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Left: order details -->
    <div class="col-lg-7">
        <!-- Articoli -->
        <div class="card section-card">
            <div class="section-header">
                <div class="section-icon" style="background:var(--brand);"><i class="bi bi-receipt"></i></div>
                <div>
                    <div class="section-title">Articoli ordinati</div>
                    <div class="section-subtitle"><?= count($items) ?> <?= count($items) === 1 ? 'piatto' : 'piatti' ?></div>
                </div>
            </div>
            <div style="padding:0;">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th style="padding-left:1.25rem;">Piatto</th>
                            <th class="text-center">Qt&agrave;</th>
                            <th class="text-end">Prezzo</th>
                            <th class="text-end" style="padding-right:1.25rem;">Totale</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $it): ?>
                        <tr>
                            <td style="padding-left:1.25rem;">
                                <?= e($it['item_name']) ?>
                                <?php if ($it['notes']): ?><br><small class="text-muted"><?= e($it['notes']) ?></small><?php endif; ?>
                            </td>
                            <td class="text-center"><?= (int)$it['quantity'] ?></td>
                            <td class="text-end">&euro; <?= number_format((float)$it['unit_price'], 2, ',', '.') ?></td>
                            <td class="text-end" style="padding-right:1.25rem;">&euro; <?= number_format((float)$it['unit_price'] * (int)$it['quantity'], 2, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end fw-semibold">Subtotale</td>
                            <td class="text-end" style="padding-right:1.25rem;">&euro; <?= number_format((float)$order['subtotal'], 2, ',', '.') ?></td>
                        </tr>
                        <?php if ((float)$order['delivery_fee'] > 0): ?>
                        <tr>
                            <td colspan="3" class="text-end">Consegna</td>
                            <td class="text-end" style="padding-right:1.25rem;">&euro; <?= number_format((float)$order['delivery_fee'], 2, ',', '.') ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ((float)$order['discount_amount'] > 0): ?>
                        <tr>
                            <td colspan="3" class="text-end text-success">Sconto</td>
                            <td class="text-end text-success" style="padding-right:1.25rem;">-&euro; <?= number_format((float)$order['discount_amount'], 2, ',', '.') ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr class="table-light">
                            <td colspan="3" class="text-end fw-bold">Totale</td>
                            <td class="text-end fw-bold" style="padding-right:1.25rem; font-size:1.05rem; color:var(--brand);">&euro; <?= number_format((float)$order['total'], 2, ',', '.') ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <?php if ($order['notes']): ?>
        <div class="card section-card mt-3">
            <div class="section-header">
                <div class="section-icon" style="background:#FFC107;"><i class="bi bi-chat-left-text"></i></div>
                <div>
                    <div class="section-title">Note del cliente</div>
                </div>
            </div>
            <div class="form-body">
                <p class="mb-0" style="font-size:.85rem;"><?= nl2br(e($order['notes'])) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($order['rejected_reason']): ?>
        <div class="card section-card mt-3" style="border-left:3px solid #dc3545;">
            <div class="section-header">
                <div class="section-icon" style="background:#dc3545;"><i class="bi bi-exclamation-triangle"></i></div>
                <div>
                    <div class="section-title">Motivo rifiuto</div>
                </div>
            </div>
            <div class="form-body">
                <p class="mb-0" style="font-size:.85rem;"><?= e($order['rejected_reason']) ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right: delivery + timeline + actions -->
    <div class="col-lg-5">
        <?php if ($isDelivery): ?>
        <div class="card section-card mb-3">
            <div class="section-header">
                <div class="section-icon" style="background:#E65100;"><i class="bi bi-geo-alt"></i></div>
                <div>
                    <div class="section-title">Consegna</div>
                    <div class="section-subtitle">Indirizzo e note corriere</div>
                </div>
            </div>
            <div class="form-body">
                <p class="mb-1" style="font-size:.88rem; font-weight:600;"><?= e($order['delivery_address']) ?></p>
                <?php if ($order['delivery_cap']): ?>
                <p class="mb-1" style="font-size:.82rem;"><span class="badge bg-light text-dark">CAP <?= e($order['delivery_cap']) ?></span></p>
                <?php endif; ?>
                <?php if ((float)$order['delivery_fee'] > 0): ?>
                <p class="mb-1" style="font-size:.78rem; color:#6c757d;">Costo consegna: <strong>&euro; <?= number_format((float)$order['delivery_fee'], 2, ',', '.') ?></strong></p>
                <?php endif; ?>
                <?php if ($order['delivery_notes']): ?>
                <div style="margin-top:.5rem; padding:.5rem .75rem; background:#f8f9fa; border-radius:8px; font-size:.78rem; color:#495057;">
                    <i class="bi bi-info-circle me-1"></i> <?= e($order['delivery_notes']) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Timeline -->
        <div class="card section-card mb-3">
            <div class="section-header">
                <div class="section-icon" style="background:#42A5F5;"><i class="bi bi-clock-history"></i></div>
                <div>
                    <div class="section-title">Cronologia</div>
                    <div class="section-subtitle">Passaggi di stato</div>
                </div>
            </div>
            <div class="form-body">
                <?php
                // Build timeline from available data
                $tlColors = [
                    'completed' => '#2E7D32', 'ready' => '#00695C', 'preparing' => '#5E35B1',
                    'accepted' => '#1565C0', 'pending' => '#E65100',
                    'cancelled' => '#757575', 'rejected' => '#C62828',
                ];
                $tlSteps = [
                    'completed' => 'Completato',
                    'ready'     => $isDelivery ? 'Pronto per la consegna' : 'Pronto per il ritiro',
                    'preparing' => 'In preparazione',
                    'accepted'  => 'Accettato',
                    'pending'   => 'Ordine ricevuto',
                    'cancelled' => 'Annullato',
                    'rejected'  => 'Rifiutato',
                ];
                $statusFlow = ['pending', 'accepted', 'preparing', 'ready', 'completed'];
                $cancelledStates = ['cancelled', 'rejected'];

                // Current status position
                $currentIdx = array_search($order['status'], $statusFlow);
                $isCancelled = in_array($order['status'], $cancelledStates);

                // Show current status first (most recent)
                if ($isCancelled):
                ?>
                <div class="do-detail-tl">
                    <div class="do-detail-tl-dot" style="background:<?= $tlColors[$order['status']] ?>;"></div>
                    <div>
                        <div class="do-detail-tl-text"><strong><?= $tlSteps[$order['status']] ?></strong></div>
                        <div class="do-detail-tl-date"><?= date('d/m/Y H:i', strtotime($order['updated_at'])) ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <?php
                // Show completed flow steps (reverse order = most recent first)
                // For cancelled/rejected: infer last normal step from valid transitions
                if ($isCancelled) {
                    // rejected comes from pending (idx 0), cancelled from accepted (1) or preparing (2)
                    $maxIdx = ($order['status'] === 'rejected') ? 0 : 1;
                } else {
                    $maxIdx = $currentIdx;
                }

                for ($i = min($maxIdx, count($statusFlow) - 1); $i >= 0; $i--):
                    $step = $statusFlow[$i];
                    if ($currentIdx !== false && $i <= $currentIdx || $isCancelled && $i <= $maxIdx):
                ?>
                <div class="do-detail-tl">
                    <div class="do-detail-tl-dot" style="background:<?= $tlColors[$step] ?? '#adb5bd' ?>;"></div>
                    <div>
                        <div class="do-detail-tl-text"><strong><?= $tlSteps[$step] ?? ucfirst($step) ?></strong></div>
                        <div class="do-detail-tl-date">
                            <?php if ($step === 'pending'): ?>
                                <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                            <?php elseif ($i === $currentIdx && !$isCancelled): ?>
                                <?= date('d/m/Y H:i', strtotime($order['updated_at'])) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php
                    endif;
                endfor;
                ?>
            </div>
        </div>

        <!-- Actions -->
        <?php if (!empty($transitions)): ?>
        <div class="card section-card">
            <div class="section-header">
                <div class="section-icon" style="background:#1565C0;"><i class="bi bi-lightning"></i></div>
                <div>
                    <div class="section-title">Azioni</div>
                    <div class="section-subtitle">Aggiorna lo stato dell'ordine</div>
                </div>
            </div>
            <div class="form-body d-grid gap-2">
                <?php foreach ($transitions as $next): ?>
                    <?php if ($next === 'rejected'): ?>
                    <form method="POST" action="<?= url("dashboard/orders/{$order['id']}/status") ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="status" value="rejected">
                        <div class="mb-2">
                            <input type="text" name="rejected_reason" class="form-control form-control-sm" placeholder="Motivo rifiuto (opzionale)">
                        </div>
                        <button type="submit" class="btn btn-danger w-100"><i class="bi bi-x-circle me-1"></i> Rifiuta ordine</button>
                    </form>
                    <?php elseif ($next === 'cancelled'): ?>
                    <form method="POST" action="<?= url("dashboard/orders/{$order['id']}/status") ?>" data-confirm="Annullare questo ordine?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="status" value="cancelled">
                        <button type="submit" class="btn btn-outline-danger w-100"><i class="bi bi-x-lg me-1"></i> Annulla ordine</button>
                    </form>
                    <?php else: ?>
                    <form method="POST" action="<?= url("dashboard/orders/{$order['id']}/status") ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="status" value="<?= $next ?>">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-arrow-right me-1"></i> <?= order_status_label($next) ?>
                        </button>
                    </form>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
