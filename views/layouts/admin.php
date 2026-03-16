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

<!-- Sidebar -->
<div class="admin-sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-name"><?= e(env('APP_NAME', 'Evulery')) ?></div>
        <div class="sidebar-brand-role">Super Admin</div>
    </div>
    <nav class="sidebar-nav">
        <a class="sidebar-link <?= ($activeMenu ?? '') === 'admin-home' ? 'active' : '' ?>" href="<?= url('admin') ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="sidebar-link <?= ($activeMenu ?? '') === 'tenants' ? 'active' : '' ?>" href="<?= url('admin/tenants') ?>">
            <i class="bi bi-shop"></i> Ristoranti
        </a>
        <a class="sidebar-link <?= ($activeMenu ?? '') === 'subscriptions' ? 'active' : '' ?>" href="<?= url('admin/subscriptions') ?>">
            <i class="bi bi-credit-card-2-front"></i> Abbonamenti
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

<!-- Main -->
<div class="admin-main">
    <div class="admin-topbar">
        <div class="topbar-title">Admin Panel</div>
        <div class="topbar-user">
            <span><?= e(auth()['name'] ?? 'Admin') ?></span>
            <div class="topbar-avatar"><?= strtoupper(substr(auth()['name'] ?? 'A', 0, 1)) ?></div>
        </div>
    </div>

    <div class="admin-page">
        <?php partial('flash-messages'); ?>
        <?= $content ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>