<?php $pageScripts = ['js/dashboard-orders.js']; ?>
<script nonce="<?= csp_nonce() ?>">window.DO_BASE = <?= json_encode(url('')) ?>;</script>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 style="font-size:1.35rem; font-weight:700; margin-bottom:0;">Ordini di oggi</h2>
        <small class="text-muted"><?= date('d/m/Y') ?></small>
    </div>
    <a href="<?= url('dashboard/orders/history') ?>" class="btn btn-outline-success btn-sm">
        <i class="bi bi-clock-history me-1"></i> Storico ordini
    </a>
</div>

<!-- Stats cards -->
<div class="row g-2 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center p-2">
            <div class="do-stat-value" id="doStatTotal"><?= $stats['total_orders'] ?></div>
            <div class="do-stat-label">Ordini totali</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center p-2">
            <div class="do-stat-value" id="doStatRevenue">€ <?= number_format($stats['revenue'], 2, ',', '.') ?></div>
            <div class="do-stat-label">Incasso</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center p-2">
            <div class="do-stat-value" id="doStatTakeaway"><?= $stats['takeaway_count'] ?></div>
            <div class="do-stat-label">Asporto</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center p-2">
            <div class="do-stat-value" id="doStatDelivery"><?= $stats['delivery_count'] ?></div>
            <div class="do-stat-label">Consegna</div>
        </div>
    </div>
</div>

<!-- Kanban board -->
<div class="do-kanban" id="doKanban">
    <?php
    $columns = [
        'pending'   => ['label' => 'Nuovi',           'icon' => 'bi-bell-fill',      'color' => 'warning'],
        'accepted'  => ['label' => 'Accettati',        'icon' => 'bi-check-circle',   'color' => 'info'],
        'preparing' => ['label' => 'In preparazione',  'icon' => 'bi-fire',           'color' => 'primary'],
        'ready'     => ['label' => 'Pronti',           'icon' => 'bi-bag-check-fill', 'color' => 'success'],
    ];
    foreach ($columns as $status => $col):
        $orders = $kanban[$status] ?? [];
    ?>
    <div class="do-kanban-col">
        <div class="do-kanban-header do-kanban-header--<?= $col['color'] ?>">
            <i class="bi <?= $col['icon'] ?> me-1"></i>
            <?= $col['label'] ?>
            <span class="badge bg-white text-dark ms-auto do-count" data-status="<?= $status ?>"><?= count($orders) ?></span>
        </div>
        <div class="do-kanban-cards" data-status="<?= $status ?>">
            <?php foreach ($orders as $o): ?>
            <?php include BASE_PATH . '/views/dashboard/orders/_card.php'; ?>
            <?php endforeach; ?>
            <?php if (empty($orders)): ?>
            <div class="do-kanban-empty">Nessun ordine</div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!--
    Completed today — container SEMPRE renderizzato (anche se vuoto) cosi'
    il JS updateCompletedTable() puo' popolarlo via polling senza richiedere
    F5. Il container e' nascosto se ci sono 0 completati.
-->
<div class="mt-4" id="doCompletedSection" style="<?= empty($completed) ? 'display:none;' : '' ?>">
    <h6 class="text-muted">
        <i class="bi bi-check-all me-1"></i>
        Completati/Chiusi oggi (<span id="doCompletedCount"><?= count($completed) ?></span>)
    </h6>
    <div class="table-responsive">
        <table class="table table-sm table-striped align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>Tipo</th>
                    <th>Totale</th>
                    <th>Stato</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="doCompletedTbody">
                <?php foreach ($completed as $co): ?>
                <tr>
                    <td><strong><?= e($co['order_number']) ?></strong></td>
                    <td><?= e($co['customer_name']) ?></td>
                    <td><?= order_type_label($co['order_type']) ?></td>
                    <td>€ <?= number_format((float)$co['total'], 2, ',', '.') ?></td>
                    <td><?= order_status_badge($co['status']) ?></td>
                    <td><a href="<?= url("dashboard/orders/{$co['id']}") ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($ridersEnabled)): ?>
<!--
    Popup assegnazione rider (singolo, riusato per ogni ordine).
    Posizionato dal JS sotto il trigger cliccato. Lista rider attivi +
    opzione "Rimuovi assegnazione" se l'ordine ha gia' un rider.
-->
<div class="rd-assign-popup" id="rd-assign-popup" style="display:none;" data-csrf="<?= csrf_token() ?>" data-base-url="<?= url('dashboard/orders') ?>">
    <div class="rd-assign-popup-head">Assegna a:</div>
    <div class="rd-assign-popup-list">
        <?php if (empty($activeRiders)): ?>
            <div class="rd-assign-popup-empty">
                Nessun rider attivo. <a href="<?= url('dashboard/riders') ?>">Aggiungine uno</a>.
            </div>
        <?php else: ?>
            <?php foreach ($activeRiders as $rider): ?>
            <button type="button" class="rd-assign-popup-item" data-rider-id="<?= (int)$rider['id'] ?>">
                <span class="rd-dot" style="background:<?= e($rider['color_hex']) ?>;"></span>
                <?= e($rider['name']) ?>
            </button>
            <?php endforeach; ?>
            <button type="button" class="rd-assign-popup-item rd-assign-popup-clear" data-rider-id="0" style="display:none;">
                <i class="bi bi-x-circle me-1"></i> Rimuovi assegnazione
            </button>
        <?php endif; ?>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
(function () {
    var popup = document.getElementById('rd-assign-popup');
    if (!popup) return;
    var csrf = popup.getAttribute('data-csrf');
    var baseUrl = popup.getAttribute('data-base-url');
    var currentTrigger = null;

    function openFor(trigger) {
        var rect = trigger.getBoundingClientRect();
        popup.style.position = 'fixed';
        popup.style.top  = (rect.bottom + 6) + 'px';
        popup.style.left = rect.left + 'px';
        popup.style.display = 'block';
        currentTrigger = trigger;
        var clearBtn = popup.querySelector('.rd-assign-popup-clear');
        if (clearBtn) {
            clearBtn.style.display = (trigger.getAttribute('data-current-rider') !== '0') ? 'flex' : 'none';
        }
        var currentId = trigger.getAttribute('data-current-rider');
        popup.querySelectorAll('.rd-assign-popup-item[data-rider-id]').forEach(function (item) {
            item.classList.toggle('is-current', item.getAttribute('data-rider-id') === currentId);
        });
    }
    function close() { popup.style.display = 'none'; currentTrigger = null; }

    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('.do-assign-trigger');
        if (trigger) {
            e.preventDefault(); e.stopPropagation();
            if (currentTrigger === trigger) { close(); return; }
            openFor(trigger);
            return;
        }
        if (!e.target.closest('#rd-assign-popup')) close();
    });

    popup.addEventListener('click', function (e) {
        var item = e.target.closest('.rd-assign-popup-item');
        if (!item || !currentTrigger) return;
        var orderId = currentTrigger.getAttribute('data-order-id');
        var riderId = item.getAttribute('data-rider-id');
        var body = new URLSearchParams();
        body.append('_csrf', csrf);
        if (riderId !== '0') body.append('rider_id', riderId);
        fetch(baseUrl + '/' + orderId + '/assign-rider', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: body.toString()
        }).then(function (r) { return r.json(); })
          .then(function () { location.reload(); })
          .catch(function () { alert('Errore assegnazione rider'); });
        close();
    });
})();
</script>
<?php endif; ?>
