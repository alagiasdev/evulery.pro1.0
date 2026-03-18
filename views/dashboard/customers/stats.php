<?php
$thOcc = (int)($tenant['segment_occasionale'] ?? 2);
$thAbi = (int)($tenant['segment_abituale'] ?? 4);
$thVip = (int)($tenant['segment_vip'] ?? 10);

// Segment helper
function statsSegment(int $bookings, int $thOcc, int $thAbi, int $thVip): array {
    if ($bookings >= $thVip) return ['vip', 'VIP', '#E8F5E9', '#2E7D32'];
    if ($bookings >= $thAbi) return ['abituale', 'Abituale', '#E3F2FD', '#1565C0'];
    if ($bookings >= $thOcc) return ['occasionale', 'Occasionale', '#FFF3E0', '#E65100'];
    return ['nuovo', 'Nuovo', '#F5F5F5', '#757575'];
}

// Rank colors
$rankColors = ['gold', 'silver', 'bronze'];

// Segment display data
$segTotal = $segments['totale'] ?: 1; // avoid division by zero
$segData = [
    ['name' => 'Nuovo',       'count' => $segments['nuovo'],       'color' => '#9E9E9E', 'bg' => '#E0E0E0'],
    ['name' => 'Occasionale', 'count' => $segments['occasionale'], 'color' => '#FF9800', 'bg' => '#FFF3E0'],
    ['name' => 'Abituale',    'count' => $segments['abituale'],    'color' => '#1565C0', 'bg' => '#E3F2FD'],
    ['name' => 'VIP',         'count' => $segments['vip'],         'color' => '#2E7D32', 'bg' => '#E8F5E9'],
];

// Donut percentages for new vs return
$returnPct = $kpi['return_rate'];
$newPct = 100 - $returnPct;

// Period presets
$presets = [
    '30'  => 'Ultimi 30 giorni',
    '90'  => 'Ultimi 90 giorni',
    '365' => 'Ultimo anno',
];
$currentDays = (strtotime($dateTo) - strtotime($dateFrom)) / 86400;
$activePreset = '';
foreach ($presets as $days => $label) {
    if (abs($currentDays - (int)$days) <= 1) { $activePreset = $days; break; }
}
?>

<!-- Page header -->
<div class="cs-header">
    <div>
        <h1 class="cs-title"><i class="bi bi-graph-up-arrow"></i> Statistiche Clienti</h1>
        <p class="cs-subtitle">Analisi della clientela e delle tendenze di prenotazione</p>
    </div>
    <a href="<?= url('dashboard/customers') ?>" class="cs-back-btn">
        <i class="bi bi-arrow-left"></i> Torna a Clienti
    </a>
</div>

<!-- Filters -->
<form method="GET" action="<?= url('dashboard/customers/stats') ?>" class="cs-filters" id="stats-filter-form">
    <label class="cs-filter-label">Periodo:</label>
    <select name="preset" class="cs-filter-select" id="period-preset">
        <?php foreach ($presets as $days => $label): ?>
        <option value="<?= $days ?>" <?= $activePreset === $days ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
        <option value="custom" <?= !$activePreset ? 'selected' : '' ?>>Personalizzato</option>
    </select>
    <label class="cs-filter-label">Da:</label>
    <input type="date" name="from" value="<?= e($dateFrom) ?>" class="cs-filter-input" id="date-from">
    <label class="cs-filter-label">A:</label>
    <input type="date" name="to" value="<?= e($dateTo) ?>" class="cs-filter-input" id="date-to">
    <button type="submit" class="cs-filter-btn"><i class="bi bi-funnel"></i> Applica</button>
</form>

<!-- KPI Cards -->
<div class="cs-kpi-row">
    <div class="cs-kpi">
        <div class="cs-kpi-value"><?= number_format($kpi['total_customers']) ?></div>
        <div class="cs-kpi-label">Clienti totali</div>
        <div class="cs-kpi-trend cs-trend-up"><i class="bi bi-arrow-up"></i> +<?= $kpi['new_in_period'] ?> nuovi nel periodo</div>
    </div>
    <div class="cs-kpi">
        <div class="cs-kpi-value"><?= $kpi['avg_bookings'] ?></div>
        <div class="cs-kpi-label">Pren. medie / cliente</div>
    </div>
    <div class="cs-kpi">
        <div class="cs-kpi-value"><?= $kpi['return_rate'] ?>%</div>
        <div class="cs-kpi-label">Tasso di ritorno</div>
    </div>
    <div class="cs-kpi">
        <div class="cs-kpi-value"><?= $kpi['noshow_rate'] ?>%</div>
        <div class="cs-kpi-label">Tasso no-show</div>
        <?php if ($kpi['noshow_rate'] > 0): ?>
        <div class="cs-kpi-trend cs-trend-down"><i class="bi bi-exclamation-circle"></i> su <?= $kpi['total_res'] ?> prenotazioni</div>
        <?php endif; ?>
    </div>
</div>

<!-- Two columns: Top Clients + Charts -->
<div class="cs-two-col">

    <!-- Top Clients -->
    <div class="card">
        <div class="card-header">
            <h6><i class="bi bi-trophy me-1"></i> Top clienti per frequenza</h6>
            <span class="cs-card-sub">nel periodo selezionato</span>
        </div>
        <div class="cs-top-list">
            <?php if (empty($topClients)): ?>
            <div class="cs-empty"><i class="bi bi-people"></i> Nessuna prenotazione nel periodo</div>
            <?php else: ?>
            <?php foreach ($topClients as $i => $c):
                [$seg, $segLabel, $segBg, $segColor] = statsSegment((int)$c['total_bookings'], $thOcc, $thAbi, $thVip);
                $rankClass = $rankColors[$i] ?? 'normal';
            ?>
            <a href="<?= url("dashboard/customers/{$c['id']}") ?>" class="cs-top-item">
                <div class="cs-rank <?= $rankClass ?>"><?= $i + 1 ?></div>
                <div class="cs-top-info">
                    <div class="cs-top-name"><?= e($c['first_name'] . ' ' . $c['last_name']) ?></div>
                    <div class="cs-top-meta">
                        <span class="seg-badge-sm <?= $seg ?>"><?= $segLabel ?></span>
                        &middot; <?= (int)$c['total_noshow'] ?> no-show
                    </div>
                </div>
                <div class="cs-top-count"><?= (int)$c['period_bookings'] ?> <small>pren.</small></div>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right column: Nuovi vs Ritorno + Segmentazione -->
    <div>
        <!-- Nuovi vs Ritorno -->
        <div class="card">
            <div class="card-header">
                <h6><i class="bi bi-pie-chart me-1"></i> Nuovi vs Ritorno</h6>
                <span class="cs-card-sub">sul totale prenotazioni</span>
            </div>
            <div class="cs-nvr-chart">
                <div class="cs-donut" style="background: conic-gradient(var(--brand) 0% <?= $returnPct ?>%, #0d6efd <?= $returnPct ?>% 100%);">
                    <div class="cs-donut-inner">
                        <div class="cs-donut-pct"><?= $returnPct ?>%</div>
                        <div class="cs-donut-label">ritorno</div>
                    </div>
                </div>
                <div class="cs-nvr-legend">
                    <div class="cs-nvr-item">
                        <div class="cs-nvr-dot" style="background:var(--brand);"></div>
                        <span>Clienti di ritorno</span>
                        <span class="cs-nvr-val"><?= number_format($kpi['returning_res']) ?></span>
                    </div>
                    <div class="cs-nvr-item">
                        <div class="cs-nvr-dot" style="background:#0d6efd;"></div>
                        <span>Nuovi clienti</span>
                        <span class="cs-nvr-val"><?= number_format($kpi['new_res']) ?></span>
                    </div>
                    <div class="cs-nvr-total">
                        <i class="bi bi-info-circle"></i>
                        Totale: <?= number_format($kpi['total_res']) ?> prenotazioni nel periodo
                    </div>
                </div>
            </div>
        </div>

        <!-- Segmentazione -->
        <div class="card">
            <div class="card-header">
                <h6><i class="bi bi-diagram-3 me-1"></i> Segmentazione clienti</h6>
            </div>
            <div class="cs-segments">
                <?php foreach ($segData as $s):
                    $pct = round(($s['count'] / $segTotal) * 100);
                ?>
                <div class="cs-seg-row">
                    <div class="cs-seg-dot" style="background:<?= $s['bg'] ?>;"></div>
                    <div class="cs-seg-name"><?= $s['name'] ?></div>
                    <div class="cs-seg-bar-wrap">
                        <div class="cs-seg-bar" style="width:<?= $pct ?>%;background:<?= $s['color'] ?>;"></div>
                    </div>
                    <div class="cs-seg-num"><?= $s['count'] ?></div>
                    <div class="cs-seg-pct"><?= $pct ?>%</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
document.addEventListener('DOMContentLoaded', function() {
    var preset = document.getElementById('period-preset');
    var dateFrom = document.getElementById('date-from');
    var dateTo = document.getElementById('date-to');

    if (preset) {
        preset.addEventListener('change', function() {
            var val = this.value;
            if (val !== 'custom') {
                var to = new Date();
                var from = new Date();
                from.setDate(from.getDate() - parseInt(val));
                dateFrom.value = from.toISOString().split('T')[0];
                dateTo.value = to.toISOString().split('T')[0];
            }
        });
    }

    // Click on any date input switches preset to "custom"
    [dateFrom, dateTo].forEach(function(el) {
        if (el) {
            el.addEventListener('change', function() {
                if (preset) preset.value = 'custom';
            });
        }
    });
});
</script>