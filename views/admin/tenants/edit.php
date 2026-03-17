<div class="admin-page-header-left" style="margin-bottom:1.25rem;">
    <a href="<?= url('admin/tenants') ?>" class="adm-action" title="Torna alla lista">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h1 class="admin-page-title">Modifica: <?= e($tenant['name']) ?></h1>
        <p class="admin-page-sub" style="margin-bottom:0;">Aggiorna i dati del ristorante</p>
    </div>
</div>

<div class="adm-edit-grid">
    <!-- Left: Form -->
    <div>
        <form method="POST" action="<?= url("admin/tenants/{$tenant['id']}") ?>">
            <?= csrf_field() ?>

            <div class="adm-card">
                <div class="adm-card-hdr">
                    <span class="adm-card-hdr-title"><i class="bi bi-shop me-1"></i> Dati Ristorante</span>
                </div>
                <div class="adm-card-body">
                    <div class="adm-form-row">
                        <div>
                            <label class="adm-form-label">Nome *</label>
                            <input type="text" class="adm-form-input" name="name" value="<?= e($tenant['name']) ?>" required>
                        </div>
                        <div>
                            <label class="adm-form-label">Email *</label>
                            <input type="email" class="adm-form-input" name="email" value="<?= e($tenant['email']) ?>" required>
                        </div>
                    </div>
                    <div class="adm-form-row">
                        <div>
                            <label class="adm-form-label">Telefono</label>
                            <input type="text" class="adm-form-input" name="phone" value="<?= e($tenant['phone'] ?? '') ?>">
                        </div>
                        <div>
                            <label class="adm-form-label">Piano</label>
                            <select class="adm-form-select" name="plan">
                                <option value="base" <?= $tenant['plan'] === 'base' ? 'selected' : '' ?>>Base</option>
                                <option value="deposit" <?= $tenant['plan'] === 'deposit' ? 'selected' : '' ?>>Deposit</option>
                                <option value="custom" <?= $tenant['plan'] === 'custom' ? 'selected' : '' ?>>Custom</option>
                            </select>
                        </div>
                    </div>
                    <div class="adm-form-full">
                        <label class="adm-form-label">Indirizzo</label>
                        <input type="text" class="adm-form-input" name="address" value="<?= e($tenant['address'] ?? '') ?>">
                    </div>
                    <div class="adm-form-row-3">
                        <div>
                            <label class="adm-form-label">Durata tavolo (min)</label>
                            <input type="number" class="adm-form-input" name="table_duration" value="<?= (int)$tenant['table_duration'] ?>" min="30" step="15">
                        </div>
                        <div>
                            <label class="adm-form-label">Step orari (min)</label>
                            <input type="number" class="adm-form-input" name="time_step" value="<?= (int)$tenant['time_step'] ?>" min="15" step="15">
                        </div>
                        <div>
                            <label class="adm-form-label">Slug</label>
                            <input type="text" class="adm-form-input" value="<?= e($tenant['slug']) ?>" disabled>
                        </div>
                    </div>
                    <div class="adm-form-check">
                        <input type="checkbox" name="is_active" id="is_active" <?= $tenant['is_active'] ? 'checked' : '' ?>>
                        <label for="is_active">Attivo</label>
                    </div>
                </div>
            </div>

            <div style="display:flex;gap:.75rem;">
                <button type="submit" class="adm-btn adm-btn-primary">
                    <i class="bi bi-check-circle"></i> Salva Modifiche
                </button>
                <a href="<?= url('admin/tenants') ?>" class="adm-btn adm-btn-outline">Torna alla lista</a>
            </div>
        </form>
    </div>

    <!-- Right: Info cards -->
    <div>
        <div class="adm-info-card">
            <div class="adm-info-hdr"><i class="bi bi-people me-1"></i> Utenti associati</div>
            <div class="adm-info-body">
                <?php if (empty($users)): ?>
                    <p style="color:#adb5bd;font-size:.82rem;margin:0;">Nessun utente.</p>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                    <form method="POST" action="<?= url("admin/tenants/{$tenant['id']}/users/{$u['id']}") ?>" style="margin-bottom:.75rem;padding:.75rem;background:#f8f9fa;border-radius:8px;">
                        <?= csrf_field() ?>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-bottom:.5rem;">
                            <input type="text" name="first_name" value="<?= e($u['first_name']) ?>" placeholder="Nome"
                                   style="padding:.4rem .6rem;border:1px solid #dee2e6;border-radius:6px;font-size:.82rem;">
                            <input type="text" name="last_name" value="<?= e($u['last_name']) ?>" placeholder="Cognome"
                                   style="padding:.4rem .6rem;border:1px solid #dee2e6;border-radius:6px;font-size:.82rem;">
                        </div>
                        <div style="margin-bottom:.5rem;">
                            <input type="email" name="email" value="<?= e($u['email']) ?>" placeholder="Email"
                                   style="width:100%;padding:.4rem .6rem;border:1px solid #dee2e6;border-radius:6px;font-size:.82rem;">
                        </div>
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <span class="adm-badge <?= $u['is_active'] ? 'adm-badge-active' : 'adm-badge-inactive' ?>" style="font-size:.7rem;">
                                <?= e(ucfirst($u['role'])) ?> &middot; <?= $u['is_active'] ? 'Attivo' : 'Inattivo' ?>
                            </span>
                            <button type="submit" class="adm-btn adm-btn-primary" style="padding:.3rem .7rem;font-size:.75rem;">
                                <i class="bi bi-check-lg"></i> Salva
                            </button>
                        </div>
                    </form>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="adm-info-card">
            <div class="adm-info-hdr"><i class="bi bi-link-45deg me-1"></i> Link prenotazione</div>
            <div class="adm-info-body">
                <div class="adm-link-box"><?= e(url($tenant['slug'])) ?></div>
            </div>
        </div>
    </div>
</div>