<div class="rs-page-header">
    <div>
        <h1 class="rs-page-title">I miei clienti</h1>
        <p class="rs-page-sub"><?= (int)$stats['total'] ?> <?= $stats['total'] === 1 ? 'cliente' : 'clienti' ?> · <?= (int)$stats['active'] ?> <?= $stats['active'] === 1 ? 'attivo' : 'attivi' ?></p>
    </div>
</div>

<div class="rs-kpi-grid" style="grid-template-columns:repeat(3,1fr);">
    <div class="rs-kpi-card">
        <div class="rs-kpi-icon" style="background:#E8F5E9;color:#00844A;"><i class="bi bi-shop"></i></div>
        <div>
            <div class="val"><?= (int)$stats['active'] ?></div>
            <div class="lbl">Clienti attivi</div>
        </div>
    </div>
    <div class="rs-kpi-card">
        <div class="rs-kpi-icon" style="background:#FFF3E0;color:#f57c00;"><i class="bi bi-arrow-repeat"></i></div>
        <div>
            <div class="val">€<?= number_format((float)$stats['annual_recurring'], 0, ',', '.') ?></div>
            <div class="lbl">Ricorrente annuo</div>
        </div>
    </div>
    <div class="rs-kpi-card">
        <div class="rs-kpi-icon" style="background:#F3E5F5;color:#7B1FA2;"><i class="bi bi-people"></i></div>
        <div>
            <div class="val"><?= (int)$stats['total'] ?></div>
            <div class="lbl">Totale acquisiti</div>
        </div>
    </div>
</div>

<div class="rs-card">
    <?php if (empty($clients)): ?>
        <div class="rs-card-empty">
            Non hai ancora clienti acquisiti. Quando l'admin convertirà un tuo lead in cliente, comparirà qui con la commissione annuale che maturi.
        </div>
    <?php else: ?>
        <table class="rs-tbl">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Piano</th>
                    <th>Ciclo pagamento</th>
                    <th>Stato</th>
                    <th>Attivato</th>
                    <th>Commissione/anno</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clients as $c): ?>
                    <?php
                        $planClass = 'rs-plan-' . strtolower($c['plan_name'] ?? '');
                        $bcLabel = match ($c['billing_cycle'] ?? '') {
                            'annual'      => 'Annuale',
                            'semiannual'  => 'Semestrale',
                            default       => '—',
                        };
                    ?>
                    <tr>
                        <td>
                            <div class="name"><?= e($c['name']) ?></div>
                            <div class="sub">/<?= e($c['slug']) ?></div>
                        </td>
                        <td>
                            <?php if (!empty($c['plan_name'])): ?>
                                <span class="rs-plan <?= e($planClass) ?>"><?= e($c['plan_name']) ?></span>
                            <?php else: ?>
                                <span class="sub">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($bcLabel) ?></td>
                        <td><span class="rs-st <?= e($c['status_label']['class']) ?>"><?= e($c['status_label']['label']) ?></span></td>
                        <td><?= format_date($c['created_at'], 'd/m/Y') ?></td>
                        <td><strong>€<?= number_format((float)$c['annual_commission'], 0, ',', '.') ?></strong></td>
                        <td style="text-align:right;">
                            <a href="<?= url($c['slug']) ?>" target="_blank" rel="noopener" class="rs-btn rs-btn-ghost rs-btn-sm" title="Apri sito pubblico">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
