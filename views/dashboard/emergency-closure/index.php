<?php
/** @var array $tenant @var array $meals @var ?array $active @var string $today */
?>
<div class="ec-page">

    <div class="res-page-header">
        <h1><i class="bi bi-exclamation-octagon-fill" style="color:#b3261e;"></i> Chiusura straordinaria</h1>
    </div>
    <p style="color:#6c757d;font-size:.9rem;margin:.25rem 0 1.25rem;">
        Per imprevisti (guasto, allagamento, emergenza). Blocca le nuove prenotazioni nel periodo
        e gestisce quelle già prese avvisando i clienti. Diversa da
        <a href="<?= url('dashboard/settings/closures') ?>" style="color:var(--brand);">Chiusure e Ferie</a>, che serve per le chiusure programmate.
    </p>

    <?php if ($active): ?>
        <?php $ec = $active; include __DIR__ . '/../../partials/emergency-banner.php'; ?>
        <div class="alert alert-light border" style="font-size:.86rem;">
            C'è già una chiusura straordinaria attiva (sopra). Gestiscila prima di crearne un'altra, oppure procedi qui sotto per un periodo diverso.
        </div>
    <?php endif; ?>

    <div class="card" style="max-width:680px;padding:1.5rem;">
        <form method="POST" action="<?= url('dashboard/emergency-closure/preview') ?>" id="ec-form">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Dal</label>
                    <input type="date" name="date_from" class="form-control" value="<?= e($today) ?>" min="<?= e($today) ?>" required>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Al <span class="text-muted fw-normal">(lascia vuoto per un solo giorno)</span></label>
                    <input type="date" name="date_to" class="form-control" min="<?= e($today) ?>">
                </div>
            </div>

            <label class="form-label fw-semibold mt-4">Cosa chiudi</label>
            <div class="ec-scopes">
                <label class="ec-scope">
                    <input type="radio" name="scope" value="full" checked>
                    <span><i class="bi bi-calendar-x"></i> Giorno intero</span>
                </label>
                <?php foreach ($meals as $m): ?>
                <label class="ec-scope">
                    <input type="radio" name="scope" value="meal:<?= (int)$m['id'] ?>">
                    <span><i class="bi bi-clock"></i> Solo <?= e($m['display_name'] ?? $m['name']) ?>
                        <small class="text-muted">(<?= substr($m['start_time'], 0, 5) ?>–<?= substr($m['end_time'], 0, 5) ?>)</small>
                    </span>
                </label>
                <?php endforeach; ?>
                <label class="ec-scope">
                    <input type="radio" name="scope" value="custom">
                    <span><i class="bi bi-sliders"></i> Fascia oraria personalizzata</span>
                </label>
            </div>

            <div class="row g-3 mt-1" id="ec-custom-times" style="display:none;">
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Dalle</label>
                    <input type="time" name="time_from" class="form-control" step="900">
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Alle</label>
                    <input type="time" name="time_to" class="form-control" step="900">
                </div>
            </div>

            <label class="form-label fw-semibold mt-4">Messaggio ai clienti <span class="text-muted fw-normal">(opzionale)</span></label>
            <textarea name="message" class="form-control" rows="3" maxlength="500"
                placeholder="Es. Per un imprevisto tecnico siamo costretti a sospendere il servizio. Ci scusiamo per il disagio."></textarea>
            <div class="form-text">Verrà incluso nell'email inviata ai clienti.</div>

            <div class="d-flex justify-content-between align-items-center mt-4">
                <a href="<?= url('dashboard/reservations') ?>" class="btn btn-outline-secondary">Annulla</a>
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-people-fill"></i> Avanti: vedi prenotazioni interessate
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.ec-scopes { display:flex; flex-direction:column; gap:.5rem; }
.ec-scope { display:flex; align-items:center; gap:.6rem; border:1.5px solid #e4e8eb; border-radius:10px; padding:.65rem .85rem; cursor:pointer; margin:0; font-size:.9rem; transition:border-color .12s, background .12s; }
.ec-scope:hover { border-color:#c4ccd2; background:#fbfdfc; }
.ec-scope input { accent-color:var(--brand); }
.ec-scope:has(input:checked) { border-color:var(--brand); background:#f6fbf8; }
.ec-scope span i { color:var(--brand); margin-right:.15rem; }
</style>

<script nonce="<?= csp_nonce() ?>">
(function () {
    var custom = document.getElementById('ec-custom-times');
    document.querySelectorAll('#ec-form input[name="scope"]').forEach(function (r) {
        r.addEventListener('change', function () {
            var isCustom = document.querySelector('#ec-form input[name="scope"]:checked').value === 'custom';
            custom.style.display = isCustom ? '' : 'none';
            custom.querySelectorAll('input').forEach(function (i) { i.required = isCustom; });
        });
    });
    // tieni date_to >= date_from
    var df = document.querySelector('#ec-form input[name="date_from"]');
    var dt = document.querySelector('#ec-form input[name="date_to"]');
    df.addEventListener('change', function () { dt.min = df.value; });
})();
</script>
