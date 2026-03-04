<div class="card booking-card">
    <div class="card-body text-center py-5">
        <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
        <h3 class="mt-3"><?= e($message ?? 'Prenotazione Confermata!') ?></h3>
        <p class="text-muted">Riceverai una conferma via email.</p>
        <a href="<?= url($tenant['slug'] ?? '') ?>" class="btn btn-outline-primary mt-3">
            Prenota un altro tavolo
        </a>
    </div>
</div>
