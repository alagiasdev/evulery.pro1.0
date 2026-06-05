<?php
// Variabili: $stats (array per rider con total/completed/cancelled/avg_minutes/total_value),
//            $kpi (aggregati globali), $dateFrom, $dateTo
$MONTHS_IT = ['', 'Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'];
$fromTs = strtotime($dateFrom);
$toTs   = strtotime($dateTo);
$periodLabel = (int)date('Y', $fromTs) === (int)date('Y', $toTs)
    ? sprintf('%d %s — %d %s %d', (int)date('j', $fromTs), $MONTHS_IT[(int)date('n', $fromTs)], (int)date('j', $toTs), $MONTHS_IT[(int)date('n', $toTs)], (int)date('Y', $toTs))
    : sprintf('%s — %s', date('d/m/Y', $fromTs), date('d/m/Y', $toTs));
?>

<div class="rd-page-header">
    <div>
        <h1>Statistiche Rider</h1>
        <p class="rd-page-sub">Performance per rider · <?= e($periodLabel) ?></p>
    </div>
    <div class="rd-page-actions">
        <a href="<?= url('dashboard/riders') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Anagrafica
        </a>
    </div>
</div>

<!-- Range selector -->
<form method="GET" action="<?= url('dashboard/riders/stats') ?>" class="rd-range-form">
    <div class="rd-range-quick">
        <?php
            $today = date('Y-m-d');
            $monthStart = date('Y-m-01');
            $sevenAgo = date('Y-m-d', strtotime('-7 days'));
            $thirtyAgo = date('Y-m-d', strtotime('-30 days'));
            $ranges = [
                'Mese corrente' => [$monthStart, $today],
                'Ultimi 7 giorni' => [$sevenAgo, $today],
                'Ultimi 30 giorni' => [$thirtyAgo, $today],
            ];
        ?>
        <?php foreach ($ranges as $label => $r): ?>
            <?php $active = ($dateFrom === $r[0] && $dateTo === $r[1]); ?>
            <a href="<?= url('dashboard/riders/stats') . '?from=' . $r[0] . '&to=' . $r[1] ?>"
               class="rd-range-chip <?= $active ? 'rd-range-chip--active' : '' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>
    <div class="rd-range-custom">
        <input type="date" name="from" value="<?= e($dateFrom) ?>" class="form-control form-control-sm">
        <span style="font-size:.78rem;color:#6c757d;">→</span>
        <input type="date" name="to" value="<?= e($dateTo) ?>" class="form-control form-control-sm">
        <button type="submit" class="btn btn-sm btn-outline-success">Applica</button>
    </div>
</form>

<!-- KPI cards -->
<div class="rd-kpi-grid">
    <div class="rd-kpi-card">
        <div class="rd-kpi-label">Consegne totali</div>
        <div class="rd-kpi-value"><?= (int)$kpi['total'] ?></div>
        <div class="rd-kpi-sub"><?= (int)$kpi['completed'] ?> completate · <?= (int)$kpi['cancelled'] ?> annullate</div>
    </div>
    <div class="rd-kpi-card">
        <div class="rd-kpi-label">Tempo medio consegna</div>
        <div class="rd-kpi-value">
            <?php if ($kpi['avg_minutes'] !== null): ?>
                <?= (int)$kpi['avg_minutes'] ?> <span style="font-size:.7em;color:#6c757d;font-weight:500;">min</span>
            <?php else: ?>
                <span style="color:#adb5bd;">—</span>
            <?php endif; ?>
        </div>
        <div class="rd-kpi-sub">dal momento dell'assegnazione</div>
    </div>
    <div class="rd-kpi-card">
        <div class="rd-kpi-label">Valore gestito</div>
        <div class="rd-kpi-value">€ <?= number_format((float)$kpi['total_value'], 0, ',', '.') ?></div>
        <div class="rd-kpi-sub">totale ordini completati</div>
    </div>
    <div class="rd-kpi-card">
        <div class="rd-kpi-label">% completate</div>
        <div class="rd-kpi-value">
            <?php if ($kpi['completion_rate'] !== null): ?>
                <?= (int)$kpi['completion_rate'] ?>%
            <?php else: ?>
                <span style="color:#adb5bd;">—</span>
            <?php endif; ?>
        </div>
        <div class="rd-kpi-sub">completate / totali</div>
    </div>
</div>

<!-- Tabella per rider -->
<?php
$activeRiders = array_filter($stats, fn($s) => (int)$s['is_active'] === 1 || (int)$s['total'] > 0);
?>
<?php if (empty($activeRiders)): ?>
<div class="card" style="padding:2rem;text-align:center;">
    <i class="bi bi-graph-up" style="font-size:2rem;color:#adb5bd;"></i>
    <h2 style="font-size:1rem;margin:.75rem 0 .25rem;">Nessun dato in questo periodo</h2>
    <p style="font-size:.82rem;color:#6c757d;margin:0;">Aggiungi rider e assegna ordini per vedere le statistiche.</p>
</div>
<?php else: ?>
<div class="card rd-card d-none d-md-block">
    <table class="rd-table">
        <thead>
            <tr>
                <th style="width:25%;">Rider</th>
                <th>Consegne</th>
                <th>Completate</th>
                <th>Annullate</th>
                <th>Tempo medio</th>
                <th>Valore gestito</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($activeRiders as $s): ?>
            <tr class="<?= (int)$s['is_active'] === 0 ? 'rd-row-inactive' : '' ?>">
                <td>
                    <div class="rd-name">
                        <span class="rd-dot" style="background:<?= e($s['color_hex']) ?>;"></span>
                        <?= e($s['name']) ?>
                        <?php if ((int)$s['is_active'] === 0): ?>
                            <span class="rd-status rd-status--inactive" style="margin-left:.4rem;">Archiviato</span>
                        <?php endif; ?>
                    </div>
                </td>
                <td><strong><?= (int)$s['total'] ?></strong></td>
                <td><?= (int)$s['completed'] ?></td>
                <td><?= (int)$s['cancelled'] ?></td>
                <td>
                    <?php if ($s['avg_minutes'] !== null): ?>
                        <?= (int)$s['avg_minutes'] ?> min
                    <?php else: ?>
                        <span class="rd-empty">—</span>
                    <?php endif; ?>
                </td>
                <td>€ <?= number_format((float)$s['total_value'], 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Mobile cards -->
<div class="d-md-none">
    <?php foreach ($activeRiders as $s): ?>
    <div class="card rd-card-m">
        <div class="rd-card-m-head">
            <div class="rd-name">
                <span class="rd-dot" style="background:<?= e($s['color_hex']) ?>;"></span>
                <?= e($s['name']) ?>
            </div>
            <strong><?= (int)$s['total'] ?> ordini</strong>
        </div>
        <div class="rd-card-m-meta">
            <span><?= (int)$s['completed'] ?> ok · <?= (int)$s['cancelled'] ?> ann.</span>
            <span><?= $s['avg_minutes'] !== null ? ((int)$s['avg_minutes'] . ' min') : '—' ?></span>
            <span><strong>€ <?= number_format((float)$s['total_value'], 0, ',', '.') ?></strong></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="rd-stats-note">
    <i class="bi bi-info-circle"></i>
    <strong>Tempo medio consegna</strong> = minuti dal momento in cui hai assegnato l'ordine al rider fino al passaggio in stato "completato". Non include il tempo in preparazione (responsabilità della cucina, non del rider).
</div>
