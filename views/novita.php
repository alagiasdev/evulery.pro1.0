<?php
/** @var array $releases  già filtrate per pubblico + ordinate DESC */
/** @var array $categories */
/** @var string $context   'owner' | 'reseller' */
$MESI = [1=>'gennaio',2=>'febbraio',3=>'marzo',4=>'aprile',5=>'maggio',6=>'giugno',7=>'luglio',8=>'agosto',9=>'settembre',10=>'ottobre',11=>'novembre',12=>'dicembre'];
$heroSub = ($context ?? 'owner') === 'reseller'
    ? 'Le ultime funzioni rilasciate — pronte da mostrare ai tuoi clienti e prospect.'
    : 'Le ultime funzioni che abbiamo rilasciato per far lavorare meglio il tuo ristorante.';
?>
<style nonce="<?= csp_nonce() ?>">
.rel-page{ max-width:820px; }
.rel-hero{ position:relative; overflow:hidden; border-radius:16px; padding:2rem 1.8rem 1.8rem; margin-bottom:1.6rem;
    background:linear-gradient(135deg,#00844A 0%,#046b3e 100%); color:#fff; box-shadow:0 10px 30px rgba(0,132,74,.18); }
.rel-hero::after{ content:"🎉"; position:absolute; right:-.2rem; top:-.7rem; font-size:6rem; opacity:.12; transform:rotate(12deg); }
.rel-hero .kick{ text-transform:uppercase; letter-spacing:.12em; font-size:.72rem; font-weight:700; opacity:.85; }
.rel-hero h1{ font-size:1.9rem; font-weight:800; margin:.3rem 0 .35rem; }
.rel-hero p{ margin:0; font-size:.95rem; opacity:.92; max-width:52ch; }
.rel-feed{ display:flex; flex-direction:column; gap:1rem; }
.rel-card{ display:flex; gap:1rem; background:#fff; border:1px solid #e4e9eb; border-radius:14px; padding:1.1rem 1.2rem;
    box-shadow:0 1px 2px rgba(16,40,30,.04); transition:transform .12s, box-shadow .12s; }
.rel-card:hover{ transform:translateY(-2px); box-shadow:0 12px 32px rgba(16,40,30,.08); }
.rel-card.is-new{ border-color:#bfe3ce; box-shadow:0 0 0 3px rgba(0,132,74,.07); }
.rel-ic{ width:44px; height:44px; border-radius:12px; flex:none; display:grid; place-items:center; font-size:1.3rem; }
.rel-ic.green{ background:#e6f4ed; } .rel-ic.blue{ background:#e7f0fb; } .rel-ic.amber{ background:#fdf0d9; } .rel-ic.violet{ background:#efe9fb; }
.rel-body{ flex:1; min-width:0; }
.rel-meta{ display:flex; align-items:center; gap:.55rem; flex-wrap:wrap; margin-bottom:.35rem; }
.rel-chip{ font-size:.7rem; font-weight:700; padding:.16rem .55rem; border-radius:100px; }
.rel-chip.green{ background:#e6f4ed; color:#046b3e; } .rel-chip.blue{ background:#e7f0fb; color:#1565C0; }
.rel-chip.amber{ background:#fdf0d9; color:#b46e00; } .rel-chip.violet{ background:#efe9fb; color:#6d4bb3; }
.rel-date{ font-size:.76rem; color:#93a0a7; }
.rel-newbadge{ font-size:.64rem; font-weight:800; letter-spacing:.04em; text-transform:uppercase; color:#fff; background:#00844A; padding:.14rem .5rem; border-radius:100px; }
.rel-card h3{ margin:.1rem 0 .25rem; font-size:1.05rem; font-weight:700; color:#16232b; }
.rel-card p{ margin:0; font-size:.9rem; color:#5d6b72; line-height:1.5; }
.rel-empty{ text-align:center; padding:2.5rem 1rem; color:#98a2a8; }
.rel-empty i{ font-size:2rem; color:#c9d2d7; display:block; margin-bottom:.4rem; }
@media (max-width:560px){ .rel-hero h1{ font-size:1.55rem; } .rel-card{ padding:1rem; } }
</style>

<div class="rel-page">
    <div class="rel-hero">
        <div class="kick">Evulery cresce con te</div>
        <h1>Novità</h1>
        <p><?= e($heroSub) ?></p>
    </div>

    <div class="rel-feed">
        <?php if (empty($releases)): ?>
            <div class="rel-empty">
                <i class="bi bi-stars"></i>
                Nessuna novità al momento. Torna presto!
            </div>
        <?php else: ?>
            <?php foreach ($releases as $r): ?>
                <?php
                    $cat = $categories[$r['category']] ?? ['label' => 'Novità', 'color' => 'green', 'icon' => 'stars', 'emoji' => '✨'];
                    $ts = strtotime($r['date']);
                    $isNew = $ts >= strtotime('-10 days');
                    $dateLabel = (int)date('j', $ts) . ' ' . ($MESI[(int)date('n', $ts)] ?? '') . ' ' . date('Y', $ts);
                ?>
                <div class="rel-card <?= $isNew ? 'is-new' : '' ?>">
                    <div class="rel-ic <?= e($cat['color']) ?>"><?= $cat['emoji'] ?? '✨' ?></div>
                    <div class="rel-body">
                        <div class="rel-meta">
                            <span class="rel-chip <?= e($cat['color']) ?>"><?= e($cat['label']) ?></span>
                            <?php if ($isNew): ?><span class="rel-newbadge">Nuovo</span><?php endif; ?>
                            <span class="rel-date"><?= e($dateLabel) ?></span>
                        </div>
                        <h3><?= e($r['title']) ?></h3>
                        <p><?= e($r['desc']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
