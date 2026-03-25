<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Admin') ?> - <?= e(env('APP_NAME', 'Evulery')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    <link href="<?= asset('css/admin.css') ?>" rel="stylesheet">
</head>
<body>

<!-- Mobile Header (same pattern as restaurant dashboard) -->
<div class="admin-mobile-header d-md-none" id="admin-mobile-header">
    <button class="admin-mobile-toggle" id="admin-sidebar-toggle" type="button">
        <i class="bi bi-list"></i>
    </button>
    <span class="admin-mobile-title">Admin Panel</span>
    <a href="<?= url('admin/profile') ?>" class="admin-mobile-avatar" style="text-decoration:none;">
        <div class="topbar-avatar"><?= strtoupper(substr(auth()['name'] ?? 'A', 0, 1)) ?></div>
    </a>
</div>

<!-- Sidebar -->
<div class="admin-sidebar" id="admin-sidebar">
    <!-- Mobile: close button -->
    <div class="d-md-none d-flex align-items-center justify-content-between" style="padding:.85rem 1.25rem;border-bottom:1px solid #333;">
        <span style="font-weight:700;font-size:1.05rem;">Admin Panel</span>
        <button class="btn btn-sm" id="admin-sidebar-close" type="button" style="color:#adb5bd;border:none;">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <!-- Desktop: brand -->
    <div class="sidebar-brand d-none d-md-block">
        <div class="sidebar-brand-name"><?= e(env('APP_NAME', 'Evulery')) ?></div>
        <div class="sidebar-brand-role">Super Admin</div>
    </div>
    <nav class="sidebar-nav">
        <div class="sidebar-section">Principale</div>
        <a class="sidebar-link <?= ($activeMenu ?? '') === 'admin-home' ? 'active' : '' ?>" href="<?= url('admin') ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="sidebar-link <?= ($activeMenu ?? '') === 'tenants' ? 'active' : '' ?>" href="<?= url('admin/tenants') ?>">
            <i class="bi bi-shop"></i> Ristoranti
        </a>
        <a class="sidebar-link <?= ($activeMenu ?? '') === 'users' ? 'active' : '' ?>" href="<?= url('admin/users') ?>">
            <i class="bi bi-people"></i> Utenti
        </a>

        <div class="sidebar-section">Business</div>
        <a class="sidebar-link <?= ($activeMenu ?? '') === 'subscriptions' ? 'active' : '' ?>" href="<?= url('admin/subscriptions') ?>">
            <i class="bi bi-credit-card-2-front"></i> Abbonamenti
        </a>

        <div class="sidebar-section">Sistema</div>
        <a class="sidebar-link <?= ($activeMenu ?? '') === 'activity-log' ? 'active' : '' ?>" href="<?= url('admin/activity-log') ?>">
            <i class="bi bi-clock-history"></i> Log Attivit&agrave;
        </a>
        <a class="sidebar-link <?= ($activeMenu ?? '') === 'settings' ? 'active' : '' ?>" href="<?= url('admin') ?>">
            <i class="bi bi-gear"></i> Impostazioni
        </a>
    </nav>
    <div class="sidebar-footer">
        <form method="POST" action="<?= url('auth/logout') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="sidebar-logout">
                <i class="bi bi-box-arrow-left"></i> Esci
            </button>
        </form>
    </div>
</div>

<!-- Sidebar Overlay (mobile) -->
<div class="admin-sidebar-overlay" id="admin-sidebar-overlay"></div>

<!-- Main -->
<div class="admin-main">
    <div class="admin-topbar d-none d-md-flex">
        <div class="topbar-title">Admin Panel</div>
        <a href="<?= url('admin/profile') ?>" class="topbar-user" style="text-decoration:none;color:inherit;">
            <span><?= e(auth()['name'] ?? 'Admin') ?></span>
            <div class="topbar-avatar"><?= strtoupper(substr(auth()['name'] ?? 'A', 0, 1)) ?></div>
        </a>
    </div>

    <div class="admin-page">
        <?php partial('flash-messages'); ?>
        <?= $content ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
(function() {
    var sidebar = document.getElementById('admin-sidebar');
    var overlay = document.getElementById('admin-sidebar-overlay');
    var toggleBtn = document.getElementById('admin-sidebar-toggle');
    var closeBtn = document.getElementById('admin-sidebar-close');

    function openSidebar() {
        if (sidebar) sidebar.classList.add('show');
        if (overlay) overlay.classList.add('show');
    }

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('show');
        if (overlay) overlay.classList.remove('show');
    }

    if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);

    // Close sidebar on nav link click (mobile)
    if (sidebar) {
        sidebar.querySelectorAll('.sidebar-link').forEach(function(link) {
            link.addEventListener('click', closeSidebar);
        });
    }
})();
</script>
</body>
</html>
