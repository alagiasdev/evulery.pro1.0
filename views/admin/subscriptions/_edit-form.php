<div class="adm-sub-edit">
    <form method="POST" action="<?= url("admin/subscriptions/{$s['id']}/change-plan") ?>">
        <?= csrf_field() ?>
        <div class="adm-sub-edit-title">
            <i class="bi bi-pencil-square"></i> Modifica: <?= e($s['tenant_name']) ?>
        </div>
        <div class="adm-sub-edit-grid">
            <div>
                <label class="adm-form-label">Piano</label>
                <select name="plan_id" class="adm-form-input">
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
                <select name="billing_cycle" class="adm-form-input">
                    <option value="semiannual" <?= $sCycle === 'semiannual' ? 'selected' : '' ?>>Semestrale</option>
                    <option value="annual" <?= $sCycle === 'annual' ? 'selected' : '' ?>>Annuale</option>
                </select>
            </div>
            <div>
                <label class="adm-form-label">Stato</label>
                <select name="status" class="adm-form-input">
                    <option value="active" <?= $s['status'] === 'active' ? 'selected' : '' ?>>Attivo</option>
                    <option value="trialing" <?= $s['status'] === 'trialing' ? 'selected' : '' ?>>Trial</option>
                    <option value="past_due" <?= $s['status'] === 'past_due' ? 'selected' : '' ?>>Non pagato</option>
                    <option value="cancelled" <?= $s['status'] === 'cancelled' ? 'selected' : '' ?>>Cancellato</option>
                </select>
            </div>
            <div>
                <label class="adm-form-label">Sconto extra %</label>
                <input type="number" name="extra_discount" class="adm-form-input" min="0" max="100" step="0.5"
                       value="<?= number_format((float)($s['extra_discount'] ?? 0), 1, '.', '') ?>">
            </div>
            <div>
                <label class="adm-form-label">Inizio periodo</label>
                <input type="date" name="period_start" class="adm-form-input"
                       value="<?= $s['current_period_start'] ? date('Y-m-d', strtotime($s['current_period_start'])) : '' ?>">
            </div>
            <div>
                <label class="adm-form-label">Scadenza</label>
                <input type="date" name="period_end" class="adm-form-input"
                       value="<?= $s['current_period_end'] ? date('Y-m-d', strtotime($s['current_period_end'])) : '' ?>">
            </div>
            <div>
                <label class="adm-form-label">Crediti Email</label>
                <input type="number" name="email_credits" class="adm-form-input" min="0"
                       value="<?= (int)$s['email_credits'] ?>">
            </div>
            <div>
                <label class="adm-form-label">Crediti SMS</label>
                <input type="number" name="sms_credits" class="adm-form-input" min="0"
                       value="<?= (int)$s['sms_credits'] ?>">
            </div>
        </div>
        <div class="adm-sub-edit-actions">
            <button type="submit" class="adm-btn adm-btn-primary">
                <i class="bi bi-check-circle"></i> Salva
            </button>
            <button type="button" class="adm-btn" style="background:#f0f0f0;color:#495057;"
                    data-bs-toggle="collapse" data-bs-target="#<?= $editCollapseId ?>">Annulla</button>
        </div>
    </form>
</div>
