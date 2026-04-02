<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tenantName ?? 'Ristorante') ?> - Prenotazioni non disponibili</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    <style nonce="<?= csp_nonce() ?>">
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f8f9fa;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:1.5rem;color:#1a1d23}
        .pub-susp{max-width:440px;width:100%;text-align:center}
        .pub-susp-logo{max-height:80px;margin-bottom:1rem}
        .pub-susp-name{font-size:1.5rem;font-weight:700;margin-bottom:2rem}
        .pub-susp-card{background:#fff;border-radius:16px;padding:2.5rem 2rem;box-shadow:0 2px 12px rgba(0,0,0,.06)}
        .pub-susp-icon{font-size:2.5rem;color:#adb5bd;margin-bottom:1rem}
        .pub-susp-title{font-size:1.15rem;font-weight:700;margin-bottom:.5rem;color:#495057}
        .pub-susp-text{font-size:.9rem;color:#6c757d;line-height:1.6;margin-bottom:1.5rem}
        .pub-susp-contacts{display:flex;flex-direction:column;gap:.6rem}
        .pub-susp-contact{display:flex;align-items:center;gap:.6rem;padding:.65rem 1rem;border-radius:10px;background:#f8f9fa;text-decoration:none;color:#1a1d23;font-size:.88rem;font-weight:500;transition:background .15s}
        .pub-susp-contact:hover{background:#e9ecef;color:#1a1d23}
        .pub-susp-contact i{font-size:1.1rem;color:#6c757d;width:20px;text-align:center}
        .pub-susp-footer{margin-top:2rem;font-size:.72rem;color:#adb5bd}
        .pub-susp-footer a{color:#adb5bd;text-decoration:none}
    </style>
</head>
<body>
    <div class="pub-susp">
        <?php if (!empty($tenantLogo)): ?>
            <img src="<?= e($tenantLogo) ?>" alt="<?= e($tenantName ?? '') ?>" class="pub-susp-logo">
        <?php endif; ?>
        <div class="pub-susp-name"><?= e($tenantName ?? 'Ristorante') ?></div>

        <div class="pub-susp-card">
            <div class="pub-susp-icon"><i class="bi bi-calendar-x"></i></div>
            <div class="pub-susp-title">Prenotazioni non disponibili</div>
            <p class="pub-susp-text">
                Il servizio di prenotazione online non è al momento attivo.<br>
                Per prenotare un tavolo, contatta direttamente il ristorante.
            </p>

            <div class="pub-susp-contacts">
                <?php if (!empty($tenantPhone)): ?>
                <a href="tel:<?= e($tenantPhone) ?>" class="pub-susp-contact">
                    <i class="bi bi-telephone"></i> <?= e($tenantPhone) ?>
                </a>
                <?php endif; ?>
                <?php if (!empty($tenantEmail)): ?>
                <a href="mailto:<?= e($tenantEmail) ?>" class="pub-susp-contact">
                    <i class="bi bi-envelope"></i> <?= e($tenantEmail) ?>
                </a>
                <?php endif; ?>
                <?php if (!empty($tenantAddress)): ?>
                <div class="pub-susp-contact" style="cursor:default;">
                    <i class="bi bi-geo-alt"></i> <?= e($tenantAddress) ?>
                </div>
                <?php endif; ?>
                <?php if (empty($tenantPhone) && empty($tenantEmail) && empty($tenantAddress)): ?>
                <p class="pub-susp-text" style="margin:0;">Contatta il ristorante per informazioni.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="pub-susp-footer">
            &copy; <?= date('Y') ?> Evulery &middot; by alagias. - Soluzioni per il web
        </div>
    </div>
</body>
</html>
