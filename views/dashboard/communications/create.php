<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;">
    <a href="<?= url('dashboard/communications') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h5 style="font-weight:700;margin:0;">Nuova Comunicazione</h5>
        <p style="font-size:.82rem;color:#6c757d;margin:0;">Invia un'email ai tuoi clienti</p>
    </div>
</div>

<form method="POST" action="<?= url('dashboard/communications') ?>" id="broadcast-form">
    <?= csrf_field() ?>

    <div style="display:grid;grid-template-columns:1fr 320px;gap:1.25rem;align-items:start;">
        <!-- Left: Form -->
        <div>
            <div class="card" style="padding:1.25rem;">
                <div style="margin-bottom:1rem;">
                    <label style="font-weight:600;font-size:.85rem;margin-bottom:.35rem;display:block;">Oggetto *</label>
                    <input type="text" name="subject" class="form-control" placeholder="Es: Novit&agrave; di stagione!" maxlength="255" required>
                </div>
                <div style="margin-bottom:1rem;">
                    <label style="font-weight:600;font-size:.85rem;margin-bottom:.35rem;display:block;">Messaggio *</label>
                    <textarea name="body_text" class="form-control" rows="10" maxlength="5000" placeholder="Scrivi il messaggio per i tuoi clienti...&#10;&#10;Puoi scrivere pi&ugrave; paragrafi separandoli con una riga vuota." required style="resize:vertical;"></textarea>
                    <div style="text-align:right;font-size:.72rem;color:#adb5bd;margin-top:.25rem;">
                        <span id="char-count">0</span>/5000 caratteri
                    </div>
                </div>

                <!-- Segment selection -->
                <div style="margin-bottom:.5rem;">
                    <label style="font-weight:600;font-size:.85rem;margin-bottom:.5rem;display:block;">Destinatari</label>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem;" id="segment-radios">
                        <?php
                        $segments = [
                            'all'          => ['Tutti i clienti', 'bi-people-fill', '#6c757d'],
                            'nuovo'        => ['Nuovi', 'bi-person-plus', '#0dcaf0'],
                            'occasionale'  => ['Occasionali', 'bi-person', '#ffc107'],
                            'abituale'     => ['Abituali', 'bi-person-check', '#198754'],
                            'vip'          => ['VIP', 'bi-star-fill', '#E65100'],
                            'inactive'     => ['Inattivi', 'bi-person-x', '#dc3545'],
                        ];
                        foreach ($segments as $key => $seg): ?>
                        <label class="segment-option" style="display:flex;align-items:center;gap:.5rem;padding:.6rem .75rem;border:2px solid #dee2e6;border-radius:8px;cursor:pointer;font-size:.82rem;transition:all .15s;">
                            <input type="radio" name="segment_filter" value="<?= $key ?>" <?= $key === 'all' ? 'checked' : '' ?> style="accent-color:<?= $seg[2] ?>;">
                            <i class="bi <?= $seg[1] ?>" style="color:<?= $seg[2] ?>;"></i>
                            <span><?= $seg[0] ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Inactive days (shown only when segment=inactive) -->
                <div id="inactive-days-row" style="display:none;margin-top:.75rem;">
                    <label style="font-weight:600;font-size:.82rem;margin-bottom:.35rem;display:block;">Inattivi da quanti giorni?</label>
                    <input type="number" name="inactive_days" id="inactive-days-input" class="form-control" value="30" min="1" max="365" style="max-width:150px;">
                </div>
            </div>

            <div style="margin-top:1rem;">
                <button type="submit" class="btn btn-success" id="submit-btn">
                    <i class="bi bi-send me-1"></i> Invia comunicazione
                </button>
                <a href="<?= url('dashboard/communications') ?>" class="btn btn-outline-secondary ms-2">Annulla</a>
            </div>
        </div>

        <!-- Right: Preview panel -->
        <div>
            <div class="card" style="padding:1rem;">
                <div style="font-weight:700;font-size:.85rem;margin-bottom:.75rem;">
                    <i class="bi bi-bar-chart me-1"></i> Riepilogo
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;border-bottom:1px solid #f0f0f0;">
                    <span style="font-size:.82rem;color:#6c757d;">Destinatari</span>
                    <span style="font-size:1.1rem;font-weight:700;color:#1a1d23;" id="recipient-count">
                        <i class="bi bi-arrow-repeat" style="animation:spin 1s linear infinite;font-size:.85rem;color:#adb5bd;"></i>
                    </span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;border-bottom:1px solid #f0f0f0;">
                    <span style="font-size:.82rem;color:#6c757d;">Crediti necessari</span>
                    <span style="font-size:.95rem;font-weight:700;color:#dc3545;" id="credits-needed">—</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;">
                    <span style="font-size:.82rem;color:#6c757d;">Saldo crediti</span>
                    <span style="font-size:.95rem;font-weight:700;color:#00844A;"><?= number_format($credits, 0, ',', '.') ?></span>
                </div>

                <div id="credits-alert" style="display:none;margin-top:.75rem;padding:.5rem .75rem;background:#fff3cd;border-radius:6px;font-size:.78rem;color:#856404;">
                    <i class="bi bi-exclamation-triangle me-1"></i> Crediti insufficienti per questo invio.
                </div>
            </div>
        </div>
    </div>
</form>

<script nonce="<?= csp_nonce() ?>">
(function() {
    var previewUrl = '<?= url("dashboard/communications/preview") ?>';
    var credits = <?= (int)$credits ?>;
    var countEl = document.getElementById('recipient-count');
    var neededEl = document.getElementById('credits-needed');
    var alertEl = document.getElementById('credits-alert');
    var submitBtn = document.getElementById('submit-btn');
    var inactiveDaysRow = document.getElementById('inactive-days-row');
    var inactiveDaysInput = document.getElementById('inactive-days-input');
    var textarea = document.querySelector('textarea[name="body_text"]');
    var charCount = document.getElementById('char-count');
    var fetchTimer = null;

    // Character counter
    textarea.addEventListener('input', function() {
        charCount.textContent = this.value.length;
    });

    // Segment change → fetch count
    function fetchCount() {
        var segment = document.querySelector('input[name="segment_filter"]:checked').value;
        var url = previewUrl + '?segment=' + segment;

        inactiveDaysRow.style.display = segment === 'inactive' ? 'block' : 'none';

        if (segment === 'inactive') {
            url += '&inactive_days=' + (inactiveDaysInput.value || 30);
        }

        countEl.innerHTML = '<i class="bi bi-arrow-repeat" style="animation:spin 1s linear infinite;font-size:.85rem;color:#adb5bd;"></i>';

        fetch(url, { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var count = data.count || 0;
                countEl.textContent = count.toLocaleString('it-IT');
                neededEl.textContent = count.toLocaleString('it-IT');

                if (count > credits) {
                    alertEl.style.display = 'block';
                    submitBtn.disabled = true;
                } else if (count === 0) {
                    alertEl.style.display = 'none';
                    submitBtn.disabled = true;
                } else {
                    alertEl.style.display = 'none';
                    submitBtn.disabled = false;
                }
            })
            .catch(function() {
                countEl.textContent = '?';
            });
    }

    document.querySelectorAll('input[name="segment_filter"]').forEach(function(radio) {
        radio.addEventListener('change', fetchCount);
    });

    inactiveDaysInput.addEventListener('input', function() {
        clearTimeout(fetchTimer);
        fetchTimer = setTimeout(fetchCount, 500);
    });

    // Confirm before submit
    document.getElementById('broadcast-form').addEventListener('submit', function(e) {
        var count = parseInt(countEl.textContent.replace(/\./g, ''), 10) || 0;
        if (!confirm('Stai per inviare ' + count + ' email.\nVerranno scalati ' + count + ' crediti.\n\nConfermi?')) {
            e.preventDefault();
        }
    });

    // Highlight selected segment
    document.querySelectorAll('.segment-option input').forEach(function(radio) {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.segment-option').forEach(function(el) {
                el.style.borderColor = '#dee2e6';
                el.style.background = '';
            });
            if (this.checked) {
                this.closest('.segment-option').style.borderColor = '#00844A';
                this.closest('.segment-option').style.background = '#f0fdf4';
            }
        });
    });

    // Initial state
    document.querySelector('.segment-option').style.borderColor = '#00844A';
    document.querySelector('.segment-option').style.background = '#f0fdf4';

    // Initial fetch
    fetchCount();
})();
</script>
<style nonce="<?= csp_nonce() ?>">
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
@media (max-width: 768px) {
    #broadcast-form > div { grid-template-columns: 1fr !important; }
    #segment-radios { grid-template-columns: 1fr 1fr !important; }
}
</style>
