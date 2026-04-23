<div style="text-align:center;">
    <div class="auth-page-icon"><?= bi_icon('shield-lock') ?></div>
</div>
<h1 class="auth-title" style="text-align:center;">Reimposta Password</h1>
<p class="auth-subtitle" style="text-align:center;">Scegli una nuova password per il tuo account.</p>

<form method="POST" action="<?= url('auth/reset-password') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token ?? '') ?>">

    <div class="auth-field">
        <label class="auth-label" for="password">Nuova Password</label>
        <div class="auth-input-wrap">
            <span class="auth-input-icon"><?= bi_icon('lock') ?></span>
            <input type="password" class="auth-input has-icon has-toggle" id="password" name="password" placeholder="Minimo 8 caratteri" required minlength="8">
            <button type="button" class="auth-toggle-pw"><?= bi_icon('eye') ?></button>
        </div>
    </div>

    <div class="auth-field">
        <label class="auth-label" for="password_confirmation">Conferma Password</label>
        <div class="auth-input-wrap">
            <span class="auth-input-icon"><?= bi_icon('lock') ?></span>
            <input type="password" class="auth-input has-icon has-toggle" id="password_confirmation" name="password_confirmation" placeholder="Ripeti la password" required minlength="8">
            <button type="button" class="auth-toggle-pw"><?= bi_icon('eye') ?></button>
        </div>
    </div>

    <button type="submit" class="auth-btn">
        <?= bi_icon('check-circle') ?> Reimposta Password
    </button>
</form>
