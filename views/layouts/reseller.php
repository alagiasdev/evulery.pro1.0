<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Area Reseller') ?> - <?= e(env('APP_NAME', 'Evulery')) ?></title>
    <link rel="icon" type="image/png" href="<?= asset('img/Favicon.png') ?>">
    <link rel="apple-touch-icon" href="<?= asset('img/Favicon.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    <link href="<?= asset('css/reseller.css') ?>" rel="stylesheet">
</head>
<body>

<?php
    // Counter lead "Da contattare" del reseller (per badge sidebar)
    $rsOpenLeadsCount = 0;
    try {
        $stmt = \App\Core\Database::getInstance()->prepare(
            "SELECT COUNT(*) FROM demo_requests
             WHERE assigned_reseller_id = :uid
               AND status NOT IN ('customer','lost')"
        );
        $stmt->execute(['uid' => auth()['id'] ?? 0]);
        $rsOpenLeadsCount = (int)$stmt->fetchColumn();
    } catch (\Throwable $e) { /* tabella non ancora migrata */ }
?>

<!-- Mobile header -->
<div class="rs-mobile-header d-md-none" id="rs-mobile-header">
    <button class="rs-mobile-toggle" id="rs-sidebar-toggle" type="button">
        <i class="bi bi-list"></i>
    </button>
    <span class="rs-mobile-title">Area Reseller</span>
    <a href="<?= url('reseller/profile') ?>" class="rs-mobile-avatar" style="text-decoration:none;">
        <div class="rs-topbar-avatar"><?= strtoupper(substr(auth()['name'] ?? 'R', 0, 1)) ?></div>
    </a>
</div>

<!-- Sidebar -->
<aside class="rs-sidebar" id="rs-sidebar">
    <!-- Mobile: close button -->
    <div class="d-md-none d-flex align-items-center justify-content-between" style="padding:.85rem 1.25rem;border-bottom:1px solid #2a2d33;">
        <span style="font-weight:700;font-size:1.05rem;">Area Reseller</span>
        <button class="btn btn-sm" id="rs-sidebar-close" type="button" style="color:#adb5bd;border:none;">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <!-- Desktop: brand -->
    <div class="rs-sidebar-brand d-none d-md-block">
        <img src="<?= asset('img/Logo_evulery_footer.png') ?>" alt="Evulery" class="rs-sidebar-brand-logo">
        <div class="rs-sidebar-brand-role">Area Reseller</div>
    </div>
    <nav class="rs-sidebar-nav">
        <div class="rs-sidebar-section">Principale</div>
        <a class="rs-sidebar-link <?= ($activeMenu ?? '') === 'reseller-home' ? 'active' : '' ?>" href="<?= url('reseller') ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="rs-sidebar-link <?= ($activeMenu ?? '') === 'reseller-leads' ? 'active' : '' ?>" href="<?= url('reseller/leads') ?>">
            <i class="bi bi-funnel"></i> I miei lead
            <?php if ($rsOpenLeadsCount > 0): ?>
                <span class="rs-sidebar-badge"><?= $rsOpenLeadsCount ?></span>
            <?php endif; ?>
        </a>

        <div class="rs-sidebar-section">Account</div>
        <a class="rs-sidebar-link <?= ($activeMenu ?? '') === 'reseller-profile' ? 'active' : '' ?>" href="<?= url('reseller/profile') ?>">
            <i class="bi bi-person"></i> Profilo
        </a>
    </nav>
    <div class="rs-sidebar-footer">
        <form method="POST" action="<?= url('auth/logout') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="rs-sidebar-logout">
                <i class="bi bi-box-arrow-left"></i> Esci
            </button>
        </form>
    </div>
</aside>

<!-- Overlay mobile -->
<div class="rs-sidebar-overlay" id="rs-sidebar-overlay"></div>

<!-- Main -->
<div class="rs-main">
    <div class="rs-topbar d-none d-md-flex">
        <div class="rs-topbar-title"><?= e($title ?? 'Area Reseller') ?></div>
        <a href="<?= url('reseller/profile') ?>" class="rs-topbar-user" style="text-decoration:none;color:inherit;">
            <span><?= e(auth()['name'] ?? 'Reseller') ?></span>
            <div class="rs-topbar-avatar"><?= strtoupper(substr(auth()['name'] ?? 'R', 0, 1)) ?></div>
        </a>
    </div>

    <div class="rs-page">
        <?php partial('flash-messages'); ?>
        <?= $content ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script nonce="<?= csp_nonce() ?>">
document.addEventListener('DOMContentLoaded', function() {
    var sidebar = document.getElementById('rs-sidebar');
    var overlay = document.getElementById('rs-sidebar-overlay');
    var toggleBtn = document.getElementById('rs-sidebar-toggle');
    var closeBtn = document.getElementById('rs-sidebar-close');

    function openSidebar() {
        if (sidebar) sidebar.classList.add('show');
        if (overlay) overlay.classList.add('show');
    }
    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('show');
        if (overlay) overlay.classList.remove('show');
    }
    if (toggleBtn) {
        toggleBtn.addEventListener('click', openSidebar);
        toggleBtn.addEventListener('touchend', function(e) { e.preventDefault(); openSidebar(); });
    }
    if (closeBtn) {
        closeBtn.addEventListener('click', closeSidebar);
        closeBtn.addEventListener('touchend', function(e) { e.preventDefault(); closeSidebar(); });
    }
    if (overlay) overlay.addEventListener('click', closeSidebar);
    if (sidebar) {
        sidebar.querySelectorAll('.rs-sidebar-link').forEach(function(link) {
            link.addEventListener('click', closeSidebar);
        });
    }
});
</script>
</body>
</html>
