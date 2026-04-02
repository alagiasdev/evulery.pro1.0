/**
 * Settings Reviews — toggle master + filter + copy buttons
 */
document.addEventListener("DOMContentLoaded", function() {
    // Master toggle
    var master = document.getElementById("rv-toggle-master");
    var body = document.getElementById("rv-settings-body");
    if (master && body) {
        master.addEventListener("change", function() {
            body.style.display = this.checked ? "" : "none";
        });
    }

    // Filter toggle
    var filter = document.getElementById("rv-toggle-filter");
    var filterBody = document.getElementById("rv-filter-body");
    if (filter && filterBody) {
        filter.addEventListener("change", function() {
            filterBody.style.display = this.checked ? "" : "none";
        });
    }

    // Copy link buttons (same pattern as general settings)
    document.querySelectorAll("[data-copy-target]").forEach(function(btn) {
        btn.addEventListener("click", function(e) {
            e.preventDefault();
            var el = document.getElementById(this.dataset.copyTarget);
            if (el) {
                navigator.clipboard.writeText(el.textContent.trim()).then(function() {
                    var orig = btn.innerHTML;
                    btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Copiato!';
                    setTimeout(function() { btn.innerHTML = orig; }, 1500);
                });
            }
        });
    });

    // Copy embed code buttons
    document.querySelectorAll("[data-copy-text]").forEach(function(btn) {
        btn.addEventListener("click", function(e) {
            e.preventDefault();
            navigator.clipboard.writeText(this.dataset.copyText).then(function() {
                var orig = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Copiato!';
                setTimeout(function() { btn.innerHTML = orig; }, 1500);
            });
        });
    });
});
