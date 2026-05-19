/**
 * Settings Reviews — toggle master + filter + copy buttons
 */
document.addEventListener("DOMContentLoaded", function() {
    // Master toggle (componente .toggle-big — div cliccabile + input nascosto)
    var master = document.getElementById("rv-toggle");
    var masterCard = document.getElementById("rv-master");
    var enabledInput = document.getElementById("rv-enabled-input");
    var body = document.getElementById("rv-settings-body");
    if (master && body) {
        master.addEventListener("click", function() {
            var on = !master.classList.contains("on");
            master.classList.toggle("on", on);
            master.classList.toggle("off", !on);
            if (masterCard) {
                masterCard.classList.toggle("enabled", on);
                masterCard.classList.toggle("disabled", !on);
            }
            if (enabledInput) enabledInput.value = on ? "1" : "";
            body.style.display = on ? "" : "none";
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
