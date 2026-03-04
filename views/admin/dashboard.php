<h2 class="mb-4">Dashboard</h2>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stat-card border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1">Ristoranti Totali</h6>
                        <h3 class="mb-0"><?= (int)$totalTenants ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-shop fs-1 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1">Attivi</h6>
                        <h3 class="mb-0"><?= (int)$activeTenants ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-check-circle fs-1 text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1">Prenotazioni Oggi</h6>
                        <h3 class="mb-0"><?= (int)$todayReservations ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-calendar-check fs-1 text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card border-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1">Utenti</h6>
                        <h3 class="mb-0"><?= (int)$totalUsers ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-people fs-1 text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Azioni rapide</h5>
    </div>
    <div class="card-body">
        <a href="<?= url('admin/tenants/create') ?>" class="btn btn-primary me-2">
            <i class="bi bi-plus-circle me-1"></i> Nuovo Ristorante
        </a>
        <a href="<?= url('admin/tenants') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-list me-1"></i> Gestisci Ristoranti
        </a>
    </div>
</div>
