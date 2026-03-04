<h4 class="mb-3">Accedi</h4>
<form method="POST" action="<?= url('auth/login') ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control" id="email" name="email" value="<?= old('email') ?>" required autofocus>
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">Accedi</button>
    <div class="text-center mt-3">
        <a href="<?= url('auth/forgot-password') ?>" class="text-muted small">Password dimenticata?</a>
    </div>
</form>
