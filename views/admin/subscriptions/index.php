<?php
$tabs = [
    ['key' => 'subscriptions', 'label' => 'Abbonamenti', 'url' => url('admin/subscriptions')],
    ['key' => 'plans',         'label' => 'Piani',       'url' => url('admin/subscriptions/plans')],
    ['key' => 'services',      'label' => 'Servizi',     'url' => url('admin/subscriptions/services')],
];
?>

<h1 class="admin-page-title">Abbonamenti</h1>
<p class="admin-page-sub">Gestisci abbonamenti, piani e catalogo servizi</p>

<!-- Tabs -->
<div class="adm-tabs">
    <?php foreach ($tabs as $tab): ?>
    <a class="adm-tab <?= ($activeTab ?? '') === $tab['key'] ? 'active' : '' ?>" href="<?= $tab['url'] ?>">
        <?= $tab['label'] ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Stats -->
<div class="admin-stats">
    <div class="admin-stat">
        <div class="admin-stat-icon" style="background:#F3E5F5;color:#7B1FA2;">
            <i class="bi bi-currency-euro"></i>
        </div>
        <div>
            <div class="admin-stat-value">&euro;<?= number_format($mrr, 0, ',', '.') ?></div>
            <div class="admin-stat-label">MRR</div>
        </div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-icon" style="background:#E8F5E9;color:#2E7D32;">
            <i class="bi bi-check2-circle"></i>
        </div>
        <div>
            <div class="admin-stat-value"><?= $activeCount ?></div>
            <div class="admin-stat-label">Attivi</div>
        </div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-icon" style="background:#E3F2FD;color:#1565C0;">
            <i class="bi bi-hourglass-split"></i>
        </div>
        <div>
            <div class="admin-stat-value"><?= $trialCount ?></div>
            <div class="admin-stat-label">Trial</div>
        </div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-icon" style="background:#FFF3E0;color:#E65100;">
            <i class="bi bi-exclamation-triangle"></i>
        </div>
        <div>
            <div class="admin-stat-value"><?= $expiringCount ?></div>
            <div class="admin-stat-label">In scadenza (30gg)</div>
        </div>
    </div>
</div>

<!-- Filter pills + Table -->
<div class="adm-card">
    <div class="adm-card-hdr">
        <span class="adm-card-hdr-title">Elenco abbonamenti</span>
        <div class="adm-filter-pills">
            <a class="adm-pill <?= $filter === '' ? 'active' : '' ?>" href="<?= url('admin/subscriptions') ?>">Tutti</a>
            <a class="adm-pill <?= $filter === 'active' ? 'active' : '' ?>" href="<?= url('admin/subscriptions?filter=active') ?>">Attivi</a>
            <a class="adm-pill <?= $filter === 'trialing' ? 'active' : '' ?>" href="<?= url('admin/subscriptions?filter=trialing') ?>">Trial</a>
            <a class="adm-pill <?= $filter === 'expiring' ? 'active' : '' ?>" href="<?= url('admin/subscriptions?filter=expiring') ?>">In scadenza</a>
            <a class="adm-pill <?= $filter === 'cancelled' ? 'active' : '' ?>" href="<?= url('admin/subscriptions?filter=cancelled') ?>">Scaduti</a>
        </div>
    </div>
    <div class="adm-table-wrap">
        <table class="adm-table">
            <thead>
                <tr>
                    <th>Ristorante</th>
                    <th>Piano</th>
                    <th>Prezzo ciclo</th>
                    <th>Crediti Email</th>
                    <th>Crediti SMS</th>
                    <th>Stato</th>
                    <th>Scadenza</th>
                    <th style="text-align:right;">Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subscriptions)): ?>
                <tr>
                    <td colspan="8" class="adm-empty">Nessun abbonamento trovato.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($subscriptions as $s): ?>
                <tr>
                    <td class="cell-name"><?= e($s['tenant_name']) ?></td>
                    <td>
                        <?php if ($s['plan_name']): ?>
                        <span class="adm-badge-plan" style="background:<?= e($s['plan_color']) ?>15;color:<?= e($s['plan_color']) ?>;">
                            <?= e($s['plan_name']) ?>
                        </span>
                        <?php else: ?>
                        <span class="adm-badge adm-badge-inactive"><?= e(ucfirst($s['plan'])) ?></span>
                        <?php endif; ?>
                    </td>
                    <?php
                    $cycle = $s['billing_cycle'] ?? 'annual';
                    $extraDisc = (float)($s['extra_discount'] ?? 0);
                    $planForCalc = array_merge($s, ['price' => $s['plan_price'] ?? $s['price']]);
                    $calcPrice = \App\Models\Plan::calculatePrice($planForCalc, $cycle, $extraDisc);
                    $cycleLabel = $cycle === 'semiannual' ? '6 mesi' : '12 mesi';
                    ?>
                    <td>
                        <div style="font-weight:600;">&euro;<?= number_format($calcPrice['total'], 2, ',', '.') ?></div>
                        <div style="font-size:.68rem;color:#6c757d;">
                            <?= $cycleLabel ?>
                            <?php if ($extraDisc > 0): ?>
                            &middot; <span style="color:#2E7D32;">-<?= number_format($extraDisc, 0) ?>%</span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:.65rem;color:#adb5bd;">&euro;<?= number_format($calcPrice['monthly'], 2, ',', '.') ?>/mese</div>
                    </td>
                    <td>
                        <?php
                        $ec = (int)$s['email_credits'];
                        $ecColor = $ec <= 0 ? '#adb5bd' : ($ec < 50 ? '#E65100' : '#1a1d23');
                        $ecLabel = $ec <= 0 ? 'esauriti' : ($ec < 50 ? 'quasi esauriti!' : 'rimasti');
                        ?>
                        <span style="font-weight:600;color:<?= $ecColor ?>;"><?= $ec ?></span>
                        <span style="font-size:.68rem;color:<?= $ec < 50 && $ec > 0 ? '#E65100' : '#adb5bd' ?>;"><?= $ecLabel ?></span>
                    </td>
                    <td>
                        <?php
                        $sc = (int)$s['sms_credits'];
                        $scColor = $sc <= 0 ? '#adb5bd' : ($sc < 20 ? '#E65100' : '#1a1d23');
                        $scLabel = $sc <= 0 ? 'esauriti' : ($sc < 20 ? 'quasi esauriti!' : 'rimasti');
                        ?>
                        <span style="font-weight:600;color:<?= $scColor ?>;"><?= $sc ?></span>
                        <span style="font-size:.68rem;color:<?= $sc < 20 && $sc > 0 ? '#E65100' : '#adb5bd' ?>;"><?= $scLabel ?></span>
                    </td>
                    <td>
                        <?php if ($s['status'] === 'active'): ?>
                            <?php
                            $endTs2 = $s['current_period_end'] ? strtotime($s['current_period_end']) : null;
                            $isExpired2  = $endTs2 && $endTs2 < time();
                            $isExpiring2 = $endTs2 && !$isExpired2 && $endTs2 <= strtotime('+30 days');
                            ?>
                            <?php if ($isExpired2): ?>
                                <span class="adm-badge adm-badge-inactive">Scaduto</span>
                            <?php elseif ($isExpiring2): ?>
                                <span class="adm-badge adm-badge-warning">In scadenza</span>
                            <?php else: ?>
                                <span class="adm-badge adm-badge-active">Attivo</span>
                            <?php endif; ?>
                        <?php elseif ($s['status'] === 'trialing'): ?>
                            <?php
                            $daysLeft = $s['current_period_end'] ? max(0, (int)ceil((strtotime($s['current_period_end']) - time()) / 86400)) : 0;
                            ?>
                            <span class="adm-badge adm-badge-trial">Trial (<?= $daysLeft ?>gg)</span>
                        <?php elseif ($s['status'] === 'past_due'): ?>
                            <span class="adm-badge adm-badge-warning">Non pagato</span>
                        <?php else: ?>
                            <span class="adm-badge adm-badge-inactive"><?= e(ucfirst($s['status'])) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="cell-date">
                        <?php if ($s['current_period_end']): ?>
                            <?php
                            $endTs = strtotime($s['current_period_end']);
                            $isExpiring = $endTs <= strtotime('+7 days');
                            $isExpired  = $endTs < time();
                            ?>
                            <span style="<?= $isExpired ? 'color:#C62828;font-weight:600;' : ($isExpiring ? 'color:#E65100;font-weight:600;' : 'color:#6c757d;') ?>">
                                <?= date('d/m/Y', $endTs) ?>
                            </span>
                        <?php else: ?>
                            &mdash;
                        <?php endif; ?>
                    </td>
                    <td class="cell-actions">
                        <button type="button" class="adm-action-btn" title="Cambia piano"
                                data-bs-toggle="collapse" data-bs-target="#changePlan<?= $s['id'] ?>">
                            <i class="bi bi-arrow-up-circle"></i>
                        </button>
                    </td>
                </tr>
                <!-- Inline edit subscription row -->
                <tr class="collapse" id="changePlan<?= $s['id'] ?>">
                    <td colspan="8" style="background:#fafbfc;padding:.75rem 1.25rem;">
                        <form method="POST" action="<?= url("admin/subscriptions/{$s['id']}/change-plan") ?>">
                            <?= csrf_field() ?>
                            <div style="font-size:.82rem;font-weight:700;color:#495057;margin-bottom:.5rem;">
                                Modifica abbonamento: <?= e($s['tenant_name']) ?>
                            </div>
                            <div style="display:flex;align-items:flex-end;gap:.75rem;flex-wrap:wrap;">
                                <div>
                                    <label class="adm-form-label">Piano</label>
                                    <select name="plan_id" class="adm-form-input" style="min-width:180px;">
                                        <?php foreach ($plans as $p): ?>
                                        <option value="<?= $p['id'] ?>" <?= (int)($s['plan_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>>
                                            <?= e($p['name']) ?> (&euro;<?= number_format($p['price'], 0, ',', '.') ?>/mese)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="adm-form-label">Ciclo</label>
                                    <?php $sCycle = $s['billing_cycle'] ?? 'annual'; ?>
                                    <select name="billing_cycle" class="adm-form-input" style="min-width:120px;">
                                        <option value="semiannual" <?= $sCycle === 'semiannual' ? 'selected' : '' ?>>Semestrale</option>
                                        <option value="annual" <?= $sCycle === 'annual' ? 'selected' : '' ?>>Annuale</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="adm-form-label">Sconto extra %</label>
                                    <input type="number" name="extra_discount" class="adm-form-input" style="width:80px;" min="0" max="100" step="0.5"
                                           value="<?= number_format((float)($s['extra_discount'] ?? 0), 1, '.', '') ?>">
                                </div>
                                <div>
                                    <label class="adm-form-label">Stato</label>
                                    <select name="status" class="adm-form-input" style="min-width:130px;">
                                        <option value="active" <?= $s['status'] === 'active' ? 'selected' : '' ?>>Attivo</option>
                                        <option value="trialing" <?= $s['status'] === 'trialing' ? 'selected' : '' ?>>Trial</option>
                                        <option value="past_due" <?= $s['status'] === 'past_due' ? 'selected' : '' ?>>Non pagato</option>
                                        <option value="cancelled" <?= $s['status'] === 'cancelled' ? 'selected' : '' ?>>Cancellato</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="adm-form-label">Inizio periodo</label>
                                    <input type="date" name="period_start" class="adm-form-input" style="min-width:140px;"
                                           value="<?= $s['current_period_start'] ? date('Y-m-d', strtotime($s['current_period_start'])) : '' ?>">
                                </div>
                                <div>
                                    <label class="adm-form-label">Scadenza</label>
                                    <input type="date" name="period_end" class="adm-form-input" style="min-width:140px;"
                                           value="<?= $s['current_period_end'] ? date('Y-m-d', strtotime($s['current_period_end'])) : '' ?>">
                                </div>
                                <div>
                                    <label class="adm-form-label">Crediti Email</label>
                                    <input type="number" name="email_credits" class="adm-form-input" style="width:90px;" min="0"
                                           value="<?= (int)$s['email_credits'] ?>">
                                </div>
                                <div>
                                    <label class="adm-form-label">Crediti SMS</label>
                                    <input type="number" name="sms_credits" class="adm-form-input" style="width:90px;" min="0"
                                           value="<?= (int)$s['sms_credits'] ?>">
                                </div>
                                <div style="display:flex;gap:.35rem;">
                                    <button type="submit" class="admin-qa admin-qa-primary" style="font-size:.78rem;padding:.45rem .75rem;">
                                        <i class="bi bi-check-circle"></i> Salva
                                    </button>
                                    <button type="button" class="admin-qa admin-qa-outline" style="font-size:.78rem;padding:.45rem .75rem;"
                                            data-bs-toggle="collapse" data-bs-target="#changePlan<?= $s['id'] ?>">Annulla</button>
                                </div>
                            </div>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
