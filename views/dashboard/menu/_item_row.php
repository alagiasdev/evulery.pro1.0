<div class="dm-admin-item">
    <?php if ($item['image_url']): ?>
    <img src="<?= e($item['image_url']) ?>" alt="" class="dm-admin-item-thumb">
    <?php else: ?>
    <div class="dm-admin-item-thumb dm-admin-item-nophoto"><i class="bi bi-image"></i></div>
    <?php endif; ?>
    <div class="dm-admin-item-info">
        <div class="dm-admin-item-name">
            <?= e($item['name']) ?>
            <?php if (!empty($item['is_orderable']) && tenant_can('online_ordering')): ?>
            <span class="badge" style="background:#e8f5e9; color:#00844A; font-size:.65rem; font-weight:600;"><i class="bi bi-bag-check" style="font-size:.6rem;"></i> Ordinabile</span>
            <?php endif; ?>
            <?php if ($item['is_daily_special']): ?>
            <span class="badge" style="background:#FFF3E0; color:#E65100; font-size:.65rem; font-weight:600;">Del giorno</span>
            <?php endif; ?>
            <?php if (!$item['is_available']): ?>
            <span class="badge" style="background:#f8d7da; color:#842029; font-size:.65rem; font-weight:600;">Non disponibile</span>
            <?php endif; ?>
        </div>
        <div class="dm-admin-item-meta">
            <span class="dm-admin-item-price"><?= number_format((float)$item['price'], 2, ',', '.') ?> &euro;</span>
            <?php $itemAllergens = $item['allergens'] ?? []; ?>
            <?php if (!empty($itemAllergens)): ?>
            <span class="dm-admin-allergen-dots">
                <?php foreach ($itemAllergens as $aKey): ?>
                <span class="dm-allergen-dot" style="background:<?= $allergenColors[$aKey] ?? '#999' ?>;" title="<?= e($allergens[$aKey] ?? $aKey) ?>">
                    <?= $allergenIcons[$aKey] ?? '?' ?>
                </span>
                <?php endforeach; ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
    <div class="dm-admin-item-actions">
        <form method="POST" action="<?= url("dashboard/menu/items/{$item['id']}/toggle") ?>" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="promo-toggle-btn" title="<?= $item['is_available'] ? 'Segna non disponibile' : 'Segna disponibile' ?>">
                <div class="promo-toggle <?= $item['is_available'] ? 'promo-toggle-on' : '' ?>" style="transform:scale(.8);">
                    <div class="promo-toggle-knob"></div>
                </div>
            </button>
        </form>
        <form method="POST" action="<?= url("dashboard/menu/items/{$item['id']}/toggle-special") ?>" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm <?= $item['is_daily_special'] ? 'btn-warning' : 'btn-outline-secondary' ?>" title="Piatto del giorno" style="font-size:.7rem;">
                <i class="bi bi-star<?= $item['is_daily_special'] ? '-fill' : '' ?>"></i>
            </button>
        </form>
        <a href="<?= url("dashboard/menu/items/{$item['id']}/edit") ?>" class="btn btn-sm btn-outline-secondary" title="Modifica">
            <i class="bi bi-pencil"></i>
        </a>
        <form method="POST" action="<?= url("dashboard/menu/items/{$item['id']}/delete") ?>" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-outline-danger" title="Elimina"
                    data-confirm="Eliminare &quot;<?= e($item['name']) ?>&quot;?">
                <i class="bi bi-trash3"></i>
            </button>
        </form>
    </div>
</div>
