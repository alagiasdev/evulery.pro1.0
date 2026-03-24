<div class="bw-conf-card">
    <div class="bw-conf-header bw-conf-header--cancel">
        <div class="bw-conf-check">
            <i class="bi bi-x-lg"></i>
        </div>
        <div class="bw-conf-title">Pagamento non completato</div>
        <div class="bw-conf-subtitle">La prenotazione non è stata confermata</div>
    </div>

    <div class="bw-conf-body">
        <div class="bw-conf-note bw-conf-note--warn">
            <i class="bi bi-exclamation-triangle"></i>
            <span>Il pagamento della caparra non è stato completato. La prenotazione verrà annullata automaticamente se il pagamento non viene effettuato entro 30 minuti.</span>
        </div>

        <div class="bw-conf-actions">
            <a href="<?= url($tenant['slug'] ?? '') ?>" class="bw-conf-btn-primary bw-conf-btn--orange">
                <i class="bi bi-arrow-repeat"></i> Riprova la prenotazione
            </a>
            <?php if (!empty($tenant['phone'])): ?>
            <a href="tel:<?= e($tenant['phone']) ?>" class="bw-conf-btn-secondary bw-conf-btn--muted">
                <i class="bi bi-telephone"></i> Contatta il ristorante
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="bw-conf-footer">
        &copy; <?= date('Y') ?> Evulery &middot; by alagias. - Soluzioni per il web
    </div>
</div>
