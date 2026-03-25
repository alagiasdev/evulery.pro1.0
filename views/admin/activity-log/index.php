<?php
use App\Services\AuditLog;

// Extended event styles (icon + colors)
$eventStyles = [
    'login_success'          => ['icon' => 'bi-box-arrow-in-right', 'bg' => '#F3E5F5', 'color' => '#7B1FA2'],
    'login_failed'           => ['icon' => 'bi-shield-exclamation', 'bg' => '#FFEBEE', 'color' => '#C62828'],
    'logout'                 => ['icon' => 'bi-box-arrow-left',     'bg' => '#F3E5F5', 'color' => '#7B1FA2'],
    'password_reset_request' => ['icon' => 'bi-key',                'bg' => '#FFF3E0', 'color' => '#E65100'],
    'password_reset_done'    => ['icon' => 'bi-key-fill',           'bg' => '#E8F5E9', 'color' => '#2E7D32'],
    'tenant_created'         => ['icon' => 'bi-shop',               'bg' => '#E8F5E9', 'color' => '#2E7D32'],
    'tenant_toggled'         => ['icon' => 'bi-toggle-on',          'bg' => '#E3F2FD', 'color' => '#1565C0'],
    'reservation_created'    => ['icon' => 'bi-calendar-plus',      'bg' => '#E8F5E9', 'color' => '#2E7D32'],
    'reservation_updated'    => ['icon' => 'bi-calendar-check',     'bg' => '#E3F2FD', 'color' => '#1565C0'],
    'reservation_status'     => ['icon' => 'bi-calendar-event',     'bg' => '#FFF3E0', 'color' => '#E65100'],
    'reservation_deleted'    => ['icon' => 'bi-calendar-x',         'bg' => '#FFEBEE', 'color' => '#C62828'],
    'customer_notes_updated' => ['icon' => 'bi-pencil-square',      'bg' => '#E3F2FD', 'color' => '#1565C0'],
    'customer_blocked'       => ['icon' => 'bi-person-slash',       'bg' => '#FFEBEE', 'color' => '#C62828'],
    'menu_category_created'  => ['icon' => 'bi-bookmark-plus',      'bg' => '#E8F5E9', 'color' => '#2E7D32'],
    'menu_category_updated'  => ['icon' => 'bi-bookmark-check',     'bg' => '#E3F2FD', 'color' => '#1565C0'],
    'menu_category_deleted'  => ['icon' => 'bi-bookmark-x',         'bg' => '#FFEBEE', 'color' => '#C62828'],
    'menu_item_created'      => ['icon' => 'bi-egg-fried',          'bg' => '#E8F5E9', 'color' => '#2E7D32'],
    'menu_item_updated'      => ['icon' => 'bi-egg-fried',          'bg' => '#E3F2FD', 'color' => '#1565C0'],
    'menu_item_deleted'      => ['icon' => 'bi-egg-fried',          'bg' => '#FFEBEE', 'color' => '#C62828'],
    'menu_toggled'           => ['icon' => 'bi-book',               'bg' => '#FFF3E0', 'color' => '#E65100'],
    'promotion_created'      => ['icon' => 'bi-megaphone',          'bg' => '#E8F5E9', 'color' => '#2E7D32'],
    'promotion_updated'      => ['icon' => 'bi-megaphone',          'bg' => '#E3F2FD', 'color' => '#1565C0'],
    'promotion_deleted'      => ['icon' => 'bi-megaphone',          'bg' => '#FFEBEE', 'color' => '#C62828'],
    'subscription_changed'   => ['icon' => 'bi-credit-card-2-front','bg' => '#FFF3E0', 'color' => '#E65100'],
    'plan_created'           => ['icon' => 'bi-star',               'bg' => '#E8F5E9', 'color' => '#2E7D32'],
    'plan_updated'           => ['icon' => 'bi-star-half',          'bg' => '#E3F2FD', 'color' => '#1565C0'],
    'plan_deleted'           => ['icon' => 'bi-star',               'bg' => '#FFEBEE', 'color' => '#C62828'],
    'service_created'        => ['icon' => 'bi-puzzle',             'bg' => '#E8F5E9', 'color' => '#2E7D32'],
    'service_updated'        => ['icon' => 'bi-puzzle',             'bg' => '#E3F2FD', 'color' => '#1565C0'],
    'service_deleted'        => ['icon' => 'bi-puzzle',             'bg' => '#FFEBEE', 'color' => '#C62828'],
    'settings_updated'       => ['icon' => 'bi-gear',               'bg' => '#E3F2FD', 'color' => '#1565C0'],
    'deposit_updated'        => ['icon' => 'bi-cash-coin',          'bg' => '#FFF3E0', 'color' => '#E65100'],
    'slots_updated'          => ['icon' => 'bi-clock',              'bg' => '#E3F2FD', 'color' => '#1565C0'],
    'profile_updated'        => ['icon' => 'bi-person-gear',        'bg' => '#E3F2FD', 'color' => '#1565C0'],
];
$defaultStyle = ['icon' => 'bi-circle', 'bg' => '#F5F5F5', 'color' => '#757575'];
?>

<h1 class="admin-page-title">Log Attivit&agrave;</h1>
<p class="admin-page-sub">Registro completo delle operazioni del sistema</p>

<!-- KPI -->
<div class="admin-stats">
    <div class="admin-stat">
        <div class="admin-stat-icon" style="background:#E3F2FD;color:#1565C0;">
            <i class="bi bi-activity"></i>
        </div>
        <div>
            <div class="admin-stat-value"><?= number_format($eventsToday, 0, ',', '.') ?></div>
            <div class="admin-stat-label">Eventi oggi</div>
        </div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-icon" style="background:#F3E5F5;color:#7B1FA2;">
            <i class="bi bi-box-arrow-in-right"></i>
        </div>
        <div>
            <div class="admin-stat-value"><?= number_format($loginsToday, 0, ',', '.') ?></div>
            <div class="admin-stat-label">Login oggi</div>
        </div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-icon" style="background:#E8F5E9;color:#2E7D32;">
            <i class="bi bi-graph-up"></i>
        </div>
        <div>
            <div class="admin-stat-value"><?= number_format($events7d, 0, ',', '.') ?></div>
            <div class="admin-stat-label">Ultimi 7 giorni</div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="adm-card" style="margin-bottom:1.25rem;">
    <div class="adm-card-body" style="padding:.85rem 1.25rem;">
        <form method="GET" action="<?= url('admin/activity-log') ?>" class="adm-filter-form" style="display:flex;gap:.65rem;align-items:flex-end;flex-wrap:wrap;">
            <div style="flex:1;min-width:160px;">
                <label class="adm-form-label" style="margin-bottom:.25rem;">Evento</label>
                <select name="event" class="adm-form-input" style="font-size:.82rem;">
                    <option value="">Tutti gli eventi</option>
                    <?php foreach ($eventList as $ev): ?>
                    <option value="<?= e($ev) ?>" <?= ($filter['event'] ?? '') === $ev ? 'selected' : '' ?>>
                        <?= e(AuditLog::eventLabel($ev)) ?>
                    </option>
                    <?php endforeach; ?>
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
            <div style="min-width:140px;">
                <label class="adm-form-label" style="margin-bottom:.25rem;">Da</label>
                <input type="date" name="from" class="adm-form-input" style="font-size:.82rem;" value="<?= e($filter['from'] ?? '') ?>">
            </div>
            <div style="min-width:140px;">
                <label class="adm-form-label" style="margin-bottom:.25rem;">A</label>
                <input type="date" name="to" class="adm-form-input" style="font-size:.82rem;" value="<?= e($filter['to'] ?? '') ?>">
            </div>
            <div style="display:flex;gap:.35rem;">
                <button type="submit" class="admin-qa admin-qa-primary" style="font-size:.82rem;padding:.45rem .85rem;">
                    <i class="bi bi-funnel"></i> Filtra
                </button>
                <a href="<?= url('admin/activity-log') ?>" class="admin-qa admin-qa-outline" style="font-size:.82rem;padding:.45rem .85rem;">
                    <i class="bi bi-x-circle"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Purge -->
<?php if ($totalLogs > 0): ?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:.5rem;">
    <div style="font-size:.78rem;color:#6c757d;">
        <i class="bi bi-database me-1"></i> Totale log nel database: <strong><?= number_format($totalLogs, 0, ',', '.') ?></strong>
    </div>
    <form method="POST" action="<?= url('admin/activity-log/purge') ?>" style="display:flex;align-items:center;gap:.5rem;"
          data-confirm-purge>
        <?= csrf_field() ?>
        <select name="months" class="adm-form-input" style="font-size:.78rem;padding:.3rem .5rem;width:auto;">
            <option value="1">Più vecchi di 1 mese</option>
            <option value="3">Più vecchi di 3 mesi</option>
            <option value="6" selected>Più vecchi di 6 mesi</option>
            <option value="12">Più vecchi di 12 mesi</option>
        </select>
        <button type="submit" class="admin-qa admin-qa-outline" style="font-size:.78rem;padding:.35rem .7rem;color:#C62828;border-color:#C62828;">
            <i class="bi bi-trash"></i> Elimina
        </button>
    </form>
</div>
<script>
document.querySelector('[data-confirm-purge]').addEventListener('submit', function(e) {
    var sel = this.querySelector('select[name="months"]');
    var label = sel.options[sel.selectedIndex].text.toLowerCase();
    if (!confirm('Eliminare tutti i log ' + label + '?\nQuesta azione è irreversibile.')) {
        e.preventDefault();
    }
});
</script>
<?php endif; ?>

<!-- Log Table -->
<div class="adm-card">
    <div class="adm-card-hdr">
        <span class="adm-card-hdr-title"><i class="bi bi-clock-history me-1"></i> Registro eventi</span>
        <?php if (!empty($pagination)): ?>
        <span style="font-size:.75rem;color:#6c757d;"><?= $pagination['from'] ?>-<?= $pagination['to'] ?> di <?= $pagination['totalItems'] ?></span>
        <?php endif; ?>
    </div>

    <?php if (empty($logs)): ?>
    <div class="adm-card-body adm-empty">Nessun evento trovato con i filtri selezionati.</div>
    <?php else: ?>
    <div class="adm-table-wrap">
    <table class="adm-table">
        <thead>
            <tr>
                <th style="width:150px;">Data/Ora</th>
                <th style="width:200px;">Evento</th>
                <th>Utente</th>
                <th>Ristorante</th>
                <th>Descrizione</th>
                <th style="width:120px;">IP</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log):
                $evKey = $log['event'] ?? '';
                $style = $eventStyles[$evKey] ?? $defaultStyle;
                $userName = trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? ''));
            ?>
            <tr>
                <td style="font-size:.78rem;white-space:nowrap;">
                    <?= date('d/m/Y', strtotime($log['created_at'])) ?><br>
                    <span style="color:#6c757d;"><?= date('H:i:s', strtotime($log['created_at'])) ?></span>
                </td>
                <td>
                    <span style="display:inline-flex;align-items:center;gap:.35rem;font-size:.78rem;font-weight:600;padding:.25rem .55rem;border-radius:6px;background:<?= $style['bg'] ?>;color:<?= $style['color'] ?>;">
                        <i class="bi <?= $style['icon'] ?>"></i>
                        <?= e(AuditLog::eventLabel($evKey)) ?>
                    </span>
                </td>
                <td style="font-size:.82rem;">
                    <?php if ($userName): ?>
                        <?= e($userName) ?>
                    <?php elseif ($log['user_email'] ?? ''): ?>
                        <?= e($log['user_email']) ?>
                    <?php else: ?>
                        <span style="color:#adb5bd;font-style:italic;">Sistema</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:.82rem;">
                    <?php if ($log['tenant_name'] ?? ''): ?>
                        <?= e($log['tenant_name']) ?>
                    <?php else: ?>
                        <span style="color:#adb5bd;">&mdash;</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:.8rem;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <?= e($log['description'] ?? '') ?>
                </td>
                <td style="font-size:.75rem;font-family:monospace;color:#6c757d;">
                    <?= e($log['ip_address'] ?? '') ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div><!-- /adm-table-wrap -->

    <?php if (!empty($pagination)): ?>
    <div class="pagination-bar" style="padding:.75rem 1rem;border-top:1px solid #eee;">
        <span class="pagination-info" style="font-size:.8rem;color:#6c757d;"><?= $pagination['from'] ?>-<?= $pagination['to'] ?> di <?= $pagination['totalItems'] ?> eventi</span>
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
