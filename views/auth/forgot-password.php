<div style="text-align:center;">
    <div class="auth-page-icon"><i class="bi bi-envelope-open"></i></div>
</div>
<h1 class="auth-title" style="text-align:center;">Recupera Password</h1>
<p class="auth-subtitle" style="text-align:center;">Inserisci la tua email. Riceverai un link per reimpostare la password.</p>

<form method="POST" action="<?= url('auth/forgot-password') ?>">
    <?= csrf_field() ?>

    <div class="auth-field">
        <label class="auth-label" for="email">Email</label>
        <div class="auth-input-wrap">
            <i class="bi bi-envelope auth-input-icon"></i>
            <input type="email" class="auth-input has-icon" id="email" name="email" placeholder="nome@ristorante.it" required autofocus>
        </div>
    </div>

    <button type="submit" class="auth-btn">
        <i class="bi bi-send"></i> Invia link di reset
    </button>

    <div class="auth-link-row">
        <a href="<?= url('auth/login') ?>" class="auth-link"><i class="bi bi-arrow-left me-1"></i>Torna al login</a>
    </div>
</form>