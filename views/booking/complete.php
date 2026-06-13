<?php
$bn     = (int)($reservation['booking_number'] ?? $reservation['id']);
$date   = $reservation['reservation_date'] ?? '';
$time   = substr((string)($reservation['reservation_time'] ?? ''), 0, 5);
$party  = (int)($reservation['party_size'] ?? 0);
$slug   = $tenant['slug'] ?? '';
$dateIt = $date ? date('d/m/Y', strtotime($date)) : '';
$depositType = $tenant['deposit_type'] ?? 'info';
$isGuarantee = $depositType === 'guarantee';

// Permanenza al tavolo: durata snapshot della prenotazione (fallback globale).
$durMin = (int)($reservation['duration_minutes'] ?? $tenant['table_duration'] ?? 90);
$stayEnd = ($time && $durMin > 0) ? date('H:i', strtotime($reservation['reservation_time']) + $durMin * 60) : '';
$stayText = $stayEnd ? 'dalle ' . $time . ' alle ' . $stayEnd . ' (' . format_duration_label($durMin) . ')' : '';

// CTA "Prenota di nuovo": riapre il widget precompilato con data e coperti
$rebookUrl = url($slug) . '?rebook=1&date=' . urlencode($date) . '&party=' . $party;
$manageUrl = !empty($reservation['manage_token']) ? url('manage/' . $reservation['manage_token']) : '';

// Box riepilogo prenotazione (condiviso)
$recapBox = '<div style="background:#f8f9fa;border:1px solid #e9ecef;border-radius:10px;padding:14px;margin:14px 0;font-size:.88rem;text-align:left;">'
    . '<div style="display:flex;justify-content:space-between;padding:5px 0;"><span style="color:#6c757d;">Ristorante</span><strong>' . e($tenant['name'] ?? '') . '</strong></div>'
    . '<div style="display:flex;justify-content:space-between;padding:5px 0;border-top:1px solid #eef0f2;"><span style="color:#6c757d;">Data</span><strong>' . e($dateIt) . '</strong></div>'
    . '<div style="display:flex;justify-content:space-between;padding:5px 0;border-top:1px solid #eef0f2;"><span style="color:#6c757d;">Orario</span><strong>' . e($time) . '</strong></div>'
    . '<div style="display:flex;justify-content:space-between;padding:5px 0;border-top:1px solid #eef0f2;"><span style="color:#6c757d;">Persone</span><strong>' . $party . '</strong></div>'
    . ($stayText ? '<div style="display:flex;justify-content:space-between;padding:5px 0;border-top:1px solid #eef0f2;"><span style="color:#6c757d;">Tavolo riservato</span><strong>' . $stayText . '</strong></div>' : '')
    . '</div>';
?>
<div class="bw-conf-card">

<?php if ($state === 'expired'): ?>

    <div class="bw-conf-header bw-conf-header--cancel">
        <div class="bw-conf-check"><i class="bi bi-hourglass-bottom"></i></div>
        <div class="bw-conf-title">Prenotazione scaduta</div>
        <div class="bw-conf-subtitle">Il tempo per completare è terminato</div>
    </div>
    <div class="bw-conf-body">
        <div class="bw-conf-note bw-conf-note--warn">
            <i class="bi bi-exclamation-triangle"></i>
            <span>
                Non hai completato <?= $isGuarantee ? 'la registrazione della carta' : 'il pagamento della caparra' ?>
                entro <strong>30 minuti</strong>: la prenotazione <strong>n. <?= $bn ?></strong> è stata annullata
                e il tavolo è tornato disponibile per altri clienti.
            </span>
        </div>
        <?= $recapBox ?>
        <div class="bw-conf-actions">
            <a href="<?= e($rebookUrl) ?>" class="bw-conf-btn-primary">
                <i class="bi bi-calendar-plus"></i> Prenota di nuovo
            </a>
        </div>
        <p style="font-size:.76rem;color:#9aa1a9;text-align:center;margin:10px 0 0;">
            La disponibilità per la data e l'orario indicati non è garantita.
        </p>
    </div>

<?php elseif ($state === 'confirmed'): ?>

    <div class="bw-conf-header">
        <div class="bw-conf-check"><i class="bi bi-check-lg"></i></div>
        <div class="bw-conf-title">Prenotazione confermata</div>
        <div class="bw-conf-subtitle">Tutto a posto, ti aspettiamo!</div>
    </div>
    <div class="bw-conf-body">
        <div class="bw-conf-note">
            <i class="bi bi-check-circle"></i>
            <span>
                La prenotazione <strong>n. <?= $bn ?></strong> è già confermata
                <?= $isGuarantee ? '— la carta a garanzia è stata registrata.' : '— la caparra è stata ricevuta.' ?>
                Non devi fare altro.
            </span>
        </div>
        <?= $recapBox ?>
        <?php include __DIR__ . '/../partials/booking-directions.php'; ?>
        <div class="bw-conf-actions">
            <?php if ($manageUrl): ?>
            <a href="<?= e($manageUrl) ?>" class="bw-conf-btn-primary">
                <i class="bi bi-pencil-square"></i> Gestisci la prenotazione
            </a>
            <?php endif; ?>
            <?php if (!empty($tenant['menu_enabled'])): ?>
            <a href="<?= e(url($slug . '/menu')) ?>" class="bw-conf-btn-secondary bw-conf-btn--muted">
                <i class="bi bi-book"></i> Consulta il menù
            </a>
            <?php endif; ?>
        </div>
    </div>

<?php else: /* pending */ ?>

    <div class="bw-conf-header bw-conf-header--cancel">
        <div class="bw-conf-check"><i class="bi bi-hourglass-split"></i></div>
        <div class="bw-conf-title">Prenotazione in attesa</div>
        <div class="bw-conf-subtitle">Manca un ultimo passaggio</div>
    </div>
    <div class="bw-conf-body">
        <div class="bw-conf-note bw-conf-note--warn">
            <i class="bi bi-envelope"></i>
            <span>
                La prenotazione <strong>n. <?= $bn ?></strong> è in attesa di conferma.
                Segui le istruzioni che ti abbiamo inviato via email per completarla.
            </span>
        </div>
        <?= $recapBox ?>
        <div class="bw-conf-actions">
            <a href="<?= e(url($slug)) ?>" class="bw-conf-btn-secondary bw-conf-btn--muted">
                <i class="bi bi-arrow-repeat"></i> Torna al ristorante
            </a>
        </div>
    </div>

<?php endif; ?>

</div>
