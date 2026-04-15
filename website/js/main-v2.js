/* ============================================
   Evulery.it — Main JS v2
   No scroll animations, clean and functional
   ============================================ */

document.addEventListener('DOMContentLoaded', function() {

    /* ========== Navbar scroll effect ========== */
    var navbar = document.getElementById('navbar');
    function checkScroll() {
        navbar.classList.toggle('scrolled', window.scrollY > 20);
    }
    window.addEventListener('scroll', checkScroll, { passive: true });
    checkScroll();

    /* ========== Mobile menu ========== */
    var hamburger = document.getElementById('hamburger');
    var mobileMenu = document.getElementById('mobile-menu');

    hamburger.addEventListener('click', function() {
        var isOpen = mobileMenu.classList.toggle('open');
        hamburger.classList.toggle('active', isOpen);
        hamburger.setAttribute('aria-expanded', isOpen);
    });

    mobileMenu.querySelectorAll('a').forEach(function(link) {
        link.addEventListener('click', function() {
            mobileMenu.classList.remove('open');
            hamburger.classList.remove('active');
        });
    });

    /* ========== FAQ accordion ========== */
    document.querySelectorAll('.ev-faq-question').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var item = btn.closest('.ev-faq-item');
            var isOpen = item.classList.contains('open');

            document.querySelectorAll('.ev-faq-item.open').forEach(function(openItem) {
                openItem.classList.remove('open');
                openItem.querySelector('.ev-faq-question').setAttribute('aria-expanded', 'false');
            });

            if (!isOpen) {
                item.classList.add('open');
                btn.setAttribute('aria-expanded', 'true');
            }
        });
    });

    /* ========== Smooth scroll for anchor links ========== */
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            var target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    /* ========== Demo form with reCAPTCHA v3 ========== */
    var API_URL = 'https://dash.evulery.it/api/v1/demo-request';
    var form = document.getElementById('demo-form');
    var formError = document.getElementById('demo-form-error');

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        if (formError) formError.style.display = 'none';

        var btn = form.querySelector('button[type="submit"]');
        var originalText = btn.innerHTML;

        var name = form.querySelector('#demo-name').value.trim();
        var restaurant = form.querySelector('#demo-restaurant').value.trim();
        var email = form.querySelector('#demo-email').value.trim();
        var phone = form.querySelector('#demo-phone').value.trim();
        var message = form.querySelector('#demo-message').value.trim();

        if (!name || !restaurant || !email || !phone) return;

        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Invio in corso...';

        // Get reCAPTCHA token, then submit
        var siteKey = document.querySelector('meta[name="recaptcha-site-key"]');
        var key = siteKey ? siteKey.getAttribute('content') : '';

        function submitForm(recaptchaToken) {
            var body = new FormData();
            body.append('name', name);
            body.append('restaurant', restaurant);
            body.append('email', email);
            body.append('phone', phone);
            body.append('message', message);
            if (recaptchaToken) body.append('recaptcha_token', recaptchaToken);

            fetch(API_URL, { method: 'POST', body: body })
                .then(function(r) { return r.json(); })
                .then(function(json) {
                    if (json.success) {
                        btn.innerHTML = '<i class="bi bi-check-circle"></i> Richiesta inviata!';
                        btn.style.background = '#28a745';
                        setTimeout(function() {
                            form.reset();
                            btn.innerHTML = originalText;
                            btn.disabled = false;
                            btn.style.background = '';
                        }, 4000);
                    } else {
                        showFormError(json.error || 'Errore nell\'invio. Riprova.');
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }
                })
                .catch(function() {
                    showFormError('Errore di connessione. Riprova o contattaci a info@evulery.it');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
        }

        if (key && window.grecaptcha) {
            grecaptcha.ready(function() {
                grecaptcha.execute(key, { action: 'demo_request' }).then(submitForm);
            });
        } else {
            submitForm('');
        }
    });

    function showFormError(msg) {
        if (!formError) return;
        formError.textContent = msg;
        formError.style.display = 'block';
    }
});
