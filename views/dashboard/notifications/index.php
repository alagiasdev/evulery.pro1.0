<?php if (empty($canPush)): ?>
    <?php
    $lockedTitle = 'Notifiche in tempo reale';
    $lockedDesc = 'Campanella dashboard e notifiche push nel browser. Contatta il supporto per effettuare un upgrade.';
    partial('service-locked', compact('lockedTitle', 'lockedDesc'));
    ?>
<?php else: ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="page-title mb-0">Notifiche</h1>
    <?php if ($total > 0): ?>
    <div class="d-flex gap-2">
        <form method="POST" action="<?= url('dashboard/notifications/read-all') ?>" style="display:inline;">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-success btn-sm">
                <i class="bi bi-check2-all me-1"></i> Segna tutte lette
            </button>
        </form>
        <form method="POST" action="<?= url('dashboard/notifications/delete-all') ?>" style="display:inline;"
              data-confirm="Eliminare tutte le notifiche? Questa azione non è reversibile.">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-trash me-1"></i> Elimina tutte
            </button>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php if (empty($notifications)): ?>
    <div class="card" style="padding:3rem 2rem;text-align:center;">
        <i class="bi bi-bell-slash" style="font-size:2.5rem;color:#adb5bd;"></i>
        <p style="margin-top:1rem;color:#6c757d;font-size:.9rem;">Nessuna notifica al momento.</p>
    </div>
<?php else: ?>
    <div class="card" style="border-radius:10px;overflow:hidden;">
        <?php foreach ($notifications as $n):
            $isUnread = empty($n['read_at']);
            $data = $n['data'] ?? [];
            $url = $data['url'] ?? '#';

            // Icon by type
            switch ($n['type']) {
                case 'new_reservation':
                    $iconCls = 'notif-page-item-icon notif-item-icon--new';
                    $icon = 'bi-calendar-plus';
                    break;
                case 'cancellation':
                    $iconCls = 'notif-page-item-icon notif-item-icon--cancel';
                    $icon = 'bi-calendar-x';
                    break;
                case 'deposit_received':
                    $iconCls = 'notif-page-item-icon notif-item-icon--deposit';
                    $icon = 'bi-cash-coin';
                    break;
                default:
                    $iconCls = 'notif-page-item-icon notif-item-icon--default';
                    $icon = 'bi-bell';
            }

            $createdAt = $n['created_at'] ?? '';
            $ago = '';
            if ($createdAt) {
                $diff = time() - strtotime($createdAt);
                if ($diff < 60) $ago = 'ora';
                elseif ($diff < 3600) $ago = floor($diff / 60) . ' min fa';
                elseif ($diff < 86400) $ago = floor($diff / 3600) . ' ore fa';
                elseif ($diff < 604800) $ago = floor($diff / 86400) . ' giorni fa';
                else $ago = date('d/m/Y', strtotime($createdAt));
            }
        ?>
        <div class="notif-page-item<?= $isUnread ? ' notif-page-item--unread' : '' ?>">
            <a href="<?= e($url) ?>" class="<?= $iconCls ?>" style="text-decoration:none;"><i class="bi <?= $icon ?>"></i></a>
            <a href="<?= e($url) ?>" class="notif-page-item-content" style="text-decoration:none;color:inherit;">
                <div class="notif-page-item-title"><?= e($n['title']) ?></div>
                <?php if (!empty($n['body'])): ?>
                <div class="notif-page-item-body"><?= e($n['body']) ?></div>
                <?php endif; ?>
                <div class="notif-page-item-time"><?= e($ago) ?></div>
            </a>
            <div class="notif-page-actions">
                <?php if ($isUnread): ?>
                <form method="POST" action="<?= url('dashboard/notifications/' . $n['id'] . '/read') ?>" class="notif-page-mark-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="notif-page-mark-btn" title="Segna come letta">
                        <i class="bi bi-check2"></i>
                    </button>
                </form>
                <?php endif; ?>
                <form method="POST" action="<?= url('dashboard/notifications/' . $n['id'] . '/delete') ?>" class="notif-page-mark-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="notif-page-delete-btn" title="Elimina">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav class="mt-3 d-flex justify-content-center">
        <ul class="pagination pagination-sm">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                <a class="page-link" href="<?= url('dashboard/notifications?page=' . $p) ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
<?php endif; ?>

<?php endif; ?>
