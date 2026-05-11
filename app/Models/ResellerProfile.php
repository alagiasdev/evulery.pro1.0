<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * ResellerProfile — dati estesi degli utenti con ruolo 'reseller'.
 * Contiene le commissioni configurabili per piano (decise il 2026-05-11).
 * Tabella separata da users per non appesantire users e permettere
 * estensioni future (partner_code, partita_iva, ecc.).
 */
class ResellerProfile
{
    public const DEFAULT_COMMISSION_SETUP        = 130.00;
    public const DEFAULT_COMMISSION_STARTER      = 120.00;
    public const DEFAULT_COMMISSION_PROFESSIONAL = 200.00;
    public const DEFAULT_COMMISSION_ENTERPRISE   = 320.00;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM reseller_profiles WHERE user_id = :id');
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Crea profilo con commissioni custom o default.
     */
    public function create(int $userId, array $data = []): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO reseller_profiles
             (user_id, commission_setup, commission_starter, commission_professional, commission_enterprise, notes)
             VALUES (:uid, :setup, :st, :pr, :ent, :notes)'
        );
        $stmt->execute([
            'uid'   => $userId,
            'setup' => $data['commission_setup']        ?? self::DEFAULT_COMMISSION_SETUP,
            'st'    => $data['commission_starter']      ?? self::DEFAULT_COMMISSION_STARTER,
            'pr'    => $data['commission_professional'] ?? self::DEFAULT_COMMISSION_PROFESSIONAL,
            'ent'   => $data['commission_enterprise']   ?? self::DEFAULT_COMMISSION_ENTERPRISE,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function update(int $userId, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE reseller_profiles
             SET commission_setup = :setup,
                 commission_starter = :st,
                 commission_professional = :pr,
                 commission_enterprise = :ent,
                 notes = :notes
             WHERE user_id = :uid'
        );
        return $stmt->execute([
            'uid'   => $userId,
            'setup' => $data['commission_setup']        ?? self::DEFAULT_COMMISSION_SETUP,
            'st'    => $data['commission_starter']      ?? self::DEFAULT_COMMISSION_STARTER,
            'pr'    => $data['commission_professional'] ?? self::DEFAULT_COMMISSION_PROFESSIONAL,
            'ent'   => $data['commission_enterprise']   ?? self::DEFAULT_COMMISSION_ENTERPRISE,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Lista tutti i reseller (id + nome + email) per dropdown e selezioni.
     */
    public function listResellers(): array
    {
        return $this->db->query(
            "SELECT u.id, u.first_name, u.last_name, u.email, u.is_active
             FROM users u
             WHERE u.role = 'reseller'
             ORDER BY u.first_name, u.last_name"
        )->fetchAll();
    }
}
