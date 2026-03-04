<h2 class="mb-4">Impostazioni Generali</h2>

<div class="row">
    <div class="col-lg-8">
        <form method="POST" action="<?= url('dashboard/settings') ?>">
            <?= csrf_field() ?>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome ristorante</label>
                            <input type="text" class="form-control" name="name" value="<?= e($tenant['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?= e($tenant['email']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telefono</label>
                            <input type="text" class="form-control" name="phone" value="<?= e($tenant['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
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
                        <div class="col-12">
                            <label class="form-label">Politica di cancellazione</label>
                            <textarea class="form-control" name="cancellation_policy" rows="3"><?= e($tenant['cancellation_policy'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle me-1"></i> Salva
            </button>
        </form>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Link prenotazione</h6></div>
            <div class="card-body">
                <p class="mb-1"><strong>Pagina hosted:</strong></p>
                <code class="d-block mb-3"><?= e(url($tenant['slug'])) ?></code>
                <p class="mb-1"><strong>Embed (iframe):</strong></p>
                <code class="d-block" style="word-break: break-all; font-size: 0.75rem;">&lt;iframe src="<?= e(url($tenant['slug'] . '?embed=1')) ?>" width="100%" height="600" frameborder="0"&gt;&lt;/iframe&gt;</code>
            </div>
        </div>
    </div>
</div>
