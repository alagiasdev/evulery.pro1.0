<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#00844A">
    <title><?= e($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    <link href="<?= asset('css/menu.css') ?>" rel="stylesheet">
</head>
<body>

<?php
    $hasHero = !empty($heroImage);
    $heroClass = $hasHero ? 'dm-hero--photo' : 'dm-hero--plain';
?>

<!-- ===== HERO ===== -->
<div class="dm-hero <?= $heroClass ?>">
    <?php if ($hasHero): ?>
    <div class="dm-hero-bg" style="background-image:url('<?= e($heroImage) ?>');"></div>
    <div class="dm-hero-gradient"></div>
    <?php endif; ?>
    <div class="dm-hero-content">
        <?php if ($tenantLogo): ?>
        <img src="<?= e($tenantLogo) ?>" alt="<?= e($tenantName) ?>" class="dm-hero-logo">
        <?php endif; ?>
        <h1 class="dm-hero-name"><?= e($tenantName) ?></h1>
        <?php if ($tagline): ?>
        <p class="dm-hero-tagline"><?= e($tagline) ?></p>
        <?php endif; ?>
        <div class="dm-hero-info">
            <?php if ($address): ?>
            <span class="dm-hero-chip"><i class="bi bi-geo-alt-fill"></i> <?= e($address) ?></span>
            <?php endif; ?>
            <?php if ($phone): ?>
            <span class="dm-hero-chip"><i class="bi bi-telephone-fill"></i> <?= e($phone) ?></span>
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
    <div class="dm-landing-title">Sfoglia il menu</div>
    <div class="dm-landing-grid">
        <?php if (!empty($specials)): ?>
        <a class="dm-landing-card dm-landing-card--special" data-target="specials">
            <div class="dm-landing-icon"><i class="bi bi-star-fill"></i></div>
            <span class="dm-landing-label">Del Giorno</span>
            <span class="dm-landing-count"><?= count($specials) ?> piatt<?= count($specials) === 1 ? 'o' : 'i' ?></span>
        </a>
        <?php endif; ?>
        <?php foreach ($categories as $cat): ?>
        <?php
            $landingCount = count($cat['items']);
            foreach ($cat['subcategories'] ?? [] as $sub) { $landingCount += count($sub['items']); }
        ?>
        <a class="dm-landing-card" data-target="cat-<?= (int)$cat['id'] ?>">
            <div class="dm-landing-icon"><i class="bi <?= e($cat['icon'] ?? 'bi-list') ?>"></i></div>
            <span class="dm-landing-label"><?= e($cat['name']) ?></span>
            <span class="dm-landing-count"><?= $landingCount ?> piatt<?= $landingCount === 1 ? 'o' : 'i' ?></span>
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
        <a href="#specials" class="dm-cat-nav-item"><i class="bi bi-star-fill" style="font-size:.68rem;margin-right:.15rem;color:#F59E0B;"></i> Del giorno</a>
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
        <input type="text" class="dm-search" placeholder="Cerca nel menu..." id="dm-search-input">
    </div>

    <?php if (empty($categories) && empty($specials)): ?>
    <!-- EMPTY STATE -->
    <div class="dm-empty">
        <i class="bi bi-book" style="font-size:2.5rem; color:#dee2e6;"></i>
        <p>Il menu non &egrave; ancora disponibile.</p>
    </div>
    <?php else: ?>

    <!-- ===== DAILY SPECIALS ===== -->
    <?php if (!empty($specials)): ?>
    <div class="dm-specials dm-section-anchor" id="specials">
        <div class="dm-specials-badge"><i class="bi bi-star-fill"></i> Piatti del giorno</div>

        <?php foreach ($specials as $special): ?>
        <div class="dm-special-card dm-searchable" data-name="<?= e(mb_strtolower($special['name'])) ?>">
            <div class="dm-special-inner">
                <?php if ($special['image_url']): ?>
                <img src="<?= e($special['image_url']) ?>" alt="" class="dm-special-img" loading="lazy">
                <?php endif; ?>
                <div class="dm-special-body">
                    <div class="dm-special-label"><i class="bi bi-star-fill"></i> Speciale del giorno</div>
                    <div class="dm-special-name"><?= e($special['name']) ?></div>
                    <?php if ($special['description']): ?>
                    <div class="dm-special-desc"><?= e($special['description']) ?></div>
                    <?php endif; ?>
                    <div class="dm-special-footer">
                        <span class="dm-special-price"><?= number_format((float)$special['price'], 2, ',', '.') ?> &euro;</span>
                        <?php if (!empty($special['allergens'])): ?>
                        <span class="dm-special-allergens">
                            <?php foreach ($special['allergens'] as $aKey): ?>
                            <span class="dm-allergen-tag dm-at-<?= e($aKey) ?>"><span class="dm-allergen-tag-dot"></span><?= e($allergens[$aKey] ?? $aKey) ?></span>
                            <?php endforeach; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ===== CATEGORY SECTIONS ===== -->
    <?php foreach ($categories as $cat): ?>
    <?php
        $allCatItems = $cat['items'];
        foreach ($cat['subcategories'] ?? [] as $sub) {
            $allCatItems = array_merge($allCatItems, $sub['items']);
        }
        $totalCount = count($allCatItems);
    ?>
    <div class="dm-section dm-section-anchor" id="cat-<?= (int)$cat['id'] ?>">
        <div class="dm-section-header">
            <div class="dm-section-icon"><i class="bi <?= e($cat['icon'] ?? 'bi-list') ?>"></i></div>
            <h2 class="dm-section-title"><?= e($cat['name']) ?></h2>
            <span class="dm-section-count"><?= $totalCount ?> piatt<?= $totalCount === 1 ? 'o' : 'i' ?></span>
        </div>
        <?php if (!empty($cat['description'])): ?>
        <p class="dm-section-desc"><?= e($cat['description']) ?></p>
        <?php endif; ?>

        <?php // Items directly in parent category ?>
        <?php if (!empty($cat['items'])): ?>
        <div class="dm-items-grid">
            <?php foreach ($cat['items'] as $item): ?>
            <div class="dm-item <?= $item['image_url'] ? '' : 'dm-item--text-only' ?> dm-searchable" data-name="<?= e(mb_strtolower($item['name'])) ?>">
                <?php if ($item['image_url']): ?>
                <div class="dm-item-img-wrap">
                    <img src="<?= e($item['image_url']) ?>" alt="" class="dm-item-img" loading="lazy">
                </div>
                <?php endif; ?>
                <div class="dm-item-content">
                    <div class="dm-item-top">
                        <span class="dm-item-name"><?= e($item['name']) ?></span>
                        <span class="dm-item-price"><?= number_format((float)$item['price'], 2, ',', '.') ?> &euro;</span>
                    </div>
                    <?php if ($item['description']): ?>
                    <div class="dm-item-desc"><?= e($item['description']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($item['allergens'])): ?>
                    <div class="dm-item-meta">
                        <div class="dm-allergen-tags">
                            <?php foreach ($item['allergens'] as $aKey): ?>
                            <span class="dm-allergen-tag dm-at-<?= e($aKey) ?>"><span class="dm-allergen-tag-dot"></span><?= e($allergens[$aKey] ?? $aKey) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php // Subcategory sections ?>
        <?php foreach ($cat['subcategories'] ?? [] as $sub): ?>
        <div class="dm-subsection">
            <div class="dm-subsection-header">
                <i class="bi <?= e($sub['icon'] ?? 'bi-list') ?>"></i>
                <span><?= e($sub['name']) ?></span>
            </div>
            <div class="dm-items-grid">
                <?php foreach ($sub['items'] as $item): ?>
                <div class="dm-item <?= $item['image_url'] ? '' : 'dm-item--text-only' ?> dm-searchable" data-name="<?= e(mb_strtolower($item['name'])) ?>">
                    <?php if ($item['image_url']): ?>
                    <div class="dm-item-img-wrap">
                        <img src="<?= e($item['image_url']) ?>" alt="" class="dm-item-img" loading="lazy">
                    </div>
                    <?php endif; ?>
                    <div class="dm-item-content">
                        <div class="dm-item-top">
                            <span class="dm-item-name"><?= e($item['name']) ?></span>
                            <span class="dm-item-price"><?= number_format((float)$item['price'], 2, ',', '.') ?> &euro;</span>
                        </div>
                        <?php if ($item['description']): ?>
                        <div class="dm-item-desc"><?= e($item['description']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($item['allergens'])): ?>
                        <div class="dm-item-meta">
                            <div class="dm-allergen-tags">
                                <?php foreach ($item['allergens'] as $aKey): ?>
                                <span class="dm-allergen-tag dm-at-<?= e($aKey) ?>"><span class="dm-allergen-tag-dot"></span><?= e($allergens[$aKey] ?? $aKey) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <!-- ===== ALLERGEN LEGEND ===== -->
    <div class="dm-legend-section">
        <button class="dm-legend-toggle" aria-expanded="true" id="legend-toggle" type="button">
            <span class="dm-legend-toggle-left">
                <i class="bi bi-info-circle-fill"></i>
                <span class="dm-legend-toggle-title">Informazioni sugli Allergeni</span>
            </span>
            <i class="bi bi-chevron-down dm-legend-toggle-arrow"></i>
        </button>
        <div class="dm-legend-body" id="legend-body">
            <p class="dm-legend-note">Ai sensi del Reg. UE 1169/2011. Per ulteriori informazioni rivolgiti al personale di sala.</p>
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
            <h3 class="dm-cta-title">Ti abbiamo fatto venire fame?</h3>
            <p class="dm-cta-sub">Prenota per la prossima cena o il prossimo pranzo</p>
            <a href="<?= url($slug) ?>" class="dm-cta-btn"><i class="bi bi-calendar-check me-1"></i> Prenota un tavolo</a>
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
        });
    });

    // Scroll spy
    window.addEventListener('scroll', function() {
        var navH = catNav.offsetHeight + 30;
        var sections = document.querySelectorAll('.dm-section-anchor');
        var current = '';
        sections.forEach(function(s) {
            if (s.getBoundingClientRect().top <= navH + 50) current = s.id;
        });
        document.querySelectorAll('.dm-cat-nav-item').forEach(function(l) {
            l.classList.toggle('active', l.getAttribute('href') === '#' + current);
        });
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
