<?php
/**
 * Public Review Landing Page (standalone, no layout)
 * Variables: $title, $tenant, $slug, $token, $state, $embed, $reviewRequest, $apiBaseUrl
 */
$tenantName = e($tenant['name'] ?? '');
$logoUrl = $tenant['logo_url'] ?? '';
$platformLabel = $tenant['review_platform_label'] ?? '';
$reviewUrl = $tenant['review_url'] ?? '';
$filterMessage = $tenant['review_filter_message'] ?? 'Ci dispiace! Dicci cosa possiamo migliorare';
$threshold = (int)($tenant['review_filter_threshold'] ?? 4);

// Customer name (email tracked mode)
$customerName = '';
if ($reviewRequest && !empty($reviewRequest['first_name'])) {
    $customerName = e($reviewRequest['first_name']);
}

// Reservation info
$resDate = '';
if ($reviewRequest && !empty($reviewRequest['reservation_date'])) {
    $resDate = format_date($reviewRequest['reservation_date'], 'd F Y');
    if (!empty($reviewRequest['reservation_time'])) {
        $resDate .= ', ore ' . substr($reviewRequest['reservation_time'], 0, 5);
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= url('assets/css/reviews.css') ?>" rel="stylesheet">
    <?php if ($embed): ?>
    <style nonce="<?= csp_nonce() ?>">body { background: transparent; } .rv-footer { display: none; } .rv-page { min-height: auto; padding: 1rem; }</style>
    <?php endif; ?>
</head>
<body class="<?= $embed ? 'rv-embed' : '' ?>">

<div class="rv-page">
    <?php if (!$embed): ?>
    <div class="rv-header">
        <?php if ($logoUrl): ?>
        <img src="<?= e($logoUrl) ?>" alt="<?= $tenantName ?>" class="rv-logo">
        <?php else: ?>
        <div class="rv-logo-placeholder"><i class="bi bi-shop"></i></div>
        <?php endif; ?>
        <div class="rv-restaurant"><?= $tenantName ?></div>
        <div class="rv-subtitle">Ti ringraziamo per averci scelto!</div>
    </div>
    <?php endif; ?>

    <div class="rv-content">

        <?php if ($state === 'expired'): ?>
        <!-- EXPIRED -->
        <div class="rv-state-card">
            <div class="rv-state-icon" style="background:#FFF3E0; color:#FF9800;"><i class="bi bi-clock-history"></i></div>
            <h3 class="rv-state-title">Link scaduto</h3>
            <p class="rv-state-text">Questo link per la recensione non &egrave; pi&ugrave; valido.<br>Se desideri lasciare un feedback, contatta direttamente il ristorante.</p>
        </div>

        <?php elseif ($state === 'already'): ?>
        <!-- ALREADY REVIEWED -->
        <div class="rv-state-card">
            <div class="rv-state-icon" style="background:#E3F2FD; color:#42A5F5;"><i class="bi bi-check2-circle"></i></div>
            <h3 class="rv-state-title">Grazie!</h3>
            <p class="rv-state-text">Hai gi&agrave; lasciato la tua valutazione.<br>Il tuo feedback &egrave; prezioso per noi!</p>
        </div>

        <?php elseif ($state === 'direct'): ?>
        <!-- DIRECT REDIRECT (no filter) -->
        <div class="rv-state-card">
            <?php if ($customerName): ?>
            <p class="rv-greeting">Ciao <strong><?= $customerName ?></strong>, grazie per aver cenato da <?= $tenantName ?>!</p>
            <?php endif; ?>
            <p class="rv-state-text">Ci farebbe molto piacere se condividessi la tua esperienza.</p>
            <?php if ($reviewUrl): ?>
            <a href="<?= e($reviewUrl) ?>" class="rv-cta-btn" target="_blank" rel="noopener">
                <i class="bi bi-star"></i>
                Lascia una recensione<?= $platformLabel ? ' su ' . e($platformLabel) : '' ?>
            </a>
            <?php endif; ?>
        </div>

        <?php elseif ($state === 'rating'): ?>
        <!-- RATING (with sentiment filter) -->
        <div id="rv-rating-view">
            <?php if ($customerName): ?>
            <div class="rv-greeting">
                Ciao <strong><?= $customerName ?></strong>, com'&egrave; andata la tua esperienza?
            </div>
            <?php endif; ?>
            <?php if ($resDate): ?>
            <div class="rv-res-info"><i class="bi bi-calendar3"></i> <?= $resDate ?></div>
            <?php endif; ?>

            <div class="rv-stars-card">
                <div class="rv-stars-question">Come valuti la tua esperienza?</div>
                <div class="rv-stars-row" id="rv-stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <button class="rv-star-btn" data-rating="<?= $i ?>">&#9733;</button>
                    <?php endfor; ?>
                </div>
                <div class="rv-star-labels">
                    <span>Pessima</span>
                    <span>Eccellente</span>
                </div>
            </div>
        </div>

        <!-- Feedback form (hidden, shown by JS after low rating) -->
        <div id="rv-feedback-view" style="display:none;">
            <div class="rv-feedback-card">
                <div class="rv-feedback-title">Aiutaci a migliorare</div>
                <div class="rv-feedback-subtitle">Il tuo commento rester&agrave; privato e verr&agrave; letto direttamente dal ristorante</div>
                <textarea id="rv-feedback-text" class="rv-feedback-textarea" placeholder="Raccontaci cosa non ha funzionato: servizio, tempi di attesa, qualit&agrave; del cibo, ambiente..." minlength="10" maxlength="2000"></textarea>
                <div class="rv-feedback-hint">Minimo 10 caratteri</div>
                <button id="rv-feedback-submit" class="rv-cta-btn" style="width:100%; margin-top:.75rem;">
                    <i class="bi bi-send"></i> Invia feedback
                </button>
                <div class="rv-privacy"><i class="bi bi-shield-check"></i> Il tuo feedback &egrave; privato</div>
            </div>
        </div>

        <!-- Thank you (shown by JS) -->
        <div id="rv-thanks-view" style="display:none;">
            <div class="rv-state-card">
                <div class="rv-state-icon" style="background:#e8f5e9; color:#00844A;"><i class="bi bi-check-lg"></i></div>
                <h3 class="rv-state-title">Grazie!</h3>
                <p class="rv-state-text" id="rv-thanks-text">Il tuo feedback &egrave; stato inviato.</p>
            </div>
        </div>

        <?php endif; ?>
    </div>

    <?php if (!$embed): ?>
    <div class="rv-footer">
        &copy; <?= date('Y') ?> Evulery &middot; by alagias. &mdash; Soluzioni per il web
    </div>
    <?php endif; ?>
</div>

<?php if ($state === 'rating'): ?>
<div id="rv-config" style="display:none;"
     data-api-base-url="<?= e(rtrim($apiBaseUrl, '/')) ?>"
     data-slug="<?= e($slug) ?>"
     data-token="<?= e($token) ?>"
     data-source="<?= e($_GET['source'] ?? 'embed') ?>"
     data-embed="<?= $embed ? '1' : '0' ?>"></div>
<script nonce="<?= csp_nonce() ?>" src="<?= asset('js/review-landing.js') ?>"></script>
<?php endif; ?>

</body>
</html>
