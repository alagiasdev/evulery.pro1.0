<?php /** @var string $tab @var array $tenant */ ?>
<div class="res-page-header">
    <h1><i class="bi bi-megaphone" style="color:var(--brand);"></i> Marketing</h1>
</div>
<p style="color:#6c757d;font-size:.88rem;margin:.25rem 0 1rem;">
    Capisci da quale canale arrivano le prenotazioni e genera i link tracciati per le tue campagne.
</p>

<div class="mk-tabs">
    <a href="<?= url('dashboard/marketing') ?>" class="mk-tab <?= ($tab ?? '') === 'provenienza' ? 'active' : '' ?>">
        <i class="bi bi-bar-chart-line"></i> Provenienza
    </a>
    <a href="<?= url('dashboard/marketing/links') ?>" class="mk-tab <?= ($tab ?? '') === 'links' ? 'active' : '' ?>">
        <i class="bi bi-link-45deg"></i> Genera link
    </a>
    <a href="<?= url('dashboard/marketing/vetrina') ?>" class="mk-tab <?= ($tab ?? '') === 'vetrina' ? 'active' : '' ?>">
        <i class="bi bi-shop"></i> Vetrina
    </a>
</div>

<style>
.mk-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:1.1rem;border-bottom:1px solid #eceff2;padding-bottom:.1rem;}
.mk-tab{display:inline-flex;align-items:center;gap:.4rem;padding:.5rem .9rem;font-size:.85rem;font-weight:600;color:#6c757d;text-decoration:none;border-radius:8px 8px 0 0;border-bottom:2px solid transparent;}
.mk-tab:hover{color:var(--brand-d,#006b3c);}
.mk-tab.active{color:var(--brand,#00844A);border-bottom-color:var(--brand,#00844A);background:#f6fbf8;}
</style>
