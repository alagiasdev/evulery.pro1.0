<?php
// Segment thresholds
$thOcc = (int)($tenant['segment_occasionale'] ?? 2);
$thAbi = (int)($tenant['segment_abituale'] ?? 4);
$thVip = (int)($tenant['segment_vip'] ?? 10);

// Calculate segment
$bookings = (int)$customer['total_bookings'];
$noshow = (int)$customer['total_noshow'];
if ($bookings >= $thVip) { $segment = 'vip'; $segmentLabel = 'VIP'; }
elseif ($bookings >= $thAbi) { $segment = 'abituale'; $segmentLabel = 'Abituale'; }
elseif ($bookings >= $thOcc) { $segment = 'occasionale'; $segmentLabel = 'Occasionale'; }
else { $segment = 'nuovo'; $segmentLabel = 'Nuovo'; }

// Avatar color
$avatarColors = ['vip' => '#E65100', 'abituale' => '#2E7D32', 'occasionale' => '#1565C0', 'nuovo' => '#757575'];
$avatarColor = $avatarColors[$segment];
$initials = mb_strtoupper(mb_substr($customer['first_name'], 0, 1) . mb_substr($customer['last_name'], 0, 1));

// Stats
$completedRes = array_filter($reservations, fn($r) => in_array($r['status'], ['confirmed', 'arrived']));
$avgPartySize = !empty($completedRes) ? round(array_sum(array_column($completedRes, 'party_size')) / count($completedRes), 1) : 0;
$noshowRate = $bookings > 0 ? round(($noshow / $bookings) * 100) : 0;
// Use DB last_visit first, fallback to reservation history
$lastVisit = $customer['last_visit'] ?? null;
if (!$lastVisit) {
    foreach ($reservations as $r) {
        if ($r['status'] === 'arrived') { $lastVisit = $r['reservation_date']; break; }
    }
}
// Tags
$customerTags = [];
if (!empty($customer['tags'])) {
    $decoded = is_string($customer['tags']) ? json_decode($customer['tags'], true) : $customer['tags'];
    $customerTags = is_array($decoded) ? $decoded : [];
}

// Tag color classification (keyword-based)
function tagColorClass(string $tag): string {
    $lower = mb_strtolower($tag);
    // Geographic
    $geoKeywords = ['provincia', 'regione', 'nazionalit', 'citta', 'città', 'paese', 'cap ', 'zona'];
    foreach ($geoKeywords as $kw) {
        if (str_contains($lower, $kw)) return 'tag-geo';
    }
    // Demographic
    $demoKeywords = ['segno', 'anno ', 'eta ', 'età ', 'gennaio', 'febbraio', 'marzo', 'aprile', 'maggio', 'giugno', 'luglio', 'agosto', 'settembre', 'ottobre', 'novembre', 'dicembre'];
    foreach ($demoKeywords as $kw) {
        if (str_contains($lower, $kw)) return 'tag-demo';
    }
    // Behavior
    $behaviorKeywords = ['vip', 'cena', 'pranzo', 'compleanno', 'arrivato', 'no show', 'noshow', 'fuori prenotazione', 'walk-in', 'walkin', 'fedele', 'nuovo', 'inattivo'];
    foreach ($behaviorKeywords as $kw) {
        if (str_contains($lower, $kw)) return 'tag-behavior';
    }
    return '';
}

// Reliability
$reliability = $bookings > 0 ? max(0, 100 - $noshowRate) : 100;
if ($reliability >= 90) { $relColor = 'var(--brand)'; $relLabel = 'Ottima'; }
elseif ($reliability >= 70) { $relColor = '#ffc107'; $relLabel = 'Buona'; }
elseif ($reliability >= 50) { $relColor = '#E65100'; $relLabel = 'Scarsa'; }
else { $relColor = '#dc3545'; $relLabel = 'Critica'; }

// Customer age (how long they've been a customer)
$createdAt = new DateTime($customer['created_at']);
$now = new DateTime();
$diff = $now->diff($createdAt);
if ($diff->y > 0) $customerAge = $diff->y . ' ann' . ($diff->y === 1 ? 'o' : 'i');
elseif ($diff->m > 0) $customerAge = $diff->m . ' mes' . ($diff->m === 1 ? 'e' : 'i');
else $customerAge = $diff->d . ' giorni';

// Birthday: age + proximity check
$age = null;
$bdayDaysAway = null;
$bdayNextAge = null;
if (!empty($customer['birthday'])) {
    $bday = new DateTime($customer['birthday']);
    $today = new DateTime();
    $age = $today->diff($bday)->y;
    // Calculate days until next birthday
    $nextBday = new DateTime($today->format('Y') . '-' . $bday->format('m-d'));
    if ($nextBday < $today) {
        $nextBday->modify('+1 year');
    }
    $bdayDaysAway = (int)$today->diff($nextBday)->days;
    $bdayNextAge = $age + ($nextBday->format('Y') > $today->format('Y') || ($nextBday->format('Y') === $today->format('Y') && $bdayDaysAway > 0) ? 1 : 0);
    if ($bdayDaysAway === 0) $bdayNextAge = $age;
}

// Stat trends: compare current month vs previous month reservations
$curMonth = date('Y-m');
$prevMonth = date('Y-m', strtotime('-1 month'));
$curMonthBookings = 0;
$prevMonthBookings = 0;
$curMonthNoshow = 0;
$prevMonthNoshow = 0;
foreach ($reservations as $r) {
    $rm = substr($r['reservation_date'], 0, 7);
    if ($rm === $curMonth) {
        $curMonthBookings++;
        if ($r['status'] === 'noshow') $curMonthNoshow++;
    } elseif ($rm === $prevMonth) {
        $prevMonthBookings++;
        if ($r['status'] === 'noshow') $prevMonthNoshow++;
    }
}
$bookingTrend = $curMonthBookings - $prevMonthBookings;
$noshowTrend = $curMonthNoshow - $prevMonthNoshow;

// Frequency chart: last 6 months
$freqMonths = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-{$i} months"));
    $freqMonths[$m] = 0;
}
foreach ($reservations as $r) {
    if (!in_array($r['status'], ['confirmed', 'arrived'])) continue;
    $rm = substr($r['reservation_date'], 0, 7);
    if (isset($freqMonths[$rm])) {
        $freqMonths[$rm]++;
    }
}
$freqMax = max(1, max($freqMonths));
$monthNamesShort = ['01' => 'Gen', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr', '05' => 'Mag', '06' => 'Giu', '07' => 'Lug', '08' => 'Ago', '09' => 'Set', '10' => 'Ott', '11' => 'Nov', '12' => 'Dic'];

// History status counts
$statusCounts = ['all' => count($reservations), 'confirmed' => 0, 'arrived' => 0, 'noshow' => 0, 'cancelled' => 0];
foreach ($reservations as $r) {
    $st = $r['status'];
    if (isset($statusCounts[$st])) $statusCounts[$st]++;
}

// Insights
$insights = [];
$cenaCount = 0; $pranzoCount = 0;
$dayFreq = []; $timeSlots = [];
foreach ($reservations as $r) {
    if (!in_array($r['status'], ['confirmed', 'arrived'])) continue;
    $hour = (int)substr($r['reservation_time'], 0, 2);
    if ($hour >= 16) $cenaCount++; else $pranzoCount++;
    $dow = (new DateTime($r['reservation_date']))->format('N');
    $dayFreq[$dow] = ($dayFreq[$dow] ?? 0) + 1;
    $timeSlots[] = substr($r['reservation_time'], 0, 5);
}
if ($cenaCount > $pranzoCount && $cenaCount >= 2) $insights[] = ['bi-moon-stars', '#5C6BC0', 'Preferisce la cena'];
elseif ($pranzoCount > $cenaCount && $pranzoCount >= 2) $insights[] = ['bi-sun', '#FF9800', 'Preferisce il pranzo'];
if (!empty($dayFreq)) {
    $topDay = array_keys($dayFreq, max($dayFreq))[0];
    $dayNames = [1 => 'lunedì', 2 => 'martedì', 3 => 'mercoledì', 4 => 'giovedì', 5 => 'venerdì', 6 => 'sabato', 7 => 'domenica'];
    if (max($dayFreq) >= 2) $insights[] = ['bi-calendar-week', 'var(--brand)', 'Viene il ' . $dayNames[$topDay]];
}
if ($avgPartySize > 0) {
    $paxLabel = $avgPartySize <= 2 ? 'Tavoli da 1-2' : ($avgPartySize <= 4 ? 'Tavoli da 2-4' : 'Gruppi grandi');
    $insights[] = ['bi-people', '#E65100', $paxLabel];
}
if (!empty($timeSlots)) {
    $times = array_unique($timeSlots);
    sort($times);
    if (count($times) <= 3) $insights[] = ['bi-clock', '#1565C0', 'Ore ' . implode('-', array_slice($times, 0, 2))];
}

// Source labels
$sourceLabels = ['phone' => 'Telefono', 'walkin' => 'Walk-in', 'widget' => 'Widget', 'altro' => 'Altro'];
$sourceIcons = [
    'import'  => ['bi-cloud-upload', 'Importato da CSV'],
    'manual'  => ['bi-pencil-square', 'Aggiunto manualmente'],
    'ordering'=> ['bi-bag-check', 'Da ordine online'],
];
$sourceLabelsPrivacy = [
    'import' => 'Importazione CSV', 'manual' => 'Inserimento manuale',
    'ordering' => 'Ordine online', 'booking' => 'Prenotazione',
];
?>

<!-- Breadcrumb -->
<div class="page-back">
    <a href="<?= url('dashboard/customers') ?>"><i class="bi bi-arrow-left"></i> Clienti</a>
    <span style="color:#adb5bd;font-size:.82rem;margin-left:.25rem;">/ <?= e($customer['first_name'] . ' ' . $customer['last_name']) ?></span>
</div>

<?php if (!empty($customer['is_blocked'])): ?>
<div class="blocked-banner">
    <i class="bi bi-slash-circle"></i>
    <div>
        <strong>Cliente bloccato</strong>
        <span>Non può effettuare prenotazioni<?= $customer['blocked_at'] ? ' — bloccato il ' . format_date($customer['blocked_at'], 'd/m/Y') : '' ?></span>
    </div>
</div>
<?php endif; ?>

<?php if ($bdayDaysAway !== null && $bdayDaysAway <= 7): ?>
<!-- Birthday alert banner -->
<div class="cs-birthday-alert">
    <span class="cs-birthday-alert-icon">🎂</span>
    <div class="cs-birthday-alert-text">
        <?php if ($bdayDaysAway === 0): ?>
        <strong>Oggi è il compleanno!</strong> <?= e($customer['first_name']) ?> compie <strong><?= $bdayNextAge ?> anni</strong>.
        <?php elseif ($bdayDaysAway === 1): ?>
        <strong>Compleanno domani!</strong> <?= e($customer['first_name']) ?> compie <strong><?= $bdayNextAge ?> anni</strong> il <?= format_date($customer['birthday'], 'd F') ?>.
        <?php else: ?>
        <strong>Compleanno tra <?= $bdayDaysAway ?> giorni!</strong> <?= e($customer['first_name']) ?> compie <strong><?= $bdayNextAge ?> anni</strong> il <?= format_date($customer['birthday'], 'd F') ?>.
        <?php endif; ?>
    </div>
    <?php
        $waNum = preg_replace('/[^0-9]/', '', $customer['phone']);
        if (str_starts_with($waNum, '0')) $waNum = '39' . substr($waNum, 1);
        elseif (!str_starts_with($waNum, '39') && strlen($waNum) <= 10) $waNum = '39' . $waNum;
    ?>
    <a href="https://wa.me/<?= e($waNum) ?>?text=<?= rawurlencode('Tanti auguri di buon compleanno! 🎂') ?>" target="_blank" rel="noopener" class="cs-birthday-alert-action">
        <i class="bi bi-whatsapp me-1"></i> Invia auguri
    </a>
</div>
<?php endif; ?>

<!-- Hero Card -->
<div class="hero-card hero-customer<?= !empty($customer['is_blocked']) ? ' cust-blocked' : '' ?>">
    <div class="hero-avatar" style="background: <?= !empty($customer['is_blocked']) ? '#dc3545' : $avatarColor ?>;">
        <?= $initials ?>
        <?php if ($bdayDaysAway !== null && $bdayDaysAway <= 7): ?>
        <div class="cs-bday-ring"></div>
        <?php endif; ?>
    </div>
    <div class="hero-info">
        <div class="hero-name">
            <?= e($customer['first_name'] . ' ' . $customer['last_name']) ?>
            <span class="seg-badge <?= $segment ?>"><?= $segmentLabel ?></span>
            <?php if (!empty($customer['is_blocked'])): ?>
            <span class="blocked-badge"><i class="bi bi-slash-circle"></i> Bloccato</span>
            <?php endif; ?>
            <?php if (!empty($customer['unsubscribed'])): ?>
            <span class="unsub-badge"><i class="bi bi-envelope-slash"></i> Disiscritto</span>
            <?php endif; ?>
        </div>
        <div class="hero-contacts">
            <div class="hero-contact">
                <i class="bi bi-telephone-fill"></i>
                <a href="tel:<?= e($customer['phone']) ?>"><?= e($customer['phone']) ?></a>
            </div>
            <div class="hero-contact">
                <?php
                    $waNum = preg_replace('/[^0-9]/', '', $customer['phone']);
                    if (str_starts_with($waNum, '0')) $waNum = '39' . substr($waNum, 1);
                    elseif (!str_starts_with($waNum, '39') && strlen($waNum) <= 10) $waNum = '39' . $waNum;
                ?>
                <i class="bi bi-whatsapp"></i>
                <a href="https://wa.me/<?= e($waNum) ?>" target="_blank" rel="noopener">Inizia a Chattare</a>
            </div>
            <div class="hero-contact">
                <i class="bi bi-envelope-fill"></i>
                <a href="mailto:<?= e($customer['email']) ?>"><?= e($customer['email']) ?></a>
            </div>
        </div>
        <div class="hero-meta">
            Cliente dal <?= format_date($customer['created_at'], 'd F Y') ?>
            <?php
                $src = $customer['source'] ?? 'booking';
                if (isset($sourceIcons[$src])):
            ?>
            <span class="source-badge"><i class="bi <?= $sourceIcons[$src][0] ?>"></i> <?= $sourceIcons[$src][1] ?></span>
            <?php endif; ?>
            <?php if ($age !== null): ?>
            <span class="cs-hero-birthday" title="Data di nascita">
                <i class="bi bi-cake2"></i> <?= format_date($customer['birthday'], 'd M Y') ?> · <?= $age ?> anni
            </span>
            <?php endif; ?>
        </div>
    </div>
    <div class="hero-actions">
        <?php if (empty($customer['is_blocked'])): ?>
        <a href="<?= url('dashboard/reservations/create?customer_id=' . (int)$customer['id']) ?>" class="btn btn-brand">
            <i class="bi bi-plus-circle me-1"></i> Prenota
        </a>
        <?php endif; ?>
        <form method="POST" action="<?= url("dashboard/customers/{$customer['id']}/toggle-block") ?>" style="display:inline;">
            <?= csrf_field() ?>
            <?php if (!empty($customer['is_blocked'])): ?>
            <button type="submit" class="btn btn-outline-success" title="Sblocca cliente">
                <i class="bi bi-unlock me-1"></i> Sblocca
            </button>
            <?php else: ?>
            <button type="submit" class="btn btn-outline-danger" title="Blocca cliente"
                    data-confirm="Bloccare <?= e($customer['first_name'] . ' ' . $customer['last_name']) ?>? Non potrà prenotare.">
                <i class="bi bi-slash-circle me-1"></i> Blocca
            </button>
            <?php endif; ?>
        </form>
        <?php if (!empty($customer['unsubscribed'])): ?>
        <form method="POST" action="<?= url("dashboard/customers/{$customer['id']}/resubscribe") ?>" style="display:inline;">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-success" title="Re-iscrivi alle comunicazioni"
                    data-confirm="Re-iscrivere <?= e($customer['first_name'] . ' ' . $customer['last_name']) ?> alle comunicazioni email?">
                <i class="bi bi-envelope-check me-1"></i> Re-iscrivi
            </button>
        </form>
        <?php endif; ?>
        <a href="<?= url('dashboard/customers') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Lista
        </a>
    </div>
</div>

<!-- Stats Strip -->
<div class="stats-strip">
    <div class="stat-card">
        <?php if ($bookingTrend > 0): ?>
        <span class="cs-stat-trend up">+<?= $bookingTrend ?></span>
        <?php elseif ($bookingTrend < 0): ?>
        <span class="cs-stat-trend down"><?= $bookingTrend ?></span>
        <?php else: ?>
        <span class="cs-stat-trend neutral">—</span>
        <?php endif; ?>
        <div class="stat-value" style="color: var(--brand);"><?= $bookings ?></div>
        <div class="stat-label">Prenotazioni</div>
    </div>
    <div class="stat-card">
        <?php if ($noshowTrend > 0): ?>
        <span class="cs-stat-trend down">+<?= $noshowTrend ?></span>
        <?php elseif ($noshowTrend < 0): ?>
        <span class="cs-stat-trend up"><?= $noshowTrend ?></span>
        <?php else: ?>
        <span class="cs-stat-trend neutral">—</span>
        <?php endif; ?>
        <div class="stat-value" style="color: #dc3545;"><?= $noshow ?></div>
        <div class="stat-label">No-show</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color: #6c757d;"><?= $avgPartySize ?></div>
        <div class="stat-label">Media coperti</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color: #495057;"><?= $lastVisit ? format_date($lastVisit, 'd M') : '—' ?></div>
        <div class="stat-label">Ultima visita</div>
    </div>
    <div class="stat-card">
        <?php if ($age !== null): ?>
        <div class="stat-value" style="color: #E65100;"><?= $age ?></div>
        <div class="stat-label">Età (anni)</div>
        <?php else: ?>
        <div class="stat-value" style="color: #adb5bd;"><?= $customerAge ?></div>
        <div class="stat-label">Cliente da</div>
        <?php endif; ?>
    </div>
</div>

<!-- Tags strip (full-width) -->
<div class="cs-tags-strip">
    <div class="cs-tags-header">
        <span class="cs-tags-title"><i class="bi bi-tags me-1"></i> Tag</span>
        <?php if (!empty($customerTags)): ?>
        <span class="cs-tags-count"><?= count($customerTags) ?> tag</span>
        <?php endif; ?>
    </div>
    <div class="cs-tags-wrap" id="csTagsWrap">
        <?php foreach ($customerTags as $tag):
            $colorClass = tagColorClass($tag);
        ?>
        <span class="customer-tag <?= $colorClass ?>">
            <?= e($tag) ?>
            <form method="POST" action="<?= url("dashboard/customers/{$customer['id']}/remove-tag") ?>" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="tag" value="<?= e($tag) ?>">
                <button type="submit" class="tag-remove" title="Rimuovi">&times;</button>
            </form>
        </span>
        <?php endforeach; ?>
        <?php if (empty($customerTags)): ?>
        <span style="font-size:.75rem;color:#adb5bd;">Nessun tag</span>
        <?php endif; ?>
        <form method="POST" action="<?= url("dashboard/customers/{$customer['id']}/add-tag") ?>" class="cs-add-tag-inline" style="display:inline-flex;">
            <?= csrf_field() ?>
            <input type="text" name="tag" placeholder="Nuovo tag..." maxlength="50" required>
            <button type="submit">+ Aggiungi</button>
        </form>
    </div>
    <?php if (count($customerTags) > 8): ?>
    <button class="cs-tag-toggle" id="csTagToggle">Mostra tutti (<?= count($customerTags) ?>)</button>
    <?php endif; ?>
</div>

<!-- Reliability bar -->
<div class="reliability-card">
    <div class="rel-header">
        <span class="rel-title"><i class="bi bi-shield-check me-1"></i> Affidabilità</span>
        <span class="rel-value" style="color: <?= $relColor ?>;"><?= $relLabel ?> — <?= $reliability ?>%</span>
    </div>
    <div class="rel-bar">
        <div class="rel-bar-fill" style="width: <?= $reliability ?>%; background: <?= $relColor ?>;"></div>
    </div>
</div>

<div class="row g-4">
    <!-- Left column: Profile card with tabs -->
    <div class="col-lg-4">
        <div class="cs-profile-card">
            <div class="cs-profile-tabs">
                <button class="cs-profile-tab active" data-tab="profilo">
                    <i class="bi bi-person"></i> <span>Profilo</span>
                </button>
                <button class="cs-profile-tab" data-tab="note">
                    <i class="bi bi-sticky"></i> <span>Note</span>
                </button>
                <button class="cs-profile-tab" data-tab="privacy">
                    <i class="bi bi-shield-lock"></i> <span>Privacy</span>
                </button>
            </div>
            <div class="cs-profile-body">

                <!-- Tab: Profilo -->
                <div class="cs-profile-panel active" id="csTab-profilo">
                    <?php if (!empty($insights)): ?>
                    <div class="cs-profile-section-title"><i class="bi bi-lightbulb"></i> Insight</div>
                    <div style="margin-bottom: .75rem;">
                        <?php foreach ($insights as $ins): ?>
                        <span class="insight-chip"><i class="bi <?= $ins[0] ?>" style="color:<?= $ins[1] ?>;"></i> <?= e($ins[2]) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <hr style="margin: .75rem 0; border-color: #f0f0f0;">
                    <?php endif; ?>

                    <div class="cs-profile-section-title"><i class="bi bi-cake2"></i> Data di nascita</div>
                    <form method="POST" action="<?= url("dashboard/customers/{$customer['id']}/birthday") ?>" class="d-flex align-items-center gap-2 mb-2">
                        <?= csrf_field() ?>
                        <input type="date" class="form-control form-control-sm" name="birthday" value="<?= e($customer['birthday'] ?? '') ?>" style="max-width:160px; font-size:.8rem;">
                        <button type="submit" class="btn-action btn-act-edit" style="display:inline-flex;white-space:nowrap;">
                            <i class="bi bi-check-lg"></i> Salva
                        </button>
                    </form>

                    <?php if (!empty($reservations)): ?>
                    <hr style="margin: .75rem 0; border-color: #f0f0f0;">
                    <div class="cs-profile-section-title"><i class="bi bi-graph-up"></i> Frequenza visite</div>
                    <div style="font-size:.65rem;color:#adb5bd;margin-bottom:.25rem;">Ultimi 6 mesi</div>
                    <div class="cs-frequency-chart">
                        <?php foreach ($freqMonths as $ym => $count):
                            $pct = round(($count / $freqMax) * 100);
                            $mLabel = $monthNamesShort[substr($ym, 5, 2)] ?? '';
                        ?>
                        <div class="cs-freq-bar" style="height:<?= max(5, $pct) ?>%;" data-tip="<?= $mLabel ?>: <?= $count ?>"></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="cs-freq-labels">
                        <?php foreach ($freqMonths as $ym => $count):
                            $mLabel = $monthNamesShort[substr($ym, 5, 2)] ?? '';
                        ?>
                        <span class="cs-freq-label"><?= $mLabel ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Tab: Note -->
                <div class="cs-profile-panel" id="csTab-note">
                    <div class="cs-profile-section-title"><i class="bi bi-sticky"></i> Note interne</div>
                    <form method="POST" action="<?= url("dashboard/customers/{$customer['id']}/notes") ?>">
                        <?= csrf_field() ?>
                        <textarea class="notes-textarea" name="notes" rows="5" placeholder="Allergie, preferenze, tavolo preferito..."><?= e($customer['notes'] ?? '') ?></textarea>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span style="font-size:.65rem;color:#adb5bd;">
                                <?php if (!empty($customer['updated_at'])): ?>
                                Ultima modifica: <?= format_date($customer['updated_at'], 'd M Y') ?>
                                <?php endif; ?>
                            </span>
                            <button type="submit" class="btn-action btn-act-edit" style="display:inline-flex;">
                                <i class="bi bi-check-lg"></i> Salva note
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tab: Privacy -->
                <div class="cs-profile-panel" id="csTab-privacy">
                    <div class="cs-profile-section-title"><i class="bi bi-shield-lock"></i> Privacy e consensi</div>
                    <div class="cs-privacy-row">
                        <span class="cs-privacy-label">Email marketing</span>
                        <?php if (!empty($customer['unsubscribed'])): ?>
                        <span class="cs-privacy-value cs-privacy-no"><i class="bi bi-x-circle me-1"></i> Disiscritto</span>
                        <?php else: ?>
                        <span class="cs-privacy-value cs-privacy-ok"><i class="bi bi-check-circle me-1"></i> Iscritto</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($customer['unsubscribed_at'])): ?>
                    <div class="cs-privacy-row">
                        <span class="cs-privacy-label">Data disiscrizione</span>
                        <span class="cs-privacy-value"><?= format_date($customer['unsubscribed_at'], 'd M Y') ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="cs-privacy-row">
                        <span class="cs-privacy-label">Fonte dati</span>
                        <span class="cs-privacy-value"><?= e($sourceLabelsPrivacy[$customer['source'] ?? 'booking'] ?? 'Prenotazione') ?></span>
                    </div>
                    <div class="cs-privacy-row">
                        <span class="cs-privacy-label">Data acquisizione</span>
                        <span class="cs-privacy-value"><?= format_date($customer['created_at'], 'd M Y') ?></span>
                    </div>
                    <div class="cs-privacy-row">
                        <span class="cs-privacy-label">Stato account</span>
                        <?php if (!empty($customer['is_blocked'])): ?>
                        <span class="cs-privacy-value cs-privacy-no"><i class="bi bi-slash-circle me-1"></i> Bloccato</span>
                        <?php else: ?>
                        <span class="cs-privacy-value cs-privacy-ok"><i class="bi bi-check-circle me-1"></i> Attivo</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($customer['unsubscribed'])): ?>
                    <div class="cs-privacy-info">
                        <i class="bi bi-info-circle me-1"></i> Questo cliente si è disiscritto dalle comunicazioni email. Puoi re-iscriverlo dal pulsante nell'header.
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <!-- Right column: History with filters -->
    <div class="col-lg-8">
        <div class="section-label"><i class="bi bi-clock-history"></i> Storico prenotazioni</div>

        <?php if (empty($reservations)): ?>
        <div class="card">
            <div class="empty-state">
                <i class="bi bi-calendar-x"></i>
                <p>Nessuna prenotazione.</p>
            </div>
        </div>
        <?php else: ?>

        <!-- Desktop history -->
        <div class="card desktop-history">
            <!-- Filter chips -->
            <div class="cs-history-filters">
                <span class="cs-filter-chip active" data-filter="all">Tutte <span class="cs-fc-count"><?= $statusCounts['all'] ?></span></span>
                <span class="cs-filter-chip" data-filter="confirmed"><i class="bi bi-check-circle me-1" style="color:var(--brand);font-size:.6rem;"></i>Confermate <span class="cs-fc-count"><?= $statusCounts['confirmed'] ?></span></span>
                <span class="cs-filter-chip" data-filter="arrived"><i class="bi bi-person-check me-1" style="color:#1565C0;font-size:.6rem;"></i>Arrivati <span class="cs-fc-count"><?= $statusCounts['arrived'] ?></span></span>
                <span class="cs-filter-chip" data-filter="noshow"><i class="bi bi-x-circle me-1" style="color:#dc3545;font-size:.6rem;"></i>No-show <span class="cs-fc-count"><?= $statusCounts['noshow'] ?></span></span>
                <span class="cs-filter-chip" data-filter="cancelled"><i class="bi bi-dash-circle me-1" style="color:#adb5bd;font-size:.6rem;"></i>Annullate <span class="cs-fc-count"><?= $statusCounts['cancelled'] ?></span></span>
            </div>

            <div class="ch-header">
                <span>Data</span>
                <span>Ora</span>
                <span>Dettaglio</span>
                <span style="text-align:center;">Pax</span>
                <span style="text-align:right;">Stato</span>
                <span></span>
            </div>
            <?php foreach ($reservations as $r):
                $hour = (int)substr($r['reservation_time'], 0, 2);
                $mealType = $hour >= 16 ? 'Cena' : 'Pranzo';
                $source = $sourceLabels[$r['source'] ?? ''] ?? '';
                $detail = $mealType . ($source ? ' · ' . $source : '');
            ?>
            <div class="ch-row" data-url="<?= url("dashboard/reservations/{$r['id']}") ?>" data-status="<?= e($r['status']) ?>">
                <div class="ch-date"><?= format_date($r['reservation_date'], 'd M Y') ?></div>
                <div class="ch-time"><?= format_time($r['reservation_time']) ?></div>
                <div class="ch-detail"><?= e($detail) ?></div>
                <div class="ch-pax"><?= (int)$r['party_size'] ?></div>
                <div class="ch-status">
                    <span class="status-dot <?= e($r['status']) ?>"></span>
                    <span class="ch-status-label"><?= status_label($r['status']) ?></span>
                </div>
                <i class="bi bi-chevron-right" style="color:#d0d0d0;font-size:.7rem;"></i>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Mobile history -->
        <div class="mobile-history">
            <?php foreach ($reservations as $r):
                $hour = (int)substr($r['reservation_time'], 0, 2);
                $mealType = $hour >= 16 ? 'Cena' : 'Pranzo';
            ?>
            <a href="<?= url("dashboard/reservations/{$r['id']}") ?>" class="mh-card" data-status="<?= e($r['status']) ?>">
                <div>
                    <div class="mh-date"><?= format_date($r['reservation_date'], 'd M Y') ?> · <?= format_time($r['reservation_time']) ?></div>
                    <div class="mh-meta"><?= (int)$r['party_size'] ?> pax · <?= $mealType ?></div>
                </div>
                <div class="mh-right">
                    <div class="ch-status">
                        <span class="status-dot <?= e($r['status']) ?>"></span>
                        <span class="ch-status-label"><?= status_label($r['status']) ?></span>
                    </div>
                    <i class="bi bi-chevron-right" style="color:#d0d0d0;font-size:.7rem;"></i>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
(function() {
    // Confirm dialogs
    document.querySelectorAll('[data-confirm]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm)) e.preventDefault();
        });
    });

    // Profile tab switching
    document.querySelectorAll('.cs-profile-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.cs-profile-tab').forEach(function(t) { t.classList.remove('active'); });
            document.querySelectorAll('.cs-profile-panel').forEach(function(p) { p.classList.remove('active'); });
            this.classList.add('active');
            var panel = document.getElementById('csTab-' + this.dataset.tab);
            if (panel) panel.classList.add('active');
        });
    });

    // Tag collapse/expand
    var tagsWrap = document.getElementById('csTagsWrap');
    var tagToggle = document.getElementById('csTagToggle');
    if (tagsWrap && tagToggle) {
        tagsWrap.classList.add('collapsed');
        tagToggle.addEventListener('click', function() {
            if (tagsWrap.classList.contains('collapsed')) {
                tagsWrap.classList.remove('collapsed');
                this.textContent = 'Mostra meno';
            } else {
                tagsWrap.classList.add('collapsed');
                this.textContent = 'Mostra tutti (' + tagsWrap.querySelectorAll('.customer-tag').length + ')';
            }
        });
    }

    // History filter chips
    document.querySelectorAll('.cs-filter-chip').forEach(function(chip) {
        chip.addEventListener('click', function() {
            document.querySelectorAll('.cs-filter-chip').forEach(function(c) { c.classList.remove('active'); });
            this.classList.add('active');
            var filter = this.dataset.filter;
            // Filter desktop rows
            document.querySelectorAll('.ch-row[data-status]').forEach(function(row) {
                row.style.display = (filter === 'all' || row.dataset.status === filter) ? '' : 'none';
            });
            // Filter mobile cards
            document.querySelectorAll('.mh-card[data-status]').forEach(function(card) {
                card.style.display = (filter === 'all' || card.dataset.status === filter) ? '' : 'none';
            });
        });
    });

    // Clickable history rows
    document.querySelectorAll('.ch-row[data-url]').forEach(function(row) {
        row.addEventListener('click', function() {
            window.location.href = this.dataset.url;
        });
    });
})();
</script>
