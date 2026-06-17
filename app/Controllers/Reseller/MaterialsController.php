<?php

namespace App\Controllers\Reseller;

use App\Core\Request;
use App\Core\Response;

/**
 * Materiali commerciali scaricabili dal reseller.
 *
 * Whitelist statica: solo i file esplicitamente elencati possono essere
 * scaricati. Path-traversal impossibile per design (la chiave del download
 * non e' il path, e' la chiave nel catalogo).
 */
class MaterialsController
{
    private const CATALOG = [
        // 1. Da presentare al cliente — anteprima HTML + download PDF
        'onepager' => [
            'category'      => 'client',
            'title'         => 'One-pager',
            'description'   => 'Riassunto di 1 pagina. Da inviare via email dopo il primo contatto.',
            'icon'          => 'file-earmark-text',
            'preview_file'  => 'sales/onepager.html',
            // 'download_file' rimosso: il PDF e disallineato dall'HTML aggiornato.
            // Ripristinare 'download_file' => 'sales/onepager.pdf' dopo il ri-export.
        ],
        'pitch-deck' => [
            'category'      => 'client',
            'title'         => 'Pitch deck',
            'description'   => 'Presentazione completa: problema, soluzione, ROI, prezzi. Usalo in demo.',
            'icon'          => 'file-earmark-slides',
            'preview_file'  => 'sales/pitch-deck.html',
            // 'download_file' rimosso: il PDF e disallineato dall'HTML aggiornato.
            // Ripristinare 'download_file' => 'sales/pitch-deck.pdf' dopo il ri-export.
        ],
        'faq-ristoratore' => [
            'category'    => 'client',
            'title'       => 'FAQ ristoratore',
            'description' => 'Le 15 domande più frequenti su contratti, migrazione, dati, supporto. Invialo dopo la demo ai clienti tiepidi che dicono "ci devo pensare".',
            'icon'        => 'patch-question',
            'file'        => 'sales/faq-ristoratore.html',
        ],

        // 2. Strumenti operativi (colloquio e demo)
        'playbook-primo-incontro' => [
            'category'    => 'tools',
            'title'       => 'Playbook primo incontro',
            'description' => 'Come condurre il primo colloquio: cosa preparare, simulazione completa passo-passo, le obiezioni, gli errori da evitare e il follow-up. Studialo per arrivare pronto.',
            'icon'        => 'journal-text',
            'file'        => 'sales/playbook-primo-incontro.html',
        ],
        'traccia-primo-appuntamento' => [
            'category'    => 'tools',
            'title'       => 'Traccia primo appuntamento',
            'description' => 'Versione rapida del primo incontro: le 3 leve per spiegare Evulery, come far parlare il cliente e agganciare la demo.',
            'icon'        => 'signpost-2',
            'file'        => 'sales/traccia-primo-appuntamento.html',
        ],
        'demo-script' => [
            'category'    => 'tools',
            'title'       => 'Demo script',
            'description' => 'Copilota interattivo per la demo: 9 fasi, cronometro, checklist persistenti. Tienilo aperto durante la chiamata.',
            'icon'        => 'mic',
            'file'        => 'sales/demo-script.html',
        ],
        'demo-guidata' => [
            'category'    => 'tools',
            'title'       => 'Demo guidata (5 schermate)',
            'description' => 'Versione operativa del Demo script: solo le 5 schermate da mostrare + gli approfondimenti on-demand, con cronometro. Tienila aperta mentre condividi lo schermo.',
            'icon'        => 'display',
            'file'        => 'sales/demo-guidata.html',
        ],
        'roi-calculator' => [
            'category'    => 'tools',
            'title'       => 'ROI Calculator',
            'description' => 'Inserisci i dati del prospect (coperti, scontrino, costi attuali, no-show) e ottieni risparmio, recupero no-show e payback in tempo reale. Riepilogo pronto da copiare in email.',
            'icon'        => 'calculator',
            'file'        => 'sales/roi-calculator.html',
        ],
        'battlecard' => [
            'category'    => 'tools',
            'title'       => 'Battlecard piattaforme',
            'description' => 'Confronto Evulery vs i 3 principali competitor. Tienila aperta in demo per gestire le obiezioni più comuni.',
            'icon'        => 'shield-shaded',
            'file'        => 'sales/battlecard-piattaforme.html',
        ],
        'onboarding-checklist' => [
            'category'    => 'tools',
            'title'       => 'Checklist onboarding',
            'description' => 'La sequenza per attivare un nuovo cliente: dal primo accesso al widget online. Spunta le voci durante l\'affiancamento — le spunte si salvano.',
            'icon'        => 'clipboard-check',
            'file'        => 'sales/onboarding-checklist.html',
        ],

        // 3. Opzionali — per outbound a freddo
        'email-outbound' => [
            'category'    => 'outbound',
            'title'       => 'Email outbound',
            'description' => 'Template di primo contatto e follow-up per email a freddo. Personalizza e invia.',
            'icon'        => 'envelope-paper',
            'file'        => 'sales/email-outbound-pack.html',
        ],
        'outbound-targeting' => [
            'category'    => 'outbound',
            'title'       => 'Guida targeting',
            'description' => 'Come costruire una lista qualificata di ristoranti nella tua zona da contattare.',
            'icon'        => 'crosshair',
            'file'        => 'sales/outbound-targeting-guide.html',
        ],
    ];

    public function index(Request $request): void
    {
        $catalog = self::CATALOG;

        view('reseller/materials/index', [
            'title'      => 'Materiali commerciali',
            'activeMenu' => 'reseller-materials',
            'forClient'  => array_filter($catalog, fn($m) => $m['category'] === 'client'),
            'forTools'   => array_filter($catalog, fn($m) => $m['category'] === 'tools'),
            'forOutbound'=> array_filter($catalog, fn($m) => $m['category'] === 'outbound'),
        ], 'reseller');
    }

    /**
     * Scarica il materiale (PDF se disponibile, altrimenti file principale).
     */
    public function download(Request $request): void
    {
        $entry = self::CATALOG[(string)$request->param('key')] ?? null;
        if (!$entry) {
            Response::error('Materiale non trovato.', 'NOT_FOUND', 404);
            return;
        }
        $path = $entry['download_file'] ?? $entry['file'] ?? null;
        if (!$path) {
            Response::error('Download non disponibile per questo materiale.', 'NOT_FOUND', 404);
            return;
        }
        $this->serveFile($path, 'attachment');
    }

    /**
     * Anteprima inline del materiale (HTML preferito).
     */
    public function preview(Request $request): void
    {
        $entry = self::CATALOG[(string)$request->param('key')] ?? null;
        if (!$entry) {
            Response::error('Materiale non trovato.', 'NOT_FOUND', 404);
            return;
        }
        $path = $entry['preview_file'] ?? $entry['file'] ?? null;
        if (!$path) {
            Response::error('Anteprima non disponibile per questo materiale.', 'NOT_FOUND', 404);
            return;
        }
        $this->serveFile($path, 'inline');
    }

    private function serveFile(string $relPath, string $disposition): void
    {
        $absPath = BASE_PATH . '/' . $relPath;
        $real = realpath($absPath);
        $salesRoot = realpath(BASE_PATH . '/sales');
        // Difensivo: il file deve risolversi dentro la cartella sales/.
        // Non basta la whitelist CATALOG: se domani entra una entry malformata
        // con '../' non deve poter uscire dalla directory consentita.
        if (!$real || !$salesRoot || strncmp($real, $salesRoot . DIRECTORY_SEPARATOR, strlen($salesRoot) + 1) !== 0) {
            Response::error('File non disponibile.', 'NOT_FOUND', 404);
            return;
        }
        if (!is_file($real)) {
            Response::error('File non disponibile.', 'NOT_FOUND', 404);
            return;
        }
        $absPath = $real;

        $ext = strtolower(pathinfo($relPath, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'pdf'  => 'application/pdf',
            'html' => 'text/html; charset=UTF-8',
            'md'   => 'text/plain; charset=UTF-8',
            default => 'application/octet-stream',
        };

        // HTML/MD: forza sempre inline (no download)
        if (in_array($ext, ['html', 'md'], true)) {
            $disposition = 'inline';
        }

        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . $disposition . '; filename="' . basename($relPath) . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('X-Content-Type-Options: nosniff');

        // Per gli HTML iniettiamo il CSP nonce in ogni <script> inline:
        // l'app emette CSP con `script-src 'self' 'nonce-X'` (no 'unsafe-inline'),
        // quindi senza nonce il browser blocca silenziosamente gli script
        // del materiale (es. timer/checklist in demo-script.html).
        if ($ext === 'html') {
            $html = file_get_contents($absPath);
            $nonce = csp_nonce();
            // Aggiunge nonce ai tag <script> che ancora non l'hanno
            $html = preg_replace(
                '/<script\b(?![^>]*\bnonce=)([^>]*)>/i',
                '<script nonce="' . $nonce . '"$1>',
                $html
            );
            header('Content-Length: ' . strlen($html));
            echo $html;
        } else {
            header('Content-Length: ' . filesize($absPath));
            readfile($absPath);
        }
        exit;
    }
}
