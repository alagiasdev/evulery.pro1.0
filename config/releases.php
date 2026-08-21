<?php
/**
 * Registro "Novità" (release notes) mostrate nella sezione Novità di dashboard
 * e area reseller. NESSUN CMS: aggiungi una voce qui in cima e appare subito.
 *
 * Ogni voce:
 *   date     'Y-m-d'   (l'ordine di visualizzazione è per data DESC, calcolato a runtime)
 *   category chiave in 'categories' (colore + icona + etichetta)
 *   title    breve, benefit-first (parla al pubblico, non alla tecnica)
 *   desc     1-2 frasi
 *   audience array: 'owner' (ristoratore) e/o 'reseller'
 *
 * NB: niente numeri di versione (1.x.x) in vetrina — per il ristoratore/reseller
 * contano date + nomi funzione, non il semver.
 */
return [
    'categories' => [
        'prenotazioni' => ['label' => 'Prenotazioni', 'color' => 'green',  'icon' => 'calendar-check', 'emoji' => '📅'],
        'clienti'      => ['label' => 'Clienti',      'color' => 'blue',   'icon' => 'people',         'emoji' => '👥'],
        'widget'       => ['label' => 'Widget',       'color' => 'amber',  'icon' => 'window',         'emoji' => '✨'],
        'menu'         => ['label' => 'Menù',         'color' => 'violet', 'icon' => 'book',           'emoji' => '📖'],
    ],

    'releases' => [
        [
            'date'     => '2026-08-21',
            'category' => 'widget',
            'title'    => 'Il cliente non trova più “tutto esaurito” a vuoto',
            'desc'     => 'Quando una data è al completo, il widget propone il primo posto disponibile con un tap — invece di un vicolo cieco. Un “no” trasformato in una prenotazione.',
            'audience' => ['owner', 'reseller'],
        ],
        [
            'date'     => '2026-08-21',
            'category' => 'prenotazioni',
            'title'    => 'Chiudi le prenotazioni online quando sei pieno',
            'desc'     => 'Sei al completo per una sera o un evento? Ferma le prenotazioni online di un giorno o di un solo servizio (anche per più giorni di fila), senza chiudere il locale. Riapri con un clic.',
            'audience' => ['owner', 'reseller'],
        ],
        [
            'date'     => '2026-08-20',
            'category' => 'prenotazioni',
            'title'    => 'Prenotazione al volo, col solo nome',
            'desc'     => 'Aggiungi una prenotazione in un attimo: bastano nome, coperti e orario. Niente telefono o email obbligatori — perfetto per il cliente al telefono o il walk-in.',
            'audience' => ['owner', 'reseller'],
        ],
        [
            'date'     => '2026-08-20',
            'category' => 'clienti',
            'title'    => 'Elenco clienti più pulito, scheda modificabile',
            'desc'     => 'I nomi “al volo” senza contatto non intasano più l’elenco. E ora modifichi i dati del cliente (telefono, email…) direttamente dalla sua scheda.',
            'audience' => ['owner', 'reseller'],
        ],
    ],
];
