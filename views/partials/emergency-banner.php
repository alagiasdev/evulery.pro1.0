<?php
/**
 * Banner "chiusura straordinaria attiva". Richiede $ec (riga emergency_closures
 * con status='active'). Mostrato in Prenotazioni/Home e nella pagina dedicata.
 */
if (empty($ec)) return;
$ecDays = ['Dom','Lun','Mar','Mer','Gio','Ven','Sab'];
$ecFmt = function (string $d) use ($ecDays) {
    $t = strtotime($d);
    return $ecDays[(int)date('w', $t)] . ' ' . date('d/m', $t);
};
$ecPeriod = $ec['date_from'] === $ec['date_to'] ? $ecFmt($ec['date_from']) : ($ecFmt($ec['date_from']) . ' → ' . $ecFmt($ec['date_to']));
$ecSuspend = $ec['mode'] === 'suspend';
$ecCount = (int)($ec['affected_count'] ?? 0);
?>
<div class="ec-banner <?= $ecSuspend ? 'ec-banner-susp' : 'ec-banner-closed' ?>">
    <i class="bi <?= $ecSuspend ? 'bi-pause-circle-fill' : 'bi-x-octagon-fill' ?> ec-banner-ic"></i>
    <div class="ec-banner-text">
        <b><?= $ecSuspend ? 'Servizio sospeso' : 'Servizio chiuso' ?> — <?= e($ec['scope_label']) ?> · <?= e($ecPeriod) ?></b>
        <small>
            <?php if ($ecSuspend): ?>
                <?= $ecCount > 0 ? $ecCount . ' ' . ($ecCount === 1 ? 'prenotazione in sospeso' : 'prenotazioni in sospeso') . ' · clienti avvisati' : 'nuove prenotazioni bloccate' ?>. Quando sai com'è andata:
            <?php else: ?>
                <?= $ecCount > 0 ? $ecCount . ' ' . ($ecCount === 1 ? 'prenotazione annullata' : 'prenotazioni annullate') : 'nuove prenotazioni bloccate' ?>. Quando riapri il locale:
            <?php endif; ?>
        </small>
    </div>
    <div class="ec-banner-actions">
        <form method="POST" action="<?= url('dashboard/emergency-closure/reopen') ?>" style="display:inline;"
              data-confirm="<?= $ecSuspend ? 'Riaprire il servizio? Le prenotazioni future tornano confermate, quelle gia&#39; passate vengono annullate con scuse.' : 'Riaprire le prenotazioni per questo periodo?' ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="closure_id" value="<?= (int)$ec['id'] ?>">
            <button type="submit" class="btn btn-sm <?= $ecSuspend ? 'btn-success' : 'btn-outline-success' ?>">
                <i class="bi bi-arrow-clockwise"></i> <?= $ecSuspend ? 'Riapri / Tutto risolto' : 'Riapri le prenotazioni' ?>
            </button>
        </form>
        <?php if ($ecSuspend): ?>
        <form method="POST" action="<?= url('dashboard/emergency-closure/close') ?>" style="display:inline;"
              data-confirm="Confermare la chiusura definitiva? Le prenotazioni in sospeso verranno annullate con email di scuse.">
            <?= csrf_field() ?>
            <input type="hidden" name="closure_id" value="<?= (int)$ec['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-x-octagon"></i> Chiudi definitivo
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>
