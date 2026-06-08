<?php
// Variabili opzionali: $heading, $subtitle, $body, $icon
$heading  = $heading  ?? 'Prenotazione non trovata';
$subtitle = $subtitle ?? 'Il link potrebbe essere scaduto o non valido';
$body     = $body     ?? 'Se hai bisogno di assistenza, contatta direttamente il ristorante.';
$icon     = $icon     ?? 'bi-question-circle';
?>
<div class="manage-card">
    <div class="manage-header" style="background:#6c757d;">
        <div style="font-size:2rem;margin-bottom:8px;"><i class="bi <?= e($icon) ?>"></i></div>
        <h1><?= e($heading) ?></h1>
        <p><?= e($subtitle) ?></p>
    </div>
    <div class="manage-body" style="text-align:center;">
        <p style="color:#6c757d;font-size:.88rem;">
            <?= e($body) ?>
        </p>
    </div>
    <div class="manage-footer">
        &copy; <?= date('Y') ?> Evulery &middot; by alagias. - Soluzioni per il web
    </div>
</div>