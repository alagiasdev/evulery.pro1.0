<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Ristoranti</h2>
    <a href="<?= url('admin/tenants/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Nuovo Ristorante
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nome</th>
                    <th>Slug</th>
                    <th>Email</th>
                    <th>Piano</th>
                    <th>Stato</th>
                    <th>Creato</th>
                    <th class="text-end">Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tenants)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">Nessun ristorante registrato.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($tenants as $t): ?>
                <tr>
                    <td class="fw-semibold"><?= e($t['name']) ?></td>
                    <td><code><?= e($t['slug']) ?></code></td>
                    <td><?= e($t['email']) ?></td>
                    <td><span class="badge bg-info"><?= e(ucfirst($t['plan'])) ?></span></td>
                    <td>
                        <?php if ($t['is_active']): ?>
                            <span class="badge bg-success">Attivo</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inattivo</span>
                        <?php endif; ?>
                    </td>
                    <td><?= format_date($t['created_at']) ?></td>
                    <td class="text-end">
                        <a href="<?= url($t['slug']) ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Vedi pagina">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="<?= url("admin/tenants/{$t['id']}/edit") ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="<?= url("admin/tenants/{$t['id']}/toggle") ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-<?= $t['is_active'] ? 'warning' : 'success' ?>" title="<?= $t['is_active'] ? 'Disattiva' : 'Attiva' ?>">
                                <i class="bi bi-<?= $t['is_active'] ? 'pause' : 'play' ?>"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
