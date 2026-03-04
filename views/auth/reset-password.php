<h4 class="mb-3">Reimposta Password</h4>
<form method="POST" action="<?= url('auth/reset-password') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
    <div class="mb-3">
        <label for="password" class="form-label">Nuova Password</label>
        <input type="password" class="form-control" id="password" name="password" required minlength="8">
    </div>
    <div class="mb-3">
        <label for="password_confirmation" class="form-label">Conferma Password</label>
        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required minlength="8">
    </div>
    <button type="submit" class="btn btn-primary w-100">Reimposta Password</button>
</form>
