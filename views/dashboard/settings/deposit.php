<h2 class="mb-4">Impostazioni Caparra</h2>

<div class="row">
    <div class="col-lg-8">
        <form method="POST" action="<?= url('dashboard/settings/deposit') ?>">
            <?= csrf_field() ?>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="deposit_enabled" id="deposit_enabled"
                                   <?= $tenant['deposit_enabled'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="deposit_enabled">
                                <strong>Richiedi caparra</strong>
                            </label>
                        </div>
                        <small class="text-muted">Se attivato, i clienti dovranno versare una caparra per confermare la prenotazione.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Importo caparra (&euro;)</label>
                        <input type="number" class="form-control" name="deposit_amount" style="max-width: 200px;"
                               value="<?= $tenant['deposit_amount'] ? number_format($tenant['deposit_amount'], 2, '.', '') : '' ?>"
                               min="1" step="0.50" placeholder="es. 10.00">
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
            <div class="card-header"><h6 class="mb-0">Info</h6></div>
            <div class="card-body">
                <p class="text-muted small mb-0">
                    La caparra viene raccolta tramite Stripe Checkout. Il cliente viene reindirizzato
                    alla pagina di pagamento sicura dopo aver compilato il form di prenotazione.
                    Il pagamento viene confermato automaticamente tramite webhook.
                </p>
            </div>
        </div>
    </div>
</div>
