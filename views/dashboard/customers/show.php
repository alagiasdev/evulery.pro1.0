<?php
// Soglie segmento dal tenant (configurabili in Impostazioni > Generali)
$thOcc = (int)($tenant['segment_occasionale'] ?? 2);
$thAbi = (int)($tenant['segment_abituale'] ?? 4);
$thVip = (int)($tenant['segment_vip'] ?? 10);

// Calcolo segmento cliente
$bookings = (int)$customer['total_bookings'];
$noshow = (int)$customer['total_noshow'];
if ($bookings >= $thVip) { $segment = 'vip'; $segmentLabel = 'VIP'; $segmentBadge = 'bg-warning text-dark'; }
elseif ($bookings >= $thAbi) { $segment = 'abituale'; $segmentLabel = 'Abituale'; $segmentBadge = 'bg-success'; }
elseif ($bookings >= $thOcc) { $segment = 'occasionale'; $segmentLabel = 'Occasionale'; $segmentBadge = 'bg-info text-dark'; }
else { $segment = 'nuovo'; $segmentLabel = 'Nuovo'; $segmentBadge = 'bg-secondary'; }

// Statistiche calcolate dalle prenotazioni
$completedRes = array_filter($reservations, fn($r) => in_array($r['status'], ['confirmed', 'arrived']));
$avgPartySize = !empty($completedRes) ? round(array_sum(array_column($completedRes, 'party_size')) / count($completedRes), 1) : 0;
$noshowRate = $bookings > 0 ? round(($noshow / $bookings) * 100) : 0;
$lastVisit = null;
foreach ($reservations as $r) {
    if ($r['status'] === 'arrived') { $lastVisit = $r['reservation_date']; break; }
}
// Affidabilità: 100% - tasso no-show (con minimo 0)
$reliability = $bookings > 0 ? max(0, 100 - $noshowRate) : 100;
if ($reliability >= 90) { $reliabilityColor = 'success'; $reliabilityLabel = 'Ottima'; }
elseif ($reliability >= 70) { $reliabilityColor = 'warning'; $reliabilityLabel = 'Buona'; }
elseif ($reliability >= 50) { $reliabilityColor = 'danger'; $reliabilityLabel = 'Scarsa'; }
else { $reliabilityColor = 'danger'; $reliabilityLabel = 'Critica'; }
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <?= e($customer['first_name'] . ' ' . $customer['last_name']) ?>
            <span class="badge <?= $segmentBadge ?> fs-6 align-middle"><?= $segmentLabel ?></span>
        </h2>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('dashboard/reservations/create?customer_id=' . (int)$customer['id']) ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Nuova prenotazione
        </a>
        <a href="<?= url('dashboard/customers') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Torna alla lista
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <!-- Informazioni -->
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Informazioni</h5></div>
            <div class="card-body">
                <p><strong>Email:</strong> <a href="mailto:<?= e($customer['email']) ?>"><?= e($customer['email']) ?></a></p>
                <p><strong>Telefono:</strong> <a href="tel:<?= e($customer['phone']) ?>"><?= e($customer['phone']) ?></a></p>
                <p><strong>Prenotazioni:</strong> <?= $bookings ?></p>
                <p><strong>No-show:</strong> <?= $noshow ?></p>
                <p class="mb-0"><strong>Cliente dal:</strong> <?= format_date($customer['created_at']) ?></p>
            </div>
        </div>

        <!-- Statistiche -->
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Statistiche</h5></div>
            <div class="card-body">
                <p><strong>Media coperti:</strong> <?= $avgPartySize ?> pax</p>
                <p><strong>Tasso no-show:</strong> <?= $noshowRate ?>%</p>
                <p><strong>Ultima visita:</strong> <?= $lastVisit ? format_date($lastVisit) : '<span class="text-muted">—</span>' ?></p>
                <div class="mt-2">
                    <strong>Affidabilità:</strong> <span class="text-<?= $reliabilityColor ?>"><?= $reliabilityLabel ?> (<?= $reliability ?>%)</span>
                    <div class="progress mt-1" style="height: 8px;">
                        <div class="progress-bar bg-<?= $reliabilityColor ?>" style="width: <?= $reliability ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Note Cliente -->
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Note Cliente</h5></div>
            <div class="card-body">
                <form method="POST" action="<?= url("dashboard/customers/{$customer['id']}/notes") ?>">
                    <?= csrf_field() ?>
                    <textarea class="form-control mb-2" name="notes" rows="3" placeholder="Allergie, preferenze, tavolo preferito..."><?= e($customer['notes'] ?? '') ?></textarea>
                    <button type="submit" class="btn btn-sm btn-outline-primary">Salva note</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Storico Prenotazioni</h5></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Orario</th>
                            <th>Persone</th>
                            <th>Stato</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reservations)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Nessuna prenotazione.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($reservations as $r): ?>
                        <tr class="reservation-row" data-url="<?= url("dashboard/reservations/{$r['id']}") ?>">
                            <td><?= format_date($r['reservation_date']) ?></td>
                            <td><?= format_time($r['reservation_time']) ?></td>
                            <td><?= (int)$r['party_size'] ?> pax</td>
                            <td><span class="badge <?= status_badge($r['status']) ?>"><?= status_label($r['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
