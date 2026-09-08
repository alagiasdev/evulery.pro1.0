<?php
/**
 * Rigenera i PDF dei materiali commerciali a partire dagli HTML in sales/.
 *
 * Uso (locale, Windows):
 *   C:\xampp\php\php.exe scripts/build-sales-pdf.php [nome]
 *   ...senza argomenti rigenera tutti i PDF del catalogo qui sotto.
 *
 * PERCHE' ESISTE: gli HTML caricano il font Inter e le icone Bootstrap da CDN.
 * Se il PDF viene stampato senza rete (o da un renderer che non aspetta i
 * webfont) le icone spariscono e il testo cade su un font di sistema — e' quello
 * che era successo ai PDF di aprile 2026. Chrome headless con un budget di tempo
 * generoso scarica i font e li incorpora nel PDF.
 *
 * VERIFICA dopo la generazione: lo script controlla che dentro al PDF ci sia
 * "Inter" e, se il sorgente usa ancora il font CDN per le icone, anche
 * "bootstrap-icons". I materiali con icone SVG incorporate
 * (scripts/inline-sales-icons.php) non hanno il font: le icone sono vettori.
 */

define('BASE_PATH', dirname(__DIR__));

/** HTML sorgente => PDF di destinazione (path relativi alla root). */
const TARGETS = [
    'pitch-deck' => ['sales/pitch-deck.html', 'sales/pitch-deck.pdf'],
    'onepager'   => ['sales/onepager.html',   'sales/onepager.pdf'],
];

/** Percorsi tipici di Chrome/Edge su Windows, primo che esiste. */
function find_browser(): ?string
{
    $candidates = [
        'C:\Program Files\Google\Chrome\Application\chrome.exe',
        'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
        'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe',
        'C:\Program Files\Microsoft\Edge\Application\msedge.exe',
        '/usr/bin/google-chrome',
        '/usr/bin/chromium',
    ];
    foreach ($candidates as $c) {
        if (is_file($c)) {
            return $c;
        }
    }
    return null;
}

/** Cerca i nomi dei font dentro al PDF, anche negli stream compressi. */
function pdf_contains_fonts(string $pdfPath): array
{
    $data = (string) file_get_contents($pdfPath);
    $all = $data;
    if (preg_match_all('~stream\r?\n~', $data, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[0] as $hit) {
            $start = $hit[1] + strlen($hit[0]);
            $end = strpos($data, 'endstream', $start);
            if ($end === false) {
                continue;
            }
            $raw = substr($data, $start, $end - $start);
            $out = @gzuncompress($raw);
            if ($out === false) {
                $out = @gzinflate($raw);
            }
            if ($out !== false && $out !== '') {
                $all .= "\n" . $out;
            }
        }
    }
    return [
        'inter' => str_contains($all, 'Inter'),
        'icons' => str_contains($all, 'bootstrap-icons'),
    ];
}

$browser = find_browser();
if ($browser === null) {
    fwrite(STDERR, "Chrome/Edge non trovato: serve un browser Chromium per stampare i PDF.\n");
    exit(1);
}

$only = $argv[1] ?? null;
if ($only !== null && !isset(TARGETS[$only])) {
    fwrite(STDERR, "Materiale sconosciuto: $only (attesi: " . implode(', ', array_keys(TARGETS)) . ")\n");
    exit(1);
}

$exit = 0;
foreach (TARGETS as $name => [$src, $dst]) {
    if ($only !== null && $only !== $name) {
        continue;
    }
    $srcAbs = BASE_PATH . '/' . $src;
    $dstAbs = BASE_PATH . '/' . $dst;
    if (!is_file($srcAbs)) {
        fwrite(STDERR, "[$name] sorgente mancante: $src\n");
        $exit = 1;
        continue;
    }
    // Il file di destinazione puo' essere bloccato da un lettore PDF aperto.
    if (is_file($dstAbs) && !is_writable($dstAbs)) {
        fwrite(STDERR, "[$name] $dst non scrivibile (aperto in un lettore PDF?)\n");
        $exit = 1;
        continue;
    }

    $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'evulery-' . $name . '-' . getmypid() . '.pdf';
    $cmd = escapeshellarg($browser)
        . ' --headless=new --disable-gpu --no-pdf-header-footer'
        . ' --run-all-compositor-stages-before-draw --virtual-time-budget=25000'
        . ' --print-to-pdf=' . escapeshellarg($tmp)
        . ' ' . escapeshellarg('file:///' . str_replace(DIRECTORY_SEPARATOR, '/', $srcAbs));
    exec($cmd . ' 2>&1', $out, $code);

    if (!is_file($tmp) || filesize($tmp) < 10000) {
        fwrite(STDERR, "[$name] generazione fallita (exit $code)\n" . implode("\n", $out) . "\n");
        $exit = 1;
        @unlink($tmp);
        continue;
    }

    // Icone: o sono SVG incorporati nel sorgente, o devono essere nel PDF come font.
    $iconsInlined = str_contains((string) file_get_contents($srcAbs), '<use href="#bi-');
    $fonts = pdf_contains_fonts($tmp);
    if (!$fonts['inter'] || (!$iconsInlined && !$fonts['icons'])) {
        fwrite(STDERR, "[$name] PDF senza font incorporati (Inter: "
            . ($fonts['inter'] ? 'si' : 'NO') . ", icone: " . ($iconsInlined ? 'SVG' : ($fonts['icons'] ? 'si' : 'NO'))
            . "). Controlla la connessione: i font arrivano da CDN.\n");
        $exit = 1;
        @unlink($tmp);
        continue;
    }

    if (!@copy($tmp, $dstAbs)) {
        fwrite(STDERR, "[$name] impossibile scrivere $dst (file aperto altrove?)\n");
        $exit = 1;
        @unlink($tmp);
        continue;
    }
    @unlink($tmp);
    printf("[%s] OK -> %s (%d KB, Inter incorporato, icone %s)\n", $name, $dst, round(filesize($dstAbs) / 1024), $iconsInlined ? "SVG inline" : "font incorporato");
}

exit($exit);
