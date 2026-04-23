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
        'count_label' => '1 articolo',
        'keywords'    => 'chiusure ferie giorni riposo eventi privati',
        'read_time'   => 2,
        'body' => <<<'HTML'
<p>In <em>Impostazioni &rarr; Chiusure</em> puoi bloccare specifici giorni o periodi (ferie, riposo settimanale, eventi privati).</p>
<ul>
    <li><strong>Chiusura singola</strong>: una data specifica (es. Natale)</li>
    <li><strong>Chiusura periodica</strong>: un intervallo di date (es. ferie estive)</li>
    <li><strong>Chiusura parziale</strong>: solo una fascia oraria di un giorno</li>
</ul>
<p>Nei giorni di chiusura il widget non accetta prenotazioni e mostra un messaggio al cliente.</p>
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
        'keywords'    => 'caparra deposito noshow stripe link pagamento informativa bonifico',
        'read_time'   => 3,
        'body' => <<<'HTML'
<p>La caparra &egrave; la soluzione pi&ugrave; efficace per ridurre i no-show. Evulery offre 3 modalit&agrave;:</p>
<ol>
    <li><strong>Informativa</strong>: comunichi l&rsquo;importo al cliente, che paga di persona o con bonifico</li>
    <li><strong>Link di pagamento</strong>: il cliente paga con il metodo che preferisci (Satispay, PayPal, ecc.)</li>
    <li><strong>Stripe</strong>: pagamento automatico con carta di credito durante la prenotazione</li>
</ol>
<p>Puoi configurare <strong>quando richiedere la caparra</strong> (es. solo per tavoli &gt;4 persone, solo nel weekend, solo per eventi specifici).</p>
<div class="hg-warn"><strong><i class="bi bi-exclamation-triangle me-1"></i>Attenzione:</strong> una prenotazione con caparra Stripe resta <em>in attesa</em> fino al pagamento. Se il cliente non paga entro 30 minuti, viene cancellata automaticamente.</div>
HTML
    ],

    // ══════════ SERVIZI AVANZATI ══════════

    'ordini' => [
        'title'       => 'Ordini online',
        'subtitle'    => 'Asporto e consegna a domicilio',
        'icon'        => 'bi-bag-check',
        'color'       => '#F57F17',
        'category'    => 'avanzati',
        'count_label' => '1 articolo',
        'keywords'    => 'ordini online asporto delivery consegna domicilio kanban cap zone store',
        'read_time'   => 3,
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

    'notifiche' => [
        'title'       => 'Notifiche',
        'subtitle'    => 'Non perdere mai una prenotazione',
        'icon'        => 'bi-bell',
        'color'       => '#D81B60',
        'category'    => 'avanzati',
        'count_label' => '1 articolo',
        'keywords'    => 'notifiche email campanella push browser chrome firefox edge',
        'read_time'   => 2,
        'body' => <<<'HTML'
<p>Evulery ti avvisa su 3 canali:</p>
<ul>
    <li><strong>Email</strong>: arriva su tutti i piani ogni volta che ricevi una prenotazione o un ordine</li>
    <li><strong>Campanella in dashboard</strong>: notifica visiva in tempo reale quando la dashboard &egrave; aperta</li>
    <li><strong>Push browser</strong>: notifica del sistema operativo anche con dashboard chiusa (Chrome, Firefox, Edge)</li>
</ul>
<p>Per attivare le push, vai in <em>Notifiche</em> e clicca su <strong>Attiva push</strong>.</p>
HTML
    ],

    'dominio' => [
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
