<?php
/** @var array $prefill          Query string parameters (es. da convert lead) */
/** @var ?array $sourceLead      Lead di origine se conversione */
/** @var ?string $leadResellerName Nome del reseller associato al lead */
$prefill = $prefill ?? [];
$valOf = function(string $key, $default = '') use ($prefill) {
    return old($key) ?: (string)($prefill[$key] ?? $default);
};
?>

<div class="admin-page-header-left" style="margin-bottom:1.25rem;">
    <a href="<?= url('admin/tenants') ?>" class="adm-action" title="Torna alla lista">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h1 class="admin-page-title">Nuovo Ristorante</h1>
        <p class="admin-page-sub" style="margin-bottom:0;">Crea un nuovo ristorante sulla piattaforma</p>
    </div>
</div>

<?php if ($sourceLead ?? null): ?>
<div style="max-width:720px;margin-bottom:1rem;background:#E8F5E9;border-left:4px solid #00844A;border-radius:8px;padding:14px 18px;font-size:.88rem;line-height:1.5;color:#1a1d23;">
    <strong style="color:#006837;"><i class="bi bi-arrow-right-circle"></i> Conversione da lead #<?= (int)$sourceLead['id'] ?></strong>
    <div style="margin-top:4px;">
        Stai creando un tenant dal lead "<strong><?= e($sourceLead['restaurant']) ?></strong>".
        <?php if ($leadResellerName): ?>
            Verrà associato al reseller <strong><?= e($leadResellerName) ?></strong>.
        <?php else: ?>
            Nessun reseller associato al lead (verrà creato come <strong>diretto</strong>).
        <?php endif; ?>
        Il lead verrà marcato come <strong>Cliente</strong> al salvataggio.
    </div>
</div>
<?php endif; ?>

<div style="max-width:720px;">
    <form method="POST" action="<?= url('admin/tenants') ?>">
        <?= csrf_field() ?>
        <?php if (!empty($prefill['lead_id'])): ?>
            <input type="hidden" name="lead_id" value="<?= (int)$prefill['lead_id'] ?>">
        <?php endif; ?>

        <!-- Dati Ristorante -->
        <div class="adm-card">
            <div class="adm-card-hdr">
                <span class="adm-card-hdr-title"><i class="bi bi-shop me-1"></i> Dati Ristorante</span>
            </div>
            <div class="adm-card-body">
                <div class="adm-form-row">
                    <div>
                        <label class="adm-form-label">Nome ristorante *</label>
                        <input type="text" class="adm-form-input" name="name" value="<?= e($valOf('name')) ?>" required>
                    </div>
                    <div>
                        <label class="adm-form-label">Email ristorante *</label>
                        <input type="email" class="adm-form-input" name="email" value="<?= e($valOf('email')) ?>" required>
                    </div>
                </div>
                <div class="adm-form-row">
                    <div>
                        <label class="adm-form-label">Telefono</label>
                        <input type="text" class="adm-form-input" name="phone" value="<?= e($valOf('phone')) ?>">
                    </div>
                    <div>
                        <label class="adm-form-label">Piano</label>
                        <select class="adm-form-select" name="plan_id">
                            <?php foreach ($plans as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= old('plan_id') == $p['id'] ? 'selected' : '' ?>>
                                <?= e($p['name']) ?> (&euro;<?= number_format($p['price'], 0, ',', '.') ?>/mese)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="adm-form-full">
                    <label class="adm-form-label">Indirizzo</label>
                    <input type="text" class="adm-form-input" name="address" value="<?= old('address') ?>">
                </div>
                <div class="adm-form-check">
                    <input type="checkbox" name="is_active" id="is_active" checked>
                    <label for="is_active">Attivo subito</label>
                </div>
            </div>
        </div>

        <!-- Account Proprietario -->
        <div class="adm-card">
            <div class="adm-card-hdr">
                <span class="adm-card-hdr-title"><i class="bi bi-person me-1"></i> Account Proprietario</span>
            </div>
            <div class="adm-card-body">
                <div class="adm-form-row">
                    <div>
                        <label class="adm-form-label">Nome *</label>
                        <input type="text" class="adm-form-input" name="owner_first_name" value="<?= e($valOf('owner_first_name')) ?>" required>
                    </div>
                    <div>
                        <label class="adm-form-label">Cognome *</label>
                        <input type="text" class="adm-form-input" name="owner_last_name" value="<?= e($valOf('owner_last_name')) ?>" required>
                    </div>
                </div>
                <div class="adm-form-row">
                    <div>
                        <label class="adm-form-label">Email login *</label>
                        <input type="email" class="adm-form-input" name="owner_email" value="<?= e($valOf('owner_email')) ?>" required>
                    </div>
                    <div>
                        <label class="adm-form-label">Password *</label>
                        <input type="password" class="adm-form-input" name="owner_password" required minlength="8">
                        <div class="adm-form-hint">Minimo 8 caratteri</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div style="display:flex;gap:.75rem;">
            <button type="submit" class="adm-btn adm-btn-primary">
                <i class="bi bi-check-circle"></i> Crea Ristorante
            </button>
            <a href="<?= url('admin/tenants') ?>" class="adm-btn adm-btn-outline">Annulla</a>
        </div>
    </form>
</div>