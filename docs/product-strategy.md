# Evulery.Pro - Ragionamenti di Prodotto e Strategia

Documento di riferimento con le analisi, i ragionamenti e le decisioni di prodotto prese durante la progettazione. Utile come base per il copy del sito web, materiale marketing e comunicazione verso i ristoratori.

---

## 1. Vision del Prodotto

Evulery.Pro e un sistema di prenotazione SaaS pensato per i ristoratori indipendenti. Non e un aggregatore come TheFork - il ristorante mantiene il pieno controllo del proprio brand, dei propri clienti e della propria pagina di prenotazione.

**Differenza chiave rispetto a TheFork:**
- TheFork e un marketplace: il cliente cerca il ristorante sulla piattaforma, il ristorante paga commissioni per ogni coperto
- Evulery.Pro e uno strumento: il ristorante ha la propria pagina di prenotazione, i propri clienti, zero commissioni

**Proposta di valore:**
- Il ristoratore possiede i dati dei propri clienti (CRM)
- Nessuna commissione per coperto
- Brand del ristorante, non del marketplace
- Prezzo fisso mensile prevedibile

---

## 2. Widget di Prenotazione - L'Esperienza del Cliente Finale

### Filosofia: Zero Frizione
La prenotazione deve essere veloce e intuitiva. 4 step puliti, nessuna registrazione richiesta, nessun account da creare.

### Il Flusso (4 Step)
1. **Data** - Calendario mensile visuale, navigazione mese, giorni passati disabilitati
2. **Orario** - Slot raggruppati per categoria pasto (Brunch, Pranzo, Aperitivo, Cena, After Dinner). Ogni ristorante configura le proprie fasce
3. **Persone** - Griglia 1-12 con opzione "piu persone" (13-20) per gruppi
4. **Dati** - Solo i dati essenziali: nome, cognome, telefono, email

### Social Proof
Banner in cima al widget: "Gia X prenotazioni per oggi". Crea urgenza e fiducia. Il numero e reale, calcolato in tempo reale dal sistema.

### Perche NON chiediamo la registrazione
- Ogni campo in piu nel form riduce le conversioni
- Il cliente vuole prenotare, non creare un account
- I dati necessari per il CRM li raccogliamo gia dalla prenotazione (email, telefono, nome)
- L'account non serve: il link magico risolve il bisogno di gestire la prenotazione

---

## 3. CRM Automatico - La Banca Dati Clienti

### Il Problema
I ristoratori annotano i clienti su fogli di carta, Excel o nella memoria. Non hanno una visione strutturata di chi viene, quanto spesso, e chi non torna piu.

### La Soluzione: CRM che si costruisce da solo
Ogni prenotazione alimenta automaticamente il database clienti. Il ristoratore non deve fare nulla - il sistema raggruppa per email e costruisce il profilo.

### Cosa vede il ristoratore
- **Lista clienti** con numero visite, ultima visita, no-show rate
- **Scheda cliente** con storico completo di tutte le prenotazioni
- **Segmentazione automatica**:
  - Nuovo (1 visita) - appena arrivato, da coltivare
  - Occasionale (2-3 visite) - potenziale da fidelizzare
  - Abituale (4-9 visite) - cliente fedele, da mantenere
  - VIP (10+ visite) - il cuore del business, da coccolare
- **Note personali**: "Allergia noci", "Preferisce veranda", "Compleanno moglie 15 marzo"

### Perche e tenant-scoped (non globale)
Ogni ristorante vede SOLO i propri clienti. Non stiamo costruendo un aggregatore - i dati appartengono al ristoratore. Se Mario Rossi prenota in due ristoranti diversi, sono due profili separati.

### Valore per il ristoratore
- Sa chi sono i suoi clienti migliori
- Identifica chi non torna (retention)
- Riconosce i clienti problematici (no-show ripetuti → blacklist)
- Puo personalizzare il servizio (note: allergie, preferenze)

---

## 4. Link Magico - Gestione Prenotazione Senza Account

### Il Problema
Il cliente ha prenotato per sabato sera ma deve cambiare orario. Come fa? Chiama il ristorante? Manda un WhatsApp? E se e domenica e il ristorante e chiuso?

### La Soluzione: Un Link, Zero Password
Dopo la prenotazione, il cliente riceve un'email con un link unico. Cliccandolo accede a una pagina dove puo:
- Vedere i dettagli della prenotazione
- Modificare data, orario o numero di persone
- Cancellare la prenotazione (con motivo opzionale)

### Perche il link magico e meglio dell'account
- **Zero frizione**: nessuna registrazione, nessuna password da ricordare
- **Funziona subito**: click dall'email → gestisci tutto
- **Copre il 90% dei casi**: il cliente vuole modificare o cancellare, non navigare un portale
- **Sicurezza**: token lungo e unico, inindovinabile, con scadenza

### Il controllo resta al ristoratore
Il ristoratore configura le regole:
- "Modifiche consentite fino a 2 ore prima"
- "Cancellazione consentita fino a 1 ora prima"
- Puo decidere se permettere modifica data, modifica persone, o entrambe

### Vantaggi operativi per il ristoratore
- Meno telefonate per modifiche/cancellazioni
- Il cliente gestisce tutto in autonomia
- Ogni modifica viene notificata nel dashboard
- Le cancellazioni last-minute liberano tavoli che possono essere riprenotati

---

## 5. Reminder Email - Ridurre i No-Show

### Il Problema
I no-show sono il nemico numero uno della ristorazione. Un tavolo vuoto per una prenotazione dimenticata e un danno economico diretto.

### La Soluzione: Promemoria Automatico
Il sistema invia un'email di promemoria prima della prenotazione. Il timing e configurabile dal ristoratore:
- 24 ore prima (default) - tempo per cancellare se hanno cambiato idea
- 2 ore prima - ultimo promemoria
- Entrambi - doppio reminder per massima copertura
- Disattivato - per chi non lo vuole

### L'email contiene
- Riepilogo prenotazione (data, ora, persone, ristorante)
- Lo stesso link magico di gestione: "Non puoi piu venire? Modifica o cancella"

### Perche funziona
- Il cliente che ha dimenticato viene ricordato → si presenta
- Il cliente che ha cambiato idea cancella con un click → il tavolo si libera
- In entrambi i casi il ristoratore ci guadagna rispetto al no-show

---

## 6. Giorni di Chiusura - Funzionalita Critica

### Il Problema
Senza questa funzionalita il widget mostra slot disponibili in giorni in cui il ristorante e chiuso (ferie, festivi, chiusura settimanale straordinaria). Il cliente prenota, il ristorante e chiuso. Pessima esperienza.

### La Soluzione
Pagina nel dashboard dove il ristoratore segna:
- **Chiusure singole**: 1 maggio, 25 dicembre, ecc.
- **Ferie**: range date (es. 10-25 agosto)
- **Orari speciali**: per un giorno specifico, orari diversi dal solito (es. vigilia Natale solo pranzo)

Il widget mostra automaticamente questi giorni come non disponibili (grigi, non cliccabili).

---

## 7. Conferma Manuale - Per Chi Vuole il Controllo

### Il Problema
Alcuni ristoratori non vogliono la conferma automatica. Preferiscono valutare ogni prenotazione prima di accettarla (ristoranti di alta fascia, posti limitati, eventi speciali).

### La Soluzione
Impostazione on/off nel dashboard. Se attiva:
- La prenotazione arriva come "In attesa di conferma"
- Il ristoratore la conferma o rifiuta dal dashboard
- Il cliente riceve un'email con l'esito
- Nella pagina del link magico vede "In attesa di conferma"

### Per chi e pensata
- Ristoranti stellati o di alta gamma
- Locali con posti molto limitati
- Eventi speciali dove serve approvazione

---

## 8. Blacklist Clienti - Gestire i No-Show Ripetuti

### Il Problema
Il cliente che non si presenta 3 volte su 5 prenotazioni costa al ristorante. Tavoli vuoti, cibo preparato, coperti persi.

### La Soluzione
Il ristoratore vede il tasso di no-show nella scheda cliente (dal CRM automatico). Se un cliente e problematico, lo blocca con un click. Il cliente bloccato che tenta di prenotare con la stessa email vede: "Contatta il ristorante telefonicamente".

Il ristoratore puo sbloccare in qualsiasi momento - non e un ban permanente.

---

## 9. Promozioni e Sconti - Riempire i Tavoli Vuoti

### La Differenza con TheFork
Su TheFork lo sconto serve ad attirare clienti sulla piattaforma (aggregatore). Su Evulery.Pro il cliente e gia sulla pagina del ristorante. Lo sconto serve a **gestire la domanda**: incentivare le fasce orarie e i giorni vuoti.

### Come Funziona per il Ristoratore
Il ristoratore crea regole di sconto dalla dashboard:
- **Giorno ricorrente**: "Ogni martedi -20%" → il martedi e sempre vuoto, lo riempio
- **Fascia oraria**: "18:00-19:00 -15% ogni giorno" → l'early dinner nessuno la prenota
- **Data specifica**: "15 marzo -30%" → settimana prossima ho poche prenotazioni
- **Combinato**: "Ogni lunedi 12:00-14:00 -25%" → il pranzo del lunedi e morto

### Come lo Vede il Cliente
Nel widget, gli slot con promozione mostrano un badge percentuale (esattamente come TheFork):
```
Pranzo
[12:00 -20%] [12:30 -20%] [13:00 -20%]

Cena
[19:00] [19:30] [20:00]
```

La conferma prenotazione include: "Promozione applicata: -20% sul conto". Lo sconto si applica al conto al tavolo - non c'e transazione digitale.

### Nota Importante
Lo sconto NON modifica la caparra Stripe. La caparra e un deposito a garanzia, lo sconto si applica al conto finale. Sono due cose separate.

### Il Valore Reale
Il ristoratore conosce i suoi giorni e orari deboli. Con le promozioni ha uno strumento concreto per riempirli. E puo misurare l'efficacia: quante prenotazioni arrivano con promozione vs prezzo pieno.

---

## 10. Email Broadcast - Comunicare con i Clienti

### Il Problema
Il ristoratore vuole comunicare con i suoi clienti: nuovo menu, festa a tema, serata speciale, promozione del weekend. Oggi usa Instagram, WhatsApp, passaparola. Non ha un canale diretto.

### La Soluzione: Newsletter Semplice
Niente editor complessi, niente drag&drop. Una pagina nel dashboard con:
- Oggetto dell'email
- Testo libero (con possibilita di aggiungere link)
- Scelta destinatari: tutti, per segmento (VIP, Abituali, chi non viene da 30+ giorni), ecc.

Il template e fisso e pulito: logo ristorante in header, messaggio al centro, footer con indirizzo e link disiscrizione.

### Esempi d'Uso
- "Giovedi sera serata pesce fresco, prenota ora!" → link al widget
- "Nuovo menu autunnale disponibile dal 1 ottobre"
- "Festeggia Capodanno con noi - menu speciale a 60 euro"
- "Ci manchi! Torna a trovarci con il -15% questa settimana" → ai clienti che non vengono da 30+ giorni

### GDPR
Link disiscrizione obbligatorio in ogni email. Il cliente che clicca "disiscrivi" non riceve piu comunicazioni ma resta nel CRM (le prenotazioni continuano a funzionare).

### Perche e Potente
Il ristoratore ha gia la banca dati (CRM automatico). L'email broadcast la trasforma in uno strumento di marketing. Un messaggio mirato al segmento giusto riempie tavoli nei giorni vuoti.

---

## 11. Dashboard Prenotazione Rapida - La Postazione del Ristoratore

### Il Problema
Il ristoratore riceve una telefonata: "Buonasera, vorrei prenotare per sabato sera, 4 persone alle 20:30". Ha pochi secondi - il cliente e al telefono, la sala e piena, la cucina chiama. La pagina "Nuova Prenotazione" attuale ha dropdown nativi, input date testuale, nessun feedback sulla disponibilita. Troppo lento, troppo scomodo, soprattutto su tablet e schermi touch che molti ristoratori usano in sala.

### La Soluzione: 5 Tocchi in 5 Secondi
Un'interfaccia pensata per la velocita e l'uso touch. Niente dropdown, niente digitazione: solo pulsanti grandi e feedback visivo immediato.

### Il Flusso Ideale (prenotazione telefonica)
1. **Tocca "Domani"** - pulsante grande, gia visibile (Oggi / Domani / Dopodomani)
2. **Tocca "20:30"** - slot raggruppati per servizio (Pranzo, Cena) con indicatore coperti liberi
3. **Tocca "4"** - griglia coperti 1-10, pulsanti grandi
4. **Digita "Bianchi"** - ricerca cliente, autocompletamento dal CRM → dati precompilati
5. **Tocca "Salva Prenotazione"** - fatto

### Indicatore Disponibilita in Tempo Reale
Ogni slot mostra i coperti disponibili con codice colore:
- **Verde**: disponibile (ampia disponibilita)
- **Giallo**: quasi pieno (pochi coperti rimasti)
- **Rosso**: pieno (non selezionabile)

Il ristoratore vede subito se puo accettare la prenotazione senza dover controllare altrove.

### Ricerca Cliente dal CRM
Il campo di ricerca unificato cerca per telefono o nome. Se il cliente ha gia prenotato, i dati si compilano automaticamente. Accanto al nome appare il badge segmento (Nuovo, Abituale, VIP). Se il cliente e in blacklist, appare un avviso e la prenotazione viene bloccata.

Questo velocizza enormemente le prenotazioni telefoniche dei clienti abituali: basta il cognome per avere tutto precompilato.

### Sorgente Prenotazione
Ogni prenotazione da dashboard registra la sorgente: **Telefono**, **Walk-in** o **Altro**. Questo dato alimenta le statistiche: il ristoratore sa quante prenotazioni arrivano dal widget vs telefonate vs passaggio diretto. Capisce se il widget sta funzionando.

### Perche Touch-Friendly
Molti ristoratori usano tablet in sala o alla cassa. I dropdown nativi del browser sono piccoli e imprecisi su touch. I pulsanti grandi (minimo 48x48px) con spaziatura generosa eliminano gli errori di tocco. Il flusso verticale a colonna singola funziona su qualsiasi schermo.

### Il Valore
- **Velocita**: prenotazione telefonica in 5 secondi, non 30
- **Meno errori**: pulsanti invece di digitazione, disponibilita visibile
- **CRM integrato**: il ristoratore riconosce subito il cliente abituale
- **Statistiche sorgente**: capire da dove arrivano le prenotazioni
- **Touch-ready**: funziona su tablet in sala senza mouse

---

## 12. Piani Commerciali - L'Architettura SaaS

### La Filosofia
4 piani crescenti, dal gratuito al business. Il piano Free include solo il widget di prenotazione base. Tutte le funzionalita avanzate sono flag booleani controllabili dall'admin, assegnati ai piani tramite una matrice configurabile.

### I Piani

**Free (0 euro/mese)**
- Per chi vuole provare il sistema
- 50 prenotazioni al mese
- Widget di prenotazione completo (calendario, orari raggruppati, form contatto)
- 1 utente staff
- *Nessuna funzionalita avanzata* - serve come prova gratuita per convincere il ristoratore del valore

**Starter (29 euro/mese)**
- Per piccoli ristoranti che vogliono crescere
- 500 prenotazioni al mese
- Tutto il Free +
- CRM clienti (segmentazione, storico, statistiche)
- Link magico gestione prenotazione
- Blacklist clienti + Note clienti
- Giorni di chiusura e ferie
- Reminder email
- Export CSV
- Email avanzate (template personalizzati)
- 2 utenti staff

**Pro (59 euro/mese)**
- Per ristoranti strutturati
- Prenotazioni illimitate
- Tutto lo Starter +
- Caparra Stripe
- Categorie pasto personalizzate
- Promozioni/Sconti (badge % nel widget)
- Email broadcast (newsletter ai clienti)
- Conferma manuale prenotazioni
- Analytics e report
- Accesso API
- Supporto prioritario
- 5 utenti staff

**Business (99 euro/mese)**
- Per ristoranti multi-sede o catene
- Prenotazioni illimitate
- Tutto il Pro +
- Dominio personalizzato (white-label)
- SMS (Twilio)
- Staff illimitati

### Perche Questa Struttura
- **Free**: abbassa la barriera d'ingresso. Il ristoratore prova il widget senza rischio. Quando vede che funziona e vuole CRM, reminder, gestione clienti, passa a Starter
- **Starter**: il primo step a pagamento. Prezzo accessibile (meno di un coperto al giorno). Il CRM, il link magico e i reminder da soli giustificano il costo: meno telefonate, meno no-show, conoscenza dei clienti
- **Pro**: per chi fa sul serio. Promozioni per riempire tavoli vuoti, broadcast per comunicare con i clienti, caparra per ridurre i no-show, conferma manuale per il controllo totale
- **Business**: per chi vuole il massimo. Dominio proprio, brand al 100%, SMS, staff illimitati

### Feature Flags - Tutto e Configurabile
L'architettura e a matrice: ogni funzionalita e un flag (booleano o con limite numerico) assegnato ai piani. L'admin puo modificare la matrice senza toccare codice. Ogni nuova funzionalita viene automaticamente aggiunta come flag nella matrice, permettendo di decidere in quale piano includerla.

Questo significa che:
- L'admin puo spostare una feature da Pro a Starter con un click
- Puo abilitare una feature gratis per un singolo tenant (override)
- Puo creare un nuovo piano senza toccare codice
- Ogni nuova feature sviluppata diventa automaticamente un flag gatable

---

## 13. Spunti per il Copy del Sito Web

### Headline Possibili
- "Il tuo sistema di prenotazione. I tuoi clienti. Zero commissioni."
- "Prenotazioni, CRM e marketing in un'unica piattaforma per il tuo ristorante"
- "Smetti di pagare commissioni per coperto. Inizia a possedere i tuoi clienti."

### Pain Points da Comunicare
- "Paghi X euro al mese di commissioni a TheFork? Con Evulery.Pro paghi un fisso e i clienti sono tuoi"
- "I no-show ti costano? Reminder automatici + caparra riducono drasticamente le assenze"
- "Non sai chi sono i tuoi clienti migliori? Il CRM automatico te lo dice senza farti fare nulla"
- "Il martedi sera hai i tavoli vuoti? Le promozioni mirate riempiono le fasce deboli"
- "Il cliente vuole cambiare orario e ti chiama? Con il link magico si gestisce da solo"
- "Ricevi una prenotazione telefonica e perdi 30 secondi a cercare disponibilita? Con la dashboard rapida bastano 5 tocchi"

### Benefici Chiave per il Ristoratore
1. **Possiedi i tuoi clienti** - CRM automatico, non su piattaforma terza
2. **Riduci i no-show** - Reminder + caparra + blacklist
3. **Riempi i tavoli vuoti** - Promozioni + email broadcast
4. **Risparmi tempo** - Il cliente si gestisce da solo (link magico)
5. **Prenotazione rapida** - Prenotazione telefonica in 5 tocchi, disponibilita in tempo reale
6. **Prezzo fisso** - Sai esattamente quanto spendi, zero sorprese

### Il Cliente Finale
Il copy per il cliente finale deve comunicare semplicita:
- "Prenota in 30 secondi"
- "Nessun account richiesto"
- "Modifica o cancella con un click"

---

## 14. Decisioni di Design Architetturale

### Perche Link Magico e Non Account
- La registrazione aggiunge frizione e riduce le conversioni
- Il 90% degli utenti vuole solo prenotare/modificare/cancellare
- L'account puo essere aggiunto in futuro come layer opzionale sopra il link magico
- I dati per il CRM li raccogliamo gia dalla prenotazione

### Perche CRM Tenant-Scoped e Non Globale
- Non siamo un aggregatore - ogni ristorante e indipendente
- I dati dei clienti appartengono al ristoratore
- Semplifica la privacy (GDPR): ogni ristorante e responsabile dei propri dati
- Non creiamo dipendenza dalla piattaforma

### Perche Sconti al Conto e Non Digitali
- Semplicita: nessun coupon code, nessun sistema di voucher
- Il ristoratore applica lo sconto al conto - come farebbe normalmente
- La caparra resta invariata (e un deposito, non il prezzo)
- In futuro si puo evolvere verso voucher digitali se serve

### Perche Email Broadcast Semplice (No Editor)
- I ristoratori non sono marketer - non userebbero un editor complesso
- Un testo pulito con il logo del ristorante e piu efficace di una newsletter graficamente sovraccarica
- Meno complessita = meno bug = piu veloce da implementare
- Si puo evolvere in futuro se emerge il bisogno

---

## 15. Ordini Online — Asporto e Consegna a Domicilio

### Filosofia: Lo Store del Ristorante
Ogni ristorante ha il suo store online (/slug/order) dove i clienti possono ordinare per asporto o consegna. Non e un marketplace — il ristorante controlla i prezzi, le zone di consegna e i tempi.

### Funzionalita Chiave
- **Takeaway + Delivery**: due modalita con configurazione indipendente
- **Zone di consegna per CAP**: costi e minimi differenziati per zona
- **Kanban operativo**: board con colonne Nuovi → Accettati → In preparazione → Pronti
- **Storico con statistiche**: trend ordini, ripartizione tipo/pagamento, top piatti venduti, top clienti
- **Auto-accept**: opzionale, per flussi ad alto volume
- **Slot con capacita**: tempo preparazione + intervallo + max ordini per slot

### Copy per Marketing
- "Il tuo ristorante, anche a casa del cliente"
- "Asporto e consegna, senza commissioni di piattaforma"
- "Gestione ordini in tempo reale con kanban visuale"

---

## 16. Gestione Reputazione — Recensioni Verificate

### Filosofia: Proteggere e Costruire la Reputazione
Il ristoratore ha bisogno di recensioni positive su Google per attirare clienti, ma non puo permettersi recensioni negative ingiustificate. Evulery agisce come filtro intelligente tra la visita e la recensione pubblica.

### Come Funziona
1. Il cliente viene segnato come "Arrivato" nella dashboard
2. Dopo un delay configurabile (1-4 ore), parte una email automatica con richiesta di feedback
3. Il cliente valuta l'esperienza su una landing con stelle (1-5)
4. Se la valutazione e alta (es. 4-5 stelle) → viene indirizzato a Google per recensire pubblicamente
5. Se la valutazione e bassa (es. 1-3 stelle) → il feedback resta privato, visibile solo al ristoratore

### Canali di Raccolta
- **Email tracciata**: invio automatico post-visita, con tracking apertura e click
- **QR/NFC/Embed anonimo**: link diretto alla landing, per tavoli, scontrini, biglietti

### Conformita Legge 34/2026 (vigente dal 7 aprile 2026)
Le nuove norme sulle recensioni online impongono:
- La recensione deve provenire da chi ha **effettivamente usufruito** del servizio
- Deve essere pubblicata entro **30 giorni** dalla fruizione
- Non deve essere legata a **sconti, vantaggi o utilita**
- E considerata autentica se corredata di documentazione fiscale

**Evulery e nativamente conforme:**
- Le richieste partono solo da prenotazioni con status "arrivato" (fruizione verificata)
- L'invio avviene entro poche ore dalla visita (ampiamente nei 30 giorni)
- Nessun incentivo offerto al cliente per la recensione
- Ogni richiesta e tracciata: cliente → prenotazione → review request → feedback

**Argomento commerciale forte**: il ristoratore che usa Evulery puo dimostrare che le sue recensioni sono conformi alla legge, un vantaggio competitivo rispetto a chi gestisce le recensioni in modo informale.

### Copy per Marketing
- "Le tue recensioni sono a norma di legge. Ogni feedback e tracciato e verificabile."
- "Proteggi la tua reputazione: i clienti felici recensiscono su Google, gli insoddisfatti parlano con te."
- "Conforme alla Legge 34/2026 sulle recensioni verificate."
- "Il 77% dei consumatori legge le recensioni prima di scegliere un ristorante. Assicurati che le tue siano autentiche."

---

## 17. Gestione Tavoli e Sala Virtuale

### Filosofia: dalla lista alla mappa visuale
La gestione tavoli avanzata e' pensata per ristoranti che vogliono ottimizzare lo sfruttamento dei posti senza perdersi tra fogli excel. Tre livelli di profondita': lista, auto-assegnazione, mappa operativa.

### Funzionalita' Chiave
- **Anagrafica tavoli**: nome, capacita' elastica (min/max), area, forma, combinazioni con altri tavoli
- **Auto-assegnazione**: il sistema sceglie il tavolo piu' calzante (fit-primario) tra quelli liberi nella fascia richiesta, rispettando priorita' configurabile via drag
- **Stati del tavolo**: jolly (solo manuale, escluso dal widget pubblico), bloccato (con motivo + lucchetto), archiviato (nascosto da sala ma storico preservato)
- **Mappa Setup**: drag&drop delle posizioni reali per riprodurre il layout del locale
- **Mappa Operativa**: vista live con scorri-orari, palette colori per stato (libero / confermato / arrivato / jolly / bloccato), popup dettaglio per riassegnazione manuale
- **Capacita' elastica min/max**: tavolo 2-4 posti accetta gruppi da 2, 3 o 4 persone; fit-primario sceglie il tavolo con meno posti sprecati
- **Combinazioni tavoli**: coppie configurabili per gruppi grandi (oltre cura tavoli singoli); N tavoli deferito a fase futura

### Copy per Marketing
- "Sala virtuale: trascina i tavoli per riprodurre il tuo locale, il sistema fa il resto."
- "Auto-assegnazione intelligente: il cliente prenota, il tavolo giusto si occupa da solo."
- "Tavolo jolly, bloccato, archiviato: ogni stato controllato senza scartoffie."

---

## 18. Caparra a 4 Livelli

### Filosofia: caparra adatta al business model
Non tutti i ristoranti hanno bisogno della caparra Stripe. Quattro modalita' coprono ogni esigenza, dal piu' semplice al piu' strutturato.

### Le 4 Modalita'
- **Info**: il widget mostra solo un avviso "richiediamo X euro di caparra", senza riscossione digitale. Il ristoratore gestisce il deposito offline.
- **Link**: il widget include un link a un sistema esterno di pagamento (es. PayPal.me). Riscossione manuale ma il cliente vede il riferimento.
- **Stripe** (online): caparra incassata via Stripe Checkout, accreditata sul conto Stripe del ristoratore (chiavi per-tenant cifrate AES-256-GCM).
- **Guarantee** (carta a garanzia): SetupIntent Stripe (`mode=setup`) — la carta del cliente viene registrata come impronta, senza addebito. Il ristoratore puo' addebitare la penale solo in caso di no-show.

### Caparra Condizionale
Configurabile per **giorno della settimana** e **fascia oraria** (meal category). Es: caparra solo nei weekend dopo le 19:00. Default retrocompatibili.

### Copy per Marketing
- "Caparra come la vuoi tu: info, link esterno, online o solo garanzia."
- "Carta a garanzia: pagano solo se non si presentano."

---

## 19. Notifiche Multi-Canale

### Filosofia: il ristoratore deve sapere subito
Quattro canali differenziati e gerarchici per non perdere nulla — email per la tranquillita', campanella in app per il follow-up, push browser per l'urgenza, audio brandizzato per il riconoscimento a colpo d'orecchio.

### I 4 Canali
1. **Email**: per ogni evento (nuova prenotazione, cancellazione, caparra, ecc.) — tutti i piani
2. **Campanella dashboard**: badge + dropdown con storia 30gg — tutti i piani
3. **Push browser** (Professional/Enterprise): notifica nativa anche con browser in background; su iOS richiede PWA installata
4. **Audio brandizzato**: sound logo Evulery diversificato per evento (nuova prenotazione, cancellazione cliente, caparra, ordine, recensione) — riconoscibile a distanza

### Filtro Privacy
La cancellazione genera audio solo se da cliente (`cancelled_by='cliente'`), non se dal ristoratore stesso (`'staff'`). Coerenza UX: il sistema non "suona addosso" a chi ha appena cliccato.

### Banner Onboarding
- Banner verde "Attiva notifiche" sulla home → opt-in esplicito (no piu' subscribe on-bell-click che era fragile)
- Banner azzurro iOS PWA install per iPhone Safari non-standalone

---

## 20. Auto-refresh Dashboard (Fase C)

### Filosofia: dashboard live senza rompere l'UX
Il polling silenzioso completo rompe form aperti, scroll, popup. Lo standard di mercato (Gmail, Slack, Linear) e' il banner "X modifiche disponibili", lasciando all'utente il controllo del momento di refresh.

### Architettura
- Endpoint `/dashboard/heartbeat/reservations` e `/dashboard/heartbeat/floor` con ETag/If-None-Match → 304 quando nulla cambia (payload zero)
- Polling 60s con pausa automatica su `document.hidden`
- Backoff esponenziale su errori (cap 5 min)
- Banner contestuale ambra sticky-top: appare solo se l'hash cambia, dismiss persistente

### Pagine coperte
- Home dashboard, Prenotazioni, Sala (modalita' operativa)

---

## 21. Stampa Ordini MVP

### Filosofia: zero dipendenze, massimo valore
Approccio HTML + CSS `@page` print, niente librerie PDF. Apertura in nuova tab, dialog stampa parte automatico, l'utente fa Ctrl+P del browser e stampa su qualsiasi stampante (termica 80mm, 58mm, A4 fallback).

### I 2 Template Contestuali
- **Ticket cucina** (su status `accepted` / `preparing`): sintetico per la brigata. # ordine GIGANTE, articoli con varianti e note in reverse video. **Niente prezzi, telefono, email, indirizzo** (privacy: il ticket gira di mano in mano)
- **Ricevuta cliente** (su status `ready`): completa con tutti i dati. Footer dinamico `tenants.website_url` con fallback widget Evulery. Sezione staccabile ✂ "Conferma di consegna" per la firma del cliente (solo delivery)

### Plugin Print Pro (futuro a pagamento)
Auto-print via PrintNode/Star CloudPRNT, editor template visuale, multi-template (cliente/cucina/quality check), multi-stampante per device. Cliente pilot identificato: D'Istinto Sushi (oggi su GloriaFood).

---

## 22. Rider Management

### Filosofia: anagrafica leggera + statistiche concrete
Per ristoranti con delivery in-house e 2-5 rider, niente login dedicati (la board pubblica condivisa basta) ma serve sapere chi consegna cosa, in quanto tempo, con quale valore gestito.

### Funzionalita' MVP
- CRUD anagrafica rider (nome, telefono opzionale, colore badge da palette di 8, attivo/archiviato)
- Assegnazione ordini delivery via popup nella card kanban
- Board pubblica `/delivery/{token}` mostra nome rider accanto a ogni ordine
- Pulsante "Consegnato" disabilitato senza rider assegnato (forza dati puliti per stats)
- Statistiche per rider: consegne totali, tempo medio consegna (da `rider_assigned_at` a `updated_at`), valore gestito, % completate
- Range stats: oggi (default), mese corrente, 7gg, 30gg, custom

### Da non confondere con
"Rider App Companion" (plugin futuro): app Android dedicata con notifiche push individuali, login proprio. Solo se 5+ rider per ristoratore.

---

## 23. Vetrina Digitale (Hub)

### Filosofia: link-in-bio brandizzato Evulery
Una pagina pubblica del ristorante che raggruppa in una sola schermata tutte le azioni del cliente: prenotare tavolo, vedere menu, ordinare online, lasciare recensione, scoprire promozioni, contattare su WhatsApp, vedere mappa.

### Use Case
- Link nella biografia Instagram (sostituisce LinkTree)
- QR code stampato sui tavoli, biglietti da visita, volantini
- Smartphone-first: pagina ottimizzata per tap

### Personalizzazione
6 palette predefinite + 8 azioni preset + custom (Enterprise). QR code generato client-side, niente trafffico server.

---

## 24. Programma Reseller B2B

### Filosofia: pipeline commerciale parallela
Oltre alla vendita diretta, Evulery permette ai reseller (consulenti, agenzie web) di portare ristoratori e guadagnare commissioni configurabili per-reseller.

### Architettura Commissioni (v3 2026-05-11)
- Tabella `reseller_profiles` con 4 commissioni configurabili: setup default 130€, starter 120€, professional 200€, enterprise 320€
- Snapshot reseller al momento conversione lead→tenant (`tenants.acquired_by_reseller_id`)
- Setup 249€ al cliente + sconti annuale 12% / semestrale 5% off-site
- Crediti ricarica email broadcast con approvazione admin

### Materiali Commerciali
3 strumenti interattivi HTML in `sales/`: demo script con cronometro, ROI calculator (live, no numeri inventati), FAQ ristoratore per follow-up.

---

## 25. Marketplace Plugin (strategia commerciale 2026-06-05)

### Filosofia: secondo flusso di ricavi via addon
Le feature avanzate ad alto costo dev ma utilita' niche entrano come plugin attivabili (es. Print Pro, Loyalty Pro, Analytics Pro, WhatsApp Notifications, Riders App Companion). I MVP core restano gratuiti nei piani.

### Criteri Addon vs Core
- < 30% utilizzo previsto → addon
- Richiede setup/onboarding dedicato → addon
- Costo runtime su servizi terzi (PrintNode, ecc.) → addon
- Verticale di segmento → addon

### Primo Plugin Candidato
**Evulery Print Pro** (~15-20h dev): auto-print PrintNode + editor template visuale + multi-template + printing history. Pricing ipotesi €19/mese per device. Cliente pilot identificato.

---

## 26. Service Gating Architettura

### Filosofia: piani come bundle di servizi attivabili
Ogni feature avanzata e' un servizio key (`digital_menu`, `promotions`, `custom_domain`, `export_csv`, `email_reminder`, `deposit`, `statistics`, `email_broadcast`, `online_ordering`, `review_management`, `table_management`, `push_notifications`). I piani Starter/Professional/Enterprise mappano insiemi di servizi.

### Pattern di Gating
- Helper `tenant_can('service_key')` per check sicuro
- Controller: `gate_service('service_key')` redirect a dashboard se bloccato
- Sidebar: voce nascosta o lucchetto se non disponibile
- API: 403 con messaggio chiaro
- Public pages: errore + CTA "Funzionalita' non disponibile"
- Cron: skip silenzioso

### UX Lock Card
Partial `views/partials/service-locked.php` per coerenza visiva: tutte le pagine gatate mostrano lo stesso pattern (icona, nome servizio, CTA contatta supporto).

---

*Documento aggiornato al 6 Giugno 2026*
*Storico iniziale: 7 Aprile 2026. Sezioni 17-26 aggiunte 2026-06-06 per allineare con stato implementazione.*
