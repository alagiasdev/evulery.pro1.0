<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tenantName) ?></title>
    <meta name="theme-color" content="#00844A">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            margin: 0;
            background: #f5f6f8;
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            color: #1a1d23;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 1rem;
        }
        .un-card {
            background: #fff; max-width: 460px; width: 100%;
            border-radius: 18px; padding: 2rem 1.5rem;
            box-shadow: 0 8px 28px rgba(0,0,0,.06);
            text-align: center;
        }
        .un-icon {
            width: 64px; height: 64px; border-radius: 50%;
            background: #E8F5E9; color: #00844A;
            display: inline-flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem; font-size: 1.6rem;
        }
        .un-title { font-size: 1.4rem; font-weight: 800; color: #1a1d23; margin: 0 0 .35rem; }
        .un-sub { font-size: .92rem; color: #6c757d; margin: 0 0 1.5rem; line-height: 1.5; }
        .un-cta {
            display: inline-flex; align-items: center; gap: .5rem;
            background: #00844A; color: #fff;
            padding: .85rem 1.5rem; border-radius: 10px;
            text-decoration: none; font-weight: 700; font-size: 1rem;
            box-shadow: 0 4px 14px rgba(0,132,74,.25);
            transition: transform .12s, box-shadow .12s;
        }
        .un-cta:hover { color: #fff; transform: translateY(-1px); box-shadow: 0 6px 18px rgba(0,132,74,.32); }
        .un-contacts { margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid #f0f0f0; font-size: .85rem; color: #6c757d; }
        .un-contacts a { color: #00844A; text-decoration: none; font-weight: 600; }
        .un-contacts a:hover { text-decoration: underline; }
        .un-contacts-row { margin-top: .35rem; display: flex; align-items: center; justify-content: center; gap: .35rem; }
        .un-footer { margin-top: 1.25rem; font-size: .7rem; color: #adb5bd; }
    </style>
</head>
<body>
    <div class="un-card">
        <div class="un-icon"><i class="bi bi-calendar-check"></i></div>
        <h1 class="un-title"><?= e($tenantName) ?></h1>
        <p class="un-sub">
            Per prenotare un tavolo o vedere tutte le informazioni del ristorante,
            usa il bottone qui sotto.
        </p>
        <a href="<?= e($bookingUrl) ?>" class="un-cta">
            <i class="bi bi-calendar-check"></i>
            Prenota un tavolo
        </a>

        <?php if ($tenantPhone || $tenantEmail || $tenantAddress): ?>
        <div class="un-contacts">
            <?php if ($tenantPhone): ?>
            <div class="un-contacts-row">
                <i class="bi bi-telephone"></i>
                <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $tenantPhone)) ?>"><?= e($tenantPhone) ?></a>
            </div>
            <?php endif; ?>
            <?php if ($tenantEmail): ?>
            <div class="un-contacts-row">
                <i class="bi bi-envelope"></i>
                <a href="mailto:<?= e($tenantEmail) ?>"><?= e($tenantEmail) ?></a>
            </div>
            <?php endif; ?>
            <?php if ($tenantAddress): ?>
            <div class="un-contacts-row">
                <i class="bi bi-geo-alt"></i>
                <span><?= e($tenantAddress) ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="un-footer">Powered by Evulery</div>
    </div>
</body>
</html>
