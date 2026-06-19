<?php
/** @var array $params @var array $affected @var int $covers */
$DAYS_IT = ['Dom','Lun','Mar','Mer','Gio','Ven','Sab'];
$fmt = function (string $d) use ($DAYS_IT) {
    $t = strtotime($d);
    return $DAYS_IT[(int)date('w', $t)] . ' ' . date('d/m/Y', $t);
};
$periodLabel = $params['date_from'] === $params['date_to']
    ? $fmt($params['date_from'])
    : ($fmt($params['date_from']) . ' → ' . $fmt($params['date_to']));
$n = count($affected);
?>
<div class="ec-page">
    <div class="res-page-header">
        <h1><i class="bi bi-exclamation-octagon-fill" style="color:#b3261e;"></i> Chiusura straordinaria — conferma</h1>
    </div>

    <div class="card mb-3" style="max-width:760px;padding:1.1rem 1.25rem;">
        <div class="d-flex flex-wrap gap-4">
            <div><div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.3px;">Periodo</div><div class="fw-bold"><?= e($periodLabel) ?></div></div>
            <div><div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.3px;">Ambito</div><div class="fw-bold"><?= e($params['scope_label']) ?></div></div>
            <div><div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.3px;">Interessate</div><div class="fw-bold"><?= $n ?> <?= $n === 1 ? 'prenotazione' : 'prenotazioni' ?> · <?= $covers ?> coperti</div></div>
        </div>
    </div>

    <form method="POST" action="<?= url('dashboard/emergency-closure/apply') ?>" style="max-width:760px;">
        <?= csrf_field() ?>
        <input type="hidden" name="date_from" value="<?= e($params['date_from']) ?>">
        <input type="hidden" name="date_to" value="<?= e($params['date_to']) ?>">
        <input type="hidden" name="scope" value="<?= e($params['scope']) ?>">
        <?php if ($params['time_from']): ?><input type="hidden" name="time_from" value="<?= e(substr($params['time_from'], 0, 5)) ?>"><?php endif; ?>
        <?php if ($params['time_to']): ?><input type="hidden" name="time_to" value="<?= e(substr($params['time_to'], 0, 5)) ?>"><?php endif; ?>
        <input type="hidden" name="message" value="<?= e($params['message']) ?>">

        <label class="form-label fw-semibold">Come gestire le prenotazioni esistenti</label>
        <div class="ec-modes">
            <label class="ec-mode">
                <input type="radio" name="mode" value="suspend" checked>
                <span class="ec-pick"></span>
                <span class="ec-mode-body">
                    <span class="ec-mode-t"><i class="bi bi-pause-circle-fill" style="color:#8b5cf6;"></i> Sospendi <span class="badge bg-success-subtle text-success-emphasis ms-1" style="font-size:.62rem;">CONSIGLIATA</span></span>
                    <span class="ec-mode-d">Per imprevisti <b>incerti</b>. Le prenotazioni non vengono cancellate: passano "in sospeso", il cliente riceve "ti aggiorniamo a breve". Se riapri in tempo le <b>recuperi</b>.</span>
                </span>
            </label>
            <label class="ec-mode">
                <input type="radio" name="mode" value="cancel">
                <span class="ec-pick"></span>
                <span class="ec-mode-body">
                    <span class="ec-mode-t"><i class="bi bi-x-circle-fill" style="color:#dc3545;"></i> Annulla subito</span>
                    <span class="ec-mode-d">Per chiusure <b>certe</b>. Le prenotazioni vengono annullate e i clienti ricevono subito le scuse + invito a riprenotare. <b>Nessun recupero</b>.</span>
                </span>
            </label>
        </div>

        <?php if ($n > 0): ?>
        <label class="form-label fw-semibold mt-4">Prenotazioni interessate</label>
        <div class="card" style="overflow:hidden;">
            <?php foreach ($affected as $r): ?>
            <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom" style="font-size:.86rem;">
                <span class="fw-bold" style="width:54px;"><?= substr($r['reservation_time'], 0, 5) ?></span>
                <span class="text-muted" style="width:84px;"><?= date('d/m', strtotime($r['reservation_date'])) ?></span>
                <span class="flex-grow-1"><?= e($r['first_name'] . ' ' . $r['last_name']) ?></span>
                <span class="text-muted fw-semibold"><?= (int)$r['party_size'] ?> pers.</span>
                <span class="badge <?= $r['status'] === 'confirmed' ? 'bg-success' : 'bg-warning text-dark' ?>"><?= status_label($r['status']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="alert alert-info mt-4" style="font-size:.88rem;">
            <i class="bi bi-info-circle"></i> Nessuna prenotazione esistente nel periodo. Verranno comunque bloccate le nuove prenotazioni.
        </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mt-4">
            <a href="<?= url('dashboard/emergency-closure') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Indietro</a>
            <button type="submit" class="btn btn-danger">
                <i class="bi bi-check2-circle"></i> Conferma chiusura
            </button>
        </div>
    </form>
</div>

<style>
.ec-modes { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; }
@media (max-width:640px){ .ec-modes { grid-template-columns:1fr; } }
.ec-mode { position:relative; display:flex; gap:.6rem; border:2px solid #e4e8eb; border-radius:12px; padding:.9rem 1rem; cursor:pointer; margin:0; }
.ec-mode:has(input:checked) { border-color:var(--brand); background:#f6fbf8; }
.ec-mode input { position:absolute; opacity:0; }
.ec-pick { flex-shrink:0; width:18px; height:18px; border-radius:50%; border:2px solid #c9d2d9; margin-top:2px; }
.ec-mode:has(input:checked) .ec-pick { border-color:var(--brand); background:var(--brand); box-shadow:inset 0 0 0 3px #fff; }
.ec-mode-body { display:flex; flex-direction:column; gap:.25rem; }
.ec-mode-t { font-weight:800; font-size:.92rem; }
.ec-mode-d { font-size:.78rem; color:#6c757d; line-height:1.4; }
</style>
