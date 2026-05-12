<?php
/** @var ?array $user         Utente in edit, null in modalità creazione */
/** @var ?array $profile      Reseller profile, null se nuovo */
/** @var ?array $defaults     Valori default commissioni (solo in creazione) */
/** @var ?array $old          Valori re-inseriti dopo errore validation (creazione) */
/** @var ?array $existingUser Account già esistente con la stessa email (creazione) */

$isEdit = $user !== null;
$action = $isEdit
    ? url("admin/users/reseller/{$user['id']}")
    : url('admin/users/reseller');

$old          = $old ?? [];
$existingUser = $existingUser ?? null;

// Valori da mostrare (in edit prevalgono i dati utente, in create i $old se presenti)
$firstName = $user['first_name'] ?? ($old['first_name'] ?? '');
$lastName  = $user['last_name']  ?? ($old['last_name']  ?? '');
$email     = $user['email']      ?? ($old['email']      ?? '');
$isActive  = $user['is_active']  ?? 1;

$cSetup = $profile['commission_setup']        ?? $defaults['commission_setup'];
$cSt    = $profile['commission_starter']      ?? $defaults['commission_starter'];
$cPr    = $profile['commission_professional'] ?? $defaults['commission_professional'];
$cEnt   = $profile['commission_enterprise']   ?? $defaults['commission_enterprise'];
$notes  = $profile['notes'] ?? ($old['notes'] ?? '');
?>

<style>
.rsl-page-hdr {
    display: flex; justify-content: space-between; align-items: flex-start;
    margin-bottom: 1.25rem;
}
.rsl-back {
    font-size: .85rem; color: #00844A; text-decoration: none;
    display: inline-flex; align-items: center; gap: 4px;
}
.rsl-back:hover { text-decoration: underline; }
.rsl-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
@media (max-width: 768px) { .rsl-grid { grid-template-columns: 1fr; } }
.rsl-card {
    background: #fff; border: 1px solid #e9ecef; border-radius: 12px;
    overflow: hidden; margin-bottom: 1rem;
}
.rsl-card-hdr {
    padding: .85rem 1.25rem; border-bottom: 1px solid #e9ecef; background: #fafbfc;
    font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
    color: #495057; display: flex; align-items: center; gap: 8px;
}
.rsl-card-hdr i { color: #00844A; font-size: 1rem; }
.rsl-card-body { padding: 1.25rem; }
.rsl-field { margin-bottom: 1rem; }
.rsl-field:last-child { margin-bottom: 0; }
.rsl-label {
    display: block; font-size: .72rem; font-weight: 700;
    color: #495057; text-transform: uppercase; letter-spacing: .5px;
    margin-bottom: 6px;
}
.rsl-input, .rsl-textarea {
    width: 100%; padding: .55rem .8rem; border: 1px solid #e9ecef;
    border-radius: 6px; font-size: .9rem; font-family: inherit;
    background: #fff; color: #1a1d23;
}
.rsl-input:focus, .rsl-textarea:focus {
    outline: none; border-color: #00844A;
    box-shadow: 0 0 0 3px rgba(0,132,74,.08);
}
.rsl-textarea { resize: vertical; min-height: 80px; }
.rsl-input-eur { position: relative; }
.rsl-input-eur input { padding-left: 2rem; }
.rsl-input-eur .prefix {
    position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
    color: #6c757d; font-weight: 600; font-size: .9rem;
}
.rsl-hint {
    font-size: .72rem; color: #6c757d; margin-top: 4px; line-height: 1.4;
}
.rsl-comm-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.rsl-checkbox-row { display: flex; align-items: center; gap: 8px; font-size: .85rem; }
.rsl-checkbox-row input { width: 16px; height: 16px; cursor: pointer; }
.rsl-actions {
    display: flex; gap: 8px; justify-content: flex-end;
    padding-top: 1rem; border-top: 1px solid #e9ecef; margin-top: 1rem;
}
.rsl-btn {
    padding: .55rem 1.2rem; border-radius: 6px; font-weight: 600;
    font-size: .88rem; cursor: pointer; border: 0;
    display: inline-flex; align-items: center; gap: 6px;
    font-family: inherit; text-decoration: none;
}
.rsl-btn-primary { background: #00844A; color: #fff; }
.rsl-btn-primary:hover { background: #006837; }
.rsl-btn-cancel { background: #fff; color: #495057; border: 1px solid #e9ecef; }
.rsl-btn-cancel:hover { border-color: #6c757d; }
.rsl-summary {
    background: #E8F5E9; border-left: 4px solid #00844A; border-radius: 8px;
    padding: 14px 18px; margin-bottom: 1rem; font-size: .85rem; color: #1a1d23;
    line-height: 1.6;
}
.rsl-summary strong { color: #006837; }
</style>

<div class="rsl-page-hdr">
    <div>
        <h1 style="font-size:1.5rem;font-weight:800;letter-spacing:-.5px;margin:0;">
            <?= $isEdit ? 'Modifica reseller' : 'Nuovo reseller' ?>
        </h1>
        <div style="font-size:.85rem;color:#6c757d;margin-top:4px;">
            <?= $isEdit
                ? 'Gestisci dati utente e commissioni concordate'
                : 'Crea un account con ruolo reseller e definisci le commissioni personalizzate' ?>
        </div>
    </div>
    <a href="<?= url('admin/users') ?>?role=reseller" class="rsl-back">
        <i class="bi bi-arrow-left"></i> Torna ai reseller
    </a>
</div>

<?php if ($existingUser): ?>
    <?php
        $exFullName = trim(($existingUser['first_name'] ?? '') . ' ' . ($existingUser['last_name'] ?? ''));
        $exRole     = role_label($existingUser['role'] ?? '');
        $exActive   = (int)($existingUser['is_active'] ?? 0) === 1;
        $exId       = (int)$existingUser['id'];
        // Se è un reseller esistente possiamo linkare direttamente alla sua scheda reseller.
        // Per altri ruoli linkiamo alla lista utenti filtrata per email.
        $exEditUrl  = ($existingUser['role'] ?? '') === 'reseller'
            ? url("admin/users/reseller/{$exId}/edit")
            : url('admin/users') . '?q=' . urlencode($existingUser['email'] ?? '');
    ?>
    <div style="background:#FFEBEE;border-left:4px solid #C62828;border-radius:8px;padding:14px 18px;margin-bottom:1rem;font-size:.88rem;color:#1a1d23;line-height:1.6;">
        <strong style="color:#C62828;"><i class="bi bi-exclamation-triangle-fill"></i> Esiste già un account con questa email</strong>
        <div style="margin-top:8px;">
            <strong><?= e($exFullName ?: '(senza nome)') ?></strong> ·
            ruolo <strong><?= e($exRole) ?></strong> ·
            <?php if ($exActive): ?>
                <span style="color:#00844A;font-weight:600;">attivo</span>
            <?php else: ?>
                <span style="color:#6c757d;font-weight:600;">disattivato</span>
            <?php endif; ?>
            <?php if (!empty($existingUser['created_at'])): ?>
                · creato il <?= format_date($existingUser['created_at'], 'd/m/Y') ?>
            <?php endif; ?>
        </div>
        <div style="margin-top:10px;">
            <a href="<?= e($exEditUrl) ?>" style="display:inline-flex;align-items:center;gap:6px;background:#C62828;color:#fff;padding:.45rem .9rem;border-radius:6px;text-decoration:none;font-weight:600;font-size:.82rem;">
                <i class="bi bi-arrow-up-right-circle"></i> Apri scheda utente
            </a>
            <span style="margin-left:8px;color:#6c757d;font-size:.78rem;">
                Cambia l'email se vuoi davvero creare un nuovo reseller.
            </span>
        </div>
    </div>
<?php endif; ?>

<?php if (!$isEdit): ?>
    <div class="rsl-summary">
        <strong>I valori di default sono:</strong> €130 setup · €120 Starter · €200 Professional · €320 Enterprise.
        Puoi modificarli qui sotto per accordi personalizzati col reseller.
    </div>
<?php endif; ?>

<form method="POST" action="<?= $action ?>">
    <?= csrf_field() ?>

    <div class="rsl-grid">
        <!-- LEFT: dati utente -->
        <div>
            <div class="rsl-card">
                <div class="rsl-card-hdr"><i class="bi bi-person"></i> Dati account</div>
                <div class="rsl-card-body">

                    <div class="rsl-field">
                        <label class="rsl-label">Nome</label>
                        <input type="text" name="first_name" class="rsl-input" required value="<?= e($firstName) ?>">
                    </div>

                    <div class="rsl-field">
                        <label class="rsl-label">Cognome</label>
                        <input type="text" name="last_name" class="rsl-input" required value="<?= e($lastName) ?>">
                    </div>

                    <div class="rsl-field">
                        <label class="rsl-label">Email</label>
                        <input type="email" name="email" class="rsl-input" required value="<?= e($email) ?>">
                    </div>

                    <div class="rsl-field">
                        <label class="rsl-label"><?= $isEdit ? 'Nuova password (opzionale)' : 'Password' ?></label>
                        <input type="password" name="password" class="rsl-input" <?= $isEdit ? '' : 'required minlength="8"' ?> minlength="8" autocomplete="new-password">
                        <div class="rsl-hint">
                            <?= $isEdit
                                ? 'Lascia vuoto per non cambiare la password attuale. Almeno 8 caratteri se la modifichi.'
                                : 'Almeno 8 caratteri. Il reseller potrà cambiarla al primo accesso.' ?>
                        </div>
                    </div>

                    <?php if ($isEdit): ?>
                        <div class="rsl-field">
                            <label class="rsl-checkbox-row">
                                <input type="checkbox" name="is_active" value="1" <?= $isActive ? 'checked' : '' ?>>
                                <span>Account attivo</span>
                            </label>
                            <div class="rsl-hint">Se disattivato, il reseller non potrà più accedere.</div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- RIGHT: commissioni -->
        <div>
            <div class="rsl-card">
                <div class="rsl-card-hdr"><i class="bi bi-cash-stack"></i> Commissioni personalizzate</div>
                <div class="rsl-card-body">

                    <div class="rsl-field">
                        <label class="rsl-label">Commissione setup (una-tantum)</label>
                        <div class="rsl-input-eur">
                            <span class="prefix">€</span>
                            <input type="number" step="0.01" min="0" name="commission_setup" class="rsl-input" value="<?= e(number_format((float)$cSetup, 2, '.', '')) ?>" required>
                        </div>
                        <div class="rsl-hint">Pagata al reseller all'attivazione del cliente. Default suggerito: €130.</div>
                    </div>

                    <div class="rsl-comm-grid">
                        <div class="rsl-field">
                            <label class="rsl-label">Annuale Starter</label>
                            <div class="rsl-input-eur">
                                <span class="prefix">€</span>
                                <input type="number" step="0.01" min="0" name="commission_starter" class="rsl-input" value="<?= e(number_format((float)$cSt, 2, '.', '')) ?>" required>
                            </div>
                        </div>
                        <div class="rsl-field">
                            <label class="rsl-label">Annuale Professional</label>
                            <div class="rsl-input-eur">
                                <span class="prefix">€</span>
                                <input type="number" step="0.01" min="0" name="commission_professional" class="rsl-input" value="<?= e(number_format((float)$cPr, 2, '.', '')) ?>" required>
                            </div>
                        </div>
                        <div class="rsl-field">
                            <label class="rsl-label">Annuale Enterprise</label>
                            <div class="rsl-input-eur">
                                <span class="prefix">€</span>
                                <input type="number" step="0.01" min="0" name="commission_enterprise" class="rsl-input" value="<?= e(number_format((float)$cEnt, 2, '.', '')) ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="rsl-field">
                        <label class="rsl-label">Note (private admin)</label>
                        <textarea name="notes" class="rsl-textarea" placeholder="Es. Reseller di zona Liguria. Accordo verbale con sconto 20% su Starter."><?= e($notes) ?></textarea>
                        <div class="rsl-hint">Le note sono visibili solo a te (admin). Il reseller non le vede.</div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="rsl-actions">
        <a href="<?= url('admin/users') ?>?role=reseller" class="rsl-btn rsl-btn-cancel">Annulla</a>
        <button type="submit" class="rsl-btn rsl-btn-primary">
            <i class="bi bi-check-circle"></i> <?= $isEdit ? 'Salva modifiche' : 'Crea reseller' ?>
        </button>
    </div>

</form>

<?php if ($isEdit): ?>
    <div style="margin-top:2.5rem;padding:1.25rem 1.5rem;background:#FFEBEE;border:1px solid #EF9A9A;border-radius:10px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;">
            <div>
                <h3 style="margin:0 0 .35rem;font-size:.95rem;font-weight:700;color:#C62828;">
                    <i class="bi bi-exclamation-triangle"></i> Danger zone
                </h3>
                <p style="margin:0;font-size:.82rem;color:#5d4037;line-height:1.5;">
                    Elimina definitivamente questo reseller. I tenant attivi a lui acquisiti bloccano l'operazione.
                    I tenant inattivi resteranno (con attribuzione rimossa) e lo storico ricariche crediti andrà perso.
                </p>
            </div>
            <form method="POST" action="<?= url('admin/users/reseller/' . (int)$user['id'] . '/delete') ?>"
                  data-confirm="Eliminare definitivamente <?= e($user['first_name'] . ' ' . $user['last_name']) ?>? L'azione non è reversibile.">
                <?= csrf_field() ?>
                <button type="submit" style="background:#C62828;color:#fff;border:none;padding:.55rem 1.1rem;border-radius:8px;font-size:.85rem;font-weight:600;cursor:pointer;">
                    <i class="bi bi-trash"></i> Elimina reseller
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>
