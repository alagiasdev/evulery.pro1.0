<div class="bw-conf-card">
    <div class="bw-conf-header bw-conf-header--cancel">
        <div class="bw-conf-check">
            <i class="bi bi-x-lg"></i>
        </div>
        <div class="bw-conf-title">Pagamento non completato</div>
        <div class="bw-conf-subtitle">La prenotazione non è stata confermata</div>
    </div>

    <div class="bw-conf-body">
        <?php if (!empty($canRetry) && !empty($reservation)): ?>
        <div class="bw-conf-note bw-conf-note--warn">
            <i class="bi bi-exclamation-triangle"></i>
            <span>Il pagamento della caparra non è stato completato. La prenotazione n. <strong><?= (int)$reservation['id'] ?></strong> è ancora in attesa.</span>
        </div>

        <div style="background:#f8f9fa;border:1px solid #e9ecef;border-radius:10px;padding:14px;margin:12px 0;font-size:.88rem;">
            <div style="margin-bottom:4px;"><strong><?= e($reservation['first_name'] . ' ' . $reservation['last_name']) ?></strong></div>
            <div><?= date('d/m/Y', strtotime($reservation['reservation_date'])) ?> alle <?= substr($reservation['reservation_time'], 0, 5) ?> &mdash; <?= (int)$reservation['party_size'] ?> persone</div>
            <div style="margin-top:4px;color:#E65100;font-weight:600;">Caparra: &euro;<?= number_format((float)$reservation['deposit_amount'], 2, ',', '.') ?></div>
        </div>

        <div class="bw-conf-actions">
            <button type="button" id="btn-retry-payment" class="bw-conf-btn-primary"
                    data-api="<?= e(url('api/v1/tenants/' . $tenant['slug'] . '/reservations/' . (int)$reservation['id'] . '/retry-payment')) ?>"
                    style="border:none;cursor:pointer;">
                <i class="bi bi-credit-card"></i> Paga la caparra adesso
            </button>
            <a href="<?= url($tenant['slug'] ?? '') ?>" class="bw-conf-btn-secondary bw-conf-btn--muted">
                <i class="bi bi-arrow-repeat"></i> Nuova prenotazione
            </a>
        </div>

        <script nonce="<?= csp_nonce() ?>">
        document.getElementById('btn-retry-payment').addEventListener('click', function() {
            var btn = this;
            var apiUrl = btn.getAttribute('data-api');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Creazione pagamento...';

            fetch(apiUrl, { method: 'POST', headers: { 'Content-Type': 'application/json' } })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success && data.data && data.data.stripe_checkout_url) {
                        window.location.href = data.data.stripe_checkout_url;
                    } else {
                        var msg = (data.error && data.error.message) ? data.error.message : 'Errore. Riprova.';
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-credit-card"></i> Paga la caparra adesso';
                        alert(msg);
                    }
                })
                .catch(function() {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-credit-card"></i> Paga la caparra adesso';
                    alert('Errore di connessione. Riprova.');
                });
        });
        </script>

        <?php else: ?>
        <div class="bw-conf-note bw-conf-note--warn">
            <i class="bi bi-exclamation-triangle"></i>
            <span>Il pagamento della caparra non è stato completato. La prenotazione verrà annullata automaticamente.</span>
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
        <?php endif; ?>
    </div>

    <div class="bw-conf-footer">
        &copy; <?= date('Y') ?> Evulery &middot; by alagias. - Soluzioni per il web
    </div>
</div>
