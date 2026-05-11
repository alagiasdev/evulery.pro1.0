<div class="rs-page-header">
    <div>
        <h1 class="rs-page-title">Nuovo lead</h1>
        <p class="rs-page-sub">Aggiungi un lead acquisito dal tuo network. Verrà automaticamente assegnato a te.</p>
    </div>
    <div>
        <a href="<?= url('reseller/leads') ?>" class="rs-btn rs-btn-ghost">
            <i class="bi bi-arrow-left"></i> Indietro
        </a>
    </div>
</div>

<?php if (!empty($duplicate)): ?>
    <div style="background:#FFF3E0;border-left:3px solid #f57c00;padding:1rem 1.25rem;border-radius:8px;margin-bottom:1.25rem;color:#5d4037;font-size:.88rem;line-height:1.6;">
        <strong style="color:#E65100;"><i class="bi bi-exclamation-triangle-fill"></i> Lead simile già presente</strong><br>
        Esiste già un lead con l'email <strong><?= e($duplicate['email']) ?></strong> ricevuto il <?= format_date($duplicate['created_at'], 'd/m/Y H:i') ?>
        per <strong><?= e($duplicate['restaurant']) ?></strong>
        (stato: <em><?= e(\App\Models\DemoRequest::STATUSES[$duplicate['status']] ?? $duplicate['status']) ?></em>).
        <br>
        Se è davvero un lead diverso, puoi procedere comunque cliccando "Salva comunque". Altrimenti rivedi i campi.
    </div>
<?php endif; ?>

<form method="POST" action="<?= url('reseller/leads') ?>">
    <?= csrf_field() ?>
    <?php if ($force): ?>
        <input type="hidden" name="force" value="1">
    <?php endif; ?>

    <div class="rs-card">
        <div class="rs-card-hdr">
            <span><i class="bi bi-person"></i> Contatti</span>
        </div>
        <div class="rs-card-body">
            <div class="rs-form-grid">
                <div class="rs-field">
                    <label for="name">Nome e cognome *</label>
                    <input type="text" name="name" id="name" required value="<?= e($old['name'] ?? '') ?>" placeholder="Es. Mario Rossi">
                </div>
                <div class="rs-field">
                    <label for="restaurant">Ristorante *</label>
                    <input type="text" name="restaurant" id="restaurant" required value="<?= e($old['restaurant'] ?? '') ?>" placeholder="Es. Osteria del Borgo">
                </div>
                <div class="rs-field">
                    <label for="email">Email *</label>
                    <input type="email" name="email" id="email" required value="<?= e($old['email'] ?? '') ?>" placeholder="info@osteria.it">
                </div>
                <div class="rs-field">
                    <label for="phone">Telefono</label>
                    <input type="tel" name="phone" id="phone" value="<?= e($old['phone'] ?? '') ?>" placeholder="+39 ...">
                </div>
                <div class="rs-field rs-form-grid-full">
                    <label for="message">Note / contesto (opzionale)</label>
                    <textarea name="message" id="message" rows="3" placeholder="Es. Conosciuto al pranzo Confcommercio. Interessato a digital menu + ordini takeaway." style="resize:vertical;"><?= e($old['message'] ?? '') ?></textarea>
                    <div class="rs-field-help">Quello che scrivi qui sarà visibile a te e all'admin nello storico del lead.</div>
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:.75rem;align-items:center;">
        <button type="submit" class="rs-btn rs-btn-primary">
            <i class="bi bi-check-circle"></i>
            <?= $force ? 'Salva comunque' : 'Crea lead' ?>
        </button>
        <a href="<?= url('reseller/leads') ?>" class="rs-btn rs-btn-ghost">Annulla</a>
    </div>
</form>
