<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Login') ?> - <?= e(env('APP_NAME', 'Evulery')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    <style nonce="<?= csp_nonce() ?>">
        :root { --brand: #00844A; --brand-dark: #006837; --brand-light: #E8F5E9; }
        body {
            background: #f5f6f8;
            font-family: system-ui, -apple-system, sans-serif;
            margin: 0; min-height: 100vh;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
        }
        .auth-wrapper { width: 100%; max-width: 420px; padding: 2rem 1rem; }
        .auth-logo { text-align: center; margin-bottom: 1.75rem; }
        .auth-logo-icon {
            width: 48px; height: 48px; border-radius: 12px;
            background: var(--brand); color: #fff;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 1.4rem; margin-bottom: .65rem;
        }
        .auth-logo-name { font-size: 1.5rem; font-weight: 700; color: #1a1d23; margin: 0; line-height: 1.2; }
        .auth-logo-sub { font-size: .82rem; color: #6c757d; margin-top: .25rem; }
        .auth-card {
            background: #fff; border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
            padding: 1.75rem;
        }
        .auth-title { font-size: 1.1rem; font-weight: 700; color: #1a1d23; margin: 0 0 .25rem; }
        .auth-subtitle { font-size: .82rem; color: #6c757d; margin: 0 0 1.25rem; }
        .auth-field { margin-bottom: 1rem; }
        .auth-label { display: block; font-size: .78rem; font-weight: 600; color: #495057; margin-bottom: .35rem; }
        .auth-input-wrap { position: relative; }
        .auth-input {
            width: 100%; border: 1px solid #dee2e6; border-radius: 8px;
            padding: .6rem .85rem; font-size: .88rem;
            transition: border-color .15s, box-shadow .15s;
            color: #1a1d23; background: #fff;
        }
        .auth-input:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 3px rgba(0,132,74,.08); }
        .auth-input::placeholder { color: #adb5bd; }
        .auth-input-icon {
            position: absolute; left: .75rem; top: 50%; transform: translateY(-50%);
            color: #adb5bd; font-size: .9rem; pointer-events: none;
        }
        .auth-input.has-icon { padding-left: 2.25rem; }
        .auth-toggle-pw {
            position: absolute; right: .65rem; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: #adb5bd;
            cursor: pointer; font-size: .95rem; padding: .15rem; line-height: 1;
        }
        .auth-toggle-pw:hover { color: #495057; }
        .auth-input.has-toggle { padding-right: 2.5rem; }
        .auth-btn {
            width: 100%; background: var(--brand); color: #fff;
            border: none; border-radius: 10px;
            padding: .7rem; font-size: .92rem; font-weight: 700;
            cursor: pointer; transition: background .15s;
            display: flex; align-items: center; justify-content: center; gap: .4rem;
        }
        .auth-btn:hover { background: var(--brand-dark); }
        .auth-btn:active { transform: scale(.98); }
        .auth-link-row { text-align: center; margin-top: 1rem; }
        .auth-link { color: var(--brand); font-size: .82rem; text-decoration: none; font-weight: 500; }
        .auth-link:hover { text-decoration: underline; }
        .auth-footer { text-align: center; margin-top: 1.5rem; font-size: .72rem; color: #adb5bd; }
        .auth-page-icon {
            width: 48px; height: 48px; border-radius: 50%;
            background: var(--brand-light);
            display: inline-flex; align-items: center; justify-content: center;
            margin-bottom: .75rem;
        }
        .auth-page-icon i { font-size: 1.3rem; color: var(--brand); }

        @media (max-width: 576px) {
            .auth-wrapper { padding: 1.5rem 1rem; }
            .auth-card { padding: 1.25rem; }
            .auth-logo-name { font-size: 1.3rem; }
            .auth-logo-icon { width: 42px; height: 42px; font-size: 1.2rem; }
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-logo">
            <div class="auth-logo-icon"><i class="bi bi-calendar-check"></i></div>
            <div class="auth-logo-name"><?= e(env('APP_NAME', 'Evulery')) ?></div>
            <div class="auth-logo-sub">by alagias. - Soluzioni per il web</div>
        </div>

        <div class="auth-card">
            <?php partial('flash-messages'); ?>
            <?= $content ?>
        </div>

        <div class="auth-footer">
            &copy; <?= date('Y') ?> Evulery &middot; by alagias. - Soluzioni per il web
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script nonce="<?= csp_nonce() ?>">
    document.querySelectorAll('.auth-toggle-pw').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var input = this.closest('.auth-input-wrap').querySelector('.auth-input');
            var icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });
    });
    </script>
</body>
</html>