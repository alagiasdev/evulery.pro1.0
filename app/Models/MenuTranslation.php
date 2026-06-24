<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Traduzioni del menu (multilingua). L'italiano e' il testo "base" nelle colonne
 * originali; questa tabella contiene SOLO le lingue aggiuntive. Fallback automatico
 * all'italiano se una traduzione manca.
 *
 * entity_type: 'category' | 'item' | 'tenant'
 * field:       'name' | 'description' | 'tagline' | 'featured_label'
 */
class MenuTranslation
{
    private PDO $db;

    /** Lingue supportate dalla piattaforma. 'it' e' sempre la base (non traducibile). */
    public const LANGUAGES = [
        'it' => ['label' => 'Italiano', 'short' => 'IT'],
        'en' => ['label' => 'English',  'short' => 'EN'],
        'de' => ['label' => 'Deutsch',  'short' => 'DE'],
        'fr' => ['label' => 'Français', 'short' => 'FR'],
        'es' => ['label' => 'Español',  'short' => 'ES'],
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Normalizza la lista lingue di un tenant (CSV) in array valido, con 'it' sempre primo.
     */
    public static function parseLanguages(?string $csv): array
    {
        $langs = array_filter(array_map('trim', explode(',', (string)$csv)));
        $langs = array_values(array_intersect($langs, array_keys(self::LANGUAGES)));
        $langs = array_values(array_unique(array_merge(['it'], $langs)));
        return $langs;
    }

    /** Lingue aggiuntive (escluso 'it'). */
    public static function extraLanguages(?string $csv): array
    {
        return array_values(array_filter(self::parseLanguages($csv), fn($l) => $l !== 'it'));
    }

    /**
     * Tutte le traduzioni di un'entita' in una lingua: [field => value].
     */
    public function forEntity(int $tenantId, string $entityType, int $entityId, string $lang): array
    {
        if ($lang === 'it') {
            return [];
        }
        $stmt = $this->db->prepare(
            'SELECT field, value FROM menu_translations
             WHERE tenant_id = :t AND entity_type = :et AND entity_id = :eid AND lang = :lang'
        );
        $stmt->execute(['t' => $tenantId, 'et' => $entityType, 'eid' => $entityId, 'lang' => $lang]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[$row['field']] = $row['value'];
        }
        return $out;
    }

    /**
     * Traduzioni in blocco per molte entita' dello stesso tipo, in una lingua:
     * [entity_id => [field => value]].
     */
    public function bulk(int $tenantId, string $entityType, string $lang): array
    {
        if ($lang === 'it') {
            return [];
        }
        $stmt = $this->db->prepare(
            'SELECT entity_id, field, value FROM menu_translations
             WHERE tenant_id = :t AND entity_type = :et AND lang = :lang'
        );
        $stmt->execute(['t' => $tenantId, 'et' => $entityType, 'lang' => $lang]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int)$row['entity_id']][$row['field']] = $row['value'];
        }
        return $out;
    }

    /**
     * Upsert di un campo tradotto. Valore vuoto => elimina la riga (torna al fallback IT).
     */
    public function put(int $tenantId, string $entityType, int $entityId, string $lang, string $field, ?string $value): void
    {
        if ($lang === 'it' || !array_key_exists($lang, self::LANGUAGES)) {
            return; // l'italiano non si salva qui
        }
        $value = trim((string)$value);
        if ($value === '') {
            $del = $this->db->prepare(
                'DELETE FROM menu_translations
                 WHERE tenant_id = :t AND entity_type = :et AND entity_id = :eid AND lang = :lang AND field = :f'
            );
            $del->execute(['t' => $tenantId, 'et' => $entityType, 'eid' => $entityId, 'lang' => $lang, 'f' => $field]);
            return;
        }
        $stmt = $this->db->prepare(
            'INSERT INTO menu_translations (tenant_id, entity_type, entity_id, lang, field, value)
             VALUES (:t, :et, :eid, :lang, :f, :v)
             ON DUPLICATE KEY UPDATE value = VALUES(value)'
        );
        $stmt->execute(['t' => $tenantId, 'et' => $entityType, 'eid' => $entityId, 'lang' => $lang, 'f' => $field, 'v' => $value]);
    }

    /**
     * Numero di entita' (entityType) con il NOME tradotto e non vuoto, in una lingua.
     * Usato per il cruscotto completezza in dashboard.
     */
    public function translatedNameCount(int $tenantId, string $entityType, string $lang): int
    {
        if ($lang === 'it') {
            return 0;
        }
        $stmt = $this->db->prepare(
            "SELECT COUNT(DISTINCT entity_id) AS c FROM menu_translations
             WHERE tenant_id = :t AND entity_type = :et AND lang = :lang AND field = 'name' AND value <> ''"
        );
        $stmt->execute(['t' => $tenantId, 'et' => $entityType, 'lang' => $lang]);
        return (int)$stmt->fetch()['c'];
    }

    /**
     * Elimina tutte le traduzioni di un'entita' (cleanup quando si elimina piatto/categoria).
     */
    public function deleteForEntity(int $tenantId, string $entityType, int $entityId): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM menu_translations WHERE tenant_id = :t AND entity_type = :et AND entity_id = :eid'
        );
        $stmt->execute(['t' => $tenantId, 'et' => $entityType, 'eid' => $entityId]);
    }
}
