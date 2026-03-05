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
    <!-- Mobile Header (solo mobile) -->
    <div class="mobile-header d-md-none" id="mobile-header">
        <button class="mobile-header-toggle" id="sidebar-toggle" type="button">
            <i class="bi bi-list"></i>
        </button>
        <span class="mobile-header-title"><?= e(tenant()['name'] ?? env('APP_NAME', 'Evulery')) ?></span>
    </div>

    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="bg-dark text-white" id="sidebar" style="width: 250px; min-height: 100vh;">
            <div class="p-3 border-bottom border-secondary d-none d-md-block">
                <h5 class="mb-0"><?= e(tenant()['name'] ?? env('APP_NAME', 'Evulery')) ?></h5>
                <small class="text-muted"><?= e(auth()['name'] ?? '') ?></small>
            </div>
            <!-- Mobile: close button inside sidebar -->
            <div class="p-3 border-bottom border-secondary d-md-none d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-0"><?= e(tenant()['name'] ?? env('APP_NAME', 'Evulery')) ?></h5>
                    <small class="text-muted"><?= e(auth()['name'] ?? '') ?></small>
                </div>
                <button class="btn btn-outline-light btn-sm" id="sidebar-close" type="button">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <nav class="p-3">
                <ul class="nav flex-column">
                    <li class="nav-item mb-1">
                        <a class="nav-link text-white <?= ($activeMenu ?? '') === 'home' ? 'active bg-primary rounded' : '' ?>" href="<?= url('dashboard') ?>">
                            <i class="bi bi-house-door me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link text-white <?= ($activeMenu ?? '') === 'reservations' ? 'active bg-primary rounded' : '' ?>" href="<?= url('dashboard/reservations') ?>">
                            <i class="bi bi-calendar-check me-2"></i> Prenotazioni
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link text-white <?= ($activeMenu ?? '') === 'customers' ? 'active bg-primary rounded' : '' ?>" href="<?= url('dashboard/customers') ?>">
                            <i class="bi bi-people me-2"></i> Clienti
                        </a>
                    </li>
                    <li class="nav-item mt-3 mb-1">
                        <small class="text-uppercase text-muted px-3">Impostazioni</small>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link text-white <?= ($activeMenu ?? '') === 'settings' ? 'active bg-primary rounded' : '' ?>" href="<?= url('dashboard/settings') ?>">
                            <i class="bi bi-gear me-2"></i> Generali
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link text-white <?= ($activeMenu ?? '') === 'slots' ? 'active bg-primary rounded' : '' ?>" href="<?= url('dashboard/settings/slots') ?>">
                            <i class="bi bi-clock me-2"></i> Orari e Coperti
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link text-white <?= ($activeMenu ?? '') === 'meal-categories' ? 'active bg-primary rounded' : '' ?>" href="<?= url('dashboard/settings/meal-categories') ?>">
                            <i class="bi bi-list-columns me-2"></i> Categorie Pasto
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link text-white <?= ($activeMenu ?? '') === 'deposit' ? 'active bg-primary rounded' : '' ?>" href="<?= url('dashboard/settings/deposit') ?>">
                            <i class="bi bi-credit-card me-2"></i> Caparra
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link text-white <?= ($activeMenu ?? '') === 'domain' ? 'active bg-primary rounded' : '' ?>" href="<?= url('dashboard/settings/domain') ?>">
                            <i class="bi bi-globe me-2"></i> Dominio
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="p-3 mt-auto border-top border-secondary">
                <form method="POST" action="<?= url('auth/logout') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-light btn-sm w-100">
                        <i class="bi bi-box-arrow-right me-1"></i> Esci
                    </button>
                </form>
            </div>
        </div>

        <!-- Sidebar Overlay (mobile) -->
        <div class="sidebar-overlay" id="sidebar-overlay"></div>

        <!-- Page Content -->
        <div class="flex-grow-1">
            <!-- Top Bar (desktop only) -->
            <nav class="navbar navbar-light bg-white shadow-sm px-4 d-none d-md-flex">
                <span class="navbar-text">
                    <i class="bi bi-calendar3 me-1"></i> <?= date('d/m/Y') ?>
                </span>
                <div>
                    <a href="<?= url(tenant()['slug'] ?? '') ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-eye me-1"></i> Vedi pagina prenotazione
                    </a>
                </div>
            </nav>

            <!-- Main Content -->
            <div class="p-4">
                <?php partial('flash-messages'); ?>
                <?= $content ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?= asset('js/app.js') ?>"></script>
    <script>
    document.addEventListener('click', function(e) {
        var row = e.target.closest('[data-url]');
        if (row && !e.target.closest('a, button')) {
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
