<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">Ricariche crediti</h1>
        <p class="admin-page-sub" style="margin-bottom:0;">
            Richieste di ricarica crediti email inviate dai reseller per i loro clienti.
        </p>
    </div>
</div>

<!-- Tab/Filtri -->
<div style="display:flex;gap:.4rem;margin-bottom:1rem;flex-wrap:wrap;">
    <?php
        $tabs = [
            ''         => 'Tutte (' . array_sum($counts) . ')',
            'pending'  => 'In attesa (' . (int)$counts['pending'] . ')',
            'approved' => 'Approvate (' . (int)$counts['approved'] . ')',
            'rejected' => 'Rifiutate (' . (int)$counts['rejected'] . ')',
        ];
        foreach ($tabs as $key => $label):
            $isActive = ($filterStatus ?? '') === $key;
            $url = $key === '' ? url('admin/credit-requests') : url('admin/credit-requests') . '?status=' . $key;
    ?>
        <a href="<?= e($url) ?>"
           style="padding:.5rem .9rem;border-radius:8px;font-size:.82rem;font-weight:600;text-decoration:none;
                  background:<?= $isActive ? '#1565C0' : '#fff' ?>;
                  color:<?= $isActive ? '#fff' : '#495057' ?>;
                  border:1px solid <?= $isActive ? '#1565C0' : '#dee2e6' ?>;">
            <?= e($label) ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="adm-card">
    <?php if (empty($requests)): ?>
        <div style="padding:2rem;text-align:center;color:#6c757d;">
            Nessuna richiesta in questa lista.
        </div>
    <?php else: ?>
        <table class="adm-table" style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#fafbfc;border-bottom:1px solid #e9ecef;">
                    <th style="padding:12px 14px;text-align:left;font-size:.68rem;text-transform:uppercase;letter-spacing:.5px;color:#495057;">Ricevuta</th>
                    <th style="padding:12px 14px;text-align:left;font-size:.68rem;text-transform:uppercase;letter-spacing:.5px;color:#495057;">Reseller</th>
                    <th style="padding:12px 14px;text-align:left;font-size:.68rem;text-transform:uppercase;letter-spacing:.5px;color:#495057;">Cliente</th>
                    <th style="padding:12px 14px;text-align:right;font-size:.68rem;text-transform:uppercase;letter-spacing:.5px;color:#495057;">Crediti</th>
                    <th style="padding:12px 14px;text-align:right;font-size:.68rem;text-transform:uppercase;letter-spacing:.5px;color:#495057;">Saldo attuale</th>
                    <th style="padding:12px 14px;text-align:left;font-size:.68rem;text-transform:uppercase;letter-spacing:.5px;color:#495057;">Stato</th>
                    <th style="padding:12px 14px;text-align:right;font-size:.68rem;text-transform:uppercase;letter-spacing:.5px;color:#495057;">Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $r): ?>
                    <?php
                        $balanceAfter = (int)$r['email_credits_balance'] + ($r['status'] === 'pending' ? (int)$r['credits_requested'] : 0);
                        $stClass = match ($r['status']) {
                            'pending'  => 'background:#FFF3E0;color:#E65100;border-color:#E65100;',
                            'approved' => 'background:#E8F5E9;color:#00844A;border-color:#00844A;',
                            'rejected' => 'background:#FFEBEE;color:#C62828;border-color:#C62828;',
                            default    => 'background:#eceff1;color:#6c757d;border-color:#6c757d;',
                        };
                    ?>
                    <tr style="border-bottom:1px solid #e9ecef;vertical-align:top;">
                        <td style="padding:14px;font-size:.82rem;">
                            <?= format_date($r['created_at'], 'd/m/Y H:i') ?>
                        </td>
                        <td style="padding:14px;">
                            <div style="font-weight:600;font-size:.85rem;"><?= e(trim($r['reseller_first_name'] . ' ' . $r['reseller_last_name'])) ?></div>
                        </td>
                        <td style="padding:14px;">
                            <div style="font-weight:600;font-size:.85rem;"><?= e($r['tenant_name']) ?></div>
                            <div style="font-size:.74rem;color:#6c757d;">/<?= e($r['tenant_slug']) ?></div>
                        </td>
                        <td style="padding:14px;text-align:right;font-weight:700;font-size:.95rem;color:#1565C0;">
                            +<?= number_format((int)$r['credits_requested'], 0, ',', '.') ?>
                        </td>
                        <td style="padding:14px;text-align:right;font-size:.82rem;">
                            <?= number_format((int)$r['email_credits_balance'], 0, ',', '.') ?>
                            <?php if ($r['status'] === 'pending'): ?>
                                <div style="font-size:.72rem;color:#00844A;font-weight:600;">→ <?= number_format($balanceAfter, 0, ',', '.') ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="padding:14px;">
                            <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:100px;font-size:.72rem;font-weight:700;border:1.5px solid;<?= $stClass ?>">
                                <?= e($statuses[$r['status']] ?? $r['status']) ?>
                            </span>
                            <?php if ($r['status'] !== 'pending' && $r['processed_at']): ?>
                                <div style="font-size:.7rem;color:#6c757d;margin-top:4px;">
                                    <?= format_date($r['processed_at'], 'd/m H:i') ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="padding:14px;text-align:right;">
                            <?php if ($r['status'] === 'pending'): ?>
                                <div style="display:flex;gap:.4rem;justify-content:flex-end;">
                                    <form method="POST" action="<?= url('admin/credit-requests/' . (int)$r['id'] . '/approve') ?>" style="display:inline;"
                                          data-confirm="Confermi l'approvazione? I crediti verranno accreditati subito.">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="adm-btn adm-btn-success adm-btn-sm" style="background:#00844A;color:#fff;border:none;padding:.4rem .8rem;border-radius:6px;font-size:.78rem;font-weight:600;cursor:pointer;">
                                            <i class="bi bi-check"></i> Approva
                                        </button>
                                    </form>
                                    <button type="button" class="adm-btn adm-btn-sm" style="background:#C62828;color:#fff;border:none;padding:.4rem .8rem;border-radius:6px;font-size:.78rem;font-weight:600;cursor:pointer;" data-reject-id="<?= (int)$r['id'] ?>">
                                        <i class="bi bi-x"></i> Rifiuta
                                    </button>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if (!empty($r['notes_reseller'])): ?>
                        <tr style="border-bottom:1px solid #e9ecef;">
                            <td colspan="7" style="padding:0 14px 14px;font-size:.78rem;color:#495057;">
                                <strong style="color:#1a1d23;">Nota reseller:</strong> <?= nl2br(e($r['notes_reseller'])) ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($r['status'] === 'rejected' && !empty($r['notes_admin'])): ?>
                        <tr style="border-bottom:1px solid #e9ecef;">
                            <td colspan="7" style="padding:0 14px 14px;">
                                <div style="padding:8px 12px;background:#FFEBEE;border-left:3px solid #C62828;border-radius:4px;font-size:.78rem;color:#C62828;">
                                    <strong>Motivo rifiuto:</strong> <?= e($r['notes_admin']) ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <!-- Form rifiuto nascosto, attivato da JS -->
                    <?php if ($r['status'] === 'pending'): ?>
                        <tr id="reject-form-<?= (int)$r['id'] ?>" style="display:none;border-bottom:1px solid #e9ecef;background:#FFEBEE;">
                            <td colspan="7" style="padding:14px;">
                                <form method="POST" action="<?= url('admin/credit-requests/' . (int)$r['id'] . '/reject') ?>" style="display:flex;gap:.5rem;align-items:flex-start;">
                                    <?= csrf_field() ?>
                                    <textarea name="reason" placeholder="Motivo del rifiuto (sarà inviato via email al reseller)..." required
                                              style="flex:1;padding:.5rem .75rem;border:1px solid #C62828;border-radius:6px;font-size:.82rem;font-family:inherit;resize:vertical;min-height:60px;"></textarea>
                                    <div style="display:flex;flex-direction:column;gap:.4rem;">
                                        <button type="submit" style="background:#C62828;color:#fff;border:none;padding:.4rem .8rem;border-radius:6px;font-size:.78rem;font-weight:600;cursor:pointer;">
                                            Conferma rifiuto
                                        </button>
                                        <button type="button" data-cancel-id="<?= (int)$r['id'] ?>" style="background:#fff;color:#6c757d;border:1px solid #dee2e6;padding:.4rem .8rem;border-radius:6px;font-size:.78rem;font-weight:600;cursor:pointer;">
                                            Annulla
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script nonce="<?= csp_nonce() ?>">
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-reject-id]').forEach(function(b) {
        b.addEventListener('click', function() {
            var row = document.getElementById('reject-form-' + b.getAttribute('data-reject-id'));
            if (row) row.style.display = 'table-row';
        });
    });
    document.querySelectorAll('[data-cancel-id]').forEach(function(b) {
        b.addEventListener('click', function() {
            var row = document.getElementById('reject-form-' + b.getAttribute('data-cancel-id'));
            if (row) row.style.display = 'none';
        });
    });
});
</script>
