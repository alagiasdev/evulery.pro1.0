<?php

namespace App\Services;

use App\Core\Database;
use PDO;

class LoginThrottle
{
    private PDO $db;
    private int $maxAttempts = 5;
    private int $decayMinutes = 15;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function isLocked(string $email, string $ip): bool
    {
        $this->cleanup();

        $since = date('Y-m-d H:i:s', strtotime("-{$this->decayMinutes} minutes"));

        // Check by email (prevents targeted brute force on one account)
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as cnt FROM login_attempts WHERE email = :email AND attempted_at >= :since'
        );
        $stmt->execute(['email' => $email, 'since' => $since]);
        if ((int)$stmt->fetch()['cnt'] >= $this->maxAttempts) {
            return true;
        }

        // Check by IP (prevents credential stuffing across accounts)
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as cnt FROM login_attempts WHERE ip_address = :ip AND attempted_at >= :since'
        );
        $stmt->execute(['ip' => $ip, 'since' => $since]);
        if ((int)$stmt->fetch()['cnt'] >= $this->maxAttempts * 4) {
            return true;
        }

        return false;
    }

    public function recordFailedAttempt(string $email, string $ip): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO login_attempts (email, ip_address) VALUES (:email, :ip)'
        );
        $stmt->execute(['email' => $email, 'ip' => $ip]);
    }

    public function clearAttempts(string $email): void
    {
        $stmt = $this->db->prepare('DELETE FROM login_attempts WHERE email = :email');
        $stmt->execute(['email' => $email]);
    }

    public function remainingSeconds(string $email): int
    {
        $since = date('Y-m-d H:i:s', strtotime("-{$this->decayMinutes} minutes"));

        $stmt = $this->db->prepare(
            'SELECT MIN(attempted_at) as oldest FROM login_attempts WHERE email = :email AND attempted_at >= :since'
        );
        $stmt->execute(['email' => $email, 'since' => $since]);
        $row = $stmt->fetch();

        if (!$row || !$row['oldest']) {
            return 0;
        }

        $unlockAt = strtotime($row['oldest']) + ($this->decayMinutes * 60);
        return max(0, $unlockAt - time());
    }

    private function cleanup(): void
    {
        $cutoff = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $this->db->prepare('DELETE FROM login_attempts WHERE attempted_at < :cutoff')
            ->execute(['cutoff' => $cutoff]);
    }
}
