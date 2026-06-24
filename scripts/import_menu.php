<?php
/**
 * Import menù da file Word/ODT.
 *
 * Uso:
 *   php scripts/import_menu.php --file="C:\tmp\menu.odt"                      (PREVIEW, non scrive)
 *   php scripts/import_menu.php --file="C:\tmp\menu.odt" --tenant=slug --commit  (IMPORTA)
 *   ... --commit --force   (procede anche se il tenant ha già piatti; dedup per nome)
 *
 * Sicuro: solo INSERT, dedup per nome (categoria/piatto) → ri-eseguibile senza duplicati.
 */
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/vendor/autoload.php';
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v); putenv(trim($k) . '=' . trim($v));
    }
}
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Europe/Rome');
require_once BASE_PATH . '/app/Helpers/functions.php';

use App\Core\Database;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Tenant;

// ---- args ----
$args = [];
foreach (array_slice($argv, 1) as $a) {
    if (preg_match('/^--([^=]+)=(.*)$/', $a, $m)) { $args[$m[1]] = $m[2]; }
    elseif (preg_match('/^--(.+)$/', $a, $m)) { $args[$m[1]] = true; }
}
$file   = $args['file']   ?? 'C:\\tmp\\menu.odt';
$slug   = $args['tenant'] ?? null;
$commit = isset($args['commit']);
$force  = isset($args['force']);

// ---- legenda allergeni DEL CLIENTE (numerazione propria, NON standard UE) ----
$ALLERGEN_MAP = [1=>'gluten',2=>'crustaceans',3=>'molluscs',4=>'eggs',5=>'fish',6=>'milk',7=>'nuts',8=>'celery'];
$CAT_ICONS = ['per iniziare'=>'bi-egg-fried','le paste'=>'bi-cup-hot','secondi'=>'bi-fire','i contorni'=>'bi-list','i dolci'=>'bi-cake2'];

// ---- correzioni refusi ----
function fixText(string $s): string {
    $repl = [
        'baccala\'' => 'baccalà',
        'aro maticho' => 'aromatiche', 'aromaticho' => 'aromatiche',
        'Siciliae' => 'Sicilia e',
        'del perù' => 'del Perù',
        'brulèe' => 'brûlée',
        'pollo,con' => 'pollo, con',
    ];
    $s = str_replace(["\xC2\xA0", "\xE2\x80\xAF"], ' ', $s); // NBSP / narrow NBSP → spazio normale
    $s = strtr($s, $repl);
    $s = preg_replace('/,(?=\S)/u', ', ', $s);   // virgola senza spazio → ", "
    $s = preg_replace('/\s{2,}/u', ' ', $s);     // spazi multipli
    return trim($s);
}

// ---- estrazione testo da ODT/DOCX ----
function extractText(string $path): string {
    if (!file_exists($path)) { fwrite(STDERR, "File non trovato: $path\n"); exit(2); }
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) { fwrite(STDERR, "Impossibile aprire (zip): $path\n"); exit(2); }
    $inner = $zip->locateName('content.xml') !== false ? 'content.xml' : 'word/document.xml';
    $xml = $zip->getFromName($inner);
    $zip->close();
    if ($xml === false) { fwrite(STDERR, "XML interno non trovato\n"); exit(2); }
    $xml = preg_replace('/<(w|text):tab[^>]*\/>/', "\t", $xml);
    $xml = preg_replace('/<(w|text):(line-)?break[^>]*\/>/', "\n", $xml);
    $xml = preg_replace('/<\/(w|text):p>/', "\n", $xml);
    $xml = preg_replace('/<\/text:h>/', "\n", $xml);
    $txt = preg_replace('/<[^>]+>/', '', $xml);
    return html_entity_decode($txt, ENT_QUOTES, 'UTF-8');
}

// ---- sorgente dati: file PHP già parsato (--data) oppure parsing dell'ODT/DOCX ----
$menu = [];
if (isset($args['data'])) {
    if (!file_exists($args['data'])) { fwrite(STDERR, "File dati non trovato: {$args['data']}\n"); exit(2); }
    $menu = require $args['data'];
    if (!is_array($menu)) { fwrite(STDERR, "File dati non valido.\n"); exit(2); }
    goto preview;
}

// ---- parsing ----
$text = extractText($file);
$lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $text)), fn($l) => $l !== '');

$curIdx = -1;
foreach ($lines as $line) {
    if (stripos($line, 'ELENCO DEGLI ALLERGENI') !== false) continue;

    $hasPrice = (bool)preg_match('/\d{1,3},\d{2}/', $line);
    $clean = trim(rtrim($line, " -–—"));

    // Intestazione categoria: niente prezzo + tutto maiuscolo
    if (!$hasPrice && preg_match('/^[A-ZÀ-Ù\s\'\-]+$/u', $clean) && mb_strlen($clean) <= 40) {
        // sentence-case: solo prima lettera maiuscola
        $name = mb_strtoupper(mb_substr($clean, 0, 1, 'UTF-8'), 'UTF-8') . mb_strtolower(mb_substr($clean, 1, null, 'UTF-8'), 'UTF-8');
        $menu[] = ['name' => $name, 'icon' => $CAT_ICONS[mb_strtolower($name, 'UTF-8')] ?? 'bi-list', 'items' => []];
        $curIdx = count($menu) - 1;
        continue;
    }
    if (!$hasPrice || $curIdx < 0) continue; // riga non riconosciuta

    // allergeni: gruppo numerico tra parentesi a fine riga
    $allerg = [];
    $work = $line;
    if (preg_match('/\(([\d\s,\-\.]+)\)\s*$/u', $work, $m)) {
        if (preg_match_all('/\d/', $m[1], $dm)) {
            foreach ($dm[0] as $d) {
                $d = (int)$d;
                if (isset($ALLERGEN_MAP[$d])) $allerg[$ALLERGEN_MAP[$d]] = true;
            }
        }
        $work = preg_replace('/\([\d\s,\-\.]+\)\s*$/u', '', $work);
    }
    // prezzo: ultimo token X,XX (offset byte di preg → uso substr, NON mb_substr)
    preg_match_all('/(\d{1,3}),(\d{2})/', $work, $pm, PREG_OFFSET_CAPTURE);
    if (empty($pm[0])) continue;
    $last = end($pm[0]);
    $price = (float)(str_replace(',', '.', $last[0]));
    $name = fixText(substr($work, 0, $last[1]));
    if ($name === '') continue;

    $menu[$curIdx]['items'][] = ['name' => $name, 'price' => $price, 'allergens' => array_keys($allerg)];
}

// ---- output preview ----
preview:
$totItems = array_sum(array_map(fn($c) => count($c['items']), $menu));
$ALL_LABELS = MenuItem::ALLERGENS;
echo "\n=== ANTEPRIMA IMPORT — " . count($menu) . " categorie, $totItems piatti ===\n";
foreach ($menu as $cat) {
    echo "\n## {$cat['name']}  [{$cat['icon']}]\n";
    foreach ($cat['items'] as $it) {
        $al = array_map(fn($k) => $ALL_LABELS[$k] ?? $k, $it['allergens']);
        printf("  - %-70s %6.2f  [%s]\n", mb_strimwidth($it['name'], 0, 70, '…', 'UTF-8'), $it['price'], implode(', ', $al));
    }
}

if (isset($args['dump'])) {
    file_put_contents($args['dump'], "<?php\n// Dati menù validati — generato da scripts/import_menu.php\nreturn " . var_export($menu, true) . ";\n");
    echo "\n>>> Dump dati scritto in: {$args['dump']}\n";
    exit(0);
}

// --sql: genera SQL pronto per phpMyAdmin (nessuna CLI necessaria lato cliente)
if (isset($args['sql'])) {
    if (!$slug) { fwrite(STDERR, "\n--tenant=SLUG obbligatorio con --sql\n"); exit(3); }
    $q = fn($s) => "'" . str_replace(["\\", "'"], ["\\\\", "''"], (string)$s) . "'";
    $out  = "-- Import menù per tenant '$slug' — generato da scripts/import_menu.php\n";
    $out .= "-- ATTENZIONE: eseguire UNA SOLA VOLTA (il menù del tenant deve essere vuoto).\n";
    $out .= "-- Se @tid risulta NULL lo slug è errato: gli INSERT falliranno (nessun dato sporco).\n\n";
    $out .= "SET NAMES utf8mb4;\n";
    $out .= "SET @tid := (SELECT id FROM tenants WHERE slug = " . $q($slug) . " LIMIT 1);\n\n";
    $sc = 0;
    foreach ($menu as $cat) {
        $out .= "INSERT INTO menu_categories (tenant_id, parent_id, name, description, icon, is_wine, sort_order, is_active)\n";
        $out .= "VALUES (@tid, NULL, " . $q($cat['name']) . ", '', " . $q($cat['icon']) . ", 0, " . ($sc++) . ", 1);\n";
        $out .= "SET @c := LAST_INSERT_ID();\n";
        if (!empty($cat['items'])) {
            $rows = []; $si = 0;
            foreach ($cat['items'] as $it) {
                $alg = empty($it['allergens']) ? 'NULL' : $q(json_encode(array_values($it['allergens']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                $rows[] = "(@tid, @c, " . $q($it['name']) . ", '', " . number_format((float)$it['price'], 2, '.', '') . ", $alg, 1, 0, " . ($si++) . ")";
            }
            $out .= "INSERT INTO menu_items (tenant_id, category_id, name, description, price, allergens, is_available, is_daily_special, sort_order) VALUES\n";
            $out .= implode(",\n", $rows) . ";\n";
        }
        $out .= "\n";
    }
    if (is_string($args['sql'])) { file_put_contents($args['sql'], $out); echo "\n>>> SQL scritto in: {$args['sql']}\n"; }
    else { echo "\n" . $out; }
    exit(0);
}

if (!$commit) {
    echo "\n>>> PREVIEW: nessuna scrittura. Aggiungi --tenant=SLUG --commit per importare.\n";
    exit(0);
}

// ---- import ----
if (!$slug) { fwrite(STDERR, "\n--tenant=SLUG obbligatorio con --commit\n"); exit(3); }
$tenant = (new Tenant())->findBySlug($slug);
if (!$tenant) { fwrite(STDERR, "\nTenant '$slug' non trovato.\n"); exit(2); }
$tid = (int)$tenant['id'];

$catModel = new MenuCategory();
$itemModel = new MenuItem();

$existingItems = $itemModel->findAllByTenant($tid);
if (!empty($existingItems) && !$force) {
    fwrite(STDERR, "\nIl tenant '{$tenant['name']}' ha già " . count($existingItems) . " piatti. Usa --force per procedere comunque (dedup per nome).\n");
    exit(1);
}

// mappe esistenti per dedup
$existingCats = [];
foreach ($catModel->findAllByTenant($tid) as $c) { $existingCats[mb_strtolower($c['name'], 'UTF-8')] = (int)$c['id']; }
$existingItemKeys = [];
foreach ($existingItems as $it) { $existingItemKeys[$it['category_id'] . '|' . mb_strtolower($it['name'], 'UTF-8')] = true; }

$rollback = isset($args['rollback']);
$db = Database::getInstance();
if ($rollback) { $db->beginTransaction(); }

$created = ['cats' => 0, 'items' => 0, 'skipped' => 0];
$sort = 0;
foreach ($menu as $cat) {
    $key = mb_strtolower($cat['name'], 'UTF-8');
    if (isset($existingCats[$key])) {
        $catId = $existingCats[$key];
    } else {
        $catId = $catModel->create($tid, ['name' => $cat['name'], 'icon' => $cat['icon'], 'is_wine' => 0, 'sort_order' => $sort++]);
        $existingCats[$key] = $catId;
        $created['cats']++;
    }
    $isort = 0;
    foreach ($cat['items'] as $it) {
        $ikey = $catId . '|' . mb_strtolower($it['name'], 'UTF-8');
        if (isset($existingItemKeys[$ikey])) { $created['skipped']++; continue; }
        $itemModel->create($tid, [
            'category_id'  => $catId,
            'name'         => $it['name'],
            'description'  => '',
            'price'        => $it['price'],
            'allergens'    => $it['allergens'],
            'is_available' => 1,
            'sort_order'   => $isort++,
        ]);
        $existingItemKeys[$ikey] = true;
        $created['items']++;
    }
}

if ($rollback) {
    $db->rollBack();
    echo "\n=== TEST (ROLLBACK) su '{$tenant['name']}' (#$tid) — nessun dato persistito ===\n";
} else {
    echo "\n=== IMPORT COMPLETATO su '{$tenant['name']}' (#$tid) ===\n";
}
echo "  categorie create: {$created['cats']}\n  piatti creati: {$created['items']}\n  piatti saltati (già presenti): {$created['skipped']}\n";
exit(0);
