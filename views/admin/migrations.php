<?php
$applied = $status['applied'] ?? [];
$pending = $status['pending'] ?? [];
$recent  = $status['recent']  ?? [];
$total   = $status['total']   ?? 0;
$nApplied = count($applied);
$nPending = count($pending);
?>

<div class="page-head">
    <h2><i class="bi bi-database-fill-gear me-2"></i>Migration Database</h2>
    <p class="text-muted small mb-0">Applica le migration SQL pending direttamente da qui dopo un deploy. Equivalente al comando CLI <code>php scripts/migrate.php</code>.</p>
</div>

<!-- KPI cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold mb-1">Totale</div>
                <div class="fs-2 fw-bold"><?= (int)$total ?></div>
                <div class="small text-muted">file SQL in <code>database/migrations/</code></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm" style="background:#e8f5e9;">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold mb-1" style="color:#2E7D32;">Applicate</div>
                <div class="fs-2 fw-bold" style="color:#1B5E20;"><?= (int)$nApplied ?></div>
                <div class="small" style="color:#2E7D32;">migration eseguite</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm" style="background:<?= $nPending > 0 ? '#fff3cd' : '#f5f5f5' ?>;">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold mb-1" style="color:<?= $nPending > 0 ? '#664d03' : '#6c757d' ?>;">Pending</div>
                <div class="fs-2 fw-bold" style="color:<?= $nPending > 0 ? '#664d03' : '#6c757d' ?>;"><?= (int)$nPending ?></div>
                <div class="small" style="color:<?= $nPending > 0 ? '#664d03' : '#6c757d' ?>;">in attesa di esecuzione</div>
            </div>
        </div>
    </div>
</div>

<!-- Risultato ultima esecuzione (se presente) -->
<?php if (!empty($lastResult)): ?>
<div class="card border-0 shadow-sm mb-4 <?= !empty($lastResult['success']) ? 'border-success' : 'border-danger' ?>">
    <div class="card-body">
        <h6 class="card-title">
            <?php if (!empty($lastResult['success'])): ?>
                <i class="bi bi-check-circle-fill text-success me-1"></i> Ultima esecuzione completata
            <?php else: ?>
                <i class="bi bi-exclamation-triangle-fill text-danger me-1"></i> Errore nell'ultima esecuzione
            <?php endif; ?>
        </h6>
        <?php if (!empty($lastResult['applied'])): ?>
        <div class="small">
            <strong>Applicate (<?= count($lastResult['applied']) ?>):</strong>
            <ul class="mb-0 ps-3 mt-1">
                <?php foreach ($lastResult['applied'] as $a): ?>
                <li>
                    <code><?= e($a['filename']) ?></code>
                    <span class="text-muted">— <?= (int)$a['duration_ms'] ?> ms</span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        <?php if (!empty($lastResult['error'])): ?>
        <div class="alert alert-danger mt-2 mb-0 small">
            <strong>Errore</strong>
            <?php if (!empty($lastResult['error_file'])): ?>
            su <code><?= e($lastResult['error_file']) ?></code>
            <?php endif; ?>:
            <pre class="mb-0 mt-1" style="white-space:pre-wrap;font-size:.85em;"><?= e($lastResult['error']) ?></pre>
            <div class="mt-2">
                ⚠ MySQL non supporta DDL transactional. La migration potrebbe essere stata applicata PARZIALMENTE.
                Verifica lo stato del DB e correggi prima di rilanciare.
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Pending list + apply button -->
<?php if ($nPending > 0): ?>
<div class="card border-0 shadow-sm mb-4 border-warning">
    <div class="card-header bg-warning bg-opacity-25 border-0">
        <h5 class="card-title mb-0">
            <i class="bi bi-clock-history me-1"></i>
            Migration pending (<?= $nPending ?>)
        </h5>
    </div>
    <div class="card-body">
        <p class="small text-muted mb-2">Le seguenti migration sono presenti nei file ma non ancora applicate al database. Clicca "Applica tutte" per eseguirle in sequenza.</p>
        <ul class="list-group list-group-flush mb-3">
            <?php foreach ($pending as $f): ?>
            <li class="list-group-item d-flex align-items-center py-2 border-0 ps-0">
                <i class="bi bi-file-earmark-code text-warning me-2"></i>
                <code><?= e($f) ?></code>
            </li>
            <?php endforeach; ?>
        </ul>
        <form method="POST" action="<?= url('admin/migrations/run') ?>" onsubmit="return confirm('Confermi l\'esecuzione di <?= $nPending ?> migration sul database? Operazione non reversibile.');">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-success btn-lg">
                <i class="bi bi-play-fill me-1"></i>
                Applica tutte le pending (<?= $nPending ?>)
            </button>
        </form>
    </div>
</div>
<?php else: ?>
<div class="card border-0 shadow-sm mb-4 border-success">
    <div class="card-body text-center py-4">
        <i class="bi bi-check-circle-fill text-success" style="font-size:2.5rem;"></i>
        <h5 class="mt-2 mb-0">Tutto allineato</h5>
        <p class="small text-muted mb-0">Nessuna migration da applicare. Il database è aggiornato all'ultima versione dello schema.</p>
    </div>
</div>
<?php endif; ?>

<!-- Recenti -->
<?php if (!empty($recent)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-light border-0">
        <h6 class="card-title mb-0">
            <i class="bi bi-clock me-1"></i>
            Ultime 5 applicate
        </h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr class="text-muted small text-uppercase">
                    <th class="ps-3">File</th>
                    <th>Applicata il</th>
                    <th>Durata</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $r): ?>
                <tr>
                    <td class="ps-3"><code class="small"><?= e($r['filename']) ?></code></td>
                    <td class="small text-muted"><?= e($r['applied_at']) ?></td>
                    <td class="small text-muted"><?= (int)$r['duration_ms'] ?> ms</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Documentazione -->
<details class="mt-4">
    <summary class="text-muted small" style="cursor:pointer;">ℹ️ Come funziona</summary>
    <div class="card border-0 bg-light mt-2">
        <div class="card-body small text-muted">
            <ul class="mb-0 ps-3">
                <li>I file SQL stanno in <code>database/migrations/</code> con naming <code>NNN_descrittivo.sql</code></li>
                <li>La tabella <code>migrations</code> traccia quali sono già state applicate (idempotente)</li>
                <li>Le pending vengono eseguite in ordine alfanumerico, una alla volta</li>
                <li>In caso di errore lo script si ferma e segnala il file colpevole</li>
                <li>Un lock MySQL (<code>GET_LOCK</code>) previene esecuzioni concorrenti</li>
                <li>Equivalente CLI: <code>php scripts/migrate.php</code></li>
            </ul>
        </div>
    </div>
</details>
