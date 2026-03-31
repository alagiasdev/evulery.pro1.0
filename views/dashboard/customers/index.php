<?php
// Segment thresholds from tenant
$thOcc = (int)($tenant['segment_occasionale'] ?? 2);
$thAbi = (int)($tenant['segment_abituale'] ?? 4);
$thVip = (int)($tenant['segment_vip'] ?? 10);

function customerSegment(int $bookings, int $thOcc, int $thAbi, int $thVip): array {
    if ($bookings >= $thVip) return ['vip', 'VIP'];
    if ($bookings >= $thAbi) return ['abituale', 'Abituale'];
    if ($bookings >= $thOcc) return ['occasionale', 'Occasionale'];
    return ['nuovo', 'Nuovo'];
}

// Avatar color per segment
$avatarColors = ['vip' => '#E65100', 'abituale' => '#2E7D32', 'occasionale' => '#1565C0', 'nuovo' => '#757575'];

$currentSeg = $segment ?? '';

$segTabs = [
    ['key' => '',            'label' => 'Tutti',       'count' => $stats['totale'],      'color' => '#0d6efd'],
    ['key' => 'nuovo',       'label' => 'Nuovi',       'count' => $stats['nuovo'],       'color' => '#6c757d'],
    ['key' => 'occasionale', 'label' => 'Occasionali', 'count' => $stats['occasionale'], 'color' => '#0dcaf0'],
    ['key' => 'abituale',    'label' => 'Abituali',    'count' => $stats['abituale'],    'color' => '#198754'],
    ['key' => 'vip',         'label' => 'VIP',         'count' => $stats['vip'],         'color' => '#ffc107'],
];
?>

<!-- Segment tabs + Stats link -->
<div class="seg-tabs">
    <?php foreach ($segTabs as $tab):
        $isActive = $tab['key'] === $currentSeg;
        $href = $tab['key']
            ? url('dashboard/customers?segment=' . $tab['key'] . ($search ? '&q=' . urlencode($search) : ''))
            : url('dashboard/customers' . ($search ? '?q=' . urlencode($search) : ''));
    ?>
    <a href="<?= $href ?>" class="seg-tab <?= $isActive ? 'active' : '' ?>" style="--seg-color:<?= $tab['color'] ?>;">
        <div class="seg-dot" style="background:<?= $tab['color'] ?>;"></div>
        <div>
            <div class="seg-count"><?= $tab['count'] ?></div>
            <div class="seg-label"><?= $tab['label'] ?></div>
        </div>
    </a>
    <?php endforeach; ?>
    <?php if (tenant_can('statistics')): ?>
    <a href="<?= url('dashboard/customers/stats') ?>" class="seg-tab seg-tab-stats" style="--seg-color:#00844A;">
        <i class="bi bi-graph-up-arrow"></i>
        <div>
            <div class="seg-count" style="font-size:.85rem;">Statistiche</div>
            <div class="seg-label">analisi</div>
        </div>
    </a>
    <?php else: ?>
    <a href="<?= url('dashboard/customers/stats') ?>" class="seg-tab seg-tab-stats" style="--seg-color:#adb5bd;opacity:.6;">
        <i class="bi bi-graph-up-arrow"></i>
        <div>
            <div class="seg-count" style="font-size:.85rem;">Statistiche <i class="bi bi-lock-fill" style="font-size:.6rem;"></i></div>
            <div class="seg-label">analisi</div>
        </div>
    </a>
    <?php endif; ?>
</div>

<!-- Action bar -->
<div class="d-flex align-items-center justify-content-end" style="margin-bottom:.5rem;">
    <a href="<?= url('dashboard/customers/import') ?>" class="btn btn-sm btn-outline-secondary" style="font-size:.78rem;">
        <i class="bi bi-cloud-upload me-1"></i> Importa CSV
    </a>
</div>

<!-- Filter bar -->
<form method="GET" action="<?= url('dashboard/customers') ?>">
<div class="filter-bar">
    <div class="filter-row">
        <div class="search-wrap">
            <i class="bi bi-search"></i>
            <input type="text" name="q" value="<?= e($search ?? '') ?>" placeholder="Cerca per nome, email o telefono...">
        </div>
        <div class="filter-divider"></div>
        <select class="filter-input" name="segment" data-autosubmit>
            <option value="">Tutti i segmenti</option>
            <option value="nuovo" <?= $currentSeg === 'nuovo' ? 'selected' : '' ?>>Nuovo</option>
            <option value="occasionale" <?= $currentSeg === 'occasionale' ? 'selected' : '' ?>>Occasionale</option>
            <option value="abituale" <?= $currentSeg === 'abituale' ? 'selected' : '' ?>>Abituale</option>
            <option value="vip" <?= $currentSeg === 'vip' ? 'selected' : '' ?>>VIP</option>
        </select>
        <div class="filter-actions">
            <button type="submit" class="btn-filter btn-filter-primary"><i class="bi bi-search me-1"></i>Filtra</button>
            <a href="<?= url('dashboard/customers') ?>" class="btn-filter btn-filter-reset"><i class="bi bi-x-lg"></i></a>
        </div>
    </div>
</div>
</form>

<!-- Desktop Table -->
<div class="card desktop-table">
    <div class="cust-header">
        <span>Cliente</span>
        <span>Segmento</span>
        <span>Email</span>
        <span>Telefono</span>
        <span style="text-align:center;">Pren.</span>
        <span style="text-align:center;">N/S</span>
        <span></span>
    </div>

    <?php if (empty($customers)): ?>
    <div class="empty-state">
        <i class="bi bi-people"></i>
        <p>Nessun cliente trovato.</p>
    </div>
    <?php else: ?>
    <?php foreach ($customers as $c):
        [$seg, $segLabel] = customerSegment((int)$c['total_bookings'], $thOcc, $thAbi, $thVip);
        $createdDate = isset($c['created_at']) ? format_date($c['created_at'], 'd/m/Y') : '';
    ?>
    <div class="cust-row<?= !empty($c['is_blocked']) ? ' cust-blocked' : '' ?>" data-url="<?= url("dashboard/customers/{$c['id']}") ?>">
        <div>
            <div class="c-name">
                <?= e($c['first_name'] . ' ' . $c['last_name']) ?>
                <?php if (!empty($c['is_blocked'])): ?>
                <span class="blocked-badge"><i class="bi bi-slash-circle"></i> Bloccato</span>
                <?php endif; ?>
                <?php if (!empty($c['unsubscribed'])): ?>
                <span class="unsub-badge"><i class="bi bi-envelope-slash"></i></span>
                <?php endif; ?>
            </div>
            <?php if ($createdDate): ?>
            <div class="c-sub">Cliente dal <?= $createdDate ?></div>
            <?php endif; ?>
        </div>
        <div><span class="seg-badge <?= $seg ?>"><?= $segLabel ?></span></div>
        <div class="c-email"><?= e($c['email']) ?></div>
        <div class="c-phone"><?= e($c['phone']) ?></div>
        <div class="c-bookings"><?= (int)$c['total_bookings'] ?></div>
        <div class="c-noshow">
            <span class="noshow-count <?= $c['total_noshow'] > 0 ? 'has' : 'none' ?>">
                <?= (int)$c['total_noshow'] ?>
            </span>
        </div>
        <i class="bi bi-chevron-right c-arrow"></i>
    </div>
    <?php endforeach; ?>

    <?php if (!empty($pagination)): ?>
    <div class="pagination-bar">
        <span class="pagination-info"><?= $pagination['from'] ?>-<?= $pagination['to'] ?> di <?= $pagination['totalItems'] ?> client<?= $pagination['totalItems'] === 1 ? 'e' : 'i' ?></span>
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
    <?php else: ?>
    <div class="pagination-bar">
        <span class="pagination-info"><?= count($customers) ?> client<?= count($customers) === 1 ? 'e' : 'i' ?></span>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Mobile Card List -->
<div class="mobile-list">
    <?php if (empty($customers)): ?>
    <div class="empty-state">
        <i class="bi bi-people"></i>
        <p>Nessun cliente trovato.</p>
    </div>
    <?php else: ?>
    <?php foreach ($customers as $c):
        [$seg, $segLabel] = customerSegment((int)$c['total_bookings'], $thOcc, $thAbi, $thVip);
        $initials = mb_strtoupper(mb_substr($c['first_name'], 0, 1) . mb_substr($c['last_name'], 0, 1));
        $avatarColor = $avatarColors[$seg] ?? '#757575';
    ?>
    <a href="<?= url("dashboard/customers/{$c['id']}") ?>" class="mobile-card<?= !empty($c['is_blocked']) ? ' cust-blocked' : '' ?>">
        <div class="mc-avatar" style="background:<?= !empty($c['is_blocked']) ? '#dc3545' : $avatarColor ?>;"><?= $initials ?></div>
        <div class="mc-info">
            <div class="mc-name">
                <?= e($c['first_name'] . ' ' . $c['last_name']) ?>
                <?php if (!empty($c['is_blocked'])): ?>
                <span class="blocked-badge"><i class="bi bi-slash-circle"></i></span>
                <?php endif; ?>
                <?php if (!empty($c['unsubscribed'])): ?>
                <span class="unsub-badge"><i class="bi bi-envelope-slash"></i></span>
                <?php endif; ?>
            </div>
            <div class="mc-meta"><?= e($c['phone']) ?> &middot; <?= (int)$c['total_bookings'] ?> pren.</div>
        </div>
        <div class="mc-right">
            <span class="seg-badge <?= $seg ?>"><?= $segLabel ?></span>
            <?php if ($c['total_noshow'] > 0): ?>
            <span class="noshow-count has"><?= (int)$c['total_noshow'] ?></span>
            <?php endif; ?>
            <i class="bi bi-chevron-right" style="color:#d0d0d0;font-size:.7rem;"></i>
        </div>
    </a>
    <?php endforeach; ?>

    <?php if (!empty($pagination)): ?>
    <div class="pagination-bar" style="padding:.75rem 1rem;">
        <span class="pagination-info"><?= $pagination['from'] ?>-<?= $pagination['to'] ?> di <?= $pagination['totalItems'] ?></span>
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