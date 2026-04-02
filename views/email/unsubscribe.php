<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disiscrizione - Evulery</title>
    <style nonce="<?= csp_nonce() ?>">
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: #f5f6f8; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .unsub-card { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,.08); max-width: 480px; width: 90%; padding: 3rem 2rem; text-align: center; }
        .unsub-icon { width: 64px; height: 64px; border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: 1.5rem; }
        .unsub-icon.ok { background: #d1fae5; color: #00844A; }
        .unsub-icon.err { background: #fee2e2; color: #dc3545; }
        .unsub-title { font-size: 1.25rem; font-weight: 700; color: #1a1d23; margin-bottom: .75rem; }
        .unsub-text { font-size: .9rem; color: #6c757d; line-height: 1.6; margin-bottom: 1.5rem; }
        .unsub-footer { font-size: .75rem; color: #ced4da; border-top: 1px solid #f0f0f0; padding-top: 1.25rem; }
    </style>
</head>
<body>
    <div class="unsub-card">
        <?php if ($success): ?>
            <div class="unsub-icon ok">&#10003;</div>
            <h1 class="unsub-title">Ti sei disiscritto</h1>
            <p class="unsub-text">
                Non riceverai pi&ugrave; comunicazioni email da <strong><?= e($tenantName) ?></strong>.<br>
                Se hai cambiato idea, contatta direttamente il ristorante.
            </p>
        <?php else: ?>
            <div class="unsub-icon err">&#10007;</div>
            <h1 class="unsub-title">Link non valido</h1>
            <p class="unsub-text">
                Questo link di disiscrizione non &egrave; valido o &egrave; gi&agrave; stato utilizzato.
            </p>
        <?php endif; ?>
        <div class="unsub-footer">
            &copy; <?= date('Y') ?> Evulery &middot; by alagias. - Soluzioni per il web
        </div>
    </div>
</body>
</html>
