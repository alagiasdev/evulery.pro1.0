<h1 class="admin-page-title">Abbonamenti</h1>
<p class="admin-page-sub">Gestisci gli abbonamenti dei ristoranti</p>

<div class="adm-card">
    <div class="adm-card-hdr">
        <span class="adm-card-hdr-title">Elenco abbonamenti</span>
    </div>
    <table class="adm-table">
        <thead>
            <tr>
                <th>Ristorante</th>
                <th>Piano</th>
                <th>Prezzo</th>
                <th>Stato</th>
                <th>Periodo</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($subscriptions)): ?>
            <tr>
                <td colspan="5" class="adm-empty">Nessun abbonamento attivo.</td>
            </tr>
            <?php else: ?>
            <?php foreach ($subscriptions as $s): ?>
            <tr>
                <td class="cell-name"><?= e($s['tenant_name']) ?></td>
                <td>
                    <span class="adm-badge <?= $s['plan'] === 'deposit' ? 'adm-badge-deposit' : 'adm-badge-plan' ?>">
                        <?= e(ucfirst($s['plan'])) ?>
                    </span>
                </td>
                <td>&euro;<?= number_format($s['price'], 2, ',', '.') ?></td>
                <td>
                    <?php if ($s['status'] === 'active'): ?>
                        <span class="adm-badge adm-badge-active">Attivo</span>
                    <?php elseif ($s['status'] === 'expiring'): ?>
                        <span class="adm-badge adm-badge-warning">In scadenza</span>
                    <?php else: ?>
                        <span class="adm-badge adm-badge-inactive"><?= e(ucfirst($s['status'])) ?></span>
                    <?php endif; ?>
                </td>
                <td class="cell-date">
                    <?php if ($s['current_period_start']): ?>
                        <?= format_date($s['current_period_start']) ?> - <?= format_date($s['current_period_end']) ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>