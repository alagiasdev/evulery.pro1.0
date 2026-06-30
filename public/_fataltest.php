<?php
// TEST POSITIVO TEMPORANEO — verifica che il meccanismo del logger funzioni
// in produzione (PHP 8.3, cgi-fcgi). Registra una shutdown function come
// quella di index.php e provoca un fatal apposta: se dopo la visita compare
// storage/logs/fataltest.log -> il logger FUNZIONA (quindi i 500 reali NON
// sono fatal PHP). ELIMINARE questo file E fataltest.log dopo l'uso.
define('BASE_PATH', dirname(__DIR__));

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e === null) {
        return;
    }
    @file_put_contents(
        BASE_PATH . '/storage/logs/fataltest.log',
        date('Y-m-d H:i:s') . " CAUGHT: {$e['message']} in {$e['file']}:{$e['line']}\n",
        FILE_APPEND | LOCK_EX
    );
});

header('Content-Type: text/plain; charset=utf-8');
echo "Provoco un fatal di test...\n";
funzione_che_non_esiste_test_logger();
echo "(questa riga non verra' mai stampata)\n";
