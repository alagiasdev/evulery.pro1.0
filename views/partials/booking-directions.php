<?php
/**
 * Partial: indirizzo del ristorante + link "Come arrivare" (Google Maps).
 * Richiede $tenant. Si mostra solo se l'indirizzo è configurato.
 */
$dirAddress = trim((string)($tenant['address'] ?? ''));
if ($dirAddress !== ''):
    $dirQuery = urlencode(trim((string)($tenant['name'] ?? '') . ' ' . $dirAddress));
?>
<div class="bw-conf-directions">
    <div class="bw-conf-directions-info">
        <i class="bi bi-geo-alt-fill"></i>
        <span><?= e($dirAddress) ?></span>
    </div>
    <a href="https://www.google.com/maps/search/?api=1&amp;query=<?= $dirQuery ?>"
       target="_blank" rel="noopener" class="bw-conf-directions-btn">
        <i class="bi bi-signpost-2"></i> Come arrivare
    </a>
</div>
<?php endif; ?>
