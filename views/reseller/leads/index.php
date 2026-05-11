<div class="rs-page-header">
    <div>
        <h1 class="rs-page-title">I miei lead</h1>
        <p class="rs-page-sub">Lead a te assegnati. <?= (int)$totalCount ?> totali.</p>
    </div>
</div>

<!-- Counter status -->
<div class="rs-kpi-grid" style="grid-template-columns:repeat(5,1fr);">
    <a href="<?= url('reseller/leads') ?>?status=new" class="rs-kpi-card" style="text-decoration:none;color:inherit;padding:.7rem;">
        <div>
            <div class="val" style="color:#0277bd;font-size:1.1rem;"><?= (int)($statusCounts['new'] ?? 0) ?></div>
            <div class="lbl">Nuovi</div>
        </div>
    </a>
    <a href="<?= url('reseller/leads') ?>?status=contacted" class="rs-kpi-card" style="text-decoration:none;color:inherit;padding:.7rem;">
        <div>
            <div class="val" style="color:#f57c00;font-size:1.1rem;"><?= (int)($statusCounts['contacted'] ?? 0) ?></div>
            <div class="lbl">Contattati</div>
        </div>
    </a>
    <a href="<?= url('reseller/leads') ?>?status=demo_scheduled" class="rs-kpi-card" style="text-decoration:none;color:inherit;padding:.7rem;">
        <div>
            <div class="val" style="color:#7b1fa2;font-size:1.1rem;"><?= (int)($statusCounts['demo_scheduled'] ?? 0) ?></div>
            <div class="lbl">Demo prog.</div>
        </div>
    </a>
    <a href="<?= url('reseller/leads') ?>?status=negotiating" class="rs-kpi-card" style="text-decoration:none;color:inherit;padding:.7rem;">
        <div>
            <div class="val" style="color:#c2185b;font-size:1.1rem;"><?= (int)($statusCounts['negotiating'] ?? 0) ?></div>
            <div class="lbl">Trattativa</div>
        </div>
    </a>
    <a href="<?= url('reseller/leads') ?>?status=customer" class="rs-kpi-card" style="text-decoration:none;color:inherit;padding:.7rem;">
        <div>
            <div class="val" style="color:#00844A;font-size:1.1rem;"><?= (int)($statusCounts['customer'] ?? 0) ?></div>
            <div class="lbl">Clienti</div>
        </div>
    </a>
</div>

<!-- Filtri -->
<div class="rs-card">
    <div style="padding:.85rem 1.25rem;border-bottom:1px solid var(--rs-line);">
        <form method="GET" action="<?= url('reseller/leads') ?>" style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">
            <select name="status" style="padding:.45rem .7rem;border:1px solid var(--rs-line);border-radius:8px;font-size:.85rem;">
                <option value="">Tutti gli stati</option>
                <?php foreach ($statuses as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $filterStatus === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="search" value="<?= e($filterSearch ?? '') ?>" placeholder="Cerca per nome, ristorante o email" style="flex:1;min-width:200px;padding:.45rem .7rem;border:1px solid var(--rs-line);border-radius:8px;font-size:.85rem;">
            <button type="submit" class="rs-btn rs-btn-primary rs-btn-sm">
                <i class="bi bi-search"></i> Filtra
            </button>
            <?php if ($filterStatus || $filterSearch): ?>
                <a href="<?= url('reseller/leads') ?>" class="rs-btn rs-btn-ghost rs-btn-sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabella -->
    <?php if (empty($leads)): ?>
        <div class="rs-card-empty">
            Nessun lead trovato.<br>
            <?= $filterStatus || $filterSearch ? 'Prova a rimuovere i filtri.' : 'Quando l\'admin ti assegnerà lead, appariranno qui.' ?>
        </div>
    <?php else: ?>
        <table class="rs-tbl">
            <thead>
                <tr>
                    <th>Lead</th>
                    <th>Ristorante</th>
                    <th>Contatti</th>
                    <th>Stato</th>
                    <th>Follow-up</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leads as $l): ?>
                    <?php
                        $fu = '';
                        if (!empty($l['next_followup_at'])) {
                            $fuDate = strtotime($l['next_followup_at']);
                            $diffDays = (int)floor(($fuDate - strtotime(date('Y-m-d'))) / 86400);
                            if ($diffDays < 0)       { $fu = '<span class="rs-fu-overdue"><i class="bi bi-exclamation-circle-fill"></i> Scaduto ' . abs($diffDays) . 'gg</span>'; }
                            elseif ($diffDays === 0) { $fu = '<span class="rs-fu-today"><i class="bi bi-bell-fill"></i> Oggi</span>'; }
                            else                      { $fu = '<span class="rs-fu-future"><i class="bi bi-calendar3"></i> ' . date('d/m', $fuDate) . '</span>'; }
                        }
                    ?>
                    <tr>
                        <td><span class="name"><?= e($l['name']) ?></span></td>
                        <td><?= e($l['restaurant']) ?></td>
                        <td>
                            <div><?= e($l['email']) ?></div>
                            <?php if (!empty($l['phone'])): ?>
                                <div class="sub"><?= e($l['phone']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><span class="rs-badge rs-b-<?= e($l['status']) ?>"><?= e($statuses[$l['status']] ?? $l['status']) ?></span></td>
                        <td><?= $fu ?: '<span class="sub">—</span>' ?></td>
                        <td style="text-align:right;">
                            <a href="<?= url('reseller/leads/' . (int)$l['id']) ?>" class="rs-btn rs-btn-ghost rs-btn-sm">
                                <i class="bi bi-eye"></i> Apri
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
$totalPages = (int)ceil($totalCount / $limit);
if ($totalPages > 1):
?>
<div style="display:flex;justify-content:center;gap:.5rem;margin-top:1rem;">
    <?php
        $params = [];
        if ($filterStatus) $params['status'] = $filterStatus;
        if ($filterSearch) $params['search'] = $filterSearch;
        $qs = function ($p) use ($params) {
            $params['page'] = $p;
            return '?' . http_build_query($params);
        };
    ?>
    <?php if ($page > 1): ?>
        <a href="<?= url('reseller/leads') . $qs($page - 1) ?>" class="rs-btn rs-btn-ghost rs-btn-sm">‹ Precedente</a>
    <?php endif; ?>
    <span style="padding:.35rem .7rem;font-size:.8rem;color:var(--rs-muted);">Pagina <?= $page ?> di <?= $totalPages ?></span>
    <?php if ($page < $totalPages): ?>
        <a href="<?= url('reseller/leads') . $qs($page + 1) ?>" class="rs-btn rs-btn-ghost rs-btn-sm">Successiva ›</a>
    <?php endif; ?>
</div>
<?php endif; ?>
