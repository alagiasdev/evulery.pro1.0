<?php
// Segment thresholds from tenant
$thOcc = (int)($tenant['segment_occasionale'] ?? 2);
$thAbi = (int)($tenant['segment_abituale'] ?? 4);
$thVip = (int)($tenant['segment_vip'] ?? 10);

function customerSegment(int $bookings, int $thOcc, int $thAbi, int $thVip): array {
    if ($bookings >= $thVip) return ['vip', 'VIP'];
    if ($bookings >= $thAbi) return ['abituale', 'Abituale'];
    if ($bookings >= $thOcc) return ['occasionale', 'Occasionale'];
    return ['nuovo', 'Nuovo'];
}

// Avatar color per segment
$avatarColors = ['vip' => '#E65100', 'abituale' => '#2E7D32', 'occasionale' => '#1565C0', 'nuovo' => '#757575'];

$currentSeg = $segment ?? '';

/**
 * Link "auguri su WhatsApp" per un cliente, o null se il numero manca/non e'
 * utilizzabile. Definita una volta sola perche' serve in DUE cicli (tabella
 * desktop e card mobile): calcolarla in uno solo lasciava alle card mobili il
 * link dell'ultimo cliente del ciclo precedente - cioe' gli auguri alla persona
 * sbagliata.
 */
$makeWaLink = static function (array $c) use ($tenant): ?string {
    $num = wa_phone($c['phone'] ?? '');
    if (!$num) {
        return null;
    }
    $text = 'Ciao ' . trim((string)$c['first_name']) . ', tanti auguri di buon compleanno da tutti noi!'
          . "\n" . trim((string)($tenant['name'] ?? ''));
    return 'https://wa.me/' . $num . '?text=' . rawurlencode($text);
};

// Stesso impianto delle card statistiche del Menu: icona dentro un riquadro
// colorato, numero e etichetta accanto. Qui in piu' sono filtri, quindi quello
// attivo resta segnato dal bordo.
$segTabs = [
    ['key' => '',            'label' => 'Tutti',       'count' => $stats['totale'],      'color' => '#0d6efd', 'bg' => '#e8f0fe', 'icon' => 'people-fill'],
    ['key' => 'nuovo',       'label' => 'Nuovi',       'count' => $stats['nuovo'],       'color' => '#6c757d', 'bg' => '#f1f3f5', 'icon' => 'person-plus'],
    ['key' => 'occasionale', 'label' => 'Occasionali', 'count' => $stats['occasionale'], 'color' => '#0dcaf0', 'bg' => '#e0f7fa', 'icon' => 'person'],
    ['key' => 'abituale',    'label' => 'Abituali',    'count' => $stats['abituale'],    'color' => '#198754', 'bg' => '#e8f5e9', 'icon' => 'person-check'],
    ['key' => 'vip',         'label' => 'VIP',         'count' => $stats['vip'],         'color' => '#ffc107', 'bg' => '#fff8e1', 'icon' => 'star-fill'],
];

// Compleanni del mese in corso: stessa finestra del segmento email
// "Compleanno questo mese", cosi' chi si vede qui e' chi ricevera' gli auguri.
$MESI_IT = ['', 'gennaio', 'febbraio', 'marzo', 'aprile', 'maggio', 'giugno',
            'luglio', 'agosto', 'settembre', 'ottobre', 'novembre', 'dicembre'];
$meseCorrente = $MESI_IT[(int)date('n')];
if (!empty($stats['con_compleanno'])) {
    // Etichetta corta e fissa: "Compleanni <mese>" misura 141px contro gli 85
    // disponibili nella card su telefono, e cambierebbe lunghezza ogni mese.
    // Il mese resta scritto nella barra sotto ("Compleanni di settembre: ...").
    $segTabs[] = ['key' => 'compleanno', 'label' => 'Compleanni',
                  'count' => $stats['compleanno'], 'color' => '#D81B60',
                  'bg' => '#fce4ec', 'icon' => 'gift-fill'];
}
?>

<!-- Segment tabs + Stats link -->
<div class="seg-tabs">
    <?php foreach ($segTabs as $tab):
        $isActive = $tab['key'] === $currentSeg;
        $href = $tab['key']
            ? url('dashboard/customers?segment=' . $tab['key'] . ($search ? '&q=' . urlencode($search) : ''))
            : url('dashboard/customers' . ($search ? '?q=' . urlencode($search) : ''));
    ?>
    <a href="<?= $href ?>" class="seg-tab <?= $isActive ? 'active' : '' ?>" style="--seg-color:<?= $tab['color'] ?>;">
        <div class="seg-icon" style="background:<?= $tab['bg'] ?>;color:<?= $tab['color'] ?>;">
            <i class="bi bi-<?= e($tab['icon']) ?>"></i>
        </div>
        <div class="seg-text">
            <div class="seg-count"><?= $tab['count'] ?></div>
            <div class="seg-label"><?= $tab['label'] ?></div>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<!-- Barra azioni: "Statistiche" sta qui in alto a destra, non piu' come settima
     casella in mezzo ai filtri (non e' un filtro, e nella griglia restava da
     sola su una riga). Le azioni di modifica restano nascoste allo staff. -->
<div class="d-flex align-items-center justify-content-end gap-2 flex-wrap" style="margin-bottom:.5rem;">
    <?php if (tenant_can('statistics')): ?>
    <a href="<?= url('dashboard/customers/stats') ?>" class="btn btn-sm btn-outline-success" style="font-size:.78rem;">
        <i class="bi bi-graph-up-arrow me-1"></i> Statistiche
    </a>
    <?php else: ?>
    <a href="<?= url('dashboard/customers/stats') ?>" class="btn btn-sm btn-outline-secondary" style="font-size:.78rem;opacity:.65;">
        <i class="bi bi-graph-up-arrow me-1"></i> Statistiche <i class="bi bi-lock-fill" style="font-size:.6rem;"></i>
    </a>
    <?php endif; ?>
<?php if (!is_staff()): ?>
    <?php if (!empty($deletableImportedCount)): ?>
    <button type="button" class="btn btn-sm btn-outline-danger" style="font-size:.78rem;"
            id="bulkDelImportedBtn" data-count="<?= (int)$deletableImportedCount ?>">
        <i class="bi bi-trash3 me-1"></i> Elimina importati mai prenotati (<?= (int)$deletableImportedCount ?>)
    </button>
    <?php endif; ?>
    <a href="<?= url('dashboard/customers/import') ?>" class="btn btn-sm btn-outline-secondary" style="font-size:.78rem;">
        <i class="bi bi-cloud-upload me-1"></i> Importa CSV
    </a>
<?php endif; ?>
</div>

<!-- Filter bar -->
<form method="GET" action="<?= url('dashboard/customers') ?>">
<div class="filter-bar">
    <div class="filter-row">
        <div class="search-wrap">
            <i class="bi bi-search"></i>
            <input type="text" name="q" value="<?= e($search ?? '') ?>" placeholder="Cerca per nome, email o telefono...">
        </div>
        <div class="filter-divider"></div>
        <select class="filter-input" name="segment" data-autosubmit>
            <option value="">Tutti i segmenti</option>
            <option value="nuovo" <?= $currentSeg === 'nuovo' ? 'selected' : '' ?>>Nuovo</option>
            <option value="occasionale" <?= $currentSeg === 'occasionale' ? 'selected' : '' ?>>Occasionale</option>
            <option value="abituale" <?= $currentSeg === 'abituale' ? 'selected' : '' ?>>Abituale</option>
            <option value="vip" <?= $currentSeg === 'vip' ? 'selected' : '' ?>>VIP</option>
        </select>
        <div class="filter-actions">
            <button type="submit" class="btn-filter btn-filter-primary"><i class="bi bi-search me-1"></i>Filtra</button>
            <a href="<?= url('dashboard/customers') ?>" class="btn-filter btn-filter-reset"><i class="bi bi-x-lg"></i></a>
        </div>
    </div>
</div>
</form>

<?php if ($currentSeg === 'compleanno'): ?>
<!-- Ponte verso la campagna: da qui si vedono i festeggiati, di la' si mandano
     gli auguri. Il segmento e' lo stesso, quindi i destinatari sono esattamente
     questi. -->
<div class="bday-cta">
    <?php $bdContattabili = (int)($stats['compleanno_contattabili'] ?? 0); ?>
    <?php $bdPassati = (int)($stats['compleanno_passati'] ?? 0); ?>
    <div>
        <strong>Compleanni di <?= e($meseCorrente) ?>:</strong>
        <?= (int)$stats['compleanno'] ?> client<?= (int)$stats['compleanno'] === 1 ? 'e' : 'i' ?><?php
            if ($bdPassati > 0): ?>, <?= $bdPassati ?> gi&agrave; festeggiat<?= $bdPassati === 1 ? 'o' : 'i' ?><?php endif; ?><?php
            if ((int)$stats['compleanno'] > 0): ?>. Ancora da festeggiare con email e consenso: <strong><?= $bdContattabili ?></strong><?php
            endif; ?>.
        <span style="color:#6c757d;">Data di nascita nota per <?= (int)$stats['con_compleanno'] ?> client<?= (int)$stats['con_compleanno'] === 1 ? 'e' : 'i' ?> su <?= (int)$stats['totale'] ?>.</span>
        <?php if ((int)$stats['compleanno'] > 0 && $bdContattabili === 0): ?>
        <div style="margin-top:4px;font-size:.8rem;">
            Nessuno di loro pu&ograve; ricevere email: serve il consenso alle comunicazioni, che il cliente d&agrave; prenotando dal widget.
        </div>
        <?php endif; ?>
    </div>
    <?php if (tenant_can('email_broadcast') && $bdContattabili > 0): ?>
    <?php // Nella stessa schermata ci sono due canali: qui la campagna email (a
          // crediti, verso chi ha dato il consenso), sulle righe WhatsApp uno per
          // uno. Il pulsante deve dire quale dei due e' - "Invia gli auguri" da
          // solo era ambiguo. ?>
    <a href="<?= url('dashboard/communications/create') ?>?segment=birthday_month" class="bday-cta-btn">
        <i class="bi bi-envelope-heart"></i> Invia auguri via email
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Desktop Table -->
<div class="card desktop-table">
    <div class="cust-header">
        <span>Cliente</span>
        <span>Segmento</span>
        <span>Email</span>
        <span>Telefono</span>
        <span style="text-align:center;">Pren.</span>
        <span style="text-align:center;">N/S</span>
        <span></span>
    </div>

    <?php if (empty($customers)): ?>
    <div class="empty-state">
        <i class="bi bi-people"></i>
        <p>Nessun cliente trovato.</p>
    </div>
    <?php else: ?>
    <?php foreach ($customers as $c):
        [$seg, $segLabel] = customerSegment((int)$c['total_bookings'], $thOcc, $thAbi, $thVip);
        $createdDate = isset($c['created_at']) ? format_date($c['created_at'], 'd/m/Y') : '';
        // Nel filtro compleanni la riga sotto il nome mostra la data (e gli anni
        // che compie): e' l'informazione per cui si sta guardando l'elenco.
        $bdayLine = null;
        $waLink = null;
        $bdayPassato = false;
        if ($currentSeg === 'compleanno' && !empty($c['birthday'])) {
            $bd = date_create($c['birthday']);
            if ($bd) {
                $eta = (int)date('Y') - (int)$bd->format('Y');
                // Chi ha gia' festeggiato resta in elenco (si puo' sempre salutarlo
                // su WhatsApp) ma e' fuori dalla campagna email: va distinto.
                $bdayPassato = (int)$bd->format('j') < (int)date('j');
                $bdayLine = '🎂 ' . (int)$bd->format('j') . ' ' . $MESI_IT[(int)$bd->format('n')];
                // L'eta' si mostra solo se plausibile: con un anno segnaposto
                // (1900, tipico degli archivi importati) verrebbe "compie 126 anni".
                if ($eta >= 5 && $eta <= 110) {
                    $bdayLine .= ' · compie ' . $eta . ' anni';
                }
            }
            // Auguri via WhatsApp, uno per uno: per il locale piccolo e' la strada
            // piu' diretta, e non serve il consenso alle comunicazioni commerciali.
            $waLink = $makeWaLink($c);
        }
    ?>
    <div class="cust-row<?= !empty($c['is_blocked']) ? ' cust-blocked' : '' ?>" data-url="<?= url("dashboard/customers/{$c['id']}") ?>">
        <div>
            <div class="c-name">
                <?= e($c['first_name'] . ' ' . $c['last_name']) ?>
                <?php if (($c['source'] ?? '') === 'import'): ?>
                <span class="origin-import-badge" title="Cliente importato da CSV" style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:.66rem;font-weight:700;background:#EEF2F7;color:#5a6b7b;vertical-align:middle;"><i class="bi bi-cloud-upload"></i> importato</span>
                <?php endif; ?>
                <?php if (!empty($c['is_blocked'])): ?>
                <span class="blocked-badge"><i class="bi bi-slash-circle"></i> Bloccato</span>
                <?php endif; ?>
                <?php if (!empty($c['unsubscribed'])): ?>
                <span class="unsub-badge"><i class="bi bi-envelope-slash"></i></span>
                <?php endif; ?>
                <?php if ($waLink): ?>
                <?php // Icona + parola: la sola icona verde non diceva a cosa serve. ?>
                <a href="<?= e($waLink) ?>" target="_blank" rel="noopener" class="wa-btn" title="Apre WhatsApp con gli auguri gia' scritti">
                    <i class="bi bi-whatsapp"></i> Invia auguri
                </a>
                <?php endif; ?>
            </div>
            <?php if ($bdayLine): ?>
            <div class="c-sub" style="color:<?= $bdayPassato ? '#9aa4ab' : '#D81B60' ?>;font-weight:600;">
                <?= e($bdayLine) ?><?= $bdayPassato ? ' · già festeggiato' : '' ?>
            </div>
            <?php elseif ($createdDate): ?>
            <div class="c-sub">Cliente dal <?= $createdDate ?></div>
            <?php endif; ?>
        </div>
        <div><span class="seg-badge <?= $seg ?>"><?= $segLabel ?></span></div>
        <div class="c-email"><?= e($c['email']) ?></div>
        <div class="c-phone"><?= e($c['phone']) ?></div>
        <div class="c-bookings"><?= (int)$c['total_bookings'] ?></div>
        <div class="c-noshow">
            <span class="noshow-count <?= $c['total_noshow'] > 0 ? 'has' : 'none' ?>">
                <?= (int)$c['total_noshow'] ?>
            </span>
        </div>
        <i class="bi bi-chevron-right c-arrow"></i>
    </div>
    <?php endforeach; ?>

    <?php if (!empty($pagination)): ?>
    <div class="pagination-bar">
        <span class="pagination-info"><?= $pagination['from'] ?>-<?= $pagination['to'] ?> di <?= $pagination['totalItems'] ?> client<?= $pagination['totalItems'] === 1 ? 'e' : 'i' ?></span>
        <div class="pagination-nav">
            <?php if ($pagination['prev']): ?>
            <a href="<?= $pagination['prev'] ?>" class="pg-btn"><i class="bi bi-chevron-left"></i></a>
            <?php endif; ?>
            <?php foreach ($pagination['pages'] as $pg): ?>
                <?php if ($pg['type'] === 'gap'): ?>
                    <span class="pg-gap">&hellip;</span>
                <?php else: ?>
                    <a href="<?= $pg['url'] ?>" class="pg-btn <?= $pg['active'] ? 'pg-active' : '' ?>"><?= $pg['number'] ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if ($pagination['next']): ?>
            <a href="<?= $pagination['next'] ?>" class="pg-btn"><i class="bi bi-chevron-right"></i></a>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="pagination-bar">
        <span class="pagination-info"><?= count($customers) ?> client<?= count($customers) === 1 ? 'e' : 'i' ?></span>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Mobile Card List -->
<div class="mobile-list">
    <?php if (empty($customers)): ?>
    <div class="empty-state">
        <i class="bi bi-people"></i>
        <p>Nessun cliente trovato.</p>
    </div>
    <?php else: ?>
    <?php foreach ($customers as $c):
        [$seg, $segLabel] = customerSegment((int)$c['total_bookings'], $thOcc, $thAbi, $thVip);
        $initials = mb_strtoupper(mb_substr($c['first_name'], 0, 1) . mb_substr($c['last_name'], 0, 1));
        $avatarColor = $avatarColors[$seg] ?? '#757575';
        // Ricalcolato per QUESTO cliente: vedi la nota su $makeWaLink in cima.
        $waLink = $currentSeg === 'compleanno' ? $makeWaLink($c) : null;
    ?>
    <?php // Non piu' un <a> che avvolge tutto: dentro c'e' il link WhatsApp, e un
          // link dentro un link non e' valido. Con data-url la card resta cliccabile
          // (gestore globale in layouts/dashboard.php, che ignora i click sui link). ?>
    <div data-url="<?= url("dashboard/customers/{$c['id']}") ?>" class="mobile-card<?= !empty($c['is_blocked']) ? ' cust-blocked' : '' ?>">
        <div class="mc-avatar" style="background:<?= !empty($c['is_blocked']) ? '#dc3545' : $avatarColor ?>;"><?= $initials ?></div>
        <div class="mc-info">
            <div class="mc-name">
                <?= e($c['first_name'] . ' ' . $c['last_name']) ?>
                <?php if (($c['source'] ?? '') === 'import'): ?>
                <span class="origin-import-badge" title="Importato da CSV" style="display:inline-block;padding:0 6px;border-radius:10px;font-size:.62rem;font-weight:700;background:#EEF2F7;color:#5a6b7b;vertical-align:middle;"><i class="bi bi-cloud-upload"></i></span>
                <?php endif; ?>
                <?php if (!empty($c['is_blocked'])): ?>
                <span class="blocked-badge"><i class="bi bi-slash-circle"></i></span>
                <?php endif; ?>
                <?php if (!empty($c['unsubscribed'])): ?>
                <span class="unsub-badge"><i class="bi bi-envelope-slash"></i></span>
                <?php endif; ?>
            </div>
            <?php if ($currentSeg === 'compleanno' && !empty($c['birthday'])):
                $bdM = date_create($c['birthday']); ?>
            <?php $etaM = (int)date('Y') - (int)$bdM->format('Y');
                  $passatoM = (int)$bdM->format('j') < (int)date('j'); ?>
            <div class="mc-meta" style="color:<?= $passatoM ? '#9aa4ab' : '#D81B60' ?>;font-weight:600;">🎂 <?= (int)$bdM->format('j') ?> <?= $MESI_IT[(int)$bdM->format('n')] ?><?= ($etaM >= 5 && $etaM <= 110) ? ' · compie ' . $etaM . ' anni' : '' ?><?= $passatoM ? ' · già festeggiato' : '' ?></div>
            <?php else: ?>
            <div class="mc-meta"><?= e($c['phone']) ?> &middot; <?= (int)$c['total_bookings'] ?> pren.</div>
            <?php endif; ?>
        </div>
        <div class="mc-right">
            <span class="seg-badge <?= $seg ?>"><?= $segLabel ?></span>
            <?php if ($c['total_noshow'] > 0): ?>
            <span class="noshow-count has"><?= (int)$c['total_noshow'] ?></span>
            <?php endif; ?>
            <?php if ($waLink): ?>
            <a href="<?= e($waLink) ?>" target="_blank" rel="noopener" class="wa-btn" title="Apre WhatsApp con gli auguri gia' scritti">
                <i class="bi bi-whatsapp"></i> Invia auguri
            </a>
            <?php endif; ?>
            <i class="bi bi-chevron-right" style="color:#d0d0d0;font-size:.7rem;"></i>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if (!empty($pagination)): ?>
    <div class="pagination-bar" style="padding:.75rem 1rem;">
        <span class="pagination-info"><?= $pagination['from'] ?>-<?= $pagination['to'] ?> di <?= $pagination['totalItems'] ?></span>
        <div class="pagination-nav">
            <?php if ($pagination['prev']): ?>
            <a href="<?= $pagination['prev'] ?>" class="pg-btn"><i class="bi bi-chevron-left"></i></a>
            <?php endif; ?>
            <?php foreach ($pagination['pages'] as $pg): ?>
                <?php if ($pg['type'] === 'gap'): ?>
                    <span class="pg-gap">&hellip;</span>
                <?php else: ?>
                    <a href="<?= $pg['url'] ?>" class="pg-btn <?= $pg['active'] ? 'pg-active' : '' ?>"><?= $pg['number'] ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if ($pagination['next']): ?>
            <a href="<?= $pagination['next'] ?>" class="pg-btn"><i class="bi bi-chevron-right"></i></a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php if (!empty($deletableImportedCount)): ?>
<!-- Modale conferma forte: eliminazione bulk clienti importati mai prenotati -->
<div id="bulkDelOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1050;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:14px;max-width:460px;width:100%;padding:1.4rem 1.5rem;box-shadow:0 20px 60px rgba(0,0,0,.3);">
        <div style="width:42px;height:42px;border-radius:50%;background:#FFEBEE;color:#C62828;display:flex;align-items:center;justify-content:center;font-size:1.2rem;margin-bottom:.6rem;">
            <i class="bi bi-exclamation-triangle"></i>
        </div>
        <h5 style="font-weight:800;margin:0 0 .5rem;">Elimina clienti importati</h5>
        <p style="font-size:.88rem;color:#495057;margin:0 0 .2rem;">Stai per eliminare definitivamente:</p>
        <div style="font-size:2rem;font-weight:800;color:#B71C1C;text-align:center;margin:.3rem 0;"><?= (int)$deletableImportedCount ?> client<?= (int)$deletableImportedCount === 1 ? 'e' : 'i' ?></div>
        <p style="text-align:center;font-size:.82rem;color:#6c757d;margin:0 0 .6rem;">importati che non hanno mai prenotato né ordinato.</p>
        <div style="background:#E8F5E9;border-radius:8px;padding:.6rem .8rem;font-size:.8rem;color:#1B5E20;margin:.2rem 0 .8rem;">
            <i class="bi bi-shield-check me-1"></i> Restano <strong>protetti</strong> i clienti che hanno prenotato o ordinato — non vengono toccati.
        </div>
        <p style="color:#B71C1C;font-size:.82rem;font-weight:600;margin:0 0 .5rem;"><i class="bi bi-exclamation-octagon me-1"></i> Operazione NON reversibile.</p>
        <form method="POST" action="<?= url('dashboard/customers/bulk-delete-imported') ?>">
            <?= csrf_field() ?>
            <label style="font-size:.8rem;color:#6c757d;display:block;">Per confermare, digita il numero <strong><?= (int)$deletableImportedCount ?></strong>:</label>
            <input type="text" name="confirm_count" id="bulkDelConfirmInput" autocomplete="off" inputmode="numeric"
                   style="width:100%;border:2px solid #dee2e6;border-radius:8px;padding:.5rem .7rem;font-size:1rem;font-weight:700;text-align:center;margin-top:.3rem;">
            <div style="display:flex;justify-content:flex-end;gap:.5rem;margin-top:1.1rem;">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="bulkDelCancel">Annulla</button>
                <button type="submit" class="btn btn-sm btn-danger" id="bulkDelSubmit" disabled>Elimina <?= (int)$deletableImportedCount ?></button>
            </div>
        </form>
    </div>
</div>
<script nonce="<?= csp_nonce() ?>">
(function(){
    var btn = document.getElementById('bulkDelImportedBtn');
    var ovl = document.getElementById('bulkDelOverlay');
    if (!btn || !ovl) return;
    var input = document.getElementById('bulkDelConfirmInput');
    var submit = document.getElementById('bulkDelSubmit');
    var cancel = document.getElementById('bulkDelCancel');
    var target = String(btn.getAttribute('data-count'));
    function open(){ ovl.style.display = 'flex'; input.value = ''; submit.disabled = true; setTimeout(function(){ input.focus(); }, 50); }
    function close(){ ovl.style.display = 'none'; }
    btn.addEventListener('click', open);
    cancel.addEventListener('click', close);
    ovl.addEventListener('click', function(e){ if (e.target === ovl) close(); });
    input.addEventListener('input', function(){ submit.disabled = (input.value.trim() !== target); });
})();
</script>
<?php endif; ?>