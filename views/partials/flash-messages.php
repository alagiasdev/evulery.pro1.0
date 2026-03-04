<?php
$alertType = \App\Core\Session::getFlash('alert_type');
$alertMessage = \App\Core\Session::getFlash('alert_message');
if ($alertType && $alertMessage):
?>
<div class="alert alert-<?= e($alertType) ?> alert-dismissible fade show" role="alert">
    <?= e($alertMessage) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
