<div class="card booking-card">
    <div class="card-body text-center py-5">
        <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
        <h3 class="mt-3"><?= e($message ?? 'Prenotazione Confermata!') ?></h3>
        <p class="text-muted">Riceverai una conferma via email.</p>
        <div class="d-flex flex-column align-items-center gap-2 mt-3">
            <a href="<?= url($tenant['slug'] ?? '') ?>" class="btn btn-outline-primary">
                Prenota un altro tavolo
            </a>
            <?php if (!empty($tenant['menu_enabled'])): ?>
            <a href="<?= url(($tenant['slug'] ?? '') . '/menu') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-book me-1"></i> Consulta il menù
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>
