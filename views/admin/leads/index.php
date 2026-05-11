<?php
/** @var array $leads */
/** @var int $totalCount */
/** @var array $statusCounts */
/** @var array $statuses */
/** @var array $filters */
/** @var int $page */
/** @var int $limit */
/** @var array $resellers */

$statusColors = [
    'new'            => ['#0277bd', '#e3f2fd'],
    'contacted'      => ['#f57c00', '#fff3e0'],
    'demo_scheduled' => ['#7b1fa2', '#f3e5f5'],
    'demo_done'      => ['#1976d2', '#e3f2fd'],
    'negotiating'    => ['#c2185b', '#fce4ec'],
    'customer'       => ['#fff', '#00844A'],
    'lost'           => ['#757575', '#f5f5f5'],
];

function leadStatusBadge(string $status, array $statuses, array $colors): string {
    $label = $statuses[$status] ?? $status;
    [$txt, $bg] = $colors[$status] ?? ['#757575', '#f5f5f5'];
    $borderColor = $status === 'customer' ? '#00844A' : $txt;
    return '<span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:100px;font-size:.72rem;font-weight:700;color:' . $txt . ';background:' . $bg . ';border:1.5px solid ' . $borderColor . ';">'
         . '<span style="width:6px;height:6px;border-radius:50%;background:currentColor;"></span>'
         . e($label) . '</span>';
}

$totalPages = max(1, (int)ceil($totalCount / $limit));
?>

<style>
.leads-stats { display: grid; grid-template-columns: repeat(7, 1fr); gap: 10px; margin-bottom: 1.25rem; }
.leads-stat-card {
    background: #fff; border: 1px solid #e9ecef; border-radius: 10px;
    padding: 12px 14px; border-top: 3px solid;
}
.leads-stat-card .num { font-size: 1.5rem; font-weight: 800; line-height: 1; }
.leads-stat-card .lbl { font-size: .68rem; color: #6c757d; text-transform: uppercase; letter-spacing: .5px; margin-top: 5px; font-weight: 600; }

.leads-filters {
    background: #fff; border: 1px solid #e9ecef; border-radius: 10px;
    padding: 14px 18px; margin-bottom: 1rem;
    display: flex; gap: 12px; align-items: center; flex-wrap: wrap;
}
.leads-search { position: relative; flex: 1; min-width: 240px; }
.leads-search input {
    width: 100%; padding: .55rem .55rem .55rem 2.2rem;
    border: 1px solid #e9ecef; border-radius: 6px; font-size: .85rem;
}
.leads-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #6c757d; }
.leads-filters select {
    padding: .5rem .8rem; border: 1px solid #e9ecef; border-radius: 6px;
    font-size: .85rem; background: #fff; font-family: inherit;
}
.leads-filters .lbl { font-size: .78rem; font-weight: 600; color: #495057; }
.leads-table-card { background: #fff; border: 1px solid #e9ecef; border-radius: 10px; overflow: hidden; }
.leads-tbl { width: 100%; border-collapse: collapse; }
.leads-tbl thead th {
    background: #fafbfc; padding: 12px 14px; text-align: left;
    font-size: .68rem; font-weight: 700; color: #495057;
    text-transform: uppercase; letter-spacing: .5px;
    border-bottom: 1px solid #e9ecef;
}
.leads-tbl tbody td { padding: 14px; border-bottom: 1px solid #e9ecef; font-size: .85rem; vertical-align: middle; }
.leads-tbl tbody tr:hover { background: #fafbfc; }
.leads-tbl tbody tr:last-child td { border-bottom: 0; }
.leads-cell-name { font-weight: 700; color: #1a1d23; }
.leads-cell-restaurant { font-weight: 500; }
.leads-cell-restaurant .secondary { display: block; font-size: .75rem; color: #6c757d; font-weight: 400; margin-top: 2px; }
.leads-cell-contact { font-size: .8rem; color: #495057; }
.leads-cell-contact .email { display: block; font-weight: 500; color: #1a1d23; }
.leads-cell-contact .phone { font-size: .75rem; color: #6c757d; margin-top: 2px; }
.leads-cell-date { font-size: .8rem; color: #495057; }
.leads-cell-date .secondary { display: block; font-size: .7rem; color: #6c757d; margin-top: 2px; }
.leads-cell-actions { text-align: right; white-space: nowrap; }
.leads-btn-action {
    background: transparent; border: 1px solid #e9ecef; color: #495057;
    padding: 4px 10px; border-radius: 4px; cursor: pointer;
    font-size: .8rem; text-decoration: none;
    display: inline-flex; align-items: center; gap: 4px;
}
.leads-btn-action:hover { border-color: #00844A; color: #00844A; }
.leads-pill-unassigned {
    display: inline-flex; align-items: center; gap: 6px;
    background: #fff3e0; color: #f57c00;
    padding: 3px 8px; border-radius: 100px; font-size: .75rem; font-weight: 600;
}
.leads-pill-assigned {
    display: inline-flex; align-items: center; gap: 6px;
    background: #f5f5f5; padding: 3px 8px; border-radius: 100px;
    font-size: .75rem;
}
.leads-pill-assigned .avatar {
    width: 18px; height: 18px; border-radius: 50%;
    background: #00844A; color: #fff;
    font-size: .65rem; font-weight: 700;
    display: inline-flex; align-items: center; justify-content: center;
}
.leads-tbl-footer {
    display: flex; justify-content: space-between; align-items: center;
    padding: 12px 18px; background: #fafbfc; border-top: 1px solid #e9ecef;
    font-size: .78rem; color: #495057;
}
.leads-pagination { display: flex; gap: 4px; }
.leads-pagination a, .leads-pagination span {
    background: #fff; border: 1px solid #e9ecef; padding: 4px 10px;
    border-radius: 4px; font-size: .78rem; text-decoration: none; color: #495057;
}
.leads-pagination .active { background: #00844A; color: #fff; border-color: #00844A; }

.leads-empty {
    padding: 60px 20px; text-align: center; color: #6c757d;
}
.leads-empty i { font-size: 2.5rem; color: #adb5bd; }
.leads-empty h3 { font-size: 1.1rem; font-weight: 600; margin-top: 12px; color: #495057; }
.leads-empty p { font-size: .88rem; margin-top: 6px; }
</style>

<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.25rem;">
    <div>
        <h1 style="font-size:1.5rem;font-weight:800;letter-spacing:-.5px;margin:0;">Lead</h1>
        <div style="font-size:.85rem;color:#6c757d;margin-top:4px;">Gestione richieste demo dal sito + assegnazione manuale ai reseller</div>
    </div>
</div>

<!-- Stats counter -->
<div class="leads-stats">
    <?php
    $statColors = [
        'new'            => '#0277bd',
        'contacted'      => '#f57c00',
        'demo_scheduled' => '#7b1fa2',
        'demo_done'      => '#1976d2',
        'negotiating'    => '#c2185b',
        'customer'       => '#00844A',
        'lost'           => '#757575',
    ];
    foreach ($statuses as $key => $label):
        $count = $statusCounts[$key] ?? 0;
        $color = $statColors[$key];
    ?>
        <a href="<?= url('admin/leads') ?>?status=<?= e($key) ?>" class="leads-stat-card" style="text-decoration:none;color:inherit;border-top-color:<?= $color ?>;">
            <div class="num" style="color:<?= $color ?>;"><?= $count ?></div>
            <div class="lbl"><?= e($label) ?></div>
        </a>
    <?php endforeach; ?>
</div>

<!-- Filters -->
<form method="GET" action="<?= url('admin/leads') ?>" class="leads-filters">
    <div class="leads-search">
        <i class="bi bi-search"></i>
        <input type="text" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Cerca per nome, ristorante o email...">
    </div>
    <div style="display:flex;align-items:center;gap:8px;">
        <span class="lbl">Stato:</span>
        <select name="status" onchange="this.form.submit()">
            <option value="">Tutti</option>
            <?php foreach ($statuses as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= ($filters['status'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div style="display:flex;align-items:center;gap:8px;">
        <span class="lbl">Periodo:</span>
        <select name="period" onchange="this.form.submit()">
            <option value="7d">Ultimi 7 giorni</option>
            <option value="30d" selected>Ultimi 30 giorni</option>
            <option value="month">Questo mese</option>
            <option value="all">Sempre</option>
        </select>
    </div>
    <button type="submit" style="background:#00844A;color:#fff;padding:.45rem .9rem;border:0;border-radius:6px;font-weight:600;font-size:.82rem;cursor:pointer;">
        <i class="bi bi-funnel"></i> Filtra
    </button>
</form>

<!-- Tabella -->
<div class="leads-table-card">
    <?php if (empty($leads)): ?>
        <div class="leads-empty">
            <i class="bi bi-inbox"></i>
            <h3>Nessun lead trovato</h3>
            <p>Quando arriveranno richieste demo dal sito, le vedrai qui.</p>
        </div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="leads-tbl">
                <thead>
                    <tr>
                        <th>Lead</th>
                        <th>Ristorante</th>
                        <th>Contatti</th>
                        <th>Stato</th>
                        <th>Follow-up</th>
                        <th>Assegnato a</th>
                        <th>Ricevuto</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leads as $l): ?>
                        <tr>
                            <td class="leads-cell-name"><?= e($l['name']) ?></td>
                            <td class="leads-cell-restaurant"><?= e($l['restaurant']) ?></td>
                            <td class="leads-cell-contact">
                                <span class="email"><?= e($l['email']) ?></span>
                                <span class="phone"><?= e($l['phone']) ?></span>
                            </td>
                            <td><?= leadStatusBadge($l['status'], $statuses, $statusColors) ?></td>
                            <td>
                                <?php
                                if (empty($l['next_followup_at'])) {
                                    echo '<span style="font-size:.78rem;color:#adb5bd;">&mdash;</span>';
                                } else {
                                    $today = strtotime(date('Y-m-d'));
                                    $fu    = strtotime(date('Y-m-d', strtotime($l['next_followup_at'])));
                                    $diff  = (int) round(($fu - $today) / 86400);
                                    if ($diff < 0) {
                                        echo '<span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:100px;font-size:.72rem;font-weight:700;color:#C62828;background:#FFEBEE;border:1.5px solid #C62828;"><i class="bi bi-exclamation-circle-fill"></i> Scaduto ' . abs($diff) . 'gg fa</span>';
                                    } elseif ($diff === 0) {
                                        echo '<span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:100px;font-size:.72rem;font-weight:700;color:#E65100;background:#FFF3E0;border:1.5px solid #E65100;"><i class="bi bi-bell-fill"></i> Oggi</span>';
                                    } elseif ($diff === 1) {
                                        echo '<span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:100px;font-size:.72rem;font-weight:600;color:#1565C0;background:#E3F2FD;border:1px solid #1565C0;">Domani</span>';
                                    } else {
                                        echo '<span style="font-size:.78rem;color:#495057;">tra ' . $diff . ' giorni</span>';
                                        echo '<span style="display:block;font-size:.7rem;color:#adb5bd;margin-top:2px;">' . date('d/m/Y', $fu) . '</span>';
                                    }
                                }
                                ?>
                            </td>
                            <td>
                                <?php if (!$l['assigned_reseller_id']): ?>
                                    <span class="leads-pill-unassigned"><i class="bi bi-exclamation-circle"></i> Non assegnato</span>
                                <?php else: ?>
                                    <span class="leads-pill-assigned"><span class="avatar">R</span> Reseller #<?= (int)$l['assigned_reseller_id'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="leads-cell-date">
                                <?= date('d M H:i', strtotime($l['created_at'])) ?>
                                <span class="secondary"><?= date('Y', strtotime($l['created_at'])) ?></span>
                            </td>
                            <td class="leads-cell-actions">
                                <a href="<?= url("admin/leads/{$l['id']}") ?>" class="leads-btn-action">
                                    <i class="bi bi-eye"></i> Apri
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="leads-tbl-footer">
            <div>
                Mostrando <?= (($page - 1) * $limit) + 1 ?>-<?= min($page * $limit, $totalCount) ?> di <?= $totalCount ?> lead
            </div>
            <?php if ($totalPages > 1): ?>
                <div class="leads-pagination">
                    <?php
                    $base = url('admin/leads') . '?' . http_build_query(array_diff_key($filters, ['date_from' => true]));
                    $pageLink = function($p) use ($base) {
                        return $base . (str_contains($base, '?') ? '&' : '?') . 'page=' . $p;
                    };
                    ?>
                    <?php if ($page > 1): ?><a href="<?= $pageLink($page - 1) ?>">‹</a><?php endif; ?>
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="active"><?= $i ?></span>
                        <?php else: ?>
                            <a href="<?= $pageLink($i) ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?><a href="<?= $pageLink($page + 1) ?>">›</a><?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
