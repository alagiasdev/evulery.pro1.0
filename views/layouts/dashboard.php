<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Dashboard') ?> - <?= e(env('APP_NAME', 'Evulery')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    <link href="<?= asset('css/dashboard.css') ?>" rel="stylesheet">
    <?php if (isset($pageStyles)): ?>
        <?php foreach ((array)$pageStyles as $style): ?>
        <link href="<?= asset($style) ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#00844A">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Evulery">
    <link rel="apple-touch-icon" href="<?= asset('img/icon-192.png') ?>">
</head>
<body>
    <!-- Mobile Header -->
    <div class="mobile-header d-md-none" id="mobile-header">
        <button class="mobile-header-toggle" id="sidebar-toggle" type="button">
            <i class="bi bi-list"></i>
        </button>
        <span class="mobile-header-title"><?= e(tenant()['name'] ?? env('APP_NAME', 'Evulery')) ?></span>
        <?php if (tenant_can('push_notifications')): ?>
        <button class="notif-bell-btn notif-bell-btn--mobile" id="notif-bell-btn-mobile" type="button" title="Notifiche">
            <i class="bi bi-bell"></i>
            <span class="notif-badge" id="notif-badge-mobile" style="display:none;">0</span>
        </button>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div id="sidebar">
        <div class="sidebar-brand d-none d-md-block"><?= e(tenant()['name'] ?? env('APP_NAME', 'Evulery')) ?></div>
        <a href="<?= url('dashboard/profile') ?>" class="sidebar-user d-none d-md-block"><?= e(auth()['name'] ?? '') ?> <i class="bi bi-pencil-square" style="font-size:.7rem;opacity:.5;margin-left:.25rem;"></i></a>
        <!-- Mobile: close button -->
        <div class="d-md-none d-flex align-items-center justify-content-between" style="padding:.85rem 1rem;border-bottom:1px solid #333;">
            <span style="font-weight:700;font-size:1.05rem;"><?= e(tenant()['name'] ?? env('APP_NAME', 'Evulery')) ?></span>
            <button class="btn btn-sm" id="sidebar-close" type="button" style="color:#adb5bd;border:none;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <nav style="padding:.5rem 0;">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?= ($activeMenu ?? '') === 'home' ? 'active' : '' ?>" href="<?= url('dashboard') ?>">
                        <i class="bi bi-house-door me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($activeMenu ?? '') === 'reservations' ? 'active' : '' ?>" href="<?= url('dashboard/reservations') ?>">
                        <i class="bi bi-calendar-check me-2"></i> Prenotazioni
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($activeMenu ?? '') === 'customers' ? 'active' : '' ?>" href="<?= url('dashboard/customers') ?>">
                        <i class="bi bi-people me-2"></i> Clienti
                    </a>
                </li>
                <li class="nav-item">
                    <?php if (tenant_can('digital_menu')): ?>
                    <a class="nav-link <?= ($activeMenu ?? '') === 'menu' ? 'active' : '' ?>" href="<?= url('dashboard/menu') ?>">
                        <i class="bi bi-book me-2"></i> Menu
                    </a>
                    <?php else: ?>
                    <a class="nav-link" href="<?= url('dashboard/menu') ?>" style="opacity:.5;">
                        <i class="bi bi-book me-2"></i> Menu <i class="bi bi-lock-fill ms-auto" style="font-size:.7rem;"></i>
                    </a>
                    <?php endif; ?>
                </li>
                <?php if (tenant_can('online_ordering')): ?>
                <li class="nav-item">
                    <a class="nav-link <?= ($activeMenu ?? '') === 'orders' ? 'active' : '' ?>" href="<?= url('dashboard/orders') ?>">
                        <i class="bi bi-bag-check me-2"></i> Ordini
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <?php if (tenant_can('email_broadcast')): ?>
                    <a class="nav-link <?= ($activeMenu ?? '') === 'communications' ? 'active' : '' ?>" href="<?= url('dashboard/communications') ?>">
                        <i class="bi bi-envelope me-2"></i> Comunicazioni
                    </a>
                    <?php else: ?>
                    <a class="nav-link" href="<?= url('dashboard/communications') ?>" style="opacity:.5;">
                        <i class="bi bi-envelope me-2"></i> Comunicazioni <i class="bi bi-lock-fill ms-auto" style="font-size:.7rem;"></i>
                    </a>
                    <?php endif; ?>
                </li>
                <?php if (tenant_can('review_management')): ?>
                <li class="nav-item">
                    <a class="nav-link <?= ($activeMenu ?? '') === 'reputation' ? 'active' : '' ?>" href="<?= url('dashboard/reputation') ?>">
                        <i class="bi bi-star me-2"></i> Reputazione
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link <?= ($activeMenu ?? '') === 'notifications' ? 'active' : '' ?>" href="<?= url('dashboard/notifications') ?>">
                        <i class="bi bi-bell me-2"></i> Notifiche
                    </a>
                </li>
                <?php $settingsKeys = ['settings','slots','meal-categories','closures','promotions','settings-notifications','deposit','settings-ordering','settings-reviews','domain']; ?>
                <li class="nav-item">
                    <a class="nav-link <?= in_array($activeMenu ?? '', $settingsKeys) ? 'active' : '' ?>" href="<?= url('dashboard/settings') ?>">
                        <i class="bi bi-gear me-2"></i> Impostazioni
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($activeMenu ?? '') === 'help' ? 'active' : '' ?>" href="<?= url('dashboard/help') ?>">
                        <i class="bi bi-book me-2"></i> Guida
                    </a>
                </li>
            </ul>
        </nav>
        <div class="sidebar-logout">
            <form method="POST" action="<?= url('auth/logout') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn">
                    <i class="bi bi-box-arrow-left me-1"></i> Esci
                </button>
            </form>
        </div>
    </div>

    <!-- Sidebar Overlay (mobile) -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <!-- Main Content -->
    <div id="main-content">
        <?php if (\App\Core\Auth::isImpersonating()): ?>
        <div class="impersonation-bar">
            <i class="bi bi-person-badge"></i>
            Stai accedendo come <strong><?= e(auth()['name'] ?? '') ?></strong>
            <?php if (tenant()): ?>(<?= e(tenant()['name'] ?? '') ?>)<?php endif; ?>
            &mdash;
            <form method="POST" action="<?= url('dashboard/stop-impersonation') ?>" style="display:inline;">
                <?= csrf_field() ?>
                <button type="submit" style="background:none;border:none;color:#000;text-decoration:underline;cursor:pointer;font-size:.85rem;font-weight:700;">
                    <i class="bi bi-box-arrow-left"></i> Torna ad Admin
                </button>
            </form>
        </div>
        <?php endif; ?>
        <!-- Top Bar (desktop) -->
        <div class="top-bar d-none d-md-flex">
            <span class="top-bar-date">
                <i class="bi bi-calendar3 me-1"></i> <?= format_date(date('Y-m-d'), 'l d/m/Y') ?>
            </span>
            <div class="d-flex align-items-center gap-2">
                <?php if (tenant_can('push_notifications')): ?>
                <div class="notif-bell" id="notif-bell">
                    <button class="notif-bell-btn" id="notif-bell-btn" type="button" title="Notifiche">
                        <i class="bi bi-bell"></i>
                        <span class="notif-badge" id="notif-badge" style="display:none;">0</span>
                    </button>
                    <div class="notif-dropdown" id="notif-dropdown">
                        <div class="notif-dropdown-header">
                            <span>Notifiche</span>
                            <button type="button" id="notif-mark-all" class="notif-mark-all">Segna tutte lette</button>
                        </div>
                        <div class="notif-dropdown-body" id="notif-list">
                            <div class="notif-empty">Nessuna notifica</div>
                        </div>
                        <a href="<?= url('dashboard/notifications') ?>" class="notif-dropdown-footer">
                            Vedi tutte le notifiche
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                <a href="<?= url('dashboard/reservations/create') ?>" class="btn btn-brand btn-sm btn-nuova-desktop">
                    <i class="bi bi-plus-circle me-1"></i> Nuova Prenotazione
                </a>
                <a href="<?= url(tenant()['slug'] ?? '') ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-eye me-1"></i> Vedi pagina
                </a>
            </div>
        </div>

        <div class="page-body">
            <?php partial('flash-messages'); ?>
            <?= $content ?>
        </div>
    </div>

    <!-- FAB mobile -->
    <a href="<?= url('dashboard/reservations/create') ?>" class="fab d-md-none" title="Nuova Prenotazione">
        <i class="bi bi-plus-lg"></i>
    </a>

    <script nonce="<?= csp_nonce() ?>" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script nonce="<?= csp_nonce() ?>" src="<?= asset('js/app.js') ?>"></script>
    <?php if (tenant_can('push_notifications')): ?>
    <script nonce="<?= csp_nonce() ?>" src="<?= asset('js/dashboard-notifications.js') ?>" defer
            data-unread-url="<?= url('dashboard/notifications/unread') ?>"
            data-recent-url="<?= url('dashboard/notifications/recent') ?>"
            data-mark-read-url="<?= url('dashboard/notifications') ?>"
            data-mark-all-url="<?= url('dashboard/notifications/read-all') ?>"
            data-delete-all-url="<?= url('dashboard/notifications/delete-all') ?>"
            data-vapid-url="<?= url('dashboard/push/vapid-key') ?>"
            data-subscribe-url="<?= url('dashboard/push/subscribe') ?>"
            data-csrf="<?= csrf_token() ?>"
    ></script>
    <?php endif; ?>
    <script nonce="<?= csp_nonce() ?>">
    document.addEventListener('click', function(e) {
        var row = e.target.closest('[data-url]');
        if (row && !e.target.closest('a, button, form')) {
            window.location = row.getAttribute('data-url');
        }
    });
    document.addEventListener('submit', function(e) {
        var form = e.target.closest('[data-confirm]');
        if (form && !confirm(form.getAttribute('data-confirm'))) {
            e.preventDefault();
        }
    });
    </script>
    <?php if (isset($pageScripts)): ?>
        <?php foreach ((array)$pageScripts as $script): ?>
        <script nonce="<?= csp_nonce() ?>" src="<?= asset($script) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>