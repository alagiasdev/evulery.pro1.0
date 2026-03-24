<?php
$dayLabels = ['Lun','Mar','Mer','Gio','Ven','Sab','Dom'];
$chartValues = array_values($last7Days);
$chartMax = max(1, ...array_map('intval', $chartValues));
$chartDays = array_keys($last7Days);

// Event icon/color mapping for activity feed
$eventStyles = [
    'login_success'          => ['icon' => 'bi-box-arrow-in-right', 'bg' => '#F3E5F5', 'color' => '#7B1FA2'],
    'login_failed'           => ['icon' => 'bi-shield-exclamation', 'bg' => '#FFEBEE', 'color' => '#C62828'],
    'logout'                 => ['icon' => 'bi-box-arrow-left',     'bg' => '#F3E5F5', 'color' => '#7B1FA2'],
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
    'tenant_created'         => ['icon' => 'bi-shop',               'bg' => '#E8F5E9', 'color' => '#2E7D32'],
    'tenant_toggled'         => ['icon' => 'bi-toggle-on',          'bg' => '#E3F2FD', 'color' => '#1565C0'],
    'password_reset_request' => ['icon' => 'bi-key',                'bg' => '#FFF3E0', 'color' => '#E65100'],
    'password_reset_done'    => ['icon' => 'bi-key-fill',           'bg' => '#E8F5E9', 'color' => '#2E7D32'],
];
$defaultStyle = ['icon' => 'bi-circle', 'bg' => '#F5F5F5', 'color' => '#757575'];
?>

<h1 class="admin-page-title">Dashboard</h1>
<p class="admin-page-sub">Panoramica del sistema <?= e(env('APP_NAME', 'Evulery')) ?> &mdash; <?= date('d/m/Y') ?></p>

<!-- Stats -->
<div class="admin-stats">
    <div class="admin-stat">
        <div class="admin-stat-icon" style="background:#E3F2FD;color:#1565C0;">
            <i class="bi bi-shop"></i>
        </div>
        <div>
            <div class="admin-stat-value"><?= (int)$totalTenants ?></div>
            <div class="admin-stat-label">Ristoranti</div>
        </div>
        <div style="margin-left:auto;display:flex;flex-direction:column;gap:.25rem;align-items:flex-end;">
            <span class="adm-badge adm-badge-active" style="font-size:.68rem;"><?= (int)$activeTenants ?> attivi</span>
            <?php if ($expiredSubsCount > 0): ?>
            <span class="adm-badge adm-badge-inactive" style="font-size:.68rem;"><?= (int)$expiredSubsCount ?> scaduti</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-icon" style="background:#E8F5E9;color:#2E7D32;">
            <i class="bi bi-calendar-check"></i>
        </div>
        <div>
            <div class="admin-stat-value"><?= number_format($monthReservations, 0, ',', '.') ?></div>
            <div class="admin-stat-label">Prenotazioni mese</div>
        </div>
        <?php if ($resTrend != 0): ?>
        <div style="margin-left:auto;">
            <span class="adm-trend <?= $resTrend > 0 ? 'adm-trend-up' : 'adm-trend-down' ?>">
                <i class="bi bi-arrow-<?= $resTrend > 0 ? 'up' : 'down' ?>"></i>
                <?= ($resTrend > 0 ? '+' : '') . $resTrend ?>%
            </span>
        </div>
        <?php endif; ?>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-icon" style="background:#FFF3E0;color:#E65100;">
            <i class="bi bi-people"></i>
        </div>
        <div>
            <div class="admin-stat-value"><?= number_format($monthCovers, 0, ',', '.') ?></div>
            <div class="admin-stat-label">Coperti mese</div>
        </div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-icon" style="background:#FCE4EC;color:#C62828;">
            <i class="bi bi-person-badge"></i>
        </div>
        <div>
            <div class="admin-stat-value"><?= (int)$totalUsers ?></div>
            <div class="admin-stat-label">Utenti</div>
        </div>
    </div>
</div>

<!-- Chart + Top restaurants -->
<div class="adm-grid-2-1">
    <!-- Chart: reservations last 7 days -->
    <div class="adm-card">
        <div class="adm-card-hdr">
            <span class="adm-card-hdr-title"><i class="bi bi-bar-chart me-1"></i> Prenotazioni ultimi 7 giorni</span>
        </div>
        <div class="adm-card-body">
            <div class="adm-chart-bars">
                <?php foreach ($chartValues as $i => $val): ?>
                <?php
                    $pct = $chartMax > 0 ? round(($val / $chartMax) * 100) : 0;
                    $pct = max($pct, 3);
                    $dayNum = (int)date('N', strtotime($chartDays[$i]));
                    $label = $dayLabels[$dayNum - 1] ?? '';
                    $isWeekend = ($dayNum >= 6);
                ?>
                <div class="adm-chart-col">
                    <div class="adm-chart-val"><?= $val ?></div>
                    <div class="adm-chart-bar" style="height:<?= $pct ?>%;background:<?= $isWeekend ? 'var(--admin-accent)' : '#BBDEFB' ?>;"></div>
                    <div class="adm-chart-label"><?= $label ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Top 5 restaurants -->
    <div class="adm-card">
        <div class="adm-card-hdr">
            <span class="adm-card-hdr-title"><i class="bi bi-trophy me-1"></i> Top Ristoranti</span>
            <a href="<?= url('admin/tenants') ?>" style="font-size:.75rem;color:var(--admin-accent);text-decoration:none;font-weight:600;">Vedi tutti</a>
        </div>
        <div class="adm-card-body" style="padding:.5rem 1.25rem;">
            <?php if (empty($topTenants)): ?>
                <div class="adm-empty">Nessuna prenotazione questo mese</div>
            <?php else: ?>
                <?php foreach ($topTenants as $i => $tt): ?>
                <div class="adm-top-row">
                    <div class="adm-top-rank <?= $i === 0 ? 'gold' : ($i === 1 ? 'silver' : ($i === 2 ? 'bronze' : '')) ?>"><?= $i + 1 ?></div>
                    <div class="adm-top-name"><?= e($tt['name']) ?></div>
                    <div class="adm-top-count"><?= (int)$tt['cnt'] ?></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Activity Feed + Alerts -->
<div class="adm-grid-1-1">
    <!-- Activity feed -->
    <div class="adm-card">
        <div class="adm-card-hdr">
            <span class="adm-card-hdr-title"><i class="bi bi-clock-history me-1"></i> Attivit&agrave; recente</span>
        </div>
        <div class="adm-card-body" style="padding:.25rem 1.25rem;">
            <?php if (empty($recentActivity)): ?>
                <div class="adm-empty">Nessuna attivit&agrave; registrata</div>
            <?php else: ?>
                <?php foreach ($recentActivity as $act):
                    $evKey = $act['event'] ?? '';
                    $style = $eventStyles[$evKey] ?? $defaultStyle;
                    $userName = trim(($act['first_name'] ?? '') . ' ' . ($act['last_name'] ?? ''));
                    $timeAgo = format_date($act['created_at']);
                ?>
                <div class="adm-activity-item">
                    <div class="adm-activity-icon" style="background:<?= $style['bg'] ?>;color:<?= $style['color'] ?>;">
                        <i class="bi <?= $style['icon'] ?>"></i>
                    </div>
                    <div class="adm-activity-body">
                        <div class="adm-activity-text">
                            <?php if ($userName): ?><strong><?= e($userName) ?></strong> &mdash; <?php endif; ?>
                            <?= e($act['description'] ?? $evKey) ?>
                        </div>
                        <div class="adm-activity-meta">
                            <span><?= $timeAgo ?></span>
                            <?php if (!empty($act['tenant_name'])): ?>
                                <span><i class="bi bi-shop"></i> <?= e($act['tenant_name']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alerts -->
    <div class="adm-card">
        <div class="adm-card-hdr">
            <span class="adm-card-hdr-title"><i class="bi bi-exclamation-triangle me-1"></i> Avvisi</span>
        </div>
        <div class="adm-card-body">
            <?php $hasAlerts = !empty($expiredSubs) || !empty($expiringSubs) || !empty($inactiveTenants); ?>
            <?php if (!empty($expiredSubs)): ?>
            <div class="adm-alert-box" style="background:#FFEBEE;">
                <div class="adm-alert-title" style="color:#C62828;">
                    <i class="bi bi-exclamation-circle"></i>
                    <?= count($expiredSubs) ?> abbonamento/i scaduto/i
                </div>
                <div class="adm-alert-detail">
                    <?php foreach ($expiredSubs as $es): ?>
                        <?= e($es['tenant_name']) ?> &mdash; scaduto il <?= date('d/m/Y', strtotime($es['current_period_end'])) ?><br>
                    <?php endforeach; ?>
                </div>
                <a href="<?= url('admin/subscriptions?filter=cancelled') ?>" style="font-size:.75rem;color:#C62828;font-weight:600;text-decoration:none;display:inline-block;margin-top:.35rem;">Gestisci &rarr;</a>
            </div>
            <?php endif; ?>
            <?php if (!empty($expiringSubs)): ?>
            <div class="adm-alert-box" style="background:#FFF3E0;">
                <div class="adm-alert-title" style="color:#E65100;">
                    <i class="bi bi-clock-history"></i>
                    <?= count($expiringSubs) ?> abbonamento/i in scadenza
                </div>
                <div class="adm-alert-detail">
                    <?php foreach ($expiringSubs as $xs): ?>
                        <?= e($xs['tenant_name']) ?> &mdash; scade il <?= date('d/m/Y', strtotime($xs['current_period_end'])) ?><br>
                    <?php endforeach; ?>
                </div>
                <a href="<?= url('admin/subscriptions?filter=expiring') ?>" style="font-size:.75rem;color:#E65100;font-weight:600;text-decoration:none;display:inline-block;margin-top:.35rem;">Gestisci &rarr;</a>
            </div>
            <?php endif; ?>
            <?php if (!empty($inactiveTenants)): ?>
            <div class="adm-alert-box" style="background:#F5F5F5;">
                <div class="adm-alert-title" style="color:#616161;">
                    <i class="bi bi-pause-circle"></i>
                    <?= count($inactiveTenants) ?> ristorante/i inattivo/i
                </div>
                <div class="adm-alert-detail">
                    <?php foreach ($inactiveTenants as $it): ?>
                        <?= e($it['name']) ?><br>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php if (!$hasAlerts): ?>
            <div style="text-align:center;padding:1.5rem;color:#adb5bd;">
                <i class="bi bi-check-circle" style="font-size:1.5rem;display:block;margin-bottom:.35rem;color:#2E7D32;"></i>
                <span style="font-size:.82rem;">Tutto in ordine, nessun avviso</span>
            </div>
            <?php endif; ?>
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
