<?php
$defaults = [
    'notif_title_new_reservation' => 'Nuova prenotazione',
    'notif_body_new_reservation'  => '{nome} — {data} ore {ora}, {coperti} persone',
    'notif_title_cancellation'    => 'Prenotazione cancellata',
    'notif_body_cancellation'     => '{nome} ({data} ore {ora}) — annullata {da}',
    'notif_title_deposit'         => 'Caparra ricevuta',
    'notif_body_deposit'          => '{nome} — €{importo}',
];
?>

<h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.25rem;">Impostazioni</h2>
<p style="font-size:.82rem; color:#6c757d; margin-bottom:1rem;">Configura il tuo ristorante</p>

<?php $activeKey = 'settings-notifications'; include __DIR__ . '/../../partials/settings-tabs.php'; ?>

<form method="POST" action="<?= url('dashboard/settings/notifications') ?>">
    <?= csrf_field() ?>

    <!-- Email toggles (available for all plans) -->
    <div class="card section-card">
        <div class="section-header">
            <div class="section-icon" style="background:var(--brand);"><i class="bi bi-envelope"></i></div>
            <div>
                <div class="section-title">Notifiche Email</div>
                <div class="section-subtitle">Ricevi un'email quando si verificano questi eventi</div>
            </div>
        </div>
        <div class="form-body">
            <div class="row g-3">
                <div class="col-12 field-row">
                    <div style="display:flex;flex-direction:column;gap:.5rem;">
                        <div class="form-check form-switch" style="padding-left:2.5em;">
                            <input class="form-check-input" type="checkbox" name="notify_new_reservation" value="1"
                                   id="notify-new-res" <?= !empty($tenant['notify_new_reservation']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="notify-new-res" style="font-size:.82rem;font-weight:500;">
                                Nuova prenotazione
                            </label>
                        </div>
                        <div class="form-check form-switch" style="padding-left:2.5em;">
                            <input class="form-check-input" type="checkbox" name="notify_cancellation" value="1"
                                   id="notify-cancel" <?= !empty($tenant['notify_cancellation']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="notify-cancel" style="font-size:.82rem;font-weight:500;">
                                Cancellazione prenotazione
                            </label>
                        </div>
                    </div>
                    <div class="field-hint">Disponibili per tutti i piani.</div>
                </div>
            </div>
        </div>
    </div>

    <?php if (tenant_can('push_notifications')): ?>
    <!--
        Notifiche browser (push) — stato per dispositivo + CTA opt-in.
        Lo stato e' valutato lato client da window.EvuleryPush.getStatus():
        Notification.permission + presenza subscription nel browser corrente.
        Il count di dispositivi e' globale per tenant (lato server).
    -->
    <div class="card section-card" style="margin-top:1rem;">
        <div class="section-header">
            <div class="section-icon" style="background:var(--brand);"><i class="bi bi-phone"></i></div>
            <div>
                <div class="section-title">Notifiche browser (push)</div>
                <div class="section-subtitle">Ricevi notifiche istantanee anche con il browser in background.
                    <strong><?= (int)$pushDeviceCount ?> dispositiv<?= (int)$pushDeviceCount === 1 ? 'o' : 'i' ?></strong> ricev<?= (int)$pushDeviceCount === 1 ? 'e' : 'ono' ?> le notifiche per questo ristorante.</div>
            </div>
        </div>
        <div class="form-body">
            <!-- Box stato — popolato dal JS al load -->
            <div id="push-status-loading" class="push-status-box is-inactive">
                <div class="push-status-ic"><i class="bi bi-arrow-repeat" style="animation:spin 1s linear infinite;"></i></div>
                <div class="push-status-body">
                    <div class="push-status-title">Verifica stato in corso…</div>
                </div>
            </div>
            <div id="push-status-unsupported" class="push-status-box is-inactive" style="display:none;">
                <div class="push-status-ic"><i class="bi bi-slash-circle"></i></div>
                <div class="push-status-body">
                    <div class="push-status-title">Browser non supportato</div>
                    <div class="push-status-desc">Questo browser non supporta le notifiche push. Su iPhone serve aggiungere Evulery alla schermata Home (PWA) con iOS 16.4 o superiore.</div>
                </div>
            </div>
            <div id="push-status-denied" class="push-status-box is-denied" style="display:none;">
                <div class="push-status-ic"><i class="bi bi-bell-slash"></i></div>
                <div class="push-status-body">
                    <div class="push-status-title">Permesso negato sul browser</div>
                    <div class="push-status-desc">Hai negato il permesso di notifiche. Per riattivarle: clicca sull'icona del lucchetto/info accanto all'URL → Permessi → Notifiche → Consenti, poi ricarica la pagina.</div>
                </div>
            </div>
            <div id="push-status-inactive" class="push-status-box is-inactive" style="display:none;">
                <div class="push-status-ic"><i class="bi bi-bell"></i></div>
                <div class="push-status-body">
                    <div class="push-status-title">Notifiche non attive su questo dispositivo</div>
                    <div class="push-status-desc">Clicca "Attiva" per ricevere le notifiche browser sul dispositivo che stai usando ora.</div>
                </div>
                <div class="push-status-actions">
                    <button type="button" class="btn btn-success btn-sm" id="push-activate-btn"><i class="bi bi-bell-fill me-1"></i> Attiva</button>
                </div>
            </div>
            <div id="push-status-active" class="push-status-box is-active" style="display:none;">
                <div class="push-status-ic"><i class="bi bi-check-circle-fill"></i></div>
                <div class="push-status-body">
                    <div class="push-status-title">Notifiche attive su questo dispositivo</div>
                    <div class="push-status-desc">Riceverai un avviso per nuove prenotazioni, cancellazioni, caparre ricevute e altri eventi importanti.</div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Push templates -->
    <div class="card section-card" style="margin-top:1rem;">
        <div class="section-header">
            <div class="section-icon" style="background:var(--brand);"><i class="bi bi-bell"></i></div>
            <div>
                <div class="section-title">Template Notifiche Push</div>
                <div class="section-subtitle">Personalizza titolo e testo delle notifiche nel browser e nella campanella</div>
            </div>
        </div>
        <div class="form-body">

            <!-- Placeholders legend -->
            <div style="background:#f0faf4;border:1px solid #c8e6c9;border-radius:8px;padding:10px 14px;margin-bottom:1rem;font-size:.78rem;color:#495057;">
                <strong>Segnaposto disponibili:</strong>
                <code>{nome}</code> nome cliente &middot;
                <code>{data}</code> data prenotazione &middot;
                <code>{ora}</code> orario &middot;
                <code>{coperti}</code> numero persone &middot;
                <code>{importo}</code> importo caparra &middot;
                <code>{da}</code> chi ha cancellato
            </div>

            <!-- New reservation -->
            <div style="border:1px solid #e9ecef;border-radius:10px;padding:1rem;margin-bottom:1rem;">
                <div style="font-weight:600;font-size:.88rem;margin-bottom:.75rem;"><i class="bi bi-plus-circle me-1" style="color:var(--brand);"></i> Nuova prenotazione</div>
                <div class="row g-3">
                    <div class="col-md-4 field-row">
                        <label class="field-label">Titolo</label>
                        <input type="text" class="field-input" name="notif_title_new_reservation"
                               value="<?= e($tenant['notif_title_new_reservation'] ?? '') ?>"
                               placeholder="<?= e($defaults['notif_title_new_reservation']) ?>" maxlength="255">
                    </div>
                    <div class="col-md-8 field-row">
                        <label class="field-label">Testo</label>
                        <input type="text" class="field-input" name="notif_body_new_reservation"
                               value="<?= e($tenant['notif_body_new_reservation'] ?? '') ?>"
                               placeholder="<?= e($defaults['notif_body_new_reservation']) ?>" maxlength="500">
                    </div>
                </div>
            </div>

            <!-- Cancellation -->
            <div style="border:1px solid #e9ecef;border-radius:10px;padding:1rem;margin-bottom:1rem;">
                <div style="font-weight:600;font-size:.88rem;margin-bottom:.75rem;"><i class="bi bi-x-circle me-1" style="color:#dc3545;"></i> Cancellazione</div>
                <div class="row g-3">
                    <div class="col-md-4 field-row">
                        <label class="field-label">Titolo</label>
                        <input type="text" class="field-input" name="notif_title_cancellation"
                               value="<?= e($tenant['notif_title_cancellation'] ?? '') ?>"
                               placeholder="<?= e($defaults['notif_title_cancellation']) ?>" maxlength="255">
                    </div>
                    <div class="col-md-8 field-row">
                        <label class="field-label">Testo</label>
                        <input type="text" class="field-input" name="notif_body_cancellation"
                               value="<?= e($tenant['notif_body_cancellation'] ?? '') ?>"
                               placeholder="<?= e($defaults['notif_body_cancellation']) ?>" maxlength="500">
                    </div>
                </div>
            </div>

            <!-- Deposit received -->
            <div style="border:1px solid #e9ecef;border-radius:10px;padding:1rem;">
                <div style="font-weight:600;font-size:.88rem;margin-bottom:.75rem;"><i class="bi bi-cash-coin me-1" style="color:#E65100;"></i> Caparra ricevuta</div>
                <div class="row g-3">
                    <div class="col-md-4 field-row">
                        <label class="field-label">Titolo</label>
                        <input type="text" class="field-input" name="notif_title_deposit"
                               value="<?= e($tenant['notif_title_deposit'] ?? '') ?>"
                               placeholder="<?= e($defaults['notif_title_deposit']) ?>" maxlength="255">
                    </div>
                    <div class="col-md-8 field-row">
                        <label class="field-label">Testo</label>
                        <input type="text" class="field-input" name="notif_body_deposit"
                               value="<?= e($tenant['notif_body_deposit'] ?? '') ?>"
                               placeholder="<?= e($defaults['notif_body_deposit']) ?>" maxlength="500">
                    </div>
                </div>
            </div>

            <div class="field-hint" style="margin-top:.75rem;">Lascia vuoto per usare il testo predefinito (mostrato come placeholder).</div>
        </div>
    </div>

    <div style="margin-top:1rem;">
        <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Salva impostazioni</button>
    </div>
</form>

<?php if (tenant_can('push_notifications')): ?>
<script nonce="<?= csp_nonce() ?>">
// Stato push lato client: interroga window.EvuleryPush.getStatus() esposto da
// dashboard-notifications.js e mostra il box corretto. Il JS principale e' gia'
// caricato dal layout dashboard quando il tenant ha push_notifications attivo.
(function () {
    function showBox(id) {
        ['push-status-loading','push-status-unsupported','push-status-denied','push-status-inactive','push-status-active']
            .forEach(function (b) {
                var el = document.getElementById(b);
                if (el) el.style.display = (b === id) ? 'flex' : 'none';
            });
    }
    function render() {
        if (!window.EvuleryPush || !window.EvuleryPush.getStatus) {
            // Modulo non ancora pronto: ritento a breve. Limite ~5s per evitare loop infinito.
            if ((render._attempts = (render._attempts || 0) + 1) > 25) {
                showBox('push-status-unsupported');
                return;
            }
            setTimeout(render, 200);
            return;
        }
        window.EvuleryPush.getStatus().then(function (s) {
            if (!s.supported)             return showBox('push-status-unsupported');
            if (s.permission === 'denied') return showBox('push-status-denied');
            if (s.subscribed)             return showBox('push-status-active');
            showBox('push-status-inactive');
        });
    }
    var btn = document.getElementById('push-activate-btn');
    if (btn) {
        btn.addEventListener('click', function () {
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-arrow-repeat me-1" style="animation:spin 1s linear infinite;"></i> Attivazione…';
            window.EvuleryPush.subscribe().then(function (res) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-bell-fill me-1"></i> Attiva';
                render();
            });
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', render);
    } else {
        render();
    }
})();
</script>
<?php endif; ?>
