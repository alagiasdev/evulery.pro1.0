<div class="card booking-card">
    <div class="card-body text-center py-5">
        <i class="bi bi-x-circle text-warning" style="font-size: 4rem;"></i>
        <h3 class="mt-3">Pagamento annullato</h3>
        <p class="text-muted">La prenotazione non è stata confermata perché il pagamento è stato annullato.</p>
        <a href="<?= url($tenant['slug'] ?? '') ?>" class="btn btn-primary mt-3">
            Riprova
        </a>
    </div>
</div>
