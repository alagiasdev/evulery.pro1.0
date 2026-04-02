<?php
$tabs = [
    ['key' => 'subscriptions', 'label' => 'Abbonamenti', 'url' => url('admin/subscriptions')],
    ['key' => 'plans',         'label' => 'Piani',       'url' => url('admin/subscriptions/plans')],
    ['key' => 'services',      'label' => 'Servizi',     'url' => url('admin/subscriptions/services')],
];
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
        Ogni servizio pu&ograve; essere associato a uno o pi&ugrave; piani.
    </div>
    <button type="button" class="admin-qa admin-qa-primary" style="font-size:.82rem;padding:.5rem 1rem;" data-bs-toggle="collapse" data-bs-target="#newServiceForm">
        <i class="bi bi-plus-circle"></i> Nuovo Servizio
    </button>
</div>

<!-- New Service Form (collapsible) -->
<div class="collapse" id="newServiceForm">
    <div class="adm-card" style="margin-bottom:1.5rem;">
        <div class="adm-card-hdr">
            <span class="adm-card-hdr-title"><i class="bi bi-plus-circle me-1"></i> Nuovo Servizio</span>
        </div>
        <div class="adm-card-body">
            <form method="POST" action="<?= url('admin/subscriptions/services') ?>">
                <?= csrf_field() ?>
                <div class="adm-form-grid">
                    <div>
                        <label class="adm-form-label">Nome servizio *</label>
                        <input type="text" name="name" class="adm-form-input" required maxlength="100" placeholder="Es. SMS Marketing">
                    </div>
                    <div>
                        <label class="adm-form-label">Chiave tecnica *</label>
                        <input type="text" name="key" class="adm-form-input" required maxlength="50" placeholder="Es. sms_marketing" pattern="[a-z0-9_]+">
                        <div style="font-size:.68rem;color:#adb5bd;margin-top:.2rem;">Solo lettere minuscole, numeri e underscore</div>
                    </div>
                </div>
                <div>
                    <label class="adm-form-label">Descrizione</label>
                    <input type="text" name="description" class="adm-form-input" maxlength="500" placeholder="Breve descrizione del servizio">
                </div>

                <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem;">
                    <button type="button" class="admin-qa admin-qa-outline" style="font-size:.82rem;padding:.45rem .85rem;" data-bs-toggle="collapse" data-bs-target="#newServiceForm">Annulla</button>
                    <button type="submit" class="admin-qa admin-qa-primary" style="font-size:.82rem;padding:.45rem .85rem;">
                        <i class="bi bi-check-circle"></i> Crea Servizio
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Services List -->
<div class="adm-card">
    <div class="adm-card-hdr">
        <span class="adm-card-hdr-title">Catalogo servizi (<?= count($services) ?>)</span>
    </div>
    <div>
        <?php if (empty($services)): ?>
        <div class="adm-card-body adm-empty">Nessun servizio configurato.</div>
        <?php else: ?>
        <?php foreach ($services as $svc): ?>
        <div class="adm-svc-row" id="svc-<?= $svc['id'] ?>">
            <div class="adm-svc-row-info">
                <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;">
                    <span class="adm-svc-row-label"><?= e($svc['name']) ?></span>
                    <span class="adm-svc-row-key"><?= e($svc['key']) ?></span>
                </div>
                <?php if ($svc['description']): ?>
                <div class="adm-svc-row-desc"><?= e($svc['description']) ?></div>
                <?php endif; ?>
            </div>
            <div class="adm-svc-row-plans">
                <?php foreach ($svc['plans'] as $p): ?>
                <span class="adm-badge-plan" style="background:<?= e($p['plan_color']) ?>15;color:<?= e($p['plan_color']) ?>;font-size:.6rem;">
                    <?= e($p['plan_name']) ?>
                </span>
                <?php endforeach; ?>
                <?php if (empty($svc['plans'])): ?>
                <span style="font-size:.68rem;color:#adb5bd;font-style:italic;">Nessun piano</span>
                <?php endif; ?>
            </div>
            <div class="adm-svc-row-actions">
                <button type="button" class="adm-action-btn" title="Modifica"
                        data-bs-toggle="collapse" data-bs-target="#editSvc<?= $svc['id'] ?>">
                    <i class="bi bi-pencil"></i>
                </button>
                <?php if (empty($svc['plans'])): ?>
                <form method="POST" action="<?= url("admin/subscriptions/services/{$svc['id']}/delete") ?>" style="display:inline;"
                      data-confirm="Eliminare il servizio <?= e($svc['name']) ?>?">
                    <?= csrf_field() ?>
                    <button type="submit" class="adm-action-btn adm-action-danger" title="Elimina"><i class="bi bi-trash"></i></button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <!-- Inline edit form -->
        <div class="collapse" id="editSvc<?= $svc['id'] ?>">
            <div style="padding:.75rem 1.25rem;background:#fafbfc;border-bottom:1px solid #f0f0f0;">
                <form method="POST" action="<?= url("admin/subscriptions/services/{$svc['id']}") ?>">
                    <?= csrf_field() ?>
                    <div class="adm-form-grid">
                        <div>
                            <label class="adm-form-label">Nome</label>
                            <input type="text" name="name" class="adm-form-input" required value="<?= e($svc['name']) ?>">
                        </div>
                        <div>
                            <label class="adm-form-label">Descrizione</label>
                            <input type="text" name="description" class="adm-form-input" value="<?= e($svc['description'] ?? '') ?>">
                        </div>
                    </div>
                    <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:.5rem;">
                        <button type="button" class="admin-qa admin-qa-outline" style="font-size:.75rem;padding:.35rem .65rem;" data-bs-toggle="collapse" data-bs-target="#editSvc<?= $svc['id'] ?>">Annulla</button>
                        <button type="submit" class="admin-qa admin-qa-primary" style="font-size:.75rem;padding:.35rem .65rem;">
                            <i class="bi bi-check-circle"></i> Salva
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
