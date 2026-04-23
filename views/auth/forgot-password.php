<div style="text-align:center;">
    <div class="auth-page-icon"><?= bi_icon('envelope-open') ?></div>
</div>
<h1 class="auth-title" style="text-align:center;">Recupera Password</h1>
<p class="auth-subtitle" style="text-align:center;">Inserisci la tua email. Riceverai un link per reimpostare la password.</p>

<form method="POST" action="<?= url('auth/forgot-password') ?>">
    <?= csrf_field() ?>

    <div class="auth-field">
        <label class="auth-label" for="email">Email</label>
        <div class="auth-input-wrap">
            <span class="auth-input-icon"><?= bi_icon('envelope') ?></span>
            <input type="email" class="auth-input has-icon" id="email" name="email" placeholder="nome@ristorante.it" required autofocus>
        </div>
    </div>

    <button type="submit" class="auth-btn">
        <?= bi_icon('send') ?> Invia link di reset
    </button>

    <div class="auth-link-row">
        <a href="<?= url('auth/login') ?>" class="auth-link"><?= bi_icon('arrow-left', 'icon me-1') ?>Torna al login</a>
    </div>
</form>
