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

<!-- Stats -->
<div class="admin-stats">
    <div class="admin-stat">
        <div class="admin-stat-icon" style="background:#F3E5F5;color:#7B1FA2;">
            <i class="bi bi-currency-euro"></i>
        </div>
        <div>
            <div class="admin-stat-value">&euro;<?= number_format($mrr, 0, ',', '.') ?></div>
            <div class="admin-stat-label">MRR</div>
        </div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-icon" style="background:#E8F5E9;color:#2E7D32;">
            <i class="bi bi-check2-circle"></i>
        </div>
        <div>
            <div class="admin-stat-value"><?= $activeCount ?></div>
            <div class="admin-stat-label">Attivi</div>
        </div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-icon" style="background:#E3F2FD;color:#1565C0;">
            <i class="bi bi-hourglass-split"></i>
        </div>
        <div>
            <div class="admin-stat-value"><?= $trialCount ?></div>
            <div class="admin-stat-label">Trial</div>
        </div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-icon" style="background:#FFF3E0;color:#E65100;">
            <i class="bi bi-exclamation-triangle"></i>
        </div>
        <div>
            <div class="admin-stat-value"><?= $expiringCount ?></div>
            <div class="admin-stat-label">In scadenza (30gg)</div>
        </div>
    </div>
</div>

<!-- Filter pills -->
<div class="adm-card" style="margin-bottom:0;">
    <div class="adm-card-hdr">
        <span class="adm-card-hdr-title">Elenco abbonamenti</span>
        <div class="adm-filter-pills">
            <a class="adm-pill <?= $filter === '' ? 'active' : '' ?>" href="<?= url('admin/subscriptions') ?>">Tutti</a>
            <a class="adm-pill <?= $filter === 'active' ? 'active' : '' ?>" href="<?= url('admin/subscriptions?filter=active') ?>">Attivi</a>
            <a class="adm-pill <?= $filter === 'trialing' ? 'active' : '' ?>" href="<?= url('admin/subscriptions?filter=trialing') ?>">Trial</a>
            <a class="adm-pill <?= $filter === 'expiring' ? 'active' : '' ?>" href="<?= url('admin/subscriptions?filter=expiring') ?>">In scadenza</a>
            <a class="adm-pill <?= $filter === 'cancelled' ? 'active' : '' ?>" href="<?= url('admin/subscriptions?filter=cancelled') ?>">Scaduti</a>
        </div>
    </div>

<?php if (empty($subscriptions)): ?>
    <div class="adm-card-body adm-empty">Nessun abbonamento trovato.</div>
<?php else: ?>

    <?php
    // Pre-compute shared data for each subscription
    foreach ($subscriptions as &$s) {
        $s['_cycle'] = $s['billing_cycle'] ?? 'annual';
        $s['_extraDisc'] = (float)($s['extra_discount'] ?? 0);
        $planForCalc = array_merge($s, ['price' => $s['plan_price'] ?? $s['price']]);
        $s['_calcPrice'] = \App\Models\Plan::calculatePrice($planForCalc, $s['_cycle'], $s['_extraDisc']);
        $s['_cycleLabel'] = $s['_cycle'] === 'semiannual' ? '6 mesi' : '12 mesi';
        $s['_ec'] = (int)$s['email_credits'];
        $s['_sc'] = (int)$s['sms_credits'];
        // Status badge
        if ($s['status'] === 'active') {
            $endTs2 = $s['current_period_end'] ? strtotime($s['current_period_end']) : null;
            $isExpired2  = $endTs2 && $endTs2 < time();
            $isExpiring2 = $endTs2 && !$isExpired2 && $endTs2 <= strtotime('+30 days');
            if ($isExpired2) { $s['_statusBadge'] = '<span class="adm-badge adm-badge-inactive">Scaduto</span>'; }
            elseif ($isExpiring2) { $s['_statusBadge'] = '<span class="adm-badge adm-badge-warning">In scadenza</span>'; }
            else { $s['_statusBadge'] = '<span class="adm-badge adm-badge-active">Attivo</span>'; }
        } elseif ($s['status'] === 'trialing') {
            $daysLeft = $s['current_period_end'] ? max(0, (int)ceil((strtotime($s['current_period_end']) - time()) / 86400)) : 0;
            $s['_statusBadge'] = '<span class="adm-badge adm-badge-trial">Trial (' . $daysLeft . 'gg)</span>';
        } elseif ($s['status'] === 'past_due') {
            $s['_statusBadge'] = '<span class="adm-badge adm-badge-warning">Non pagato</span>';
        } else {
            $s['_statusBadge'] = '<span class="adm-badge adm-badge-inactive">' . e(ucfirst($s['status'])) . '</span>';
        }
        // Expiry display
        if ($s['current_period_end']) {
            $endTs = strtotime($s['current_period_end']);
            $isExpiring = $endTs <= strtotime('+7 days');
            $isExpired  = $endTs < time();
            $style = $isExpired ? 'color:#C62828;font-weight:600;' : ($isExpiring ? 'color:#E65100;font-weight:600;' : 'color:#6c757d;');
            $s['_expiryHtml'] = '<span style="' . $style . '">' . date('d/m/Y', $endTs) . '</span>';
        } else {
            $s['_expiryHtml'] = '&mdash;';
        }
    }
    unset($s);
    ?>

    <!-- Mobile: card list -->
    <div class="adm-sub-mobile d-md-none">
        <?php foreach ($subscriptions as $s): ?>
        <div class="adm-sub-card">
            <div class="adm-sub-card-top">
                <div>
                    <div class="adm-sub-card-name"><?= e($s['tenant_name']) ?></div>
                    <div class="adm-sub-card-plan">
                        <?php if ($s['plan_name']): ?>
                        <span class="adm-badge-plan" style="background:<?= e($s['plan_color']) ?>15;color:<?= e($s['plan_color']) ?>;"><?= e($s['plan_name']) ?></span>
                        <?php else: ?>
                        <span class="adm-badge adm-badge-inactive"><?= e(ucfirst($s['plan'])) ?></span>
                        <?php endif; ?>
                        <?= $s['_statusBadge'] ?>
                    </div>
                </div>
                <button type="button" class="adm-action-btn" data-bs-toggle="collapse" data-bs-target="#changePlanM<?= $s['id'] ?>">
                    <i class="bi bi-pencil"></i>
                </button>
            </div>
            <div class="adm-sub-card-details">
                <div class="adm-sub-card-detail">
                    <span class="adm-sub-card-label">Prezzo</span>
                    <span class="adm-sub-card-value">&euro;<?= number_format($s['_calcPrice']['total'], 2, ',', '.') ?> / <?= $s['_cycleLabel'] ?></span>
                </div>
                <div class="adm-sub-card-detail">
                    <span class="adm-sub-card-label">Mensile</span>
                    <span class="adm-sub-card-value">&euro;<?= number_format($s['_calcPrice']['monthly'], 2, ',', '.') ?>/mese</span>
                </div>
                <div class="adm-sub-card-detail">
                    <span class="adm-sub-card-label">Email</span>
                    <span class="adm-sub-card-value" style="font-weight:600;color:<?= $s['_ec'] <= 0 ? '#adb5bd' : ($s['_ec'] < 50 ? '#E65100' : '#1a1d23') ?>;"><?= $s['_ec'] ?></span>
                </div>
                <div class="adm-sub-card-detail">
                    <span class="adm-sub-card-label">Scadenza</span>
                    <span class="adm-sub-card-value"><?= $s['_expiryHtml'] ?></span>
                </div>
            </div>
            <!-- Mobile edit form -->
            <div class="collapse" id="changePlanM<?= $s['id'] ?>">
                <?php $editCollapseId = "changePlanM{$s['id']}"; include __DIR__ . '/_edit-form.php'; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Desktop: table -->
    <div class="adm-table-wrap d-none d-md-block">
        <table class="adm-table">
            <thead>
                <tr>
                    <th>Ristorante</th>
                    <th>Piano</th>
                    <th>Prezzo ciclo</th>
                    <th>Crediti Email</th>
                    <th>Crediti SMS</th>
                    <th>Stato</th>
                    <th>Scadenza</th>
                    <th style="text-align:right;">Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subscriptions as $s): ?>
                <tr>
                    <td class="cell-name"><?= e($s['tenant_name']) ?></td>
                    <td>
                        <?php if ($s['plan_name']): ?>
                        <span class="adm-badge-plan" style="background:<?= e($s['plan_color']) ?>15;color:<?= e($s['plan_color']) ?>;"><?= e($s['plan_name']) ?></span>
                        <?php else: ?>
                        <span class="adm-badge adm-badge-inactive"><?= e(ucfirst($s['plan'])) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-weight:600;">&euro;<?= number_format($s['_calcPrice']['total'], 2, ',', '.') ?></div>
                        <div style="font-size:.68rem;color:#6c757d;"><?= $s['_cycleLabel'] ?><?php if ($s['_extraDisc'] > 0): ?> &middot; <span style="color:#2E7D32;">-<?= number_format($s['_extraDisc'], 0) ?>%</span><?php endif; ?></div>
                        <div style="font-size:.65rem;color:#adb5bd;">&euro;<?= number_format($s['_calcPrice']['monthly'], 2, ',', '.') ?>/mese</div>
                    </td>
                    <td>
                        <?php $ecColor = $s['_ec'] <= 0 ? '#adb5bd' : ($s['_ec'] < 50 ? '#E65100' : '#1a1d23'); ?>
                        <span style="font-weight:600;color:<?= $ecColor ?>;"><?= $s['_ec'] ?></span>
                        <span style="font-size:.68rem;color:<?= $s['_ec'] < 50 && $s['_ec'] > 0 ? '#E65100' : '#adb5bd' ?>;"><?= $s['_ec'] <= 0 ? 'esauriti' : ($s['_ec'] < 50 ? 'quasi esauriti!' : 'rimasti') ?></span>
                    </td>
                    <td>
                        <?php $scColor = $s['_sc'] <= 0 ? '#adb5bd' : ($s['_sc'] < 20 ? '#E65100' : '#1a1d23'); ?>
                        <span style="font-weight:600;color:<?= $scColor ?>;"><?= $s['_sc'] ?></span>
                        <span style="font-size:.68rem;color:<?= $s['_sc'] < 20 && $s['_sc'] > 0 ? '#E65100' : '#adb5bd' ?>;"><?= $s['_sc'] <= 0 ? 'esauriti' : ($s['_sc'] < 20 ? 'quasi esauriti!' : 'rimasti') ?></span>
                    </td>
                    <td><?= $s['_statusBadge'] ?></td>
                    <td class="cell-date"><?= $s['_expiryHtml'] ?></td>
                    <td class="cell-actions">
                        <button type="button" class="adm-action-btn" title="Cambia piano"
                                data-bs-toggle="collapse" data-bs-target="#changePlanD<?= $s['id'] ?>">
                            <i class="bi bi-arrow-up-circle"></i>
                        </button>
                    </td>
                </tr>
                <tr class="collapse" id="changePlanD<?= $s['id'] ?>">
                    <td colspan="8" style="padding:0;">
                        <?php $editCollapseId = "changePlanD{$s['id']}"; include __DIR__ . '/_edit-form.php'; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php endif; ?>
</div>
