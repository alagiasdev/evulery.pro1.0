<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">Profilo</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1rem;">Gestisci il tuo account</p>

<div class="row g-4">
    <div class="col-lg-7">
        <form method="POST" action="<?= url('dashboard/profile') ?>">
            <?= csrf_field() ?>

            <!-- Dati personali -->
            <div class="card section-card">
                <div class="section-header">
                    <div class="section-icon" style="background:var(--brand);"><i class="bi bi-person"></i></div>
                    <div>
                        <div class="section-title">Dati personali</div>
                        <div class="section-subtitle">Nome e contatti del tuo account</div>
                    </div>
                </div>
                <div class="form-body">
                    <div class="row g-3">
                        <div class="col-md-6 field-row">
                            <label class="field-label">Nome *</label>
                            <input type="text" class="field-input" name="first_name" value="<?= e($user['first_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6 field-row">
                            <label class="field-label">Cognome *</label>
                            <input type="text" class="field-input" name="last_name" value="<?= e($user['last_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-12 field-row">
                            <label class="field-label">Email *</label>
                            <input type="email" class="field-input" name="email" value="<?= e($user['email'] ?? '') ?>" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cambio password -->
            <div class="card section-card">
                <div class="section-header">
                    <div class="section-icon" style="background:#5C6BC0;"><i class="bi bi-shield-lock"></i></div>
                    <div>
                        <div class="section-title">Cambia password</div>
                        <div class="section-subtitle">Lascia vuoto per mantenere la password attuale</div>
                    </div>
                </div>
                <div class="form-body">
                    <div class="row g-3">
                        <div class="col-12 field-row">
                            <label class="field-label">Password attuale</label>
                            <input type="password" class="field-input" name="current_password" autocomplete="current-password">
                        </div>
                        <div class="col-md-6 field-row">
                            <label class="field-label">Nuova password</label>
                            <input type="password" class="field-input" name="new_password" autocomplete="new-password">
                            <div class="field-hint">Minimo 8 caratteri, una maiuscola e un numero</div>
                        </div>
                        <div class="col-md-6 field-row">
                            <label class="field-label">Conferma password</label>
                            <input type="password" class="field-input" name="confirm_password" autocomplete="new-password">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save bar -->
            <div class="save-bar">
                <span class="save-hint"><i class="bi bi-info-circle me-1"></i>Le modifiche saranno applicate immediatamente</span>
                <button type="submit" class="btn-save"><i class="bi bi-check-circle me-1"></i> Salva profilo</button>
            </div>
        </form>
    </div>

    <div class="col-lg-5">
        <div class="card section-card">
            <div class="tip-card">
                <i class="bi bi-lightbulb" style="color:#FFC107;font-size:1.1rem;margin-top:.1rem;"></i>
                <div>
                    <div class="tip-title">Info account</div>
                    <div class="tip-text">
                        <strong>Ruolo:</strong> <?= e(role_label($user['role'] ?? '')) ?><br>
                        <strong>Ultimo accesso:</strong> <?= !empty($user['last_login_at']) ? format_date($user['last_login_at'], 'd/m/Y H:i') : 'Mai' ?><br>
                        <strong>Registrato il:</strong> <?= format_date($user['created_at'], 'd/m/Y') ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($plan)): ?>
        <?php
        $sub = $subscription ?? null;
        $cycle = $sub['billing_cycle'] ?? 'annual';
        $extraDisc = (float)($sub['extra_discount'] ?? 0);
        $calc = \App\Models\Plan::calculatePrice($plan, $cycle, $extraDisc);
        $cycleLabel = $cycle === 'semiannual' ? 'Semestrale' : 'Annuale';
        ?>
        <div class="card section-card" style="margin-top:1rem;">
            <div style="padding:1.25rem;">
                <!-- Header piano -->
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

                <!-- Dettagli abbonamento -->
                <?php if ($sub): ?>
                <?php $cyclePeriod = $cycle === 'semiannual' ? 'semestre' : 'anno'; ?>
                <div style="display:flex;gap:1.5rem;margin-bottom:1rem;font-size:.82rem;color:#6c757d;">
                    <div>
                        <div style="font-weight:600;color:#1a1d23;">&euro;<?= number_format($calc['total'], 2, ',', '.') ?> / <?= $cyclePeriod ?></div>
                        <div>&euro;<?= number_format($calc['monthly'], 2, ',', '.') ?>/mese</div>
                    </div>
                    <?php if ($sub['current_period_end']): ?>
                    <div>
                        <?php
                        $endTs = strtotime($sub['current_period_end']);
                        $daysLeft = max(0, (int)ceil(($endTs - time()) / 86400));
                        $isExpiring = $daysLeft <= 30;
                        ?>
                        <div style="font-weight:600;color:<?= $isExpiring ? '#E65100' : '#1a1d23' ?>;">
                            <?= date('d/m/Y', $endTs) ?>
                        </div>
                        <div>Scadenza <?php if ($isExpiring): ?><span style="color:#E65100;">(<?= $daysLeft ?>gg)</span><?php endif; ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Servizi -->
                <?php if (!empty($allServices)): ?>
                <?php
                    $includedKeys = array_column($planServices ?? [], 'key');
                    $sortedServices = $allServices;
                    usort($sortedServices, function ($a, $b) use ($includedKeys) {
                        $aIn = in_array($a['key'], $includedKeys) ? 0 : 1;
                        $bIn = in_array($b['key'], $includedKeys) ? 0 : 1;
                        return $aIn !== $bIn ? $aIn - $bIn : ($a['sort_order'] ?? 0) - ($b['sort_order'] ?? 0);
                    });
                ?>
                <div style="font-weight:600;font-size:.82rem;margin-bottom:.5rem;color:#495057;">Servizi</div>
                <div style="display:flex;flex-direction:column;gap:.35rem;margin-bottom:1rem;">
                    <?php foreach ($sortedServices as $svc): ?>
                    <?php $included = in_array($svc['key'], $includedKeys); ?>
                    <div style="display:flex;align-items:center;gap:.5rem;font-size:.8rem;<?= $included ? '' : 'opacity:.5;' ?>">
                        <?php if ($included): ?>
                        <i class="bi bi-check-circle-fill" style="color:var(--brand);font-size:.75rem;"></i>
                        <span><?= e($svc['name']) ?></span>
                        <?php else: ?>
                        <i class="bi bi-x-circle" style="color:#adb5bd;font-size:.75rem;"></i>
                        <span style="color:#adb5bd;"><?= e($svc['name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- CTA upgrade -->
                <div style="border-top:1px solid #eee;padding-top:.75rem;">
                    <div style="font-size:.78rem;color:#6c757d;margin-bottom:.5rem;">
                        Vuoi accedere a pi&ugrave; funzionalit&agrave;?
                    </div>
                    <a href="mailto:<?= e(env('SUPPORT_EMAIL', '')) ?>" class="btn btn-outline-success btn-sm" style="width:100%;">
                        <i class="bi bi-envelope me-1"></i> Contatta il supporto per un upgrade
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>