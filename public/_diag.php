<?php
// FILE DIAGNOSTICO TEMPORANEO — eliminare subito dopo l'uso.
// Mostra i parametri PHP runtime e forza il reset dell'opcache, cosi' il
// nuovo index.php (con il logger dei fatal) diventa attivo.
header('Content-Type: text/plain; charset=utf-8');

echo "PHP version              = " . PHP_VERSION . "\n";
echo "SAPI                     = " . PHP_SAPI . "\n";
echo "memory_limit             = " . ini_get('memory_limit') . "\n";
echo "max_execution_time       = " . ini_get('max_execution_time') . "\n";
echo "opcache.enable           = " . ini_get('opcache.enable') . "\n";
echo "opcache.validate_timestamps = " . ini_get('opcache.validate_timestamps') . "\n";
echo "opcache.revalidate_freq  = " . ini_get('opcache.revalidate_freq') . "\n";

if (function_exists('opcache_reset')) {
    echo "opcache_reset            = " . (opcache_reset() ? "OK (cache svuotata)" : "FALLITO") . "\n";
} else {
    echo "opcache_reset            = funzione non disponibile\n";
}

echo "\nFatto. ORA ELIMINA QUESTO FILE (_diag.php).\n";
