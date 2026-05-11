<?php
$firstName = explode(' ', (string)$userName, 2)[0] ?? 'Reseller';
?>

<div class="rs-page-header">
    <div>
        <h1 class="rs-page-title">Benvenuto, <?= e($firstName) ?></h1>
        <p class="rs-page-sub">Riepilogo della tua attività di questo mese</p>
    </div>
</div>

<div class="rs-kpi-grid">
    <div class="rs-kpi-card">
        <div class="rs-kpi-icon" style="background:#E3F2FD;color:#0277bd;"><i class="bi bi-funnel-fill"></i></div>
        <div>
            <div class="val"><?= (int)$openLeads ?></div>
            <div class="lbl">Lead aperti</div>
        </div>
    </div>
    <div class="rs-kpi-card">
        <div class="rs-kpi-icon" style="background:#E8F5E9;color:#00844A;"><i class="bi bi-shop"></i></div>
        <div>
            <div class="val"><?= (int)$activeClients ?></div>
            <div class="lbl">Clienti attivi</div>
        </div>
    </div>
    <div class="rs-kpi-card">
        <div class="rs-kpi-icon" style="background:#FFF3E0;color:#f57c00;"><i class="bi bi-cash"></i></div>
        <div>
            <div class="val">€<?= number_format($monthEarned, 0, ',', '.') ?></div>
            <div class="lbl">Maturato questo mese</div>
        </div>
    </div>
    <div class="rs-kpi-card" style="align-items:flex-start;">
        <div class="rs-kpi-icon" style="background:#F3E5F5;color:#7B1FA2;"><i class="bi bi-trophy"></i></div>
        <div style="flex:1;">
            <div class="val">€<?= number_format($lifetimeEarned, 0, ',', '.') ?></div>
            <div class="lbl">Totale maturato</div>
            <div style="margin-top:8px;display:flex;flex-direction:column;gap:2px;font-size:.7rem;color:#6c757d;">
                <span>Attivazioni: <strong style="color:#1a1d23;">€<?= number_format($lifetimeFromActivations, 0, ',', '.') ?></strong></span>
                <span>Licenze: <strong style="color:#1a1d23;">€<?= number_format($lifetimeFromLicenses, 0, ',', '.') ?></strong></span>
            </div>
        </div>
    </div>
</div>

<div style="background:#fff8e1;border-left:3px solid #ffc107;padding:.7rem 1rem;border-radius:6px;font-size:.8rem;color:#5d4037;margin-bottom:1.25rem;">
    <i class="bi bi-info-circle"></i>
    La liquidazione delle commissioni maturate avviene il <strong>15 del mese successivo</strong>.
    Le attivazioni si maturano al go-live del cliente; le licenze al pagamento dell'abbonamento (annuale o semestrale).
</div>

<div class="rs-row2">
    <div>
        <!-- Da contattare -->
        <div class="rs-card">
            <div class="rs-card-hdr">
                <span><i class="bi bi-bell"></i> Da contattare</span>
                <a href="<?= url('reseller/leads') ?>" class="rs-card-hdr-link">Vai ai lead →</a>
            </div>
            <?php if (empty($toContact)): ?>
                <div class="rs-card-empty">Nessun follow-up programmato per i prossimi 7 giorni.</div>
            <?php else: ?>
                <table class="rs-tbl">
                    <tbody>
                        <?php foreach ($toContact as $l): ?>
                            <?php
                                $daysDiff = (int)$l['days_diff'];
                                if ($daysDiff < 0)        { $chipClass = 'rs-fu-overdue'; $chipText = 'Scaduto ' . abs($daysDiff) . 'gg'; $chipIcon = 'exclamation-circle-fill'; }
                                elseif ($daysDiff === 0)  { $chipClass = 'rs-fu-today';   $chipText = 'Oggi';                              $chipIcon = 'bell-fill'; }
                                else                      { $chipClass = 'rs-fu-future';  $chipText = 'Tra ' . $daysDiff . 'gg';           $chipIcon = 'calendar3'; }
                            ?>
                            <tr>
                                <td style="width:50%;">
                                    <div class="name"><?= e($l['restaurant']) ?></div>
                                    <div class="sub"><?= e($l['name']) ?></div>
                                </td>
                                <td>
                                    <span class="<?= $chipClass ?>"><i class="bi bi-<?= $chipIcon ?>"></i> <?= e($chipText) ?></span>
                                </td>
                                <td style="text-align:right;">
                                    <a href="<?= url('reseller/leads/' . (int)$l['id']) ?>" class="rs-btn rs-btn-ghost rs-btn-sm">
                                        <i class="bi bi-eye"></i> Apri
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Lead recenti -->
        <div class="rs-card">
            <div class="rs-card-hdr">
                <span><i class="bi bi-plus-circle"></i> Lead recenti</span>
                <a href="<?= url('reseller/leads') ?>" class="rs-card-hdr-link">Vai ai lead →</a>
            </div>
            <?php if (empty($recentLeads)): ?>
                <div class="rs-card-empty">Nessun lead ancora assegnato a te. Quando l'admin ti assegnerà nuovi lead apparirà qui.</div>
            <?php else: ?>
                <table class="rs-tbl">
                    <tbody>
                        <?php foreach ($recentLeads as $l): ?>
                            <tr>
                                <td>
                                    <div class="name"><?= e($l['restaurant']) ?></div>
                                    <div class="sub"><?= e($l['name']) ?> · <?= format_date($l['created_at'], 'd/m/Y') ?></div>
                                </td>
                                <td><span class="rs-badge rs-b-<?= e($l['status']) ?>"><?= e($statuses[$l['status']] ?? $l['status']) ?></span></td>
                                <td style="text-align:right;">
                                    <a href="<?= url('reseller/leads/' . (int)$l['id']) ?>" class="rs-btn rs-btn-ghost rs-btn-sm">
                                        <i class="bi bi-arrow-right"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <!-- Performance -->
        <div class="rs-card">
            <div class="rs-card-hdr">
                <span><i class="bi bi-graph-up"></i> Performance</span>
            </div>
            <div class="rs-card-body">
                <div style="margin-bottom:1.25rem;">
                    <div style="font-size:.72rem;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;font-weight:600;">Conversion rate</div>
                    <div style="font-size:1.6rem;font-weight:800;color:#00844A;line-height:1;margin-top:4px;"><?= (int)$convRate ?>%</div>
                    <div style="font-size:.75rem;color:#6c757d;margin-top:4px;">
                        <?= (int)$totalConverted ?> client<?= $totalConverted === 1 ? 'e' : 'i' ?>
                        su <?= (int)$totalReachedDemo ?> demo
                    </div>
                </div>
                <div style="margin-bottom:1.25rem;">
                    <div style="font-size:.72rem;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;font-weight:600;">Tempo medio di chiusura</div>
                    <div style="font-size:1.6rem;font-weight:800;color:#1a1d23;line-height:1;margin-top:4px;">
                        <?= $avgDays > 0 ? $avgDays . ' gg' : '—' ?>
                    </div>
                    <div style="font-size:.75rem;color:#6c757d;margin-top:4px;">Dal primo contatto alla conversione</div>
                </div>
                <div>
                    <div style="font-size:.72rem;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;font-weight:600;">Clienti attivi</div>
                    <div style="font-size:1.6rem;font-weight:800;color:#1a1d23;line-height:1;margin-top:4px;"><?= (int)$activeClients ?></div>
                    <div style="font-size:.75rem;color:#6c757d;margin-top:4px;">Tenant in tua "scuderia"</div>
                </div>
            </div>
        </div>
    </div>
</div>
