<?php
/** @var bool $canUse @var array $tenant @var string $tab
 *  @var string $hubUrl @var string $bookingUrl @var string $menuUrl @var string $orderUrl */
?>
<?php include __DIR__ . '/_tabs.php'; ?>

<?php if (!$canUse): ?>
    <?php $lockedTitle = 'Generatore link tracciati'; $lockedDesc = 'Disponibile con i piani Professional ed Enterprise.'; include BASE_PATH . '/views/partials/service-locked.php'; ?>
<?php else: ?>

<div class="card" style="max-width:760px;padding:1.5rem;">
    <p style="font-size:.86rem;color:#6c757d;margin:0 0 1rem;">
        Scegli dove mandare il cliente, il canale e (facoltativo) il nome della campagna:
        ottieni il link tracciato da incollare nell'annuncio o nella bio. Le prenotazioni che arrivano da questo link
        compaiono in <a href="<?= url('dashboard/marketing') ?>" class="text-success text-decoration-none">Provenienza</a>.
    </p>

    <label class="form-label fw-semibold">Destinazione</label>
    <div class="mk-seg" id="mk-dest"
         data-hub="<?= e($hubUrl) ?>" data-book="<?= e($bookingUrl) ?>" data-menu="<?= e($menuUrl) ?>" data-order="<?= e($orderUrl) ?>">
        <span class="mk-segbtn on" data-dest="hub">Vetrina / Hub <span class="mk-rec">consigliata</span></span>
        <span class="mk-segbtn" data-dest="book">Prenota</span>
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
        <div class="mk-chan" data-src="gbp"           data-med="organic"  data-qr="1"><i class="bi bi-shop"></i>Google Business</div>
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
        <button type="button" class="btn btn-sm btn-success" id="mk-copy">Copia</button>
    </div>
    <div class="form-text">Se mandi tutto all'Hub, l'attribuzione vale per qualunque azione il cliente faccia (prenota, menù, ordina).</div>

    <div id="mk-qr-wrap" style="display:none;margin-top:1rem;">
        <label class="form-label fw-semibold">QR <span class="text-muted fw-normal">(per la stampa)</span></label>
        <div id="mk-qr" class="mk-qr"></div>
        <div><button type="button" class="btn btn-sm btn-outline-success mt-2" id="mk-qr-dl"><i class="bi bi-download"></i> Scarica QR</button></div>
    </div>
</div>

<style>
.mk-seg{display:flex;gap:8px;flex-wrap:wrap;}
.mk-segbtn{border:1.5px solid #d4dade;background:#fff;border-radius:9px;padding:8px 13px;font-weight:600;font-size:.82rem;cursor:pointer;}
.mk-segbtn.on{background:#e8f5ee;border-color:var(--brand,#00844A);color:var(--brand-d,#006b3c);}
.mk-rec{font-size:.62rem;background:#d6f0e2;color:var(--brand-d,#006b3c);border-radius:4px;padding:1px 5px;margin-left:4px;}
.mk-chans{display:grid;grid-template-columns:repeat(auto-fill,minmax(118px,1fr));gap:8px;}
.mk-chan{border:2px solid #eceff2;border-radius:10px;padding:10px 6px;text-align:center;cursor:pointer;font-size:.78rem;font-weight:600;}
.mk-chan.on{border-color:var(--brand,#00844A);background:#f6fbf8;}
.mk-chan i{display:block;font-size:1.3rem;margin-bottom:4px;color:var(--brand,#00844A);}
.mk-urlbox{display:flex;gap:8px;align-items:center;background:#f8fafb;border:1px solid #e4e8eb;border-radius:9px;padding:9px 11px;}
.mk-urlbox span{flex:1;font-family:ui-monospace,Menlo,monospace;font-size:.78rem;word-break:break-all;}
.mk-qr{width:170px;height:170px;border:1px solid #e4e8eb;border-radius:10px;display:flex;align-items:center;justify-content:center;background:#fff;}
.mk-qr img,.mk-qr canvas{width:150px;height:150px;}
</style>

<script nonce="<?= csp_nonce() ?>">
(function(){
    var dest = document.getElementById('mk-dest');
    var bases = { hub: dest.dataset.hub, book: dest.dataset.book, menu: dest.dataset.menu, order: dest.dataset.order };
    var urlEl = document.getElementById('mk-url');
    var qrWrap = document.getElementById('mk-qr-wrap');
    var qrBox = document.getElementById('mk-qr');
    var lastUrl = '';

    function slug(v){ return (v||'').toLowerCase().trim().replace(/\s+/g,'-').replace(/[^a-z0-9._-]/g,''); }

    function build(){
        var d = document.querySelector('#mk-dest .mk-segbtn.on').dataset.dest;
        var c = document.querySelector('.mk-chan.on');
        var src = c.dataset.src, med = c.dataset.med, isQr = c.dataset.qr === '1', isGen = src === '__generic';
        document.getElementById('mk-generic-wrap').style.display = isGen ? '' : 'none';
        if (isGen) { src = slug(document.getElementById('mk-gsrc').value) || 'altro'; }
        var camp = slug(document.getElementById('mk-camp').value);
        var u = bases[d] + '?utm_source=' + encodeURIComponent(src) + '&utm_medium=' + encodeURIComponent(med);
        if (camp) { u += '&utm_campaign=' + encodeURIComponent(camp); }
        urlEl.textContent = u;
        lastUrl = u;
        qrWrap.style.display = isQr ? '' : 'none';
        if (isQr) { renderQr(u); }
    }

    function loadQrLib(cb){
        if (window.QRCode) { cb(); return; }
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js';
        s.onload = cb;
        s.onerror = function(){ qrBox.innerHTML = '<span style="font-size:.75rem;color:#999;">QR non disponibile offline</span>'; };
        document.head.appendChild(s);
    }
    function renderQr(text){
        loadQrLib(function(){
            qrBox.innerHTML = '';
            new QRCode(qrBox, { text: text, width: 150, height: 150, colorDark: '#212529', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M });
        });
    }

    document.querySelectorAll('#mk-dest .mk-segbtn').forEach(function(b){ b.addEventListener('click', function(){ document.querySelectorAll('#mk-dest .mk-segbtn').forEach(function(x){x.classList.remove('on');}); b.classList.add('on'); build(); }); });
    document.querySelectorAll('.mk-chan').forEach(function(c){ c.addEventListener('click', function(){ document.querySelectorAll('.mk-chan').forEach(function(x){x.classList.remove('on');}); c.classList.add('on'); build(); }); });
    document.getElementById('mk-camp').addEventListener('input', build);
    document.getElementById('mk-gsrc').addEventListener('input', build);

    document.getElementById('mk-copy').addEventListener('click', function(){
        var b = this;
        navigator.clipboard.writeText(lastUrl).then(function(){ b.textContent = 'Copiato!'; setTimeout(function(){ b.textContent = 'Copia'; }, 1200); });
    });
    document.getElementById('mk-qr-dl').addEventListener('click', function(){
        var node = qrBox.querySelector('canvas, img');
        if (!node) return;
        var data = node.tagName === 'CANVAS' ? node.toDataURL('image/png') : node.src;
        var a = document.createElement('a'); a.href = data; a.download = 'qr-evulery.png'; a.click();
    });

    build();
})();
</script>

<?php endif; // canUse ?>
