<?php
$currentTab = $tab ?? 'panoramica';
$currentPeriod = $period ?? '30';

// Helper: percentuale variazione
function ohPctChange(float $current, float $previous): ?int {
    if ($previous == 0) return $current > 0 ? 100 : null;
    return (int)round(($current - $previous) / $previous * 100);
}
?>

<!-- Header -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
    <div>
        <h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.15rem;"><i class="bi bi-bag-check" style="color:var(--brand, #00844A);"></i> Storico Ordini</h2>
        <p style="font-size:.82rem; color:#6c757d; margin-bottom:0;">Analisi e riepilogo ordini online</p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <?php if ($currentTab !== 'ordini'): ?>
        <div class="oh-period-selector">
            <?php foreach (['7' => '7gg', '30' => '30gg', '90' => '90gg', 'all' => 'Tutto'] as $pv => $pl): ?>
            <a class="oh-period-btn <?= $currentPeriod === (string)$pv ? 'active' : '' ?>"
               href="<?= url('dashboard/orders/history' . ($currentTab === 'classifiche' ? '/rankings' : '')) ?>?period=<?= $pv ?>"><?= $pl ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if ($currentTab === 'ordini'): ?>
        <a href="<?= url('dashboard/orders/history/csv') ?>?status=<?= e($filters['status'] ?? '') ?>&type=<?= e($filters['order_type'] ?? '') ?>&from=<?= e($filters['date_from'] ?? '') ?>&to=<?= e($filters['date_to'] ?? '') ?>&q=<?= e($filters['search'] ?? '') ?>"
           class="oh-export-btn"><i class="bi bi-download"></i> CSV</a>
        <?php endif; ?>
        <a href="<?= url('dashboard/orders') ?>" class="oh-period-btn">
            <i class="bi bi-kanban me-1"></i> Kanban
        </a>
    </div>
</div>

<!-- Tabs -->
<div class="oh-tabs">
    <a class="oh-tab <?= $currentTab === 'panoramica' ? 'active' : '' ?>" href="<?= url('dashboard/orders/history') ?>?period=<?= $currentPeriod ?>">
        <i class="bi bi-grid"></i> Panoramica
    </a>
    <a class="oh-tab <?= $currentTab === 'ordini' ? 'active' : '' ?>" href="<?= url('dashboard/orders/history/orders') ?>?period=<?= $currentPeriod ?>">
        <i class="bi bi-list-ul"></i> Ordini
        <?php if (isset($total)): ?><span class="badge bg-secondary" style="font-size:.6rem;"><?= $total ?></span><?php endif; ?>
    </a>
    <a class="oh-tab <?= $currentTab === 'classifiche' ? 'active' : '' ?>" href="<?= url('dashboard/orders/history/rankings') ?>?period=<?= $currentPeriod ?>">
        <i class="bi bi-trophy"></i> Classifiche
    </a>
</div>

<?php if ($currentTab === 'panoramica'): ?>
<!-- ═══════════ TAB: PANORAMICA ═══════════ -->

<?php
$s = $stats;
$ps = $prevStats;
$pctOrders = $ps ? ohPctChange($s['total_orders'], $ps['total_orders']) : null;
$pctRevenue = $ps ? ohPctChange($s['revenue'], $ps['revenue']) : null;
$pctAvg = $ps ? ohPctChange($s['avg_order'], $ps['avg_order']) : null;
$pctCompletion = $ps ? ohPctChange($s['completion_rate'], $ps['completion_rate']) : null;
?>

<!-- KPI -->
<div class="oh-kpi-row">
    <div class="oh-kpi-card">
        <div class="oh-kpi-icon"><i class="bi bi-bag-check" style="color:#1a1d23;"></i></div>
        <div class="oh-kpi-value"><?= $s['total_orders'] ?></div>
        <div class="oh-kpi-label">Ordini totali</div>
        <?php if ($pctOrders !== null): ?>
        <span class="oh-kpi-trend <?= $pctOrders >= 0 ? 'up' : 'down' ?>"><i class="bi bi-arrow-<?= $pctOrders >= 0 ? 'up' : 'down' ?>"></i> <?= $pctOrders >= 0 ? '+' : '' ?><?= $pctOrders ?>%</span>
        <?php endif; ?>
    </div>
    <div class="oh-kpi-card">
        <div class="oh-kpi-icon"><i class="bi bi-currency-euro" style="color:var(--brand, #00844A);"></i></div>
        <div class="oh-kpi-value" style="color:var(--brand, #00844A);">&euro; <?= number_format($s['revenue'], 0, ',', '.') ?></div>
        <div class="oh-kpi-label">Incasso totale</div>
        <?php if ($pctRevenue !== null): ?>
        <span class="oh-kpi-trend <?= $pctRevenue >= 0 ? 'up' : 'down' ?>"><i class="bi bi-arrow-<?= $pctRevenue >= 0 ? 'up' : 'down' ?>"></i> <?= $pctRevenue >= 0 ? '+' : '' ?><?= $pctRevenue ?>%</span>
        <?php endif; ?>
    </div>
    <div class="oh-kpi-card">
        <div class="oh-kpi-icon"><i class="bi bi-receipt" style="color:#E65100;"></i></div>
        <div class="oh-kpi-value" style="color:#E65100;">&euro; <?= number_format($s['avg_order'], 2, ',', '.') ?></div>
        <div class="oh-kpi-label">Ordine medio</div>
        <?php if ($pctAvg !== null): ?>
        <span class="oh-kpi-trend <?= $pctAvg >= 0 ? 'up' : 'down' ?>"><i class="bi bi-arrow-<?= $pctAvg >= 0 ? 'up' : 'down' ?>"></i> <?= $pctAvg >= 0 ? '+' : '' ?><?= $pctAvg ?>%</span>
        <?php endif; ?>
    </div>
    <div class="oh-kpi-card">
        <div class="oh-kpi-icon"><i class="bi bi-check-circle" style="color:#2e7d32;"></i></div>
        <div class="oh-kpi-value" style="color:#2e7d32;"><?= $s['completion_rate'] ?>%</div>
        <div class="oh-kpi-label">Tasso completamento</div>
        <?php if ($pctCompletion !== null): ?>
        <span class="oh-kpi-trend <?= $pctCompletion >= 0 ? 'up' : 'down' ?>"><i class="bi bi-arrow-<?= $pctCompletion >= 0 ? 'up' : 'down' ?>"></i> <?= $pctCompletion >= 0 ? '+' : '' ?><?= $pctCompletion ?>%</span>
        <?php endif; ?>
    </div>
</div>

<!-- Charts -->
<div class="row g-3">
    <div class="col-lg-7">
        <div class="oh-chart-card">
            <div class="oh-chart-header">
                <div class="oh-chart-title"><i class="bi bi-graph-up" style="color:var(--brand, #00844A);"></i> Trend ordini</div>
                <div class="oh-chart-period">vs periodo precedente</div>
            </div>
            <?php if (!empty($trend)): ?>
            <?php
                // Index trends by day offset for comparison
                $maxOrders = max(1, max(array_column($trend, 'orders')));
                if (!empty($prevTrend)) {
                    $maxOrders = max($maxOrders, max(array_column($prevTrend, 'orders')));
                }
                $prevByIndex = array_values($prevTrend);
            ?>
            <div class="oh-bar-chart">
                <?php foreach ($trend as $i => $day):
                    $pct = round($day['orders'] / $maxOrders * 100);
                    $prevPct = 0;
                    if (isset($prevByIndex[$i])) {
                        $prevPct = round($prevByIndex[$i]['orders'] / $maxOrders * 100);
                    }
                ?>
                <div class="oh-bar-col">
                    <?php if ($prevPct > 0): ?><div class="oh-bar-fill prev" style="height:<?= max(2, $prevPct) ?>%;"></div><?php endif; ?>
                    <div class="oh-bar-fill current" style="height:<?= max(2, $pct) ?>%;"></div>
                    <div class="oh-bar-label"><?= date('d/m', strtotime($day['day'])) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="oh-chart-legend">
                <div class="oh-legend-item"><div class="oh-legend-dot" style="background:var(--brand, #00844A);"></div> Questo periodo</div>
                <div class="oh-legend-item"><div class="oh-legend-dot" style="background:#e0e0e0;"></div> Periodo precedente</div>
            </div>
            <?php else: ?>
            <div class="text-center py-4" style="color:#adb5bd; font-size:.82rem;">
                <i class="bi bi-graph-up" style="font-size:2rem; display:block; margin-bottom:.5rem;"></i>
                Nessun ordine nel periodo selezionato
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-5">
        <!-- Breakdown tipo -->
        <div class="oh-chart-card">
            <div class="oh-chart-header">
                <div class="oh-chart-title"><i class="bi bi-pie-chart" style="color:#7C4DFF;"></i> Ripartizione per tipo</div>
            </div>
            <?php
            $totalType = max(1, $s['takeaway_count'] + $s['delivery_count']);
            $takeawayPct = round($s['takeaway_count'] / $totalType * 100);
            $deliveryPct = 100 - $takeawayPct;
            ?>
            <div class="oh-funnel">
                <div class="oh-funnel-step">
                    <div class="oh-funnel-label"><i class="bi bi-bag" style="color:#E65100;"></i> Asporto</div>
                    <div class="oh-funnel-bar-bg">
                        <div class="oh-funnel-bar-fill" style="width:<?= max(5, $takeawayPct) ?>%; background:#E65100;"><?= $s['takeaway_count'] ?></div>
                    </div>
                    <div class="oh-funnel-value"><?= $takeawayPct ?>%</div>
                </div>
                <div class="oh-funnel-step">
                    <div class="oh-funnel-label"><i class="bi bi-truck" style="color:#1565C0;"></i> Consegna</div>
                    <div class="oh-funnel-bar-bg">
                        <div class="oh-funnel-bar-fill" style="width:<?= max(5, $deliveryPct) ?>%; background:#1565C0;"><?= $s['delivery_count'] ?></div>
                    </div>
                    <div class="oh-funnel-value"><?= $deliveryPct ?>%</div>
                </div>
            </div>
        </div>

        <!-- Breakdown pagamento -->
        <div class="oh-chart-card">
            <div class="oh-chart-header">
                <div class="oh-chart-title"><i class="bi bi-wallet2" style="color:#2e7d32;"></i> Ripartizione per pagamento</div>
            </div>
            <?php
            $totalPay = max(1, $s['cash_count'] + $s['stripe_count']);
            $cashPct = round($s['cash_count'] / $totalPay * 100);
            $stripePct = 100 - $cashPct;
            ?>
            <div class="oh-funnel">
                <div class="oh-funnel-step">
                    <div class="oh-funnel-label"><i class="bi bi-cash" style="color:#2e7d32;"></i> Contanti</div>
                    <div class="oh-funnel-bar-bg">
                        <div class="oh-funnel-bar-fill" style="width:<?= max(5, $cashPct) ?>%; background:var(--brand, #00844A);"><?= $s['cash_count'] ?></div>
                    </div>
                    <div class="oh-funnel-value"><?= $cashPct ?>%</div>
                </div>
                <div class="oh-funnel-step">
                    <div class="oh-funnel-label"><i class="bi bi-credit-card" style="color:#7B1FA2;"></i> Carta</div>
                    <div class="oh-funnel-bar-bg">
                        <div class="oh-funnel-bar-fill" style="width:<?= max(5, $stripePct) ?>%; background:#7B1FA2;"><?= $s['stripe_count'] ?></div>
                    </div>
                    <div class="oh-funnel-value"><?= $stripePct ?>%</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($currentTab === 'ordini'): ?>
<!-- ═══════════ TAB: ORDINI ═══════════ -->

<!-- Filters -->
<div class="oh-filters">
    <form method="GET" action="<?= url('dashboard/orders/history/orders') ?>" class="row g-2 align-items-end">
        <div class="col-md-2">
            <label class="form-label" style="font-size:.72rem; font-weight:600; color:#6c757d;">Stato</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">Tutti</option>
                <?php foreach (['pending','accepted','preparing','ready','completed','cancelled','rejected'] as $sv): ?>
                <option value="<?= $sv ?>" <?= ($filters['status'] ?? '') === $sv ? 'selected' : '' ?>><?= order_status_label($sv) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" style="font-size:.72rem; font-weight:600; color:#6c757d;">Tipo</label>
            <select name="type" class="form-select form-select-sm">
                <option value="">Tutti</option>
                <option value="takeaway" <?= ($filters['order_type'] ?? '') === 'takeaway' ? 'selected' : '' ?>>Asporto</option>
                <option value="delivery" <?= ($filters['order_type'] ?? '') === 'delivery' ? 'selected' : '' ?>>Consegna</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" style="font-size:.72rem; font-weight:600; color:#6c757d;">Da</label>
            <input type="date" name="from" class="form-control form-control-sm" value="<?= e($filters['date_from'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label" style="font-size:.72rem; font-weight:600; color:#6c757d;">A</label>
            <input type="date" name="to" class="form-control form-control-sm" value="<?= e($filters['date_to'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label" style="font-size:.72rem; font-weight:600; color:#6c757d;">Cerca</label>
            <input type="text" name="q" class="form-control form-control-sm" placeholder="Nome, #ordine, tel..." value="<?= e($filters['search'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-success btn-sm w-100"><i class="bi bi-search me-1"></i> Filtra</button>
        </div>
    </form>
</div>

<!-- Table -->
<div class="oh-table-card">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0" style="font-size:.82rem;">
            <thead>
                <tr>
                    <th class="oh-th">#</th>
                    <th class="oh-th">Data</th>
                    <th class="oh-th">Cliente</th>
                    <th class="oh-th">Tipo</th>
                    <th class="oh-th">Articoli</th>
                    <th class="oh-th">Totale</th>
                    <th class="oh-th">Pagamento</th>
                    <th class="oh-th">Stato</th>
                    <th class="oh-th"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">Nessun ordine trovato.</td></tr>
                <?php endif; ?>
                <?php foreach ($orders as $o): ?>
                <tr style="border-bottom:1px solid #f5f5f5;">
                    <td class="oh-td"><strong style="color:#1a1d23;"><?= e($o['order_number']) ?></strong></td>
                    <td class="oh-td"><?= date('d/m H:i', strtotime($o['created_at'])) ?></td>
                    <td class="oh-td">
                        <div style="font-weight:600; font-size:.8rem;"><?= e($o['customer_name']) ?></div>
                        <div style="font-size:.68rem; color:#6c757d;"><?= e($o['customer_phone']) ?></div>
                    </td>
                    <td class="oh-td">
                        <i class="bi <?= $o['order_type'] === 'delivery' ? 'bi-truck' : 'bi-bag' ?>" style="color:<?= $o['order_type'] === 'delivery' ? '#1565C0' : '#E65100' ?>;"></i>
                        <?= order_type_label($o['order_type']) ?>
                    </td>
                    <td class="oh-td" style="font-size:.72rem; color:#6c757d; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        <?= e($itemSummaries[$o['id']] ?? '') ?>
                    </td>
                    <td class="oh-td"><strong>&euro; <?= number_format((float)$o['total'], 2, ',', '.') ?></strong></td>
                    <td class="oh-td">
                        <?php if ($o['payment_status'] === 'paid'): ?>
                        <span class="oh-payment oh-payment--paid"><i class="bi bi-credit-card"></i> Pagato</span>
                        <?php else: ?>
                        <span class="oh-payment oh-payment--cash"><i class="bi bi-cash"></i> <?= $o['payment_method'] === 'stripe' ? 'Carta' : 'Contanti' ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="oh-td"><?= order_status_badge($o['status']) ?></td>
                    <td class="oh-td"><a href="<?= url("dashboard/orders/{$o['id']}") ?>" class="oh-detail-btn"><i class="bi bi-eye"></i></a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php
    $currentOffset = (int)($filters['offset'] ?? 0);
    $limit = (int)($filters['limit'] ?? 30);
    $totalPages = max(1, ceil($total / $limit));
    $currentPage = floor($currentOffset / $limit) + 1;
    $baseUrl = url('dashboard/orders/history/orders') . '?status=' . e($filters['status'] ?? '') . '&type=' . e($filters['order_type'] ?? '') . '&from=' . e($filters['date_from'] ?? '') . '&to=' . e($filters['date_to'] ?? '') . '&q=' . e($filters['search'] ?? '');
    ?>
    <?php if ($totalPages > 1): ?>
    <div class="oh-pagination">
        <div class="oh-pagination-info">Mostrando <strong><?= $currentOffset + 1 ?>-<?= min($currentOffset + $limit, $total) ?></strong> di <strong><?= $total ?></strong> ordini</div>
        <div class="oh-page-btns">
            <a class="oh-page-btn <?= $currentPage <= 1 ? 'disabled' : '' ?>" href="<?= $baseUrl ?>&offset=<?= max(0, $currentOffset - $limit) ?>"><i class="bi bi-chevron-left"></i></a>
            <?php for ($p = max(1, $currentPage - 2); $p <= min($totalPages, $currentPage + 2); $p++): ?>
            <a class="oh-page-btn <?= $p === $currentPage ? 'active' : '' ?>" href="<?= $baseUrl ?>&offset=<?= ($p - 1) * $limit ?>"><?= $p ?></a>
            <?php endfor; ?>
            <a class="oh-page-btn <?= $currentPage >= $totalPages ? 'disabled' : '' ?>" href="<?= $baseUrl ?>&offset=<?= min(($totalPages - 1) * $limit, $currentOffset + $limit) ?>"><i class="bi bi-chevron-right"></i></a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php elseif ($currentTab === 'classifiche'): ?>
<!-- ═══════════ TAB: CLASSIFICHE ═══════════ -->

<?php if (empty($topItems) && empty($topCustomers)): ?>
<div class="text-center py-5" style="color:#adb5bd; font-size:.82rem;">
    <i class="bi bi-trophy" style="font-size:2.5rem; display:block; margin-bottom:.5rem;"></i>
    Nessun dato nel periodo selezionato
</div>
<?php else: ?>
<div class="row g-3">
    <!-- Top piatti -->
    <div class="col-lg-6">
        <div class="oh-chart-card">
            <div class="oh-chart-header">
                <div class="oh-chart-title"><i class="bi bi-trophy" style="color:#FFC107;"></i> Top 10 piatti venduti</div>
                <div class="oh-chart-period">Per quantità</div>
            </div>
            <?php if (empty($topItems)): ?>
            <div class="text-center py-3" style="color:#adb5bd; font-size:.8rem;">Nessun piatto ordinato</div>
            <?php else: ?>
            <?php $maxQty = max(1, (int)$topItems[0]['total_qty']); ?>
            <?php foreach ($topItems as $i => $item):
                $rank = $i + 1;
                $rankClass = match($rank) { 1 => 'gold', 2 => 'silver', 3 => 'bronze', default => 'normal' };
                $barPct = round((int)$item['total_qty'] / $maxQty * 100);
                $barOpacity = $rank <= 3 ? (1 - ($rank - 1) * 0.15) : 1;
                $barColor = $rank <= 3 ? 'var(--brand, #00844A)' : '#e0e0e0';
            ?>
            <div class="oh-top-item">
                <div class="oh-top-rank <?= $rankClass ?>"><?= $rank ?></div>
                <div class="oh-top-info">
                    <div class="oh-top-name"><?= e($item['item_name']) ?></div>
                    <div class="oh-top-meta"><?= (int)$item['order_count'] ?> ordini</div>
                </div>
                <div class="oh-top-bar"><div class="oh-top-bar-fill" style="width:<?= $barPct ?>%; background:<?= $barColor ?>; opacity:<?= $barOpacity ?>;"></div></div>
                <div class="oh-top-value"><?= (int)$item['total_qty'] ?> <small>pz</small></div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top clienti -->
    <div class="col-lg-6">
        <div class="oh-chart-card">
            <div class="oh-chart-header">
                <div class="oh-chart-title"><i class="bi bi-people" style="color:var(--brand, #00844A);"></i> Top 10 clienti</div>
                <div class="oh-chart-period">Per spesa totale</div>
            </div>
            <?php if (empty($topCustomers)): ?>
            <div class="text-center py-3" style="color:#adb5bd; font-size:.8rem;">Nessun cliente</div>
            <?php else: ?>
            <?php $maxSpent = max(1, (float)$topCustomers[0]['total_spent']); ?>
            <?php foreach ($topCustomers as $i => $cust):
                $rank = $i + 1;
                $rankClass = match($rank) { 1 => 'gold', 2 => 'silver', 3 => 'bronze', default => 'normal' };
                $barPct = round((float)$cust['total_spent'] / $maxSpent * 100);
                $barOpacity = $rank <= 3 ? (1 - ($rank - 1) * 0.15) : 1;
                $barColor = $rank <= 3 ? 'var(--brand, #00844A)' : '#e0e0e0';
                $prefType = (int)$cust['takeaway'] >= (int)$cust['delivery'] ? 'takeaway' : 'delivery';
            ?>
            <div class="oh-top-item">
                <div class="oh-top-rank <?= $rankClass ?>"><?= $rank ?></div>
                <div class="oh-top-info">
                    <div class="oh-top-name"><?= e($cust['customer_name']) ?></div>
                    <div class="oh-top-meta">
                        <?= (int)$cust['order_count'] ?> ordini &middot;
                        <i class="bi <?= $prefType === 'delivery' ? 'bi-truck' : 'bi-bag' ?>" style="color:<?= $prefType === 'delivery' ? '#1565C0' : '#E65100' ?>; font-size:.6rem;"></i>
                        <?= $prefType === 'delivery' ? 'Consegna' : 'Asporto' ?>
                    </div>
                </div>
                <div class="oh-top-bar"><div class="oh-top-bar-fill" style="width:<?= $barPct ?>%; background:<?= $barColor ?>; opacity:<?= $barOpacity ?>;"></div></div>
                <div class="oh-top-value">&euro; <?= number_format((float)$cust['total_spent'], 0, ',', '.') ?></div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>
