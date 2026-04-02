<?php
// Segment calculation: nuovo (<2), occasionale (2-3), abituale (4-9), vip (10+)
$bookings = (int)$reservation['total_bookings'];
if ($bookings >= 10) { $segment = 'vip'; $segmentLabel = 'VIP'; }
elseif ($bookings >= 4) { $segment = 'abituale'; $segmentLabel = 'Abituale'; }
elseif ($bookings >= 2) { $segment = 'occasionale'; $segmentLabel = 'Occasionale'; }
else { $segment = 'nuovo'; $segmentLabel = 'Nuovo'; }

// Total covers from history
$totalHistoryCovers = 0;
foreach ($history as $h) {
    $totalHistoryCovers += (int)$h['party_size'];
}

// Delete timer
$minutesSinceCreation = (time() - strtotime($reservation['created_at'])) / 60;
$canDelete = $minutesSinceCreation <= 30;
$remainingMin = max(0, (int)ceil(30 - $minutesSinceCreation));

// Source labels
$sourceLabels = ['phone' => 'Telefono', 'walkin' => 'Walk-in', 'widget' => 'Widget', 'altro' => 'Altro'];
$sourceLabel = $sourceLabels[$reservation['source']] ?? ucfirst($reservation['source']);
?>

<!-- Back -->
<div class="page-back">
    <a href="<?= url('dashboard/reservations?date=' . $reservation['reservation_date']) ?>">
        <i class="bi bi-arrow-left"></i> Torna alle prenotazioni
    </a>
</div>

<!-- Hero card -->
<div class="hero-card">
    <div class="hero-top">
        <div>
            <span class="hero-name"><?= e($reservation['first_name'] . ' ' . $reservation['last_name']) ?></span>
            <span class="hero-id">#<?= (int)$reservation['id'] ?></span>
        </div>
        <span class="status-badge <?= e($reservation['status']) ?>"><?= status_label($reservation['status']) ?></span>
    </div>

    <div class="hero-details">
        <div>
            <div class="detail-label"><i class="bi bi-calendar3 me-1"></i>Data</div>
            <div class="detail-value"><?= format_date($reservation['reservation_date'], 'd/m/Y') ?></div>
        </div>
        <div>
            <div class="detail-label"><i class="bi bi-clock me-1"></i>Orario</div>
            <div class="detail-value"><?= format_time($reservation['reservation_time']) ?></div>
        </div>
        <div>
            <div class="detail-label"><i class="bi bi-people me-1"></i>Persone</div>
            <div class="detail-value"><?= (int)$reservation['party_size'] ?> pax</div>
        </div>
        <div>
            <div class="detail-label"><i class="bi bi-telephone me-1"></i>Telefono</div>
            <div class="detail-value"><a href="tel:<?= e($reservation['phone']) ?>"><?= e($reservation['phone']) ?></a></div>
        </div>
        <div>
            <?php
                $waNum = preg_replace('/[^0-9]/', '', $reservation['phone']);
                if (str_starts_with($waNum, '0')) $waNum = '39' . substr($waNum, 1);
                elseif (!str_starts_with($waNum, '39') && strlen($waNum) <= 10) $waNum = '39' . $waNum;
            ?>
            <div class="detail-label"><i class="bi bi-whatsapp me-1"></i>WhatsApp</div>
            <div class="detail-value"><a href="https://wa.me/<?= e($waNum) ?>" target="_blank" rel="noopener">Inizia a Chattare</a></div>
        </div>
        <div>
            <div class="detail-label"><i class="bi bi-envelope me-1"></i>Email</div>
            <div class="detail-value"><a href="mailto:<?= e($reservation['email']) ?>"><?= e($reservation['email']) ?></a></div>
        </div>
        <div>
            <div class="detail-label"><i class="bi bi-globe me-1"></i>Fonte</div>
            <div class="detail-value"><?= e($sourceLabel) ?></div>
        </div>
        <?php if (!empty($reservation['discount_percent'])): ?>
        <div>
            <div class="detail-label"><i class="bi bi-percent me-1"></i>Promozione</div>
            <div class="detail-value">
                <span style="background:#FFF3E0;color:#E65100;font-size:.75rem;font-weight:700;padding:2px 8px;border-radius:4px;">-<?= (int)$reservation['discount_percent'] ?>% sconto</span>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($reservation['deposit_required']): ?>
        <div>
            <div class="detail-label"><i class="bi bi-credit-card me-1"></i>Caparra</div>
            <div class="detail-value">
                &euro;<?= number_format($reservation['deposit_amount'], 2) ?>
                <?php if ($reservation['deposit_paid']): ?>
                    <span style="background:#E8F5E9;color:#2E7D32;font-size:.7rem;font-weight:600;padding:1px 6px;border-radius:4px;">Pagata</span>
                <?php else: ?>
                    <span style="background:#FFF8E1;color:#E65100;font-size:.7rem;font-weight:600;padding:1px 6px;border-radius:4px;">Non pagata</span>
                <?php endif; ?>
            </div>
        </div>
        <?php
            $depTypeLabels = ['info' => 'Bonifico', 'link' => 'Link esterno', 'stripe' => 'Stripe'];
            $depTypeIcons = ['info' => 'bi-bank', 'link' => 'bi-link-45deg', 'stripe' => 'bi-credit-card'];
            $depType = $tenant['deposit_type'] ?? 'info';
        ?>
        <div>
            <div class="detail-label"><i class="bi <?= $depTypeIcons[$depType] ?? 'bi-wallet2' ?> me-1"></i>Metodo</div>
            <div class="detail-value"><?= $depTypeLabels[$depType] ?? ucfirst($depType) ?></div>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($reservation['customer_notes'])): ?>
    <div class="customer-note">
        <i class="bi bi-chat-left-text"></i>
        <strong>Note prenotazione:</strong> <?= e($reservation['customer_notes']) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($reservation['customer_notes_persistent'])): ?>
    <div class="customer-note" style="background:#E3F2FD;">
        <i class="bi bi-person-lines-fill"></i>
        <strong>Note interne cliente:</strong> <?= e($reservation['customer_notes_persistent']) ?>
    </div>
    <?php endif; ?>

    <!-- Action buttons -->
    <?php if (!in_array($reservation['status'], ['cancelled', 'noshow'])): ?>
    <div class="actions-bar">
        <?php if ($reservation['status'] !== 'arrived'): ?>
        <a href="<?= url("dashboard/reservations/{$reservation['id']}/edit") ?>" class="btn-action btn-act-edit">
            <i class="bi bi-pencil"></i> Modifica
        </a>
        <?php endif; ?>

        <?php if ($reservation['status'] === 'pending'): ?>
        <form method="POST" action="<?= url("dashboard/reservations/{$reservation['id']}/status") ?>" class="d-inline">
            <?= csrf_field() ?>
            <input type="hidden" name="status" value="confirmed">
            <button type="submit" class="btn-action btn-act-confirm"><i class="bi bi-check-circle"></i> Conferma</button>
        </form>
        <?php endif; ?>

        <?php if ($reservation['deposit_required'] && !$reservation['deposit_paid']): ?>
        <form method="POST" action="<?= url("dashboard/reservations/{$reservation['id']}/deposit-paid") ?>" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn-action" style="background:#E8F5E9;color:#2E7D32;border:1px solid #C8E6C9;"><i class="bi bi-cash-coin"></i> Segna caparra ricevuta</button>
        </form>
        <?php endif; ?>

        <?php if (in_array($reservation['status'], ['confirmed', 'pending'])): ?>
        <form method="POST" action="<?= url("dashboard/reservations/{$reservation['id']}/status") ?>" class="d-inline">
            <?= csrf_field() ?>
            <input type="hidden" name="status" value="arrived">
            <button type="submit" class="btn-action btn-act-arrived"><i class="bi bi-person-check"></i> Segna Arrivato</button>
        </form>
        <form method="POST" action="<?= url("dashboard/reservations/{$reservation['id']}/status") ?>" class="d-inline">
            <?= csrf_field() ?>
            <input type="hidden" name="status" value="noshow">
            <button type="submit" class="btn-action btn-act-noshow"><i class="bi bi-person-x"></i> No-Show</button>
        </form>
        <form method="POST" action="<?= url("dashboard/reservations/{$reservation['id']}/status") ?>" class="d-inline" data-confirm="Sei sicuro di voler annullare?">
            <?= csrf_field() ?>
            <input type="hidden" name="status" value="cancelled">
            <button type="submit" class="btn-action btn-act-cancel"><i class="bi bi-x-circle"></i> Annulla</button>
        </form>
        <?php endif; ?>

        <?php if ($reservation['status'] === 'arrived'): ?>
        <form method="POST" action="<?= url("dashboard/reservations/{$reservation['id']}/status") ?>" class="d-inline">
            <?= csrf_field() ?>
            <input type="hidden" name="status" value="confirmed">
            <button type="submit" class="btn-action btn-act-undo-arrived"><i class="bi bi-arrow-counterclockwise"></i> Annulla arrivo</button>
        </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($reservation['status'] === 'cancelled' && $reservation['deposit_required'] && $reservation['deposit_paid']): ?>
    <div class="deposit-refund-alert" style="margin-top:1rem;padding:.85rem 1rem;border-radius:8px;display:flex;align-items:center;justify-content:space-between;gap:1rem;<?= $reservation['deposit_refunded'] ? 'background:#E8F5E9;border:1px solid #C8E6C9;' : 'background:#FFF3E0;border:1px solid #FFE0B2;' ?>">
        <?php if ($reservation['deposit_refunded']): ?>
            <div style="font-size:.85rem;color:#2E7D32;">
                <i class="bi bi-check-circle-fill me-1"></i>
                Caparra di <strong>&euro;<?= number_format($reservation['deposit_amount'], 2) ?></strong> rimborsata
            </div>
        <?php else: ?>
            <div style="font-size:.85rem;color:#E65100;">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                Caparra di <strong>&euro;<?= number_format($reservation['deposit_amount'], 2) ?></strong> da rimborsare
            </div>
            <form method="POST" action="<?= url("dashboard/reservations/{$reservation['id']}/deposit-refunded") ?>" class="d-inline" data-confirm="Confermi di aver rimborsato la caparra?">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm" style="background:#2E7D32;color:#fff;font-size:.78rem;">
                    <i class="bi bi-check2 me-1"></i> Segna come rimborsata
                </button>
            </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Detail grid -->
<div class="detail-grid">

    <!-- Left column -->
    <div>
        <!-- Notes -->
        <div class="card" style="margin-bottom:1.25rem;">
            <div class="card-header">
                <h6><i class="bi bi-journal-text me-1"></i> Note Interne</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= url("dashboard/reservations/{$reservation['id']}/notes") ?>">
                    <?= csrf_field() ?>
                    <textarea class="notes-textarea" name="internal_notes" placeholder="Aggiungi note interne sulla prenotazione..."><?= e($reservation['internal_notes'] ?? '') ?></textarea>
                    <div style="margin-top:.5rem; text-align:right;">
                        <button type="submit" class="btn-action btn-act-edit" style="display:inline-flex;">
                            <i class="bi bi-check-lg"></i> Salva note
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete zone -->
        <?php if ($canDelete): ?>
        <div class="delete-zone">
            <div class="delete-zone-info">
                <strong><i class="bi bi-trash me-1"></i>Elimina prenotazione</strong>
                <small>Disponibile ancora per <?= $remainingMin ?> minuti. Azione irreversibile.</small>
            </div>
            <form method="POST" action="<?= url("dashboard/reservations/{$reservation['id']}/delete") ?>" data-confirm="Sei sicuro? Questa azione è IRREVERSIBILE e cancellerà definitivamente la prenotazione.">
                <?= csrf_field() ?>
                <button type="submit" class="btn-delete"><i class="bi bi-trash me-1"></i> Elimina</button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right column -->
    <div>
        <!-- Customer history -->
        <div class="card" style="margin-bottom:1.25rem;">
            <div class="history-header">
                <h6><i class="bi bi-person-circle me-2"></i>Storico Cliente</h6>
                <span class="segment-badge <?= $segment ?>"><?= $segmentLabel ?></span>
            </div>
            <div class="history-stats">
                <div class="hs-item">
                    <div class="hs-num" style="color:var(--brand);"><?= $bookings ?></div>
                    <div class="hs-label">Prenotazioni</div>
                </div>
                <div class="hs-item">
                    <div class="hs-num" style="color:#dc3545;"><?= (int)$reservation['total_noshow'] ?></div>
                    <div class="hs-label">No-show</div>
                </div>
                <div class="hs-item">
                    <div class="hs-num" style="color:#0d6efd;"><?= $totalHistoryCovers ?></div>
                    <div class="hs-label">Coperti tot.</div>
                </div>
            </div>
            <?php if (!empty($history)): ?>
            <div class="history-list">
                <?php foreach (array_slice($history, 0, 8) as $h): ?>
                <div class="history-row">
                    <span class="h-date"><?= format_date($h['reservation_date'], 'd/m/y') ?></span>
                    <span class="h-dot <?= e($h['status']) ?>"></span>
                    <span class="h-status"><?= status_label($h['status']) ?></span>
                    <span class="h-pax"><?= (int)$h['party_size'] ?> pax</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Timeline / log -->
        <div class="card">
            <div class="card-header">
                <h6><i class="bi bi-clock-history me-1"></i> Cronologia</h6>
            </div>
            <?php if (empty($logs)): ?>
            <div class="card-body">
                <p class="text-muted mb-0" style="font-size:.85rem;">Nessuna attività registrata.</p>
            </div>
            <?php else: ?>
            <div class="timeline-list">
                <?php foreach ($logs as $log):
                    // Determine icon type
                    $iconClass = 'status-change';
                    $iconName = 'bi-arrow-repeat';
                    if (!$log['old_status']) {
                        $iconClass = 'created';
                        $iconName = 'bi-plus';
                    } elseif ($log['note'] && str_contains($log['note'], 'Modificata')) {
                        $iconClass = 'edit';
                        $iconName = 'bi-pencil';
                    }
                ?>
                <div class="tl-item">
                    <div class="tl-icon <?= $iconClass ?>"><i class="bi <?= $iconName ?>"></i></div>
                    <div class="tl-body">
                        <div class="tl-time"><?= format_date($log['created_at'], 'd/m/Y H:i') ?></div>
                        <div class="tl-text">
                            <?php if ($log['old_status']): ?>
                                <?= status_label($log['old_status']) ?>
                                <i class="bi bi-arrow-right" style="font-size:.65rem;"></i>
                                <strong><?= status_label($log['new_status']) ?></strong>
                            <?php else: ?>
                                <strong><?= status_label($log['new_status']) ?></strong>
                            <?php endif; ?>
                        </div>
                        <?php if ($log['first_name']): ?>
                            <div class="tl-user">da <?= e($log['first_name'] . ' ' . $log['last_name']) ?></div>
                        <?php endif; ?>
                        <?php if ($log['note']): ?>
                            <div class="tl-text" style="font-size:.75rem;color:#6c757d;"><?= e($log['note']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>