<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Dashboard') ?> - <?= e(env('APP_NAME', 'Evulery')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    <link href="<?= asset('css/dashboard.css') ?>" rel="stylesheet">
</head>
<body>
    <!-- Mobile Header -->
    <div class="mobile-header d-md-none" id="mobile-header">
        <button class="mobile-header-toggle" id="sidebar-toggle" type="button">
            <i class="bi bi-list"></i>
        </button>
        <span class="mobile-header-title"><?= e(tenant()['name'] ?? env('APP_NAME', 'Evulery')) ?></span>
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
                    <a class="nav-link <?= ($activeMenu ?? '') === 'menu' ? 'active' : '' ?>" href="<?= url('dashboard/menu') ?>">
                        <i class="bi bi-book me-2"></i> Menu
                    </a>
                </li>
                <li class="nav-item"><div class="sidebar-section">Impostazioni</div></li>
                <li class="nav-item">
                    <a class="nav-link <?= ($activeMenu ?? '') === 'settings' ? 'active' : '' ?>" href="<?= url('dashboard/settings') ?>">
                        <i class="bi bi-gear me-2"></i> Generali
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($activeMenu ?? '') === 'slots' ? 'active' : '' ?>" href="<?= url('dashboard/settings/slots') ?>">
                        <i class="bi bi-clock me-2"></i> Orari e Coperti
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($activeMenu ?? '') === 'meal-categories' ? 'active' : '' ?>" href="<?= url('dashboard/settings/meal-categories') ?>">
                        <i class="bi bi-tags me-2"></i> Categorie Pasto
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($activeMenu ?? '') === 'closures' ? 'active' : '' ?>" href="<?= url('dashboard/settings/closures') ?>">
                        <i class="bi bi-calendar-x me-2"></i> Chiusure
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($activeMenu ?? '') === 'promotions' ? 'active' : '' ?>" href="<?= url('dashboard/settings/promotions') ?>">
                        <i class="bi bi-percent me-2"></i> Promozioni
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($activeMenu ?? '') === 'deposit' ? 'active' : '' ?>" href="<?= url('dashboard/settings/deposit') ?>">
                        <i class="bi bi-cash me-2"></i> Caparra
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($activeMenu ?? '') === 'domain' ? 'active' : '' ?>" href="<?= url('dashboard/settings/domain') ?>">
                        <i class="bi bi-globe me-2"></i> Dominio
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
        <!-- Top Bar (desktop) -->
        <div class="top-bar d-none d-md-flex">
            <span class="top-bar-date">
                <i class="bi bi-calendar3 me-1"></i> <?= format_date(date('Y-m-d'), 'D d/m/Y') ?>
            </span>
            <div class="d-flex align-items-center gap-2">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?= asset('js/app.js') ?>"></script>
    <script nonce="<?= csp_nonce() ?>">
    document.addEventListener('click', function(e) {
        var row = e.target.closest('[data-url]');
        if (row && !e.target.closest('a, button, form')) {
            window.location = row.getAttribute('data-url');
        }
    });
    </script>
    <?php if (isset($pageScripts)): ?>
        <?php foreach ((array)$pageScripts as $script): ?>
        <script src="<?= asset($script) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>