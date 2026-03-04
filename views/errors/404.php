<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Pagina non trovata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="text-center">
        <h1 class="display-1 fw-bold text-muted">404</h1>
        <p class="lead">Pagina non trovata</p>
        <p class="text-muted">La pagina che stai cercando non esiste.</p>
        <a href="<?= url('/') ?>" class="btn btn-primary">Torna alla home</a>
    </div>
</body>
</html>
