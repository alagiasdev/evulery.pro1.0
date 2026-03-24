<div class="admin-page-header-left" style="margin-bottom:1.25rem;">
    <a href="<?= url('admin/tenants') ?>" class="adm-action" title="Torna alla lista">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h1 class="admin-page-title">Nuovo Ristorante</h1>
        <p class="admin-page-sub" style="margin-bottom:0;">Crea un nuovo ristorante sulla piattaforma</p>
    </div>
</div>

<div style="max-width:720px;">
    <form method="POST" action="<?= url('admin/tenants') ?>">
        <?= csrf_field() ?>

        <!-- Dati Ristorante -->
        <div class="adm-card">
            <div class="adm-card-hdr">
                <span class="adm-card-hdr-title"><i class="bi bi-shop me-1"></i> Dati Ristorante</span>
            </div>
            <div class="adm-card-body">
                <div class="adm-form-row">
                    <div>
                        <label class="adm-form-label">Nome ristorante *</label>
                        <input type="text" class="adm-form-input" name="name" value="<?= old('name') ?>" required>
                    </div>
                    <div>
                        <label class="adm-form-label">Email ristorante *</label>
                        <input type="email" class="adm-form-input" name="email" value="<?= old('email') ?>" required>
                    </div>
                </div>
                <div class="adm-form-row">
                    <div>
                        <label class="adm-form-label">Telefono</label>
                        <input type="text" class="adm-form-input" name="phone" value="<?= old('phone') ?>">
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
                        <input type="text" class="adm-form-input" name="owner_first_name" value="<?= old('owner_first_name') ?>" required>
                    </div>
                    <div>
                        <label class="adm-form-label">Cognome *</label>
                        <input type="text" class="adm-form-input" name="owner_last_name" value="<?= old('owner_last_name') ?>" required>
                    </div>
                </div>
                <div class="adm-form-row">
                    <div>
                        <label class="adm-form-label">Email login *</label>
                        <input type="email" class="adm-form-input" name="owner_email" value="<?= old('owner_email') ?>" required>
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