<div class="rs-page-header">
    <div>
        <h1 class="rs-page-title">Ricariche crediti email</h1>
        <p class="rs-page-sub">Richiedi ricariche di crediti email per i tuoi clienti. L'admin approva e attiva.</p>
    </div>
    <div>
        <a href="<?= url('reseller/credits/create') ?>" class="rs-btn rs-btn-primary">
            <i class="bi bi-plus-circle"></i> Nuova richiesta
        </a>
    </div>
</div>

<!-- KPI -->
<div class="rs-kpi-grid">
    <div class="rs-kpi-card">
        <div class="rs-kpi-icon" style="background:#FFF3E0;color:#E65100;"><i class="bi bi-hourglass-split"></i></div>
        <div>
            <div class="val"><?= (int)$counts['pending'] ?></div>
            <div class="lbl">In attesa</div>
        </div>
    </div>
    <div class="rs-kpi-card">
        <div class="rs-kpi-icon" style="background:#E8F5E9;color:#00844A;"><i class="bi bi-check-circle"></i></div>
        <div>
            <div class="val"><?= (int)$counts['approved'] ?></div>
            <div class="lbl">Approvate</div>
        </div>
    </div>
    <div class="rs-kpi-card">
        <div class="rs-kpi-icon" style="background:#FFEBEE;color:#C62828;"><i class="bi bi-x-circle"></i></div>
        <div>
            <div class="val"><?= (int)$counts['rejected'] ?></div>
            <div class="lbl">Rifiutate</div>
        </div>
    </div>
    <div class="rs-kpi-card">
        <div class="rs-kpi-icon" style="background:#E3F2FD;color:#0277bd;"><i class="bi bi-envelope-paper"></i></div>
        <div>
            <div class="val"><?= number_format((int)$totalApproved, 0, ',', '.') ?></div>
            <div class="lbl">Crediti accreditati</div>
        </div>
    </div>
</div>

<div class="rs-card">
    <div class="rs-card-hdr">
        <span><i class="bi bi-clock-history"></i> Storico richieste</span>
    </div>
    <?php if (empty($requests)): ?>
        <div class="rs-card-empty">
            Nessuna richiesta ancora inviata. <a href="<?= url('reseller/credits/create') ?>" style="color:var(--rs-brand);">Crea la prima</a>.
        </div>
    <?php else: ?>
        <table class="rs-tbl">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Cliente</th>
                    <th style="text-align:right;">Crediti</th>
                    <th>Note</th>
                    <th>Stato</th>
                    <th>Processata</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $r): ?>
                    <tr>
                        <td><?= format_date($r['created_at'], 'd/m/Y H:i') ?></td>
                        <td>
                            <div class="name"><?= e($r['tenant_name']) ?></div>
                            <div class="sub">Saldo attuale: <?= number_format((int)$r['email_credits_balance'], 0, ',', '.') ?></div>
                        </td>
                        <td style="text-align:right;"><strong>+<?= number_format((int)$r['credits_requested'], 0, ',', '.') ?></strong></td>
                        <td style="max-width:280px;font-size:.82rem;color:#495057;">
                            <?= !empty($r['notes_reseller']) ? nl2br(e($r['notes_reseller'])) : '<span class="sub">—</span>' ?>
                            <?php if ($r['status'] === 'rejected' && !empty($r['notes_admin'])): ?>
                                <div style="margin-top:6px;padding:6px 10px;background:#FFEBEE;border-left:3px solid #C62828;border-radius:4px;font-size:.78rem;color:#C62828;">
                                    <strong>Motivo rifiuto:</strong> <?= e($r['notes_admin']) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><span class="rs-rq rs-rq-<?= e($r['status']) ?>"><?= e($statuses[$r['status']] ?? $r['status']) ?></span></td>
                        <td>
                            <?php if ($r['processed_at']): ?>
                                <?= format_date($r['processed_at'], 'd/m/Y H:i') ?>
                            <?php else: ?>
                                <span class="sub">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div style="background:#fff8e1;border-left:3px solid #ffc107;padding:.7rem 1rem;border-radius:6px;font-size:.8rem;color:#5d4037;margin-top:1rem;">
    <i class="bi bi-info-circle"></i>
    Tempo medio di approvazione: <strong>entro 1 giorno lavorativo</strong>. I crediti vengono accreditati direttamente sul saldo del cliente.
</div>
