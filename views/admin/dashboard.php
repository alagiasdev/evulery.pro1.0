<h1 class="admin-page-title">Dashboard</h1>
<p class="admin-page-sub">Panoramica del sistema <?= e(env('APP_NAME', 'Evulery')) ?></p>

<!-- Stats -->
<div class="admin-stats">
    <div class="admin-stat">
        <div class="admin-stat-icon" style="background:#E3F2FD;color:#1565C0;">
            <i class="bi bi-shop"></i>
        </div>
        <div>
            <div class="admin-stat-value"><?= (int)$totalTenants ?></div>
            <div class="admin-stat-label">Ristoranti totali</div>
        </div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-icon" style="background:#E8F5E9;color:#2E7D32;">
            <i class="bi bi-check-circle"></i>
        </div>
        <div>
            <div class="admin-stat-value"><?= (int)$activeTenants ?></div>
            <div class="admin-stat-label">Attivi</div>
        </div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-icon" style="background:#FFF3E0;color:#E65100;">
            <i class="bi bi-calendar-check"></i>
        </div>
        <div>
            <div class="admin-stat-value"><?= (int)$todayReservations ?></div>
            <div class="admin-stat-label">Prenotazioni oggi</div>
        </div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-icon" style="background:#FCE4EC;color:#C62828;">
            <i class="bi bi-people"></i>
        </div>
        <div>
            <div class="admin-stat-value"><?= (int)$totalUsers ?></div>
            <div class="admin-stat-label">Utenti</div>
        </div>
    </div>
</div>

<!-- Quick actions -->
<div class="adm-card">
    <div class="adm-card-hdr">
        <span class="adm-card-hdr-title">Azioni rapide</span>
    </div>
    <div class="adm-card-body">
        <div class="admin-quick-actions">
            <a href="<?= url('admin/tenants/create') ?>" class="admin-qa admin-qa-primary">
                <i class="bi bi-plus-circle"></i> Nuovo Ristorante
            </a>
            <a href="<?= url('admin/tenants') ?>" class="admin-qa admin-qa-outline">
                <i class="bi bi-list"></i> Gestisci Ristoranti
            </a>
            <a href="<?= url('admin/subscriptions') ?>" class="admin-qa admin-qa-outline">
                <i class="bi bi-credit-card"></i> Abbonamenti
            </a>
        </div>
    </div>
</div>

<!-- Recent tenants -->
<?php if (!empty($recentTenants)): ?>
<div class="adm-card">
    <div class="adm-card-hdr">
        <span class="adm-card-hdr-title">Ristoranti recenti</span>
        <a href="<?= url('admin/tenants') ?>" style="font-size:.78rem;color:var(--admin-accent);text-decoration:none;font-weight:600;">
            Vedi tutti <i class="bi bi-arrow-right"></i>
        </a>
    </div>
    <table class="adm-table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Slug</th>
                <th>Piano</th>
                <th>Stato</th>
                <th>Creato</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentTenants as $t): ?>
            <tr>
                <td class="cell-name"><?= e($t['name']) ?></td>
                <td><span class="slug-code"><?= e($t['slug']) ?></span></td>
                <td><span class="adm-badge adm-badge-plan"><?= e(ucfirst($t['plan'])) ?></span></td>
                <td>
                    <?php if ($t['is_active']): ?>
                        <span class="adm-badge adm-badge-active">Attivo</span>
                    <?php else: ?>
                        <span class="adm-badge adm-badge-inactive">Inattivo</span>
                    <?php endif; ?>
                </td>
                <td class="cell-date"><?= format_date($t['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>