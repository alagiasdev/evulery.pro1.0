<?php

/**
 * Registro dei DOCUMENTI operativi/legali (distinti dai materiali commerciali).
 * Fonte unica condivisa tra area reseller (/reseller/documents) e admin
 * (/admin/documents). Aggiungere un documento = una riga qui.
 *
 * I file sono serviti da App\Services\DocumentService::serve() con la stessa
 * logica sicura dei materiali (whitelist implicita = solo le chiavi qui elencate,
 * path risolto dentro sales/, iniezione nonce CSP negli <script> inline).
 *
 * Campi per documento:
 *   category    chiave categoria (vedi DocumentService::CATEGORIES)
 *   title       titolo mostrato
 *   description descrizione breve
 *   icon        bootstrap-icon (senza prefisso "bi-")
 *   file        path relativo alla root del progetto (dev'essere dentro sales/)
 *   downloadable (bool, opz.) mostra anche il pulsante Stampa/Scarica
 *   draft       (bool, opz.) mostra il badge "BOZZA"
 */

return [
    'modulo-attivazione' => [
        'category'     => 'onboarding',
        'title'        => 'Modulo di attivazione',
        'description'  => 'A4 fronte-retro: presa dati del cliente + condizioni contrattuali. Da stampare e far firmare.',
        'icon'         => 'file-earmark-text',
        // PDF caricato via FTP in storage/documents/ (sorgente HTML: sales/modulo-attivazione.html).
        'file'         => 'storage/documents/modulo-attivazione.pdf',
        'downloadable' => true,
    ],

    'messaggi-attivazione' => [
        'category'     => 'onboarding',
        'title'        => 'Messaggi di attivazione',
        'description'  => 'Testi pronti (email + WhatsApp) per consegnare le credenziali al cliente. Con pulsante "Copia".',
        'icon'         => 'chat-dots',
        'file'         => 'sales/kit-benvenuto-credenziali.html',
    ],

    'informativa-privacy' => [
        'category'     => 'legal',
        'title'        => 'Informativa privacy (GDPR)',
        'description'  => 'Informativa sul trattamento dei dati da consegnare/far firmare al cliente. Bozza da validare dal legale.',
        'icon'         => 'shield-check',
        // PDF caricato via FTP in storage/documents/ (sorgente HTML: sales/informativa-privacy.html).
        'file'         => 'storage/documents/informativa-privacy.pdf',
        'downloadable' => true,
        'draft'        => true,
    ],
];
