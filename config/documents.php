<?php

/**
 * Registro dei DOCUMENTI operativi/legali (distinti dai materiali commerciali).
 * Fonte unica condivisa tra area reseller (/reseller/documents) e admin
 * (/admin/documents). Aggiungere un documento = una riga qui.
 *
 * I file sono serviti da App\Services\DocumentService::serve() con logica sicura
 * (solo le chiavi qui elencate; path risolto dentro sales/ oppure
 * storage/documents/; iniezione nonce CSP negli <script> inline degli HTML).
 *
 * I PDF firmabili vivono in storage/documents/ e si caricano via FTP (fuori dal
 * repo): si aggiornano senza deploy di codice. Se il file non c'è ancora, la
 * card mostra "In preparazione".
 *
 * Campi per documento:
 *   category    chiave categoria (vedi DocumentService::CATEGORIES)
 *   title       titolo mostrato
 *   description descrizione breve
 *   icon        bootstrap-icon (senza prefisso "bi-")
 *   file        path relativo alla root (dentro sales/ o storage/documents/)
 *   downloadable (bool, opz.) mostra anche il pulsante Scarica
 *   draft       (bool, opz.) mostra il badge "Bozza"
 */

return [
    'modulo-attivazione' => [
        'category'     => 'onboarding',
        'title'        => 'Modulo di attivazione',
        'description'  => 'A4 da stampare e far firmare: presa dati del cliente, condizioni contrattuali e informativa privacy (GDPR) in un unico documento.',
        'icon'         => 'file-earmark-text',
        // PDF caricato via FTP in storage/documents/ (unione modulo + privacy).
        'file'         => 'storage/documents/evulery-modulo-attivazione.pdf',
        'downloadable' => true,
    ],

    'messaggi-attivazione' => [
        'category'     => 'onboarding',
        'title'        => 'Messaggi di attivazione',
        'description'  => 'Testi pronti (email + WhatsApp) per consegnare le credenziali al cliente. Con pulsante "Copia".',
        'icon'         => 'chat-dots',
        'file'         => 'sales/kit-benvenuto-credenziali.html',
    ],
];
