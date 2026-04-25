<?php
$primary = $colors['primary'] ?? '#00844A';
$accent  = $colors['accent']  ?? '#E8F5E9';
$dark    = $colors['dark']    ?? '#006837';
$bg      = $colors['bg']      ?? '#ffffff';
$tenantName = $tenant['name'] ?? 'Ristorante';
$logoInitial = mb_strtoupper(mb_substr($tenantName, 0, 1));
$logoUrl = $settings['logo_url'] ?? null;
$coverUrl = $settings['cover_url'] ?? null;
$hideBranding = !empty($settings['hide_branding']);

$hexToRgb = function (string $hex): string {
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6) return '0,132,74';
    return hexdec(substr($hex, 0, 2)) . ',' . hexdec(substr($hex, 2, 2)) . ',' . hexdec(substr($hex, 4, 2));
};
$primaryRgb = $hexToRgb($primary);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offerte · <?= e($tenantName) ?></title>
    <meta name="description" content="Promozioni attive di <?= e($tenantName) ?>">
    <meta name="theme-color" content="<?= e($primary) ?>">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= asset('css/hub.css') ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body.hub-public-page {
            --hub-primary: <?= e($primary) ?>;
            --hub-primary-rgb: <?= e($primaryRgb) ?>;
            --hub-accent: <?= e($accent) ?>;
            --hub-dark: <?= e($dark) ?>;
            --hub-bg: <?= e($bg) ?>;
        }
    </style>
</head>
<body class="hub-public-page">

    <div class="hub-public-cover <?= $coverUrl ? '' : 'gradient' ?>"
         <?= $coverUrl ? 'style="background-image: url(' . e($coverUrl) . ')"' : '' ?>>
        <div class="hub-public-logo">
            <?php if ($logoUrl): ?>
                <img src="<?= e($logoUrl) ?>" alt="<?= e($tenantName) ?>">
            <?php else: ?>
                <?= e($logoInitial) ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="hub-public-body">
        <a href="<?= url($tenant['slug'] . '/hub') ?>" class="hub-promo-back">
            <i class="bi bi-arrow-left"></i> Torna alla Vetrina
        </a>

        <h1 class="hub-public-name"><?= e($tenantName) ?></h1>
        <p class="hub-public-tag">Offerte del momento</p>

        <?php if (empty($promos)): ?>
        <div class="hub-promo-empty">
            <i class="bi bi-gift"></i>
            <p>Nessuna offerta attiva al momento.<br>Torna a trovarci presto!</p>
            <a href="<?= url($tenant['slug']) ?>" class="hub-public-cta">
                <i class="bi bi-calendar-check"></i>
                <div class="hub-public-cta-label">PRENOTA UN TAVOLO</div>
            </a>
        </div>
        <?php else: ?>
        <div class="hub-promo-list">
            <?php foreach ($promos as $p): ?>
            <div class="hub-promo-card <?= $p['_is_live_now'] ? 'is-live' : '' ?>">
                <div class="hub-promo-discount">
                    <span class="hub-promo-discount-sign">−</span><span class="hub-promo-discount-num"><?= (int)$p['discount_percent'] ?></span><span class="hub-promo-discount-pct">%</span>
                </div>
                <div class="hub-promo-info">
                    <div class="hub-promo-head">
                        <div class="hub-promo-name"><?= e($p['name']) ?></div>
                        <?php if ($p['_is_live_now']): ?>
                        <span class="hub-promo-badge"><i class="bi bi-broadcast"></i> Attiva adesso</span>
                        <?php endif; ?>
                    </div>
                    <div class="hub-promo-meta">
                        <span><i class="bi bi-clock"></i> <?= e($p['_when_label']) ?></span>
                        <span><i class="bi bi-tag"></i> <?= e($p['_applies_to_label']) ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <a href="<?= url($tenant['slug']) ?>" class="hub-public-cta" style="margin-top:1.5rem;">
            <i class="bi bi-calendar-check"></i>
            <div class="hub-public-cta-label">PRENOTA UN TAVOLO</div>
            <div class="hub-public-cta-hint">Approfitta delle offerte attive</div>
        </a>
        <?php endif; ?>
    </div>

    <footer class="hub-public-footer">
        <?php if ($tenant['address'] ?? null): ?>
        <div><?= e($tenant['address']) ?></div>
        <?php endif; ?>
        <?php if (!$hideBranding): ?>
        <div class="hub-evulery-mark">Powered by Evulery</div>
        <?php endif; ?>
    </footer>

</body>
</html>
