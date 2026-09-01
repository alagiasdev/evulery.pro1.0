<?php
// $reason: 'expired' (abbonamento scaduto) | 'deactivated' (ristorante disattivato dall'admin)
$reason  = $reason ?? 'expired';
$isDeact = $reason === 'deactivated';
$tName   = tenant()['name'] ?? '';
$mailSubject = $isDeact ? 'Riattivazione account ' . $tName : 'Rinnovo abbonamento ' . $tName;
$waText      = $isDeact
    ? 'Salve, vorrei riattivare l\'account per ' . $tName . '.'
    : 'Salve, vorrei rinnovare l\'abbonamento per ' . $tName . '.';
?>
<div class="susp-container">
    <div class="susp-card">
        <div class="susp-icon">
            <i class="bi bi-pause-circle"></i>
        </div>
        <h1 class="susp-title"><?= $isDeact ? 'Account sospeso' : 'Abbonamento Sospeso' ?></h1>
        <p class="susp-subtitle">
            <?php if ($isDeact): ?>
                Il tuo account è stato <strong>temporaneamente sospeso</strong>. Contatta il supporto per riattivarlo.
            <?php else: ?>
                Il tuo abbonamento al piano <strong><?= e($planName) ?></strong> è scaduto
                <?php if ($expiredDate): ?>
                    il <strong><?= date('d/m/Y', strtotime($expiredDate)) ?></strong>
                    (<?= $daysSinceExpiry ?> giorni fa).
                <?php else: ?>
                    .
                <?php endif; ?>
            <?php endif; ?>
        </p>

        <div class="susp-info-box">
            <div class="susp-info-row">
                <i class="bi bi-shield-check"></i>
                <div>
                    <strong>I tuoi dati sono al sicuro</strong>
                    <p>Prenotazioni, clienti e impostazioni sono conservati. <?= $isDeact ? 'Alla riattivazione' : 'Rinnovando l\'abbonamento' ?> ritroverai tutto come prima.</p>
                </div>
            </div>
            <div class="susp-info-row">
                <i class="bi bi-globe-americas"></i>
                <div>
                    <strong>Pagine pubbliche sospese</strong>
                    <p>La pagina di prenotazione, il menù e il widget non sono attualmente visibili ai clienti.</p>
                </div>
            </div>
        </div>

        <div class="susp-section-title"><?= $isDeact ? 'Come riattivarlo' : 'Come rinnovare' ?></div>

        <div class="susp-actions">
            <a href="mailto:<?= e($supportEmail) ?>?subject=<?= rawurlencode($mailSubject) ?>"
               class="susp-btn susp-btn-primary">
                <i class="bi bi-envelope"></i> Contatta il supporto via email
            </a>
            <?php if ($supportPhone): ?>
            <a href="https://wa.me/<?= e(preg_replace('/[^0-9]/', '', $supportPhone)) ?>?text=<?= rawurlencode($waText) ?>"
               target="_blank" class="susp-btn susp-btn-whatsapp">
                <i class="bi bi-whatsapp"></i> Scrivici su WhatsApp
            </a>
            <a href="tel:<?= e($supportPhone) ?>" class="susp-btn susp-btn-outline">
                <i class="bi bi-telephone"></i> Chiama <?= e($supportPhone) ?>
            </a>
            <?php endif; ?>
            <?php if (!$isDeact): ?>
            <button type="button" class="susp-btn susp-btn-disabled" disabled title="Prossimamente">
                <i class="bi bi-credit-card"></i> Rinnova online
                <span class="susp-badge-soon">Prossimamente</span>
            </button>
            <?php endif; ?>
        </div>

        <div class="susp-footer">
            <p>Hai domande? Scrivi a <a href="mailto:<?= e($supportEmail) ?>"><?= e($supportEmail) ?></a></p>
        </div>
    </div>
</div>
