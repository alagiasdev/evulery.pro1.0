<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">Impostazioni</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1rem;">Configura il tuo ristorante</p>

<?php $activeKey = 'collaborators'; include __DIR__ . '/../../partials/settings-tabs.php'; ?>

<div class="row g-4">
    <!-- Lista collaboratori -->
    <div class="col-lg-7">
        <div class="card section-card">
            <div class="section-header">
                <div class="section-icon" style="background:var(--brand);"><i class="bi bi-people-fill"></i></div>
                <div>
                    <div class="section-title">Collaboratori</div>
                    <div class="section-subtitle"><?= count($staff) ?> / <?= (int)$limit ?> account staff</div>
                </div>
            </div>
            <div class="form-body">
                <?php if (empty($staff)): ?>
                <p style="font-size:.85rem;color:#6c757d;margin:0;">Nessun collaboratore. Aggiungine uno dal modulo qui a fianco.</p>
                <?php else: ?>
                <?php foreach ($staff as $s): ?>
                <div style="border-bottom:1px solid #f0f0f0;padding:.6rem 0;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:.75rem;">
                        <div style="min-width:0;">
                            <div style="font-weight:600;font-size:.9rem;">
                                <?= e($s['first_name'] . ' ' . $s['last_name']) ?>
                                <?php if (empty($s['is_active'])): ?>
                                <span style="font-size:.68rem;color:#E65100;background:#FFF3E0;padding:1px 7px;border-radius:10px;margin-left:.3rem;">disattivato</span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size:.77rem;color:#6c757d;overflow:hidden;text-overflow:ellipsis;">
                                <?= e($s['email']) ?> · accesso: <?= !empty($s['last_login_at']) ? format_date($s['last_login_at'], 'd/m/Y H:i') : 'mai' ?>
                            </div>
                        </div>
                        <div style="display:flex;gap:.4rem;flex-shrink:0;">
                            <form method="POST" action="<?= url('dashboard/settings/collaborators/' . $s['id'] . '/toggle') ?>" style="display:inline;">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-secondary" type="submit"><?= empty($s['is_active']) ? 'Riattiva' : 'Disattiva' ?></button>
                            </form>
                            <form method="POST" action="<?= url('dashboard/settings/collaborators/' . $s['id'] . '/delete') ?>" style="display:inline;">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger" type="submit" title="Rimuovi"
                                        data-confirm="Rimuovere <?= e($s['first_name'] . ' ' . $s['last_name']) ?>? Non potrà più accedere.">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <details style="margin-top:.4rem;">
                        <summary style="font-size:.74rem;color:#5C6BC0;cursor:pointer;"><i class="bi bi-key"></i> Cambia password</summary>
                        <form method="POST" action="<?= url('dashboard/settings/collaborators/' . $s['id'] . '/password') ?>" style="margin-top:.4rem;">
                            <?= csrf_field() ?>
                            <div style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap;">
                                <input class="field-input js-pw-input" type="text" name="password" placeholder="Nuova password" required style="max-width:220px;">
                                <button class="btn btn-sm btn-outline-secondary" type="submit">Salva</button>
                            </div>
                            <div class="pw-check" style="font-size:.7rem;color:#6c757d;margin-top:.3rem;display:flex;gap:.7rem;flex-wrap:wrap;">
                                <span data-rule="len"><i class="bi bi-circle"></i> 8+</span>
                                <span data-rule="upper"><i class="bi bi-circle"></i> maiuscola</span>
                                <span data-rule="num"><i class="bi bi-circle"></i> numero</span>
                            </div>
                        </form>
                    </details>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Aggiungi + info -->
    <div class="col-lg-5">
        <div class="card section-card">
            <div class="section-header">
                <div class="section-icon" style="background:#5C6BC0;"><i class="bi bi-person-plus"></i></div>
                <div>
                    <div class="section-title">Aggiungi collaboratore</div>
                    <div class="section-subtitle">Crea un accesso staff</div>
                </div>
            </div>
            <div class="form-body">
                <?php if (empty($canAddMore)): ?>
                <div style="background:#FFF3E0;color:#9A4A00;border-radius:8px;padding:.7rem .9rem;font-size:.82rem;">
                    <i class="bi bi-info-circle me-1"></i> Hai raggiunto il limite di <?= (int)$limit ?> collaborator<?= (int)$limit === 1 ? 'e' : 'i' ?>. Contatta il supporto per aumentarlo.
                </div>
                <?php else: ?>
                <form method="POST" action="<?= url('dashboard/settings/collaborators') ?>">
                    <?= csrf_field() ?>
                    <div class="field-row"><label class="field-label">Nome *</label><input class="field-input" name="first_name" value="<?= old('first_name') ?>" required></div>
                    <div class="field-row"><label class="field-label">Cognome *</label><input class="field-input" name="last_name" value="<?= old('last_name') ?>" required></div>
                    <div class="field-row"><label class="field-label">Email *</label><input class="field-input" type="email" name="email" value="<?= old('email') ?>" required autocomplete="off"></div>
                    <div class="field-row">
                        <label class="field-label">Password *</label>
                        <input class="field-input js-pw-input" type="text" name="password" required autocomplete="new-password">
                        <div class="pw-check" style="font-size:.72rem;color:#6c757d;margin-top:.35rem;display:flex;gap:.8rem;flex-wrap:wrap;">
                            <span data-rule="len"><i class="bi bi-circle"></i> 8+ caratteri</span>
                            <span data-rule="upper"><i class="bi bi-circle"></i> 1 maiuscola</span>
                            <span data-rule="num"><i class="bi bi-circle"></i> 1 numero</span>
                        </div>
                        <div class="field-hint">Comunicala tu al collaboratore.</div>
                    </div>
                    <button class="btn btn-success btn-sm" type="submit" style="margin-top:.5rem;"><i class="bi bi-check-circle me-1"></i> Crea collaboratore</button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card section-card">
            <div class="form-body">
                <div style="font-weight:600;font-size:.82rem;margin-bottom:.4rem;color:#495057;"><i class="bi bi-shield-lock me-1"></i> Cosa vede un collaboratore</div>
                <div style="font-size:.78rem;color:#6c757d;line-height:1.6;">
                    ✅ Prenotazioni, Sala, Ordini, Clienti (sola lettura).<br>
                    ❌ Impostazioni, abbonamento, caparra/Stripe, comunicazioni, marketing, eliminazioni e addebiti.
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
document.querySelectorAll('[data-confirm]').forEach(function(b){
    b.addEventListener('click', function(e){ if (!confirm(this.dataset.confirm)) e.preventDefault(); });
});
// Verifica password in tempo reale (8+ caratteri, 1 maiuscola, 1 numero)
document.querySelectorAll('.js-pw-input').forEach(function(inp){
    var box = inp.closest('.field-row, form');
    var check = box ? box.querySelector('.pw-check') : null;
    if (!check) return;
    var rules = { len: function(v){ return v.length >= 8; }, upper: function(v){ return /[A-Z]/.test(v); }, num: function(v){ return /[0-9]/.test(v); } };
    function update(){
        var v = inp.value;
        Object.keys(rules).forEach(function(k){
            var el = check.querySelector('[data-rule="'+k+'"]');
            if (!el) return;
            var ok = rules[k](v);
            el.style.color = ok ? '#2E7D32' : '#9aa0a6';
            var icon = el.querySelector('i');
            if (icon) icon.className = ok ? 'bi bi-check-circle-fill' : 'bi bi-circle';
        });
    }
    inp.addEventListener('input', update);
    update();
});
</script>
