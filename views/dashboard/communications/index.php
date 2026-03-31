<?php if (!$canUse): ?>
    <?php $lockedTitle = 'Email Marketing'; partial('service-locked', compact('lockedTitle')); ?>
<?php else: ?>

<!-- KPI cards -->
<div class="stats-mini" style="margin-bottom:1.25rem;">
    <a href="<?= url('dashboard/communications/credits') ?>" class="stat-pill" style="text-decoration:none;color:inherit;">
        <div class="sp-dot" style="background:#00844A;"></div>
        <span class="sp-num" style="color:#00844A;"><?= number_format($credits, 0, ',', '.') ?></span>
        <span class="sp-label">Crediti disponibili <i class="bi bi-chevron-right" style="font-size:.6rem;"></i></span>
    </a>
    <div class="stat-pill">
        <div class="sp-dot" style="background:#0d6efd;"></div>
        <span class="sp-num" style="color:#0d6efd;"><?= (int)$kpi['sent_campaigns'] ?></span>
        <span class="sp-label">Campagne inviate</span>
    </div>
    <div class="stat-pill">
        <div class="sp-dot" style="background:#6f42c1;"></div>
        <span class="sp-num" style="color:#6f42c1;"><?= number_format((int)$kpi['total_sent'], 0, ',', '.') ?></span>
        <span class="sp-label">Email inviate</span>
    </div>
    <?php if (!empty($unsubCount)): ?>
    <div class="stat-pill">
        <div class="sp-dot" style="background:#dc3545;"></div>
        <span class="sp-num" style="color:#dc3545;"><?= $unsubCount ?></span>
        <span class="sp-label">Disiscritti</span>
    </div>
    <?php endif; ?>
</div>

<!-- Action bar -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
    <div style="display:flex;align-items:center;gap:1rem;">
        <h5 style="font-weight:700;margin:0;"><?= $showArchived ? 'Archivio' : 'Comunicazioni' ?></h5>
        <?php if ($showArchived): ?>
            <a href="<?= url('dashboard/communications') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Torna alle attive
            </a>
        <?php elseif ($archivedCount > 0): ?>
            <a href="<?= url('dashboard/communications') ?>?archived=1" style="font-size:.82rem;color:#6c757d;text-decoration:none;">
                <i class="bi bi-archive me-1"></i> Archivio (<?= $archivedCount ?>)
            </a>
        <?php endif; ?>
    </div>
    <?php if ($credits > 0): ?>
    <a href="<?= url('dashboard/communications/create') ?>" class="btn btn-outline-success btn-sm">
        <i class="bi bi-envelope-plus me-1"></i> Nuova comunicazione
    </a>
    <?php else: ?>
    <button class="btn btn-secondary btn-sm" disabled title="Crediti esauriti">
        <i class="bi bi-envelope-plus me-1"></i> Nuova comunicazione
    </button>
    <?php endif; ?>
</div>

<!-- Campaign list -->
<div class="card">
    <?php if (empty($campaigns)): ?>
    <div class="empty-state">
        <i class="bi bi-envelope"></i>
        <p>Nessuna comunicazione ancora inviata.</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.85rem;">
            <thead>
                <tr>
                    <th style="min-width:200px;">Oggetto</th>
                    <th>Segmento</th>
                    <th class="text-center">Destinatari</th>
                    <th class="text-center">Inviati</th>
                    <th class="text-center">Falliti</th>
                    <th>Stato</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($campaigns as $c): ?>
                <tr style="cursor:pointer;" data-url="<?= url("dashboard/communications/{$c['id']}") ?>">
                    <td>
                        <strong><?= e($c['subject']) ?></strong>
                    </td>
                    <td>
                        <?php
                        $segLabels = [
                            'all' => ['Tutti', '#6c757d'],
                            'nuovo' => ['Nuovi', '#0dcaf0'],
                            'occasionale' => ['Occasionali', '#ffc107'],
                            'abituale' => ['Abituali', '#198754'],
                            'vip' => ['VIP', '#E65100'],
                            'inactive' => ['Inattivi', '#dc3545'],
                        ];
                        $seg = $segLabels[$c['segment_filter']] ?? ['—', '#6c757d'];
                        ?>
                        <span style="font-size:.75rem;font-weight:600;padding:2px 8px;border-radius:4px;background:<?= $seg[1] ?>15;color:<?= $seg[1] ?>;">
                            <?= $seg[0] ?>
                        </span>
                    </td>
                    <td class="text-center"><?= (int)$c['total_recipients'] ?></td>
                    <td class="text-center" style="color:#198754;font-weight:600;"><?= (int)$c['sent_count'] ?></td>
                    <td class="text-center" style="color:<?= (int)$c['failed_count'] > 0 ? '#dc3545' : '#adb5bd' ?>;"><?= (int)$c['failed_count'] ?></td>
                    <td>
                        <?php
                        $statusBadges = [
                            'draft'   => ['Bozza', '#6c757d'],
                            'queued'  => ['In coda', '#ffc107'],
                            'sending' => ['Invio...', '#0d6efd'],
                            'sent'    => ['Inviata', '#198754'],
                            'failed'  => ['Fallita', '#dc3545'],
                        ];
                        $sb = $statusBadges[$c['status']] ?? ['—', '#6c757d'];
                        ?>
                        <span style="font-size:.72rem;font-weight:700;padding:2px 8px;border-radius:4px;background:<?= $sb[1] ?>18;color:<?= $sb[1] ?>;">
                            <?= $sb[0] ?>
                        </span>
                    </td>
                    <td style="color:#6c757d;font-size:.8rem;white-space:nowrap;">
                        <?= $c['sent_at'] ? date('d/m/Y H:i', strtotime($c['sent_at'])) : date('d/m/Y', strtotime($c['created_at'])) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pagination): ?>
    <div class="pagination-bar">
        <?= $pagination ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php endif; ?>
