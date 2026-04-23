<h1 class="auth-title">Accedi</h1>
<p class="auth-subtitle">Inserisci le tue credenziali per continuare</p>

<form method="POST" action="<?= url('auth/login') ?>">
    <?= csrf_field() ?>

    <div class="auth-field">
        <label class="auth-label" for="email">Email</label>
        <div class="auth-input-wrap">
            <span class="auth-input-icon"><?= bi_icon('envelope') ?></span>
            <input type="email" class="auth-input has-icon" id="email" name="email" value="<?= old('email') ?>" placeholder="nome@ristorante.it" required autofocus>
        </div>
    </div>

    <div class="auth-field">
        <label class="auth-label" for="password">Password</label>
        <div class="auth-input-wrap">
            <span class="auth-input-icon"><?= bi_icon('lock') ?></span>
            <input type="password" class="auth-input has-icon has-toggle" id="password" name="password" placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;" required>
            <button type="button" class="auth-toggle-pw"><?= bi_icon('eye') ?></button>
        </div>
    </div>

    <button type="submit" class="auth-btn">
        <?= bi_icon('box-arrow-in-right') ?> Accedi
    </button>

    <div class="auth-link-row">
        <a href="<?= url('auth/forgot-password') ?>" class="auth-link">Password dimenticata?</a>
    </div>
</form>
