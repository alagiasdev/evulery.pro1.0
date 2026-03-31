<?php
$step = $step ?? '1';
?>

<div class="page-back">
    <a href="<?= url('dashboard/customers') ?>">
        <i class="bi bi-arrow-left"></i> Torna ai clienti
    </a>
</div>

<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">
    <i class="bi bi-cloud-upload me-1" style="color:var(--brand);"></i> Importa clienti
</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1.25rem;">Carica un file CSV per importare i tuoi clienti</p>

<?php if ($step === '1'): ?>
<!-- ===== STEP 1: Upload ===== -->
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card section-card">
            <div class="section-header">
                <div class="section-icon" style="background:var(--brand);"><i class="bi bi-file-earmark-spreadsheet"></i></div>
                <div>
                    <div class="section-title">Carica file CSV</div>
                    <div class="section-subtitle">Formati supportati: .csv (separatore virgola, punto e virgola o tab)</div>
                </div>
            </div>
            <div class="form-body">
                <form method="POST" action="<?= url('dashboard/customers/import') ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="upload">

                    <div id="dropZone" style="border:2px dashed var(--brand); border-radius:14px; padding:2.5rem 1.5rem; text-align:center; cursor:pointer; transition: all .2s; background:#fafff8;">
                        <i class="bi bi-cloud-arrow-up" style="font-size:2.5rem; color:var(--brand); display:block; margin-bottom:.5rem;"></i>
                        <div style="font-size:.9rem; font-weight:600; margin-bottom:.25rem;">Trascina il file qui</div>
                        <div style="font-size:.75rem; color:#6c757d; margin-bottom:.75rem;">oppure clicca per selezionare</div>
                        <input type="file" name="csv_file" id="csvFile" accept=".csv,.txt" required
                            style="position:absolute; opacity:0; width:0; height:0;">
                        <div id="fileLabel" style="font-size:.78rem; color:var(--brand); font-weight:600; display:none;"></div>
                    </div>

                    <div style="font-size:.68rem; color:#adb5bd; margin-top:.75rem;">
                        <i class="bi bi-info-circle me-1"></i> Max 5 MB. La prima riga deve contenere le intestazioni (Nome, Cognome, Email, Telefono).
                    </div>

                    <button type="submit" class="btn-save mt-3" id="btnUpload" disabled>
                        <i class="bi bi-arrow-right me-1"></i> Carica e continua
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card section-card">
            <div class="card-body">
                <div style="font-size:.82rem; font-weight:700; margin-bottom:.75rem;"><i class="bi bi-lightbulb me-1" style="color:#FFC107;"></i> Formato CSV</div>
                <div style="background:#f8f9fa; border-radius:8px; padding:.75rem; font-family:monospace; font-size:.7rem; line-height:1.6; margin-bottom:.75rem; overflow-x:auto;">
                    Nome;Cognome;Email;Telefono<br>
                    Mario;Rossi;mario@email.it;333 1234567<br>
                    Laura;Bianchi;laura@email.it;340 9876543
                </div>
                <div style="font-size:.72rem; color:#6c757d;">
                    <div class="mb-1"><i class="bi bi-check-circle text-success me-1"></i> Separatore automatico (virgola, punto e virgola, tab)</div>
                    <div class="mb-1"><i class="bi bi-check-circle text-success me-1"></i> Deduplica automatica per email/telefono</div>
                    <div class="mb-1"><i class="bi bi-check-circle text-success me-1"></i> I clienti gi&agrave; presenti non vengono modificati</div>
                    <div class="mb-1"><i class="bi bi-check-circle text-success me-1"></i> Mappatura colonne flessibile</div>
                    <div><i class="bi bi-check-circle text-success me-1"></i> Campi extra: nascita, ultima visita, presenze, tag, consenso, note</div>
                </div>
                <hr>
                <div style="font-size:.72rem; color:#6c757d;">
                    <i class="bi bi-shield-check me-1" style="color:var(--brand);"></i>
                    <strong>GDPR:</strong> Assicurati che i clienti abbiano dato il consenso al trattamento dati e alla ricezione di comunicazioni email prima di importarli.
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($step === 'map'): ?>
<!-- ===== STEP MAP: Column Mapping + Preview ===== -->
<div class="card section-card">
    <div class="section-header">
        <div class="section-icon" style="background:#1565C0;"><i class="bi bi-columns-gap"></i></div>
        <div>
            <div class="section-title">Mappa le colonne</div>
            <div class="section-subtitle">Indica quale colonna corrisponde a ciascun campo</div>
        </div>
    </div>
    <div class="form-body">
        <form method="POST" action="<?= url('dashboard/customers/import') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="confirm">

            <div style="font-size:.78rem;font-weight:600;margin-bottom:.4rem;"><i class="bi bi-person-vcard me-1"></i> Campi principali</div>
            <div class="row g-3 mb-3">
                <?php
                $fields = [
                    'col_first_name'        => ['label' => 'Nome', 'icon' => 'bi-person', 'key' => 'first_name'],
                    'col_last_name'         => ['label' => 'Cognome', 'icon' => 'bi-person-badge', 'key' => 'last_name'],
                    'col_email'             => ['label' => 'Email', 'icon' => 'bi-envelope', 'key' => 'email'],
                    'col_phone'             => ['label' => 'Telefono', 'icon' => 'bi-telephone', 'key' => 'phone'],
                ];
                $fieldsExtra = [
                    'col_birthday'          => ['label' => 'Data nascita', 'icon' => 'bi-cake2', 'key' => 'birthday'],
                    'col_last_visit'        => ['label' => 'Ultima visita', 'icon' => 'bi-calendar-check', 'key' => 'last_visit'],
                    'col_total_bookings'    => ['label' => 'Presenze', 'icon' => 'bi-hash', 'key' => 'total_bookings'],
                    'col_tags'              => ['label' => 'Tag', 'icon' => 'bi-tags', 'key' => 'tags'],
                    'col_marketing_consent' => ['label' => 'Consenso marketing', 'icon' => 'bi-envelope-check', 'key' => 'marketing_consent'],
                    'col_notes'             => ['label' => 'Note', 'icon' => 'bi-sticky', 'key' => 'notes'],
                ];
                ?>
                <?php foreach ($fields as $inputName => $field): ?>
                <div class="col-md-3">
                    <label class="field-label"><i class="bi <?= $field['icon'] ?> me-1"></i> <?= $field['label'] ?></label>
                    <select class="form-select form-select-sm" name="<?= $inputName ?>">
                        <option value="-1">— Non presente —</option>
                        <?php foreach ($headers as $i => $h): ?>
                        <option value="<?= $i ?>" <?= ($mapping[$field['key']] ?? -1) === $i ? 'selected' : '' ?>><?= e($h) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endforeach; ?>
            </div>

            <div style="font-size:.78rem;font-weight:600;margin-bottom:.4rem;"><i class="bi bi-plus-circle me-1" style="color:var(--brand);"></i> Campi aggiuntivi <span style="font-weight:400;color:#6c757d;">(opzionali)</span></div>
            <div class="row g-3 mb-3">
                <?php foreach ($fieldsExtra as $inputName => $field): ?>
                <div class="col-md-4 col-lg-2">
                    <label class="field-label"><i class="bi <?= $field['icon'] ?> me-1"></i> <?= $field['label'] ?></label>
                    <select class="form-select form-select-sm" name="<?= $inputName ?>">
                        <option value="-1">— Non presente —</option>
                        <?php foreach ($headers as $i => $h): ?>
                        <option value="<?= $i ?>" <?= ($mapping[$field['key']] ?? -1) === $i ? 'selected' : '' ?>><?= e($h) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($preview)): ?>
            <div style="font-size:.78rem; font-weight:600; margin-bottom:.4rem;"><i class="bi bi-eye me-1"></i> Anteprima (prime <?= count($preview) ?> righe)</div>
            <div class="table-responsive" style="max-height:300px; overflow:auto; border:1px solid #eee; border-radius:8px;">
                <table class="table table-sm mb-0" style="font-size:.72rem;">
                    <thead style="position:sticky;top:0;background:#f8f9fa;">
                        <tr>
                            <?php foreach ($headers as $h): ?>
                            <th style="white-space:nowrap; padding:.4rem .5rem;"><?= e($h) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preview as $row): ?>
                        <tr>
                            <?php foreach ($headers as $i => $h): ?>
                            <td style="padding:.3rem .5rem;"><?= e($row[$i] ?? '') ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <div class="mt-3 p-3" style="background:#fff8e1; border-radius:10px;">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="gdprConsent" required>
                    <label class="form-check-label" for="gdprConsent" style="font-size:.78rem;">
                        Confermo che i clienti importati hanno dato il consenso al trattamento dei dati personali e alla ricezione di comunicazioni email ai sensi del GDPR.
                    </label>
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <a href="<?= url('dashboard/customers/import') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Torna indietro
                </a>
                <button type="submit" class="btn-save">
                    <i class="bi bi-cloud-check me-1"></i> Importa clienti
                </button>
            </div>
        </form>
    </div>
</div>

<?php endif; ?>

<script nonce="<?= csp_nonce() ?>">
(function() {
    var dropZone = document.getElementById('dropZone');
    var fileInput = document.getElementById('csvFile');
    var fileLabel = document.getElementById('fileLabel');
    var btnUpload = document.getElementById('btnUpload');

    if (!dropZone || !fileInput) return;

    dropZone.addEventListener('click', function() { fileInput.click(); });

    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        dropZone.style.borderColor = '#006837';
        dropZone.style.background = '#e8f5ee';
    });
    dropZone.addEventListener('dragleave', function() {
        dropZone.style.borderColor = 'var(--brand)';
        dropZone.style.background = '#fafff8';
    });
    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        dropZone.style.borderColor = 'var(--brand)';
        dropZone.style.background = '#fafff8';
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            showFile(e.dataTransfer.files[0]);
        }
    });

    fileInput.addEventListener('change', function() {
        if (this.files.length) showFile(this.files[0]);
    });

    function showFile(file) {
        fileLabel.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
        fileLabel.style.display = 'block';
        if (btnUpload) btnUpload.disabled = false;
    }
})();
</script>
