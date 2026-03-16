<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">Ristoranti</h1>
        <p class="admin-page-sub" style="margin-bottom:0;">Gestisci tutti i ristoranti della piattaforma</p>
    </div>
    <a href="<?= url('admin/tenants/create') ?>" class="adm-btn adm-btn-primary">
        <i class="bi bi-plus-circle"></i> Nuovo Ristorante
    </a>
</div>

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
                <td colspan="7" class="adm-empty">Nessun ristorante registrato.</td>
            </tr>
            <?php else: ?>
            <?php foreach ($tenants as $t): ?>
            <tr>
                <td class="cell-name"><?= e($t['name']) ?></td>
                <td><span class="slug-code"><?= e($t['slug']) ?></span></td>
                <td><?= e($t['email']) ?></td>
                <td><span class="adm-badge <?= $t['plan'] === 'deposit' ? 'adm-badge-deposit' : 'adm-badge-plan' ?>"><?= e(ucfirst($t['plan'])) ?></span></td>
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
</div>