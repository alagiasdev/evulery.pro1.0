<?php
// Temporary diagnostic — DELETE after use!
// Protected by secret key
if (($_GET['key'] ?? '') !== 'evlr_diag_2026') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../scripts/test-broadcast.php';
