<?php
/**
 * Dashboard Reputazione — Feedback
 * Variables: $tenant, $items, $total, $page, $totalPages, $filters
 */
$currentStatus = $filters['feedback_status'] ?? '';
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h2 style="font-size:1.35rem; font-weight:700; margin-bottom:.15rem;"><i class="bi bi-star" style="color:#FFC107;"></i> Reputazione</h2>
        <p style="font-size:.82rem; color:#6c757d; margin-bottom:0;">Monitora le recensioni e il feedback dei tuoi clienti</p>
    </div>
</div>

<!-- Tabs -->
<div class="rv-tabs mb-4">
    <a href="<?= url('dashboard/reputation') ?>" class="rv-tab"><i class="bi bi-grid"></i> Panoramica</a>
    <a href="<?= url('dashboard/reputation/feedback') ?>" class="rv-tab active">
        <i class="bi bi-chat-dots"></i> Feedback
    </a>
    <a href="<?= url('dashboard/reputation/history') ?>" class="rv-tab"><i class="bi bi-clock-history"></i> Storico invii</a>
</div>

<!-- Filters -->
<div class="d-flex align-items-center gap-2 flex-wrap mb-3">
    <a href="<?= url('dashboard/reputation/feedback') ?>" class="rv-filter-chip <?= $currentStatus === '' ? 'active' : '' ?>">Tutti (<?= $total ?>)</a>
    <a href="<?= url('dashboard/reputation/feedback?status=new') ?>" class="rv-filter-chip <?= $currentStatus === 'new' ? 'active' : '' ?>">
        <i class="bi bi-circle-fill" style="font-size:.5rem; color:#1565C0;"></i> Nuovi
    </a>
    <a href="<?= url('dashboard/reputation/feedback?status=read') ?>" class="rv-filter-chip <?= $currentStatus === 'read' ? 'active' : '' ?>">
        <i class="bi bi-circle-fill" style="font-size:.5rem; color:#757575;"></i> Letti
    </a>
    <a href="<?= url('dashboard/reputation/feedback?status=replied') ?>" class="rv-filter-chip <?= $currentStatus === 'replied' ? 'active' : '' ?>">
        <i class="bi bi-circle-fill" style="font-size:.5rem; color:#2e7d32;"></i> Risposti
    </a>
</div>

<?php if (empty($items)): ?>
<div class="text-center" style="padding:3rem 1rem;">
    <i class="bi bi-chat-dots" style="font-size:2.5rem; color:#e0e0e0;"></i>
    <h5 style="font-size:.88rem; font-weight:700; color:#6c757d; margin-top:.75rem;">Nessun feedback</h5>
    <p style="font-size:.78rem; color:#adb5bd;">I feedback dei clienti appariranno qui.</p>
</div>
<?php else: ?>

<div class="card section-card">
    <?php foreach ($items as $idx => $fb): ?>
    <div class="rv-feedback-item" style="<?= $idx > 0 ? 'border-top:1px solid #f0f0f0;' : '' ?>">
        <div class="d-flex gap-3 p-3">
            <!-- Avatar -->
            <?php
            $isAnon = empty($fb['first_name']);
            $initials = $isAnon ? '?' : mb_strtoupper(mb_substr($fb['first_name'], 0, 1) . mb_substr($fb['last_name'] ?? '', 0, 1));
            $avatarColors = ['#E3F2FD', '#FCE4EC', '#E8F5E9', '#FFF8E1', '#F3E5F5'];
            $bgColor = $avatarColors[((int)($fb['id'] ?? 0)) % count($avatarColors)];
            ?>
            <div style="width:36px; height:36px; border-radius:50%; background:<?= $bgColor ?>; display:flex; align-items:center; justify-content:center; font-size:.75rem; font-weight:700; flex-shrink:0; color:#495057;">
                <?= e($initials) ?>
            </div>

            <div style="flex:1; min-width:0;">
                <!-- Header -->
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <span style="font-size:.8rem; font-weight:700;">
                        <?php if (!empty($fb['first_name'])): ?>
                            <?= e($fb['first_name'] . ' ' . ($fb['last_name'] ?? '')) ?>
                        <?php else: ?>
                            <span style="color:#adb5bd;">Anonimo</span>
                        <?php endif; ?>
                    </span>
                    <span style="font-size:.72rem; color:#FFC107;"><?= str_repeat('★', (int)($fb['rating'] ?? 0)) ?><?= str_repeat('☆', 5 - (int)($fb['rating'] ?? 0)) ?></span>
                    <?= review_status_badge($fb['feedback_status'] ?? 'new') ?>
                    <span style="font-size:.68rem; color:#adb5bd; margin-left:auto;"><?= format_date($fb['created_at'] ?? '', 'd/m/Y H:i') ?></span>
                </div>

                <!-- Text -->
                <div style="font-size:.78rem; color:#495057; line-height:1.45; margin-bottom:.35rem;">
                    <?= nl2br(e($fb['feedback_text'] ?? '')) ?>
                </div>

                <!-- Meta -->
                <div class="d-flex align-items-center gap-3 flex-wrap" style="font-size:.65rem; color:#adb5bd;">
                    <?php
                    $src = $fb['source'] ?? 'email';
                    if ($src === 'email'): ?>
                    <span class="badge" style="background:#E3F2FD; color:#1565C0; font-size:.6rem; font-weight:600;"><i class="bi bi-envelope me-1"></i>Email</span>
                    <?php elseif ($src === 'qr' || $src === 'nfc'): ?>
                    <span class="badge" style="background:#FFF8E1; color:#F57F17; font-size:.6rem; font-weight:600;"><i class="bi bi-qr-code me-1"></i>QR/NFC</span>
                    <?php elseif ($src === 'embed'): ?>
                    <span class="badge" style="background:#F3E5F5; color:#7B1FA2; font-size:.6rem; font-weight:600;"><i class="bi bi-code-slash me-1"></i>Embed</span>
                    <?php endif; ?>
                    <?php if (!empty($fb['reservation_date'])): ?>
                    <span><i class="bi bi-calendar3 me-1"></i> <?= format_date($fb['reservation_date'], 'd/m/Y') ?> <?= substr($fb['reservation_time'] ?? '', 0, 5) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($fb['party_size'])): ?>
                    <span><i class="bi bi-people me-1"></i> <?= (int)$fb['party_size'] ?> persone</span>
                    <?php endif; ?>
                    <?php if (!empty($fb['email'])): ?>
                    <span><i class="bi bi-envelope me-1"></i> <?= e($fb['email']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Reply (if replied) -->
                <?php if (!empty($fb['feedback_reply'])): ?>
                <div style="background:#f8f9fa; border-radius:8px; padding:.55rem .75rem; margin-top:.5rem; border-left:3px solid var(--brand, #00844A);">
                    <div style="font-size:.65rem; font-weight:600; color:var(--brand, #00844A); margin-bottom:.15rem;">La tua risposta:</div>
                    <div style="font-size:.75rem; color:#495057;"><?= nl2br(e($fb['feedback_reply'])) ?></div>
                </div>
                <?php endif; ?>

                <!-- Reply form (if not replied and has customer email) -->
                <?php if (($fb['feedback_status'] ?? '') !== 'replied' && !empty($fb['email'])): ?>
                <div class="rv-reply-form mt-2" id="reply-form-<?= (int)$fb['id'] ?>" style="display:none;">
                    <form method="POST" action="<?= url('dashboard/reputation/feedback/' . (int)$fb['id'] . '/reply') ?>">
                        <?= csrf_field() ?>
                        <div class="input-group input-group-sm">
                            <input type="text" name="reply" class="form-control form-control-sm" placeholder="Scrivi la tua risposta..." required style="font-size:.78rem;">
                            <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-send"></i></button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <!-- Actions -->
            <div class="d-flex gap-1 flex-shrink-0 align-self-start">
                <?php if (($fb['feedback_status'] ?? '') !== 'replied' && !empty($fb['email'])): ?>
                <button class="rv-action-btn" title="Rispondi"
                        data-toggle-reply="reply-form-<?= (int)$fb['id'] ?>">
                    <i class="bi bi-reply"></i>
                </button>
                <?php endif; ?>
                <?php if (($fb['feedback_status'] ?? '') === 'new'): ?>
                <form method="POST" action="<?= url('dashboard/reputation/feedback/' . (int)$fb['id'] . '/status') ?>" style="display:inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="status" value="read">
                    <button type="submit" class="rv-action-btn" title="Segna come letto"><i class="bi bi-check2"></i></button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="<?= url('dashboard/reputation/feedback?' . http_build_query(array_merge($filters, ['page' => $p]))) ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php endif; ?>

<?php $pageScripts = ['js/dashboard-reputation.js']; ?>
