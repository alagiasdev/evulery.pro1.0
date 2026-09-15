<?php
/**
 * Admin → Log applicativi. Legge storage/logs/ senza passare dal File Manager.
 * I colori usano i token dichiarati in admin.css (vedi DESIGN.md).
 */
$peso = $pesoTotale > 1048576
    ? round($pesoTotale / 1048576, 1) . ' MB'
    : round($pesoTotale / 1024) . ' KB';

$colori = [
    'error'   => ['var(--noshow-bg)',  'var(--danger)'],
    'warning' => ['var(--warn-bg)',    'var(--warn-text)'],
    'info'    => ['var(--admin-accent-bg)', 'var(--admin-accent)'],
];
?>

<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title"><i class="bi bi-journal-text me-2"></i>Log applicativi</h1>
        <p class="admin-page-sub" style="margin-bottom:0;">
            Le righe scritte da <code>app_log()</code> in <code>storage/logs/</code>.
            Contengono indirizzi IP: restano qui dentro.
        </p>
    </div>
</div>

<!-- Ricerca: la ragione per cui questa pagina esiste -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="<?= url('admin/logs') ?>" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="adm-form-label">Cerca in tutti i file</label>
                <input type="text" name="q" value="<?= e($q) ?>" class="adm-form-input"
                       placeholder="Es. CSRF, Stripe, errore..." autofocus>
            </div>
            <div class="col-md-3">
                <label class="adm-form-label">Livello</label>
                <select name="livello" class="adm-form-select">
                    <option value="">Tutti</option>
                    <?php foreach (['error' => 'Solo errori', 'warning' => 'Solo warning', 'info' => 'Solo info'] as $k => $lbl): ?>
                    <option value="<?= $k ?>" <?= $livello === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-success"><i class="bi bi-search me-1"></i>Cerca</button>
                <?php if ($q !== '' || $livello !== '' || $fileCorrente): ?>
                <a href="<?= url('admin/logs') ?>" class="btn btn-outline-secondary">Azzera</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="row g-3">
    <!-- Elenco dei giorni -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-baseline mb-2">
                    <div class="fw-bold" style="font-size:.9rem;">Giorni</div>
                    <div style="font-size:.75rem;color:var(--mute);"><?= (int)$totaleFile ?> file · <?= $peso ?></div>
                </div>

                <?php if (empty($files)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-inbox" style="font-size:2rem;color:var(--faint);"></i>
                        <p style="color:var(--mute);margin-top:.75rem;font-size:.88rem;margin-bottom:0;">Nessun log registrato.</p>
                    </div>
                <?php else: ?>
                    <div style="max-height:520px;overflow-y:auto;">
                    <?php foreach ($files as $f): ?>
                        <a href="<?= url('admin/logs') ?>?file=<?= urlencode($f['nome']) ?><?= $livello ? '&livello=' . e($livello) : '' ?>"
                           style="display:flex;align-items:center;gap:.5rem;padding:.45rem .6rem;border-radius:6px;text-decoration:none;
                                  <?= $fileCorrente === $f['nome'] ? 'background:var(--brand-light);' : '' ?>">
                            <span style="flex:1;font-size:.82rem;color:var(--ink);font-family:ui-monospace,monospace;">
                                <?= e($f['data'] ?? $f['nome']) ?>
                                <?php if ($f['perf']): ?><span style="font-size:.62rem;color:var(--faint);">perf</span><?php endif; ?>
                            </span>
                            <?php if ($f['error'] > 0): ?>
                                <span class="adm-badge adm-badge-inactive"><?= (int)$f['error'] ?></span>
                            <?php endif; ?>
                            <?php if ($f['warning'] > 0): ?>
                                <span style="font-size:.68rem;font-weight:700;color:var(--warn-text);"><?= (int)$f['warning'] ?>&nbsp;w</span>
                            <?php endif; ?>
                            <span style="font-size:.7rem;color:var(--faint);"><?= (int)$f['righe'] ?></span>
                        </a>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pulizia: nessuno li ha mai cancellati -->
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-body">
                <div class="fw-bold mb-1" style="font-size:.9rem;">Pulizia</div>
                <p style="font-size:.78rem;color:var(--mute);">I file non vengono eliminati da soli.</p>
                <form method="POST" action="<?= url('admin/logs/purge') ?>"
                      data-confirm="Eliminare i log più vecchi del periodo scelto? L'operazione non è reversibile.">
                    <?= csrf_field() ?>
                    <div class="d-flex gap-2">
                        <select name="giorni" class="adm-form-select" style="max-width:180px;">
                            <option value="30">più vecchi di 30 giorni</option>
                            <option value="90" selected>più vecchi di 90 giorni</option>
                            <option value="180">più vecchi di 180 giorni</option>
                        </select>
                        <button type="submit" class="adm-action-btn adm-action-danger" title="Elimina">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Righe -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="fw-bold mb-2" style="font-size:.9rem;">
                    <?php if ($q !== ''): ?>
                        Risultati per &ldquo;<?= e($q) ?>&rdquo;
                    <?php elseif ($fileCorrente): ?>
                        <?= e($fileCorrente) ?>
                    <?php else: ?>
                        Righe
                    <?php endif; ?>
                    <span style="font-weight:400;color:var(--mute);font-size:.8rem;">
                        <?= count($righe) ?><?= $troncato ? '+ (troncato)' : '' ?>
                    </span>
                </div>

                <?php if (empty($righe)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-search" style="font-size:2rem;color:var(--faint);"></i>
                        <p style="color:var(--mute);margin-top:.75rem;font-size:.88rem;margin-bottom:0;">
                            <?= $q !== '' ? 'Nessuna riga contiene questo testo.' : 'Scegli un giorno a sinistra, oppure cerca.' ?>
                        </p>
                    </div>
                <?php else: ?>
                    <?php if ($troncato): ?>
                    <div style="background:var(--warn-bg);color:var(--warn-text);padding:.5rem .75rem;border-radius:6px;font-size:.78rem;margin-bottom:.6rem;">
                        <i class="bi bi-exclamation-triangle me-1"></i>Elenco troncato: restringi la ricerca per vedere il resto.
                    </div>
                    <?php endif; ?>

                    <div style="max-height:560px;overflow-y:auto;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.74rem;">
                    <?php foreach ($righe as $r): ?>
                        <?php [$bg, $fg] = $colori[$r['livello']] ?? ['transparent', 'var(--body)']; ?>
                        <div style="display:flex;gap:.5rem;padding:.3rem .4rem;border-bottom:1px solid var(--hairline);">
                            <span style="color:var(--faint);white-space:nowrap;"><?= e($r['ts']) ?></span>
                            <?php if ($r['livello'] !== ''): ?>
                            <span style="background:<?= $bg ?>;color:<?= $fg ?>;padding:0 .35rem;border-radius:4px;font-weight:700;height:fit-content;white-space:nowrap;">
                                <?= e($r['livello']) ?>
                            </span>
                            <?php endif; ?>
                            <span style="color:var(--ink);word-break:break-word;flex:1;"><?= e($r['testo']) ?></span>
                            <?php // La data e' gia' nell'orario: si segnala solo se la riga
                                  // viene dal log delle prestazioni, che e' un'altra cosa.
                            if (str_starts_with($r['file'], 'perf-')): ?>
                            <span style="color:var(--faint);white-space:nowrap;">perf</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
