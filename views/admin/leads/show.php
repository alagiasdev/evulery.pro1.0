<?php
/** @var array $lead */
/** @var array $activities */
/** @var array $statuses */
/** @var array $activityLabels */
/** @var array $resellers */
/** @var ?string $assignedResellerName */

$statusColors = [
    'new'            => ['#0277bd', '#e3f2fd'],
    'contacted'      => ['#f57c00', '#fff3e0'],
    'demo_scheduled' => ['#7b1fa2', '#f3e5f5'],
    'demo_done'      => ['#1976d2', '#e3f2fd'],
    'negotiating'    => ['#c2185b', '#fce4ec'],
    'customer'       => ['#fff', '#00844A'],
    'lost'           => ['#757575', '#f5f5f5'],
];
$activityColors = [
    'created'        => '#00844A',
    'status_changed' => '#f57c00',
    'assigned'       => '#7b1fa2',
    'reassigned'     => '#7b1fa2',
    'note_added'     => '#0277bd',
    'email_sent'     => '#1976d2',
    'material_sent'  => '#5e35b1',
    'contacted'      => '#f57c00',
    'demo_done'      => '#00844A',
    'converted'      => '#00844A',
];
$activityIcons = [
    'created'        => 'bi-plus-circle',
    'status_changed' => 'bi-arrow-right',
    'assigned'       => 'bi-person-check',
    'reassigned'     => 'bi-person-check',
    'note_added'     => 'bi-chat-dots',
    'email_sent'     => 'bi-envelope',
    'material_sent'  => 'bi-file-earmark-text',
    'contacted'      => 'bi-telephone',
    'demo_done'      => 'bi-camera-video',
    'converted'      => 'bi-trophy',
];

[$statusTxt, $statusBg] = $statusColors[$lead['status']] ?? ['#757575', '#f5f5f5'];
$statusBorder = $lead['status'] === 'customer' ? '#00844A' : $statusTxt;
$statusLabel = $statuses[$lead['status']] ?? $lead['status'];
?>

<style>
.lead-breadcrumb { font-size: .8rem; color: #6c757d; margin-bottom: 1rem; }
.lead-breadcrumb a { color: #00844A; text-decoration: none; }
.lead-breadcrumb i { font-size: .7rem; margin: 0 4px; }
.lead-header {
    background: #fff; border: 1px solid #e9ecef; border-radius: 12px;
    padding: 1.5rem; margin-bottom: 1rem;
    display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;
}
.lead-restaurant { font-size: 1.5rem; font-weight: 800; letter-spacing: -.5px; margin-bottom: 4px; }
.lead-meta { display: flex; gap: 18px; flex-wrap: wrap; font-size: .85rem; color: #495057; margin-top: 8px; }
.lead-meta span { display: inline-flex; align-items: center; gap: 5px; }
.lead-meta i { color: #6c757d; }
.lead-status-row { margin-top: 14px; display: flex; gap: 10px; align-items: center; }
.lead-back-btn {
    background: #fff; color: #1a1d23; padding: .55rem 1rem;
    border-radius: 6px; font-weight: 600; font-size: .85rem;
    border: 1px solid #e9ecef; text-decoration: none;
    display: inline-flex; align-items: center; gap: 6px;
}
.lead-back-btn:hover { border-color: #00844A; color: #00844A; }

.lead-grid { display: grid; grid-template-columns: 1fr 360px; gap: 1rem; }
@media (max-width: 900px) { .lead-grid { grid-template-columns: 1fr; } }

.lead-card {
    background: #fff; border: 1px solid #e9ecef; border-radius: 12px;
    margin-bottom: 1rem; overflow: hidden;
}
.lead-card-hdr {
    padding: .85rem 1.25rem; border-bottom: 1px solid #e9ecef; background: #fafbfc;
    font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
    color: #495057; display: flex; align-items: center; gap: 8px;
}
.lead-card-hdr i { color: #00844A; font-size: 1rem; }
.lead-card-body { padding: 1.25rem; }

.lead-info-row {
    display: grid; grid-template-columns: 130px 1fr;
    padding: 8px 0; border-bottom: 1px solid #f5f5f5;
    align-items: center; font-size: .85rem;
}
.lead-info-row:last-child { border-bottom: 0; }
.lead-info-row .lbl { font-size: .72rem; font-weight: 700; color: #6c757d; text-transform: uppercase; letter-spacing: .5px; }
.lead-info-row .val { font-weight: 500; color: #1a1d23; }
.lead-info-row .val.private { color: #6c757d; font-size: .78rem; font-family: 'SF Mono', Menlo, monospace; }

.lead-message-box {
    background: #fafbfc; border-left: 3px solid #00844A;
    padding: 12px 16px; border-radius: 6px; margin-top: 12px;
    font-size: .88rem; color: #1a1d23; line-height: 1.6; font-style: italic;
    white-space: pre-wrap;
}

.lead-action-section {
    margin-bottom: 1.25rem; padding-bottom: 1.25rem;
    border-bottom: 1px solid #f5f5f5;
}
.lead-action-section:last-of-type { border-bottom: 0; padding-bottom: 0; margin-bottom: 0; }
.lead-action-label {
    font-size: .68rem; font-weight: 700; color: #6c757d;
    text-transform: uppercase; letter-spacing: .5px; margin-bottom: 8px;
}
.lead-select, .lead-textarea, .lead-input-date {
    width: 100%; padding: .55rem .8rem; border: 1px solid #e9ecef;
    border-radius: 6px; font-size: .85rem; font-family: inherit;
    background: #fff; color: #1a1d23;
}
.lead-textarea { resize: vertical; min-height: 70px; }
.lead-btn-primary {
    background: #00844A; color: #fff; padding: .55rem 1rem;
    border-radius: 6px; font-weight: 600; font-size: .85rem;
    border: 0; cursor: pointer; width: 100%;
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
}
.lead-btn-primary:hover { background: #006837; }
.lead-btn-success {
    background: #E8F5E9; color: #006837; padding: .65rem 1.1rem;
    border-radius: 6px; font-weight: 700; font-size: .85rem;
    border: 1.5px solid #00844A; cursor: pointer;
    text-decoration: none; display: inline-flex; align-items: center;
    justify-content: center; gap: 6px; width: 100%;
}
.lead-btn-success:hover { background: #00844A; color: #fff; }

.lead-private-warning {
    margin-top: 10px; background: #fce4ec; border-left: 3px solid #c2185b;
    padding: 8px 12px; border-radius: 4px; font-size: .72rem; color: #b71c1c; line-height: 1.4;
}

/* Timeline */
.lead-timeline { padding: 0 1.25rem 1.25rem 1.25rem; }
.lead-tl-item {
    position: relative; padding-left: 28px; padding-bottom: 16px;
    border-left: 2px solid #e8e8ef; margin-left: 8px;
}
.lead-tl-item:last-child { border-left-color: transparent; padding-bottom: 0; }
.lead-tl-icon {
    position: absolute; left: -11px; top: 0;
    width: 20px; height: 20px; border-radius: 50%;
    background: #fff; border: 2px solid;
    display: flex; align-items: center; justify-content: center; font-size: .65rem;
}
.lead-tl-content { padding-left: 12px; }
.lead-tl-action { font-size: .85rem; font-weight: 600; color: #1a1d23; }
.lead-tl-meta { font-size: .72rem; color: #6c757d; margin-top: 2px; }
.lead-tl-note {
    margin-top: 6px; background: #fafbfc; padding: 8px 12px; border-radius: 6px;
    font-size: .82rem; color: #1a1d23; border-left: 2px solid #e9ecef; font-style: italic;
}

.lead-empty-tl { padding: 20px; text-align: center; color: #6c757d; font-size: .85rem; }
</style>

<div class="lead-breadcrumb">
    <a href="<?= url('admin/leads') ?>">Lead</a>
    <i class="bi bi-chevron-right"></i>
    <span><?= e($lead['restaurant']) ?></span>
</div>

<!-- Header -->
<div class="lead-header">
    <div style="flex:1;">
        <div class="lead-restaurant"><?= e($lead['restaurant']) ?></div>
        <div class="lead-meta">
            <span><i class="bi bi-person"></i> <?= e($lead['name']) ?></span>
            <span><i class="bi bi-envelope"></i> <a href="mailto:<?= e($lead['email']) ?>" style="color:inherit;text-decoration:none;"><?= e($lead['email']) ?></a></span>
            <span><i class="bi bi-telephone"></i> <a href="tel:<?= e($lead['phone']) ?>" style="color:inherit;text-decoration:none;"><?= e($lead['phone']) ?></a></span>
        </div>
        <div class="lead-status-row">
            <span style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:100px;font-size:.8rem;font-weight:700;color:<?= $statusTxt ?>;background:<?= $statusBg ?>;border:1.5px solid <?= $statusBorder ?>;">
                <span style="width:7px;height:7px;border-radius:50%;background:currentColor;"></span>
                <?= e($statusLabel) ?>
            </span>
            <span style="font-size:.8rem;color:#6c757d;">·</span>
            <span style="font-size:.8rem;color:#495057;">Ricevuto il <strong><?= date('d/m/Y', strtotime($lead['created_at'])) ?></strong> alle <?= date('H:i', strtotime($lead['created_at'])) ?></span>
        </div>
    </div>
    <div>
        <a href="<?= url('admin/leads') ?>" class="lead-back-btn"><i class="bi bi-arrow-left"></i> Torna alla lista</a>
    </div>
</div>

<div class="lead-grid">
    <!-- LEFT: dati + storico -->
    <div>
        <!-- Dati -->
        <div class="lead-card">
            <div class="lead-card-hdr"><i class="bi bi-info-circle"></i> Informazioni richiesta</div>
            <div class="lead-card-body">
                <div class="lead-info-row"><div class="lbl">Nome</div><div class="val"><?= e($lead['name']) ?></div></div>
                <div class="lead-info-row"><div class="lbl">Ristorante</div><div class="val"><?= e($lead['restaurant']) ?></div></div>
                <div class="lead-info-row"><div class="lbl">Email</div><div class="val"><?= e($lead['email']) ?></div></div>
                <div class="lead-info-row"><div class="lbl">Telefono</div><div class="val"><?= e($lead['phone']) ?></div></div>

                <details style="margin-top:12px;">
                    <summary style="cursor:pointer;font-size:.78rem;font-weight:600;color:#00844A;user-select:none;">
                        <i class="bi bi-pencil"></i> Correggi anagrafica
                    </summary>
                    <form method="POST" action="<?= url("admin/leads/{$lead['id']}/contact") ?>" style="margin-top:10px;display:flex;flex-direction:column;gap:8px;">
                        <?= csrf_field() ?>
                        <input type="text" name="name" value="<?= e($lead['name']) ?>" placeholder="Nome" required
                               style="padding:.4rem .6rem;border:1px solid #dee2e6;border-radius:6px;font-size:.82rem;">
                        <input type="text" name="restaurant" value="<?= e($lead['restaurant']) ?>" placeholder="Ristorante" required
                               style="padding:.4rem .6rem;border:1px solid #dee2e6;border-radius:6px;font-size:.82rem;">
                        <input type="email" name="email" value="<?= e($lead['email']) ?>" placeholder="Email" required
                               style="padding:.4rem .6rem;border:1px solid #dee2e6;border-radius:6px;font-size:.82rem;">
                        <input type="tel" name="phone" value="<?= e($lead['phone']) ?>" placeholder="Telefono"
                               style="padding:.4rem .6rem;border:1px solid #dee2e6;border-radius:6px;font-size:.82rem;">
                        <button type="submit" style="background:#00844A;color:#fff;border:none;padding:.45rem;border-radius:6px;font-size:.8rem;font-weight:600;cursor:pointer;">
                            Salva anagrafica
                        </button>
                    </form>
                </details>
                <?php if (!empty($lead['ip_address'])): ?>
                    <div class="lead-info-row"><div class="lbl">IP</div><div class="val private"><?= e($lead['ip_address']) ?></div></div>
                <?php endif; ?>
                <?php if (!empty($lead['referrer'])): ?>
                    <div class="lead-info-row"><div class="lbl">Referrer</div><div class="val private"><?= e($lead['referrer']) ?></div></div>
                <?php endif; ?>
                <?php if (!empty($lead['utm_source'])): ?>
                    <div class="lead-info-row"><div class="lbl">UTM source</div><div class="val private"><?= e($lead['utm_source']) ?></div></div>
                <?php endif; ?>

                <?php if (!empty($lead['message'])): ?>
                    <div style="margin-top:14px;font-size:.72rem;font-weight:700;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;">Messaggio</div>
                    <div class="lead-message-box">"<?= e($lead['message']) ?>"</div>
                <?php endif; ?>

                <?php if (!empty($lead['notes'])): ?>
                    <div style="margin-top:18px;font-size:.72rem;font-weight:700;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;">Note admin (private)</div>
                    <div class="lead-message-box" style="border-left-color:#c2185b;background:#fce4ec;color:#1a1d23;font-style:normal;"><?= nl2br(e($lead['notes'])) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Storico attività -->
        <div class="lead-card">
            <div class="lead-card-hdr"><i class="bi bi-clock-history"></i> Storico attività</div>
            <?php if (empty($activities)): ?>
                <div class="lead-empty-tl">Nessuna attività registrata.</div>
            <?php else: ?>
                <div class="lead-timeline">
                    <?php foreach ($activities as $a):
                        $color = $activityColors[$a['type']] ?? '#6c757d';
                        $icon = $activityIcons[$a['type']] ?? 'bi-circle';
                        $label = $activityLabels[$a['type']] ?? $a['type'];
                        $author = trim(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? '')) ?: 'Sistema';
                    ?>
                        <div class="lead-tl-item">
                            <div class="lead-tl-icon" style="border-color:<?= $color ?>;color:<?= $color ?>;"><i class="bi <?= $icon ?>"></i></div>
                            <div class="lead-tl-content">
                                <div class="lead-tl-action"><?= e($label) ?></div>
                                <div class="lead-tl-meta"><?= e($author) ?> · <?= date('d M Y · H:i', strtotime($a['created_at'])) ?></div>
                                <?php if (!empty($a['description'])): ?>
                                    <div class="lead-tl-note"><?= e($a['description']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- RIGHT: pannello azioni -->
    <div>
        <div class="lead-card">
            <div class="lead-card-hdr"><i class="bi bi-lightning-charge"></i> Azioni</div>
            <div class="lead-card-body">
                <form method="POST" action="<?= url("admin/leads/{$lead['id']}") ?>">
                    <?= csrf_field() ?>

                    <div class="lead-action-section">
                        <div class="lead-action-label">Cambia stato</div>
                        <select name="status" class="lead-select">
                            <?php foreach ($statuses as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= $lead['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="lead-action-section">
                        <div class="lead-action-label">Assegnato a</div>
                        <select name="assigned_reseller_id" class="lead-select">
                            <option value="">Non assegnato</option>
                            <?php foreach ($resellers as $r): ?>
                                <option value="<?= (int)$r['id'] ?>" <?= (int)$lead['assigned_reseller_id'] === (int)$r['id'] ? 'selected' : '' ?>>
                                    <?= e(trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($resellers)): ?>
                            <div style="font-size:.72rem;color:#6c757d;margin-top:6px;font-style:italic;">
                                <i class="bi bi-info-circle"></i> Nessun reseller registrato. La lista si popolerà quando avrai utenti con ruolo reseller.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="lead-action-section">
                        <div class="lead-action-label">Prossimo follow-up</div>
                        <input type="date" name="next_followup_at" class="lead-input-date" value="<?= e($lead['next_followup_at'] ? date('Y-m-d', strtotime($lead['next_followup_at'])) : '') ?>">
                    </div>

                    <div class="lead-action-section">
                        <div class="lead-action-label">Aggiungi nota</div>
                        <textarea name="note" class="lead-textarea" placeholder="Es. Cliente molto interessato, chiede prezzi..."></textarea>
                        <div class="lead-private-warning">
                            <i class="bi bi-shield-lock"></i> Le note sono <strong>private dell'admin</strong>. I reseller non le vedranno.
                        </div>
                    </div>

                    <button type="submit" class="lead-btn-primary">
                        <i class="bi bi-check-circle"></i> Salva modifiche
                    </button>
                </form>
            </div>
        </div>

        <!-- Conversion CTA -->
        <?php if ($lead['status'] !== 'customer' && $lead['status'] !== 'lost'): ?>
            <div class="lead-card" style="background:linear-gradient(180deg, #E8F5E9 0%, #fff 100%);border-color:#00844A;">
                <div class="lead-card-body">
                    <div style="font-size:.75rem;font-weight:800;color:#00844A;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">
                        <i class="bi bi-trophy"></i> Pronto per convertire?
                    </div>
                    <div style="font-size:.85rem;color:#1a1d23;margin-bottom:12px;line-height:1.5;">
                        Quando il cliente è pronto a partire, crea il tenant Evulery con i dati di questo lead già pre-compilati.
                    </div>
                    <a href="<?= url("admin/leads/{$lead['id']}/convert") ?>" class="lead-btn-success">
                        <i class="bi bi-arrow-right-circle"></i> Crea tenant da questo lead
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
