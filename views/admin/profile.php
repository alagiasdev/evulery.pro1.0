<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">Profilo</h1>
        <p class="admin-page-sub" style="margin-bottom:0;">Gestisci il tuo account amministratore</p>
    </div>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; max-width:900px;">
    <div style="grid-column:1/2;">
        <form method="POST" action="<?= url('admin/profile') ?>">
            <?= csrf_field() ?>

            <!-- Dati personali -->
            <div class="adm-card" style="margin-bottom:1.5rem;">
                <div style="padding:1rem 1.25rem;border-bottom:1px solid #eee;">
                    <div style="font-weight:600;font-size:.95rem;">Dati personali</div>
                    <div style="font-size:.78rem;color:#6c757d;">Nome e contatti</div>
                </div>
                <div style="padding:1.25rem;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                        <div>
                            <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:.35rem;">Nome *</label>
                            <input type="text" name="first_name" value="<?= e($user['first_name'] ?? '') ?>" required
                                   style="width:100%;padding:.5rem .75rem;border:1px solid #dee2e6;border-radius:8px;font-size:.85rem;">
                        </div>
                        <div>
                            <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:.35rem;">Cognome *</label>
                            <input type="text" name="last_name" value="<?= e($user['last_name'] ?? '') ?>" required
                                   style="width:100%;padding:.5rem .75rem;border:1px solid #dee2e6;border-radius:8px;font-size:.85rem;">
                        </div>
                    </div>
                    <div>
                        <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:.35rem;">Email *</label>
                        <input type="email" name="email" value="<?= e($user['email'] ?? '') ?>" required
                               style="width:100%;padding:.5rem .75rem;border:1px solid #dee2e6;border-radius:8px;font-size:.85rem;">
                    </div>
                </div>
            </div>

            <!-- Cambio password -->
            <div class="adm-card" style="margin-bottom:1.5rem;">
                <div style="padding:1rem 1.25rem;border-bottom:1px solid #eee;">
                    <div style="font-weight:600;font-size:.95rem;">Cambia password</div>
                    <div style="font-size:.78rem;color:#6c757d;">Lascia vuoto per mantenere la password attuale</div>
                </div>
                <div style="padding:1.25rem;">
                    <div style="margin-bottom:1rem;">
                        <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:.35rem;">Password attuale</label>
                        <input type="password" name="current_password" autocomplete="current-password"
                               style="width:100%;padding:.5rem .75rem;border:1px solid #dee2e6;border-radius:8px;font-size:.85rem;">
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div>
                            <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:.35rem;">Nuova password</label>
                            <input type="password" name="new_password" autocomplete="new-password"
                                   style="width:100%;padding:.5rem .75rem;border:1px solid #dee2e6;border-radius:8px;font-size:.85rem;">
                            <div style="font-size:.72rem;color:#6c757d;margin-top:.35rem;">Min. 8 caratteri, una maiuscola e un numero</div>
                        </div>
                        <div>
                            <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:.35rem;">Conferma password</label>
                            <input type="password" name="confirm_password" autocomplete="new-password"
                                   style="width:100%;padding:.5rem .75rem;border:1px solid #dee2e6;border-radius:8px;font-size:.85rem;">
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="adm-btn adm-btn-primary">
                <i class="bi bi-check-circle"></i> Salva profilo
            </button>
        </form>
    </div>

    <div style="grid-column:2/3;">
        <div class="adm-card">
            <div style="padding:1.25rem;">
                <div style="font-weight:600;font-size:.9rem;margin-bottom:.75rem;">Info account</div>
                <div style="font-size:.82rem;color:#495057;line-height:1.8;">
                    <strong>Ruolo:</strong> <?= e(role_label($user['role'] ?? '')) ?><br>
                    <strong>Ultimo accesso:</strong> <?= !empty($user['last_login_at']) ? format_date($user['last_login_at'], 'd/m/Y H:i') : 'Mai' ?><br>
                    <strong>Registrato il:</strong> <?= format_date($user['created_at'], 'd/m/Y') ?>
                </div>
            </div>
        </div>
    </div>
</div>