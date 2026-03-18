<?php

namespace App\Services;

use App\Core\Database;
use PDO;

class RateLimit
{
    private PDO $db;

    // Limits: [max_requests, window_seconds]
    private array $limits = [
        'GET'  => [60, 60],   // 60 requests per minute (availability checks)
        'POST' => [10, 60],   // 10 requests per minute (bookings, cancellations)
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function check(string $ip, string $method): bool
    {
        $this->cleanup();

        [$maxRequests, $windowSeconds] = $this->limits[$method] ?? [60, 60];

        $since = date('Y-m-d H:i:s', time() - $windowSeconds);

        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM rate_limits WHERE ip_address = :ip AND endpoint = :endpoint AND attempted_at > :since'
        );
        $stmt->execute([
            'ip'       => $ip,
            'endpoint' => $method,
            'since'    => $since,
        ]);

        return (int)$stmt->fetchColumn() < $maxRequests;
    }

    public function record(string $ip, string $method): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO rate_limits (ip_address, endpoint, attempted_at) VALUES (:ip, :endpoint, NOW())'
        );
        $stmt->execute([
            'ip'       => $ip,
            'endpoint' => $method,
        ]);
    }

    public function remainingSeconds(string $ip, string $method): int
    {
        [$maxRequests, $windowSeconds] = $this->limits[$method] ?? [60, 60];

        $stmt = $this->db->prepare(
            'SELECT MIN(attempted_at) FROM rate_limits WHERE ip_address = :ip AND endpoint = :endpoint AND attempted_at > :since'
        );
        $stmt->execute([
            'ip'       => $ip,
            'endpoint' => $method,
            'since'    => date('Y-m-d H:i:s', time() - $windowSeconds),
        ]);

        $oldest = $stmt->fetchColumn();
        if (!$oldest) {
            return 0;
        }

        return max(0, $windowSeconds - (time() - strtotime($oldest)));
    }

    public function checkCustom(string $ip, string $key, int $maxRequests, int $windowSeconds): bool
    {
        $this->cleanup();

        $since = date('Y-m-d H:i:s', time() - $windowSeconds);

        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM rate_limits WHERE ip_address = :ip AND endpoint = :endpoint AND attempted_at > :since'
        );
        $stmt->execute([
            'ip'       => $ip,
            'endpoint' => $key,
            'since'    => $since,
        ]);

        return (int)$stmt->fetchColumn() < $maxRequests;
    }

    public function recordCustom(string $ip, string $key): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO rate_limits (ip_address, endpoint, attempted_at) VALUES (:ip, :endpoint, NOW())'
        );
        $stmt->execute([
            'ip'       => $ip,
            'endpoint' => $key,
        ]);
    }

    private function cleanup(): void
    {
        // Remove records older than 5 minutes
        $this->db->prepare('DELETE FROM rate_limits WHERE attempted_at < :cutoff')
            ->execute(['cutoff' => date('Y-m-d H:i:s', time() - 300)]);
    }
}
