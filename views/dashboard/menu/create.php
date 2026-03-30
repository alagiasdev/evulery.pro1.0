<?php $old = $old ?? []; ?>

<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">Nuovo Piatto</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1rem;">Aggiungi un piatto al menu</p>

<div class="page-back" style="margin-bottom:1rem;">
    <a href="<?= url('dashboard/menu') ?>"><i class="bi bi-arrow-left"></i> Torna al menu</a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card" style="padding:1.25rem;">
            <form method="POST" action="<?= url('dashboard/menu/items') ?>" enctype="multipart/form-data">
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
                           placeholder="Es. Bruschetta al pomodoro" value="<?= e($old['name'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Descrizione</label>
                    <textarea name="description" class="form-control form-control-sm" rows="2" maxlength="2000"
                              placeholder="Ingredienti, preparazione..."><?= e($old['description'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Prezzo (&euro;) *</label>
                    <input type="number" name="price" class="form-control form-control-sm" required step="0.01" min="0.01"
                           placeholder="0.00" value="<?= e($old['price'] ?? '') ?>" style="max-width:150px;">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Foto (opzionale)</label>
                    <input type="file" name="image" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                    <div style="font-size:.72rem; color:#6c757d; margin-top:.25rem;">JPG, PNG o WebP. Max 2MB.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Allergeni</label>
                    <div class="dm-allergen-grid">
                        <?php foreach ($allergens as $key => $label): ?>
                        <label class="dm-allergen-check">
                            <input type="checkbox" name="allergens[]" value="<?= $key ?>"
                                   <?= in_array($key, $old['allergens'] ?? []) ? 'checked' : '' ?>>
                            <span class="dm-allergen-dot" style="background:<?= $allergenColors[$key] ?>;"><?= $allergenIcons[$key] ?></span>
                            <span class="dm-allergen-label"><?= $label ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mb-3 d-flex gap-4">
                    <label class="d-flex align-items-center gap-2" style="font-size:.82rem; cursor:pointer;">
                        <input type="checkbox" name="is_available" value="1" <?= ($old['is_available'] ?? '1') ? 'checked' : '' ?>>
                        <span class="fw-semibold">Disponibile</span>
                    </label>
                    <label class="d-flex align-items-center gap-2" style="font-size:.82rem; cursor:pointer;">
                        <input type="checkbox" name="is_daily_special" value="1" <?= ($old['is_daily_special'] ?? '') ? 'checked' : '' ?>>
                        <span class="fw-semibold">Piatto del giorno</span>
                    </label>
                </div>

                <?php if (tenant_can('online_ordering')): ?>
                <div class="card mb-3" style="background:#f8f9fa; border:1px solid #dee2e6; padding:.75rem;">
                    <label class="form-label fw-semibold" style="font-size:.82rem;"><i class="bi bi-bag-check me-1"></i> Ordini online</label>
                    <div class="mb-2">
                        <label class="d-flex align-items-center gap-2" style="font-size:.82rem; cursor:pointer;">
                            <input type="checkbox" name="is_orderable" value="1" <?= ($old['is_orderable'] ?? '') ? 'checked' : '' ?>>
                            <span class="fw-semibold">Ordinabile online</span>
                        </label>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label style="font-size:.75rem; color:#6c757d;">Tempo preparazione (min)</label>
                            <input type="number" name="prep_minutes" class="form-control form-control-sm" min="1" max="180"
                                   value="<?= e($old['prep_minutes'] ?? '') ?>" placeholder="Default tenant">
                        </div>
                        <div class="col-6">
                            <label style="font-size:.75rem; color:#6c757d;">Max ordini giornalieri</label>
                            <input type="number" name="max_daily_qty" class="form-control form-control-sm" min="1"
                                   value="<?= e($old['max_daily_qty'] ?? '') ?>" placeholder="Illimitato">
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <button type="submit" class="btn-save" style="width:100%;">
                    <i class="bi bi-plus-circle me-1"></i> Aggiungi Piatto
                </button>
            </form>
        </div>
    </div>
</div>
