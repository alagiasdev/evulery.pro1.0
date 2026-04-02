<?php
$statusBadges = [
    'draft'   => ['Bozza', '#6c757d', 'bg-secondary'],
    'queued'  => ['In coda', '#ffc107', 'bg-warning'],
    'sending' => ['Invio in corso', '#0d6efd', 'bg-primary'],
    'sent'    => ['Inviata', '#198754', 'bg-success'],
    'failed'  => ['Fallita', '#dc3545', 'bg-danger'],
];
$sb = $statusBadges[$campaign['status']] ?? ['—', '#6c757d', 'bg-secondary'];

$segLabels = [
    'all' => 'Tutti i clienti',
    'nuovo' => 'Nuovi',
    'occasionale' => 'Occasionali',
    'abituale' => 'Abituali',
    'vip' => 'VIP',
    'inactive' => 'Inattivi',
];
?>

<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;">
    <a href="<?= url('dashboard/communications') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div style="flex:1;">
        <h5 style="font-weight:700;margin:0;"><?= e($campaign['subject']) ?></h5>
        <p style="font-size:.82rem;color:#6c757d;margin:0;">
            Creata il <?= date('d/m/Y H:i', strtotime($campaign['created_at'])) ?>
        </p>
    </div>
    <span class="badge <?= $sb[2] ?>" style="font-size:.8rem;"><?= $sb[0] ?></span>
</div>

<div style="display:grid;grid-template-columns:1fr 300px;gap:1.25rem;align-items:start;">
    <!-- Left: Message content -->
    <div>
        <div class="card" style="padding:1.25rem;">
            <div style="font-weight:600;font-size:.85rem;color:#6c757d;margin-bottom:.5rem;">
                <i class="bi bi-chat-text me-1"></i> Messaggio
            </div>
            <div style="font-size:.9rem;line-height:1.7;white-space:pre-wrap;color:#1a1d23;"><?= e($campaign['body_text']) ?></div>
        </div>

        <?php if (in_array($campaign['status'], ['draft', 'queued'])): ?>
        <div style="margin-top:1rem;display:flex;gap:.5rem;align-items:center;">
            <?php if ($campaign['status'] === 'queued'): ?>
            <form method="POST" action="<?= url("dashboard/communications/{$campaign['id']}/send-now") ?>" style="display:inline;" data-confirm="Inviare subito questa comunicazione?">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="bi bi-send me-1"></i> Invia ora
                </button>
            </form>
            <?php endif; ?>
            <?php
                $btnLabel = $campaign['status'] === 'draft' ? 'Elimina bozza' : 'Annulla e elimina';
                $confirmMsg = $campaign['status'] === 'queued'
                    ? 'Annullare questa campagna? I crediti non utilizzati verranno rimborsati.'
                    : 'Eliminare questa bozza?';
            ?>
            <form method="POST" action="<?= url("dashboard/communications/{$campaign['id']}/delete") ?>" style="display:inline;" data-confirm="<?= e($confirmMsg) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-trash me-1"></i> <?= $btnLabel ?>
                </button>
            </form>
        </div>
        <?php endif; ?>

        <?php if (in_array($campaign['status'], ['sent', 'failed'])): ?>
        <div style="margin-top:1rem;display:flex;gap:.5rem;align-items:center;">
            <?php if ($campaign['status'] === 'sent' && (int)$campaign['failed_count'] > 0): ?>
            <form method="POST" action="<?= url("dashboard/communications/{$campaign['id']}/retry") ?>" style="display:inline;" data-confirm="Ritentare l'invio a <?= (int)$campaign['failed_count'] ?> destinatari falliti?">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-warning btn-sm">
                    <i class="bi bi-arrow-repeat me-1"></i> Riprova fallite (<?= (int)$campaign['failed_count'] ?>)
                </button>
            </form>
            <?php endif; ?>
            <form method="POST" action="<?= url("dashboard/communications/{$campaign['id']}/archive") ?>" style="display:inline;" data-confirm="Archiviare questa comunicazione? Non sarà più visibile nella lista.">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-archive me-1"></i> Archivia
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right: Stats -->
    <div>
        <div class="card" style="padding:1rem;">
            <div style="font-weight:700;font-size:.85rem;margin-bottom:.75rem;">
                <i class="bi bi-bar-chart me-1"></i> Statistiche
            </div>

            <div style="display:flex;justify-content:space-between;padding:.4rem 0;border-bottom:1px solid #f0f0f0;">
                <span style="font-size:.82rem;color:#6c757d;">Segmento</span>
                <span style="font-size:.82rem;font-weight:600;"><?= $segLabels[$campaign['segment_filter']] ?? '—' ?></span>
            </div>
            <?php if ($campaign['segment_filter'] === 'inactive' && $campaign['inactive_days']): ?>
            <div style="display:flex;justify-content:space-between;padding:.4rem 0;border-bottom:1px solid #f0f0f0;">
                <span style="font-size:.82rem;color:#6c757d;">Inattivi da</span>
                <span style="font-size:.82rem;font-weight:600;"><?= (int)$campaign['inactive_days'] ?> giorni</span>
            </div>
            <?php endif; ?>
            <div style="display:flex;justify-content:space-between;padding:.4rem 0;border-bottom:1px solid #f0f0f0;">
                <span style="font-size:.82rem;color:#6c757d;">Destinatari</span>
                <span style="font-size:.9rem;font-weight:700;"><?= (int)$campaign['total_recipients'] ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:.4rem 0;border-bottom:1px solid #f0f0f0;">
                <span style="font-size:.82rem;color:#6c757d;">Inviate</span>
                <span style="font-size:.9rem;font-weight:700;color:#198754;"><?= (int)$campaign['sent_count'] ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:.4rem 0;border-bottom:1px solid #f0f0f0;">
                <span style="font-size:.82rem;color:#6c757d;">Fallite</span>
                <span style="font-size:.9rem;font-weight:700;color:<?= (int)$campaign['failed_count'] > 0 ? '#dc3545' : '#adb5bd' ?>;"><?= (int)$campaign['failed_count'] ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:.4rem 0;border-bottom:1px solid #f0f0f0;">
                <span style="font-size:.82rem;color:#6c757d;">Crediti usati</span>
                <span style="font-size:.82rem;font-weight:600;"><?= (int)$campaign['credits_used'] ?></span>
            </div>
            <?php if ($campaign['sent_at']): ?>
            <div style="display:flex;justify-content:space-between;padding:.4rem 0;">
                <span style="font-size:.82rem;color:#6c757d;">Inviata il</span>
                <span style="font-size:.82rem;font-weight:600;"><?= date('d/m/Y H:i', strtotime($campaign['sent_at'])) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style nonce="<?= csp_nonce() ?>">
@media (max-width: 768px) {
    .page-body > div:last-child { grid-template-columns: 1fr !important; }
}
</style>
