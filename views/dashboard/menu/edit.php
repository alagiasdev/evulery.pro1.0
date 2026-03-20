<?php
$old = $old ?? $item;
$existingAllergens = $old['allergens'] ?? [];
if (is_string($existingAllergens)) {
    $existingAllergens = json_decode($existingAllergens, true) ?? [];
}
?>

<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">Modifica Piatto</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1rem;">Modifica i dettagli del piatto</p>

<div class="page-back" style="margin-bottom:1rem;">
    <a href="<?= url('dashboard/menu') ?>"><i class="bi bi-arrow-left"></i> Torna al menu</a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card" style="padding:1.25rem;">
            <form method="POST" action="<?= url("dashboard/menu/items/{$item['id']}/update") ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Categoria *</label>
                    <select name="category_id" class="form-select form-select-sm" required>
                        <option value="">Seleziona...</option>
                        <?php foreach ($hierarchy as $parent): ?>
                            <?php if (empty($parent['children'])): ?>
                            <option value="<?= (int)$parent['id'] ?>" <?= ($old['category_id'] ?? '') == $parent['id'] ? 'selected' : '' ?>><?= e($parent['name']) ?></option>
                            <?php else: ?>
                            <optgroup label="<?= e($parent['name']) ?>">
                                <option value="<?= (int)$parent['id'] ?>" <?= ($old['category_id'] ?? '') == $parent['id'] ? 'selected' : '' ?>><?= e($parent['name']) ?> (generale)</option>
                                <?php foreach ($parent['children'] as $child): ?>
                                <option value="<?= (int)$child['id'] ?>" <?= ($old['category_id'] ?? '') == $child['id'] ? 'selected' : '' ?>><?= e($child['name']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Nome piatto *</label>
                    <input type="text" name="name" class="form-control form-control-sm" required maxlength="150"
                           value="<?= e($old['name'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Descrizione</label>
                    <textarea name="description" class="form-control form-control-sm" rows="2" maxlength="2000"><?= e($old['description'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Prezzo (&euro;) *</label>
                    <input type="number" name="price" class="form-control form-control-sm" required step="0.01" min="0.01"
                           value="<?= e($old['price'] ?? '') ?>" style="max-width:150px;">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Foto</label>
                    <?php if (!empty($item['image_url'])): ?>
                    <div style="margin-bottom:.5rem;">
                        <img src="<?= e($item['image_url']) ?>" alt="" style="max-width:150px; max-height:100px; border-radius:8px; object-fit:cover;">
                        <label class="d-flex align-items-center gap-1 mt-1" style="font-size:.78rem; color:#dc3545; cursor:pointer;">
                            <input type="checkbox" name="remove_image" value="1"> Rimuovi immagine
                        </label>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="image" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                    <div style="font-size:.72rem; color:#6c757d; margin-top:.25rem;">JPG, PNG o WebP. Max 2MB.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Allergeni</label>
                    <div class="dm-allergen-grid">
                        <?php foreach ($allergens as $key => $label): ?>
                        <label class="dm-allergen-check">
                            <input type="checkbox" name="allergens[]" value="<?= $key ?>"
                                   <?= in_array($key, $existingAllergens) ? 'checked' : '' ?>>
                            <span class="dm-allergen-dot" style="background:<?= $allergenColors[$key] ?>;"><?= $allergenIcons[$key] ?></span>
                            <span class="dm-allergen-label"><?= $label ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mb-3 d-flex gap-4">
                    <label class="d-flex align-items-center gap-2" style="font-size:.82rem; cursor:pointer;">
                        <input type="checkbox" name="is_available" value="1" <?= ($old['is_available'] ?? 0) ? 'checked' : '' ?>>
                        <span class="fw-semibold">Disponibile</span>
                    </label>
                    <label class="d-flex align-items-center gap-2" style="font-size:.82rem; cursor:pointer;">
                        <input type="checkbox" name="is_daily_special" value="1" <?= ($old['is_daily_special'] ?? 0) ? 'checked' : '' ?>>
                        <span class="fw-semibold">Piatto del giorno</span>
                    </label>
                </div>

                <div class="d-flex gap-2">
                    <a href="<?= url('dashboard/menu') ?>" class="btn btn-outline-secondary" style="flex:1;">Annulla</a>
                    <button type="submit" class="btn-save" style="flex:2;">
                        <i class="bi bi-check-lg me-1"></i> Salva Modifiche
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
