<?php
/**
 * Riga "vino" per il menu pubblico (categorie is_wine).
 * Variabili in scope: $item (con price = calice, price_bottle = bottiglia).
 * Niente foto, niente allergeni: doppio prezzo + produttore/annata (description).
 */
    $wHasCalice = $item['price'] !== null && $item['price'] !== '';
    $wHasBottle = isset($item['price_bottle']) && $item['price_bottle'] !== null && $item['price_bottle'] !== '';
?>
<div class="dm-wine dm-searchable" data-name="<?= e(mb_strtolower($item['name'])) ?>">
    <div class="dm-wine-head">
        <span class="dm-wine-name"><?= e($item['name']) ?></span>
        <span class="dm-wine-prices">
            <?php if ($wHasCalice): ?><span class="dm-wp">Calice <b><?= number_format((float)$item['price'], 2, ',', '.') ?>&nbsp;&euro;</b></span><?php endif; ?>
            <?php if ($wHasCalice && $wHasBottle): ?><span class="dm-wp-sep">&middot;</span><?php endif; ?>
            <?php if ($wHasBottle): ?><span class="dm-wp">Bottiglia <b><?= number_format((float)$item['price_bottle'], 2, ',', '.') ?>&nbsp;&euro;</b></span><?php endif; ?>
        </span>
    </div>
    <?php if (!empty($item['description'])): ?>
    <div class="dm-wine-sub"><?= e($item['description']) ?></div>
    <?php endif; ?>
</div>
