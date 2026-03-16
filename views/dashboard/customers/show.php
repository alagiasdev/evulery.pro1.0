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
$lastVisit = null;
foreach ($reservations as $r) {
    if ($r['status'] === 'arrived') { $lastVisit = $r['reservation_date']; break; }
}

// Reliability
$reliability = $bookings > 0 ? max(0, 100 - $noshowRate) : 100;
if ($reliability >= 90) { $relColor = 'var(--brand)'; $relLabel = 'Ottima'; }
elseif ($reliability >= 70) { $relColor = '#ffc107'; $relLabel = 'Buona'; }
elseif ($reliability >= 50) { $relColor = '#E65100'; $relLabel = 'Scarsa'; }
else { $relColor = '#dc3545'; $relLabel = 'Critica'; }

// Customer age
$createdAt = new DateTime($customer['created_at']);
$now = new DateTime();
$diff = $now->diff($createdAt);
if ($diff->y > 0) $customerAge = $diff->y . ' ann' . ($diff->y === 1 ? 'o' : 'i');
elseif ($diff->m > 0) $customerAge = $diff->m . ' mes' . ($diff->m === 1 ? 'e' : 'i');
else $customerAge = $diff->d . ' giorni';

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
?>

<!-- Breadcrumb -->
<div class="page-back">
    <a href="<?= url('dashboard/customers') ?>"><i class="bi bi-arrow-left"></i> Clienti</a>
    <span style="color:#adb5bd;font-size:.82rem;margin-left:.25rem;">/ <?= e($customer['first_name'] . ' ' . $customer['last_name']) ?></span>
</div>

<!-- Hero Card -->
<div class="hero-card hero-customer">
    <div class="hero-avatar" style="background: <?= $avatarColor ?>;"><?= $initials ?></div>
    <div class="hero-info">
        <div class="hero-name">
            <?= e($customer['first_name'] . ' ' . $customer['last_name']) ?>
            <span class="seg-badge <?= $segment ?>"><?= $segmentLabel ?></span>
        </div>
        <div class="hero-contacts">
            <div class="hero-contact">
                <i class="bi bi-telephone-fill"></i>
                <a href="tel:<?= e($customer['phone']) ?>"><?= e($customer['phone']) ?></a>
            </div>
            <div class="hero-contact">
                <i class="bi bi-envelope-fill"></i>
                <a href="mailto:<?= e($customer['email']) ?>"><?= e($customer['email']) ?></a>
            </div>
        </div>
        <div class="hero-meta">Cliente dal <?= format_date($customer['created_at'], 'd F Y') ?></div>
    </div>
    <div class="hero-actions">
        <a href="<?= url('dashboard/reservations/create?customer_id=' . (int)$customer['id']) ?>" class="btn btn-brand">
            <i class="bi bi-plus-circle me-1"></i> Prenota
        </a>
        <a href="<?= url('dashboard/customers') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Lista
        </a>
    </div>
</div>

<!-- Stats Strip -->
<div class="stats-strip">
    <div class="stat-card">
        <div class="stat-value" style="color: var(--brand);"><?= $bookings ?></div>
        <div class="stat-label">Prenotazioni</div>
    </div>
    <div class="stat-card">
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
        <div class="stat-value" style="color: #adb5bd;"><?= $customerAge ?></div>
        <div class="stat-label">Cliente da</div>
    </div>
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
    <!-- Left column -->
    <div class="col-lg-4">
        <?php if (!empty($insights)): ?>
        <div class="section-label"><i class="bi bi-lightbulb"></i> Insight</div>
        <div style="margin-bottom: 1.25rem;">
            <?php foreach ($insights as $ins): ?>
            <span class="insight-chip"><i class="bi <?= $ins[0] ?>" style="color:<?= $ins[1] ?>;"></i> <?= e($ins[2]) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Notes -->
        <div class="notes-card">
            <div class="notes-label"><i class="bi bi-sticky me-1"></i> Note interne cliente</div>
            <form method="POST" action="<?= url("dashboard/customers/{$customer['id']}/notes") ?>">
                <?= csrf_field() ?>
                <textarea class="notes-textarea" name="notes" rows="3" placeholder="Allergie, preferenze, tavolo preferito..."><?= e($customer['notes'] ?? '') ?></textarea>
                <div class="d-flex justify-content-end mt-2">
                    <button type="submit" class="btn-action btn-act-edit" style="display:inline-flex;">
                        <i class="bi bi-check-lg"></i> Salva note
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Right column: History -->
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
            <div class="ch-row" data-url="<?= url("dashboard/reservations/{$r['id']}") ?>">
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
            <a href="<?= url("dashboard/reservations/{$r['id']}") ?>" class="mh-card">
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