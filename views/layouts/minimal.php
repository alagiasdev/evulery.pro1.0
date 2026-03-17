<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#00844A">
    <title><?= e($title ?? 'Evulery') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#f5f6f8;color:#1a1d23;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px 16px;}
        .manage-wrap{max-width:480px;width:100%;}
        .manage-card{background:#fff;border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,.08);overflow:hidden;}
        .manage-header{background:#00844A;padding:28px 24px;text-align:center;color:#fff;}
        .manage-header h1{font-size:1.25rem;font-weight:700;margin:0 0 4px;}
        .manage-header p{font-size:.82rem;opacity:.8;margin:0;}
        .manage-body{padding:24px;}
        .manage-detail{display:flex;align-items:center;gap:12px;padding:14px 0;border-bottom:1px solid #f0f0f0;}
        .manage-detail:last-child{border-bottom:none;}
        .manage-detail-icon{width:40px;height:40px;border-radius:10px;background:#f8f9fa;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:#00844A;flex-shrink:0;}
        .manage-detail-label{font-size:.7rem;color:#6c757d;text-transform:uppercase;letter-spacing:.3px;font-weight:500;}
        .manage-detail-value{font-size:.95rem;font-weight:600;}
        .manage-status{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:20px;font-size:.78rem;font-weight:600;}
        .manage-status.confirmed{background:#E8F5E9;color:#2E7D32;}
        .manage-status.pending{background:#FFF8E1;color:#E65100;}
        .manage-status.cancelled{background:#FFEBEE;color:#C62828;}
        .manage-status.arrived{background:#E3F2FD;color:#1565C0;}
        .manage-status.noshow{background:#F3E5F5;color:#6A1B9A;}
        .manage-actions{padding:0 24px 24px;display:flex;flex-direction:column;gap:8px;}
        .manage-btn{display:block;width:100%;padding:12px;border-radius:10px;font-size:.88rem;font-weight:600;text-align:center;cursor:pointer;border:none;text-decoration:none;transition:all .15s;}
        .manage-btn-cancel{background:#FFEBEE;color:#C62828;}
        .manage-btn-cancel:hover{background:#C62828;color:#fff;}
        .manage-btn-back{background:#f0f0f0;color:#495057;}
        .manage-btn-back:hover{background:#e0e0e0;color:#1a1d23;}
        .manage-note{background:#FFF3E0;border-radius:10px;padding:12px 16px;font-size:.82rem;color:#E65100;margin-bottom:16px;}
        .manage-policy{background:#f8f9fa;border-radius:10px;padding:12px 16px;font-size:.78rem;color:#6c757d;margin-top:16px;}
        .manage-footer{text-align:center;padding:16px 24px;background:#f8f9fa;border-top:1px solid #f0f0f0;font-size:.72rem;color:#adb5bd;}
        .flash-msg{padding:10px 16px;border-radius:8px;font-size:.85rem;margin-bottom:16px;font-weight:500;}
        .flash-success{background:#E8F5E9;color:#2E7D32;}
        .flash-danger{background:#FFEBEE;color:#C62828;}
    </style>
</head>
<body>
    <div class="manage-wrap">
        <?php
        $alertType = \App\Core\Session::getFlash('alert_type');
        $alertMessage = \App\Core\Session::getFlash('alert_message');
        if ($alertType && $alertMessage):
        ?>
        <div class="flash-msg flash-<?= e($alertType) ?>">
            <?= e($alertMessage) ?>
        </div>
        <?php endif; ?>
        <?= $content ?>
    </div>
</body>
</html>