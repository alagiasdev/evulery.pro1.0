<?php
$isGuar      = ($tenant['deposit_type'] ?? '') === 'guarantee';
$cancelTitle = $isGuar ? 'Registrazione non completata' : 'Pagamento non completato';
$completeUrl = !empty($reservation['manage_token'])
    ? url('booking/complete/' . $reservation['manage_token'])
    : url($tenant['slug'] ?? '');
?>
<div class="bw-conf-card">
    <div class="bw-conf-header bw-conf-header--cancel">
        <div class="bw-conf-check">
            <i class="bi bi-x-lg"></i>
        </div>
        <div class="bw-conf-title"><?= e($cancelTitle) ?></div>
        <div class="bw-conf-subtitle">La prenotazione non è stata confermata</div>
    </div>

    <div class="bw-conf-body">
        <?php if (!empty($canRetry) && !empty($reservation)): ?>
        <div class="bw-conf-note bw-conf-note--warn">
            <i class="bi bi-exclamation-triangle"></i>
            <span>
                <?php if ($isGuar): ?>
                La registrazione della carta a garanzia non è stata completata.
                <?php else: ?>
                Il pagamento della caparra non è stato completato.
                <?php endif; ?>
                La prenotazione n. <strong><?= (int)($reservation['booking_number'] ?? $reservation['id']) ?></strong>
                è ancora in attesa: puoi completarla adesso, senza rifarla da capo.
            </span>
        </div>

        <div style="background:#f8f9fa;border:1px solid #e9ecef;border-radius:10px;padding:14px;margin:12px 0;font-size:.88rem;">
            <div style="margin-bottom:4px;"><strong><?= e($reservation['first_name'] . ' ' . $reservation['last_name']) ?></strong></div>
            <div><?= date('d/m/Y', strtotime($reservation['reservation_date'])) ?> alle <?= substr($reservation['reservation_time'], 0, 5) ?> &mdash; <?= (int)$reservation['party_size'] ?> persone</div>
            <?php if ($isGuar): ?>
            <div style="margin-top:4px;color:#2E7D32;font-weight:600;"><i class="bi bi-shield-lock"></i> Carta a garanzia &mdash; nessun addebito</div>
            <?php else: ?>
            <div style="margin-top:4px;color:#E65100;font-weight:600;">Caparra: &euro;<?= number_format((float)$reservation['deposit_amount'], 2, ',', '.') ?></div>
            <?php endif; ?>
        </div>

        <div class="bw-conf-actions">
            <a href="<?= e($completeUrl) ?>" class="bw-conf-btn-primary">
                <?php if ($isGuar): ?>
                <i class="bi bi-shield-lock"></i> Registra la carta a garanzia
                <?php else: ?>
                <i class="bi bi-credit-card"></i> Paga la caparra adesso
                <?php endif; ?>
            </a>
            <a href="<?= url($tenant['slug'] ?? '') ?>" class="bw-conf-btn-secondary bw-conf-btn--muted">
                <i class="bi bi-arrow-repeat"></i> Nuova prenotazione
            </a>
        </div>

        <?php else: ?>
        <div class="bw-conf-note bw-conf-note--warn">
            <i class="bi bi-exclamation-triangle"></i>
            <span>
                <?php if ($isGuar): ?>
                La registrazione della carta non è stata completata. La prenotazione verrà annullata automaticamente.
                <?php else: ?>
                Il pagamento della caparra non è stato completato. La prenotazione verrà annullata automaticamente.
                <?php endif; ?>
            </span>
        </div>

        <div class="bw-conf-actions">
            <a href="<?= url($tenant['slug'] ?? '') ?>" class="bw-conf-btn-primary bw-conf-btn--orange">
                <i class="bi bi-arrow-repeat"></i> Riprova la prenotazione
            </a>
        </div>
        <?php endif; ?>

        <?php if (!empty($tenant['phone']) || !empty($tenant['email'])): ?>
        <div style="background:#f8f9fa;border:1px solid #e9ecef;border-radius:10px;padding:14px;margin-top:16px;font-size:.85rem;text-align:center;">
            <div style="font-weight:600;margin-bottom:6px;">Hai bisogno di assistenza?</div>
            <div style="display:flex;flex-direction:column;gap:8px;align-items:center;">
                <?php if (!empty($tenant['phone'])): ?>
                <a href="tel:<?= e($tenant['phone']) ?>" style="color:#2E7D32;text-decoration:none;font-weight:500;">
                    <i class="bi bi-telephone"></i> <?= e($tenant['phone']) ?>
                </a>
                <?php endif; ?>
                <?php if (!empty($tenant['email'])): ?>
                <a href="mailto:<?= e($tenant['email']) ?>" style="color:#2E7D32;text-decoration:none;font-weight:500;">
                    <i class="bi bi-envelope"></i> <?= e($tenant['email']) ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="bw-conf-footer">
        &copy; <?= date('Y') ?> Evulery &middot; by alagias. - Soluzioni per il web
    </div>
</div>
