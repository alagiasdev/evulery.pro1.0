/**
 * Vetrina Digitale (Hub) — settings page interactive logic.
 * - Palette card click → toggles selected class
 * - Color pickers → sync hex text input
 * - QR generation client-side via qrcodejs (CDN-loaded inline)
 * - Drag-to-reorder via SortableJS (CDN inline)
 * - Custom links add/delete (Enterprise)
 * - Copy URL to clipboard
 */
(function() {
    'use strict';

    // -------- Palette card click highlights --------
    document.querySelectorAll('.hub-palette-card').forEach(function(card) {
        card.addEventListener('click', function() {
            document.querySelectorAll('.hub-palette-card').forEach(function(c) {
                c.classList.remove('selected');
            });
            card.classList.add('selected');
            var input = card.querySelector('input[type="radio"]');
            if (input) input.checked = true;
            updateLivePreview();
        });
    });

    // -------- Color pickers sync with hex text --------
    document.querySelectorAll('.hub-color-row input[type="color"]').forEach(function(picker) {
        var textInput = picker.parentElement.querySelector('[data-color-text]');
        picker.addEventListener('input', function() {
            if (textInput) textInput.value = picker.value.toUpperCase();
            updateLivePreview();
        });
        if (textInput) {
            textInput.addEventListener('input', function() {
                if (/^#[0-9a-fA-F]{6}$/.test(textInput.value)) picker.value = textInput.value;
            });
        }
    });

    // -------- Live preview update --------
    function updateLivePreview() {
        var selectedPalette = document.querySelector('.hub-palette-card.selected');
        if (!selectedPalette) return;
        var swatch = selectedPalette.querySelector('.hub-palette-swatch');
        if (!swatch) return;
        var divs = swatch.querySelectorAll('div');
        if (divs.length < 3) return;
        var primary = divs[0].style.background;
        var accent = divs[1].style.background;
        var dark = divs[2].style.background;

        // Override with custom colors if Enterprise
        var customPrimary = document.querySelector('input[name="custom_primary"]');
        var customAccent = document.querySelector('input[name="custom_accent"]');
        if (customPrimary && customPrimary.value && customPrimary.value !== '#000000') primary = customPrimary.value;
        if (customAccent && customAccent.value && customAccent.value !== '#000000') accent = customAccent.value;

        // Apply to preview elements
        var cover = document.getElementById('ppm-cover');
        if (cover) cover.style.background = 'linear-gradient(135deg, ' + primary + ', ' + dark + ')';
        var cta = document.getElementById('ppm-cta');
        if (cta) cta.style.background = primary;
        document.querySelectorAll('.ppm-row-icon').forEach(function(el) { el.style.background = accent; });
        document.querySelectorAll('.ppm-row-icon i').forEach(function(el) { el.style.color = primary; });
        var logo = document.querySelector('.ppm-logo');
        if (logo) logo.style.color = primary;
    }

    // -------- Copy URL to clipboard --------
    var copyBtn = document.getElementById('hub-copy-url');
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            var url = document.getElementById('hub-public-url').textContent.trim();
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function() {
                    var orig = copyBtn.innerHTML;
                    copyBtn.innerHTML = '<i class="bi bi-check-lg"></i> Copiato';
                    setTimeout(function() { copyBtn.innerHTML = orig; }, 1500);
                });
            }
        });
    }

    // -------- QR generation (CDN qrcodejs) --------
    function loadQrLib(callback) {
        if (window.QRCode) { callback(); return; }
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js';
        s.onload = callback;
        s.onerror = function() {
            var canvas = document.getElementById('hub-qr-canvas');
            if (canvas) canvas.innerHTML = '<span style="color:#dc3545;font-size:.8rem;">QR non disponibile</span>';
        };
        document.head.appendChild(s);
    }

    var qrCanvas = document.getElementById('hub-qr-canvas');
    if (qrCanvas) {
        loadQrLib(function() {
            qrCanvas.innerHTML = '';
            new QRCode(qrCanvas, {
                text: qrCanvas.dataset.url,
                width: 164,
                height: 164,
                colorDark: '#212529',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M
            });
        });
    }

    // Download QR as PNG
    var qrDownload = document.getElementById('hub-qr-download-png');
    if (qrDownload) {
        qrDownload.addEventListener('click', function() {
            var img = qrCanvas ? qrCanvas.querySelector('img, canvas') : null;
            if (!img) return;
            var dataUrl = img.tagName === 'CANVAS' ? img.toDataURL('image/png') : img.src;
            var a = document.createElement('a');
            a.href = dataUrl;
            a.download = 'vetrina-qr.png';
            document.body.appendChild(a);
            a.click();
            a.remove();
        });
    }

    // Print QR
    var qrPrint = document.getElementById('hub-qr-print');
    if (qrPrint) {
        qrPrint.addEventListener('click', function() {
            var img = qrCanvas ? qrCanvas.querySelector('img, canvas') : null;
            if (!img) return;
            var dataUrl = img.tagName === 'CANVAS' ? img.toDataURL('image/png') : img.src;
            var url = qrCanvas.dataset.url;
            var w = window.open('', '_blank');
            w.document.write('<!DOCTYPE html><html><head><title>QR Vetrina</title><style>body{font-family:system-ui,sans-serif;text-align:center;padding:2rem;}h1{font-size:1.5rem;margin-bottom:1rem;}img{max-width:300px;}p{color:#666;margin-top:1rem;font-size:.9rem;}</style></head><body><h1>Scansiona per accedere</h1><img src="' + dataUrl + '"><p>' + url + '</p></body></html>');
            w.document.close();
            setTimeout(function() { w.print(); }, 200);
        });
    }

    // -------- Drag-to-reorder via SortableJS --------
    function loadSortableLib(callback) {
        if (window.Sortable) { callback(); return; }
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js';
        s.onload = callback;
        document.head.appendChild(s);
    }

    var actionsList = document.getElementById('hub-actions-list');
    if (actionsList) {
        loadSortableLib(function() {
            Sortable.create(actionsList, {
                handle: '.hub-drag-handle:not(.locked)',
                draggable: '.hub-action-item:not([data-locked="1"])',
                animation: 150,
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                onEnd: function() {
                    var orderedIds = Array.prototype.map.call(
                        actionsList.querySelectorAll('.hub-action-item'),
                        function(li) { return li.dataset.id; }
                    );
                    var csrf = document.getElementById('csrf-token').value;
                    var url = document.getElementById('hub-reorder-url').value;
                    var fd = new FormData();
                    fd.append('_csrf', csrf);
                    orderedIds.forEach(function(id) { fd.append('order[]', id); });
                    fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
                        .catch(function() {
                            alert('Errore nel salvataggio dell\'ordine. Ricarica la pagina.');
                        });
                }
            });
        });
    }

    // -------- Add custom link --------
    var addLinkBtn = document.getElementById('add-custom-link-btn');
    if (addLinkBtn) {
        addLinkBtn.addEventListener('click', function() {
            var label = document.getElementById('custom-link-label').value.trim();
            var url   = document.getElementById('custom-link-url').value.trim();
            var icon  = document.getElementById('custom-link-icon').value;
            if (!label || !url) {
                alert('Inserisci etichetta e URL del link.');
                return;
            }
            document.getElementById('add-link-label').value = label;
            document.getElementById('add-link-url').value = url;
            document.getElementById('add-link-icon').value = icon;
            document.getElementById('add-custom-link-form').submit();
        });
    }

    // -------- Delete custom link --------
    document.querySelectorAll('.hub-action-delete').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Eliminare questo link personalizzato?')) return;
            var deleteUrl = btn.dataset.deleteUrl;
            var actionId = btn.closest('.hub-action-item').dataset.id;
            var deleteForm = document.getElementById('delete-link-' + actionId);
            if (deleteForm) {
                deleteForm.submit();
            } else {
                // Fallback: build form on the fly
                var f = document.createElement('form');
                f.method = 'POST';
                f.action = deleteUrl;
                var c = document.createElement('input');
                c.name = '_csrf';
                c.value = document.getElementById('csrf-token').value;
                f.appendChild(c);
                document.body.appendChild(f);
                f.submit();
            }
        });
    });

    // Init preview on page load
    updateLivePreview();
})();
