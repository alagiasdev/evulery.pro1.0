<?php
/**
 * Partial: Service Locked Card
 *
 * Variables:
 *   $lockedTitle   string  Nome del servizio (es. "Caparra", "Promozioni")
 *   $lockedDesc    string  (opzionale) Descrizione custom. Default: "Contatta il supporto per effettuare un upgrade."
 */
$lockedDesc = $lockedDesc ?? 'Contatta il supporto per effettuare un upgrade.';
?>
<div class="card" style="padding:2.5rem 2rem;text-align:center;border:2px dashed #dee2e6;background:#fafafa;border-radius:12px;margin-top:.5rem;">
    <i class="bi bi-lock-fill" style="font-size:2.5rem;color:#adb5bd;"></i>
    <h5 style="margin-top:1rem;font-weight:700;">Funzionalit&agrave; non inclusa nel tuo piano</h5>
    <p style="color:#6c757d;font-size:.88rem;margin-bottom:1rem;">
        <?= e($lockedTitle) ?> non &egrave; disponibile con il tuo piano attuale.<br>
        <?= e($lockedDesc) ?>
    </p>
    <a href="mailto:<?= e(env('SUPPORT_EMAIL', '')) ?>" class="btn btn-outline-success btn-sm">
        <i class="bi bi-envelope me-1"></i> Contatta il supporto
    </a>
</div>
