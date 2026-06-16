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
        Notifiche audio — sound logo brandizzato per evento (Fase notifiche).
        Toggle master + volume + 5 toggle per evento. Salvataggio nel form
        principale di questa pagina; il modulo JS legge i flag al refresh
        della pagina o tramite EvuleryNotificationSounds.setConfig().
    -->
    <div class="card section-card" style="margin-top:1rem;">
        <div class="section-header">
            <div class="section-icon" style="background:var(--brand);"><i class="bi bi-volume-up"></i></div>
            <div>
                <div class="section-title">Notifiche audio</div>
                <div class="section-subtitle">Suono brandizzato Evulery per ogni evento, riconoscibile anche a distanza nel rumore del ristorante.</div>
            </div>
        </div>
        <div class="form-body">
            <!-- Master toggle + volume + anteprima -->
            <div class="row g-3" style="margin-bottom:.75rem;">
                <div class="col-12 field-row">
                    <div class="form-check form-switch" style="padding-left:2.5em;">
                        <input class="form-check-input" type="checkbox" name="notification_sound_enabled" value="1"
                               id="sound-enabled" <?= !empty($tenant['notification_sound_enabled']) || !isset($tenant['notification_sound_enabled']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="sound-enabled" style="font-size:.88rem;font-weight:600;">
                            Suoni notifiche attivi
                        </label>
                    </div>
                </div>
                <div class="col-md-6 field-row">
                    <label class="field-label">Volume</label>
                    <div style="display:flex;align-items:center;gap:.75rem;">
                        <input type="range" name="notification_sound_volume" id="sound-volume" min="0" max="100" step="5"
                               value="<?= (int)($tenant['notification_sound_volume'] ?? 70) ?>"
                               style="flex:1;">
                        <span id="sound-volume-label" style="font-size:.85rem;font-weight:600;min-width:36px;text-align:right;color:#495057;"><?= (int)($tenant['notification_sound_volume'] ?? 70) ?>%</span>
                    </div>
                </div>
                <div class="col-md-6 field-row" style="display:flex;align-items:flex-end;">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="sound-preview-btn">
                        <i class="bi bi-play-circle me-1"></i> Anteprima suono
                    </button>
                </div>
            </div>

            <div style="border-top:1px solid #f0f0f0;padding-top:.75rem;margin-top:.25rem;">
                <div style="font-size:.78rem;font-weight:600;color:#495057;margin-bottom:.5rem;">Eventi che riprodurranno il suono:</div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="form-check form-switch" style="padding-left:2.5em;">
                            <input class="form-check-input" type="checkbox" name="sound_on_new_reservation" value="1"
                                   id="sound-new-res" data-preview="new_reservation"
                                   <?= !empty($tenant['sound_on_new_reservation']) || !isset($tenant['sound_on_new_reservation']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="sound-new-res" style="font-size:.82rem;">
                                Nuova prenotazione
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch" style="padding-left:2.5em;">
                            <input class="form-check-input" type="checkbox" name="sound_on_cancellation" value="1"
                                   id="sound-cancel" data-preview="cancellation"
                                   <?= !empty($tenant['sound_on_cancellation']) || !isset($tenant['sound_on_cancellation']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="sound-cancel" style="font-size:.82rem;">
                                Cancellazione (dal cliente)
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch" style="padding-left:2.5em;">
                            <input class="form-check-input" type="checkbox" name="sound_on_deposit_received" value="1"
                                   id="sound-deposit" data-preview="deposit_received"
                                   <?= !empty($tenant['sound_on_deposit_received']) || !isset($tenant['sound_on_deposit_received']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="sound-deposit" style="font-size:.82rem;">
                                Caparra ricevuta
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch" style="padding-left:2.5em;">
                            <input class="form-check-input" type="checkbox" name="sound_on_new_order" value="1"
                                   id="sound-order" data-preview="new_order"
                                   <?= !empty($tenant['sound_on_new_order']) || !isset($tenant['sound_on_new_order']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="sound-order" style="font-size:.82rem;">
                                Nuovo ordine online
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch" style="padding-left:2.5em;">
                            <input class="form-check-input" type="checkbox" name="sound_on_new_feedback" value="1"
                                   id="sound-feedback" data-preview="new_feedback"
                                   <?= !empty($tenant['sound_on_new_feedback']) || !isset($tenant['sound_on_new_feedback']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="sound-feedback" style="font-size:.82rem;">
                                Recensione ricevuta
                            </label>
                        </div>
                    </div>
                </div>
                <div class="field-hint" style="margin-top:.6rem;">Tap sul nome dell'evento per ascoltare un'anteprima.</div>
            </div>
        </div>
    </div>

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

            <?php
                // Etichetta leggibile + icona dal user-agent (closure locale: niente
                // nuova classe da autoload-are).
                $deviceInfo = function (?string $ua): array {
                    $ua = (string)$ua;
                    $os = 'Dispositivo';
                    if (stripos($ua, 'Windows') !== false) $os = 'Windows';
                    elseif (stripos($ua, 'iPhone') !== false) $os = 'iPhone';
                    elseif (stripos($ua, 'iPad') !== false) $os = 'iPad';
                    elseif (stripos($ua, 'Android') !== false) $os = 'Android';
                    elseif (stripos($ua, 'Macintosh') !== false || stripos($ua, 'Mac OS') !== false) $os = 'Mac';
                    elseif (stripos($ua, 'Linux') !== false) $os = 'Linux';
                    $browser = 'Browser'; $icon = 'bi-globe2';
                    if (stripos($ua, 'Edg') !== false) { $browser = 'Edge'; $icon = 'bi-browser-edge'; }
                    elseif (stripos($ua, 'SamsungBrowser') !== false) { $browser = 'Samsung Internet'; $icon = 'bi-browser-chrome'; }
                    elseif (stripos($ua, 'OPR') !== false || stripos($ua, 'Opera') !== false) { $browser = 'Opera'; $icon = 'bi-globe2'; }
                    elseif (stripos($ua, 'Firefox') !== false || stripos($ua, 'FxiOS') !== false) { $browser = 'Firefox'; $icon = 'bi-browser-firefox'; }
                    elseif (stripos($ua, 'CriOS') !== false || stripos($ua, 'Chrome') !== false) { $browser = 'Chrome'; $icon = 'bi-browser-chrome'; }
                    elseif (stripos($ua, 'Safari') !== false) { $browser = 'Safari'; $icon = 'bi-browser-safari'; }
                    return ['label' => $browser . ' · ' . $os, 'icon' => $icon];
                };
                $MESI_IT = ['', 'gen', 'feb', 'mar', 'apr', 'mag', 'giu', 'lug', 'ago', 'set', 'ott', 'nov', 'dic'];
            ?>
            <?php if (!empty($pushDevices)): ?>
            <div class="push-devices">
                <div class="push-devices-h"><i class="bi bi-hdd-stack me-1"></i> Dispositivi collegati (<?= count($pushDevices) ?>)</div>
                <?php foreach ($pushDevices as $d): ?>
                <?php
                    $info = $deviceInfo($d['user_agent'] ?? '');
                    $ts = !empty($d['created_at']) ? strtotime((string)$d['created_at']) : 0;
                    $dataLbl = $ts ? (date('j', $ts) . ' ' . $MESI_IT[(int)date('n', $ts)] . ' ' . date('Y', $ts)) : '';
                ?>
                <div class="push-device" data-endpoint="<?= e($d['endpoint']) ?>">
                    <div class="push-device-ic"><i class="bi <?= e($info['icon']) ?>"></i></div>
                    <div class="push-device-info">
                        <div class="push-device-name"><?= e($info['label']) ?> <span class="push-device-current" style="display:none;">Questo dispositivo</span></div>
                        <?php if ($dataLbl !== ''): ?><div class="push-device-meta">Collegato il <?= e($dataLbl) ?></div><?php endif; ?>
                    </div>
                    <button type="button" class="push-device-remove" data-push-remove title="Rimuovi dalle notifiche"><i class="bi bi-x-lg"></i></button>
                </div>
                <?php endforeach; ?>
                <div class="push-devices-hint"><i class="bi bi-info-circle me-1"></i> Rimuovendo un dispositivo ancora in uso potrebbe ricomparire alla riapertura della dashboard. Per spegnerle definitivamente su un dispositivo, usa "Rimuovi" da quel dispositivo.</div>
            </div>
            <?php endif; ?>
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
            if (!s.supported)              return showBox('push-status-unsupported');
            // SW non diventato pronto entro il timeout (es. contesto non sicuro):
            // mostra "non supportato qui" invece dello spinner perpetuo.
            if (s.ready === false)         return showBox('push-status-unsupported');
            if (s.permission === 'denied') return showBox('push-status-denied');
            if (s.subscribed)              return showBox('push-status-active');
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

    // ========== Notifiche audio: anteprima + volume label live ==========
    var volRange = document.getElementById('sound-volume');
    var volLabel = document.getElementById('sound-volume-label');
    if (volRange && volLabel) {
        volRange.addEventListener('input', function () {
            volLabel.textContent = volRange.value + '%';
            // Hot-reload del volume nel modulo audio (no reload pagina)
            if (window.EvuleryNotificationSounds) {
                window.EvuleryNotificationSounds.setConfig({ volume: parseInt(volRange.value, 10) });
            }
        });
    }
    function previewSound(type) {
        if (!window.EvuleryNotificationSounds) return;
        // testSound bypassa i toggle: l'utente vuole sentire il suono per
        // decidere se attivarlo, non e' bloccato dallo stato corrente.
        window.EvuleryNotificationSounds.testSound(type);
    }
    var previewBtn = document.getElementById('sound-preview-btn');
    if (previewBtn) {
        previewBtn.addEventListener('click', function () { previewSound('new_reservation'); });
    }
    document.querySelectorAll('label.form-check-label[for^="sound-"]').forEach(function (lbl) {
        var forId = lbl.getAttribute('for');
        var input = document.getElementById(forId);
        if (!input) return;
        var type = input.getAttribute('data-preview');
        if (!type) return;
        lbl.style.cursor = 'pointer';
        lbl.addEventListener('click', function (e) {
            // Solo click sul testo del label, non sul toggle: il browser
            // gestisce gia' il toggle nativamente. Aggiungiamo SOLO preview.
            // (Il toggle scatta lo stesso perche' label e' associata via for.)
            setTimeout(function () { previewSound(type); }, 100);
        });
    });

    // ========== Dispositivi collegati: marca "questo dispositivo" + rimozione ==========
    (function () {
        var rows = Array.prototype.slice.call(document.querySelectorAll('.push-device'));
        if (!rows.length) return;
        var CSRF = <?= json_encode(csrf_token()) ?>;
        var UNSUB_URL = <?= json_encode(url('dashboard/push/unsubscribe')) ?>;
        var currentEndpoint = null;

        // Riconosci il dispositivo corrente confrontando l'endpoint della sua subscription
        if ('serviceWorker' in navigator && navigator.serviceWorker.ready) {
            navigator.serviceWorker.ready
                .then(function (reg) { return reg.pushManager.getSubscription(); })
                .then(function (sub) {
                    if (!sub) return;
                    currentEndpoint = sub.endpoint;
                    rows.forEach(function (row) {
                        if (row.getAttribute('data-endpoint') === currentEndpoint) {
                            var badge = row.querySelector('.push-device-current');
                            if (badge) badge.style.display = '';
                        }
                    });
                }).catch(function () {});
        }

        function removeFromServer(endpoint) {
            var body = new URLSearchParams();
            body.append('_csrf', CSRF);
            body.append('endpoint', endpoint);
            fetch(UNSUB_URL, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            }).then(function () { location.reload(); }).catch(function () { location.reload(); });
        }

        rows.forEach(function (row) {
            var btn = row.querySelector('[data-push-remove]');
            if (!btn) return;
            btn.addEventListener('click', function () {
                var endpoint = row.getAttribute('data-endpoint');
                if (!endpoint || !window.confirm('Rimuovere questo dispositivo dalle notifiche?')) return;
                btn.disabled = true;
                // Se e' il dispositivo CORRENTE: disiscrivi davvero il browser
                // (rimozione permanente), poi rimuovi dal server.
                if (currentEndpoint && endpoint === currentEndpoint && 'serviceWorker' in navigator) {
                    navigator.serviceWorker.ready
                        .then(function (reg) { return reg.pushManager.getSubscription(); })
                        .then(function (sub) { return sub ? sub.unsubscribe() : null; })
                        .then(function () { removeFromServer(endpoint); })
                        .catch(function () { removeFromServer(endpoint); });
                } else {
                    removeFromServer(endpoint);
                }
            });
        });
    })();
})();
</script>
<?php endif; ?>
