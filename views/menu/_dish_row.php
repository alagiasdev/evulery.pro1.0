<?php
/**
 * Riga piatto standard del menu pubblico (categorie non-vino).
 * Variabili in scope: $item, $allergens.
 */
?>
<div class="dm-item <?= $item['image_url'] ? '' : 'dm-item--text-only' ?> dm-searchable" data-name="<?= e(mb_strtolower($item['name'])) ?>">
    <?php if ($item['image_url']): ?>
    <div class="dm-item-img-wrap">
        <img src="<?= e($item['image_url']) ?>" alt="" class="dm-item-img" loading="lazy">
    </div>
    <?php endif; ?>
    <div class="dm-item-content">
        <div class="dm-item-top">
            <span class="dm-item-name"><?= e($item['name']) ?></span>
            <span class="dm-item-price"><?= number_format((float)$item['price'], 2, ',', '.') ?> &euro;</span>
        </div>
        <?php if ($item['description']): ?>
        <div class="dm-item-desc"><?= e($item['description']) ?></div>
        <?php endif; ?>
        <?php if (!empty($item['allergens'])): ?>
        <div class="dm-item-meta">
            <div class="dm-allergen-tags">
                <?php foreach ($item['allergens'] as $aKey): ?>
                <span class="dm-allergen-tag dm-at-<?= e($aKey) ?>"><span class="dm-allergen-tag-dot"></span><?= e($allergens[$aKey] ?? $aKey) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
