<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Errore interno</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Queste due pagine caricano solo Bootstrap, nessun foglio del progetto:
         btn-success darebbe il verde di Bootstrap (#198754), non il nostro.
         Il verde di marca va quindi dichiarato qui, con i valori di DESIGN.md. -->
    <style>
        .btn-brand { background: #00844A; border-color: #00844A; color: #fff; }
        .btn-brand:hover,
        .btn-brand:focus { background: #006837; border-color: #006837; color: #fff; }
    </style>
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="text-center">
        <h1 class="display-1 fw-bold text-muted">500</h1>
        <p class="lead">Errore interno del server</p>
        <p class="text-muted">Si è verificato un errore. Riprova più tardi.</p>
        <a href="<?= url('/') ?>" class="btn btn-brand">Torna alla home</a>
    </div>
</body>
</html>
