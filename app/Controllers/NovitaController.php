<?php

namespace App\Controllers;

use App\Core\Request;

/**
 * Sezione "Novità" (release notes). Stessa lista (config/releases.php) per due
 * vetrine: dashboard ristoratore e area reseller, filtrata per pubblico.
 * Il pallino "novità" sul menu si spegne visitando la pagina (cookie per-pubblico).
 */
class NovitaController
{
    public function owner(Request $request): void
    {
        $this->markSeen('owner');
        view('novita', [
            'title'      => 'Novità',
            'activeMenu' => 'novita',
            'context'    => 'owner',
            'releases'   => releases_for('owner'),
            'categories' => releases_config()['categories'] ?? [],
        ], 'dashboard');
    }

    public function reseller(Request $request): void
    {
        $this->markSeen('reseller');
        view('novita', [
            'title'      => 'Novità',
            'activeMenu' => 'reseller-novita',
            'context'    => 'reseller',
            'releases'   => releases_for('reseller'),
            'categories' => releases_config()['categories'] ?? [],
        ], 'reseller');
    }

    /** Segna le novità come "viste": cookie (per pubblico) = data più recente. */
    private function markSeen(string $audience): void
    {
        $latest = releases_latest_date($audience);
        if ($latest === null || headers_sent()) {
            return;
        }
        $name = 'novita_seen_' . $audience;
        setcookie($name, $latest, [
            'expires'  => time() + 60 * 60 * 24 * 365,
            'path'     => '/',
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
        $_COOKIE[$name] = $latest; // coerenza nella stessa richiesta (dot spento subito)
    }
}
