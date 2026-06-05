<?php

namespace App\Services;

use App\Core\Database;

/**
 * Heartbeat per auto-refresh dashboard (Fase C).
 *
 * Risponde alla domanda "qualcosa e' cambiato dall'ultimo polling?" senza
 * scaricare interi dataset: il client invia l'ETag dell'ultima risposta nota,
 * il server confronta hash+count e ritorna 304 se invariato, 200 con nuovo hash
 * altrimenti.
 *
 * Costo query: O(log n) grazie all'indice idx_tenant_date su (tenant_id, reservation_date).
 * Polling 60s su 100 utenti attivi simultanei = ~1.7 req/s, trascurabile sul VPS.
 */
class HeartbeatService
{
    /**
     * Stato della lista prenotazioni per un tenant + giorno (o range).
     *
     * @return array{hash:string, last_updated_at:?string, count:int}
     */
    public static function forReservations(int $tenantId, string $date, ?string $dateTo = null): array
    {
        $db = Database::getInstance();

        if ($dateTo && $dateTo !== $date) {
            $sql = 'SELECT MAX(updated_at) AS last_updated, COUNT(*) AS cnt
                    FROM reservations
                    WHERE tenant_id = :tid AND reservation_date BETWEEN :d1 AND :d2';
            $params = ['tid' => $tenantId, 'd1' => $date, 'd2' => $dateTo];
        } else {
            $sql = 'SELECT MAX(updated_at) AS last_updated, COUNT(*) AS cnt
                    FROM reservations
                    WHERE tenant_id = :tid AND reservation_date = :d';
            $params = ['tid' => $tenantId, 'd' => $date];
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        $lastUpdated = $row['last_updated'] ?: null;
        $count       = (int) ($row['cnt'] ?? 0);

        // Hash deterministico: collisioni teoricamente possibili solo se due
        // modifiche avvengono nello stesso secondo SENZA cambiare il count
        // totale. Il polling 60s riconcilia comunque al ciclo successivo.
        $hash = sha1(($lastUpdated ?? 'none') . '|' . $count);

        return [
            'hash'            => $hash,
            'last_updated_at' => $lastUpdated,
            'count'           => $count,
        ];
    }
}
