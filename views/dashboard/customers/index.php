<?php
// Soglie segmento dal tenant
$thOcc = (int)($tenant['segment_occasionale'] ?? 2);
$thAbi = (int)($tenant['segment_abituale'] ?? 4);
$thVip = (int)($tenant['segment_vip'] ?? 10);

function customerSegment(int $bookings, int $thOcc, int $thAbi, int $thVip): array {
    if ($bookings >= $thVip) return ['vip', 'VIP', 'bg-warning text-dark'];
    if ($bookings >= $thAbi) return ['abituale', 'Abituale', 'bg-success'];
    if ($bookings >= $thOcc) return ['occasionale', 'Occasionale', 'bg-info text-dark'];
    return ['nuovo', 'Nuovo', 'bg-secondary'];
}

$cards = [
    ['key' => '',             'label' => 'Tutti',   'count' => $stats['totale'],       'color' => '#0d6efd'],
    ['key' => 'nuovo',        'label' => 'Nuovi',   'count' => $stats['nuovo'],        'color' => '#6c757d'],
    ['key' => 'occasionale',  'label' => 'Occas.',   'count' => $stats['occasionale'],  'color' => '#0dcaf0'],
    ['key' => 'abituale',     'label' => 'Abituali', 'count' => $stats['abituale'],     'color' => '#198754'],
    ['key' => 'vip',          'label' => 'VIP',      'count' => $stats['vip'],          'color' => '#ffc107'],
];
$currentSeg = $segment ?? '';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Clienti</h2>
</div>

<!-- Stats Cards: grid 5 colonne su desktop, row fissa su mobile -->
<div class="row g-2 mb-3">
    <?php foreach ($cards as $card):
        $isActive = $card['key'] === $currentSeg;
        $href = $card['key'] ? url('dashboard/customers?segment=' . $card['key']) : url('dashboard/customers');
    ?>
    <div class="col">
        <a href="<?= $href ?>" class="text-decoration-none d-block">
            <div class="card text-center h-100" style="border-top: 3px solid <?= $card['color'] ?>;<?= $isActive ? ' box-shadow: 0 0 0 1px ' . $card['color'] . ';' : '' ?>">
                <div class="card-body py-2 px-1">
                    <div class="fw-bold" style="color: <?= $card['color'] ?>; font-size: clamp(1.2rem, 4vw, 1.75rem);"><?= $card['count'] ?></div>
                    <div class="text-muted" style="font-size: clamp(0.6rem, 2vw, 0.8rem);"><?= $card['label'] ?></div>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<!-- Search + Filter -->
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" action="<?= url('dashboard/customers') ?>">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label mb-1">Cerca cliente</label>
                    <input type="text" class="form-control" name="q" value="<?= e($search ?? '') ?>" placeholder="Nome, email o telefono...">
                </div>
                <div class="col col-md-3">
                    <label class="form-label mb-1">Segmento</label>
                    <select class="form-select" name="segment">
                        <option value="">Tutti</option>
                        <option value="nuovo" <?= $currentSeg === 'nuovo' ? 'selected' : '' ?>>Nuovo</option>
                        <option value="occasionale" <?= $currentSeg === 'occasionale' ? 'selected' : '' ?>>Occasionale</option>
                        <option value="abituale" <?= $currentSeg === 'abituale' ? 'selected' : '' ?>>Abituale</option>
                        <option value="vip" <?= $currentSeg === 'vip' ? 'selected' : '' ?>>VIP</option>
                    </select>
                </div>
                <div class="col-auto col-md-2">
                    <div class="d-flex gap-1">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="bi bi-search"></i><span class="d-none d-md-inline ms-1">Filtra</span>
                        </button>
                        <a href="<?= url('dashboard/customers') ?>" class="btn btn-outline-secondary" title="Reset">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Desktop Table (md+) -->
<div class="card d-none d-md-block">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nome</th>
                    <th>Segmento</th>
                    <th>Email</th>
                    <th>Telefono</th>
                    <th>Prenotazioni</th>
                    <th>No-show</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">Nessun cliente trovato.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($customers as $c): ?>
                <?php [$seg, $segLabel, $segBadge] = customerSegment((int)$c['total_bookings'], $thOcc, $thAbi, $thVip); ?>
                <tr class="reservation-row" data-url="<?= url("dashboard/customers/{$c['id']}") ?>">
                    <td class="fw-semibold"><?= e($c['first_name'] . ' ' . $c['last_name']) ?></td>
                    <td><span class="badge <?= $segBadge ?>"><?= $segLabel ?></span></td>
                    <td><?= e($c['email']) ?></td>
                    <td><?= e($c['phone']) ?></td>
                    <td><?= (int)$c['total_bookings'] ?></td>
                    <td>
                        <?php if ($c['total_noshow'] > 0): ?>
                            <span class="badge bg-danger"><?= (int)$c['total_noshow'] ?></span>
                        <?php else: ?>
                            0
                        <?php endif; ?>
                    </td>
                    <td class="text-end"><i class="bi bi-chevron-right text-muted"></i></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Mobile Card List (<md) -->
<div class="d-md-none">
    <?php if (empty($customers)): ?>
        <div class="card">
            <div class="card-body text-center text-muted py-4">Nessun cliente trovato.</div>
        </div>
    <?php else: ?>
        <?php foreach ($customers as $c): ?>
            <?php [$seg, $segLabel, $segBadge] = customerSegment((int)$c['total_bookings'], $thOcc, $thAbi, $thVip); ?>
            <a href="<?= url("dashboard/customers/{$c['id']}") ?>" class="text-decoration-none d-block mb-2">
                <div class="card">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="min-width-0">
                                <div class="fw-semibold text-dark text-truncate"><?= e($c['first_name'] . ' ' . $c['last_name']) ?></div>
                                <small class="text-muted"><?= e($c['phone']) ?></small>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-shrink-0 ms-2">
                                <span class="badge <?= $segBadge ?>"><?= $segLabel ?></span>
                                <?php if ($c['total_noshow'] > 0): ?>
                                    <span class="badge bg-danger"><?= (int)$c['total_noshow'] ?></span>
                                <?php endif; ?>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
