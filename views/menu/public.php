<!DOCTYPE html>
<html lang="<?= e($lang ?? 'it') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#00844A">
    <meta name="format-detection" content="telephone=no">
    <title><?= e($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&display=swap" rel="stylesheet">
    <link href="<?= asset('css/menu.css') ?>" rel="stylesheet">
</head>
<body>

<?php
    $hasHero = !empty($heroImage);
    $heroClass = $hasHero ? 'dm-hero--photo' : 'dm-hero--plain';
    $featuredLabel = (isset($featuredLabel) && trim((string)$featuredLabel) !== '') ? $featuredLabel : 'Piatti del giorno';
    $lang = $lang ?? 'it';
    $menuLanguages = $menuLanguages ?? ['it'];
    $langMeta = $langMeta ?? [];
    $ui = $ui ?? [
        'menu_eyebrow' => 'Menù', 'cta_eyebrow' => 'Prenotazioni',
        'browse' => 'Sfoglia il menu', 'search' => 'Cerca nel menu...',
        'dish_1' => 'piatto', 'dish_n' => 'piatti', 'wine_1' => 'etichetta', 'wine_n' => 'etichette',
        'glass' => 'Calice', 'bottle' => 'Bottiglia',
        'wine_note' => 'Tutti i vini contengono solfiti. Carta soggetta a variazioni di annata e disponibilità.',
        'allergen_title' => 'Informazioni sugli Allergeni',
        'allergen_legal' => 'Ai sensi del Reg. UE 1169/2011. Per ulteriori informazioni rivolgiti al personale di sala.',
        'cta_title' => 'Ti abbiamo fatto venire fame?', 'cta_sub' => 'Prenota per la prossima cena o il prossimo pranzo',
        'cta_btn' => 'Prenota un tavolo', 'empty' => 'Il menu non è ancora disponibile.',
    ];
    // Conteggio localizzato (piatti vs etichette/vini)
    $countLabel = function (int $n, bool $isWine = false) use ($ui) {
        $w = $isWine ? ($n === 1 ? $ui['wine_1'] : $ui['wine_n']) : ($n === 1 ? $ui['dish_1'] : $ui['dish_n']);
        return $n . ' ' . $w;
    };
?>

<!-- ===== HERO ===== -->
<div class="dm-hero <?= $heroClass ?>">
    <?php if ($hasHero): ?>
    <div class="dm-hero-bg" style="background-image:url('<?= e($heroImage) ?>');"></div>
    <div class="dm-hero-gradient"></div>
    <?php endif; ?>
    <?php if (count($menuLanguages) > 1): ?>
    <div class="dm-lang-switch">
        <?php foreach ($menuLanguages as $lc): ?>
        <a href="?lang=<?= e($lc) ?>" class="dm-lang-opt<?= $lc === $lang ? ' active' : '' ?>" hreflang="<?= e($lc) ?>"><?= e($langMeta[$lc]['short'] ?? strtoupper($lc)) ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div class="dm-hero-content">
        <div class="dm-hero-over"><?= e($ui['menu_eyebrow'] ?? 'Menù') ?></div>
        <?php if ($tenantLogo): ?>
        <img src="<?= e($tenantLogo) ?>" alt="<?= e($tenantName) ?>" class="dm-hero-logo dm-hero-logo--solo">
        <?php endif; ?>
        <h1 class="dm-hero-name<?= $tenantLogo ? ' dm-sr-only' : '' ?>"><?= e($tenantName) ?></h1>
        <?php if ($tagline): ?>
        <p class="dm-hero-tagline"><?= e($tagline) ?></p>
        <?php endif; ?>
        <div class="dm-hero-info">
            <?php if ($address): ?>
            <a class="dm-hero-chip" href="https://www.google.com/maps/search/?api=1&query=<?= rawurlencode($address) ?>" target="_blank" rel="noopener"><i class="bi bi-geo-alt-fill"></i> <?= e($address) ?></a>
            <?php endif; ?>
            <?php if ($phone): ?>
            <?php
                $telHref = preg_replace('/[^\d+]/', '', $phone);
                $phoneDisplay = preg_replace('/^(\+39)\s*/', '$1 ', trim($phone)); // separa il prefisso italiano
            ?>
            <a class="dm-hero-chip" href="tel:<?= e($telHref) ?>"><i class="bi bi-telephone-fill"></i> <?= e($phoneDisplay) ?></a>
            <?php endif; ?>
            <?php if ($openingHours): ?>
            <span class="dm-hero-chip"><i class="bi bi-clock-fill"></i> <?= e($openingHours) ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ===== CATEGORY LANDING ===== -->
<?php if (!empty($categories) || !empty($specials)): ?>
<div class="dm-landing">
    <div class="dm-landing-title"><?= e($ui['browse']) ?></div>
    <div class="dm-landing-grid">
        <?php if (!empty($specials)): ?>
        <a class="dm-landing-card dm-landing-card--special" data-target="specials">
            <div class="dm-landing-icon"><i class="bi bi-star-fill"></i></div>
            <span class="dm-landing-label"><?= e($featuredLabel) ?></span>
            <span class="dm-landing-count"><?= e($countLabel(count($specials))) ?></span>
        </a>
        <?php endif; ?>
        <?php foreach ($categories as $cat): ?>
        <?php
            $landingCount = count($cat['items']);
            foreach ($cat['subcategories'] ?? [] as $sub) { $landingCount += count($sub['items']); }
        ?>
        <a class="dm-landing-card" data-target="cat-<?= (int)$cat['id'] ?>">
            <div class="dm-landing-icon"><?= menu_icon($cat['icon'] ?? 'bi-list') ?></div>
            <span class="dm-landing-label"><?= e($cat['name']) ?></span>
            <span class="dm-landing-count"><?= e($countLabel($landingCount, !empty($cat['is_wine']))) ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ===== STICKY NAV ===== -->
<?php if (!empty($categories) || !empty($specials)): ?>
<nav class="dm-cat-nav" id="cat-nav">
    <div class="dm-cat-nav-inner">
        <?php if (!empty($specials)): ?>
        <a href="#specials" class="dm-cat-nav-item"><i class="bi bi-star-fill" style="font-size:.68rem;margin-right:.15rem;color:#F59E0B;"></i> <?= e($featuredLabel) ?></a>
        <?php endif; ?>
        <?php foreach ($categories as $cat): ?>
        <a href="#cat-<?= (int)$cat['id'] ?>" class="dm-cat-nav-item"><?= e($cat['name']) ?></a>
        <?php endforeach; ?>
    </div>
</nav>
<?php endif; ?>

<div class="dm-main">

    <!-- SEARCH -->
    <div class="dm-search-wrap">
        <input type="text" class="dm-search" placeholder="<?= e($ui['search']) ?>" id="dm-search-input">
    </div>

    <?php if (empty($categories) && empty($specials)): ?>
    <!-- EMPTY STATE -->
    <div class="dm-empty">
        <i class="bi bi-book" style="font-size:2.5rem; color:#dee2e6;"></i>
        <p><?= e($ui['empty']) ?></p>
    </div>
    <?php else: ?>

    <!-- ===== IN EVIDENZA: proposta dello chef + sezione numerata ===== -->
    <?php if (!empty($specials)): ?>
    <?php $feat = $specials[0]; $restSpecials = array_slice($specials, 1); ?>
    <div class="dm-feature dm-section-anchor<?= $feat['image_url'] ? '' : ' dm-feature--nophoto' ?> dm-searchable" id="specials" data-name="<?= e(mb_strtolower($feat['name'])) ?>">
        <?php if ($feat['image_url']): ?>
        <div class="dm-feature-ph" style="background-image:url('<?= e($feat['image_url']) ?>')"></div>
        <?php endif; ?>
        <div class="dm-feature-bd">
            <div class="dm-feature-tag"><?= e($featuredLabel) ?></div>
            <div class="dm-feature-name"><?= e($feat['name']) ?></div>
            <?php if ($feat['description']): ?>
            <div class="dm-feature-desc"><?= e($feat['description']) ?></div>
            <?php endif; ?>
            <div class="dm-feature-price"><?= number_format((float)$feat['price'], 2, ',', '.') ?> &euro;</div>
            <?php if (!empty($feat['allergens'])): ?>
            <div class="dm-item-meta"><div class="dm-allergen-tags">
                <?php foreach ($feat['allergens'] as $aKey): ?>
                <span class="dm-allergen-tag dm-at-<?= e($aKey) ?>"><span class="dm-allergen-tag-dot"></span><?= e($allergens[$aKey] ?? $aKey) ?></span>
                <?php endforeach; ?>
            </div></div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($restSpecials)): ?>
    <div class="dm-section dm-section-anchor" id="specials-list">
        <div class="dm-section-header">
            <h2 class="dm-section-title"><?= e($featuredLabel) ?></h2>
            <span class="dm-section-count"><?= e($countLabel(count($restSpecials))) ?></span>
        </div>
        <div class="dm-items-grid">
            <?php foreach ($restSpecials as $item): ?>
            <?php include __DIR__ . '/_dish_row.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <!-- ===== CATEGORY SECTIONS ===== -->
    <?php foreach ($categories as $cat): ?>
    <?php
        $allCatItems = $cat['items'];
        foreach ($cat['subcategories'] ?? [] as $sub) {
            $allCatItems = array_merge($allCatItems, $sub['items']);
        }
        $totalCount = count($allCatItems);
        $isWine = !empty($cat['is_wine']);
    ?>
    <div class="dm-section dm-section-anchor<?= $isWine ? ' dm-section--wine' : '' ?>" id="cat-<?= (int)$cat['id'] ?>">
        <div class="dm-section-header">
            <div class="dm-section-icon"><?= menu_icon($cat['icon'] ?? 'bi-list') ?></div>
            <h2 class="dm-section-title"><?= e($cat['name']) ?></h2>
            <span class="dm-section-count"><?= e($countLabel($totalCount, $isWine)) ?></span>
        </div>
        <?php if (!empty($cat['description'])): ?>
        <p class="dm-section-desc"><?= e($cat['description']) ?></p>
        <?php endif; ?>

        <?php // Items directly in parent category ?>
        <?php if (!empty($cat['items'])): ?>
        <?php if ($isWine): ?>
        <div class="dm-wine-list">
            <?php foreach ($cat['items'] as $item): ?>
            <?php include __DIR__ . '/_wine_row.php'; ?>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="dm-items-grid">
            <?php foreach ($cat['items'] as $item): ?>
            <?php include __DIR__ . '/_dish_row.php'; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php // Subcategory sections ?>
        <?php foreach ($cat['subcategories'] ?? [] as $sub): ?>
        <div class="dm-subsection">
            <div class="dm-subsection-header">
                <?= menu_icon($sub['icon'] ?? 'bi-list') ?>
                <span><?= e($sub['name']) ?></span>
            </div>
            <?php if ($isWine): ?>
            <div class="dm-wine-list">
                <?php foreach ($sub['items'] as $item): ?>
                <?php include __DIR__ . '/_wine_row.php'; ?>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="dm-items-grid">
                <?php foreach ($sub['items'] as $item): ?>
                <?php include __DIR__ . '/_dish_row.php'; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php if ($isWine): ?>
        <div class="dm-wine-note"><?= e($ui['wine_note']) ?></div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <!-- ===== ALLERGEN LEGEND ===== -->
    <div class="dm-legend-section">
        <button class="dm-legend-toggle" aria-expanded="true" id="legend-toggle" type="button">
            <span class="dm-legend-toggle-left">
                <i class="bi bi-info-circle-fill"></i>
                <span class="dm-legend-toggle-title"><?= e($ui['allergen_title']) ?></span>
            </span>
            <i class="bi bi-chevron-down dm-legend-toggle-arrow"></i>
        </button>
        <div class="dm-legend-body" id="legend-body">
            <p class="dm-legend-note"><?= e($ui['allergen_legal']) ?></p>
            <div class="dm-legend-grid">
                <?php foreach ($allergens as $key => $label): ?>
                <div class="dm-legend-item">
                    <span class="dm-legend-tag dm-at-<?= e($key) ?>"><span class="dm-allergen-tag-dot"></span><?= e($label) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ===== CTA FOOTER ===== -->
    <div class="dm-cta">
        <?php if ($hasHero): ?>
        <div class="dm-cta-bg" style="background-image:url('<?= e($heroImage) ?>');"></div>
        <?php else: ?>
        <div class="dm-cta-bg dm-cta-bg--plain"></div>
        <?php endif; ?>
        <div class="dm-cta-inner">
            <div class="dm-cta-icon"><i class="bi bi-calendar-check"></i></div>
            <div class="dm-cta-over"><?= e($ui['cta_eyebrow'] ?? 'Prenotazioni') ?></div>
            <h3 class="dm-cta-title"><?= e($ui['cta_title']) ?></h3>
            <p class="dm-cta-sub"><?= e($ui['cta_sub']) ?></p>
            <a href="<?= url($slug) ?>" class="dm-cta-btn"><i class="bi bi-calendar-check me-1"></i> <?= e($ui['cta_btn']) ?></a>
        </div>
    </div>

    <?php endif; ?>

    <div class="dm-footer">
        &copy; <?= date('Y') ?> Evulery &middot; by alagias. - Soluzioni per il web
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
(function() {
    var catNav = document.getElementById('cat-nav');
    var landing = document.querySelector('.dm-landing');
    if (!catNav) return;

    // Calculate threshold once: bottom of landing section
    var navThreshold = landing
        ? landing.offsetTop + landing.offsetHeight
        : 200;

    function updateNavVisibility() {
        catNav.classList.toggle('dm-nav-visible', window.scrollY > navThreshold - 10);
    }
    window.addEventListener('scroll', updateNavVisibility, { passive: true });
    updateNavVisibility();

    // Landing card click -> smooth scroll
    document.querySelectorAll('.dm-landing-card').forEach(function(card) {
        card.addEventListener('click', function() {
            var target = document.getElementById(this.dataset.target);
            if (target) {
                var offset = 70;
                window.scrollTo({ top: target.getBoundingClientRect().top + window.scrollY - offset, behavior: 'smooth' });
            }
        });
    });

    // Sticky nav click -> smooth scroll
    document.querySelectorAll('.dm-cat-nav-item').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            var target = document.getElementById(this.getAttribute('href').substring(1));
            if (target) {
                var navH = catNav.offsetHeight;
                window.scrollTo({ top: target.getBoundingClientRect().top + window.scrollY - navH - 8, behavior: 'smooth' });
            }
            document.querySelectorAll('.dm-cat-nav-item').forEach(function(l) { l.classList.remove('active'); });
            this.classList.add('active');
            centerNavItem(this);
        });
    });

    // Porta il pill attivo al centro della barra (scroll orizzontale)
    function centerNavItem(el) {
        var navRect = catNav.getBoundingClientRect();
        var elRect = el.getBoundingClientRect();
        var delta = (elRect.left + elRect.width / 2) - (navRect.left + navRect.width / 2);
        if (Math.abs(delta) > 4) {
            catNav.scrollTo({ left: catNav.scrollLeft + delta, behavior: 'smooth' });
        }
    }

    // Scroll spy + auto-scroll barra sul pill attivo
    var lastCurrent = '';
    window.addEventListener('scroll', function() {
        var navH = catNav.offsetHeight + 30;
        var sections = document.querySelectorAll('.dm-section-anchor');
        var current = '';
        sections.forEach(function(s) {
            if (s.getBoundingClientRect().top <= navH + 50) current = s.id;
        });
        // La sezione "del giorno" (lista) condivide il pill della card in evidenza
        if (current === 'specials-list') current = 'specials';
        if (current === lastCurrent) return;
        lastCurrent = current;
        var activeEl = null;
        document.querySelectorAll('.dm-cat-nav-item').forEach(function(l) {
            var on = l.getAttribute('href') === '#' + current;
            l.classList.toggle('active', on);
            if (on) activeEl = l;
        });
        if (activeEl) centerNavItem(activeEl);
    }, { passive: true });

    // Legend toggle
    var legendToggle = document.getElementById('legend-toggle');
    if (legendToggle) {
        legendToggle.addEventListener('click', function() {
            var body = document.getElementById('legend-body');
            var expanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', String(!expanded));
            body.style.display = expanded ? 'none' : '';
        });
    }

    // Search filter
    var searchInput = document.getElementById('dm-search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var q = this.value.trim().toLowerCase();
            document.querySelectorAll('.dm-searchable').forEach(function(el) {
                var name = el.dataset.name || '';
                el.style.display = (!q || name.indexOf(q) !== -1) ? '' : 'none';
            });
            // Show/hide section headers based on visible items
            document.querySelectorAll('.dm-section').forEach(function(sec) {
                var visible = sec.querySelectorAll('.dm-searchable:not([style*="display: none"])');
                sec.style.display = (!q || visible.length > 0) ? '' : 'none';
            });
            // Show/hide specials section
            var specialsSection = document.querySelector('.dm-specials');
            if (specialsSection) {
                var visibleSpecials = specialsSection.querySelectorAll('.dm-searchable:not([style*="display: none"])');
                specialsSection.style.display = (!q || visibleSpecials.length > 0) ? '' : 'none';
            }
        });
    }
})();
</script>

</body>
</html>
