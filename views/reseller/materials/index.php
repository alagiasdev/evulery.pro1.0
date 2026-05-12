<div class="rs-page-header">
    <div>
        <h1 class="rs-page-title">Materiali commerciali</h1>
        <p class="rs-page-sub">Lo stretto necessario per presentare Evulery, condurre la demo e — se serve — partire a freddo.</p>
    </div>
</div>

<!-- 1. Da presentare al cliente -->
<div class="rs-mat-section">
    <h3><i class="bi bi-send"></i> Da presentare al cliente</h3>
    <div class="rs-mat-grid">
        <?php foreach ($forClient as $key => $m): ?>
            <?php $hasPdf = !empty($m['download_file']); ?>
            <div class="rs-mat-card">
                <div class="rs-mat-icon"><i class="bi bi-<?= e($m['icon']) ?>"></i></div>
                <h4><?= e($m['title']) ?></h4>
                <p><?= e($m['description']) ?></p>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                    <a href="<?= url('reseller/materials/' . $key . '/preview') ?>" target="_blank" rel="noopener" class="rs-btn <?= $hasPdf ? 'rs-btn-ghost' : 'rs-btn-primary' ?> rs-btn-sm">
                        <i class="bi bi-eye"></i> Apri
                    </a>
                    <?php if ($hasPdf): ?>
                        <a href="<?= url('reseller/materials/' . $key) ?>" target="_blank" rel="noopener" class="rs-btn rs-btn-primary rs-btn-sm">
                            <i class="bi bi-download"></i> Scarica PDF
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- 2. Strumenti operativi per la demo -->
<div class="rs-mat-section">
    <h3><i class="bi bi-tools"></i> In demo</h3>
    <div class="rs-mat-grid">
        <?php foreach ($forTools as $key => $m): ?>
            <div class="rs-mat-card">
                <div class="rs-mat-icon"><i class="bi bi-<?= e($m['icon']) ?>"></i></div>
                <h4><?= e($m['title']) ?></h4>
                <p><?= e($m['description']) ?></p>
                <a href="<?= url('reseller/materials/' . $key) ?>" target="_blank" rel="noopener" class="rs-btn rs-btn-ghost rs-btn-sm">
                    <i class="bi bi-arrow-up-right-circle"></i> Apri
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- 3. Outbound a freddo (opzionale) -->
<div class="rs-mat-section">
    <h3><i class="bi bi-crosshair"></i> Outbound a freddo <span style="font-weight:400;color:#6c757d;text-transform:none;letter-spacing:0;font-size:.78rem;">— solo se non parti da network warm</span></h3>
    <div class="rs-mat-grid">
        <?php foreach ($forOutbound as $key => $m): ?>
            <div class="rs-mat-card">
                <div class="rs-mat-icon"><i class="bi bi-<?= e($m['icon']) ?>"></i></div>
                <h4><?= e($m['title']) ?></h4>
                <p><?= e($m['description']) ?></p>
                <a href="<?= url('reseller/materials/' . $key) ?>" target="_blank" rel="noopener" class="rs-btn rs-btn-ghost rs-btn-sm">
                    <i class="bi bi-arrow-up-right-circle"></i> Apri
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div style="background:#fff8e1;border-left:3px solid #ffc107;padding:.7rem 1rem;border-radius:6px;font-size:.8rem;color:#5d4037;">
    <i class="bi bi-info-circle"></i>
    In arrivo: contratto procacciatore B2B, case study dei primi clienti, video di demo registrata.
</div>
