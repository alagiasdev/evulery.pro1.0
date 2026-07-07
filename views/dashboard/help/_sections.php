<?php
/**
 * Help guide sections — single source of truth for metadata + body HTML.
 * Used by both index.php (card grid) and detail.php (article view).
 *
 * Structure:
 *   slug => [
 *     title, subtitle, icon (bi-*), color (hex),
 *     category (primi|configurazione|operativita|avanzati|supporto),
 *     count_label (e.g. "1 articolo", "4 step"),
 *     keywords (for search, lowercase),
 *     read_time (approx. minutes),
 *     body (HTML content)
 *   ]
 */

return [

    // ══════════ PRIMI PASSI ══════════

    'primi-passi' => [
        'title'       => 'Come iniziare',
        'subtitle'    => 'I primi passaggi per usare Evulery',
        'icon'        => 'bi-rocket-takeoff',
        'color'       => '#00844A',
        'category'    => 'primi',
        'count_label' => '4 step',
        'keywords'    => 'come iniziare primi passi configurazione widget dashboard attivazione',
        'read_time'   => 3,
        'body' => <<<'HTML'
<p>Benvenuto! Dopo l&rsquo;attivazione del tuo account, ti consigliamo di seguire questi passaggi nell&rsquo;ordine:</p>
<div class="hg-step"><div class="hg-step-num">1</div><div class="hg-step-content"><strong>Verifica le impostazioni generali</strong> &mdash; Vai in <em>Impostazioni &rarr; Generali</em> e controlla nome ristorante, indirizzo, numero di telefono, logo e step di prenotazione.</div></div>
<div class="hg-step"><div class="hg-step-num">2</div><div class="hg-step-content"><strong>Configura orari e coperti</strong> &mdash; In <em>Impostazioni &rarr; Orari e Coperti</em> imposta quanti coperti accetti per ogni fascia oraria e giorno della settimana.</div></div>
<div class="hg-step"><div class="hg-step-num">3</div><div class="hg-step-content"><strong>Attiva il widget</strong> &mdash; Copia il codice embed e incollalo nel tuo sito, nella bio Instagram o condividi il link diretto sui social.</div></div>
<div class="hg-step"><div class="hg-step-num">4</div><div class="hg-step-content"><strong>Gestisci le prenotazioni</strong> &mdash; Apri la <em>Dashboard</em> per vedere le prenotazioni del giorno, segnare gli arrivi e gestire i no-show.</div></div>
<div class="hg-tip"><strong><i class="bi bi-lightbulb me-1"></i>Consiglio:</strong> abilita le notifiche push nel browser per non perdere nessuna prenotazione, anche quando la dashboard &egrave; chiusa.</div>
HTML
    ],

    'dashboard' => [
        'title'       => 'La dashboard',
        'subtitle'    => 'La panoramica della tua giornata',
        'icon'        => 'bi-speedometer2',
        'color'       => '#1565C0',
        'category'    => 'primi',
        'count_label' => '1 articolo',
        'keywords'    => 'dashboard panoramica kpi coperti arrivi noshow notifiche',
        'read_time'   => 2,
        'body' => <<<'HTML'
<p>La dashboard &egrave; la tua pagina operativa. Ti mostra in tempo reale tutto quello che succede nel ristorante.</p>
<p>Cosa trovi in dashboard:</p>
<ul>
    <li><strong>KPI del giorno</strong>: prenotazioni totali, coperti, arrivi, no-show</li>
    <li><strong>Lista prenotazioni</strong>: tutti gli appuntamenti del giorno, divisi per turno</li>
    <li><strong>Azioni rapide</strong>: segna arrivato, no-show, cancellato</li>
    <li><strong>Campanella notifiche</strong>: in alto a destra, avvisa quando arriva una nuova prenotazione</li>
</ul>
HTML
    ],

    // ══════════ CONFIGURAZIONE ══════════

    'orari' => [
        'title'       => 'Orari e coperti',
        'subtitle'    => 'Imposta quanti coperti accetti',
        'icon'        => 'bi-clock',
        'color'       => '#E65100',
        'category'    => 'configurazione',
        'count_label' => '1 articolo',
        'keywords'    => 'orari coperti fascia oraria step slot compilazione',
        'read_time'   => 3,
        'body' => <<<'HTML'
<p>In <em>Impostazioni &rarr; Orari e Coperti</em> imposti quanti coperti accetti per ogni fascia oraria e giorno della settimana.</p>
<ol>
    <li>Scegli lo <strong>step di prenotazione</strong> in <em>Impostazioni Generali</em> (15, 30 o 60 minuti). Questo determina ogni quanto tempo vengono proposti gli slot nel widget.</li>
    <li>Torna in <em>Orari e Coperti</em> e inserisci il numero massimo di coperti per ogni slot orario.</li>
    <li>Lascia <strong>0</strong> nelle fasce dove sei chiuso o non accetti prenotazioni.</li>
    <li>Usa il pulsante <strong>Compila tutti</strong> (20, 30, 40, 50) per riempire velocemente l&rsquo;intera griglia.</li>
</ol>
<div class="hg-info"><strong><i class="bi bi-info-circle me-1"></i>Nota:</strong> le fasce orarie devono essere allineate allo step di prenotazione. Se hai impostato step 60 minuti, puoi usare solo orari pieni (12:00, 13:00) e non mezz&rsquo;ora (12:30).</div>
HTML
    ],

    'categorie' => [
        'title'       => 'Categorie pasto',
        'subtitle'    => 'Raggruppa gli orari nel widget',
        'icon'        => 'bi-tags',
        'color'       => '#7B1FA2',
        'category'    => 'configurazione',
        'count_label' => '1 articolo',
        'keywords'    => 'categorie pasto brunch pranzo aperitivo cena after dinner widget raggruppamento',
        'read_time'   => 2,
        'body' => <<<'HTML'
<p>Le categorie pasto (Brunch, Pranzo, Aperitivo, Cena, After Dinner) <strong>raggruppano gli orari nel widget di prenotazione</strong> per rendere pi&ugrave; chiara la scelta al cliente.</p>
<p>Puoi attivare/disattivare le singole categorie, modificare orari di inizio e fine, e riordinarle.</p>
<div class="hg-info"><strong><i class="bi bi-info-circle me-1"></i>Esempio:</strong> se disattivi "Pranzo", i relativi slot orari (12:00-15:00) non compariranno pi&ugrave; nel widget, anche se hai coperti disponibili in quella fascia.</div>
HTML
    ],

    'chiusure' => [
        'title'       => 'Chiusure e ferie',
        'subtitle'    => 'Giorni di chiusura e ferie',
        'icon'        => 'bi-calendar-x',
        'color'       => '#C62828',
        'category'    => 'configurazione',
        'count_label' => '2 articoli',
        'keywords'    => 'chiusure ferie giorni riposo eventi privati straordinaria imprevisto emergenza guasto allagamento sospendi annulla riapri sospesa',
        'read_time'   => 3,
        'body' => <<<'HTML'
<p>In <em>Impostazioni &rarr; Chiusure</em> puoi bloccare specifici giorni o periodi (ferie, riposo settimanale, eventi privati).</p>
<ul>
    <li><strong>Chiusura singola</strong>: una data specifica (es. Natale)</li>
    <li><strong>Chiusura periodica</strong>: un intervallo di date (es. ferie estive)</li>
    <li><strong>Chiusura parziale</strong>: solo una fascia oraria di un giorno</li>
</ul>
<p>Nei giorni di chiusura il widget non accetta prenotazioni e mostra un messaggio al cliente.</p>

<h4>Chiusura straordinaria (imprevisti)</h4>
<p>Diversa dalle chiusure programmate: serve per <strong>emergenze improvvise</strong> (guasto, allagamento, problema tecnico) quando hai gi&agrave; prenotazioni prese. La trovi nella stessa pagina <em>Impostazioni &rarr; Chiusure</em>, nella card rossa &ldquo;Imprevisto improvviso?&rdquo;.</p>
<p>Scegli la durata (giorno intero o una fascia oraria) e cosa fare delle prenotazioni gi&agrave; presenti:</p>
<ul>
    <li><strong>Sospendi</strong> (recuperabile): le prenotazioni restano e continuano a occupare i coperti. Se l&rsquo;emergenza rientra puoi <strong>riaprire</strong> e recuperarle.</li>
    <li><strong>Annulla</strong>: annulla le prenotazioni del periodo e avvisa i clienti via email con il messaggio che scrivi tu.</li>
</ul>
<p>Prima di confermare vedi sempre l&rsquo;elenco delle <strong>prenotazioni interessate</strong>. Mentre una chiusura straordinaria &egrave; attiva, un banner ti permette di <strong>riaprire</strong> rapidamente appena torni operativo.</p>
HTML
    ],

    // ══════════ OPERATIVITÀ ══════════

    'prenotazioni' => [
        'title'       => 'Gestione prenotazioni',
        'subtitle'    => 'Dal widget all&rsquo;arrivo al tavolo',
        'icon'        => 'bi-calendar-check',
        'color'       => '#00844A',
        'category'    => 'operativita',
        'count_label' => '1 articolo',
        'keywords'    => 'prenotazioni stati confermata pending arrivata noshow cancellata creazione manuale',
        'read_time'   => 3,
        'body' => <<<'HTML'
<p>Ogni prenotazione ha uno stato che indica a che punto siamo:</p>
<ul>
    <li><strong>Confermata</strong>: accettata automaticamente (se non richiede caparra)</li>
    <li><strong>In attesa</strong>: aspetta pagamento caparra o conferma manuale</li>
    <li><strong>Arrivata</strong>: il cliente &egrave; al ristorante (segnalo manualmente)</li>
    <li><strong>No-show</strong>: il cliente non si &egrave; presentato</li>
    <li><strong>Cancellata</strong>: annullata dal cliente o da te</li>
</ul>
<p>Puoi creare prenotazioni manualmente dal pulsante <strong>Nuova Prenotazione</strong> in alto a destra, o modificarle cliccando sulla riga.</p>
<div class="hg-tip"><strong><i class="bi bi-lightbulb me-1"></i>Importante:</strong> segna sempre gli <strong>arrivati</strong> al tavolo. Questo attiva il flusso di richiesta recensione post-visita.</div>
HTML
    ],

    'widget' => [
        'title'       => 'Widget online',
        'subtitle'    => 'Come integrarlo nel tuo sito',
        'icon'        => 'bi-globe2',
        'color'       => '#42A5F5',
        'category'    => 'operativita',
        'count_label' => '1 articolo',
        'keywords'    => 'widget embed iframe link qr code sito wordpress instagram facebook',
        'read_time'   => 2,
        'body' => <<<'HTML'
<p>Il widget di prenotazione pu&ograve; essere utilizzato in tre modi:</p>
<ol>
    <li><strong>Link diretto</strong>: condividilo su WhatsApp, Instagram bio, email</li>
    <li><strong>Embed iframe</strong>: incollalo nel tuo sito web (WordPress, Wix, Squarespace, HTML)</li>
    <li><strong>QR code</strong>: stampalo su menu, biglietti da visita, vetrina</li>
</ol>
<p>Trovi tutti gli strumenti in <em>Impostazioni &rarr; Widget</em>.</p>
HTML
    ],

    'clienti' => [
        'title'       => 'CRM clienti',
        'subtitle'    => 'Il tuo database clienti',
        'icon'        => 'bi-people',
        'color'       => '#00897B',
        'category'    => 'operativita',
        'count_label' => '1 articolo',
        'keywords'    => 'clienti crm segmentazione nuovo occasionale abituale vip import csv thefork',
        'read_time'   => 2,
        'body' => <<<'HTML'
<p>Ogni volta che un cliente prenota, Evulery crea automaticamente il suo profilo nel CRM.</p>
<p>I clienti sono segmentati automaticamente in 4 livelli:</p>
<ul>
    <li><strong>Nuovo</strong>: meno di 2 visite</li>
    <li><strong>Occasionale</strong>: 2-3 visite</li>
    <li><strong>Abituale</strong>: 4-9 visite</li>
    <li><strong>VIP</strong>: 10+ visite</li>
</ul>
<p>Puoi <strong>importare clienti da CSV</strong> (es. da TheFork, Quandoo o altro) dalla sezione <em>Clienti &rarr; Importa</em>.</p>
HTML
    ],

    'menu' => [
        'title'       => 'Menu digitale',
        'subtitle'    => 'Il tuo menu online con QR code',
        'icon'        => 'bi-book',
        'color'       => '#FB8C00',
        'category'    => 'operativita',
        'count_label' => '1 articolo',
        'keywords'    => 'menu digitale categorie piatti allergeni qr code vegano vegetariano',
        'read_time'   => 2,
        'body' => <<<'HTML'
<p>Dalla sezione <em>Menu</em> puoi creare e aggiornare il menu del tuo ristorante. Il menu &egrave; organizzato in categorie (Antipasti, Primi, Secondi, ecc.) e piatti.</p>
<p>Per ogni piatto puoi inserire:</p>
<ul>
    <li>Nome, descrizione, prezzo</li>
    <li>Foto</li>
    <li>Allergeni (14 categorie UE)</li>
    <li>Flag: vegano, vegetariano, piccante, senza glutine</li>
</ul>
<p>Il menu &egrave; accessibile via URL pubblico o QR code da stampare sui tavoli.</p>
HTML
    ],

    'promozioni' => [
        'title'       => 'Promozioni',
        'subtitle'    => 'Sconti per riempire le fasce vuote',
        'icon'        => 'bi-percent',
        'color'       => '#E65100',
        'category'    => 'operativita',
        'count_label' => '1 articolo',
        'keywords'    => 'promozioni sconti ricorrenti fascia oraria data specifica widget ordini',
        'read_time'   => 2,
        'body' => <<<'HTML'
<p>Crea promozioni mirate per incentivare prenotazioni nelle fasce orarie pi&ugrave; deboli. Esempio: "Marted&igrave; -20% a cena".</p>
<p>Le promozioni possono essere:</p>
<ul>
    <li><strong>Ricorrenti</strong>: si applicano su specifici giorni/orari ogni settimana</li>
    <li><strong>Data specifica</strong>: evento singolo o periodo limitato</li>
</ul>
<p>Puoi scegliere se applicare lo sconto alle <strong>prenotazioni</strong>, agli <strong>ordini online</strong> o a entrambi.</p>
HTML
    ],

    'caparra' => [
        'title'       => 'Caparra',
        'subtitle'    => 'Riduci i no-show con 3 modalit&agrave;',
        'icon'        => 'bi-shield-lock',
        'color'       => '#5E35B1',
        'category'    => 'operativita',
        'count_label' => '1 articolo',
        'keywords'    => 'caparra deposito noshow stripe link pagamento informativa bonifico carta garanzia preautorizzazione',
        'read_time'   => 3,
        'body' => <<<'HTML'
<p>La caparra &egrave; la soluzione pi&ugrave; efficace per ridurre i no-show. Evulery offre 4 modalit&agrave;:</p>
<ol>
    <li><strong>Informativa</strong>: comunichi l&rsquo;importo al cliente, che paga di persona o con bonifico</li>
    <li><strong>Link di pagamento</strong>: il cliente paga con il metodo che preferisci (Satispay, PayPal, ecc.)</li>
    <li><strong>Stripe</strong>: pagamento automatico con carta di credito durante la prenotazione</li>
    <li><strong>Carta a garanzia</strong>: blocco preautorizzato sulla carta; addebiti la penale solo in caso di no-show</li>
</ol>
<p>Puoi configurare <strong>quando richiedere la caparra</strong> (es. solo per tavoli &gt;4 persone, solo nel weekend, solo per eventi specifici).</p>
<div class="hg-warn"><strong><i class="bi bi-exclamation-triangle me-1"></i>Attenzione:</strong> una prenotazione con caparra Stripe resta <em>in attesa</em> fino al pagamento. Se il cliente non paga entro 30 minuti, viene cancellata automaticamente.</div>
HTML
    ],

    // ══════════ SERVIZI AVANZATI ══════════

    'tavoli' => [
        'title'       => 'Gestione Tavoli e Sala',
        'subtitle'    => 'Sala virtuale, mappa e auto-assegnazione',
        'icon'        => 'bi-grid-3x3',
        'color'       => '#0c8599',
        'category'    => 'avanzati',
        'count_label' => '1 articolo',
        'keywords'    => 'tavoli sala mappa auto-assegnazione assegnazione combinazioni aree posti turni operativa setup riassegnazione capacita elastica min max posti minimi massimi jolly solo manuale bloccato motivo lucchetto archiviato disattivato stato confermato arrivato palette colori barra servizio selettore fascia categoria pasto coperti colore riempimento indicatore ora prossima prenotazione badge durata turni avanzati permanenza',
        'read_time'   => 7,
        'body' => <<<'HTML'
<p>La <strong>Gestione Tavoli</strong> (piano Enterprise) ti d&agrave; una sala virtuale: definisci i tuoi tavoli, il sistema assegna in automatico chi prenota e tu vedi lo stato della sala in tempo reale.</p>

<div class="hg-step"><div class="hg-step-num">1</div><div class="hg-step-content"><strong>Configura i tavoli</strong> &mdash; In <em>Impostazioni &rarr; Tavoli</em> usa <strong>Nuovo tavolo</strong> per aggiungere ogni tavolo con nome, posti, area (es. Sala Interna, Esterno), forma e i tavoli con cui pu&ograve; essere combinato per i gruppi grandi. L&rsquo;ordine della lista &egrave; la <strong>priorit&agrave;</strong> di assegnazione: trascina per riordinare, i tavoli pi&ugrave; in alto si riempiono per primi.</div></div>
<div class="hg-step"><div class="hg-step-num">2</div><div class="hg-step-content"><strong>Attiva l&rsquo;auto-assegnazione</strong> &mdash; Sempre in <em>Impostazioni &rarr; Tavoli</em>, accendi l&rsquo;interruttore <strong>Assegnazione automatica</strong>: a ogni nuova prenotazione il sistema sceglie il primo tavolo libero adatto. Imposta anche il <strong>buffer di pulizia</strong>, i minuti liberi tra due turni sullo stesso tavolo.</div></div>
<div class="hg-step"><div class="hg-step-num">3</div><div class="hg-step-content"><strong>Disponi la sala</strong> &mdash; Da <em>Impostazioni &rarr; Tavoli &rarr; Mappa sala</em>, in modalit&agrave; <strong>Setup</strong>, trascina i tavoli per riprodurre la disposizione reale del locale. Ricordati di salvare le posizioni.</div></div>
<div class="hg-step"><div class="hg-step-num">4</div><div class="hg-step-content"><strong>Usa la Sala durante il servizio</strong> &mdash; La voce <strong>Sala</strong> nel menu laterale apre la vista operativa in tempo reale.</div></div>

<h4>Capacit&agrave; elastica (posti minimi e massimi)</h4>
<p>Ogni tavolo ha <strong>posti minimi</strong> e <strong>posti massimi</strong>. Un tavolo viene proposto solo se il numero di coperti della prenotazione rientra in questo intervallo. Esempio: un tavolo 2&ndash;4 posti accetta gruppi da 2, 3 o 4 persone; un gruppo da 1 o da 5 cerca un altro tavolo.</p>
<p>Quando ci sono pi&ugrave; tavoli candidati, il sistema sceglie il <strong>pi&ugrave; calzante</strong> (fit primario): preferisce il tavolo che lascia meno posti vuoti. Esempio: per un gruppo da 2, prima un tavolo da 2 posti, poi uno 2&ndash;4, poi uno 4&ndash;6.</p>
<div class="hg-tip"><strong><i class="bi bi-lightbulb me-1"></i>Quando usare il range:</strong> tavolo &ldquo;rotondo da 6&rdquo; che mettiamo anche con 4 persone &rarr; range 4&ndash;6. Tavolo solo per coppie &rarr; range 2&ndash;2. Lasciare il range stretto evita di sprecare un tavolone con un gruppo piccolo quando hai tavoli pi&ugrave; piccoli liberi.</div>

<h4>Stati del tavolo: disponibile, jolly, bloccato, archiviato</h4>
<p>Ogni tavolo ha due interruttori indipendenti nella modale di modifica, sotto <em>Disponibilit&agrave;</em>:</p>
<ul>
    <li><strong>Disponibile per prenotazioni online</strong> &mdash; se <strong>disattivato</strong>, il tavolo non viene assegnato in automatico dal widget pubblico. Resta per&ograve; assegnabile manualmente dalla dashboard. &Egrave; il classico <strong>tavolo jolly</strong>: lo tieni di scorta per gestire imprevisti o walk-in. Riconoscibile sulla mappa dal <strong>bordo tratteggiato ambra</strong>.</li>
    <li><strong>Blocca tavolo</strong> &mdash; se <strong>attivato</strong>, il tavolo &egrave; fuori uso temporaneo (es. tavolo rotto, manutenzione, mancano sedie). Non riceve auto-assegnazioni n&eacute; assegnazioni manuali. Riconoscibile sulla mappa dal <strong>lucchetto rosso</strong>. Puoi indicare il <strong>motivo</strong>; se lo lasci vuoto compare in automatico &ldquo;Bloccato dal DD/MM/YYYY&rdquo;.</li>
</ul>
<p>Per il blocco c&rsquo;&egrave; anche un <strong>toggle rapido nella lista tavoli</strong>: un click blocca/sblocca il tavolo senza aprire la modale.</p>

<p>I tavoli <strong>archiviati</strong> sono quelli che non usi pi&ugrave;: vengono nascosti dalla mappa e dalla lista principale, ma restano nello storico (sezione &ldquo;Archiviati&rdquo; in fondo alla pagina). Le prenotazioni vecchie continuano a mostrare il loro tavolo, niente dati persi. Per archiviare un tavolo apri la sua modale e usa il pulsante <strong>Archivia</strong>; lo puoi riattivare in qualsiasi momento.</p>

<div class="hg-info"><strong><i class="bi bi-info-circle me-1"></i>Differenze rapide:</strong> <strong>Jolly</strong> = il widget non lo propone, tu s&igrave;. <strong>Bloccato</strong> = nessuno lo assegna, finch&eacute; non lo sblocchi. <strong>Archiviato</strong> = sparito dalla sala, ma lo storico resta.</div>

<h4>Modalit&agrave; Setup e Operativa</h4>
<p>La mappa ha due modalit&agrave; che si alternano dal selettore in alto:</p>
<ul>
    <li><strong>Setup</strong> &mdash; configuri la disposizione una volta sola: trascini i tavoli, salvi le posizioni. Lo usi all&rsquo;inizio o quando ridisponi la sala.</li>
    <li><strong>Operativa</strong> &mdash; vista del servizio in corso. Mostra lo stato dei tavoli per data e ora selezionate. &Egrave; quella che hai aperto nella voce <strong>Sala</strong> della sidebar ogni giorno.</li>
</ul>

<h4>Vista operativa: colori e stati sulla mappa</h4>
<p>Nella modalit&agrave; Operativa ogni tavolo ha un colore in base a cosa sta succedendo a quell&rsquo;ora:</p>
<ul>
    <li><span style="display:inline-block;width:14px;height:14px;background:#E6F4ED;border:1px solid #b8dcc6;border-radius:3px;vertical-align:middle;margin-right:4px;"></span> <strong>Verde chiaro</strong> &mdash; tavolo libero, nessuna prenotazione in quella fascia.</li>
    <li><span style="display:inline-block;width:14px;height:14px;background:#00844A;border-radius:3px;vertical-align:middle;margin-right:4px;"></span> <strong>Verde brand</strong> &mdash; prenotazione <strong>confermata</strong>, cliente atteso.</li>
    <li><span style="display:inline-block;width:14px;height:14px;background:#cfe2ff;border:1px solid #0EA5E9;border-radius:3px;vertical-align:middle;margin-right:4px;"></span> <strong>Azzurro</strong> &mdash; cliente <strong>arrivato</strong>, seduto al tavolo ora.</li>
    <li><span style="display:inline-block;width:14px;height:14px;background:#f1f3f5;border:3px dashed #F59E0B;box-sizing:border-box;border-radius:3px;vertical-align:middle;margin-right:4px;"></span> <strong>Bordo ambra tratteggiato</strong> &mdash; tavolo <strong>jolly</strong> (solo manuale, non assegnato dal widget).</li>
    <li><span style="display:inline-block;width:14px;height:14px;background:#9ca3af;border-radius:3px;vertical-align:middle;margin-right:4px;color:#dc3545;text-align:center;line-height:14px;font-size:10px;">&#128274;</span> <strong>Grigio con lucchetto rosso</strong> &mdash; tavolo <strong>bloccato</strong>.</li>
</ul>
<p>Cliccando un tavolo occupato o una prenotazione nella lista si apre il <strong>popup di dettaglio</strong>, da cui vedi i contatti del cliente, cambi lo stato (Confermata, Arrivato, No-show) e sposti la prenotazione su un altro tavolo. Due tavoli uniti per lo stesso gruppo sono collegati da una barra con l&rsquo;icona catena.</p>

<h4>Barra del servizio: orari, coperti e riempimento</h4>
<p>In alto nella vista operativa trovi la <strong>barra del servizio</strong>:</p>
<ul>
    <li><strong>Selettore servizio</strong> &mdash; scegli la fascia (Pranzo, Cena, Aperitivo&hellip;) e la barra mostra <strong>solo gli orari realmente configurati</strong> per quella fascia, con il totale dei coperti. La fascia scelta resta memorizzata anche se cambi pagina e torni in Sala.</li>
    <li><strong>Coperti e tavoli per slot</strong> &mdash; ogni orario indica quanti coperti e quanti tavoli sono occupati in quel momento; l&rsquo;altezza della colonna &egrave; proporzionale ai coperti.</li>
    <li><strong>Colore di riempimento</strong> &mdash; la colonna &egrave; <strong>verde</strong> quando sei tranquillo, <strong>ambra</strong> quando ti riempi (oltre il 60% della capienza) e <strong>rossa</strong> quando sei quasi pieno o pieno: capisci a colpo d&rsquo;occhio dove sei al limite.</li>
    <li><strong>Indicatore &ldquo;ora&rdquo;</strong> &mdash; una linea arancione segna l&rsquo;orario attuale, cos&igrave; sai sempre a che punto del servizio sei.</li>
    <li>Cliccando un orario, la mappa mostra lo stato dei tavoli a quell&rsquo;ora.</li>
</ul>
<p>All&rsquo;apertura la Sala si posiziona da sola sull&rsquo;orario giusto: l&rsquo;ora corrente se sei in servizio, altrimenti il primo orario con prenotazioni.</p>

<h4>Prossima prenotazione sul tavolo</h4>
<p>Su un tavolo che ha un turno successivo compare un <strong>badge</strong> con cognome e numero di persone della prossima prenotazione (es. &ldquo;ROSSI 4p&rdquo;), pi&ugrave; un &ldquo;+N&rdquo; se ci sono altri turni dopo. Cos&igrave; vedi i turni della serata senza cambiare orario; cliccando il badge apri direttamente quella prenotazione.</p>

<h4>Durata e turni per fascia</h4>
<p>Con i <strong>turni avanzati</strong> (piani Professional ed Enterprise) imposti <strong>quanto a lungo resta occupato un tavolo per ogni fascia</strong> &mdash; per esempio 75 minuti all&rsquo;aperitivo e 120 alla cena, con la possibilit&agrave; di accorciare o allungare in giorni specifici (es. weekend). Si configura in <em>Impostazioni &rarr; Categorie Pasto</em>. La durata determina ogni quanto un tavolo torna libero per il turno successivo ed &egrave; mostrata anche al cliente al momento della prenotazione.</p>

<div class="hg-tip"><strong><i class="bi bi-lightbulb me-1"></i>Consiglio:</strong> nella scheda di ogni prenotazione puoi sempre cambiare il tavolo a mano, anche con l&rsquo;auto-assegnazione attiva. E se in <em>Impostazioni &rarr; Tavoli</em> compare l&rsquo;avviso giallo, significa che in certi orari accetti pi&ugrave; coperti di quanti posti hanno i tuoi tavoli: allinea i coperti o aggiungi tavoli.</div>
HTML
    ],

    'ordini' => [
        'title'       => 'Ordini online',
        'subtitle'    => 'Asporto e consegna a domicilio',
        'icon'        => 'bi-bag-check',
        'color'       => '#F57F17',
        'category'    => 'avanzati',
        'count_label' => '1 articolo',
        'keywords'    => 'ordini online asporto delivery consegna domicilio kanban cap zone store slot intervallo max per slot tempo preparazione',
        'read_time'   => 4,
        'body' => <<<'HTML'
<p>Ogni ristorante ha il suo store online per asporto e consegna, raggiungibile su <code>dominio/nome-ristorante/order</code>.</p>
<p>Dalla sezione <em>Ordini</em> gestisci tutto con un <strong>kanban visuale</strong>:</p>
<ul>
    <li><strong>Nuovi</strong>: ordini appena ricevuti (accetta o rifiuta)</li>
    <li><strong>Accettati</strong>: hai confermato al cliente</li>
    <li><strong>In preparazione</strong>: la cucina sta lavorando</li>
    <li><strong>Pronti</strong>: pronti per ritiro o consegna</li>
</ul>
<p>Per le consegne puoi configurare <strong>zone per CAP</strong> con costi e minimi d&rsquo;ordine differenziati.</p>

<h4>Come funzionano gli slot di ritiro/consegna</h4>
<p>Quando un cliente apre il tuo store, vede una lista di <strong>orari disponibili</strong> per ritirare o ricevere l&rsquo;ordine. Tre parametri controllano come questi orari vengono generati e quanti ordini ci possono entrare:</p>
<ul>
    <li><strong>Tempo preparazione</strong>: quanti minuti ti servono per preparare un ordine. Il primo orario proposto al cliente parte da questo intervallo rispetto ad adesso. Esempio: se imposti 30 min e sono le 12:00, il primo slot disponibile sar&agrave; alle 12:30.</li>
    <li><strong>Intervallo slot</strong>: ogni quanti minuti viene creato uno slot di ritiro/consegna. Esempio: con 15 min il cliente vede 12:30, 12:45, 13:00, 13:15&hellip; Con 30 min vede 12:30, 13:00, 13:30&hellip;</li>
    <li><strong>Max per slot</strong>: quanti ordini al massimo possono concentrarsi nello stesso orario. Raggiunto il limite, quello slot <strong>sparisce</strong> dal widget e il cliente deve sceglierne un altro pi&ugrave; tardi. Serve a non sovraccaricare la cucina o i rider.</li>
</ul>

<div class="hg-info"><strong><i class="bi bi-info-circle me-1"></i>Esempio pratico:</strong> tempo preparazione 30 min, intervallo 15 min, max 10 per slot. Se sono le 12:00 e alle 13:00 hai gi&agrave; 10 ordini, il cliente che ordina ora <em>non vede</em> l&rsquo;orario 13:00 nel widget e sceglie 13:15 (con 3/10) o pi&ugrave; tardi. La cucina non viene mai sommersa da pi&ugrave; di 10 ordini per quarto d&rsquo;ora.</div>

<div class="hg-tip"><strong><i class="bi bi-lightbulb me-1"></i>Quando cambiarli:</strong> intervallo a 30 o 60 min se la tua cucina prepara ordini complessi e ha bisogno di pi&ugrave; respiro tra le consegne. Max per slot pi&ugrave; basso (5&ndash;6) per spalmare gli ordini su pi&ugrave; orari; pi&ugrave; alto (15&ndash;20) se hai cucina grande con preparazione parallela.</div>
HTML
    ],

    'reputazione' => [
        'title'       => 'Gestione reputazione',
        'subtitle'    => 'Recensioni conformi alla Legge 34/2026',
        'icon'        => 'bi-star',
        'color'       => '#FFC107',
        'category'    => 'avanzati',
        'count_label' => '1 articolo',
        'keywords'    => 'reputazione recensioni google feedback legge 34 2026 filtro sentimento',
        'read_time'   => 3,
        'body' => <<<'HTML'
<p>Evulery gestisce automaticamente la richiesta di recensioni dopo ogni visita.</p>
<p>Il flusso:</p>
<ol>
    <li>Il cliente viene segnato come <strong>arrivato</strong> in dashboard</li>
    <li>Dopo alcune ore (configurabile), parte una email che chiede di valutare l&rsquo;esperienza</li>
    <li>Se il voto &egrave; <strong>alto</strong> (4-5 stelle) &rarr; il cliente viene indirizzato a Google per recensione pubblica</li>
    <li>Se il voto &egrave; <strong>basso</strong> (1-3 stelle) &rarr; il cliente lascia un feedback privato, visibile solo a te</li>
</ol>
<div class="hg-info"><strong><i class="bi bi-shield-check me-1"></i>Conformit&agrave;:</strong> il sistema rispetta la Legge 34/2026 sulle recensioni verificate. Le richieste partono solo da clienti realmente arrivati, entro i 30 giorni previsti dalla legge e senza incentivi.</div>
HTML
    ],

    'vetrina' => [
        'title'       => 'Vetrina Digitale',
        'subtitle'    => 'Pagina pubblica con QR code',
        'icon'        => 'bi-qr-code',
        'color'       => '#00897B',
        'category'    => 'avanzati',
        'count_label' => '4 step',
        'keywords'    => 'vetrina digitale hub link in bio qr code stampa pagina pubblica social instagram biglietti volantini palette colori personalizzati enterprise white-label',
        'read_time'   => 4,
        'body' => <<<'HTML'
<p>La <strong>Vetrina Digitale</strong> &egrave; una pagina pubblica del tuo ristorante che raggruppa in un&rsquo;unica schermata tutto ci&ograve; che un cliente pu&ograve; fare: prenotare un tavolo, vedere il menu, ordinare online, lasciare una recensione, scoprire le offerte attive, contattarti su WhatsApp, vedere come raggiungerti.</p>

<p>Funziona come un &ldquo;<strong>link in bio</strong>&rdquo;: lo metti nella biografia Instagram, sui biglietti da visita, sui volantini, oppure stampi il QR code per il tavolo. Una volta scansionato, il cliente atterra su una pagina ottimizzata per smartphone con tutte le azioni a portata di tap.</p>

<div class="hg-tip"><strong><i class="bi bi-lightbulb me-1"></i>Quando &egrave; utile:</strong> sostituisce LinkTree o servizi simili, &egrave; integrato col tuo brand Evulery, gratuito col piano Professional. Un solo QR per tutto, niente confusione.</div>

<h5 style="margin-top:1.5rem;">Come si attiva</h5>

<div class="hg-step">
    <div class="hg-step-num">1</div>
    <div class="hg-step-content">
        <strong>Vai in <em>Impostazioni &rarr; Vetrina Digitale</em></strong> e attiva il toggle <strong>&ldquo;Vetrina online&rdquo;</strong>. La pagina diventa subito pubblica all&rsquo;URL <code>dash.evulery.it/tuo-ristorante/hub</code>.
    </div>
</div>

<div class="hg-step">
    <div class="hg-step-num">2</div>
    <div class="hg-step-content">
        <strong>Personalizza l&rsquo;identit&agrave;</strong><br>
        Carica un <strong>logo</strong> (200x200 min, max 2MB) e una <strong>copertina</strong> (1200x400 consigliato). Aggiungi un <strong>sottotitolo</strong> sotto il nome (es. &ldquo;Trattoria moderna in centro storico&rdquo;).
    </div>
</div>

<div class="hg-step">
    <div class="hg-step-num">3</div>
    <div class="hg-step-content">
        <strong>Scegli la palette</strong><br>
        Seleziona uno dei 6 temi preconfigurati: Evulery Green, Terracotta, Nero Elegante, Oro &amp; Marrone, Verde Bosco, Grigio Minimal. La palette colora cover, CTA, badge e icone in modo coordinato.
    </div>
</div>

<div class="hg-step">
    <div class="hg-step-num">4</div>
    <div class="hg-step-content">
        <strong>Attiva le azioni che ti interessano</strong><br>
        La sezione &ldquo;Azioni disponibili&rdquo; mostra cosa apparir&agrave; nella Vetrina. <em>Prenota un tavolo</em> &egrave; sempre presente come CTA principale. Le altre (Menu, Ordini online, Recensioni, Offerte, WhatsApp, Telefono, Mappa) appaiono automaticamente se il servizio &egrave; attivo. Trascinale per riordinarle, oppure disattiva quelle che non vuoi mostrare.
    </div>
</div>

<h5 style="margin-top:1.5rem;">Stampare il QR code</h5>
<p>Nella colonna destra trovi il QR code della tua Vetrina. Due bottoni:</p>
<ul>
    <li><strong>PNG</strong>: scarica il QR ad <strong>alta risoluzione</strong> (1024x1024 px) pronto per la tipografia. Va bene per stampe fino a 17cm a 300 dpi (qualit&agrave; professionale).</li>
    <li><strong>Stampa</strong>: apre l&rsquo;anteprima di stampa con titolo &ldquo;Scansiona per accedere&rdquo; e l&rsquo;URL sotto. Pronto da stampare per il tavolo o la vetrina.</li>
</ul>

<div class="hg-tip"><strong><i class="bi bi-lightbulb me-1"></i>Casi d&rsquo;uso comuni:</strong></div>
<ul>
    <li><strong>Bio Instagram/Facebook</strong>: incolla il link nella tua bio invece di LinkTree</li>
    <li><strong>Biglietti da visita</strong>: stampa il QR sul retro</li>
    <li><strong>Volantini</strong>: aggiungi il QR per dare ai clienti accesso immediato a menu, prenotazioni e contatti</li>
    <li><strong>Sul tavolo</strong>: QR plastificato che il cliente scansiona durante o a fine pasto</li>
    <li><strong>Vetrina del locale</strong>: poster con QR per chi passa fuori e vuole prenotare</li>
</ul>

<h5 style="margin-top:1.5rem;">Personalizzazione avanzata (Enterprise)</h5>
<p>Con il piano <strong>Enterprise</strong> puoi attivare il toggle &ldquo;Personalizza la tua vetrina&rdquo; e impostare:</p>
<ul>
    <li><strong>Colori personalizzati</strong>: 4 colori liberi (primario, scuro per il gradiente cover, accento, sfondo). Quando attivo, sostituisce la palette preset selezionata.</li>
    <li><strong>Link personalizzati illimitati</strong>: aggiungi azioni custom oltre alle preset (es. &ldquo;Eventi privati&rdquo;, &ldquo;Newsletter VIP&rdquo;, &ldquo;Compleanni&rdquo;) con etichetta, URL, icona e una breve descrizione.</li>
    <li><strong>White-label</strong>: rimuove la scritta &ldquo;Powered by Evulery&rdquo; dal footer della Vetrina.</li>
</ul>

<div class="hg-info"><strong><i class="bi bi-info-circle me-1"></i>Nota:</strong> se passi da Enterprise a un piano inferiore, le impostazioni custom restano salvate ma <strong>non vengono applicate</strong> finch&eacute; non torni Enterprise. La Vetrina mostrer&agrave; la palette preset selezionata.</div>

<h5 style="margin-top:1.5rem;">Domande frequenti</h5>
<div class="hg-faq">
    <div class="hg-faq-item">
        <div class="hg-faq-q"><i class="bi bi-chevron-right"></i>L&rsquo;URL della Vetrina si pu&ograve; cambiare?</div>
        <div class="hg-faq-a">L&rsquo;URL deriva dallo slug del ristorante (es. <code>/trattoria-da-mario/hub</code>). Per cambiarlo, modifica lo slug del tuo ristorante in <em>Impostazioni &rarr; Generali</em>. Con piano Enterprise puoi anche usare un <strong>dominio personalizzato</strong> (vedi sezione Dominio).</div>
    </div>
    <div class="hg-faq-item">
        <div class="hg-faq-q"><i class="bi bi-chevron-right"></i>Cosa vedono i clienti se la Vetrina &egrave; spenta?</div>
        <div class="hg-faq-a">Non un errore 404. Vedono una pagina friendly con il nome del ristorante e un bottone per prenotare comunque. Cos&igrave; chi scansiona il QR non resta a mani vuote.</div>
    </div>
    <div class="hg-faq-item">
        <div class="hg-faq-q"><i class="bi bi-chevron-right"></i>La Vetrina &egrave; mobile-friendly?</div>
        <div class="hg-faq-a">S&igrave;, &egrave; pensata principalmente per smartphone (chi scansiona il QR usa il telefono). Funziona perfettamente anche su desktop, ma il layout privilegia la lettura mobile.</div>
    </div>
    <div class="hg-faq-item">
        <div class="hg-faq-q"><i class="bi bi-chevron-right"></i>Posso aggiungere link ai miei social nel footer?</div>
        <div class="hg-faq-a">S&igrave;. Nella sezione &ldquo;Social e contatti&rdquo; inserisci gli URL di Instagram, Facebook, TikTok, X, YouTube e il numero WhatsApp. Le icone appaiono nel footer della Vetrina solo se hai compilato il relativo campo.</div>
    </div>
    <div class="hg-faq-item">
        <div class="hg-faq-q"><i class="bi bi-chevron-right"></i>Il QR code che ho stampato cambier&agrave; se modifico la Vetrina?</div>
        <div class="hg-faq-a">No, il QR punta solo all&rsquo;URL della Vetrina. Modifiche a colori, azioni, link, palette si vedono <strong>immediatamente</strong> alla scansione successiva, senza riguardare il QR. Stampa una volta, aggiorna quando vuoi.</div>
    </div>
</div>
HTML
    ],

    'email' => [
        'title'       => 'Email marketing',
        'subtitle'    => 'Comunicazioni e campagne',
        'icon'        => 'bi-envelope-paper',
        'color'       => '#0097A7',
        'category'    => 'avanzati',
        'count_label' => '1 articolo',
        'keywords'    => 'email marketing comunicazioni campagne crediti segmentazione newsletter',
        'read_time'   => 2,
        'body' => <<<'HTML'
<p>Dalla sezione <em>Comunicazioni</em> puoi inviare email ai tuoi clienti: promozioni, eventi, novit&agrave; del menu.</p>
<p>Il sistema funziona a <strong>crediti</strong>: ogni email inviata scala 1 credito. Puoi acquistare pacchetti di crediti in base alle tue esigenze.</p>
<p>Puoi segmentare i destinatari per livello cliente (Nuovo, Abituale, VIP) o selezionarli manualmente.</p>
HTML
    ],

    'marketing' => [
        'title'       => 'Marketing e Provenienza',
        'subtitle'    => 'Scopri da dove arrivano le prenotazioni',
        'icon'        => 'bi-megaphone',
        'color'       => '#6A1B9A',
        'category'    => 'avanzati',
        'count_label' => '1 articolo',
        'keywords'    => 'marketing provenienza canale utm link tracciati qr code campagne attribuzione instagram facebook google hub origine sorgente',
        'read_time'   => 3,
        'body' => <<<'HTML'
<p>La sezione <em>Marketing</em> ti dice <strong>da dove arrivano le tue prenotazioni</strong> e ti d&agrave; gli strumenti per misurare i tuoi canali. &Egrave; disponibile sui piani <strong>Professional ed Enterprise</strong>.</p>

<h4>Report provenienza</h4>
<p>Per ogni prenotazione il sistema registra il <strong>canale di arrivo</strong> (Instagram, Facebook, Google, sito, link diretto&hellip;) leggendo i parametri di tracciamento presenti nel link. Nel report vedi quante prenotazioni e quanti coperti arrivano da ciascun canale.</p>

<h4>Generatore di link e QR</h4>
<p>Crei <strong>link tracciati</strong> da usare nei tuoi canali (bio Instagram, post Facebook, Google, volantini). Ogni link porta dove vuoi tu &mdash; widget di prenotazione, hub, menu o ordini &mdash; e il sistema attribuisce automaticamente la prenotazione a quel canale. Puoi anche generare il <strong>QR code</strong> da stampare.</p>

<h4>Campagne salvate</h4>
<p>Salvi i link che usi pi&ugrave; spesso come <strong>campagne</strong>, cos&igrave; li ritrovi al volo e ne confronti i risultati nel tempo.</p>

<div class="hg-tip"><strong><i class="bi bi-lightbulb me-1"></i>Consiglio:</strong> usa un link tracciato diverso per ogni canale (uno per Instagram, uno per Google, ecc.). Dopo qualche settimana saprai quale canale ti porta pi&ugrave; clienti e dove conviene investire.</div>
HTML
    ],

    'notifiche' => [
        'title'       => 'Notifiche',
        'subtitle'    => 'Non perdere mai una prenotazione',
        'icon'        => 'bi-bell',
        'color'       => '#D81B60',
        'category'    => 'avanzati',
        'count_label' => '1 articolo',
        'keywords'    => 'notifiche email campanella push browser chrome firefox edge dispositivi collegati telefono tablet rimuovi attiva',
        'read_time'   => 2,
        'body' => <<<'HTML'
<p>Evulery ti avvisa su 3 canali:</p>
<ul>
    <li><strong>Email</strong>: arriva su tutti i piani ogni volta che ricevi una prenotazione o un ordine</li>
    <li><strong>Campanella in dashboard</strong>: notifica visiva in tempo reale quando la dashboard &egrave; aperta</li>
    <li><strong>Push browser</strong>: notifica del sistema operativo anche con dashboard chiusa (Chrome, Firefox, Edge)</li>
</ul>
<p>Per attivare le push, vai in <em>Impostazioni &rarr; Notifiche</em> e clicca su <strong>Attiva</strong>. Le push vanno attivate <strong>su ogni dispositivo</strong> da cui vuoi ricevere gli avvisi (es. il telefono in cucina e il tablet di sala): apri la pagina da quel dispositivo e premi Attiva.</p>

<h4>Dispositivi collegati</h4>
<p>Sempre in <em>Impostazioni &rarr; Notifiche</em> trovi l&rsquo;elenco <strong>Dispositivi collegati</strong>: per ognuno vedi il browser, il sistema operativo e quando &egrave; stato collegato. Quello che stai usando ora &egrave; segnato con &ldquo;Questo dispositivo&rdquo;.</p>
<ul>
    <li>In elenco compaiono <strong>solo i dispositivi su cui hai premuto &ldquo;Attiva&rdquo;</strong>: il semplice accesso alla dashboard non aggiunge nulla.</li>
    <li>Per smettere di ricevere le notifiche su un dispositivo, usa il pulsante <strong>&times; Rimuovi</strong>: la rimozione &egrave; <strong>definitiva</strong> e il dispositivo non si ricollega da solo. Per riattivarle, riapri la pagina da quel dispositivo e premi di nuovo &ldquo;Attiva&rdquo;.</li>
</ul>
<div class="hg-info"><strong><i class="bi bi-info-circle me-1"></i>Su iPhone:</strong> le push del browser funzionano solo aggiungendo Evulery alla schermata Home (PWA) con iOS 16.4 o superiore.</div>
HTML
    ],

    'dominio' => [
        // Feature nascosta dalla guida (vedi settings_nav): togliere 'hidden' per riattivarla.
        'hidden'      => true,
        'title'       => 'Dominio personalizzato',
        'subtitle'    => 'Usa il tuo dominio per prenotazioni e Vetrina',
        'icon'        => 'bi-globe',
        'color'       => '#3F51B5',
        'category'    => 'avanzati',
        'count_label' => '5 step',
        'keywords'    => 'dominio personalizzato custom domain cname dns ssl https registrar aruba register godaddy namecheap sottodominio',
        'read_time'   => 5,
        'body' => <<<'HTML'
<p>Con il piano <strong>Enterprise</strong> puoi usare un <strong>tuo dominio</strong> al posto di <code>dash.evulery.it/tuo-ristorante</code>. Es. i tuoi clienti prenotano su <code>prenotazioni.ristorantemario.it</code> invece che su un URL Evulery.</p>

<div class="hg-tip"><strong><i class="bi bi-lightbulb me-1"></i>Perch&eacute; usarlo:</strong> pi&ugrave; professionale, marchio coerente, aumenta la fiducia del cliente (vede il TUO dominio, non un servizio esterno).</div>

<h5 style="margin-top:1.5rem;">Cosa ti serve prima di iniziare</h5>
<ul>
    <li>Un <strong>dominio di propriet&agrave;</strong> (es. <code>ristorantemario.it</code> comprato su Aruba, Register.it, GoDaddy, Namecheap, ecc.)</li>
    <li>L&rsquo;<strong>accesso al pannello DNS</strong> del tuo registrar (la password dove hai comprato il dominio)</li>
    <li>Piano <strong>Enterprise</strong> attivo su Evulery</li>
</ul>
<p style="margin-top:.75rem;">Puoi usare il dominio <strong>completo</strong> (<code>ristorantemario.it</code>) oppure un <strong>sottodominio</strong> (<code>prenotazioni.ristorantemario.it</code>, <code>tavolo.ristorantemario.it</code>). Consigliamo il sottodominio: cos&igrave; il sito principale del tuo ristorante non viene toccato.</p>

<h5 style="margin-top:1.5rem;">Procedura in 5 step</h5>

<div class="hg-step">
    <div class="hg-step-num">1</div>
    <div class="hg-step-content">
        <strong>Inserisci il dominio in Evulery</strong><br>
        Vai in <em>Impostazioni &rarr; Dominio personalizzato</em>, scrivi il dominio che vuoi usare (es. <code>prenotazioni.ristorantemario.it</code>) e clicca <strong>Salva</strong>. Il sistema ti mostrer&agrave; un valore <strong>CNAME</strong> da configurare sul tuo DNS (es. <code>dash.evulery.it</code>).
    </div>
</div>

<div class="hg-step">
    <div class="hg-step-num">2</div>
    <div class="hg-step-content">
        <strong>Configura il record DNS sul tuo registrar</strong><br>
        Accedi al pannello del registrar dove hai comprato il dominio. Vai nella sezione <strong>Gestione DNS</strong> (o &ldquo;Zone DNS&rdquo;) del tuo dominio. Aggiungi un record di tipo <strong>CNAME</strong>:
        <table style="margin-top:.5rem; font-size:.85rem; border-collapse: collapse;">
            <tr style="background:#f0f0f0;"><th style="padding:4px 8px; text-align:left;">Campo</th><th style="padding:4px 8px; text-align:left;">Valore</th></tr>
            <tr><td style="padding:4px 8px; border-top: 1px solid #ddd;"><strong>Tipo</strong></td><td style="padding:4px 8px; border-top: 1px solid #ddd;">CNAME</td></tr>
            <tr><td style="padding:4px 8px;"><strong>Nome / Host</strong></td><td style="padding:4px 8px;"><code>prenotazioni</code> (solo la parte prima del dominio)</td></tr>
            <tr><td style="padding:4px 8px;"><strong>Valore / Punta a</strong></td><td style="padding:4px 8px;"><code>dash.evulery.it</code></td></tr>
            <tr><td style="padding:4px 8px;"><strong>TTL</strong></td><td style="padding:4px 8px;">3600 (1 ora) o <em>automatico</em></td></tr>
        </table>
        <p style="margin-top:.5rem; font-size:.85rem;">Salva il record. <strong>Attenzione:</strong> se usi il dominio completo (non un sottodominio) chiedi al tuo registrar come fare &mdash; alcuni registrar usano <code>@</code> come &ldquo;Nome&rdquo; oppure richiedono ALIAS/ANAME invece di CNAME.</p>
    </div>
</div>

<div class="hg-step">
    <div class="hg-step-num">3</div>
    <div class="hg-step-content">
        <strong>Attendi la propagazione DNS</strong><br>
        Il cambio DNS si propaga da <strong>pochi minuti fino a 24 ore</strong>, a seconda del registrar. Nella maggior parte dei casi bastano 10-30 minuti. Torna in <em>Impostazioni &rarr; Dominio personalizzato</em> e clicca <strong>Verifica DNS</strong>. Se il verde appare, sei a met&agrave; strada.
    </div>
</div>

<div class="hg-step">
    <div class="hg-step-num">4</div>
    <div class="hg-step-content">
        <strong>Attendi l&rsquo;attivazione SSL</strong><br>
        Dopo la verifica DNS, il nostro team aggiunge il tuo dominio al server entro 24h lavorative. Poi il certificato HTTPS viene emesso automaticamente (Let&rsquo;s Encrypt). Riceverai un&rsquo;<strong>email di conferma</strong> quando il dominio &egrave; attivo. Non devi fare nulla in questa fase.
    </div>
</div>

<div class="hg-step">
    <div class="hg-step-num">5</div>
    <div class="hg-step-content">
        <strong>Testa il dominio</strong><br>
        Apri un browser e vai sul tuo dominio (es. <code>https://prenotazioni.ristorantemario.it</code>). Dovresti vedere la tua <strong>Vetrina Digitale</strong> o il tuo widget di prenotazione. Se vedi il lucchetto verde, HTTPS funziona. Aggiorna i link del tuo sito/social/biglietti da visita col nuovo dominio.
    </div>
</div>

<h5 style="margin-top:1.5rem;">Guide specifiche per registrar</h5>
<ul>
    <li><strong>Aruba</strong>: Area clienti &rarr; Hosting e domini &rarr; <em>il tuo dominio</em> &rarr; Gestione DNS &rarr; Modifica &rarr; Aggiungi record CNAME</li>
    <li><strong>Register.it</strong>: Dominio &rarr; DNS avanzato &rarr; Nuovo record &rarr; tipo CNAME</li>
    <li><strong>GoDaddy</strong>: My Products &rarr; DNS &rarr; Add New Record &rarr; CNAME</li>
    <li><strong>Namecheap</strong>: Domain List &rarr; Manage &rarr; Advanced DNS &rarr; Add New Record &rarr; CNAME Record</li>
    <li><strong>Serverplan</strong>: Pannello cPanel del tuo dominio &rarr; Zone Editor &rarr; Add Record &rarr; Tipo CNAME</li>
</ul>

<h5 style="margin-top:1.5rem;">Problemi comuni</h5>
<div class="hg-faq">
    <div class="hg-faq-item">
        <div class="hg-faq-q"><i class="bi bi-chevron-right"></i>La verifica DNS dice che il record non &egrave; corretto, ma l&rsquo;ho appena inserito</div>
        <div class="hg-faq-a">Il DNS ha bisogno di <strong>tempo per propagarsi</strong>. Aspetta 15-30 minuti e riprova. Se dopo 2 ore ancora non verifica, ricontrolla il record sul registrar &mdash; spesso l&rsquo;errore &egrave; nel campo "Nome/Host" (deve essere solo la parte prima del dominio, non quella completa).</div>
    </div>
    <div class="hg-faq-item">
        <div class="hg-faq-q"><i class="bi bi-chevron-right"></i>Posso usare direttamente il mio dominio principale (ristorantemario.it senza sottodominio)?</div>
        <div class="hg-faq-a">Tecnicamente s&igrave;, ma <strong>non lo consigliamo</strong>: sostituisci completamente il sito principale del ristorante. Inoltre molti registrar non permettono CNAME sul dominio radice, serve un record ALIAS/ANAME. Usa un sottodominio (<code>prenotazioni</code>, <code>tavolo</code>, <code>vetrina</code>) e tieni il sito principale separato.</div>
    </div>
    <div class="hg-faq-item">
        <div class="hg-faq-q"><i class="bi bi-chevron-right"></i>Quanto costa il dominio personalizzato?</div>
        <div class="hg-faq-a">Il servizio Evulery &egrave; <strong>gratuito</strong> (incluso nel piano Enterprise). Il dominio lo acquisti dal tuo registrar (tipicamente 10-15&euro;/anno per un .it).</div>
    </div>
    <div class="hg-faq-item">
        <div class="hg-faq-q"><i class="bi bi-chevron-right"></i>Il browser mostra "Not Secure" o "Certificato non valido"</div>
        <div class="hg-faq-a">&Egrave; normale nelle prime 24 ore dopo la verifica DNS: il certificato SSL non &egrave; ancora stato emesso. Aspetta e riprova. Se dopo 48 ore dalla verifica DNS vedi ancora l&rsquo;errore, <a href="mailto:info@evulery.it" style="color:var(--brand);">scrivici</a>.</div>
    </div>
    <div class="hg-faq-item">
        <div class="hg-faq-q"><i class="bi bi-chevron-right"></i>Posso cambiare o rimuovere il dominio personalizzato?</div>
        <div class="hg-faq-a">S&igrave;. In <em>Impostazioni &rarr; Dominio personalizzato</em> lascia il campo vuoto e salva. Il tuo account torner&agrave; a funzionare esclusivamente su <code>dash.evulery.it/tuo-ristorante</code>.</div>
    </div>
</div>

<div class="hg-info"><strong><i class="bi bi-question-circle me-1"></i>Hai bisogno di aiuto?</strong> Scrivici a <a href="mailto:info@evulery.it" style="color:var(--brand);">info@evulery.it</a> indicando il tuo dominio: possiamo verificare la configurazione insieme a te.</div>
HTML
    ],

    // ══════════ SUPPORTO ══════════

    'faq' => [
        'title'       => 'Domande frequenti',
        'subtitle'    => 'Risposte ai dubbi pi&ugrave; comuni',
        'icon'        => 'bi-question-circle',
        'color'       => '#6c757d',
        'category'    => 'supporto',
        'count_label' => '5 domande',
        'keywords'    => 'faq domande frequenti risposte dubbi',
        'read_time'   => 4,
        'body' => <<<'HTML'
<div class="hg-faq">
    <div class="hg-faq-item">
        <div class="hg-faq-q"><i class="bi bi-chevron-right"></i>Come modifico il mio orario dopo averlo gi&agrave; impostato?</div>
        <div class="hg-faq-a">Vai in <em>Impostazioni &rarr; Orari e Coperti</em>, modifica i coperti delle fasce desiderate e clicca <strong>Salva Configurazione</strong>. Le modifiche sono immediate.</div>
    </div>
    <div class="hg-faq-item">
        <div class="hg-faq-q"><i class="bi bi-chevron-right"></i>Posso accettare prenotazioni fuori dagli orari delle categorie?</div>
        <div class="hg-faq-a">S&igrave;. Le categorie servono solo a raggruppare visivamente gli slot nel widget. Se metti coperti in una fascia oraria senza categoria attiva, il cliente potr&agrave; comunque prenotare.</div>
    </div>
    <div class="hg-faq-item">
        <div class="hg-faq-q"><i class="bi bi-chevron-right"></i>Cosa succede se un cliente non paga la caparra Stripe?</div>
        <div class="hg-faq-a">La prenotazione resta <em>in attesa</em> per 30 minuti. Se non arriva il pagamento, viene cancellata automaticamente e lo slot torna disponibile.</div>
    </div>
    <div class="hg-faq-item">
        <div class="hg-faq-q"><i class="bi bi-chevron-right"></i>Quando parte la richiesta di recensione?</div>
        <div class="hg-faq-a">Dopo che segni il cliente come <strong>arrivato</strong>, passano le ore configurate (default 1 ora dopo la fine della visita). L&rsquo;invio rispetta l&rsquo;orario di quiet hour che hai impostato.</div>
    </div>
    <div class="hg-faq-item">
        <div class="hg-faq-q"><i class="bi bi-chevron-right"></i>Come importo clienti da TheFork?</div>
        <div class="hg-faq-a">Esporta il file CSV da TheFork, poi vai in <em>Clienti &rarr; Importa</em>, carica il file e mappa le colonne (nome, email, telefono). Il sistema importa i clienti evitando duplicati.</div>
    </div>
</div>
HTML
    ],

];
