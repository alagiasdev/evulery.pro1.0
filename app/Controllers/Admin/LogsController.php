<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuditLog;

/**
 * Pagina Admin → Log applicativi: legge i file scritti da app_log() in
 * storage/logs/. Nasce perche' senza accesso SSH l'unico modo di leggerli era
 * il File Manager di cPanel, file per file, senza poter cercare.
 *
 * Sicurezza: sta nel gruppo /admin (middleware admin, vedi config/routes.php).
 * Le righe contengono indirizzi IP, quindi non deve uscire da li'.
 *
 * ATTRAVERSAMENTO PERCORSI: il nome del file arriva dalla query string. Non
 * viene MAI concatenato cosi' com'e': deve corrispondere esattamente al formato
 * dei nomi generati da app_log(), e in piu' il percorso risolto deve trovarsi
 * dentro storage/logs. Senza il secondo controllo un ../ ben costruito potrebbe
 * uscire dalla cartella; senza il primo, un symlink dentro la cartella potrebbe
 * puntare fuori.
 */
class LogsController
{
    /** Nomi ammessi: 2026-09-10.log e perf-2026-09-10.log, niente altro. */
    private const NOME_VALIDO = '/^(perf-)?\d{4}-\d{2}-\d{2}\.log$/';

    /** Limite di righe mostrate per un singolo giorno, per non gonfiare la pagina. */
    private const MAX_RIGHE = 2000;

    /** Limite di risultati della ricerca fra tutti i file. */
    private const MAX_RISULTATI = 300;

    public function index(Request $request): void
    {
        $dir = $this->dir();
        $files = $this->elencoFile($dir);

        $q        = trim((string) $request->input('q', ''));
        $livello  = (string) $request->input('livello', '');
        $file     = (string) $request->input('file', '');

        $righe = [];
        $troncato = false;
        $fileCorrente = null;

        if ($q !== '') {
            [$righe, $troncato] = $this->cerca($dir, $files, $q, $livello);
        } elseif ($file !== '') {
            $percorso = $this->percorsoSicuro($dir, $file);
            if ($percorso === null) {
                flash('danger', 'Nome file non valido.');
                Response::redirect(url('admin/logs'));
                return;
            }
            $fileCorrente = $file;
            [$righe, $troncato] = $this->leggiFile($percorso, $livello);
        }

        view('admin/logs/index', [
            'title'        => 'Log applicativi',
            'activeMenu'   => 'logs',
            'files'        => $files,
            'righe'        => $righe,
            'troncato'     => $troncato,
            'q'            => $q,
            'livello'      => $livello,
            'fileCorrente' => $fileCorrente,
            'totaleFile'   => count($files),
            'pesoTotale'   => array_sum(array_column($files, 'bytes')),
        ], 'admin');
    }

    /**
     * Elimina i log piu' vecchi di N giorni. Nessuno li ha mai cancellati: a
     * settembre 2026 c'erano 74 file che partivano da marzo.
     */
    public function purge(Request $request): void
    {
        $giorni = max(7, (int) $request->input('giorni', 90));
        $dir    = $this->dir();
        $limite = strtotime("-{$giorni} days");
        $eliminati = 0;

        foreach ($this->elencoFile($dir) as $f) {
            // La data sta nel NOME, non nella data di modifica del file: e' quella
            // che conta, ed e' immune a copie o ripristini che toccano l'mtime.
            if ($f['data'] !== null && strtotime($f['data']) < $limite) {
                $percorso = $this->percorsoSicuro($dir, $f['nome']);
                if ($percorso !== null && @unlink($percorso)) {
                    $eliminati++;
                }
            }
        }

        if ($eliminati > 0) {
            AuditLog::log('logs_purged', "Eliminati {$eliminati} file di log piu' vecchi di {$giorni} giorni", Auth::id());
            flash('success', "Eliminati {$eliminati} file di log.");
        } else {
            flash('info', 'Nessun file piu' . "'" . " vecchio di {$giorni} giorni.");
        }

        Response::redirect(url('admin/logs'));
    }

    // ---------------------------------------------------------------- privati

    private function dir(): string
    {
        return BASE_PATH . '/storage/logs';
    }

    /**
     * Percorso sicuro o null. Due controlli indipendenti: il nome deve avere il
     * formato giusto, e il percorso risolto deve stare dentro la cartella.
     */
    private function percorsoSicuro(string $dir, string $nome): ?string
    {
        if (!preg_match(self::NOME_VALIDO, $nome)) {
            return null;
        }
        $reale = realpath($dir . '/' . $nome);
        $base  = realpath($dir);
        if ($reale === false || $base === false || !str_starts_with($reale, $base)) {
            return null;
        }
        return $reale;
    }

    /** @return array<int, array{nome:string,data:?string,bytes:int,righe:int,warning:int,error:int,perf:bool}> */
    private function elencoFile(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }
        $out = [];
        foreach (scandir($dir) ?: [] as $nome) {
            if (!preg_match(self::NOME_VALIDO, $nome, $m)) {
                continue;
            }
            $percorso = $dir . '/' . $nome;
            $contenuto = (string) @file_get_contents($percorso);
            $out[] = [
                'nome'    => $nome,
                'data'    => preg_match('/(\d{4}-\d{2}-\d{2})/', $nome, $d) ? $d[1] : null,
                'bytes'   => (int) @filesize($percorso),
                'righe'   => $contenuto === '' ? 0 : substr_count($contenuto, "\n"),
                'warning' => substr_count($contenuto, '] [warning]'),
                'error'   => substr_count($contenuto, '] [error]'),
                'perf'    => str_starts_with($nome, 'perf-'),
            ];
        }
        // Piu' recenti in cima. NON basta ordinare per nome: 'perf-2026-09-10'
        // viene dopo '2026-09-10' in alfabetico, quindi tutti i perf finirebbero
        // in cima e i log normali sepolti sotto. Si ordina per DATA, e a parita'
        // di data prima il log applicativo e poi quello delle prestazioni.
        usort($out, function ($a, $b) {
            $d = strcmp((string) $b['data'], (string) $a['data']);
            return $d !== 0 ? $d : ($a['perf'] <=> $b['perf']);
        });
        return $out;
    }

    /** @return array{0: array<int, array{file:string,ts:string,livello:string,testo:string}>, 1: bool} */
    private function leggiFile(string $percorso, string $livello): array
    {
        $righe = @file($percorso, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $righe = array_reverse($righe); // le piu' recenti in cima
        $out = [];
        foreach ($righe as $r) {
            $p = $this->analizza($r, basename($percorso));
            if ($livello !== '' && $p['livello'] !== $livello) {
                continue;
            }
            $out[] = $p;
            if (count($out) >= self::MAX_RIGHE) {
                return [$out, true];
            }
        }
        return [$out, false];
    }

    /** @return array{0: array<int, array{file:string,ts:string,livello:string,testo:string}>, 1: bool} */
    private function cerca(string $dir, array $files, string $q, string $livello): array
    {
        $out = [];
        foreach ($files as $f) {
            $percorso = $this->percorsoSicuro($dir, $f['nome']);
            if ($percorso === null) {
                continue;
            }
            foreach (array_reverse(@file($percorso, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []) as $r) {
                if (stripos($r, $q) === false) {
                    continue;
                }
                $p = $this->analizza($r, $f['nome']);
                if ($livello !== '' && $p['livello'] !== $livello) {
                    continue;
                }
                $out[] = $p;
                if (count($out) >= self::MAX_RISULTATI) {
                    return [$out, true];
                }
            }
        }
        return [$out, false];
    }

    /**
     * Formato scritto da app_log(): "[data ora] [livello] messaggio".
     * Una riga che non lo rispetta viene mostrata comunque, cosi' com'e':
     * meglio una riga senza etichetta che una riga sparita.
     */
    private function analizza(string $riga, string $file): array
    {
        if (preg_match('/^\[([^\]]+)\] \[([a-z]+)\] (.*)$/s', $riga, $m)) {
            return ['file' => $file, 'ts' => $m[1], 'livello' => $m[2], 'testo' => $m[3]];
        }
        return ['file' => $file, 'ts' => '', 'livello' => '', 'testo' => $riga];
    }
}
