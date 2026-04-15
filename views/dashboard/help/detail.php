<?php
// $slug, $section, $related come from controller
$categoryLabels = [
    'primi'          => 'Primi passi',
    'configurazione' => 'Configurazione',
    'operativita'    => 'Operativit&agrave;',
    'avanzati'       => 'Servizi avanzati',
    'supporto'       => 'Supporto',
];
$categoryLabel = $categoryLabels[$section['category'] ?? 'supporto'] ?? '';
?>

<!-- Breadcrumb -->
<div class="hg-breadcrumb">
    <a href="<?= url('dashboard/help') ?>"><i class="bi bi-book"></i> Guida</a>
    <span class="sep">/</span>
    <span><?= $categoryLabel ?></span>
    <span class="sep">/</span>
    <span style="color:#1a1d23; font-weight:500;"><?= $section['title'] ?></span>
</div>

<div class="hg-detail-layout">

    <!-- Articolo -->
    <article class="hg-article">

        <div class="hg-article-header">
            <div class="hg-article-icon" style="background:<?= e($section['color'] ?? '#00844A') ?>;">
                <i class="bi <?= e($section['icon'] ?? 'bi-file-text') ?>"></i>
            </div>
            <div style="flex:1;">
                <h1 class="hg-article-title"><?= $section['title'] ?></h1>
                <p class="hg-article-subtitle"><?= $section['subtitle'] ?? '' ?></p>
                <div class="hg-article-meta">
                    <span><i class="bi bi-clock-history"></i> Lettura ~<?= (int)($section['read_time'] ?? 2) ?> min</span>
                    <span><i class="bi bi-calendar3"></i> Aggiornato 15/04/2026</span>
                </div>
            </div>
        </div>

        <div class="hg-article-body">
            <?= $section['body'] ?? '' ?>
        </div>

        <!-- Footer: feedback + back -->
        <div class="hg-article-footer">
            <div class="hg-feedback" data-hg-section="<?= e($slug) ?>">
                <span class="hg-feedback-label">Questo articolo ti &egrave; stato utile?</span>
                <button type="button" class="hg-feedback-btn" data-hg-vote="up" data-hg-section="<?= e($slug) ?>">
                    <i class="bi bi-hand-thumbs-up"></i> S&igrave;
                </button>
                <button type="button" class="hg-feedback-btn" data-hg-vote="down" data-hg-section="<?= e($slug) ?>">
                    <i class="bi bi-hand-thumbs-down"></i> No
                </button>
            </div>
            <a href="<?= url('dashboard/help') ?>" class="hg-back-btn">
                <i class="bi bi-arrow-left"></i> Torna alla guida
            </a>
        </div>
    </article>

    <!-- Sidebar -->
    <aside class="hg-detail-sidebar">

        <!-- Articoli correlati -->
        <?php if (!empty($related)): ?>
        <div class="hg-sidebar-card">
            <div class="hg-sidebar-title">Articoli correlati</div>
            <div class="hg-related">
                <?php foreach ($related as $rSlug => $rData): ?>
                <a href="<?= url('dashboard/help/' . $rSlug) ?>" class="hg-related-item">
                    <div class="hg-related-icon" style="background:<?= e($rData['color'] ?? '#00844A') ?>;">
                        <i class="bi <?= e($rData['icon'] ?? 'bi-file-text') ?>"></i>
                    </div>
                    <div class="hg-related-text"><?= $rData['title'] ?></div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Supporto box -->
        <div class="hg-sidebar-card hg-support-sidebar">
            <div class="hg-support-sidebar-icon"><i class="bi bi-headset"></i></div>
            <h4>Serve assistenza?</h4>
            <p>Non hai trovato quello che cercavi? Contattaci e ti aiuteremo personalmente.</p>
            <?php $supportEmail = env('SUPPORT_EMAIL', 'info@evulery.it'); ?>
            <a href="mailto:<?= e($supportEmail) ?>" class="hg-support-sidebar-btn">
                <i class="bi bi-envelope me-1"></i> Contattaci
            </a>
        </div>

    </aside>
</div>

<!-- Feedback modal (open on 👎 click) -->
<div class="hg-modal-backdrop" id="hgModalBackdrop" role="dialog" aria-modal="true" aria-labelledby="hgModalTitle">
    <div class="hg-modal">
        <button type="button" class="hg-modal-close" id="hgModalClose" aria-label="Chiudi"><i class="bi bi-x-lg"></i></button>
        <h4 id="hgModalTitle"><i class="bi bi-chat-left-text me-1" style="color:var(--brand);"></i> Aiutaci a migliorare</h4>
        <p>Cosa non ti &egrave; stato utile in questo articolo? Il tuo commento ci aiuta a scrivere una guida pi&ugrave; chiara.</p>
        <textarea id="hgModalComment" rows="4" placeholder="Scrivi qui il tuo commento (facoltativo)..."></textarea>
        <div class="hg-modal-actions">
            <button type="button" class="hg-modal-btn hg-modal-btn--ghost" id="hgModalCancel">Annulla</button>
            <button type="button" class="hg-modal-btn hg-modal-btn--primary" id="hgModalSubmit">Invia</button>
        </div>
    </div>
</div>

<!-- Feedback toast -->
<div class="hg-toast" id="hgToast" role="status" aria-live="polite"></div>

<script nonce="<?= csp_nonce() ?>">
(function() {
    var CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
    var FEEDBACK_URL = <?= json_encode(url('dashboard/help/feedback')) ?>;

    // Modal elements
    var modal = document.getElementById('hgModalBackdrop');
    var modalClose = document.getElementById('hgModalClose');
    var modalCancel = document.getElementById('hgModalCancel');
    var modalSubmit = document.getElementById('hgModalSubmit');
    var modalComment = document.getElementById('hgModalComment');
    var toast = document.getElementById('hgToast');
    var pendingSection = null;

    function showToast(msg, isError) {
        if (!toast) return;
        toast.textContent = msg;
        toast.className = 'hg-toast show' + (isError ? ' error' : '');
        setTimeout(function() { toast.className = 'hg-toast'; }, 3500);
    }

    function openModal(sectionId) {
        pendingSection = sectionId;
        if (modalComment) modalComment.value = '';
        if (modal) {
            modal.classList.add('show');
            setTimeout(function() { if (modalComment) modalComment.focus(); }, 50);
        }
    }
    function closeModal() {
        if (modal) modal.classList.remove('show');
        pendingSection = null;
    }

    if (modalClose) modalClose.addEventListener('click', closeModal);
    if (modalCancel) modalCancel.addEventListener('click', closeModal);
    if (modal) modal.addEventListener('click', function(e) {
        if (e.target === modal) closeModal();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('show')) closeModal();
    });

    function sendFeedback(sectionId, vote, comment) {
        var body = new FormData();
        body.append('section', sectionId);
        body.append('value', vote);
        body.append('comment', comment || '');
        body.append('_csrf', CSRF_TOKEN);

        return fetch(FEEDBACK_URL, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: body
        }).then(function(r) { return r.json(); });
    }

    // Handle feedback button clicks (delegation)
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.hg-feedback-btn');
        if (!btn) return;
        var vote = btn.dataset.hgVote;
        var sectionId = btn.dataset.hgSection;
        if (!vote || !sectionId) return;

        if (vote === 'up') {
            btn.disabled = true;
            sendFeedback(sectionId, 'up', '')
                .then(function(json) {
                    if (json && json.success) {
                        showToast('Grazie per il tuo feedback!');
                        document.querySelectorAll('.hg-feedback-btn[data-hg-section="' + sectionId + '"]').forEach(function(b) {
                            b.disabled = true;
                        });
                        btn.classList.add('voted');
                    } else {
                        btn.disabled = false;
                        showToast('Errore nell\'invio. Riprova.', true);
                    }
                })
                .catch(function() {
                    btn.disabled = false;
                    showToast('Errore di rete. Riprova.', true);
                });
        } else {
            openModal(sectionId);
        }
    });

    if (modalSubmit) modalSubmit.addEventListener('click', function() {
        if (!pendingSection) return;
        var comment = modalComment ? modalComment.value.trim() : '';
        modalSubmit.disabled = true;
        modalSubmit.textContent = 'Invio...';

        sendFeedback(pendingSection, 'down', comment)
            .then(function(json) {
                modalSubmit.disabled = false;
                modalSubmit.textContent = 'Invia';
                if (json && json.success) {
                    showToast('Grazie, il tuo feedback ci aiuta a migliorare.');
                    document.querySelectorAll('.hg-feedback-btn[data-hg-section="' + pendingSection + '"]').forEach(function(b) {
                        b.disabled = true;
                        if (b.dataset.hgVote === 'down') b.classList.add('voted');
                    });
                    closeModal();
                } else {
                    showToast('Errore nell\'invio. Riprova.', true);
                }
            })
            .catch(function() {
                modalSubmit.disabled = false;
                modalSubmit.textContent = 'Invia';
                showToast('Errore di rete. Riprova.', true);
            });
    });
})();
</script>
