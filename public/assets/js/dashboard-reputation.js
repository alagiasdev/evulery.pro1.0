/**
 * Dashboard Reputation — reply toggle
 */
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll("[data-toggle-reply]").forEach(function(btn) {
        btn.addEventListener("click", function() {
            var formId = this.getAttribute("data-toggle-reply");
            var form = document.getElementById(formId);
            if (form) {
                form.style.display = form.style.display === "none" ? "block" : "none";
                if (form.style.display === "block") {
                    var input = form.querySelector("input[name=reply]");
                    if (input) input.focus();
                }
            }
        });
    });
});
