<?php
$tabs = [
    ['key' => 'subscriptions', 'label' => 'Abbonamenti', 'url' => url('admin/subscriptions')],
    ['key' => 'plans',         'label' => 'Piani',       'url' => url('admin/subscriptions/plans')],
    ['key' => 'services',      'label' => 'Servizi',     'url' => url('admin/subscriptions/services')],
];
$colors = ['#1565C0', '#7B1FA2', '#E65100', '#2E7D32', '#C62828'];
?>

<h1 class="admin-page-title">Abbonamenti</h1>
<p class="admin-page-sub">Gestisci abbonamenti, piani e catalogo servizi</p>

<!-- Tabs -->
<div class="adm-tabs">
    <?php foreach ($tabs as $tab): ?>
    <a class="adm-tab <?= ($activeTab ?? '') === $tab['key'] ? 'active' : '' ?>" href="<?= $tab['url'] ?>">
        <?= $tab['label'] ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Info + New button -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:.5rem;">
    <div style="font-size:.82rem;color:#6c757d;">
        <i class="bi bi-info-circle me-1"></i>
        Coperti illimitati per tutti i piani. La differenza &egrave; nei <strong>servizi inclusi</strong>.
    </div>
    <button type="button" class="admin-qa admin-qa-primary" style="font-size:.82rem;padding:.5rem 1rem;" data-bs-toggle="collapse" data-bs-target="#newPlanForm">
        <i class="bi bi-plus-circle"></i> Nuovo Piano
    </button>
</div>

<!-- New Plan Form (collapsible) -->
<div class="collapse <?= !$editId ? '' : '' ?>" id="newPlanForm">
    <div class="adm-card" style="margin-bottom:1.5rem;">
        <div class="adm-card-hdr">
            <span class="adm-card-hdr-title"><i class="bi bi-plus-circle me-1"></i> Nuovo Piano</span>
        </div>
        <div class="adm-card-body">
            <form method="POST" action="<?= url('admin/subscriptions/plans') ?>">
                <?= csrf_field() ?>
                <div class="adm-form-grid">
                    <div>
                        <label class="adm-form-label">Nome piano *</label>
                        <input type="text" name="name" class="adm-form-input" required maxlength="100" placeholder="Es. Premium">
                    </div>
                    <div>
                        <label class="adm-form-label">Prezzo mensile (&euro;) *</label>
                        <input type="number" name="price" class="adm-form-input" required min="0" step="0.01" placeholder="49.00">
                    </div>
                </div>
                <div class="adm-form-grid">
                    <div>
                        <label class="adm-form-label">Mesi da pagare (Semestrale)</label>
                        <input type="number" name="billing_months_semi" class="adm-form-input" min="1" max="6" value="5">
                        <div style="font-size:.68rem;color:#6c757d;margin-top:.2rem;">Su 6 mesi (default: 5 = 1 mese gratis)</div>
                    </div>
                    <div>
                        <label class="adm-form-label">Mesi da pagare (Annuale)</label>
                        <input type="number" name="billing_months_annual" class="adm-form-input" min="1" max="12" value="10">
                        <div style="font-size:.68rem;color:#6c757d;margin-top:.2rem;">Su 12 mesi (default: 10 = 2 mesi gratis)</div>
                    </div>
                </div>
                <div class="adm-form-grid">
                    <div>
                        <label class="adm-form-label">Colore badge</label>
                        <div class="adm-color-picker">
                            <?php foreach ($colors as $c): ?>
                            <label class="adm-color-opt">
                                <input type="radio" name="color" value="<?= $c ?>" <?= $c === '#1565C0' ? 'checked' : '' ?>>
                                <span style="background:<?= $c ?>;"></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div>
                        <label class="adm-form-label">Descrizione</label>
                        <input type="text" name="description" class="adm-form-input" maxlength="500" placeholder="Breve descrizione del piano">
                    </div>
                </div>

                <label class="adm-form-label" style="margin-bottom:.5rem;">Servizi inclusi</label>
                <div class="adm-svc-grid">
                    <?php foreach ($services as $svc): ?>
                    <label class="adm-svc-check">
                        <input type="checkbox" name="services[]" value="<?= $svc['id'] ?>">
                        <div>
                            <div class="adm-svc-check-label"><?= e($svc['name']) ?></div>
                            <?php if ($svc['description']): ?>
                            <div class="adm-svc-check-desc"><?= e($svc['description']) ?></div>
                            <?php endif; ?>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>

                <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1.25rem;">
                    <button type="button" class="admin-qa admin-qa-outline" style="font-size:.82rem;padding:.45rem .85rem;" data-bs-toggle="collapse" data-bs-target="#newPlanForm">Annulla</button>
                    <button type="submit" class="admin-qa admin-qa-primary" style="font-size:.82rem;padding:.45rem .85rem;">
                        <i class="bi bi-check-circle"></i> Crea Piano
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editId && isset($editPlan)): ?>
<!-- Edit Plan Form -->
<div class="adm-card" style="margin-bottom:1.5rem;border:2px solid var(--admin-accent);">
    <div class="adm-card-hdr">
        <span class="adm-card-hdr-title"><i class="bi bi-pencil me-1"></i> Modifica Piano: <?= e($editPlan['name']) ?></span>
        <a href="<?= url('admin/subscriptions/plans') ?>" class="admin-qa admin-qa-outline" style="font-size:.75rem;padding:.35rem .75rem;">Annulla</a>
    </div>
    <div class="adm-card-body">
        <form method="POST" action="<?= url("admin/subscriptions/plans/{$editId}") ?>">
            <?= csrf_field() ?>
            <div class="adm-form-grid">
                <div>
                    <label class="adm-form-label">Nome piano *</label>
                    <input type="text" name="name" class="adm-form-input" required maxlength="100" value="<?= e($editPlan['name']) ?>">
                </div>
                <div>
                    <label class="adm-form-label">Prezzo mensile (&euro;) *</label>
                    <input type="number" name="price" class="adm-form-input" required min="0" step="0.01" value="<?= $editPlan['price'] ?>">
                </div>
            </div>
            <div class="adm-form-grid">
                <div>
                    <label class="adm-form-label">Mesi da pagare (Semestrale)</label>
                    <input type="number" name="billing_months_semi" class="adm-form-input" min="1" max="6" value="<?= (int)($editPlan['billing_months_semi'] ?? 5) ?>">
                    <div style="font-size:.68rem;color:#6c757d;margin-top:.2rem;">Su 6 mesi (default: 5 = 1 mese gratis)</div>
                </div>
                <div>
                    <label class="adm-form-label">Mesi da pagare (Annuale)</label>
                    <input type="number" name="billing_months_annual" class="adm-form-input" min="1" max="12" value="<?= (int)($editPlan['billing_months_annual'] ?? 10) ?>">
                    <div style="font-size:.68rem;color:#6c757d;margin-top:.2rem;">Su 12 mesi (default: 10 = 2 mesi gratis)</div>
                </div>
            </div>
            <div class="adm-form-grid">
                <div>
                    <label class="adm-form-label">Colore badge</label>
                    <div class="adm-color-picker">
                        <?php foreach ($colors as $c): ?>
                        <label class="adm-color-opt">
                            <input type="radio" name="color" value="<?= $c ?>" <?= $editPlan['color'] === $c ? 'checked' : '' ?>>
                            <span style="background:<?= $c ?>;"></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <label class="adm-form-label">Descrizione</label>
                    <input type="text" name="description" class="adm-form-input" maxlength="500" value="<?= e($editPlan['description'] ?? '') ?>">
                </div>
            </div>

            <label class="adm-form-label" style="margin-bottom:.5rem;">Servizi inclusi</label>
            <div class="adm-svc-grid">
                <?php foreach ($services as $svc): ?>
                <label class="adm-svc-check <?= in_array($svc['id'], $editServiceIds ?? []) ? 'checked' : '' ?>">
                    <input type="checkbox" name="services[]" value="<?= $svc['id'] ?>"
                           <?= in_array($svc['id'], $editServiceIds ?? []) ? 'checked' : '' ?>>
                    <div>
                        <div class="adm-svc-check-label"><?= e($svc['name']) ?></div>
                        <?php if ($svc['description']): ?>
                        <div class="adm-svc-check-desc"><?= e($svc['description']) ?></div>
                        <?php endif; ?>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>

            <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1.25rem;">
                <a href="<?= url('admin/subscriptions/plans') ?>" class="admin-qa admin-qa-outline" style="font-size:.82rem;padding:.45rem .85rem;">Annulla</a>
                <button type="submit" class="admin-qa admin-qa-primary" style="font-size:.82rem;padding:.45rem .85rem;">
                    <i class="bi bi-check-circle"></i> Salva Piano
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Plan List -->
<div class="adm-plans-list">
    <?php if (empty($plans)): ?>
    <div class="adm-card">
        <div class="adm-card-body adm-empty">Nessun piano configurato.</div>
    </div>
    <?php endif; ?>

    <?php foreach ($plans as $plan): ?>
    <div class="adm-plan-row <?= !$plan['is_active'] ? 'inactive' : '' ?>">
        <div class="adm-plan-dot" style="background:<?= e($plan['color']) ?>;"></div>
        <div class="adm-plan-info">
            <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
                <span class="adm-plan-name"><?= e($plan['name']) ?></span>
                <span class="adm-plan-price">&euro;<?= number_format($plan['price'], 0, ',', '.') ?>/mese</span>
                <?php if ($plan['is_default']): ?>
                <span style="font-size:.6rem;font-weight:700;background:#E3F2FD;color:#1565C0;padding:2px 6px;border-radius:4px;">PREDEFINITO</span>
                <?php endif; ?>
                <?php if (!$plan['is_active']): ?>
                <span style="font-size:.6rem;font-weight:700;background:#F5F5F5;color:#757575;padding:2px 6px;border-radius:4px;">INATTIVO</span>
                <?php endif; ?>
            </div>
            <?php if ($plan['description']): ?>
            <div class="adm-plan-desc"><?= e($plan['description']) ?></div>
            <?php endif; ?>
            <div class="adm-plan-services">
                <?php foreach ($plan['services'] as $svc): ?>
                <span class="adm-plan-svc-tag"><?= e($svc['name']) ?></span>
                <?php endforeach; ?>
                <?php if (empty($plan['services'])): ?>
                <span style="font-size:.72rem;color:#adb5bd;font-style:italic;">Nessun servizio associato</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="adm-plan-stats">
            <div class="adm-plan-stats-num"><?= (int)$plan['active_count'] ?></div>
            <div class="adm-plan-stats-label">ristoranti</div>
        </div>
        <div class="adm-plan-actions">
            <a class="adm-action-btn" title="Modifica" href="<?= url("admin/subscriptions/plans/{$plan['id']}/edit") ?>">
                <i class="bi bi-pencil"></i>
            </a>
            <form method="POST" action="<?= url("admin/subscriptions/plans/{$plan['id']}/duplicate") ?>" style="display:inline;">
                <?= csrf_field() ?>
                <button type="submit" class="adm-action-btn" title="Duplica"><i class="bi bi-copy"></i></button>
            </form>
            <?php if (!$plan['is_default']): ?>
            <form method="POST" action="<?= url("admin/subscriptions/plans/{$plan['id']}/delete") ?>" style="display:inline;"
                  onsubmit="return confirm('Eliminare il piano <?= e($plan['name']) ?>?')">
                <?= csrf_field() ?>
                <button type="submit" class="adm-action-btn adm-action-danger" title="Elimina"><i class="bi bi-trash"></i></button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
