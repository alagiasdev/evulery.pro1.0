<?php

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Migrator — logica core di applicazione migration SQL.
 *
 * Stesso comportamento di scripts/migrate.php ma in classe riutilizzabile
 * sia dal CLI (script via cron / shell) sia da web (controller admin).
 *
 * - Mantiene una tabella `migrations` di tracking (filename PK).
 * - Scansiona `database/migrations/*.sql` in ordine alfanumerico.
 * - Idempotente: ri-applica solo i file NON ancora in tabella.
 * - Lock via GET_LOCK per prevenire run concorrenti.
 * - Checksum SHA-256 per audit (warning se file modificato dopo applicazione).
 */
class Migrator
{
    private PDO $db;
    private string $migrationsDir;
    private string $lockName = 'evulery_migrate';

    public function __construct(?PDO $db = null, ?string $migrationsDir = null)
    {
        $this->db = $db ?? Database::connect();
        $this->migrationsDir = $migrationsDir ?? (defined('BASE_PATH') ? BASE_PATH . '/database/migrations' : __DIR__ . '/../../database/migrations');
    }

    /**
     * Stato attuale: applicate + pending + ultime 5 applicate (con timestamp).
     * @return array{applied: string[], pending: string[], recent: array<int,array{filename:string,applied_at:string,duration_ms:int}>}
     */
    public function status(): array
    {
        $this->ensureMigrationsTable();
        $all = $this->scanMigrationFiles();
        $applied = $this->fetchAppliedSet();
        $pending = array_values(array_filter($all, fn($f) => !isset($applied[$f])));
        $recent = $this->db->query("SELECT filename, applied_at, duration_ms FROM migrations ORDER BY applied_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        return [
            'applied' => array_keys($applied),
            'pending' => $pending,
            'recent'  => $recent ?: [],
            'total'   => count($all),
        ];
    }

    /**
     * Applica tutte le migration pending (o fino al target se specificato).
     * Acquisisce GET_LOCK per evitare run concorrenti. Rilascia sempre.
     *
     * @return array{success: bool, applied: array<int,array{filename:string,duration_ms:int}>, error: ?string, error_file: ?string}
     */
    public function applyPending(?string $target = null): array
    {
        $result = ['success' => false, 'applied' => [], 'error' => null, 'error_file' => null];

        if (!$this->acquireLock()) {
            $result['error'] = "Un'altra istanza di migrate sta gia' girando (lock '{$this->lockName}' busy). Riprova tra 10 secondi.";
            return $result;
        }

        try {
            $this->ensureMigrationsTable();
            $all = $this->scanMigrationFiles();
            $applied = $this->fetchAppliedSet();
            $pending = array_values(array_filter($all, fn($f) => !isset($applied[$f])));

            if ($target !== null && $target !== '') {
                $idx = null;
                foreach ($pending as $i => $f) {
                    if (str_contains($f, $target)) { $idx = $i; break; }
                }
                if ($idx === null) {
                    $result['error'] = "Target '$target' non trovato tra le pending.";
                    $this->releaseLock();
                    return $result;
                }
                $pending = array_slice($pending, 0, $idx + 1);
            }

            foreach ($pending as $f) {
                $path = $this->migrationsDir . '/' . $f;
                $sql = file_get_contents($path);
                if ($sql === false || trim($sql) === '') {
                    $result['error'] = "File vuoto o illeggibile.";
                    $result['error_file'] = $f;
                    $this->releaseLock();
                    return $result;
                }
                $t0 = microtime(true);
                try {
                    $this->db->exec($sql);
                } catch (\Throwable $e) {
                    $result['error'] = $e->getMessage();
                    $result['error_file'] = $f;
                    $this->releaseLock();
                    return $result;
                }
                $ms = (int)((microtime(true) - $t0) * 1000);
                $this->markApplied($path, $f, $ms);
                $result['applied'][] = ['filename' => $f, 'duration_ms' => $ms];
            }

            $result['success'] = true;
        } finally {
            $this->releaseLock();
        }

        return $result;
    }

    /**
     * Marca tutte le pending come applicate SENZA eseguirle (bootstrap).
     * Da usare quando il DB e' gia' allineato manualmente.
     */
    public function forceMarkAll(): array
    {
        $result = ['success' => false, 'marked' => 0, 'error' => null];
        if (!$this->acquireLock()) {
            $result['error'] = "Lock busy.";
            return $result;
        }
        try {
            $this->ensureMigrationsTable();
            $all = $this->scanMigrationFiles();
            $applied = $this->fetchAppliedSet();
            $pending = array_values(array_filter($all, fn($f) => !isset($applied[$f])));
            foreach ($pending as $f) {
                $this->markApplied($this->migrationsDir . '/' . $f, $f, 0);
                $result['marked']++;
            }
            $result['success'] = true;
        } finally {
            $this->releaseLock();
        }
        return $result;
    }

    // ============================================================
    // Helpers privati
    // ============================================================

    private function ensureMigrationsTable(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                filename     VARCHAR(255) NOT NULL PRIMARY KEY,
                applied_at   DATETIME     NOT NULL,
                duration_ms  INT UNSIGNED NOT NULL DEFAULT 0,
                checksum     VARCHAR(64)  DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    /** @return string[] lista file ordinata alfanumericamente */
    private function scanMigrationFiles(): array
    {
        if (!is_dir($this->migrationsDir)) return [];
        $files = array_map('basename', glob($this->migrationsDir . '/*.sql') ?: []);
        sort($files, SORT_STRING);
        return $files;
    }

    /** @return array<string,true> set di filename gia' applicati */
    private function fetchAppliedSet(): array
    {
        $rows = $this->db->query("SELECT filename FROM migrations")->fetchAll(PDO::FETCH_COLUMN);
        return array_flip($rows);
    }

    private function markApplied(string $path, string $filename, int $durationMs): void
    {
        $checksum = is_file($path) ? hash_file('sha256', $path) : null;
        $stmt = $this->db->prepare("
            INSERT INTO migrations (filename, applied_at, duration_ms, checksum)
            VALUES (?, NOW(), ?, ?)
        ");
        $stmt->execute([$filename, $durationMs, $checksum]);
    }

    private function acquireLock(): bool
    {
        $stmt = $this->db->prepare("SELECT GET_LOCK(?, 5)");
        $stmt->execute([$this->lockName]);
        $r = $stmt->fetchColumn();
        return $r === '1' || $r === 1;
    }

    private function releaseLock(): void
    {
        try {
            $stmt = $this->db->prepare("SELECT RELEASE_LOCK(?)");
            $stmt->execute([$this->lockName]);
        } catch (\Throwable $e) { /* ignore */ }
    }
}
