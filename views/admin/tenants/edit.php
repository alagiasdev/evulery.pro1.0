<h2 class="mb-4">Modifica: <?= e($tenant['name']) ?></h2>

<div class="row">
    <div class="col-lg-8">
        <form method="POST" action="<?= url("admin/tenants/{$tenant['id']}") ?>">
            <?= csrf_field() ?>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Dati Ristorante</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome *</label>
                            <input type="text" class="form-control" name="name" value="<?= e($tenant['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" value="<?= e($tenant['email']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telefono</label>
                            <input type="text" class="form-control" name="phone" value="<?= e($tenant['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Piano</label>
                            <select class="form-select" name="plan">
                                <option value="base" <?= $tenant['plan'] === 'base' ? 'selected' : '' ?>>Base</option>
                                <option value="deposit" <?= $tenant['plan'] === 'deposit' ? 'selected' : '' ?>>Deposit</option>
                                <option value="custom" <?= $tenant['plan'] === 'custom' ? 'selected' : '' ?>>Custom</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Indirizzo</label>
                            <input type="text" class="form-control" name="address" value="<?= e($tenant['address'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Durata tavolo (min)</label>
                            <input type="number" class="form-control" name="table_duration" value="<?= (int)$tenant['table_duration'] ?>" min="30" step="15">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Step orari (min)</label>
                            <input type="number" class="form-control" name="time_step" value="<?= (int)$tenant['time_step'] ?>" min="15" step="15">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Slug</label>
                            <input type="text" class="form-control" value="<?= e($tenant['slug']) ?>" disabled>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= $tenant['is_active'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">Attivo</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i> Salva Modifiche
                </button>
                <a href="<?= url('admin/tenants') ?>" class="btn btn-outline-secondary">Torna alla lista</a>
            </div>
        </form>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Utenti associati</h6></div>
            <div class="card-body">
                <?php if (empty($users)): ?>
                    <p class="text-muted mb-0">Nessun utente.</p>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <strong><?= e($u['first_name'] . ' ' . $u['last_name']) ?></strong><br>
                            <small class="text-muted"><?= e($u['email']) ?></small>
                        </div>
                        <span class="badge bg-<?= $u['is_active'] ? 'success' : 'secondary' ?>"><?= $u['is_active'] ? 'Attivo' : 'Inattivo' ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h6 class="mb-0">Link prenotazione</h6></div>
            <div class="card-body">
                <code><?= e(url($tenant['slug'])) ?></code>
            </div>
        </div>
    </div>
</div>
