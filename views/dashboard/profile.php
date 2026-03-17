<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">Profilo</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1rem;">Gestisci il tuo account</p>

<div class="row g-4">
    <div class="col-lg-7">
        <form method="POST" action="<?= url('dashboard/profile') ?>">
            <?= csrf_field() ?>

            <!-- Dati personali -->
            <div class="card section-card">
                <div class="section-header">
                    <div class="section-icon" style="background:var(--brand);"><i class="bi bi-person"></i></div>
                    <div>
                        <div class="section-title">Dati personali</div>
                        <div class="section-subtitle">Nome e contatti del tuo account</div>
                    </div>
                </div>
                <div class="form-body">
                    <div class="row g-3">
                        <div class="col-md-6 field-row">
                            <label class="field-label">Nome *</label>
                            <input type="text" class="field-input" name="first_name" value="<?= e($user['first_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6 field-row">
                            <label class="field-label">Cognome *</label>
                            <input type="text" class="field-input" name="last_name" value="<?= e($user['last_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-12 field-row">
                            <label class="field-label">Email *</label>
                            <input type="email" class="field-input" name="email" value="<?= e($user['email'] ?? '') ?>" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cambio password -->
            <div class="card section-card">
                <div class="section-header">
                    <div class="section-icon" style="background:#5C6BC0;"><i class="bi bi-shield-lock"></i></div>
                    <div>
                        <div class="section-title">Cambia password</div>
                        <div class="section-subtitle">Lascia vuoto per mantenere la password attuale</div>
                    </div>
                </div>
                <div class="form-body">
                    <div class="row g-3">
                        <div class="col-12 field-row">
                            <label class="field-label">Password attuale</label>
                            <input type="password" class="field-input" name="current_password" autocomplete="current-password">
                        </div>
                        <div class="col-md-6 field-row">
                            <label class="field-label">Nuova password</label>
                            <input type="password" class="field-input" name="new_password" autocomplete="new-password">
                            <div class="field-hint">Minimo 8 caratteri, una maiuscola e un numero</div>
                        </div>
                        <div class="col-md-6 field-row">
                            <label class="field-label">Conferma password</label>
                            <input type="password" class="field-input" name="confirm_password" autocomplete="new-password">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save bar -->
            <div class="save-bar">
                <span class="save-hint"><i class="bi bi-info-circle me-1"></i>Le modifiche saranno applicate immediatamente</span>
                <button type="submit" class="btn-save"><i class="bi bi-check-circle me-1"></i> Salva profilo</button>
            </div>
        </form>
    </div>

    <div class="col-lg-5">
        <div class="card section-card">
            <div class="tip-card">
                <i class="bi bi-lightbulb" style="color:#FFC107;font-size:1.1rem;margin-top:.1rem;"></i>
                <div>
                    <div class="tip-title">Info account</div>
                    <div class="tip-text">
                        <strong>Ruolo:</strong> <?= e(ucfirst($user['role'] ?? '')) ?><br>
                        <strong>Ultimo accesso:</strong> <?= !empty($user['last_login_at']) ? format_date($user['last_login_at'], 'd/m/Y H:i') : 'Mai' ?><br>
                        <strong>Registrato il:</strong> <?= format_date($user['created_at'], 'd/m/Y') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>