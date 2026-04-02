/**
 * Review Landing — star rating + feedback form
 * Reads config from #rv-config data attributes.
 */
(function() {
    var cfgEl = document.getElementById('rv-config');
    if (!cfgEl) return;

    var config = {
        apiBaseUrl: cfgEl.dataset.apiBaseUrl || '',
        slug: cfgEl.dataset.slug || '',
        token: cfgEl.dataset.token || '',
        source: cfgEl.dataset.source || 'embed',
        embed: cfgEl.dataset.embed === '1'
    };

    var selectedRating = 0;
    var reviewId = null;

    // Star buttons
    var stars = document.querySelectorAll('.rv-star-btn');
    stars.forEach(function(btn) {
        btn.addEventListener('mouseenter', function() {
            highlightStars(parseInt(this.getAttribute('data-rating')));
        });
        btn.addEventListener('mouseleave', function() {
            highlightStars(selectedRating);
        });
        btn.addEventListener('click', function() {
            selectedRating = parseInt(this.getAttribute('data-rating'));
            highlightStars(selectedRating);
            submitRating(selectedRating);
        });
    });

    function highlightStars(n) {
        stars.forEach(function(s) {
            s.classList.toggle('active', parseInt(s.getAttribute('data-rating')) <= n);
        });
    }

    function submitRating(rating) {
        stars.forEach(function(s) { s.disabled = true; });

        fetch(config.apiBaseUrl + '/tenants/' + config.slug + '/review', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                rating: rating,
                token: config.token,
                source: config.source
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            reviewId = data.reviewId || null;
            if (data.showFeedback) {
                document.getElementById('rv-rating-view').style.display = 'none';
                document.getElementById('rv-feedback-view').style.display = 'block';
            } else if (data.redirect) {
                showThanks('Grazie per la tua valutazione! Verrai reindirizzato...');
                setTimeout(function() { window.open(data.redirect, '_blank'); }, 1500);
            } else {
                showThanks('Grazie per la tua valutazione!');
            }
        })
        .catch(function() {
            showThanks('Grazie per la tua valutazione!');
        });
    }

    // Feedback submit
    var fbSubmit = document.getElementById('rv-feedback-submit');
    if (fbSubmit) {
        fbSubmit.addEventListener('click', function() {
            var text = document.getElementById('rv-feedback-text').value.trim();
            if (text.length < 10) {
                document.getElementById('rv-feedback-text').style.borderColor = '#ef5350';
                return;
            }
            fbSubmit.disabled = true;
            fbSubmit.innerHTML = '<i class="bi bi-hourglass-split"></i> Invio...';

            fetch(config.apiBaseUrl + '/tenants/' + config.slug + '/review/feedback', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    review_id: reviewId,
                    feedback_text: text
                })
            })
            .then(function() {
                showThanks('Grazie! Il tuo feedback \u00e8 stato inviato e verr\u00e0 letto dal ristorante.');
            })
            .catch(function() {
                showThanks('Grazie per il tuo feedback!');
            });
        });
    }

    function showThanks(msg) {
        var rv = document.getElementById('rv-rating-view');
        var fv = document.getElementById('rv-feedback-view');
        if (rv) rv.style.display = 'none';
        if (fv) fv.style.display = 'none';
        document.getElementById('rv-thanks-text').textContent = msg;
        document.getElementById('rv-thanks-view').style.display = 'block';
        if (config.embed) {
            try { window.parent.postMessage({type: 'rv-resize', height: document.body.scrollHeight}, '*'); } catch(e) {}
        }
    }
})();
