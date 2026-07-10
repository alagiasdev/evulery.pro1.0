<?php

namespace App\Services;

use App\Core\Response;

/**
 * Libreria documenti operativi/legali condivisa tra area reseller e admin.
 * Il catalogo vive in config/documents.php (una riga per documento).
 * Il serving replica la logica sicura di Reseller\MaterialsController:
 *  - solo le chiavi presenti nel catalogo sono servibili (no path traversal);
 *  - il file deve risolversi dentro sales/;
 *  - agli HTML si inietta il nonce CSP negli <script> inline.
 */
class DocumentService
{
    public const CATEGORIES = [
        'onboarding' => ['label' => 'Onboarding & attivazione', 'icon' => 'clipboard-check'],
        'legal'      => ['label' => 'Legale & privacy',        'icon' => 'shield-lock'],
    ];

    public static function catalog(): array
    {
        static $cat = null;
        if ($cat === null) {
            $cat = require BASE_PATH . '/config/documents.php';
        }
        return $cat;
    }

    public static function find(string $key): ?array
    {
        return self::catalog()[$key] ?? null;
    }

    /**
     * Documenti raggruppati per categoria, nell'ordine di CATEGORIES.
     * Categorie vuote escluse.
     */
    public static function grouped(): array
    {
        $out = [];
        foreach (array_keys(self::CATEGORIES) as $catKey) {
            $out[$catKey] = [];
        }
        foreach (self::catalog() as $key => $doc) {
            $c = $doc['category'] ?? 'onboarding';
            if (!isset($out[$c])) {
                $out[$c] = [];
            }
            $out[$c][$key] = $doc;
        }
        return array_filter($out, static fn($docs) => !empty($docs));
    }

    /**
     * Serve un documento. $disposition: 'inline' (anteprima) | 'attachment' (download).
     * Gli HTML sono comunque forzati inline (anteprima/stampa dal browser).
     */
    public static function serve(string $key, string $disposition): void
    {
        $entry = self::find($key);
        $relPath = $entry['file'] ?? null;
        if (!$entry || !$relPath) {
            Response::error('Documento non trovato.', 'NOT_FOUND', 404);
            return;
        }

        $real = realpath(BASE_PATH . '/' . $relPath);
        $salesRoot = realpath(BASE_PATH . '/sales');
        // Difensivo: il file deve risolversi dentro sales/.
        if (!$real || !$salesRoot || strncmp($real, $salesRoot . DIRECTORY_SEPARATOR, strlen($salesRoot) + 1) !== 0) {
            Response::error('File non disponibile.', 'NOT_FOUND', 404);
            return;
        }
        if (!is_file($real)) {
            Response::error('File non disponibile.', 'NOT_FOUND', 404);
            return;
        }

        $ext = strtolower(pathinfo($relPath, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'pdf'  => 'application/pdf',
            'html' => 'text/html; charset=UTF-8',
            'md'   => 'text/plain; charset=UTF-8',
            default => 'application/octet-stream',
        };
        if (in_array($ext, ['html', 'md'], true)) {
            $disposition = 'inline';
        }

        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . $disposition . '; filename="' . basename($relPath) . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('X-Content-Type-Options: nosniff');

        if ($ext === 'html') {
            // Inietta il nonce CSP negli <script> inline che non l'hanno gia'.
            $html = file_get_contents($real);
            $nonce = csp_nonce();
            $html = preg_replace(
                '/<script\b(?![^>]*\bnonce=)([^>]*)>/i',
                '<script nonce="' . $nonce . '"$1>',
                $html
            );
            header('Content-Length: ' . strlen($html));
            echo $html;
        } else {
            header('Content-Length: ' . filesize($real));
            readfile($real);
        }
        exit;
    }
}
