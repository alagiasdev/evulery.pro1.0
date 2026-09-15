<?php
/**
 * Admin -> Impostazioni. Configurazioni globali di Evulery (non di un tenant).
 * Classi e struttura sono quelle delle altre pagine admin: adm-card /
 * adm-card-hdr / adm-form-*, che collassano da sole a una colonna sotto il
 * breakpoint (vedi admin.css).
 */
$mancaBanca = ($s['bank_iban'] ?? '') === '' || ($s['bank_holder'] ?? '') === '';
?>

<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title"><i class="bi bi-gear me-2"></i>Impostazioni</h1>
        <p class="admin-page-sub" style="margin-bottom:0;">
            Quello che vale per tutto Evulery, non per un singolo ristorante.
        </p>
    </div>
</div>

<?php if ($mancaBanca): ?>
<div style="background:var(--warn-bg);color:var(--warn-text);border-left:3px solid var(--warn-text);
            border-radius:8px;padding:.7rem .9rem;font-size:.84rem;margin-bottom:1.25rem;">
    <i class="bi bi-exclamation-triangle-fill me-1"></i>
    <b>Intestatario e IBAN non sono ancora compilati.</b>
    Finché mancano, il checkout non ha coordinate da mostrare al cliente.
</div>
<?php endif; ?>

<form method="POST" action="<?= url('admin/settings') ?>">
    <?= csrf_field() ?>

    <div class="adm-edit-grid">
        <div>

            <!-- Coordinate bancarie -->
            <div class="adm-card" style="margin-bottom:1.25rem;">
                <div class="adm-card-hdr">
                    <span class="adm-card-hdr-title"><i class="bi bi-bank me-1"></i> Coordinate per i bonifici</span>
                </div>
                <div class="adm-card-body">

                    <div class="adm-form-full">
                        <label class="adm-form-label">Intestatario *</label>
                        <input type="text" class="adm-form-input" name="bank_holder"
                               value="<?= e($s['bank_holder'] ?? '') ?>"
                               placeholder="Come risulta alla banca">
                        <div class="adm-form-hint">
                            Se non combacia con l'intestazione del conto, alcune banche rifiutano il bonifico.
                        </div>
                    </div>

                    <div class="adm-form-full">
                        <label class="adm-form-label">IBAN *</label>
                        <input type="text" class="adm-form-input" name="bank_iban"
                               value="<?= e($ibanVisibile) ?>"
                               placeholder="IT.. .... .... .... .... .... ..."
                               style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace;letter-spacing:.5px;">
                        <div class="adm-form-hint">
                            Gli spazi non contano. Viene controllato davvero (mod-97): un IBAN con una
                            cifra sbagliata viene rifiutato qui, non dalla banca fra tre giorni.
                        </div>
                    </div>

                    <div class="adm-form-row" style="margin-bottom:0;">
                        <div>
                            <label class="adm-form-label">Banca</label>
                            <input type="text" class="adm-form-input" name="bank_name"
                                   value="<?= e($s['bank_name'] ?? '') ?>">
                        </div>
                        <div>
                            <label class="adm-form-label">BIC / SWIFT</label>
                            <input type="text" class="adm-form-input" name="bank_bic"
                                   value="<?= e($s['bank_bic'] ?? '') ?>" placeholder="Facoltativo">
                            <div class="adm-form-hint">Serve solo per bonifici dall'estero.</div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Fatturazione -->
            <div class="adm-card" style="margin-bottom:1.25rem;">
                <div class="adm-card-hdr">
                    <span class="adm-card-hdr-title"><i class="bi bi-receipt me-1"></i> Fatturazione</span>
                </div>
                <div class="adm-card-body">

                    <div class="adm-form-full">
                        <label class="adm-form-label">Dicitura fiscale</label>
                        <textarea class="adm-form-input" name="tax_notice" rows="2"
                                  style="resize:vertical;"><?= e($s['tax_notice'] ?? '') ?></textarea>
                        <div class="adm-form-hint">
                            Compare sotto il totale nel checkout e nelle email. Fattela dare dal
                            commercialista: è l'unica riga di questa pagina che non possiamo scrivere noi.
                        </div>
                    </div>

                    <div class="adm-form-row" style="margin-bottom:0;">
                        <div>
                            <label class="adm-form-label">Marca da bollo</label>
                            <input type="text" class="adm-form-input" name="stamp_duty_amount"
                                   value="<?= e($s['stamp_duty_amount'] ?? '2.00') ?>">
                            <div class="adm-form-hint">In euro. <b>Metti 0 e sparisce da tutto.</b></div>
                        </div>
                        <div>
                            <label class="adm-form-label">Si applica sopra</label>
                            <input type="text" class="adm-form-input" name="stamp_duty_threshold"
                                   value="<?= e($s['stamp_duty_threshold'] ?? '77.47') ?>">
                            <div class="adm-form-hint">
                                In euro. Sotto questa cifra il bollo non viene addebitato.
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Tolleranza -->
            <div class="adm-card" style="margin-bottom:1.25rem;">
                <div class="adm-card-hdr">
                    <span class="adm-card-hdr-title"><i class="bi bi-hourglass-split me-1"></i> Tolleranza sui bonifici</span>
                </div>
                <div class="adm-card-body">

                    <div class="adm-form-row" style="margin-bottom:0;">
                        <div>
                            <label class="adm-form-label">Giorni di tolleranza</label>
                            <input type="number" class="adm-form-input" name="grace_days" min="0"
                                   max="<?= (int)$graceMax ?>"
                                   value="<?= e($s['grace_days'] ?? '10') ?>">
                            <div class="adm-form-hint">Massimo <?= (int)$graceMax ?>.</div>
                        </div>
                        <div>
                            <label class="adm-form-label">Email mittente</label>
                            <input type="email" class="adm-form-input" name="billing_email_from"
                                   value="<?= e($s['billing_email_from'] ?? '') ?>"
                                   placeholder="Se vuoto usa SUPPORT_EMAIL">
                            <div class="adm-form-hint">Da cui partono le comunicazioni di pagamento.</div>
                        </div>
                    </div>

                    <div style="background:var(--admin-accent-bg);color:var(--admin-accent);border-radius:8px;
                                padding:.65rem .8rem;font-size:.79rem;line-height:1.5;margin-top:1rem;">
                        Per quanti giorni un ristorante continua a lavorare dopo aver ordinato, mentre il
                        bonifico viaggia. <b>Sotto i 7 giorni un ordine del venerdì sera non ce la fa:</b>
                        la banca lo lavora il lunedì, i soldi arrivano il martedì.
                    </div>

                </div>
            </div>

            <button type="submit" class="adm-btn adm-btn-primary">
                <i class="bi bi-check-lg me-1"></i> Salva
            </button>

        </div>

        <!-- Colonna di destra: dove finiscono questi valori -->
        <div>
            <div class="adm-card">
                <div class="adm-card-hdr">
                    <span class="adm-card-hdr-title"><i class="bi bi-signpost-2 me-1"></i> Dove finiscono</span>
                </div>
                <div class="adm-card-body" style="font-size:.8rem;color:#6c757d;line-height:1.55;">
                    <p style="margin:0 0 .8rem;">
                        <b style="color:#1a1d23;">Intestatario e IBAN</b><br>
                        Nel secondo passo del checkout e nell'email con le coordinate. Il cliente li copia
                        da lì per fare il bonifico.
                    </p>
                    <p style="margin:0 0 .8rem;">
                        <b style="color:#1a1d23;">Dicitura fiscale</b><br>
                        Sotto il totale, dove starebbe l'IVA.
                    </p>
                    <p style="margin:0 0 .8rem;">
                        <b style="color:#1a1d23;">Bollo e soglia</b><br>
                        Calcolati <b>ordine per ordine</b>, non per piano: Starter mensile a 49 &euro; sta
                        sotto la soglia e non lo paga, Professional a 79 &euro; la supera e lo paga.
                    </p>
                    <p style="margin:0;">
                        <b style="color:#1a1d23;">Tolleranza</b><br>
                        Non sposta la scadenza dell'abbonamento: resta quella fino a cui il cliente ha
                        pagato. È un lasciapassare a parte, legato all'ordine aperto.
                    </p>
                </div>
            </div>
        </div>

    </div>
</form>
