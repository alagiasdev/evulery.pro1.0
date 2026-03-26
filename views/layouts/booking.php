<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#00844A">
    <title>Prenota un tavolo - <?= e($tenantName ?? env('APP_NAME', 'Evulery')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    <link href="<?= asset('css/booking-widget.css') ?>" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                <!-- Header -->
                <div class="text-center mb-4">
                    <?php if (!empty($tenantLogo)): ?>
                        <img src="<?= e($tenantLogo) ?>" alt="<?= e($tenantName ?? '') ?>" class="mb-3" style="max-height: 80px;">
                    <?php endif; ?>
                    <h2 class="fw-bold"><?= e($tenantName ?? 'Ristorante') ?></h2>
                    <p class="text-muted mb-0">Prenota un tavolo</p>
                    <?php if (!empty($petFriendly) || !empty($kidsFriendly)): ?>
                    <div class="bw-badges">
                        <?php if (!empty($petFriendly)): ?>
                        <span class="bw-badge bw-badge--pet"><i class="bi bi-emoji-heart-eyes"></i> Pet Friendly</span>
                        <?php endif; ?>
                        <?php if (!empty($kidsFriendly)): ?>
                        <span class="bw-badge bw-badge--kids"><i class="bi bi-balloon"></i> Kids Friendly</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <?php partial('flash-messages'); ?>
                <?= $content ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?= asset('js/booking-widget.js') ?>"></script>
</body>
</html>
