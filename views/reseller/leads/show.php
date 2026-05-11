<div class="rs-page-header">
    <div>
        <h1 class="rs-page-title"><?= e($lead['restaurant']) ?></h1>
        <p class="rs-page-sub">
            Lead di <?= e($lead['name']) ?> · ricevuto il <?= format_date($lead['created_at'], 'd/m/Y H:i') ?>
        </p>
    </div>
    <div>
        <a href="<?= url('reseller/leads') ?>" class="rs-btn rs-btn-ghost">
            <i class="bi bi-arrow-left"></i> Torna ai lead
        </a>
    </div>
</div>

<div class="rs-detail-grid">
    <!-- COLONNA SINISTRA: form aggiornamento + storico -->
    <div>
        <!-- Aggiorna stato + nota + follow-up -->
        <div class="rs-card">
            <div class="rs-card-hdr">
                <span><i class="bi bi-pencil-square"></i> Aggiorna lead</span>
            </div>
            <div class="rs-card-body">
                <form method="POST" action="<?= url('reseller/leads/' . (int)$lead['id']) ?>">
                    <?= csrf_field() ?>
                    <div class="rs-form-grid">
                        <div class="rs-field">
                            <label for="status">Stato</label>
                            <select name="status" id="status">
                                <?php foreach ($allowedStatuses as $key): ?>
                                    <?php if (!isset($statuses[$key])) continue; ?>
                                    <option value="<?= e($key) ?>" <?= $lead['status'] === $key ? 'selected' : '' ?>>
                                        <?= e($statuses[$key]) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="rs-field-help">
                                Per convertire un lead a cliente, contatta l'amministratore.
                            </div>
                        </div>
                        <div class="rs-field">
                            <label for="next_followup_at">Prossimo follow-up</label>
                            <input type="datetime-local" name="next_followup_at" id="next_followup_at"
                                   value="<?= !empty($lead['next_followup_at']) ? date('Y-m-d\TH:i', strtotime($lead['next_followup_at'])) : '' ?>">
                        </div>
                        <div class="rs-field rs-form-grid-full">
                            <label for="note">Aggiungi nota</label>
                            <textarea name="note" id="note" rows="3" placeholder="Es. Chiamato oggi alle 15, fissa demo per lunedì 14:00..." style="resize:vertical;"></textarea>
                            <div class="rs-field-help">
                                Le tue note vengono salvate cronologicamente con il tuo nome.
                            </div>
                        </div>
                    </div>
                    <div style="margin-top:1rem;">
                        <button type="submit" class="rs-btn rs-btn-primary">
                            <i class="bi bi-check-circle"></i> Salva aggiornamento
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Storico attività -->
        <div class="rs-card">
            <div class="rs-card-hdr">
                <span><i class="bi bi-clock-history"></i> Storico attività</span>
            </div>
            <div class="rs-card-body">
                <?php if (empty($activities)): ?>
                    <div class="rs-card-empty" style="padding:1rem 0;">Nessuna attività registrata ancora.</div>
                <?php else: ?>
                    <ul class="rs-timeline">
                        <?php foreach ($activities as $a): ?>
                            <li>
                                <div class="ttl"><?= e($activityLabels[$a['type']] ?? $a['type']) ?></div>
                                <?php if (!empty($a['description'])): ?>
                                    <div class="desc"><?= nl2br(e($a['description'])) ?></div>
                                <?php endif; ?>
                                <div class="meta">
                                    <?= format_date($a['created_at'], 'd/m/Y H:i') ?>
                                    <?php if (!empty($a['first_name'])): ?>
                                        · <?= e($a['first_name'] . ' ' . $a['last_name']) ?>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- COLONNA DESTRA: info contatto + note storico -->
    <div>
        <div class="rs-card">
            <div class="rs-card-hdr">
                <span><i class="bi bi-person-lines-fill"></i> Informazioni lead</span>
            </div>
            <div class="rs-card-body">
                <div class="rs-info-list">
                    <div class="row">
                        <div class="lbl">Nome</div>
                        <div class="val"><?= e($lead['name']) ?></div>
                    </div>
                    <div class="row">
                        <div class="lbl">Ristorante</div>
                        <div class="val"><?= e($lead['restaurant']) ?></div>
                    </div>
                    <div class="row">
                        <div class="lbl">Email</div>
                        <div class="val"><a href="mailto:<?= e($lead['email']) ?>" style="color:var(--rs-brand);text-decoration:none;"><?= e($lead['email']) ?></a></div>
                    </div>
                    <?php if (!empty($lead['phone'])): ?>
                        <div class="row">
                            <div class="lbl">Telefono</div>
                            <div class="val"><a href="tel:<?= e($lead['phone']) ?>" style="color:var(--rs-brand);text-decoration:none;"><?= e($lead['phone']) ?></a></div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($lead['message'])): ?>
                        <div class="row" style="flex-direction:column;align-items:flex-start;gap:.35rem;">
                            <div class="lbl">Messaggio</div>
                            <div class="val" style="text-align:left;font-size:.85rem;color:var(--rs-ink-soft);line-height:1.5;">
                                <?= nl2br(e($lead['message'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="row">
                        <div class="lbl">Stato</div>
                        <div class="val"><span class="rs-badge rs-b-<?= e($lead['status']) ?>"><?= e($statuses[$lead['status']] ?? $lead['status']) ?></span></div>
                    </div>
                    <?php if (!empty($lead['next_followup_at'])): ?>
                        <div class="row">
                            <div class="lbl">Prossimo follow-up</div>
                            <div class="val"><?= format_date($lead['next_followup_at'], 'd/m/Y H:i') ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($lead['last_contact_at'])): ?>
                        <div class="row">
                            <div class="lbl">Ultimo contatto</div>
                            <div class="val"><?= format_date($lead['last_contact_at'], 'd/m/Y H:i') ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($lead['notes'])): ?>
            <div class="rs-card">
                <div class="rs-card-hdr">
                    <span><i class="bi bi-journal-text"></i> Cronologia note</span>
                </div>
                <div class="rs-card-body" style="font-size:.85rem;color:var(--rs-ink-soft);white-space:pre-wrap;line-height:1.6;">
<?= e($lead['notes']) ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
