<h2 class="mb-4">Dominio Personalizzato</h2>

<div class="row">
    <div class="col-lg-8">
        <form method="POST" action="<?= url('dashboard/settings/domain') ?>">
            <?= csrf_field() ?>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Dominio personalizzato</label>
                        <input type="text" class="form-control" name="custom_domain"
                               value="<?= e($tenant['custom_domain'] ?? '') ?>"
                               placeholder="es. prenota.ristorante.it">
                        <small class="text-muted">Lascia vuoto per rimuovere il dominio personalizzato.</small>
                    </div>

                    <?php if ($tenant['custom_domain']): ?>
                    <div class="mb-3">
                        <strong>Stato:</strong>
                        <?php if ($tenant['domain_status'] === 'linked'): ?>
                            <span class="badge bg-success">Collegato</span>
                        <?php elseif ($tenant['domain_status'] === 'dns_pending'): ?>
                            <span class="badge bg-warning">In attesa DNS</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Non configurato</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($tenant['cname_target']): ?>
                    <div class="alert alert-info">
                        <strong>Configura il DNS:</strong><br>
                        Crea un record <strong>CNAME</strong> per <code><?= e($tenant['custom_domain']) ?></code>
                        che punta a <code><?= e($tenant['cname_target']) ?></code>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i> Salva
                </button>
                <?php if ($tenant['custom_domain'] && $tenant['domain_status'] !== 'linked'): ?>
                <button type="submit" formaction="<?= url('dashboard/settings/domain/verify') ?>" class="btn btn-outline-success">
                    <i class="bi bi-arrow-repeat me-1"></i> Verifica DNS
                </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
