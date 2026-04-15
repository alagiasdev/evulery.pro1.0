<?php
// $sections comes from controller
$categories = [
    'primi'         => 'Primi passi',
    'configurazione'=> 'Configurazione',
    'operativita'   => 'Operativit&agrave;',
    'avanzati'      => 'Servizi avanzati',
    'supporto'      => 'Supporto',
];

// Group sections by category preserving config order
$byCategory = [];
foreach ($sections as $slug => $data) {
    $cat = $data['category'] ?? 'supporto';
    $byCategory[$cat][$slug] = $data;
}
?>

<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.15rem;"><i class="bi bi-book" style="color:var(--brand);"></i> Guida Evulery</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1.25rem;">Impara a gestire il tuo ristorante con Evulery. Tutto quello che serve per iniziare e diventare autonomo.</p>

<!-- Hero con ricerca -->
<div class="hg-hero">
    <div class="hg-hero-title">Come possiamo aiutarti?</div>
    <div class="hg-hero-sub">Cerca tra i nostri articoli o sfoglia le categorie qui sotto</div>
    <div class="hg-search-wrap">
        <i class="bi bi-search hg-search-icon"></i>
        <input type="text" class="hg-search" id="hgSearch" placeholder="Cerca un argomento (es. orari, widget, caparra...)">
    </div>
</div>

<!-- Categories with card grid -->
<?php foreach ($categories as $catKey => $catLabel):
    if (empty($byCategory[$catKey])) continue;
?>
<div class="hg-category" data-hg-group="<?= $catKey ?>">
    <div class="hg-category-header">
        <span><?= $catLabel ?></span>
        <div class="hg-category-line"></div>
    </div>
    <div class="hg-grid">
        <?php foreach ($byCategory[$catKey] as $slug => $data): ?>
        <a href="<?= url('dashboard/help/' . $slug) ?>" class="hg-card" data-hg-card="<?= e($data['keywords'] ?? '') ?>">
            <div class="hg-card-icon" style="background:<?= e($data['color'] ?? '#00844A') ?>;">
                <i class="bi <?= e($data['icon'] ?? 'bi-file-text') ?>"></i>
            </div>
            <h3 class="hg-card-title"><?= $data['title'] ?></h3>
            <p class="hg-card-desc"><?= $data['subtitle'] ?? '' ?></p>
            <div class="hg-card-meta">
                <span class="hg-card-count"><i class="bi bi-file-text"></i> <?= e($data['count_label'] ?? '1 articolo') ?></span>
                <span class="hg-card-arrow">Leggi <i class="bi bi-arrow-right"></i></span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<!-- No results -->
<div class="hg-no-results" id="hgNoResults">
    <i class="bi bi-search"></i>
    Nessun risultato per la tua ricerca. Prova con altre parole chiave.
</div>

<!-- Supporto box -->
<div class="hg-support">
    <div class="hg-support-icon"><i class="bi bi-headset"></i></div>
    <div class="hg-support-text">
        <h4>Non hai trovato quello che cercavi?</h4>
        <p>Il nostro team &egrave; a tua disposizione per assistenza personalizzata.</p>
    </div>
    <?php $supportEmail = env('SUPPORT_EMAIL', 'info@evulery.it'); ?>
    <a href="mailto:<?= e($supportEmail) ?>" class="hg-support-btn">
        <i class="bi bi-envelope me-1"></i> Contattaci
    </a>
</div>

<script nonce="<?= csp_nonce() ?>">
(function() {
    var search = document.getElementById('hgSearch');
    var cards = document.querySelectorAll('.hg-card');
    var categories = document.querySelectorAll('.hg-category');
    var noResults = document.getElementById('hgNoResults');

    if (!search) return;

    search.addEventListener('input', function() {
        var q = this.value.trim().toLowerCase();
        var visibleCount = 0;

        cards.forEach(function(card) {
            var haystack = (card.dataset.hgCard || '') + ' ' + card.textContent.toLowerCase();
            var match = q === '' || haystack.indexOf(q) !== -1;
            card.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });

        // Hide empty categories
        categories.forEach(function(cat) {
            var hasVisible = Array.from(cat.querySelectorAll('.hg-card')).some(function(c) {
                return c.style.display !== 'none';
            });
            cat.style.display = hasVisible ? '' : 'none';
        });

        if (noResults) noResults.style.display = (visibleCount === 0) ? 'block' : 'none';
    });
})();
</script>
