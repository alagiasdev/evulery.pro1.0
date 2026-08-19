<?php
/**
 * Card onboarding "Completa la configurazione" (solo tenant nuovi).
 * @var array|null $onboarding  stato calcolato in HomeController::buildOnboardingState()
 */
if (empty($onboarding)) {
    return;
}
$ob  = $onboarding;
$pct = $ob['total'] > 0 ? (int) round($ob['done'] / $ob['total'] * 100) : 0;
?>
<?php if (!empty($ob['collapsed'])): ?>
    <form method="POST" action="<?= url('dashboard/onboarding/expand') ?>" class="ob-min-form">
        <?= csrf_field() ?>
        <button type="submit" class="ob-min" aria-label="Riprendi la configurazione">
            <span class="ob-min-badge"><i class="bi bi-stars"></i></span>
            <span class="ob-min-txt">Completa la configurazione <b><?= (int) $ob['done'] ?> di <?= (int) $ob['total'] ?></b></span>
            <span class="ob-min-act">Riprendi <i class="bi bi-chevron-down"></i></span>
        </button>
    </form>
<?php else: ?>
    <section class="ob-card" aria-label="Completa la configurazione">
        <div class="ob-card-h">
            <div class="ob-card-tt"><span class="ob-card-badge"><i class="bi bi-stars"></i></span><h2>Completa la configurazione</h2></div>
            <span class="ob-card-cnt"><?= (int) $ob['done'] ?> di <?= (int) $ob['total'] ?> completati</span>
        </div>
        <div class="ob-card-bar"><i style="width:<?= $pct ?>%"></i></div>

        <div class="ob-card-list">
            <?php foreach ($ob['steps'] as $s): ?>
            <div class="ob-step<?= $s['done'] ? ' is-done' : '' ?>">
                <span class="ob-ck <?= $s['done'] ? 'done' : 'todo' ?>"><?php if ($s['done']): ?><i class="bi bi-check-lg"></i><?php endif; ?></span>
                <span class="ob-step-txt">
                    <span class="ob-step-lb"><?= e($s['label']) ?><?php if (!empty($s['optional'])): ?> <span class="ob-opt">opz.</span><?php endif; ?></span>
                    <span class="ob-step-hint"><?= e($s['hint']) ?></span>
                </span>
                <?php if (!$s['done']): ?><a class="ob-step-cta" href="<?= e($s['url']) ?>"><?= e($s['cta']) ?> <i class="bi bi-arrow-right"></i></a><?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($ob['share_url'])): ?>
        <div class="ob-share">
            <span class="ob-share-ic"><i class="bi bi-link-45deg"></i></span>
            <span class="ob-share-cap"><b>Condividi il link di prenotazione</b><span>Mettilo su Google, sito e social — e stampalo in sala.</span></span>
            <span class="ob-share-url"><?= e($ob['share_url']) ?></span>
            <button type="button" class="ob-share-btn solid" data-copy="<?= e($ob['share_url']) ?>"><i class="bi bi-clipboard"></i> Copia link</button>
            <?php if (!empty($ob['has_qr'])): ?><a class="ob-share-btn" href="<?= e($ob['qr_url']) ?>"><i class="bi bi-qr-code"></i> QR e Vetrina</a><?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="ob-card-foot">
            <form method="POST" action="<?= url('dashboard/onboarding/collapse') ?>"><?= csrf_field() ?><button type="submit" class="ob-link">Nascondi per ora</button></form>
            <form method="POST" action="<?= url('dashboard/onboarding/dismiss') ?>" data-confirm="Vuoi chiudere la guida alla configurazione? Non verrà più mostrata."><?= csrf_field() ?><button type="submit" class="ob-link ob-link-muted">Non mostrare più</button></form>
        </div>
    </section>
    <script nonce="<?= csp_nonce() ?>">
    (function () {
        document.querySelectorAll('.ob-share-btn[data-copy]').forEach(function (b) {
            b.addEventListener('click', function () {
                var txt = b.getAttribute('data-copy');
                if (!navigator.clipboard) { return; }
                navigator.clipboard.writeText(txt).then(function () {
                    var old = b.innerHTML;
                    b.innerHTML = '<i class="bi bi-check-lg"></i> Copiato!';
                    setTimeout(function () { b.innerHTML = old; }, 1800);
                });
            });
        });
    })();
    </script>
<?php endif; ?>
