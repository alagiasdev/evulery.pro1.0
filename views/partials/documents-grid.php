<?php
/**
 * Griglia documenti condivisa tra area reseller e admin.
 * Variabili attese (dal controller via view()):
 *   @var array  $groups      category => [key => doc]
 *   @var array  $categories  category => ['label'=>..,'icon'=>..]
 *   @var string $previewBase es. 'reseller/documents' | 'admin/documents'
 */
?>
<style>
.doc-lib .doc-sec { margin-bottom: 26px; }
.doc-lib .doc-sec-h { font-size:.8rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#006837; display:flex; align-items:center; gap:8px; margin:0 0 12px; }
.doc-lib .doc-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(255px,1fr)); gap:14px; }
.doc-lib .doc-card { position:relative; background:#fff; border:1px solid #e8edf0; border-radius:12px; padding:16px; display:flex; flex-direction:column; gap:8px; box-shadow:0 1px 2px rgba(0,0,0,.03); }
.doc-lib .doc-ic { width:38px; height:38px; border-radius:10px; background:#eef7f1; color:#006837; display:flex; align-items:center; justify-content:center; font-size:1.1rem; }
.doc-lib .doc-t { font-size:.94rem; font-weight:700; margin:0; }
.doc-lib .doc-p { font-size:.79rem; color:#6c757d; line-height:1.45; margin:0; flex:1; }
.doc-lib .doc-acts { display:flex; gap:.5rem; flex-wrap:wrap; margin-top:4px; }
.doc-lib .doc-btn { display:inline-flex; align-items:center; gap:6px; font-size:.78rem; font-weight:700; border-radius:8px; padding:7px 14px; text-decoration:none; border:1.5px solid #00844A; background:#00844A; color:#fff; }
.doc-lib .doc-btn:hover { background:#006837; border-color:#006837; }
.doc-lib .doc-btn-ghost { background:#fff; color:#006837; }
.doc-lib .doc-btn-ghost:hover { background:#eef7f1; }
.doc-lib .doc-soon { display:inline-flex; align-items:center; gap:6px; font-size:.76rem; font-weight:600; color:#9a7b00; background:#fff8e1; border:1px solid #f0d9a8; border-radius:8px; padding:6px 12px; }
.doc-lib .doc-badge { position:absolute; top:12px; right:12px; background:#fff8e1; border:1px solid #f0d9a8; color:#8a5b00; font-size:.62rem; font-weight:800; letter-spacing:.03em; border-radius:20px; padding:2px 8px; }
.doc-lib .doc-empty { color:#6c757d; font-size:.85rem; }
</style>

<div class="doc-lib">
<?php if (empty($groups)): ?>
    <p class="doc-empty">Nessun documento disponibile.</p>
<?php else: foreach ($groups as $catKey => $docs): $cat = $categories[$catKey] ?? ['label' => ucfirst($catKey), 'icon' => 'folder']; ?>
    <div class="doc-sec">
        <h3 class="doc-sec-h"><i class="bi bi-<?= e($cat['icon']) ?>"></i> <?= e($cat['label']) ?></h3>
        <div class="doc-grid">
            <?php foreach ($docs as $key => $d): ?>
            <div class="doc-card">
                <?php if (!empty($d['draft'])): ?><span class="doc-badge">Bozza</span><?php endif; ?>
                <div class="doc-ic"><i class="bi bi-<?= e($d['icon'] ?? 'file-earmark') ?>"></i></div>
                <h4 class="doc-t"><?= e($d['title']) ?></h4>
                <p class="doc-p"><?= e($d['description'] ?? '') ?></p>
                <div class="doc-acts">
                    <?php if (empty($d['_available'])): ?>
                        <span class="doc-soon"><i class="bi bi-hourglass-split"></i> In preparazione</span>
                    <?php else: ?>
                        <a href="<?= url($previewBase . '/' . $key . '/preview') ?>" target="_blank" rel="noopener" class="doc-btn"><i class="bi bi-eye"></i> Apri</a>
                        <?php if (!empty($d['downloadable'])): ?>
                        <a href="<?= url($previewBase . '/' . $key) ?>" target="_blank" rel="noopener" class="doc-btn doc-btn-ghost"><i class="bi bi-download"></i> Scarica</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; endif; ?>
</div>
