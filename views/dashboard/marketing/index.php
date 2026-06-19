<?php
/** @var bool $canUse @var array $channels @var array $totals @var string $from @var string $to
 *  @var string $rangeKey @var array $ranges @var array $tenant @var string $tab */
?>
<?php include __DIR__ . '/_tabs.php'; ?>

<?php if (!$canUse): ?>
    <?php $lockedTitle = 'Marketing & Provenienza'; $lockedDesc = 'Disponibile con i piani Professional ed Enterprise. La raccolta dati è comunque attiva: appena fai l’upgrade trovi qui lo storico.'; include BASE_PATH . '/views/partials/service-locked.php'; ?>
<?php else: ?>

<?php $maxCovers = 0; foreach ($channels as $c) { if ($c['covers'] > $maxCovers) $maxCovers = $c['covers']; } ?>

<!-- Filtro periodo -->
<div class="mk-filters">
    <?php foreach ($ranges as $days => $label): ?>
    <a href="<?= url('dashboard/marketing') ?>?days=<?= $days ?>" class="mk-pill <?= $rangeKey === (string)$days ? 'on' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
    <span class="mk-pill <?= $rangeKey === 'custom' ? 'on' : '' ?>" id="mk-custom-toggle"><i class="bi bi-calendar3"></i> Personalizzato</span>
    <span class="text-muted" style="font-size:.74rem;width:100%;margin-top:-2px;">Periodo = quando è stata effettuata la prenotazione (non la data del tavolo).</span>
    <form method="GET" action="<?= url('dashboard/marketing') ?>" class="mk-custom-form" id="mk-custom-form" style="<?= $rangeKey === 'custom' ? '' : 'display:none;' ?>">
        <input type="date" name="from" value="<?= e($from) ?>" class="form-control form-control-sm" style="width:auto;">
        <input type="date" name="to" value="<?= e($to) ?>" class="form-control form-control-sm" style="width:auto;">
        <button class="btn btn-sm btn-success" type="submit">Applica</button>
    </form>
</div>

<!-- KPI -->
<div class="mk-kpis">
    <div class="mk-kpi"><div class="n"><?= (int)$totals['n'] ?></div><div class="l">Prenotazioni</div></div>
    <div class="mk-kpi"><div class="n"><?= (int)$totals['covers'] ?></div><div class="l">Coperti</div></div>
    <div class="mk-kpi"><div class="n"><?= (int)$totals['channels'] ?></div><div class="l">Canali attivi</div></div>
    <div class="mk-kpi"><div class="n"><?= (int)$totals['tracked_pct'] ?>%</div><div class="l">Tracciate</div></div>
</div>

<?php if (empty($channels) || $totals['n'] === 0): ?>
    <div class="card" style="padding:2.2rem;text-align:center;color:#9aa1a9;">
        <i class="bi bi-bar-chart" style="font-size:1.8rem;opacity:.5;"></i>
        <p style="margin:.6rem 0 0;">Nessuna prenotazione nel periodo selezionato.</p>
        <p style="font-size:.82rem;margin:.2rem 0 0;">Usa <a href="<?= url('dashboard/marketing/links') ?>" class="text-success fw-semibold text-decoration-none">Genera link</a> per taggare le tue campagne e iniziare a misurare.</p>
    </div>
<?php else: ?>
<div class="card" style="padding:0;overflow:hidden;">
    <table class="mk-table">
        <thead>
            <tr><th>Canale</th><th class="num">Pren.</th><th class="num">Coperti</th><th style="width:170px;">Quota coperti</th></tr>
        </thead>
        <tbody>
            <?php foreach ($channels as $i => $c): ?>
            <?php $hasCampaigns = !empty($c['campaigns']); $pct = $maxCovers > 0 ? round($c['covers'] / $maxCovers * 100) : 0; ?>
            <tr class="<?= $hasCampaigns ? 'mk-exp' : '' ?>" <?= $hasCampaigns ? 'data-exp="ch' . $i . '"' : '' ?>>
                <td>
                    <span class="mk-dot" style="background:<?= e($c['color']) ?>;"></span>
                    <?= e($c['label']) ?>
                    <?php if ($hasCampaigns): ?><i class="bi bi-chevron-down mk-caret"></i><?php endif; ?>
                </td>
                <td class="num"><?= (int)$c['n'] ?></td>
                <td class="num"><?= (int)$c['covers'] ?></td>
                <td><div class="mk-bar"><i style="width:<?= $pct ?>%;background:<?= e($c['color']) ?>;"></i></div></td>
            </tr>
            <?php foreach ($c['campaigns'] as $camp): ?>
            <tr class="mk-sub" data-sub="ch<?= $i ?>" style="display:none;">
                <td>↳ <b><?= e($camp['name']) ?></b></td>
                <td class="num"><?= (int)$camp['n'] ?></td>
                <td class="num"><?= (int)$camp['covers'] ?></td>
                <td class="mk-roi">utm_campaign=<?= e($camp['name']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<p style="font-size:.78rem;color:#9aa1a9;margin:.6rem 2px 0;">
    "Diretto / sconosciuto" include telefono, walk-in e link non taggati. Tagga i link con <a href="<?= url('dashboard/marketing/links') ?>" class="text-success text-decoration-none">Genera link</a> per ridurlo.
    <?php if ((int)$totals['via_hub'] > 0): ?> · <?= (int)$totals['via_hub'] ?> passate dalla Vetrina/Hub.<?php endif; ?>
</p>
<?php endif; ?>

<?php endif; // canUse ?>

<style>
.mk-filters{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:14px;}
.mk-pill{border:1.5px solid #d4dade;background:#fff;border-radius:9px;padding:6px 12px;font-weight:600;font-size:.8rem;cursor:pointer;text-decoration:none;color:#1a1d23;}
.mk-pill.on{background:#e8f5ee;border-color:var(--brand,#00844A);color:var(--brand-d,#006b3c);}
.mk-custom-form{display:flex;gap:6px;align-items:center;}
.mk-kpis{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;}
.mk-kpi{flex:1;min-width:120px;border:1px solid #eceff2;border-radius:11px;padding:12px 14px;background:#fff;}
.mk-kpi .n{font-size:1.4rem;font-weight:800;line-height:1;}
.mk-kpi .l{font-size:.72rem;color:#6c757d;margin-top:3px;}
.mk-table{width:100%;border-collapse:collapse;font-size:.88rem;}
.mk-table th{text-align:left;font-size:.7rem;text-transform:uppercase;letter-spacing:.3px;color:#6c757d;padding:10px 14px;border-bottom:2px solid #eceff2;}
.mk-table th.num,.mk-table td.num{text-align:right;font-weight:700;}
.mk-table td{padding:11px 14px;border-bottom:1px solid #f4f6f8;vertical-align:middle;}
.mk-exp{cursor:pointer;}.mk-exp:hover{background:#fbfdfc;}
.mk-dot{display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:8px;vertical-align:middle;}
.mk-caret{font-size:.6rem;color:#bbb;margin-left:4px;}
.mk-bar{height:8px;border-radius:5px;background:#eef0f2;overflow:hidden;}
.mk-bar>i{display:block;height:100%;border-radius:5px;}
.mk-sub{background:#fafbfc;font-size:.82rem;}.mk-sub td{padding-left:30px;color:#555;}
.mk-roi{font-family:ui-monospace,Menlo,monospace;font-size:.74rem;color:#9aa3aa;}
</style>

<?php if ($canUse): ?>
<script nonce="<?= csp_nonce() ?>">
(function(){
    document.querySelectorAll('.mk-exp').forEach(function(tr){
        tr.addEventListener('click', function(){
            document.querySelectorAll('tr[data-sub="'+tr.dataset.exp+'"]').forEach(function(s){
                s.style.display = s.style.display==='none' ? 'table-row' : 'none';
            });
        });
    });
    var t = document.getElementById('mk-custom-toggle'), f = document.getElementById('mk-custom-form');
    if (t && f) t.addEventListener('click', function(){ f.style.display = f.style.display==='none' ? 'flex' : 'none'; });
})();
</script>
<?php endif; ?>
