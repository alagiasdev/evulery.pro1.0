<?php

namespace App\Models;

use App\Core\Database;
use PDO;
use Throwable;

/**
 * Impostazioni globali dell'applicazione (chiave/valore, tabella app_settings).
 *
 * PERCHE' NON USA App\Core\Cache: questi valori si leggono poche volte per
 * richiesta — il checkout, la creazione di un ordine — e una cache su file
 * andrebbe invalidata a ogni salvataggio. Dimenticarsene una volta
 * significherebbe lasciare in giro un IBAN vecchio, cioe' bonifici che
 * arrivano sul conto sbagliato. Qui si legge una volta sola per richiesta e
 * si tiene in memoria: una query, e niente cache da invalidare.
 *
 * I valori sono SEMPRE stringhe: chi ha bisogno di un numero usa float()
 * o int(), cosi' la conversione sta in un posto solo.
 */
class AppSetting
{
    /**
     * Valori di riferimento. Servono a due cose: dare un comportamento
     * sensato se una chiave manca (tabella non ancora migrata, riga
     * cancellata a mano) e documentare quali chiavi esistono.
     */
    public const DEFAULTS = [
        'bank_holder'          => '',
        'bank_iban'            => '',
        'bank_name'            => '',
        'bank_bic'             => '',
        'tax_notice'           => '',
        'stamp_duty_amount'    => '2.00',
        'stamp_duty_threshold' => '77.47',
        'grace_days'           => '10',
        'billing_email_from'   => '',
    ];

    /** @var array<string,string>|null Memo valido per la singola richiesta. */
    private static ?array $memo = null;

    /** @return array<string,string> */
    public static function all(): array
    {
        if (self::$memo !== null) {
            return self::$memo;
        }

        $rows = [];
        try {
            $stmt = Database::getInstance()->query(
                'SELECT setting_key, setting_value FROM app_settings'
            );
            $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
        } catch (Throwable $e) {
            // Tabella non ancora migrata, o DB momentaneamente irraggiungibile:
            // si lavora sui valori di riferimento invece di far esplodere la
            // pagina. Resta traccia nel log perche' non passi inosservato.
            app_log('AppSetting: lettura fallita, uso i valori di default (' . $e->getMessage() . ')', 'warning');
        }

        self::$memo = array_merge(self::DEFAULTS, array_map('strval', $rows));
        return self::$memo;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return self::all()[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function float(string $key): float
    {
        return (float) str_replace(',', '.', (string) self::get($key, '0'));
    }

    public static function int(string $key): int
    {
        return (int) self::get($key, '0');
    }

    /**
     * Scrive piu' chiavi in un colpo solo. Le chiavi sconosciute vengono
     * ignorate: l'elenco di cosa e' salvabile e' DEFAULTS, non quello che
     * arriva dal form.
     *
     * @param array<string,string> $pairs
     */
    public static function setMany(array $pairs): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO app_settings (setting_key, setting_value) VALUES (:k, :v)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );

        foreach ($pairs as $k => $v) {
            if (!array_key_exists($k, self::DEFAULTS)) {
                continue;
            }
            $stmt->execute(['k' => $k, 'v' => (string) $v]);
        }

        self::$memo = null; // la prossima lettura ripesca dal database
    }

    /**
     * Validazione IBAN con il controllo mod-97 previsto dallo standard
     * (ISO 13616), non solo un controllo di forma.
     *
     * E' il campo piu' importante della pagina: un IBAN formalmente
     * plausibile ma sbagliato in una cifra manda i bonifici in errore, e il
     * cliente lo scopre giorni dopo pensando di aver pagato. Il mod-97
     * intercetta esattamente il refuso di una cifra o l'inversione di due.
     */
    public static function ibanValido(string $iban): bool
    {
        $iban = strtoupper(preg_replace('/\s+/', '', $iban) ?? '');

        if (!preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{10,30}$/', $iban)) {
            return false;
        }
        // L'IBAN italiano ha esattamente 27 caratteri: sugli altri paesi non
        // ci mettiamo a fare da tabella, basta il mod-97.
        if (str_starts_with($iban, 'IT') && strlen($iban) !== 27) {
            return false;
        }

        // Le prime quattro cifre vanno in fondo, poi ogni lettera diventa un
        // numero (A=10 ... Z=35). Il resto della divisione per 97 deve dare 1.
        $riordinato = substr($iban, 4) . substr($iban, 0, 4);
        $numerico = '';
        foreach (str_split($riordinato) as $c) {
            $numerico .= ctype_alpha($c) ? (string)(ord($c) - 55) : $c;
        }

        // Il numero e' troppo grande per un intero: si divide a pezzi,
        // trascinando il resto. Nessuna dipendenza da bcmath.
        $resto = 0;
        foreach (str_split($numerico) as $cifra) {
            $resto = ($resto * 10 + (int) $cifra) % 97;
        }

        return $resto === 1;
    }

    /** IBAN a gruppi di quattro, come si legge sugli estratti conto. */
    public static function ibanFormattato(string $iban): string
    {
        $pulito = strtoupper(preg_replace('/\s+/', '', $iban) ?? '');
        return trim(chunk_split($pulito, 4, ' '));
    }
}
