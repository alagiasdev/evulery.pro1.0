<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">Ristoranti</h1>
        <p class="admin-page-sub" style="margin-bottom:0;">Gestisci tutti i ristoranti della piattaforma</p>
    </div>
    <a href="<?= url('admin/tenants/create') ?>" class="adm-btn adm-btn-primary">
        <i class="bi bi-plus-circle"></i> Nuovo Ristorante
    </a>
</div>

<!-- Search bar -->
<form method="GET" action="<?= url('admin/tenants') ?>" style="margin-bottom:1rem;">
    <div style="display:flex;gap:.5rem;align-items:center;">
        <div style="position:relative;flex:1;max-width:400px;">
            <i class="bi bi-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#adb5bd;"></i>
            <input type="text" name="q" value="<?= e($search ?? '') ?>" placeholder="Cerca ristorante..."
                   style="width:100%;padding:.5rem .75rem .5rem 2.25rem;border:1px solid #dee2e6;border-radius:8px;font-size:.85rem;">
        </div>
        <button type="submit" class="adm-btn adm-btn-primary" style="padding:.5rem 1rem;"><i class="bi bi-search"></i></button>
        <?php if (!empty($search)): ?>
        <a href="<?= url('admin/tenants') ?>" class="adm-btn" style="padding:.5rem 1rem;"><i class="bi bi-x-lg"></i></a>
        <?php endif; ?>
    </div>
</form>

<div class="adm-card">
    <table class="adm-table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Slug</th>
                <th>Email</th>
                <th>Piano</th>
                <th>Stato</th>
                <th>Creato</th>
                <th style="text-align:right;">Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tenants)): ?>
            <tr>
                <td colspan="7" class="adm-empty">Nessun ristorante <?= !empty($search) ? 'trovato' : 'registrato' ?>.</td>
            </tr>
            <?php else: ?>
            <?php foreach ($tenants as $t): ?>
            <tr>
                <td class="cell-name"><?= e($t['name']) ?></td>
                <td><span class="slug-code"><?= e($t['slug']) ?></span></td>
                <td><?= e($t['email']) ?></td>
                <td>
                    <?php if (!empty($t['plan_name'])): ?>
                    <span class="adm-badge-plan" style="background:<?= e($t['plan_color']) ?>15;color:<?= e($t['plan_color']) ?>;">
                        <?= e($t['plan_name']) ?>
                    </span>
                    <?php else: ?>
                    <span class="adm-badge adm-badge-inactive"><?= e(ucfirst($t['plan'])) ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($t['is_active']): ?>
                        <span class="adm-badge adm-badge-active">Attivo</span>
                    <?php else: ?>
                        <span class="adm-badge adm-badge-inactive">Inattivo</span>
                    <?php endif; ?>
                </td>
                <td class="cell-date"><?= format_date($t['created_at']) ?></td>
                <td class="cell-actions">
                    <a href="<?= url($t['slug']) ?>" target="_blank" class="adm-action" title="Vedi pagina">
                        <i class="bi bi-eye"></i>
                    </a>
                    <a href="<?= url("admin/tenants/{$t['id']}/edit") ?>" class="adm-action" title="Modifica">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <form method="POST" action="<?= url("admin/tenants/{$t['id']}/toggle") ?>" style="display:inline;">
                        <?= csrf_field() ?>
                        <?php if ($t['is_active']): ?>
                            <button type="submit" class="adm-action adm-action-warn" title="Disattiva">
                                <i class="bi bi-pause"></i>
                            </button>
                        <?php else: ?>
                            <button type="submit" class="adm-action adm-action-success" title="Attiva">
                                <i class="bi bi-play"></i>
                            </button>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (!empty($pagination)): ?>
    <div class="pagination-bar" style="padding:.75rem 1rem;border-top:1px solid #eee;">
        <span class="pagination-info" style="font-size:.8rem;color:#6c757d;"><?= $pagination['from'] ?>-<?= $pagination['to'] ?> di <?= $pagination['totalItems'] ?> ristoranti</span>
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
</div>