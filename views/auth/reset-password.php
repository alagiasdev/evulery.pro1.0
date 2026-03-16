<div style="text-align:center;">
    <div class="auth-page-icon"><i class="bi bi-shield-lock"></i></div>
</div>
<h1 class="auth-title" style="text-align:center;">Reimposta Password</h1>
<p class="auth-subtitle" style="text-align:center;">Scegli una nuova password per il tuo account.</p>

<form method="POST" action="<?= url('auth/reset-password') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token ?? '') ?>">

    <div class="auth-field">
        <label class="auth-label" for="password">Nuova Password</label>
        <div class="auth-input-wrap">
            <i class="bi bi-lock auth-input-icon"></i>
            <input type="password" class="auth-input has-icon has-toggle" id="password" name="password" placeholder="Minimo 8 caratteri" required minlength="8">
            <button type="button" class="auth-toggle-pw"><i class="bi bi-eye"></i></button>
        </div>
    </div>

    <div class="auth-field">
        <label class="auth-label" for="password_confirmation">Conferma Password</label>
        <div class="auth-input-wrap">
            <i class="bi bi-lock auth-input-icon"></i>
            <input type="password" class="auth-input has-icon has-toggle" id="password_confirmation" name="password_confirmation" placeholder="Ripeti la password" required minlength="8">
            <button type="button" class="auth-toggle-pw"><i class="bi bi-eye"></i></button>
        </div>
    </div>

    <button type="submit" class="auth-btn">
        <i class="bi bi-check-circle"></i> Reimposta Password
    </button>
</form>