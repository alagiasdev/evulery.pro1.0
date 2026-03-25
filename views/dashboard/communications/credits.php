<?php
$typeLabels = [
    'assignment' => ['Assegnazione', '#198754', 'bi-plus-circle'],
    'usage'      => ['Utilizzo', '#dc3545', 'bi-dash-circle'],
    'refund'     => ['Rimborso', '#0d6efd', 'bi-arrow-return-left'],
];
?>

<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;">
    <a href="<?= url('dashboard/communications') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div style="flex:1;">
        <h5 style="font-weight:700;margin:0;">Storico Crediti</h5>
        <p style="font-size:.82rem;color:#6c757d;margin:0;">
            <?= $total ?> movimenti registrati
        </p>
    </div>
    <div style="background:#E8F5E9;border:1px solid #C8E6C9;border-radius:8px;padding:.5rem 1rem;text-align:center;">
        <div style="font-size:.7rem;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;">Saldo attuale</div>
        <div style="font-size:1.4rem;font-weight:800;color:#198754;"><?= number_format($credits, 0, ',', '.') ?></div>
    </div>
</div>

<div class="card">
    <?php if (empty($transactions)): ?>
    <div class="empty-state">
        <i class="bi bi-wallet2"></i>
        <p>Nessun movimento registrato.</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.85rem;">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Descrizione</th>
                    <th class="text-end">Crediti</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $tx):
                    $t = $typeLabels[$tx['type']] ?? ['—', '#6c757d', 'bi-question-circle'];
                    $amount = (int)$tx['amount'];
                    $isPositive = $amount > 0;
                ?>
                <tr>
                    <td style="white-space:nowrap;color:#6c757d;">
                        <?= date('d/m/Y H:i', strtotime($tx['created_at'])) ?>
                    </td>
                    <td>
                        <span style="display:inline-flex;align-items:center;gap:.35rem;font-size:.78rem;font-weight:600;padding:2px 8px;border-radius:4px;background:<?= $t[1] ?>12;color:<?= $t[1] ?>;">
                            <i class="bi <?= $t[2] ?>"></i> <?= $t[0] ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($tx['campaign_subject']): ?>
                            <a href="<?= url("dashboard/communications/{$tx['campaign_id']}") ?>" style="color:inherit;text-decoration:none;">
                                <?= e($tx['description'] ?? $tx['campaign_subject']) ?>
                            </a>
                        <?php else: ?>
                            <?= e($tx['description'] ?? '—') ?>
                        <?php endif; ?>
                    </td>
                    <td class="text-end" style="font-weight:700;font-size:.9rem;color:<?= $isPositive ? '#198754' : '#dc3545' ?>;">
                        <?= $isPositive ? '+' : '' ?><?= number_format($amount, 0, ',', '.') ?>
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
