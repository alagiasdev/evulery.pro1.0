<?php
/**
 * Dashboard -> Abbonamento: il piano e i dati per la fattura.
 *
 * Una pagina, un argomento, UN pulsante di salvataggio — come le altre undici
 * pagine della dashboard. I dati di fatturazione stavano nel Profilo e
 * portavano a due "Salva" affiancati: chi aveva imparato la regola del
 * prodotto premeva quello sbagliato.
 *
 * Qui atterreranno il checkout e lo storico delle fatture (lotti D e G).
 */
$bill = $tenant ?? [];
$billCompleto = !empty($bill['billing_name']) && !empty($bill['billing_vat'])
    && !empty($bill['billing_address']) && !empty($bill['billing_city'])
    && !empty($bill['billing_zip']) && !empty($bill['billing_province'])
    && (!empty($bill['billing_sdi']) || !empty($bill['billing_pec']));
?>

<div class="page-back">
    <a href="<?= url('dashboard/profile') ?>"><i class="bi bi-arrow-left"></i> Torna al profilo</a>
</div>

<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">Abbonamento</h2>
<p style="font-size:.82rem; color:var(--mute); margin-bottom:1rem;">Il tuo piano e i dati per la fattura</p>

<div class="row g-4">
    <div class="col-lg-7">

        <form method="POST" action="<?= url('dashboard/abbonamento/billing') ?>">
            <?= csrf_field() ?>

            <div class="card section-card">
                <div class="section-header">
                    <div class="section-icon" style="background:#455A64;"><i class="bi bi-receipt"></i></div>
                    <div>
                        <div class="section-title">
                            Dati di fatturazione
                            <?php if (!$billCompleto): ?>
                            <span style="display:inline-block;margin-left:.4rem;padding:.1rem .5rem;border-radius:100px;
                                         font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;
                                         background:var(--warn-bg);color:var(--warn-text);vertical-align:middle;">da completare</span>
                            <?php endif; ?>
                        </div>
                        <div class="section-subtitle">Servono per la fattura del tuo abbonamento. Te li chiediamo una volta sola.</div>
                    </div>
                </div>
                <div class="form-body">
                    <div class="row g-3">
                        <div class="col-12 field-row">
                            <label class="field-label">Ragione sociale</label>
                            <input type="text" class="field-input" name="billing_name"
                                   value="<?= e($bill['billing_name'] ?? '') ?>"
                                   placeholder="Es. Trattoria da Mario S.r.l.">
                            <div class="field-hint">Come sta scritta sulla visura, non l&rsquo;insegna del locale.</div>
                        </div>

                        <div class="col-md-6 field-row">
                            <label class="field-label">Partita IVA</label>
                            <input type="text" class="field-input" name="billing_vat"
                                   value="<?= e($bill['billing_vat'] ?? '') ?>" placeholder="11 cifre" inputmode="numeric">
                        </div>
                        <div class="col-md-6 field-row">
                            <label class="field-label">Codice fiscale</label>
                            <input type="text" class="field-input" name="billing_tax_code"
                                   value="<?= e($bill['billing_tax_code'] ?? '') ?>" placeholder="Se diverso dalla P.IVA">
                        </div>

                        <div class="col-12 field-row">
                            <label class="field-label">Indirizzo della sede legale</label>
                            <input type="text" class="field-input" name="billing_address"
                                   value="<?= e($bill['billing_address'] ?? '') ?>" placeholder="Via e numero civico">
                        </div>
                        <div class="col-md-6 field-row">
                            <label class="field-label">Citt&agrave;</label>
                            <input type="text" class="field-input" name="billing_city"
                                   value="<?= e($bill['billing_city'] ?? '') ?>">
                        </div>
                        <div class="col-md-3 col-6 field-row">
                            <label class="field-label">CAP</label>
                            <input type="text" class="field-input" name="billing_zip" maxlength="5" inputmode="numeric"
                                   value="<?= e($bill['billing_zip'] ?? '') ?>">
                        </div>
                        <div class="col-md-3 col-6 field-row">
                            <label class="field-label">Provincia</label>
                            <input type="text" class="field-input" name="billing_province" maxlength="2"
                                   value="<?= e($bill['billing_province'] ?? '') ?>" placeholder="NA"
                                   style="text-transform:uppercase;">
                        </div>

                        <div class="col-md-6 field-row">
                            <label class="field-label">Codice SDI</label>
                            <input type="text" class="field-input" name="billing_sdi" maxlength="7"
                                   value="<?= e($bill['billing_sdi'] ?? '') ?>" placeholder="ABC1234"
                                   style="text-transform:uppercase;">
                        </div>
                        <div class="col-md-6 field-row">
                            <label class="field-label">PEC</label>
                            <input type="email" class="field-input" name="billing_pec"
                                   value="<?= e($bill['billing_pec'] ?? '') ?>" placeholder="pec@esempio.it">
                        </div>

                        <div class="col-12">
                            <div style="background:var(--warn-bg);color:#5D4037;border-radius:8px;
                                        padding:.65rem .8rem;font-size:.79rem;line-height:1.5;">
                                <b>Serve uno dei due, non tutti e due.</b> Se hai un gestionale che riceve le
                                fatture elettroniche ti ha dato un <b>codice SDI</b>; altrimenti va bene la
                                <b>PEC</b>. Nel dubbio chiedi al commercialista: &egrave; la risposta di trenta secondi.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="save-bar">
                <span class="save-hint"><i class="bi bi-lock me-1"></i>Restano qui: li usiamo solo per la tua fattura</span>
                <button type="submit" class="btn-save"><i class="bi bi-check-circle me-1"></i> Salva</button>
            </div>
        </form>

    </div>

    <div class="col-lg-5">
        <?php if (!empty($plan)): ?>
        <?php
        $sub = $subscription ?? null;
        $cycle = $sub['billing_cycle'] ?? 'annual';
        $calc = \App\Models\Plan::calculatePrice($plan, $cycle, (float)($sub['extra_discount'] ?? 0));
        $cyclePeriod = ['monthly' => 'mese', 'semiannual' => 'semestre'][$cycle] ?? 'anno';
        ?>
        <div class="card section-card">
            <div style="padding:1.25rem;">
                <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;">
                    <div style="width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:<?= e($plan['color']) ?>15;color:<?= e($plan['color']) ?>;">
                        <i class="bi bi-star-fill" style="font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:1rem;">Il tuo piano</div>
                        <span style="display:inline-block;padding:.15rem .6rem;border-radius:6px;font-size:.75rem;font-weight:600;background:<?= e($plan['color']) ?>15;color:<?= e($plan['color']) ?>;">
                            <?= e($plan['name']) ?>
                        </span>
                    </div>
                </div>

                <?php if ($sub): ?>
                <div style="display:flex;gap:1.5rem;margin-bottom:1rem;font-size:.82rem;color:var(--mute);">
                    <div>
                        <div style="font-weight:600;color:var(--ink);">&euro;<?= number_format($calc['total'], 2, ',', '.') ?> / <?= $cyclePeriod ?></div>
                        <?php if ($cycle !== 'monthly'): ?>
                        <div>&euro;<?= number_format($calc['monthly'], 2, ',', '.') ?>/mese</div>
                        <?php endif; ?>
                    </div>
                    <?php if ($sub['current_period_end']): ?>
                    <div>
                        <?php
                        $endTs = strtotime($sub['current_period_end']);
                        $daysLeft = max(0, (int)ceil(($endTs - time()) / 86400));
                        $isExpiring = $daysLeft <= 30;
                        ?>
                        <div style="font-weight:600;color:<?= $isExpiring ? 'var(--pending-text)' : 'var(--ink)' ?>;">
                            <?= date('d/m/Y', $endTs) ?>
                        </div>
                        <div>Scadenza <?php if ($isExpiring): ?><span style="color:var(--pending-text);">(<?= $daysLeft ?>gg)</span><?php endif; ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div style="border-top:1px solid #eee;padding-top:.75rem;">
                    <div style="font-size:.78rem;color:var(--mute);margin-bottom:.5rem;">
                        Il rinnovo non &egrave; automatico: ti avvisiamo prima della scadenza.
                    </div>
                    <a href="mailto:<?= e(env('SUPPORT_EMAIL', '')) ?>" class="btn btn-outline-success btn-sm" style="width:100%;">
                        <i class="bi bi-envelope me-1"></i> Scrivici per rinnovo o cambio piano
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card section-card" style="margin-top:1rem;">
            <div class="tip-card">
                <i class="bi bi-lightbulb" style="color:#FFC107;font-size:1.1rem;margin-top:.1rem;"></i>
                <div>
                    <div class="tip-title">A cosa servono</div>
                    <div class="tip-text">
                        Sono i dati che finiscono sulla fattura del tuo abbonamento. Li compili una
                        volta: ai rinnovi successivi non te li richiediamo pi&ugrave;.<br><br>
                        Se cambiano, correggili qui: valgono per le fatture successive. Quelle gi&agrave;
                        emesse non cambiano.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
