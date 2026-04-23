<?php

namespace App\Core;

/**
 * Minimal request latency logger.
 *
 * Registers a shutdown function that appends a single line to
 * storage/logs/perf-YYYY-MM-DD.log if the request duration exceeds
 * a configurable threshold. No DB, no side effects on the request.
 *
 * Env config:
 *   PERF_LOG_THRESHOLD_MS   slow-request threshold in ms (default 500)
 *   PERF_LOG_ENABLED        "0" to fully disable (default "1")
 *
 * Format:
 *   [2026-04-23 14:05:23] GET /dashboard dur=842 mem=8192 status=200 tenant=2
 */
class PerfLog
{
    public static function install(): void
    {
        if ((string)env('PERF_LOG_ENABLED', '1') === '0') {
            return;
        }

        $threshold = (int)env('PERF_LOG_THRESHOLD_MS', 500);
        if ($threshold <= 0) {
            return;
        }

        $startTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);

        register_shutdown_function(static function () use ($startTime, $threshold) {
            $durationMs = (int)round((microtime(true) - $startTime) * 1000);
            if ($durationMs < $threshold) {
                return;
            }

            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $uri = $_SERVER['REQUEST_URI'] ?? '-';
            // Strip query string to keep the log compact and group by route
            $path = strtok($uri, '?');
            $mem = (int)round(memory_get_peak_usage(true) / 1024);
            $status = http_response_code() ?: 0;
            $tenantId = $_SESSION['tenant_id'] ?? '-';

            $line = sprintf(
                "[%s] %s %s dur=%d mem=%d status=%d tenant=%s\n",
                date('Y-m-d H:i:s'),
                $method,
                $path,
                $durationMs,
                $mem,
                $status,
                $tenantId
            );

            $dir = BASE_PATH . '/storage/logs';
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            @file_put_contents(
                $dir . '/perf-' . date('Y-m-d') . '.log',
                $line,
                FILE_APPEND | LOCK_EX
            );
        });
    }
}
