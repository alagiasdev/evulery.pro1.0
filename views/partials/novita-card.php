<?php
/**
 * Card "Novità" in dashboard home. Collassabile (details nativo, no JS):
 * chiusa → "Mostra le ultime novità"; aperta → elenco + "Non mostrare più".
 * Auto-scadenza a 30 giorni (releases_card_visible). Si spegne con "non mostrare
 * più" (cookie) o aprendo la pagina completa in Impostazioni → Novità.
 */
if (!releases_card_visible('owner')) {
    return;
}
$nvRecent = releases_recent('owner');
$nvCats   = releases_config()['categories'] ?? [];
?>
<details class="nv-card">
    <summary class="nv-summary">
        <span class="nv-sum-l"><span class="nv-spark">✨</span> <b>Novità</b> · <?= count($nvRecent) ?> <?= count($nvRecent) === 1 ? 'nuova funzione' : 'nuove funzioni' ?></span>
        <span class="nv-sum-cta">
            <span class="nv-when-closed">Mostra le ultime novità</span>
            <span class="nv-when-open">Nascondi</span>
            <i class="bi bi-chevron-down nv-chev"></i>
        </span>
    </summary>
    <div class="nv-body">
        <ul class="nv-list">
            <?php foreach (array_slice($nvRecent, 0, 4) as $r): ?>
                <?php $c = $nvCats[$r['category']] ?? []; ?>
            <li><span class="nv-emoji"><?= $c['emoji'] ?? '✨' ?></span> <span><?= e($r['title']) ?></span></li>
            <?php endforeach; ?>
        </ul>
        <div class="nv-actions">
            <a href="<?= url('dashboard/novita') ?>" class="nv-btn-primary">Vedi tutte le novità</a>
            <form method="POST" action="<?= url('dashboard/novita/dismiss') ?>" class="nv-dismiss-form">
                <?= csrf_field() ?>
                <button type="submit" class="nv-btn-ghost">Non mostrare più</button>
            </form>
        </div>
    </div>
</details>
