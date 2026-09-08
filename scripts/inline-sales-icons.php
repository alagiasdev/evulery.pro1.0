<?php
/**
 * Incorpora le icone Bootstrap nei materiali di vendita come SVG inline.
 *
 * Uso (locale, serve rete la prima volta):
 *   C:\xampp\php\php.exe scripts/inline-sales-icons.php [file.html ...]
 *   ...senza argomenti lavora sui file elencati in FILES.
 *
 * PERCHE': gli HTML caricavano le icone da CDN. Se al momento della stampa PDF
 * (o dell'anteprima in demo) il CDN non risponde, le icone spariscono in
 * silenzio - e' successo ai PDF di aprile 2026. Con lo sprite SVG inline il
 * documento non dipende piu' da nessuno.
 *
 * COSA FA, in modo idempotente (rilanciabile senza danni):
 *  - raccoglie le icone usate nel file (sia i tag <i class="bi bi-*"> ancora da
 *    convertire, sia quelle gia' nello sprite);
 *  - scarica gli SVG ufficiali della versione ICONS_VERSION;
 *  - riscrive lo sprite fra i marker, converte i tag <i> rimasti e toglie il
 *    <link> al CDN delle icone.
 *
 * NB: il tag <i> resta come contenitore, cosi' tutte le regole CSS esistenti
 * (font-size, color, margin) continuano ad applicarsi senza modifiche.
 */

define('BASE_PATH', dirname(__DIR__));

const ICONS_VERSION = '1.11.3';
const CDN = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@' . ICONS_VERSION . '/icons/';
const MARK_START = '<!-- icone Bootstrap incorporate (scripts/inline-sales-icons.php) -->';
const MARK_END   = '<!-- /icone Bootstrap incorporate -->';

/** File su cui lavorare per default: quelli che diventano PDF. */
const FILES = [
    'sales/pitch-deck.html',
    'sales/onepager.html',
];

/** Scarica l'SVG di un'icona e ne restituisce il contenuto interno (i <path>). */
function fetch_icon(string $name): ?string
{
    $ctx = stream_context_create(['http' => ['timeout' => 15], 'https' => ['timeout' => 15]]);
    $svg = @file_get_contents(CDN . $name . '.svg', false, $ctx);
    if ($svg === false || !str_contains($svg, '<svg')) {
        return null;
    }
    if (!preg_match('~<svg[^>]*>(.*)</svg>~s', $svg, $m)) {
        return null;
    }
    return trim($m[1]);
}

$targets = array_slice($argv, 1) ?: FILES;
$exit = 0;

foreach ($targets as $rel) {
    $path = BASE_PATH . '/' . $rel;
    if (!is_file($path)) {
        fwrite(STDERR, "[$rel] file inesistente\n");
        $exit = 1;
        continue;
    }
    $html = file_get_contents($path);

    // 1. Icone usate: tag ancora da convertire + quelle gia' nello sprite.
    preg_match_all('~class="bi bi-([a-z0-9-]+)"~', $html, $m1);
    preg_match_all('~<use href="#bi-([a-z0-9-]+)"~', $html, $m2);
    $names = array_values(array_unique(array_merge($m1[1], $m2[1])));
    sort($names);
    if (!$names) {
        echo "[$rel] nessuna icona, salto\n";
        continue;
    }

    // 2. Scarica gli SVG.
    $symbols = [];
    foreach ($names as $n) {
        $inner = fetch_icon($n);
        if ($inner === null) {
            fwrite(STDERR, "[$rel] icona non scaricabile: $n (serve rete)\n");
            $exit = 1;
            continue 2; // non tocca il file se manca anche una sola icona
        }
        $symbols[] = '<symbol id="bi-' . $n . '" viewBox="0 0 16 16">' . $inner . '</symbol>';
    }

    // 3. Converte i tag <i> rimasti, conservando gli attributi extra.
    $html = preg_replace(
        '~<i class="bi bi-([a-z0-9-]+)"([^>]*)></i>~',
        '<i class="bi bi-$1"$2 aria-hidden="true"><svg viewBox="0 0 16 16"><use href="#bi-$1"/></svg></i>',
        $html
    );

    // 4. Regola CSS per gli SVG dentro alle icone (una volta sola).
    $css = '.bi > svg { display: inline-block; width: 1em; height: 1em; vertical-align: -.125em; fill: currentColor; }';
    if (!str_contains($html, '.bi > svg')) {
        $html = preg_replace('~\n</style>~', "\n/* icone incorporate: SVG inline al posto del font CDN */\n" . $css . "\n</style>", $html, 1);
    }

    // 5. Sprite fra i marker: sostituisce quello esistente o lo inserisce dopo <body>.
    $sprite = MARK_START . "\n"
        . '<svg xmlns="http://www.w3.org/2000/svg" style="display:none" aria-hidden="true">'
        . implode('', $symbols)
        . '</svg>' . "\n" . MARK_END;
    $existing = '~' . preg_quote(MARK_START, '~') . '.*?' . preg_quote(MARK_END, '~') . '~s';
    if (preg_match($existing, $html)) {
        $html = preg_replace($existing, $sprite, $html);
    } else {
        $html = preg_replace('~(<body[^>]*>)~', "$1\n" . $sprite, $html, 1);
    }

    // 6. Via il <link> al CDN delle icone: non serve piu'.
    $html = preg_replace(
        '~[ \t]*<link[^>]*bootstrap-icons[^>]*>\r?\n~',
        '',
        $html
    );

    file_put_contents($path, $html);
    printf("[%s] OK - %d icone incorporate, %d usi\n", $rel, count($names), substr_count($html, '<use href="#bi-'));
}

exit($exit);
