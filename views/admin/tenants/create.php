<h2 class="mb-4">Nuovo Ristorante</h2>

<div class="row">
    <div class="col-lg-8">
        <form method="POST" action="<?= url('admin/tenants') ?>">
            <?= csrf_field() ?>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Dati Ristorante</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome ristorante *</label>
                            <input type="text" class="form-control" name="name" value="<?= old('name') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email ristorante *</label>
                            <input type="email" class="form-control" name="email" value="<?= old('email') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telefono</label>
                            <input type="text" class="form-control" name="phone" value="<?= old('phone') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Piano</label>
                            <select class="form-select" name="plan">
                                <option value="base">Base (49&euro;/mese)</option>
                                <option value="deposit">Deposit (79&euro;/mese)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Indirizzo</label>
                            <input type="text" class="form-control" name="address" value="<?= old('address') ?>">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                                <label class="form-check-label" for="is_active">Attivo subito</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Account Proprietario</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome *</label>
                            <input type="text" class="form-control" name="owner_first_name" value="<?= old('owner_first_name') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cognome *</label>
                            <input type="text" class="form-control" name="owner_last_name" value="<?= old('owner_last_name') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email login *</label>
                            <input type="email" class="form-control" name="owner_email" value="<?= old('owner_email') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password *</label>
                            <input type="password" class="form-control" name="owner_password" required minlength="8">
                            <small class="text-muted">Minimo 8 caratteri</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i> Crea Ristorante
                </button>
                <a href="<?= url('admin/tenants') ?>" class="btn btn-outline-secondary">Annulla</a>
            </div>
        </form>
    </div>
</div>
