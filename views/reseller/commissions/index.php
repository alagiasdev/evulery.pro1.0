<?php
$monthLabels = [
    '01' => 'Gennaio', '02' => 'Febbraio', '03' => 'Marzo', '04' => 'Aprile',
    '05' => 'Maggio',  '06' => 'Giugno',   '07' => 'Luglio', '08' => 'Agosto',
    '09' => 'Settembre','10' => 'Ottobre', '11' => 'Novembre','12' => 'Dicembre',
];
$formatMonth = function (string $ym) use ($monthLabels): string {
    [$y, $m] = explode('-', $ym);
    return ($monthLabels[$m] ?? $m) . ' ' . $y;
};
?>

<div class="rs-page-header">
    <div>
        <h1 class="rs-page-title">Le mie commissioni</h1>
        <p class="rs-page-sub">Storico mensile, dettaglio per cliente e pagamenti attesi nei prossimi 12 mesi.</p>
    </div>
</div>

<!-- KPI -->
<div class="rs-kpi-grid">
    <div class="rs-kpi-card">
        <div class="rs-kpi-icon" style="background:#FFF3E0;color:#f57c00;"><i class="bi bi-cash"></i></div>
        <div>
            <div class="val">€<?= number_format($kpis['monthEarned'], 0, ',', '.') ?></div>
            <div class="lbl">Maturato questo mese</div>
        </div>
    </div>
    <div class="rs-kpi-card">
        <div class="rs-kpi-icon" style="background:#E3F2FD;color:#0277bd;"><i class="bi bi-calendar3"></i></div>
        <div>
            <div class="val">€<?= number_format($kpis['annualExpected'], 0, ',', '.') ?></div>
            <div class="lbl">Annua attesa (rinnovi)</div>
        </div>
    </div>
    <div class="rs-kpi-card">
        <div class="rs-kpi-icon" style="background:#F3E5F5;color:#7B1FA2;"><i class="bi bi-trophy"></i></div>
        <div>
            <div class="val">€<?= number_format($kpis['lifetime'], 0, ',', '.') ?></div>
            <div class="lbl">Totale maturato</div>
        </div>
    </div>
    <div class="rs-kpi-card">
        <div class="rs-kpi-icon" style="background:#E8F5E9;color:#00844A;"><i class="bi bi-shop"></i></div>
        <div>
            <div class="val"><?= (int)$kpis['activeClients'] ?></div>
            <div class="lbl">Clienti attivi</div>
        </div>
    </div>
</div>

<div style="background:#fff8e1;border-left:3px solid #ffc107;padding:.7rem 1rem;border-radius:6px;font-size:.8rem;color:#5d4037;margin-bottom:1.25rem;">
    <i class="bi bi-info-circle"></i>
    Liquidazione il <strong>15 del mese successivo</strong>. "Annua attesa" è la somma delle commissioni che incasserai nei prossimi 12 mesi se tutti i clienti attuali rinnovano.
</div>

<!-- Storico mensile -->
<div class="rs-card">
    <div class="rs-card-hdr">
        <span><i class="bi bi-calendar-week"></i> Storico ultimi 12 mesi</span>
    </div>
    <?php if (empty($history)): ?>
        <div class="rs-card-empty">Nessuna maturazione ancora. Quando il primo cliente verrà attivato, qui troverai lo storico mensile.</div>
    <?php else: ?>
        <table class="rs-tbl">
            <thead>
                <tr>
                    <th>Mese</th>
                    <th style="text-align:right;">Attivazioni</th>
                    <th style="text-align:right;">Licenze</th>
                    <th style="text-align:right;">Totale</th>
                    <th>Dettaglio</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $ym => $row): ?>
                    <tr>
                        <td class="name"><?= e($formatMonth($ym)) ?></td>
                        <td style="text-align:right;">
                            <?= $row['setup'] > 0 ? '€' . number_format($row['setup'], 0, ',', '.') : '<span class="sub">—</span>' ?>
                        </td>
                        <td style="text-align:right;">
                            <?= $row['licenses'] > 0 ? '€' . number_format($row['licenses'], 0, ',', '.') : '<span class="sub">—</span>' ?>
                        </td>
                        <td style="text-align:right;"><strong>€<?= number_format($row['total'], 0, ',', '.') ?></strong></td>
                        <td style="font-size:.78rem;color:#6c757d;">
                            <?php
                            $clientList = [];
                            foreach ($row['rows'] as $r) {
                                $clientList[] = $r['tenant'] . ' (' . ($r['type'] === 'setup' ? 'setup' : 'licenza') . ')';
                            }
                            echo e(implode(' · ', array_slice($clientList, 0, 3)));
                            if (count($clientList) > 3) {
                                echo ' <span class="sub">+' . (count($clientList) - 3) . ' altre</span>';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Breakdown per cliente -->
<div class="rs-card">
    <div class="rs-card-hdr">
        <span><i class="bi bi-shop"></i> Dettaglio per cliente</span>
    </div>
    <?php if (empty($breakdown)): ?>
        <div class="rs-card-empty">Nessun cliente attivo ancora.</div>
    <?php else: ?>
        <table class="rs-tbl">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Piano · Ciclo</th>
                    <th>Attivato</th>
                    <th style="text-align:right;">Pagamenti</th>
                    <th style="text-align:right;">Setup</th>
                    <th style="text-align:right;">Licenze maturate</th>
                    <th style="text-align:right;">Totale</th>
                    <th>Prossimo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($breakdown as $c): ?>
                    <?php
                        $planClass = 'rs-plan-' . strtolower($c['plan']);
                        $bcLabel = $c['billing_cycle'] === 'semiannual' ? 'Semestrale' : ($c['billing_cycle'] === 'annual' ? 'Annuale' : '—');
                    ?>
                    <tr>
                        <td>
                            <div class="name"><?= e($c['name']) ?></div>
                            <div class="sub">/<?= e($c['slug']) ?></div>
                        </td>
                        <td>
                            <span class="rs-plan <?= e($planClass) ?>"><?= e($c['plan']) ?></span>
                            <div class="sub"><?= e($bcLabel) ?></div>
                        </td>
                        <td><?= format_date($c['created_at'], 'd/m/Y') ?></td>
                        <td style="text-align:right;">
                            <strong><?= (int)$c['num_payments'] ?></strong>
                            <div class="sub">€<?= number_format($c['per_payment'], 0, ',', '.') ?> cad.</div>
                        </td>
                        <td style="text-align:right;">€<?= number_format($c['setup_amount'], 0, ',', '.') ?></td>
                        <td style="text-align:right;">€<?= number_format($c['licenses_earned'], 0, ',', '.') ?></td>
                        <td style="text-align:right;"><strong>€<?= number_format($c['total_earned'], 0, ',', '.') ?></strong></td>
                        <td><?= $c['next_payment_at'] ? format_date($c['next_payment_at'], 'd/m/Y') : '<span class="sub">—</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Prossimi pagamenti attesi -->
<div class="rs-card">
    <div class="rs-card-hdr">
        <span><i class="bi bi-clock"></i> Pagamenti attesi prossimi 12 mesi</span>
    </div>
    <?php if (empty($upcoming)): ?>
        <div class="rs-card-empty">Nessun pagamento atteso nei prossimi 12 mesi.</div>
    <?php else: ?>
        <table class="rs-tbl">
            <thead>
                <tr>
                    <th>Data attesa</th>
                    <th>Cliente</th>
                    <th style="text-align:right;">Importo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($upcoming as $u): ?>
                    <tr>
                        <td><?= format_date($u['date'], 'd/m/Y') ?></td>
                        <td class="name"><?= e($u['tenant_name']) ?></td>
                        <td style="text-align:right;"><strong>€<?= number_format($u['amount'], 0, ',', '.') ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
