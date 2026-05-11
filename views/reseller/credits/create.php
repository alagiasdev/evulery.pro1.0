<div class="rs-page-header">
    <div>
        <h1 class="rs-page-title">Nuova richiesta ricarica</h1>
        <p class="rs-page-sub">Compila il form e invia. L'admin riceverà la notifica e procederà con l'accredito.</p>
    </div>
    <div>
        <a href="<?= url('reseller/credits') ?>" class="rs-btn rs-btn-ghost">
            <i class="bi bi-arrow-left"></i> Indietro
        </a>
    </div>
</div>

<?php if (empty($clients)): ?>
    <div class="rs-card">
        <div class="rs-card-empty">
            Non hai ancora clienti attivi. Le ricariche possono essere richieste solo per clienti acquisiti da te e ancora attivi.
        </div>
    </div>
<?php else: ?>
    <form method="POST" action="<?= url('reseller/credits') ?>">
        <?= csrf_field() ?>

        <div class="rs-card">
            <div class="rs-card-hdr">
                <span><i class="bi bi-shop"></i> Cliente</span>
            </div>
            <div class="rs-card-body">
                <div class="rs-form-grid">
                    <div class="rs-field rs-form-grid-full">
                        <label for="tenant_id">Seleziona cliente *</label>
                        <select name="tenant_id" id="tenant_id" required>
                            <option value="">— scegli un cliente —</option>
                            <?php foreach ($clients as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" data-balance="<?= (int)$c['email_credits_balance'] ?>">
                                    <?= e($c['name']) ?> · saldo attuale: <?= number_format((int)$c['email_credits_balance'], 0, ',', '.') ?> crediti
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="rs-card">
            <div class="rs-card-hdr">
                <span><i class="bi bi-envelope-paper"></i> Crediti da accreditare</span>
            </div>
            <div class="rs-card-body">
                <div class="rs-form-grid">
                    <div class="rs-field">
                        <label for="credits">Quantità *</label>
                        <input type="number" name="credits" id="credits" required
                               min="<?= (int)$minCredits ?>" max="<?= (int)$maxCredits ?>" step="<?= (int)$step ?>"
                               value="1000">
                        <div class="rs-field-help">Min <?= (int)$minCredits ?>, max <?= number_format($maxCredits, 0, ',', '.') ?>, a step di <?= (int)$step ?>.</div>
                    </div>
                    <div class="rs-field">
                        <label>Quick select</label>
                        <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
                            <?php foreach ([500, 1000, 2000, 5000] as $q): ?>
                                <button type="button" class="rs-btn rs-btn-ghost rs-btn-sm" data-quick="<?= $q ?>">
                                    <?= number_format($q, 0, ',', '.') ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="rs-field" style="margin-top:1rem;">
                    <label for="notes">Note (opzionale)</label>
                    <textarea name="notes" id="notes" rows="3" placeholder="Es. Il cliente ha esaurito i crediti dopo la campagna di compleanno..." style="resize:vertical;"></textarea>
                    <div class="rs-field-help">Aggiungi contesto utile per l'admin (uso previsto, urgenza, ecc.).</div>
                </div>
            </div>
        </div>

        <div style="display:flex;gap:.75rem;align-items:center;">
            <button type="submit" class="rs-btn rs-btn-primary">
                <i class="bi bi-send"></i> Invia richiesta
            </button>
            <a href="<?= url('reseller/credits') ?>" class="rs-btn rs-btn-ghost">Annulla</a>
        </div>
    </form>

    <script nonce="<?= csp_nonce() ?>">
    document.addEventListener('DOMContentLoaded', function() {
        var input = document.getElementById('credits');
        document.querySelectorAll('[data-quick]').forEach(function(b) {
            b.addEventListener('click', function() {
                input.value = b.getAttribute('data-quick');
            });
        });
    });
    </script>
<?php endif; ?>
