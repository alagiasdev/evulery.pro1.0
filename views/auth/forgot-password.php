<h4 class="mb-3">Recupera Password</h4>
<p class="text-muted small">Inserisci la tua email per ricevere un link di reset.</p>
<form method="POST" action="<?= url('auth/forgot-password') ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control" id="email" name="email" required autofocus>
    </div>
    <button type="submit" class="btn btn-primary w-100">Invia link di reset</button>
    <div class="text-center mt-3">
        <a href="<?= url('auth/login') ?>" class="text-muted small">Torna al login</a>
    </div>
</form>
