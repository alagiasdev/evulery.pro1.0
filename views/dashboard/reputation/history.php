<?php
/**
 * Dashboard Reputazione — Storico invii
 * Variables: $tenant, $items, $total, $page, $totalPages, $filters
 */
$currentSource = $filters['source'] ?? '';
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.15rem;"><i class="bi bi-star" style="color:#FFC107;"></i> Reputazione</h2>
        <p style="font-size:.82rem; color:#6c757d; margin-bottom:0;">Monitora le recensioni e il feedback dei tuoi clienti</p>
    </div>
</div>

<!-- Tabs -->
<div class="rv-tabs mb-4">
    <a href="<?= url('dashboard/reputation') ?>" class="rv-tab"><i class="bi bi-grid"></i> Panoramica</a>
    <a href="<?= url('dashboard/reputation/feedback') ?>" class="rv-tab"><i class="bi bi-chat-dots"></i> Feedback</a>
    <a href="<?= url('dashboard/reputation/history') ?>" class="rv-tab active"><i class="bi bi-clock-history"></i> Storico invii</a>
</div>

<!-- Filters -->
<div class="d-flex align-items-center gap-2 flex-wrap mb-3">
    <a href="<?= url('dashboard/reputation/history') ?>" class="rv-filter-chip <?= $currentSource === '' ? 'active' : '' ?>">Tutti</a>
    <a href="<?= url('dashboard/reputation/history?source=email') ?>" class="rv-filter-chip <?= $currentSource === 'email' ? 'active' : '' ?>">
        <i class="bi bi-envelope" style="font-size:.7rem;"></i> Email
    </a>
    <a href="<?= url('dashboard/reputation/history?source=qr') ?>" class="rv-filter-chip <?= $currentSource === 'qr' ? 'active' : '' ?>">
        <i class="bi bi-qr-code" style="font-size:.7rem;"></i> QR
    </a>
    <a href="<?= url('dashboard/reputation/history?source=embed') ?>" class="rv-filter-chip <?= $currentSource === 'embed' ? 'active' : '' ?>">
        <i class="bi bi-code-slash" style="font-size:.7rem;"></i> Embed
    </a>
    <a href="<?= url('dashboard/reputation/history?source=nfc') ?>" class="rv-filter-chip <?= $currentSource === 'nfc' ? 'active' : '' ?>">
        <i class="bi bi-broadcast" style="font-size:.7rem;"></i> NFC
    </a>
</div>

<?php if (empty($items)): ?>
<div class="text-center" style="padding:3rem 1rem;">
    <i class="bi bi-clock-history" style="font-size:2.5rem; color:#e0e0e0;"></i>
    <h5 style="font-size:.88rem; font-weight:700; color:#6c757d; margin-top:.75rem;">Nessun invio</h5>
    <p style="font-size:.78rem; color:#adb5bd;">Le richieste di recensione appariranno qui.</p>
</div>
<?php else: ?>

<div class="card section-card">
    <div class="card-body p-0">
        <!-- Desktop table -->
        <div class="table-responsive d-none d-md-block">
            <table class="table table-sm mb-0" style="font-size:.78rem;">
                <thead>
                    <tr style="font-size:.72rem; color:#6c757d;">
                        <th>Cliente</th>
                        <th>Canale</th>
                        <th>Prenotazione</th>
                        <th class="text-center">Inviata</th>
                        <th class="text-center">Aperta</th>
                        <th class="text-center">Click</th>
                        <th class="text-center">Voto</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <?php if (!empty($item['first_name'])): ?>
                            <strong><?= e($item['first_name'] . ' ' . ($item['last_name'] ?? '')) ?></strong>
                            <?php else: ?>
                            <span style="color:#adb5bd;">Anonimo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $sourceIcons = ['email' => 'bi-envelope', 'qr' => 'bi-qr-code', 'embed' => 'bi-code-slash', 'nfc' => 'bi-broadcast'];
                            $sourceLabels = ['email' => 'Email', 'qr' => 'QR', 'embed' => 'Embed', 'nfc' => 'NFC'];
                            $src = $item['source'] ?? 'email';
                            ?>
                            <span style="font-size:.72rem;"><i class="bi <?= $sourceIcons[$src] ?? 'bi-question' ?> me-1"></i><?= $sourceLabels[$src] ?? $src ?></span>
                        </td>
                        <td>
                            <?php if (!empty($item['reservation_date'])): ?>
                            <span style="font-size:.72rem;"><?= format_date($item['reservation_date'], 'd/m') ?> <?= substr($item['reservation_time'] ?? '', 0, 5) ?></span>
                            <?php else: ?>
                            <span style="color:#adb5bd;">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($item['sent_at']): ?>
                            <i class="bi bi-check-circle-fill" style="color:var(--brand, #00844A);"></i>
                            <?php else: ?>
                            <i class="bi bi-dash" style="color:#e0e0e0;"></i>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($item['opened_at']): ?>
                            <i class="bi bi-eye-fill" style="color:#42A5F5;"></i>
                            <?php else: ?>
                            <i class="bi bi-dash" style="color:#e0e0e0;"></i>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($item['clicked_at']): ?>
                            <i class="bi bi-hand-index-fill" style="color:#FFC107;"></i>
                            <?php else: ?>
                            <i class="bi bi-dash" style="color:#e0e0e0;"></i>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($item['rating'] !== null): ?>
                            <span style="color:#FFC107; font-weight:700;"><?= (int)$item['rating'] ?>★</span>
                            <?php else: ?>
                            <span style="color:#e0e0e0;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:.72rem; color:#adb5bd;"><?= format_date($item['created_at'] ?? '', 'd/m/Y H:i') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile cards -->
        <div class="d-md-none">
            <?php foreach ($items as $idx => $item): ?>
            <div style="padding:.75rem 1rem; <?= $idx > 0 ? 'border-top:1px solid #f0f0f0;' : '' ?>">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <div>
                        <?php if (!empty($item['first_name'])): ?>
                        <strong style="font-size:.8rem;"><?= e($item['first_name'] . ' ' . ($item['last_name'] ?? '')) ?></strong>
                        <?php else: ?>
                        <span style="font-size:.8rem; color:#adb5bd;">Anonimo</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if ($item['rating'] !== null): ?>
                        <span style="color:#FFC107; font-weight:700; font-size:.8rem;"><?= (int)$item['rating'] ?>★</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap" style="font-size:.68rem; color:#adb5bd;">
                    <?php
                    $src = $item['source'] ?? 'email';
                    ?>
                    <span><i class="bi <?= $sourceIcons[$src] ?? 'bi-question' ?> me-1"></i><?= $sourceLabels[$src] ?? $src ?></span>
                    <?php if ($item['sent_at']): ?><span><i class="bi bi-check-circle-fill" style="color:var(--brand, #00844A);"></i> Inviata</span><?php endif; ?>
                    <?php if ($item['opened_at']): ?><span><i class="bi bi-eye-fill" style="color:#42A5F5;"></i> Aperta</span><?php endif; ?>
                    <?php if ($item['clicked_at']): ?><span><i class="bi bi-hand-index-fill" style="color:#FFC107;"></i> Click</span><?php endif; ?>
                    <span class="ms-auto"><?= format_date($item['created_at'] ?? '', 'd/m H:i') ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="<?= url('dashboard/reputation/history?' . http_build_query(array_merge($filters, ['page' => $p]))) ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php endif; ?>
