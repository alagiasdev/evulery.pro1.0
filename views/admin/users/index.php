<?php
$roleLabels = ['super_admin' => 'Super Admin', 'owner' => 'Proprietario', 'staff' => 'Staff'];
$roleColors = ['super_admin' => '#7B1FA2', 'owner' => '#1565C0', 'staff' => '#616161'];
?>

<h1 class="admin-page-title">Utenti</h1>
<p class="admin-page-sub">Gestisci tutti gli utenti della piattaforma</p>

<!-- Filters -->
<div class="adm-card" style="margin-bottom:1.25rem;">
    <div class="adm-card-body" style="padding:.85rem 1.25rem;">
        <form method="GET" action="<?= url('admin/users') ?>" class="adm-filter-form" style="display:flex;gap:.65rem;align-items:flex-end;flex-wrap:wrap;">
            <div style="flex:1.5;min-width:180px;">
                <label class="adm-form-label" style="margin-bottom:.25rem;">Cerca</label>
                <input type="text" name="q" class="adm-form-input" style="font-size:.82rem;" placeholder="Nome o email..." value="<?= e($filter['q'] ?? '') ?>">
            </div>
            <div style="flex:1;min-width:130px;">
                <label class="adm-form-label" style="margin-bottom:.25rem;">Ruolo</label>
                <select name="role" class="adm-form-input" style="font-size:.82rem;">
                    <option value="">Tutti</option>
                    <option value="super_admin" <?= ($filter['role'] ?? '') === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                    <option value="owner" <?= ($filter['role'] ?? '') === 'owner' ? 'selected' : '' ?>>Proprietario</option>
                    <option value="staff" <?= ($filter['role'] ?? '') === 'staff' ? 'selected' : '' ?>>Staff</option>
                </select>
            </div>
            <div style="flex:1;min-width:160px;">
                <label class="adm-form-label" style="margin-bottom:.25rem;">Ristorante</label>
                <select name="tenant" class="adm-form-input" style="font-size:.82rem;">
                    <option value="">Tutti</option>
                    <?php foreach ($tenants as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= ($filter['tenant'] ?? '') == $t['id'] ? 'selected' : '' ?>>
                        <?= e($t['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="flex:1;min-width:110px;">
                <label class="adm-form-label" style="margin-bottom:.25rem;">Stato</label>
                <select name="status" class="adm-form-input" style="font-size:.82rem;">
                    <option value="">Tutti</option>
                    <option value="1" <?= ($filter['status'] ?? '') === '1' ? 'selected' : '' ?>>Attivo</option>
                    <option value="0" <?= ($filter['status'] ?? '') === '0' ? 'selected' : '' ?>>Inattivo</option>
                </select>
            </div>
            <div style="display:flex;gap:.35rem;">
                <button type="submit" class="admin-qa admin-qa-primary" style="font-size:.82rem;padding:.45rem .85rem;">
                    <i class="bi bi-funnel"></i> Filtra
                </button>
                <a href="<?= url('admin/users') ?>" class="admin-qa admin-qa-outline" style="font-size:.82rem;padding:.45rem .85rem;">
                    <i class="bi bi-x-circle"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="adm-card">
    <div class="adm-card-hdr">
        <span class="adm-card-hdr-title"><i class="bi bi-people me-1"></i> Elenco utenti</span>
        <?php if (!empty($pagination)): ?>
        <span style="font-size:.75rem;color:#6c757d;"><?= $pagination['from'] ?>-<?= $pagination['to'] ?> di <?= $pagination['totalItems'] ?></span>
        <?php endif; ?>
    </div>

    <?php if (empty($users)): ?>
    <div class="adm-card-body adm-empty">Nessun utente trovato.</div>
    <?php else: ?>
    <div class="adm-table-wrap">
    <table class="adm-table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Email</th>
                <th>Ruolo</th>
                <th>Ristorante</th>
                <th>Stato</th>
                <th>Ultimo login</th>
                <th style="width:100px;">Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u):
                $roleLbl = $roleLabels[$u['role']] ?? ucfirst($u['role']);
                $roleClr = $roleColors[$u['role']] ?? '#616161';
            ?>
            <tr>
                <td style="font-weight:600;font-size:.85rem;">
                    <?= e($u['first_name'] . ' ' . $u['last_name']) ?>
                </td>
                <td style="font-size:.82rem;"><?= e($u['email']) ?></td>
                <td>
                    <span style="font-size:.72rem;font-weight:700;padding:2px 8px;border-radius:4px;background:<?= $roleClr ?>15;color:<?= $roleClr ?>;">
                        <?= e($roleLbl) ?>
                    </span>
                </td>
                <td style="font-size:.82rem;">
                    <?php if ($u['tenant_name'] ?? ''): ?>
                        <a href="<?= url("admin/tenants/{$u['tenant_id']}/edit") ?>" style="color:var(--admin-accent);text-decoration:none;font-weight:500;">
                            <?= e($u['tenant_name']) ?>
                        </a>
                    <?php else: ?>
                        <span style="color:#adb5bd;">&mdash;</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($u['is_active']): ?>
                    <span class="adm-badge adm-badge-active" style="font-size:.68rem;">Attivo</span>
                    <?php else: ?>
                    <span class="adm-badge adm-badge-inactive" style="font-size:.68rem;">Inattivo</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:.78rem;color:#6c757d;">
                    <?= $u['last_login_at'] ? format_date($u['last_login_at'], 'd/m/Y H:i') : '<span style="font-style:italic;">Mai</span>' ?>
                </td>
                <td>
                    <?php if ($u['role'] !== 'super_admin' && $u['tenant_id']): ?>
                    <form method="POST" action="<?= url("admin/impersonate/{$u['id']}") ?>" style="display:inline;">
                        <?= csrf_field() ?>
                        <button type="submit" class="adm-action-btn" title="Accedi come questo utente" style="font-size:.78rem;">
                            <i class="bi bi-box-arrow-in-right"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div><!-- /adm-table-wrap -->

    <?php if (!empty($pagination)): ?>
    <div class="pagination-bar" style="padding:.75rem 1rem;border-top:1px solid #eee;">
        <span class="pagination-info" style="font-size:.8rem;color:#6c757d;"><?= $pagination['from'] ?>-<?= $pagination['to'] ?> di <?= $pagination['totalItems'] ?> utenti</span>
        <div class="pagination-nav">
            <?php if ($pagination['prev']): ?>
            <a href="<?= $pagination['prev'] ?>" class="pg-btn"><i class="bi bi-chevron-left"></i></a>
            <?php endif; ?>
            <?php foreach ($pagination['pages'] as $pg): ?>
                <?php if ($pg['type'] === 'gap'): ?>
                    <span class="pg-gap">&hellip;</span>
                <?php else: ?>
                    <a href="<?= $pg['url'] ?>" class="pg-btn <?= $pg['active'] ? 'pg-active' : '' ?>"><?= $pg['number'] ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if ($pagination['next']): ?>
            <a href="<?= $pagination['next'] ?>" class="pg-btn"><i class="bi bi-chevron-right"></i></a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
