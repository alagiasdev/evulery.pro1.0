<?php
/** @var bool $canUse @var array $tenant @var string $tab
 *  @var string $hubUrl @var string $bookingUrl @var string $menuUrl @var string $orderUrl
 *  @var array $saved @var array $destLabels */

use App\Services\AttributionService;

// chiavi gia' salvate (per avviso anti-duplicato lato client)
$savedKeys = [];
foreach ($saved as $s) {
    $savedKeys[] = $s['destination'] . '|' . $s['utm_source'] . '|' . ($s['utm_medium'] ?? '') . '|' . ($s['utm_campaign'] ?? '');
}
?>
<?php include __DIR__ . '/_tabs.php'; ?>

<?php if (!$canUse): ?>
    <?php $lockedTitle = 'Generatore link tracciati'; $lockedDesc = 'Disponibile con i piani Professional ed Enterprise.'; include BASE_PATH . '/views/partials/service-locked.php'; ?>
<?php else: ?>

<?php if (!$hubActive): ?>
<div class="alert alert-warning d-flex align-items-center gap-2" style="max-width:760px;font-size:.86rem;">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <div class="flex-grow-1">
        La <strong>Vetrina Digitale</strong> non è ancora attiva: i link verso la Vetrina mostrerebbero una pagina "non disponibile".
        Per ora usa <strong>Prenota</strong> come destinazione, oppure
        <a href="<?= e($hubConfigUrl) ?>" class="alert-link">attiva la Vetrina</a>.
    </div>
</div>
<?php endif; ?>

<div class="card" style="max-width:760px;padding:1.5rem;">
    <p style="font-size:.86rem;color:#6c757d;margin:0 0 1rem;">
        Scegli dove mandare il cliente, il canale e (facoltativo) il nome della campagna:
        ottieni il link tracciato da incollare nell'annuncio o nella bio. Le prenotazioni che arrivano da questo link
        compaiono in <a href="<?= url('dashboard/marketing') ?>" class="text-success text-decoration-none">Provenienza</a>.
    </p>

    <label class="form-label fw-semibold">Destinazione</label>
    <div class="mk-seg" id="mk-dest"
         data-hub="<?= e($hubUrl) ?>" data-book="<?= e($bookingUrl) ?>" data-menu="<?= e($menuUrl) ?>" data-order="<?= e($orderUrl) ?>">
        <span class="mk-segbtn<?= $hubActive ? ' on' : ' disabled' ?>" data-dest="hub"<?= $hubActive ? '' : ' title="Attiva prima la Vetrina"' ?>>Vetrina / Hub <?php if ($hubActive): ?><span class="mk-rec">consigliata</span><?php else: ?><i class="bi bi-lock-fill" style="font-size:.6rem;"></i><?php endif; ?></span>
        <span class="mk-segbtn<?= $hubActive ? '' : ' on' ?>" data-dest="book">Prenota</span>
        <span class="mk-segbtn" data-dest="menu">Menù</span>
        <span class="mk-segbtn" data-dest="order">Ordina</span>
    </div>

    <label class="form-label fw-semibold mt-3">Canale</label>
    <div class="mk-chans" id="mk-chans">
        <div class="mk-chan on" data-src="meta"      data-med="cpc"      data-qr="0"><i class="bi bi-meta"></i>Meta/Facebook</div>
        <div class="mk-chan" data-src="instagram"    data-med="bio"      data-qr="0"><i class="bi bi-instagram"></i>Instagram bio</div>
        <div class="mk-chan" data-src="google"       data-med="cpc"      data-qr="0"><i class="bi bi-google"></i>Google Ads</div>
        <div class="mk-chan" data-src="tiktok"        data-med="social"   data-qr="0"><i class="bi bi-tiktok"></i>TikTok</div>
        <div class="mk-chan" data-src="flyer"         data-med="qr"       data-qr="1"><i class="bi bi-qr-code"></i>Volantino QR</div>
        <div class="mk-chan" data-src="gbp"           data-med="organic"  data-qr="0"><i class="bi bi-shop"></i>Google Business</div>
        <div class="mk-chan" data-src="newsletter"    data-med="email"    data-qr="0"><i class="bi bi-envelope"></i>Newsletter</div>
        <div class="mk-chan" data-src="__generic"     data-med="referral" data-qr="0"><i class="bi bi-three-dots"></i>Generico / Altro</div>
    </div>

    <div id="mk-generic-wrap" style="display:none;">
        <label class="form-label fw-semibold mt-3">Nome sorgente <span class="text-muted fw-normal">(es. tripadvisor, influencer-mario)</span></label>
        <input type="text" id="mk-gsrc" class="form-control" placeholder="tripadvisor" maxlength="40">
    </div>

    <label class="form-label fw-semibold mt-3">Nome campagna <span class="text-muted fw-normal">(facoltativo)</span></label>
    <input type="text" id="mk-camp" class="form-control" placeholder="estate-aperitivo" maxlength="60">

    <label class="form-label fw-semibold mt-4">Link da usare</label>
    <div class="mk-urlbox">
        <span id="mk-url"></span>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="mk-copy">Copia</button>
    </div>

    <!-- Avviso anti-duplicato (soft, client) -->
    <div id="mk-dup" class="alert alert-warning py-2 px-3 mt-2" style="display:none;font-size:.82rem;">
        <i class="bi bi-exclamation-triangle-fill"></i> Hai già salvato una campagna identica: la trovi nella lista qui sotto.
    </div>

    <!-- Form salvataggio: i campi nascosti sono popolati dal generatore -->
    <form method="POST" action="<?= url('dashboard/marketing/links/save') ?>" class="mt-3">
        <?= csrf_field() ?>
        <input type="hidden" name="destination" id="mk-f-dest">
        <input type="hidden" name="utm_source" id="mk-f-src">
        <input type="hidden" name="utm_medium" id="mk-f-med">
        <input type="hidden" name="utm_campaign" id="mk-f-camp">
        <button type="submit" class="btn btn-success" id="mk-save"><i class="bi bi-bookmark-plus"></i> Salva campagna</button>
        <span class="text-muted" style="font-size:.78rem;margin-left:.5rem;">La salvi una volta, poi la ritrovi qui sotto pronta da copiare.</span>
    </form>

    <div id="mk-qr-wrap" style="display:none;margin-top:1rem;">
        <label class="form-label fw-semibold">QR <span class="text-muted fw-normal">(per la stampa)</span></label>
        <div id="mk-qr" class="mk-qr"></div>
        <div><button type="button" class="btn btn-sm btn-outline-success mt-2" id="mk-qr-dl"><i class="bi bi-download"></i> Scarica QR</button></div>
    </div>
</div>

<!-- LISTA CAMPAGNE SALVATE -->
<div class="card" style="max-width:760px;padding:1.5rem;">
    <h2 style="font-size:1rem;margin:0 0 .8rem;display:flex;align-items:center;gap:.45rem;">
        <i class="bi bi-collection" style="color:var(--brand);"></i> Le tue campagne
        <span class="text-muted fw-normal" style="font-size:.82rem;">(<?= count($saved) ?>)</span>
    </h2>

    <?php if (empty($saved)): ?>
    <div style="padding:1.4rem;text-align:center;color:#9aa1a9;font-size:.86rem;">
        Nessuna campagna salvata. Crea un link qui sopra e premi <b>Salva campagna</b>.
    </div>
    <?php else: ?>
    <?php foreach ($saved as $s): ?>
    <?php
        $color = AttributionService::color($s['channel']);
        $chLabel = AttributionService::label($s['channel']);
        $name = $chLabel . ($s['utm_campaign'] ? ' · ' . $s['utm_campaign'] : '');
        $destLabel = $destLabels[$s['destination']] ?? $s['destination'];
    ?>
    <div class="mk-srow">
        <span class="mk-sdot" style="background:<?= e($color) ?>;"></span>
        <div class="mk-sinfo">
            <div class="mk-sname"><?= e($name) ?> <span class="mk-dest-badge"><?= e(strtoupper($destLabel)) ?></span></div>
            <div class="mk-smeta"><?= e($s['url']) ?></div>
        </div>
        <div class="mk-perf"><div class="pn"><?= (int)$s['bookings'] ?></div><div class="pl">pren.</div></div>
        <div class="mk-sact">
            <button type="button" class="mk-iconbtn mk-rowcopy" data-url="<?= e($s['url']) ?>" title="Copia link"><i class="bi bi-clipboard"></i></button>
            <?php if ($s['channel'] === 'flyer'): ?>
            <button type="button" class="mk-iconbtn mk-rowqr" data-url="<?= e($s['url']) ?>" title="Mostra QR"><i class="bi bi-qr-code"></i></button>
            <?php endif; ?>
            <form method="POST" action="<?= url('dashboard/marketing/links/' . (int)$s['id'] . '/delete') ?>" data-confirm="Eliminare questa campagna salvata? (le prenotazioni già attribuite restano nel report)" style="display:inline;">
                <?= csrf_field() ?>
                <button type="submit" class="mk-iconbtn del" title="Elimina"><i class="bi bi-trash3"></i></button>
            </form>
        </div>
        <div class="mk-row-qr" style="display:none;"></div>
    </div>
    <?php endforeach; ?>
    <p class="text-muted" style="font-size:.78rem;margin:.7rem 0 0;">Il numero di prenotazioni è lo stesso della scheda Provenienza (escluse annullate e no-show).</p>
    <?php endif; ?>
</div>

<style>
.mk-seg{display:flex;gap:8px;flex-wrap:wrap;}
.mk-segbtn{border:1.5px solid #d4dade;background:#fff;border-radius:9px;padding:8px 13px;font-weight:600;font-size:.82rem;cursor:pointer;}
.mk-segbtn.on{background:#e8f5ee;border-color:var(--brand,#00844A);color:var(--brand-d,#006b3c);}
.mk-segbtn.disabled{opacity:.55;cursor:not-allowed;background:#f5f5f5;color:#9aa3aa;}
.mk-rec{font-size:.62rem;background:#d6f0e2;color:var(--brand-d,#006b3c);border-radius:4px;padding:1px 5px;margin-left:4px;}
.mk-chans{display:grid;grid-template-columns:repeat(auto-fill,minmax(118px,1fr));gap:8px;}
.mk-chan{border:2px solid #eceff2;border-radius:10px;padding:10px 6px;text-align:center;cursor:pointer;font-size:.78rem;font-weight:600;}
.mk-chan.on{border-color:var(--brand,#00844A);background:#f6fbf8;}
.mk-chan i{display:block;font-size:1.3rem;margin-bottom:4px;color:var(--brand,#00844A);}
.mk-urlbox{display:flex;gap:8px;align-items:center;background:#f8fafb;border:1px solid #e4e8eb;border-radius:9px;padding:9px 11px;}
.mk-urlbox span{flex:1;font-family:ui-monospace,Menlo,monospace;font-size:.78rem;word-break:break-all;}
.mk-qr{width:170px;height:170px;border:1px solid #e4e8eb;border-radius:10px;display:flex;align-items:center;justify-content:center;background:#fff;}
.mk-qr img,.mk-qr canvas{width:150px;height:150px;}
/* lista salvate */
.mk-srow{display:flex;align-items:center;gap:12px;padding:11px 12px;border:1px solid #eceff2;border-radius:11px;margin-bottom:8px;flex-wrap:wrap;}
.mk-sdot{width:10px;height:10px;border-radius:50%;flex-shrink:0;}
.mk-sinfo{flex:1;min-width:0;}
.mk-sname{font-weight:700;font-size:.9rem;}
.mk-smeta{font-size:.73rem;color:#9aa3aa;font-family:ui-monospace,Menlo,monospace;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.mk-perf{flex-shrink:0;text-align:center;min-width:60px;}
.mk-perf .pn{font-size:1.05rem;font-weight:800;line-height:1;color:var(--brand-d,#006b3c);}
.mk-perf .pl{font-size:.66rem;color:#9aa3aa;}
.mk-sact{display:flex;gap:6px;flex-shrink:0;}
.mk-sact form{margin:0;}
.mk-iconbtn{border:1.5px solid #e4e8eb;background:#fff;border-radius:7px;width:30px;height:30px;display:grid;place-items:center;cursor:pointer;color:#555;font-size:.85rem;}
.mk-iconbtn:hover{background:#f6fbf8;border-color:var(--brand,#00844A);color:var(--brand-d,#006b3c);}
.mk-iconbtn.del:hover{background:#fdecea;border-color:#f5c2c0;color:#b3261e;}
.mk-dest-badge{font-size:.62rem;background:#eef2ff;color:#3730a3;border-radius:5px;padding:1px 6px;font-weight:700;margin-left:6px;}
.mk-row-qr{width:100%;}
.mk-row-qr.show{display:block !important;padding-top:8px;}
.mk-row-qr canvas,.mk-row-qr img{width:140px;height:140px;border:1px solid #e4e8eb;border-radius:8px;}
</style>

<script nonce="<?= csp_nonce() ?>">
(function(){
    var dest = document.getElementById('mk-dest');
    var bases = { hub: dest.dataset.hub, book: dest.dataset.book, menu: dest.dataset.menu, order: dest.dataset.order };
    var destApi = { hub: 'hub', book: 'booking', menu: 'menu', order: 'order' };
    var urlEl = document.getElementById('mk-url');
    var qrWrap = document.getElementById('mk-qr-wrap');
    var qrBox = document.getElementById('mk-qr');
    var dupBox = document.getElementById('mk-dup');
    var savedKeys = <?= json_encode($savedKeys) ?>;
    var lastUrl = '';

    function slug(v){ return (v||'').toLowerCase().trim().replace(/\s+/g,'-').replace(/[^a-z0-9._-]/g,''); }

    function loadQrLib(cb){
        if (window.QRCode) { cb(); return; }
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js';
        s.onload = cb;
        s.onerror = function(){ if (qrBox) qrBox.innerHTML = '<span style="font-size:.75rem;color:#999;">QR non disponibile offline</span>'; };
        document.head.appendChild(s);
    }
    function renderQr(box, text, size){
        loadQrLib(function(){ box.innerHTML=''; new QRCode(box, { text:text, width:size, height:size, colorDark:'#212529', colorLight:'#ffffff', correctLevel: QRCode.CorrectLevel.M }); });
    }
    // QR ad alta risoluzione per la STAMPA (1024px, correzione errori H), reso off-screen
    function genHiResQr(text, cb){
        loadQrLib(function(){
            var h = document.createElement('div'); h.style.cssText = 'position:absolute;left:-99999px;top:-99999px;';
            document.body.appendChild(h);
            new QRCode(h, { text:text, width:1024, height:1024, colorDark:'#000000', colorLight:'#ffffff', correctLevel: QRCode.CorrectLevel.H });
            setTimeout(function(){ var n = h.querySelector('canvas, img'); var d = n ? (n.tagName==='CANVAS' ? n.toDataURL('image/png') : n.src) : ''; h.remove(); cb(d); }, 60);
        });
    }
    function downloadDataUrl(d, name){ if(!d) return; var a=document.createElement('a'); a.href=d; a.download=name||'qr-evulery.png'; a.click(); }

    function build(){
        var d = document.querySelector('#mk-dest .mk-segbtn.on').dataset.dest;
        var c = document.querySelector('.mk-chan.on');
        var src = c.dataset.src, med = c.dataset.med, isQr = c.dataset.qr === '1', isGen = src === '__generic';
        document.getElementById('mk-generic-wrap').style.display = isGen ? '' : 'none';
        if (isGen) { src = slug(document.getElementById('mk-gsrc').value) || 'altro'; }
        var camp = slug(document.getElementById('mk-camp').value);
        var u = bases[d] + '?utm_source=' + encodeURIComponent(src) + '&utm_medium=' + encodeURIComponent(med);
        if (camp) { u += '&utm_campaign=' + encodeURIComponent(camp); }
        urlEl.textContent = u; lastUrl = u;

        // popola i campi del form di salvataggio
        document.getElementById('mk-f-dest').value = destApi[d];
        document.getElementById('mk-f-src').value = src;
        document.getElementById('mk-f-med').value = med;
        document.getElementById('mk-f-camp').value = camp;

        // avviso duplicato (chiave: dest|src|med|camp)
        var key = destApi[d] + '|' + src + '|' + med + '|' + camp;
        dupBox.style.display = savedKeys.indexOf(key) !== -1 ? 'block' : 'none';

        qrWrap.style.display = isQr ? '' : 'none';
        if (isQr) { renderQr(qrBox, u, 150); }
    }

    document.querySelectorAll('#mk-dest .mk-segbtn').forEach(function(b){ b.addEventListener('click', function(){ if (b.classList.contains('disabled')) return; document.querySelectorAll('#mk-dest .mk-segbtn').forEach(function(x){x.classList.remove('on');}); b.classList.add('on'); build(); }); });
    document.querySelectorAll('.mk-chan').forEach(function(c){ c.addEventListener('click', function(){ document.querySelectorAll('.mk-chan').forEach(function(x){x.classList.remove('on');}); c.classList.add('on'); build(); }); });
    document.getElementById('mk-camp').addEventListener('input', build);
    document.getElementById('mk-gsrc').addEventListener('input', build);

    document.getElementById('mk-copy').addEventListener('click', function(){ var b=this; navigator.clipboard.writeText(lastUrl).then(function(){ b.textContent='Copiato!'; setTimeout(function(){ b.textContent='Copia'; },1200); }); });
    document.getElementById('mk-qr-dl').addEventListener('click', function(){
        var b=this, orig=b.innerHTML; b.disabled=true; b.innerHTML='<i class="bi bi-hourglass-split"></i> Generazione...';
        genHiResQr(lastUrl, function(d){ b.disabled=false; b.innerHTML=orig; downloadDataUrl(d, 'qr-evulery.png'); });
    });

    // azioni lista salvate
    document.querySelectorAll('.mk-rowcopy').forEach(function(b){ b.addEventListener('click', function(){ navigator.clipboard.writeText(b.dataset.url).then(function(){ var i=b.querySelector('i'); i.className='bi bi-check2'; setTimeout(function(){ i.className='bi bi-clipboard'; },1200); }); }); });
    document.querySelectorAll('.mk-rowqr').forEach(function(b){ b.addEventListener('click', function(){
        var box = b.closest('.mk-srow').querySelector('.mk-row-qr');
        if (box.classList.contains('show')) { box.classList.remove('show'); box.innerHTML=''; return; }
        box.classList.add('show');
        var prev = document.createElement('div'); box.appendChild(prev); renderQr(prev, b.dataset.url, 140);
        var dl = document.createElement('button'); dl.type='button'; dl.className='btn btn-sm btn-outline-success mt-2'; dl.innerHTML='<i class="bi bi-download"></i> Scarica PNG (stampa)';
        dl.addEventListener('click', function(){ var o=dl.innerHTML; dl.disabled=true; dl.innerHTML='<i class="bi bi-hourglass-split"></i> ...'; genHiResQr(b.dataset.url, function(d){ dl.disabled=false; dl.innerHTML=o; downloadDataUrl(d,'qr-evulery.png'); }); });
        box.appendChild(dl);
    }); });

    build();
})();
</script>

<?php endif; // canUse ?>
