<div class="rs-page-header">
    <div>
        <h1 class="rs-page-title">Profilo</h1>
        <p class="rs-page-sub">Gestisci i tuoi dati account e visualizza le commissioni concordate</p>
    </div>
</div>

<!-- Commissioni readonly (in alto perché è la cosa più importante per il reseller) -->
<?php if ($profile): ?>
    <div class="rs-comm-readonly">
        <h3><i class="bi bi-cash-stack"></i> Commissioni concordate</h3>
        <div class="rs-comm-row">
            <div class="item">
                <div class="l">Setup (una tantum)</div>
                <div class="v">€<?= number_format((float)$profile['commission_setup'], 0, ',', '.') ?></div>
            </div>
            <div class="item">
                <div class="l">Starter (annuale)</div>
                <div class="v">€<?= number_format((float)$profile['commission_starter'], 0, ',', '.') ?></div>
            </div>
            <div class="item">
                <div class="l">Professional (annuale)</div>
                <div class="v">€<?= number_format((float)$profile['commission_professional'], 0, ',', '.') ?></div>
            </div>
            <div class="item">
                <div class="l">Enterprise (annuale)</div>
                <div class="v">€<?= number_format((float)$profile['commission_enterprise'], 0, ',', '.') ?></div>
            </div>
        </div>
        <p style="margin-top:1rem;font-size:.78rem;opacity:.85;">
            Per modificare le commissioni concordate, contatta l'amministratore.
        </p>
    </div>
<?php endif; ?>

<div class="rs-card">
    <div class="rs-card-hdr">
        <span><i class="bi bi-person-circle"></i> Dati account</span>
    </div>
    <form method="POST" action="<?= url('reseller/profile') ?>">
        <?= csrf_field() ?>
        <div class="rs-form-grid" style="padding:1.25rem;">
            <div class="rs-field">
                <label for="first_name">Nome *</label>
                <input type="text" name="first_name" id="first_name" value="<?= e($user['first_name'] ?? '') ?>" required>
            </div>
            <div class="rs-field">
                <label for="last_name">Cognome *</label>
                <input type="text" name="last_name" id="last_name" value="<?= e($user['last_name'] ?? '') ?>" required>
            </div>
            <div class="rs-field rs-form-grid-full">
                <label for="email">Email *</label>
                <input type="email" name="email" id="email" value="<?= e($user['email'] ?? '') ?>" required>
            </div>
        </div>

        <div style="padding:0 1.25rem 1.25rem;">
            <div style="border-top:1px solid var(--rs-line);padding-top:1.25rem;">
                <div style="font-weight:700;font-size:.95rem;margin-bottom:.25rem;">Cambia password</div>
                <div style="font-size:.78rem;color:var(--rs-muted);margin-bottom:1rem;">Lascia vuoto per mantenere la password attuale</div>
            </div>
            <div class="rs-form-grid">
                <div class="rs-field rs-form-grid-full">
                    <label for="current_password">Password attuale</label>
                    <input type="password" name="current_password" id="current_password" autocomplete="current-password">
                </div>
                <div class="rs-field">
                    <label for="new_password">Nuova password</label>
                    <input type="password" name="new_password" id="new_password" autocomplete="new-password">
                    <div class="rs-field-help">Min. 8 caratteri, una maiuscola e un numero</div>
                </div>
                <div class="rs-field">
                    <label for="confirm_password">Conferma password</label>
                    <input type="password" name="confirm_password" id="confirm_password" autocomplete="new-password">
                </div>
            </div>
        </div>

        <div style="padding:0 1.25rem 1.25rem;">
            <button type="submit" class="rs-btn rs-btn-primary">
                <i class="bi bi-check-circle"></i> Salva profilo
            </button>
        </div>
    </form>
</div>

<div class="rs-card">
    <div class="rs-card-body" style="font-size:.85rem;color:var(--rs-ink-soft);line-height:1.7;">
        <strong>Ruolo:</strong> <?= e(role_label($user['role'] ?? '')) ?><br>
        <strong>Ultimo accesso:</strong> <?= !empty($user['last_login_at']) ? format_date($user['last_login_at'], 'd/m/Y H:i') : 'Mai' ?><br>
        <strong>Membro dal:</strong> <?= format_date($user['created_at'], 'd/m/Y') ?>
    </div>
</div>
