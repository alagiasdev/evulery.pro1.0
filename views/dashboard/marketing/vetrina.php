<?php
/** @var bool $canUse @var array $analytics @var string $from @var string $to
 *  @var string $rangeKey @var array $ranges @var string $tab @var string $hubConfigUrl */
?>
<?php include __DIR__ . '/_tabs.php'; ?>

<?php if (!$canUse): ?>
    <?php $lockedTitle = 'Statistiche Vetrina'; $lockedDesc = 'Disponibile con i piani Professional ed Enterprise.'; include BASE_PATH . '/views/partials/service-locked.php'; ?>
<?php else: ?>

<?php $k = $analytics['kpis']; $hasData = ($k['visits'] > 0 || $k['clicks'] > 0); ?>

<div class="mkv-toolbar">
    <div class="mkv-pills">
        <?php foreach ($ranges as $days => $label): ?>
        <a href="<?= url('dashboard/marketing/vetrina') ?>?days=<?= $days ?>" class="mkv-pill <?= $rangeKey === (string)$days ? 'on' : '' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>
    <a href="<?= e($hubConfigUrl) ?>" class="mkv-cfg"><i class="bi bi-gear"></i> Configura Vetrina</a>
</div>

<!-- KPI -->
<div class="mkv-kpis">
    <div class="mkv-kpi"><div class="n"><?= (int)$k['visits'] ?></div><div class="l">Visite Vetrina</div></div>
    <div class="mkv-kpi"><div class="n"><?= (int)$k['clicks'] ?></div><div class="l">Click pulsanti</div><div class="t"><?= number_format((float)$k['cpv'], 1, ',', '') ?> per visita</div></div>
    <div class="mkv-kpi"><div class="n"><?= (int)$k['bookings'] ?></div><div class="l">Prenotazioni</div></div>
    <div class="mkv-kpi"><div class="n"><?= (int)$k['channels'] ?></div><div class="l">Canali attivi</div></div>
</div>

<?php if (!$hasData): ?>
    <div class="card" style="padding:2.2rem;text-align:center;color:#9aa1a9;">
        <i class="bi bi-bar-chart" style="font-size:1.8rem;opacity:.5;"></i>
        <p style="margin:.6rem 0 0;">Nessuna visita o click sulla Vetrina nel periodo selezionato.</p>
        <p style="font-size:.82rem;margin:.2rem 0 0;">Condividi il link della Vetrina (usa <a href="<?= url('dashboard/marketing/links') ?>" class="text-success text-decoration-none">Genera link</a>) per iniziare a misurare.</p>
    </div>
<?php else: ?>
<div class="mkv-grid">
    <!-- navigazione: canali → campagne -->
    <div class="card mkv-nav">
        <h2 class="mkv-h">Canali e campagne</h2>
        <div id="mkv-tree">
            <?php foreach ($analytics['tree'] as $node): $hasKids = !empty($node['children']); ?>
            <div class="mkv-seg<?= $node['id'] === 'all' ? ' sel' : '' ?>" data-id="<?= e($node['id']) ?>">
                <span class="mkv-caret"><?= $hasKids ? '<i class="bi bi-chevron-right"></i>' : '' ?></span>
                <span class="mkv-dot" style="background:<?= e($node['color']) ?>;"></span>
                <span class="mkv-nm"><?= e($node['label']) ?></span>
                <span class="mkv-mini"><?= (int)$node['visits'] ?> v · <b><?= (int)$node['bookings'] ?></b> p</span>
            </div>
            <?php foreach ($node['children'] as $ch): ?>
            <div class="mkv-seg child hidden" data-parent="<?= e($node['id']) ?>" data-id="<?= e($ch['id']) ?>">
                <span class="mkv-caret"></span>
                <span class="mkv-dot" style="background:<?= e($node['color']) ?>;opacity:.55;"></span>
                <span class="mkv-nm"><?= e($ch['label']) ?> <span class="mkv-badge">CAMPAGNA</span></span>
                <span class="mkv-mini"><?= (int)$ch['visits'] ?> v · <b><?= (int)$ch['bookings'] ?></b> p</span>
            </div>
            <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
        <p class="mkv-hint"><i class="bi bi-info-circle"></i> Clicca un canale per i dati aggregati, o espandilo (›) per la singola campagna.</p>
    </div>

    <!-- dettaglio scope selezionato -->
    <div class="card mkv-detail">
        <div class="mkv-dwrap">
            <div class="mkv-dhead">
                <span id="d-dot" class="mkv-ddot"></span>
                <div><div class="mkv-dt" id="d-title">—</div><div class="mkv-dsub" id="d-sub"></div></div>
            </div>
            <div class="mkv-funnel">
                <div class="mkv-fstep hub"><div class="n" id="f-visits">0</div><div class="l">Visite</div></div>
                <span class="mkv-farrow">→</span>
                <div class="mkv-fstep"><div class="n" id="f-clicks">0</div><div class="l">Click</div><span class="rate" id="f-cpv">0 /visita</span></div>
                <span class="mkv-farrow">→</span>
                <div class="mkv-fstep"><div class="n" id="f-book">0</div><div class="l">Prenotazioni</div><span class="rate" id="f-conv">0% conv.</span></div>
            </div>
            <table class="mkv-table">
                <thead><tr><th>Pulsante</th><th class="num">Click</th><th style="width:120px;">Quota</th><th class="num">Conversioni</th></tr></thead>
                <tbody id="mkv-btnbody"></tbody>
            </table>
            <p class="mkv-foot">Le prenotazioni sono quelle passate dalla Vetrina (attribuite al canale selezionato). Periodo per data di visita/click.</p>
        </div>
    </div>
</div>
<?php endif; ?>

<?php endif; // canUse ?>

<style>
.mkv-toolbar{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:14px}
.mkv-pills{display:flex;gap:8px;flex-wrap:wrap}
.mkv-pill{border:1.5px solid #d4dade;background:#fff;border-radius:9px;padding:6px 12px;font-weight:600;font-size:.8rem;text-decoration:none;color:#1a1d23}
.mkv-pill.on{background:#e8f5ee;border-color:var(--brand,#00844A);color:var(--brand-d,#006b3c)}
.mkv-cfg{border:1.5px solid #d4dade;background:#fff;border-radius:9px;padding:6px 12px;font-size:.8rem;font-weight:600;text-decoration:none;color:#1a1d23}
.mkv-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:16px}
@media(max-width:760px){.mkv-kpis{grid-template-columns:repeat(2,1fr)}}
.mkv-kpi{background:#fff;border:1px solid #eceff2;border-radius:12px;padding:12px 14px}
.mkv-kpi .n{font-size:1.45rem;font-weight:800;line-height:1}
.mkv-kpi .l{font-size:.72rem;color:#6c757d;margin-top:3px}
.mkv-kpi .t{font-size:.68rem;font-weight:700;color:var(--brand-d,#006b3c);margin-top:5px}
.mkv-grid{display:grid;grid-template-columns:330px 1fr;gap:16px;align-items:start}
@media(max-width:860px){.mkv-grid{grid-template-columns:1fr}}
.mkv-h{font-size:.78rem;text-transform:uppercase;letter-spacing:.4px;color:#6c757d;margin:0;padding:14px 16px;border-bottom:1px solid #eceff2}
.mkv-seg{display:flex;align-items:center;gap:10px;padding:11px 14px;border-bottom:1px solid #f4f6f8;cursor:pointer}
.mkv-seg:hover{background:#fbfdfc}
.mkv-seg.sel{background:#f0faf5;box-shadow:inset 3px 0 0 var(--brand,#00844A)}
.mkv-seg.open .mkv-caret{transform:rotate(90deg)}
.mkv-caret{width:14px;color:#c0c7cd;font-size:.7rem;flex-shrink:0;transition:transform .15s}
.mkv-dot{width:9px;height:9px;border-radius:50%;flex-shrink:0}
.mkv-nm{flex:1;font-weight:600;font-size:.86rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mkv-mini{font-size:.72rem;color:#6c757d;white-space:nowrap}
.mkv-seg.child{padding-left:36px;background:#fcfdfe}
.mkv-seg.child .mkv-nm{font-weight:500;font-size:.82rem}
.mkv-seg.hidden{display:none}
.mkv-badge{font-size:.58rem;font-weight:800;background:#eef2ff;color:#3730a3;border-radius:4px;padding:1px 5px;margin-left:5px}
.mkv-hint{font-size:.74rem;color:#9aa1a9;padding:10px 16px 14px;margin:0}
.mkv-dwrap{padding:16px}
.mkv-dhead{display:flex;align-items:center;gap:10px;margin-bottom:14px}
.mkv-ddot{width:12px;height:12px;border-radius:50%;background:#6b7280}
.mkv-dt{font-size:1.05rem;font-weight:800}
.mkv-dsub{font-size:.76rem;color:#6c757d}
.mkv-funnel{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px}
.mkv-fstep{flex:1;min-width:110px;border:1px solid #eceff2;border-radius:11px;padding:12px}
.mkv-fstep .n{font-size:1.4rem;font-weight:800;line-height:1}
.mkv-fstep .l{font-size:.74rem;color:#6c757d;margin-top:2px}
.mkv-fstep .rate{font-size:.68rem;font-weight:700;color:var(--brand-d,#006b3c);background:#e8f5ee;border-radius:5px;padding:1px 6px;display:inline-block;margin-top:6px}
.mkv-fstep.hub{border-color:#ddd6fe;background:#f8f7ff}.mkv-fstep.hub .n{color:#8b5cf6}
.mkv-farrow{align-self:center;color:#c9d2d9}
.mkv-table{width:100%;border-collapse:collapse;font-size:.86rem}
.mkv-table th{text-align:left;font-size:.68rem;text-transform:uppercase;letter-spacing:.3px;color:#6c757d;padding:8px 10px;border-bottom:2px solid #eceff2}
.mkv-table th.num,.mkv-table td.num{text-align:right;font-weight:700}
.mkv-table td{padding:9px 10px;border-bottom:1px solid #f4f6f8;vertical-align:middle}
.mkv-bicon{width:28px;height:28px;border-radius:7px;background:#f0faf5;color:var(--brand-d,#006b3c);display:inline-grid;place-items:center;margin-right:8px;font-size:.9rem;vertical-align:middle}
.mkv-bt{font-size:.58rem;font-weight:800;border-radius:4px;padding:1px 5px;margin-left:5px}
.mkv-bt.preset{background:#eef2ff;color:#3730a3}.mkv-bt.custom{background:#fef3c7;color:#92400e}.mkv-bt.hero{background:#dcfce7;color:#166534}.mkv-bt.social{background:#fce7f3;color:#9d174d}
.mkv-bar{height:7px;border-radius:5px;background:#eef0f2;overflow:hidden}
.mkv-bar>i{display:block;height:100%;border-radius:5px;background:var(--brand,#00844A)}
.mkv-conv{font-size:.76rem;color:#6c757d}.mkv-conv b{color:var(--brand-d,#006b3c)}
.mkv-foot{font-size:.74rem;color:#9aa1a9;margin:12px 0 0}
</style>

<?php if ($canUse && $hasData): ?>
<script nonce="<?= csp_nonce() ?>">
(function(){
    var SCOPES = <?= json_encode($analytics['scopes'], JSON_UNESCAPED_UNICODE) ?>;
    var BUTTONS = <?= json_encode($analytics['buttons'], JSON_UNESCAPED_UNICODE) ?>;
    var sel = 'all';
    function setText(id,v){ var e=document.getElementById(id); if(e) e.textContent=v; }
    function esc(s){ return (s==null?'':String(s)).replace(/[&<>"]/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c];}); }

    function renderDetail(id){
        var d = SCOPES[id]; if(!d) return;
        document.getElementById('d-dot').style.background = d.color;
        setText('d-title', d.title); setText('d-sub', d.sub + ' · periodo selezionato');
        var cpv = (typeof d.cpv === 'number' ? d.cpv : 0).toFixed(1).replace('.', ',');
        setText('f-visits', d.visits); setText('f-clicks', d.clicks); setText('f-cpv', cpv + ' /visita');
        setText('f-book', d.book); setText('f-conv', d.conv + '% conv.');
        var counts = d.buttons || {};
        var max = 1;
        BUTTONS.forEach(function(b){ var n = counts[b.ref]||0; if(n>max) max=n; });
        var html = '';
        BUTTONS.forEach(function(b){
            var n = counts[b.ref] || 0;
            var t = (b.type==='hero'?'hero':(b.type==='custom'?'custom':(b.type==='social'?'social':'preset')));
            var tlabel = t==='hero'?'PRINCIPALE':(t==='custom'?'CUSTOM':(t==='social'?'SOCIAL':'PRESET'));
            var conv = (b.ref==='booking') ? '<span class="mkv-conv"><b>'+d.vp+'</b> pren.</span>' : '—';
            var iconBg = t==='custom' ? 'style="background:#fef3c7;color:#92400e"' : '';
            html += '<tr><td><span class="mkv-bicon" '+iconBg+'><i class="bi '+esc(b.icon)+'"></i></span>'+esc(b.label)+' <span class="mkv-bt '+t+'">'+tlabel+'</span></td>'
                 + '<td class="num">'+n+'</td>'
                 + '<td><div class="mkv-bar"><i style="width:'+Math.round(n/max*100)+'%'+(t==='custom'?';background:#f59e0b':'')+'"></i></div></td>'
                 + '<td class="num">'+conv+'</td></tr>';
        });
        document.getElementById('mkv-btnbody').innerHTML = html;
    }

    var tree = document.getElementById('mkv-tree');
    tree.querySelectorAll('.mkv-seg').forEach(function(row){
        row.addEventListener('click', function(e){
            var id = row.dataset.id;
            var hasKids = !!tree.querySelector('.mkv-seg[data-parent="'+id+'"]');
            if (hasKids && e.target.closest('.mkv-caret')) {
                row.classList.toggle('open');
                tree.querySelectorAll('.mkv-seg[data-parent="'+id+'"]').forEach(function(c){ c.classList.toggle('hidden'); });
                return;
            }
            sel = id;
            tree.querySelectorAll('.mkv-seg').forEach(function(s){ s.classList.remove('sel'); });
            row.classList.add('sel');
            renderDetail(id);
        });
    });
    renderDetail('all');
})();
</script>
<?php endif; ?>
