/* ============================================
   Evulery.it — Main JS
   ============================================ */

document.addEventListener('DOMContentLoaded', function() {

    /* ========== Navbar scroll effect ========== */
    const navbar = document.getElementById('navbar');
    function checkScroll() {
        navbar.classList.toggle('scrolled', window.scrollY > 20);
    }
    window.addEventListener('scroll', checkScroll, { passive: true });
    checkScroll();

    /* ========== Mobile menu ========== */
    const hamburger = document.getElementById('hamburger');
    const mobileMenu = document.getElementById('mobile-menu');

    hamburger.addEventListener('click', function() {
        const isOpen = mobileMenu.classList.toggle('open');
        hamburger.classList.toggle('active', isOpen);
        hamburger.setAttribute('aria-expanded', isOpen);
    });

    // Close mobile menu on link click
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

            // Close all others
            document.querySelectorAll('.ev-faq-item.open').forEach(function(openItem) {
                openItem.classList.remove('open');
                openItem.querySelector('.ev-faq-question').setAttribute('aria-expanded', 'false');
            });

            // Toggle current
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

    /* ========== Demo form ========== */
    var form = document.getElementById('demo-form');
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        var btn = form.querySelector('button[type="submit"]');
        var originalText = btn.innerHTML;

        // Simple validation
        var name = form.querySelector('#demo-name').value.trim();
        var restaurant = form.querySelector('#demo-restaurant').value.trim();
        var email = form.querySelector('#demo-email').value.trim();
        var phone = form.querySelector('#demo-phone').value.trim();

        if (!name || !restaurant || !email || !phone) {
            return;
        }

        // Show loading
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Invio in corso...';

        // Simulate form submission (replace with actual endpoint)
        setTimeout(function() {
            btn.innerHTML = '<i class="bi bi-check-circle"></i> Richiesta inviata!';
            btn.style.background = '#28a745';

            // Reset after 3s
            setTimeout(function() {
                form.reset();
                btn.innerHTML = originalText;
                btn.disabled = false;
                btn.style.background = '';
            }, 3000);
        }, 1200);
    });

    /* ========== Scroll reveal animation ========== */
    var revealElements = document.querySelectorAll(
        '.ev-problem-card, .ev-feature-card, .ev-step, .ev-plan-card, .ev-faq-item'
    );

    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    revealElements.forEach(function(el) {
        el.style.opacity = '0';
        el.style.transform = 'translateY(24px)';
        el.style.transition = 'opacity .5s ease, transform .5s ease';
        observer.observe(el);
    });
});
