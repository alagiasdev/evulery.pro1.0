<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Admin') ?> - <?= e(env('APP_NAME', 'Evulery')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="bg-dark text-white" id="sidebar" style="width: 250px; min-height: 100vh;">
            <div class="p-3 border-bottom border-secondary">
                <h5 class="mb-0"><?= e(env('APP_NAME', 'Evulery')) ?></h5>
                <small class="text-warning">Super Admin</small>
            </div>
            <nav class="p-3">
                <ul class="nav flex-column">
                    <li class="nav-item mb-1">
                        <a class="nav-link text-white <?= ($activeMenu ?? '') === 'admin-home' ? 'active bg-primary rounded' : '' ?>" href="<?= url('admin') ?>">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link text-white <?= ($activeMenu ?? '') === 'tenants' ? 'active bg-primary rounded' : '' ?>" href="<?= url('admin/tenants') ?>">
                            <i class="bi bi-shop me-2"></i> Ristoranti
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link text-white <?= ($activeMenu ?? '') === 'subscriptions' ? 'active bg-primary rounded' : '' ?>" href="<?= url('admin/subscriptions') ?>">
                            <i class="bi bi-credit-card-2-front me-2"></i> Abbonamenti
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

        <!-- Page Content -->
        <div class="flex-grow-1">
            <nav class="navbar navbar-light bg-white shadow-sm px-4">
                <span class="navbar-brand mb-0">Admin Panel</span>
                <span class="navbar-text"><?= e(auth()['name'] ?? '') ?></span>
            </nav>
            <div class="p-4">
                <?php partial('flash-messages'); ?>
                <?= $content ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
