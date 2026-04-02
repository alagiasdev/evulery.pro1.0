<?php
/**
 * Dashboard Reputazione — Panoramica
 * Variables: $canUse, $tenant, $stats, $distribution, $recentFeedback, $monthlyStats
 */
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.15rem;"><i class="bi bi-star" style="color:#FFC107;"></i> Reputazione</h2>
        <p style="font-size:.82rem; color:#6c757d; margin-bottom:0;">Monitora le recensioni e il feedback dei tuoi clienti</p>
    </div>
</div>

<?php if (!$canUse): ?>
    <?php $lockedTitle = 'Gestione reputazione'; include BASE_PATH . '/views/partials/service-locked.php'; ?>
<?php else: ?>

<!-- Tabs -->
<div class="rv-tabs mb-4">
    <a href="<?= url('dashboard/reputation') ?>" class="rv-tab active"><i class="bi bi-grid"></i> Panoramica</a>
    <a href="<?= url('dashboard/reputation/feedback') ?>" class="rv-tab">
        <i class="bi bi-chat-dots"></i> Feedback
        <?php if (($stats['feedback_new'] ?? 0) > 0): ?>
        <span class="badge bg-danger" style="font-size:.62rem;"><?= $stats['feedback_new'] ?></span>
        <?php endif; ?>
    </a>
    <a href="<?= url('dashboard/reputation/history') ?>" class="rv-tab"><i class="bi bi-clock-history"></i> Storico invii</a>
</div>

<?php if (($stats['total'] ?? 0) === 0): ?>
<!-- Empty state -->
<div class="text-center" style="padding:3rem 1rem;">
    <i class="bi bi-star" style="font-size:2.5rem; color:#e0e0e0;"></i>
    <h5 style="font-size:.88rem; font-weight:700; color:#6c757d; margin-top:.75rem;">Nessuna richiesta di recensione ancora</h5>
    <p style="font-size:.78rem; color:#adb5bd;">Configura le impostazioni e attendi la prima prenotazione completata.</p>
    <a href="<?= url('dashboard/settings/reviews') ?>" class="btn btn-success btn-sm">
        <i class="bi bi-gear me-1"></i> Configura recensioni
    </a>
</div>
<?php else: ?>

<!-- KPI -->
<div class="row g-2 mb-3">
    <div class="col-6 col-md">
        <div class="card rv-kpi-card">
            <div class="card-body text-center py-3">
                <div style="font-size:1rem; margin-bottom:.2rem;"><i class="bi bi-envelope" style="color:#42A5F5;"></i></div>
                <div class="rv-kpi-value"><?= $stats['total_sent'] ?? 0 ?></div>
                <div class="rv-kpi-label">Email inviate</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card rv-kpi-card">
            <div class="card-body text-center py-3">
                <div style="font-size:1rem; margin-bottom:.2rem;"><i class="bi bi-hand-index" style="color:#7C4DFF;"></i></div>
                <div class="rv-kpi-value"><?= $stats['total_clicked'] ?? 0 ?></div>
                <div class="rv-kpi-label">Click ricevuti</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card rv-kpi-card">
            <div class="card-body text-center py-3">
                <div style="font-size:1rem; margin-bottom:.2rem;"><i class="bi bi-percent" style="color:var(--brand, #00844A);"></i></div>
                <div class="rv-kpi-value" style="color:var(--brand, #00844A);"><?= $stats['click_rate'] ?? 0 ?>%</div>
                <div class="rv-kpi-label">Tasso click</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card rv-kpi-card">
            <div class="card-body text-center py-3">
                <div style="font-size:1rem; margin-bottom:.2rem;"><i class="bi bi-star-fill" style="color:#FFC107;"></i></div>
                <div class="rv-kpi-value" style="color:#FFC107;"><?= $stats['avg_rating'] ?? '—' ?></div>
                <div class="rv-kpi-label">Media interna</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card rv-kpi-card">
            <div class="card-body text-center py-3">
                <div style="font-size:1rem; margin-bottom:.2rem;"><i class="bi bi-chat-left-text" style="color:#ef5350;"></i></div>
                <div class="rv-kpi-value" style="color:#ef5350;"><?= $stats['total_feedback'] ?? 0 ?></div>
                <div class="rv-kpi-label">Feedback privati</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Left column -->
    <div class="col-lg-7">
        <!-- Funnel -->
        <div class="card section-card mb-3">
            <div class="card-body">
                <h6 style="font-size:.85rem; font-weight:700; margin-bottom:.75rem;">
                    <i class="bi bi-funnel" style="color:#7C4DFF;"></i> Funnel conversione
                </h6>
                <?php
                $funnelSteps = [
                    ['label' => 'Inviate', 'value' => $stats['total_sent'] ?? 0, 'color' => '#E3F2FD', 'textColor' => '#1565C0'],
                    ['label' => 'Aperte', 'value' => $stats['total_opened'] ?? 0, 'color' => '#42A5F5', 'textColor' => '#fff'],
                    ['label' => 'Click', 'value' => $stats['total_clicked'] ?? 0, 'color' => '#FFC107', 'textColor' => '#000'],
                    ['label' => 'Redirect', 'value' => $stats['total_redirected'] ?? 0, 'color' => 'var(--brand, #00844A)', 'textColor' => '#fff'],
                ];
                $maxVal = max(1, $funnelSteps[0]['value']);
                foreach ($funnelSteps as $step):
                    $pct = $maxVal > 0 ? round($step['value'] / $maxVal * 100) : 0;
                ?>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div style="font-size:.72rem; color:#495057; min-width:60px; font-weight:600;"><?= $step['label'] ?></div>
                    <div style="flex:1; height:28px; background:#f8f9fa; border-radius:8px; overflow:hidden;">
                        <div style="height:100%; width:<?= max(5, $pct) ?>%; background:<?= $step['color'] ?>; border-radius:8px; display:flex; align-items:center; padding:0 .6rem;">
                            <span style="font-size:.7rem; font-weight:600; color:<?= $step['textColor'] ?>;"><?= $step['value'] ?></span>
                        </div>
                    </div>
                    <div style="font-size:.68rem; color:#adb5bd; min-width:35px; text-align:right;"><?= $pct ?>%</div>
                </div>
                <?php endforeach; ?>
                <div style="font-size:.65rem; color:#adb5bd; margin-top:.5rem; line-height:1.4;">
                    <i class="bi bi-info-circle me-1"></i> Il dato "Aperte" è indicativo: alcuni client email bloccano il tracciamento. Il "Click" è la metrica più affidabile.
                </div>
            </div>
        </div>

        <!-- Monthly trend -->
        <?php if (!empty($monthlyStats)): ?>
        <div class="card section-card mb-3">
            <div class="card-body">
                <h6 style="font-size:.85rem; font-weight:700; margin-bottom:.75rem;">
                    <i class="bi bi-graph-up" style="color:#42A5F5;"></i> Andamento mensile
                </h6>
                <div class="table-responsive">
                    <table class="table table-sm" style="font-size:.75rem;">
                        <thead>
                            <tr><th>Mese</th><th class="text-center">Inviate</th><th class="text-center">Click</th><th class="text-center">Redirect</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthlyStats as $m): ?>
                            <tr>
                                <td><?= e($m['month']) ?></td>
                                <td class="text-center"><?= (int)$m['sent'] ?></td>
                                <td class="text-center"><?= (int)$m['clicked'] ?></td>
                                <td class="text-center"><?= (int)$m['redirected'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right column -->
    <div class="col-lg-5">
        <!-- Rating distribution -->
        <div class="card section-card mb-3">
            <div class="card-body">
                <h6 style="font-size:.85rem; font-weight:700; margin-bottom:.75rem;">
                    <i class="bi bi-bar-chart" style="color:#FFC107;"></i> Distribuzione voti
                </h6>
                <?php
                $totalRated = array_sum($distribution);
                foreach ([5,4,3,2,1] as $star):
                    $cnt = $distribution[$star] ?? 0;
                    $pct = $totalRated > 0 ? round($cnt / $totalRated * 100) : 0;
                    $colors = [5 => '#4CAF50', 4 => '#8BC34A', 3 => '#FFC107', 2 => '#FF9800', 1 => '#F44336'];
                ?>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div style="font-size:.72rem; color:#FFC107; min-width:30px; text-align:right;"><?= str_repeat('★', $star) ?></div>
                    <div style="flex:1; height:8px; background:#f0f0f0; border-radius:4px; overflow:hidden;">
                        <div style="height:100%; width:<?= $pct ?>%; background:<?= $colors[$star] ?>; border-radius:4px;"></div>
                    </div>
                    <div style="font-size:.68rem; color:#6c757d; min-width:20px;"><?= $cnt ?></div>
                    <div style="font-size:.65rem; color:#adb5bd; min-width:30px; text-align:right;"><?= $pct ?>%</div>
                </div>
                <?php endforeach; ?>
                <div style="font-size:.65rem; color:#adb5bd; margin-top:.5rem; line-height:1.4;">
                    <i class="bi bi-info-circle me-1"></i> Questi voti sono raccolti internamente da Evulery e non corrispondono alle recensioni pubblicate su Google o altre piattaforme.
                </div>
            </div>
        </div>

        <!-- Recent feedback -->
        <?php if (!empty($recentFeedback)): ?>
        <div class="card section-card mb-3">
            <div class="card-body">
                <h6 style="font-size:.85rem; font-weight:700; margin-bottom:.75rem;">
                    <i class="bi bi-chat-dots" style="color:#ef5350;"></i> Ultimi feedback
                </h6>
                <?php foreach ($recentFeedback as $fb): ?>
                <div style="padding:.5rem 0; border-bottom:1px solid #f8f8f8;">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span style="font-size:.78rem; font-weight:700;"><?= e(($fb['first_name'] ?? '') . ' ' . ($fb['last_name'] ?? '')) ?></span>
                        <span style="font-size:.72rem; color:#FFC107;"><?= str_repeat('★', (int)($fb['rating'] ?? 0)) ?></span>
                        <?= review_status_badge($fb['feedback_status'] ?? 'new') ?>
                    </div>
                    <div style="font-size:.75rem; color:#495057; line-height:1.4;"><?= e(mb_substr($fb['feedback_text'] ?? '', 0, 120)) ?><?= mb_strlen($fb['feedback_text'] ?? '') > 120 ? '...' : '' ?></div>
                    <div style="font-size:.65rem; color:#adb5bd; margin-top:.2rem;"><?= format_date($fb['created_at'] ?? '', 'd/m/Y H:i') ?></div>
                </div>
                <?php endforeach; ?>
                <a href="<?= url('dashboard/reputation/feedback') ?>" class="btn btn-outline-success btn-sm w-100 mt-2" style="font-size:.75rem;">
                    Vedi tutti i feedback <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Source breakdown -->
        <div class="card section-card mb-3">
            <div class="card-body">
                <h6 style="font-size:.85rem; font-weight:700; margin-bottom:.5rem;">
                    <i class="bi bi-diagram-3" style="color:#42A5F5;"></i> Canali
                </h6>
                <div class="d-flex gap-3">
                    <div class="text-center" style="flex:1;">
                        <div style="font-size:1.1rem; font-weight:700;"><?= $stats['total_email'] ?? 0 ?></div>
                        <div style="font-size:.68rem; color:#6c757d;">Email</div>
                    </div>
                    <div class="text-center" style="flex:1;">
                        <div style="font-size:1.1rem; font-weight:700;"><?= $stats['total_anonymous'] ?? 0 ?></div>
                        <div style="font-size:.68rem; color:#6c757d;">QR/Embed/NFC</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif; // stats available ?>
<?php endif; // canUse ?>
