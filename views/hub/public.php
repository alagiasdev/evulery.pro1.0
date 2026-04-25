<?php
$primary = $colors['primary'] ?? '#00844A';
$accent  = $colors['accent']  ?? '#E8F5E9';
$dark    = $colors['dark']    ?? '#006837';
$bg      = $colors['bg']      ?? '#ffffff';
$tenantName = $tenant['name'] ?? 'Ristorante';
$logoInitial = mb_strtoupper(mb_substr($tenantName, 0, 1));
$subtitle = $settings['subtitle'] ?? '';
$logoUrl = $settings['logo_url'] ?? null;
$coverUrl = $settings['cover_url'] ?? null;
$hideBranding = !empty($settings['hide_branding']);

// Social links — only render if URL/number set
$socials = [
    'instagram' => ['url' => $settings['instagram_url'] ?? '', 'icon' => 'bi-instagram', 'label' => 'Instagram'],
    'facebook'  => ['url' => $settings['facebook_url']  ?? '', 'icon' => 'bi-facebook',  'label' => 'Facebook'],
    'tiktok'    => ['url' => $settings['tiktok_url']    ?? '', 'icon' => 'bi-tiktok',    'label' => 'TikTok'],
    'twitter'   => ['url' => $settings['twitter_url']   ?? '', 'icon' => 'bi-twitter-x', 'label' => 'X'],
    'youtube'   => ['url' => $settings['youtube_url']   ?? '', 'icon' => 'bi-youtube',   'label' => 'YouTube'],
];
$wa = $settings['whatsapp_number'] ?? '';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tenantName) ?></title>
    <meta name="description" content="<?= e($subtitle ?: $tenantName) ?>">
    <meta name="theme-color" content="<?= e($primary) ?>">

    <!-- Open Graph -->
    <meta property="og:title" content="<?= e($tenantName) ?>">
    <meta property="og:description" content="<?= e($subtitle ?: 'Prenota un tavolo, scopri il menu, contattaci.') ?>">
    <meta property="og:type" content="website">
    <?php if ($coverUrl): ?>
    <meta property="og:image" content="<?= e($coverUrl) ?>">
    <?php endif; ?>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= asset('css/hub.css') ?>">

    <!-- Inter (default, font dell'identità Evulery) + eventuale font custom scelto dal ristoratore -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php
        // Sempre Inter come base, anche se è impostato un display font (serif/caveat),
        // perché il display font si applica solo al nome del ristorante.
        $fontFamilies = ['family=Inter:wght@400;500;600;700;800'];
        switch ($settings['custom_font'] ?? null) {
            case 'serif':        $fontFamilies[] = 'family=Playfair+Display:wght@700;800;900'; break;
            case 'merriweather': $fontFamilies[] = 'family=Merriweather:wght@700;900'; break;
            case 'caveat':       $fontFamilies[] = 'family=Caveat:wght@700'; break;
        }
    ?>
    <link href="https://fonts.googleapis.com/css2?<?= implode('&', $fontFamilies) ?>&display=swap" rel="stylesheet">

    <style>
        body.hub-public-page {
            --hub-primary: <?= e($primary) ?>;
            --hub-accent: <?= e($accent) ?>;
            --hub-dark: <?= e($dark) ?>;
            --hub-bg: <?= e($bg) ?>;
            --hub-display-font: <?= e($fontFamily) ?>;
        }
        .hub-public-name { font-family: var(--hub-display-font); }
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
        <h1 class="hub-public-name"><?= e($tenantName) ?></h1>
        <?php if ($subtitle): ?>
        <p class="hub-public-tag"><?= e($subtitle) ?></p>
        <?php endif; ?>

        <?php if (!empty($hero)): ?>
        <a href="<?= e($hero['url']) ?>" class="hub-public-cta">
            <i class="bi <?= e($hero['icon']) ?>"></i>
            <div class="hub-public-cta-label"><?= e(mb_strtoupper($hero['label'])) ?></div>
            <?php if (!empty($hero['sub'])): ?>
            <div class="hub-public-cta-hint"><?= e($hero['sub']) ?></div>
            <?php endif; ?>
        </a>
        <?php endif; ?>

        <?php if (!empty($items)): ?>
        <div class="hub-public-sep">Altre cose che puoi fare</div>
        <div class="hub-public-list">
            <?php foreach ($items as $item):
                $isExternal = preg_match('#^https?://#i', $item['url']);
            ?>
            <a href="<?= e($item['url']) ?>" class="hub-public-row" <?= $isExternal ? 'target="_blank" rel="noopener"' : '' ?>>
                <span class="hub-public-row-icon"><i class="bi <?= e($item['icon']) ?>"></i></span>
                <div class="hub-public-row-text">
                    <div class="hub-public-row-label"><?= e($item['label']) ?></div>
                    <?php if (!empty($item['sub'])): ?>
                    <div class="hub-public-row-sub"><?= e($item['sub']) ?></div>
                    <?php endif; ?>
                </div>
                <i class="bi bi-chevron-right hub-public-row-chev"></i>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <footer class="hub-public-footer">
        <?php
        $hasAnySocial = array_filter($socials, fn($s) => !empty($s['url'])) || $wa;
        if ($hasAnySocial):
        ?>
        <div class="hub-public-social">
            <?php foreach ($socials as $s): if (empty($s['url'])) continue; ?>
            <a href="<?= e($s['url']) ?>" target="_blank" rel="noopener" aria-label="<?= e($s['label']) ?>">
                <i class="bi <?= e($s['icon']) ?>"></i>
            </a>
            <?php endforeach; ?>
            <?php if ($wa): ?>
            <a href="https://wa.me/<?= e(preg_replace('/[^0-9+]/', '', $wa)) ?>" target="_blank" rel="noopener" aria-label="WhatsApp">
                <i class="bi bi-whatsapp"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($tenant['address'] ?? null): ?>
        <div><?= e($tenant['address']) ?></div>
        <?php endif; ?>

        <?php if (!$hideBranding): ?>
        <div class="hub-evulery-mark">Powered by Evulery</div>
        <?php endif; ?>
    </footer>

</body>
</html>
