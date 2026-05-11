<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * DemoRequest — pipeline lead provenienti dal form demo del sito.
 *
 * Status validi: new, contacted, demo_scheduled, demo_done, negotiating, customer, lost
 * Activity types: created, status_changed, assigned, reassigned, note_added,
 *                 email_sent, material_sent, contacted, demo_done, converted
 */
class DemoRequest
{
    public const STATUSES = [
        'new'             => 'Nuovo',
        'contacted'       => 'Contattato',
        'demo_scheduled'  => 'Demo programmata',
        'demo_done'       => 'Demo effettuata',
        'negotiating'     => 'In trattativa',
        'customer'        => 'Cliente',
        'lost'            => 'Perso',
    ];

    public const ACTIVITY_LABELS = [
        'created'        => 'Lead ricevuto dal sito',
        'status_changed' => 'Stato cambiato',
        'assigned'       => 'Assegnato',
        'reassigned'     => 'Riassegnato',
        'note_added'     => 'Nota aggiunta',
        'email_sent'     => 'Email inviata',
        'material_sent'  => 'Materiale commerciale inviato',
        'contacted'      => 'Cliente contattato',
        'demo_done'      => 'Demo effettuata',
        'converted'      => 'Convertito a cliente',
    ];

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM demo_requests WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Cerca richiesta duplicata recente (stessa email negli ultimi N ore).
     * Usato per anti-duplicato visibile sul form pubblico.
     */
    public function findRecentDuplicate(string $email, int $hours = 24): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM demo_requests
             WHERE email = :email
             AND created_at > DATE_SUB(NOW(), INTERVAL :hrs HOUR)
             ORDER BY created_at DESC LIMIT 1'
        );
        $stmt->bindValue('email', $email);
        $stmt->bindValue('hrs', $hours, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Crea nuova richiesta demo. Status iniziale = 'new'.
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO demo_requests
             (name, restaurant, email, phone, message, ip_address, referrer, utm_source, status, status_changed_at, created_at)
             VALUES (:name, :restaurant, :email, :phone, :message, :ip, :referrer, :utm, "new", NOW(), NOW())'
        );
        $stmt->execute([
            'name'       => $data['name'],
            'restaurant' => $data['restaurant'],
            'email'      => $data['email'],
            'phone'      => $data['phone'],
            'message'    => $data['message'] ?? null,
            'ip'         => $data['ip_address'] ?? null,
            'referrer'   => $data['referrer'] ?? null,
            'utm'        => $data['utm_source'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Aggiunge entry al log attività.
     */
    public function logActivity(int $leadId, string $type, ?string $description = null, ?int $userId = null, ?array $metadata = null): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO demo_request_activities
             (demo_request_id, type, description, performed_by, metadata, created_at)
             VALUES (:lid, :type, :desc, :uid, :meta, NOW())'
        );
        $stmt->execute([
            'lid'  => $leadId,
            'type' => $type,
            'desc' => $description,
            'uid'  => $userId,
            'meta' => $metadata ? json_encode($metadata) : null,
        ]);
    }

    /**
     * Recupera storico attività di un lead, dal più recente al più vecchio.
     */
    public function getActivities(int $leadId): array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, u.first_name, u.last_name
             FROM demo_request_activities a
             LEFT JOIN users u ON a.performed_by = u.id
             WHERE a.demo_request_id = :lid
             ORDER BY a.created_at DESC, a.id DESC'
        );
        $stmt->execute(['lid' => $leadId]);
        return $stmt->fetchAll();
    }

    /**
     * Lead con follow-up imminenti o scaduti, esclusi quelli "chiusi"
     * (customer/lost). Utile per widget dashboard admin.
     * Ordinati: prima i piu' scaduti, poi quelli di oggi/prossimi.
     */
    public function getUpcomingFollowups(int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, name, restaurant, email, status, next_followup_at, assigned_reseller_id,
                    DATEDIFF(next_followup_at, CURDATE()) AS days_diff
             FROM demo_requests
             WHERE next_followup_at IS NOT NULL
               AND status NOT IN ('customer', 'lost')
             ORDER BY next_followup_at ASC
             LIMIT :lim"
        );
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Counter per dashboard admin. Chiave = status, valore = count.
     */
    public function countByStatus(): array
    {
        $stmt = $this->db->query(
            'SELECT status, COUNT(*) AS cnt FROM demo_requests GROUP BY status'
        );
        $out = array_fill_keys(array_keys(self::STATUSES), 0);
        foreach ($stmt->fetchAll() as $row) {
            $out[$row['status']] = (int)$row['cnt'];
        }
        return $out;
    }

    /**
     * Lista lead con filtri opzionali (status, assigned, search, date range).
     */
    public function listFiltered(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params['status'] = $filters['status'];
        }

        if (isset($filters['assigned_reseller_id'])) {
            if ($filters['assigned_reseller_id'] === 'unassigned') {
                $where[] = 'assigned_reseller_id IS NULL';
            } else {
                $where[] = 'assigned_reseller_id = :arid';
                $params['arid'] = (int)$filters['assigned_reseller_id'];
            }
        }

        if (!empty($filters['search'])) {
            $where[] = '(name LIKE :s OR restaurant LIKE :s OR email LIKE :s)';
            $params['s'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= :df';
            $params['df'] = $filters['date_from'];
        }

        $sql = 'SELECT * FROM demo_requests WHERE ' . implode(' AND ', $where)
            . ' ORDER BY created_at DESC LIMIT :lim OFFSET :off';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue('off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Conta lead totali con stessi filtri (per paginazione).
     */
    public function countFiltered(array $filters = []): int
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params['status'] = $filters['status'];
        }
        if (isset($filters['assigned_reseller_id'])) {
            if ($filters['assigned_reseller_id'] === 'unassigned') {
                $where[] = 'assigned_reseller_id IS NULL';
            } else {
                $where[] = 'assigned_reseller_id = :arid';
                $params['arid'] = (int)$filters['assigned_reseller_id'];
            }
        }
        if (!empty($filters['search'])) {
            $where[] = '(name LIKE :s OR restaurant LIKE :s OR email LIKE :s)';
            $params['s'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= :df';
            $params['df'] = $filters['date_from'];
        }

        $sql = 'SELECT COUNT(*) FROM demo_requests WHERE ' . implode(' AND ', $where);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }
}
