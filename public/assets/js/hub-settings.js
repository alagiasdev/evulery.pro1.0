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

    // -------- File uploaders (logo + cover) --------
    document.querySelectorAll('.hub-uploader').forEach(function(uploader) {
        var field = uploader.dataset.field; // 'logo' | 'cover'
        var dropZone = uploader.querySelector('.hub-uploader-drop');
        var fileInput = uploader.querySelector('input[type="file"]');
        var removeCheckbox = uploader.querySelector('input[name="' + field + '_remove"]');
        var preview = uploader.querySelector('.hub-uploader-preview');
        var removeBtn = uploader.querySelector('.hub-uploader-remove');
        if (!dropZone || !fileInput) return;

        // Drag visual feedback
        ['dragenter', 'dragover'].forEach(function(ev) {
            dropZone.addEventListener(ev, function(e) {
                e.preventDefault(); e.stopPropagation();
                dropZone.classList.add('is-dragging');
            });
        });
        ['dragleave', 'drop'].forEach(function(ev) {
            dropZone.addEventListener(ev, function(e) {
                e.preventDefault(); e.stopPropagation();
                dropZone.classList.remove('is-dragging');
            });
        });
        dropZone.addEventListener('drop', function(e) {
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                handleFileChange();
            }
        });

        // File chosen via picker → show preview
        fileInput.addEventListener('change', handleFileChange);

        function handleFileChange() {
            var f = fileInput.files && fileInput.files[0];
            if (!f) return;
            // Client-side size guard (server validates anyway)
            if (f.size > 2 * 1024 * 1024) {
                alert('File troppo grande (max 2 MB).');
                fileInput.value = '';
                return;
            }
            // If "remove" was checked previously, uncheck it
            if (removeCheckbox) removeCheckbox.checked = false;
            // Show inline preview
            var reader = new FileReader();
            reader.onload = function(ev) {
                replacePreview(ev.target.result);
            };
            reader.readAsDataURL(f);
        }

        function replacePreview(dataUrl) {
            // Build or update the preview block
            var existing = uploader.querySelector('.hub-uploader-preview');
            if (existing) existing.remove();
            var div = document.createElement('div');
            div.className = 'hub-uploader-preview';
            var img = document.createElement('img');
            img.className = 'hub-uploader-thumb ' + field;
            img.src = dataUrl;
            img.alt = field;
            div.appendChild(img);
            uploader.insertBefore(div, dropZone);
        }

        // Remove existing image
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                if (!confirm('Rimuovere ' + (field === 'logo' ? 'il logo' : 'la copertina') + '? La modifica diventa effettiva al salvataggio.')) return;
                if (preview) preview.remove();
                fileInput.value = '';
                if (removeCheckbox) removeCheckbox.checked = true;
            });
        }
    });

    // -------- Master enable toggle: grey out config blocks live --------
    var masterToggle = document.getElementById('hub-enabled-toggle');
    var masterCard = document.getElementById('hub-master-card');
    var configBlock = document.getElementById('hub-config-block');
    var stickyBlock = document.getElementById('hub-sticky-block');
    if (masterToggle) {
        masterToggle.addEventListener('change', function() {
            var enabled = masterToggle.checked;
            if (configBlock) configBlock.classList.toggle('hub-greyed', !enabled);
            if (stickyBlock) stickyBlock.classList.toggle('hub-greyed', !enabled);
            if (masterCard) {
                masterCard.classList.toggle('enabled', enabled);
                masterCard.classList.toggle('disabled', !enabled);
            }
        });
    }

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

    // -------- Custom-colors master toggle --------
    var customColorsToggle = document.getElementById('hub-custom-colors-toggle');
    var customColorsBlock = document.getElementById('hub-custom-colors-block');
    if (customColorsToggle && customColorsBlock) {
        customColorsToggle.addEventListener('change', function() {
            customColorsBlock.classList.toggle('is-disabled', !customColorsToggle.checked);
            updateLivePreview();
        });
    }

    // -------- Live preview update --------
    function updateLivePreview() {
        // Modalità: custom toggle ON → usa input custom; altrimenti palette
        var useCustom = customColorsToggle && customColorsToggle.checked;
        var primary, accent, dark, bg = '#ffffff';

        if (useCustom) {
            var cp = document.querySelector('input[name="custom_primary"]');
            var ca = document.querySelector('input[name="custom_accent"]');
            var cd = document.querySelector('input[name="custom_dark"]');
            var cb = document.querySelector('input[name="custom_bg"]');
            primary = cp ? cp.value : '#00844A';
            accent  = ca ? ca.value : '#E8F5E9';
            dark    = cd && cd.value ? cd.value : primary;  // fallback al primary se vuoto
            bg      = cb ? cb.value : '#ffffff';
        } else {
            var selectedPalette = document.querySelector('.hub-palette-card.selected');
            if (!selectedPalette) return;
            var swatch = selectedPalette.querySelector('.hub-palette-swatch');
            if (!swatch) return;
            var divs = swatch.querySelectorAll('div');
            if (divs.length < 3) return;
            primary = divs[0].style.background;
            accent  = divs[1].style.background;
            dark    = divs[2].style.background;
        }

        // Apply to preview elements
        var cover = document.getElementById('ppm-cover');
        if (cover) cover.style.background = 'linear-gradient(135deg, ' + primary + ', ' + dark + ')';
        var cta = document.getElementById('ppm-cta');
        if (cta) cta.style.background = primary;
        document.querySelectorAll('.ppm-row-icon').forEach(function(el) { el.style.background = accent; });
        document.querySelectorAll('.ppm-row-icon i').forEach(function(el) { el.style.color = primary; });
        var logo = document.querySelector('.ppm-logo');
        if (logo) logo.style.color = primary;
        var body = document.querySelector('.ppm-body');
        if (body) body.style.background = bg;
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

    // Generate a high-resolution QR data URL (1024x1024, error correction H for print)
    function generateHighResQrDataUrl(text, callback) {
        loadQrLib(function() {
            var holder = document.createElement('div');
            holder.style.cssText = 'position:absolute;left:-99999px;top:-99999px;';
            document.body.appendChild(holder);
            new QRCode(holder, {
                text: text,
                width: 1024,
                height: 1024,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });
            // qrcodejs renders asynchronously — wait a tick for canvas/img to populate
            setTimeout(function() {
                var node = holder.querySelector('canvas, img');
                var dataUrl = '';
                if (node) {
                    dataUrl = node.tagName === 'CANVAS' ? node.toDataURL('image/png') : node.src;
                }
                holder.remove();
                callback(dataUrl);
            }, 50);
        });
    }

    // Download QR as high-res PNG (1024x1024, suitable for print)
    var qrDownload = document.getElementById('hub-qr-download-png');
    if (qrDownload) {
        qrDownload.addEventListener('click', function() {
            if (!qrCanvas) return;
            var originalLabel = qrDownload.innerHTML;
            qrDownload.disabled = true;
            qrDownload.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Generazione...';
            generateHighResQrDataUrl(qrCanvas.dataset.url, function(dataUrl) {
                qrDownload.disabled = false;
                qrDownload.innerHTML = originalLabel;
                if (!dataUrl) return;
                var a = document.createElement('a');
                a.href = dataUrl;
                a.download = 'vetrina-qr.png';
                document.body.appendChild(a);
                a.click();
                a.remove();
            });
        });
    }

    // Print QR (uses high-res image so output is sharp on paper)
    var qrPrint = document.getElementById('hub-qr-print');
    if (qrPrint) {
        qrPrint.addEventListener('click', function() {
            if (!qrCanvas) return;
            var originalLabel = qrPrint.innerHTML;
            qrPrint.disabled = true;
            qrPrint.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Generazione...';
            generateHighResQrDataUrl(qrCanvas.dataset.url, function(dataUrl) {
                qrPrint.disabled = false;
                qrPrint.innerHTML = originalLabel;
                if (!dataUrl) return;
                var url = qrCanvas.dataset.url;
                var w = window.open('', '_blank');
                w.document.write('<!DOCTYPE html><html><head><title>QR Vetrina</title><style>body{font-family:system-ui,sans-serif;text-align:center;padding:2rem;}h1{font-size:1.5rem;margin-bottom:1rem;}img{width:300px;height:300px;image-rendering:pixelated;}p{color:#666;margin-top:1rem;font-size:.9rem;word-break:break-all;}@media print{body{padding:0;}img{width:8cm;height:8cm;}}</style></head><body><h1>Scansiona per accedere</h1><img src="' + dataUrl + '"><p>' + url + '</p></body></html>');
                w.document.close();
                setTimeout(function() { w.print(); }, 300);
            });
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
            var sub   = document.getElementById('custom-link-sub').value.trim();
            var icon  = document.getElementById('custom-link-icon').value;
            if (!label || !url) {
                alert('Inserisci etichetta e URL del link.');
                return;
            }
            document.getElementById('add-link-label').value = label;
            document.getElementById('add-link-url').value = url;
            document.getElementById('add-link-sub').value = sub;
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
