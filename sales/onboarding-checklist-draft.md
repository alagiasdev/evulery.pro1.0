# DRAFT — Checklist Onboarding Ristoratore (rev. 2026-07-03)

> Questo è il draft da rivedere/modificare. Quando lo approvi, lo porto nell'HTML `sales/onboarding-checklist.html`.
> **Modifiche rispetto alla versione attuale:** rimosse Fase 0 (Prima dell'incontro) e Fase 1 (Creazione account); fasi rinumerate 1→6; ogni fase più dettagliata e passo-passo; nuova FASE 4 Marketing; sezioni Tavoli / Ordini / Reputazione / Vetrina espanse; rimossi i 2 bullet reseller nella chiusura; "Errori comuni" ampliata.

---

## Intro (testo in alto alla pagina)

**Checklist Onboarding Ristoratore**
Guida passo-passo per portare un cliente da **account appena creato** a **widget di prenotazione attivo** e ristoratore autonomo. L'account lo crea l'**admin** (fuori da questa checklist): qui si parte dal **primo accesso**. Pensata per l'affiancamento col reseller. Le spunte si salvano sul tuo browser.

---

## FASE 1 — Setup essenziale  ·  *I passi senza cui il widget non funziona*  ·  [RISTORATORE]

- [ ] **Primo accesso**: il ristoratore apre la dashboard e fa login con **email + password del titolare** (le credenziali consegnate dall'admin). Al primo accesso conviene cambiare la password da **Profilo**.
- [ ] **Impostazioni → Generali** ★
  - Controlla **nome, telefono, indirizzo, email del ristorante** (l'email è quella che riceverà le notifiche di prenotazione: verifica che sia corretta e attiva).
  - Carica il **logo** (JPG/PNG/WebP): compare nel widget e nelle email → look professionale.
  - Imposta lo **Step di prenotazione** (ogni quanti minuti parte uno slot: 15/30/60). È il parametro più delicato: **deve combaciare con gli orari** che imposterai (vedi sotto).
  - Imposta la **Durata tavolo** (quanto resta occupato un tavolo per prenotazione): incide su quanti coperti restano liberi negli slot successivi.
- [ ] **Impostazioni → Categorie Pasto**
  - **Attiva** le fasce che il locale usa (Pranzo, Cena, Aperitivo…) e **disattiva** quelle che non usa.
  - Controlla che gli **orari di inizio/fine** di ogni fascia coprano gli slot di quella fascia (una Cena 19:00–23:30 deve includere gli slot serali).
- [ ] **Impostazioni → Orari e Coperti** ★
  - Compila la griglia **giorni × orari** con i **coperti massimi per slot**.
  - Usa **"Compila tutti"** per impostare in un colpo un valore su tutta la griglia, poi ritocca le eccezioni.
  - Verifica che gli orari siano **allineati allo step** (step 30 → orari :00/:30; step 15 → :00/:15/:30/:45).
  - **Chiusura settimanale:** qui NON c'è un interruttore "giorno chiuso". Un giorno è chiuso quando **non ha slot/coperti**: lascia **vuota** la riga di quel giorno (0 coperti) e il widget non accetterà prenotazioni in quella giornata (es. lunedì di riposo).
- [ ] **Impostazioni → Chiusure**
  - Serve **solo per date specifiche**: **ferie** e **chiusure straordinarie** (giorni singoli o periodi — es. Natale, ponte, evento privato).
  - **Non** gestisce il giorno di riposo settimanale ricorrente: quello si imposta lasciando il giorno vuoto in *Orari e Coperti* (vedi sopra).

> 💡 **Regola d'oro:** *step e orari devono combaciare.* Step 30 min + orari a :00/:30. Se non torna, il widget mostra pochi o nessuno slot — è l'errore più frequente.
> 💬 **Da dire al cliente:** "Con questi passi sei già online: le persone possono prenotare h24, anche mentre sei in cucina."

---

## FASE 2 — Servizi del piano  ·  *Configura ciò che è incluso*  ·  [RISTORATORE]

- [ ] In **Impostazioni**, le voci con il **🔒 lucchetto** NON sono nel piano del cliente → **salta** (è un'occasione di upsell, non da configurare).
- [ ] **Caparra** (se inclusa): scegli il **tipo** (informativa / link / Stripe / carta a garanzia), l'**importo**, **da quante persone** scatta, e gli eventuali **giorni/fasce** in cui richiederla.
- [ ] **Menu digitale**: crea o **importa** categorie e piatti; aggiungi **prezzi** e **allergeni**; volendo, la **carta dei vini** e le voci "in evidenza".
- [ ] **Promozioni** (se incluse): sconti **ricorrenti** o su **date/fasce** specifiche.
- [ ] **Notifiche**: attiva le **email** al ristorante e, sul dispositivo di sala, le **push del browser** (vedi Fase 5).

### Gestione Tavoli  *(servizio avanzato)*
- [ ] **Crea i tavoli** della sala (nome/numero e **capacità**). La capacità è **elastica**: imposta **min–max coperti** per tavolo (es. un tavolo da 2 che al bisogno regge 4).
- [ ] Definisci per ogni tavolo se è:
  - **Prenotabile online** → il widget può **assegnarlo in automatico** alle prenotazioni.
  - **Solo manuale ("jolly")** → resta **fuori dall'auto-assegnazione** del widget: lo assegni tu a mano (bordo ambra tratteggiato). Utile per tavoli speciali o tenuti "di scorta".
  - **Bloccato** → temporaneamente **fuori uso** (con motivo), non assegnabile finché non lo sblocchi.
  - **Archiviato** → rimosso dalla sala; lo storico delle prenotazioni passate resta.
- [ ] Imposta la **priorità** di assegnazione (quali tavoli riempire prima) e i **tavoli combinati** (coppie che si uniscono per i gruppi grandi).
- [ ] Disegna la **mappa sala**: vista **Setup** (disponi i tavoli) e vista **Operativa** (assegni/segui i tavoli durante il servizio).

### Ordini online — asporto & consegna  *(servizio avanzato)*
- [ ] Scegli la **modalità**: **Solo asporto**, **Solo consegna** o **Asporto + Consegna**.
- [ ] Imposta il **tempo di preparazione** e, per la consegna, le **zone di consegna** (con eventuale **costo** e **ordine minimo** per zona).
- [ ] Gestisci gli ordini in arrivo dalla **board kanban** (per stato: nuovo → in preparazione → pronto → consegnato).
- [ ] **Rider (fattorini)**: crea i rider (nome, telefono, colore), **assegnali** agli ordini di consegna e consulta le **statistiche rider** (ordini del mese per fattorino).
- [ ] **Stampa** ordine su **stampante termica**: **ricevuta cliente** e **comanda cucina**.
- [ ] **Area riservata rider**: pagina dedicata (accesso con **PIN**) da cui il fattorino gestisce le proprie consegne dal telefono, senza entrare in dashboard.

### Gestione Reputazione  *(servizio avanzato)*
- [ ] Attiva le **richieste di recensione** post-visita.
- [ ] Scegli i **canali**: **email tracciata** (inviata al cliente dopo la prenotazione) e/o **QR/NFC anonimo** (da esporre al tavolo).
- [ ] **Filtro sentimento**: chi è soddisfatto viene indirizzato alla recensione pubblica (Google); chi non lo è viene raccolto come **feedback privato** al ristoratore.
- [ ] **Personalizza il testo** dell'email di richiesta recensione.

### Collaboratori (Staff)  *(se incluso nel piano)*
- [ ] Crea gli **account per i collaboratori** del locale da **Impostazioni → Collaboratori** (visibile solo al titolare).
- [ ] Il **numero massimo** di collaboratori dipende dal piano.
- [ ] Lo staff ha **accesso operativo limitato**: gestisce prenotazioni/servizio, **clienti in sola lettura**; **non** accede a soldi, impostazioni, comunicazioni e marketing.

> 💬 **Upsell naturale:** "Le voci col lucchetto sono funzioni del piano superiore — quando vorrai la caparra, la gestione tavoli o gli ordini online, le attiviamo in un attimo."

---

## FASE 3 — Widget & Vetrina  ·  *Mettere il locale "in vetrina"*  ·  [RISTORATORE]

- [ ] Copia il **link pubblico di prenotazione** del locale (lo trovi in dashboard / Impostazioni).
- [ ] Per il **sito web**: usa il **codice embed** (iframe) — vedi **Guida → Widget online**.

### Vetrina Digitale  *(hub del locale)*
- [ ] Attiva la **Vetrina Digitale** da **Impostazioni → Vetrina Digitale**: è la **mini-pagina/hub** del locale che raccoglie tutto (prenota, menù, ordina, contatti).
- [ ] A cosa serve, da spiegare al cliente: è il **link unico** da mettere nella **bio dei social**, sul **Profilo Google (Business)**, su **WhatsApp**; perfetta **per chi non ha un sito**.
- [ ] **Personalizzala**: **immagine di copertina**, **colori** del brand e (con **Enterprise**) **link extra** verso altri canali del locale.
- [ ] Genera il **QR code** della Vetrina/prenotazione e stampalo (tavoli, vetrina, menu cartaceo).

- [ ] **Pubblica il link** su tutti i canali: **sito**, scheda **Google (Profilo dell'attività)**, **Instagram/Facebook** (bio e post), **stato/broadcast WhatsApp**.
- [ ] **TEST INSIEME** ★: aprite il link, fate una **prenotazione di prova reale** e verificate che compaia **in dashboard** (e che arrivi l'email di notifica al ristorante).

> 💡 Il test dal vivo è il momento **"wow"** dell'onboarding: il cliente vede la prenotazione comparire in tempo reale e l'email di notifica arrivare. **Non saltarlo.**

---

## FASE 4 — Marketing  ·  *Farsi trovare e far tornare i clienti*  ·  [RISTORATORE]  *(Professional / Enterprise)*

- [ ] **Generatore link tracciati** (Impostazioni → Marketing): crea link con tracciamento scegliendo **destinazione** (Vetrina, Prenota, Menù, Ordina, il tuo Sito) e **canale** (Meta/Facebook, Instagram bio, Google Ads, TikTok, Volantino QR, Google Business, Newsletter…). Incolli il link nell'annuncio o nella bio.
- [ ] **Statistiche / Provenienza**: le prenotazioni e gli ordini che arrivano da quei link vengono **attribuiti** al canale/campagna, così vedi **quale canale porta davvero clienti**.
- [ ] **Comunicazioni email ai clienti**: invii mirati per **compleanni del mese** o per **tipologia di cliente** (nuovo, abituale, VIP…). Nota bene: **massimo 1 invio al giorno** e il servizio funziona **a crediti** (si ricaricano).

> 💬 **Da dire al cliente:** "Non serve indovinare dove investire: coi link tracciati vedi nero su bianco quale canale ti riempie la sala."

---

## FASE 5 — Formazione operativa  ·  *Come si usa ogni giorno*  ·  [RISTORATORE]

- [ ] **Dashboard**: la panoramica del giorno (coperti attesi, prossimi arrivi, stati).
- [ ] **Prenotazioni**: il ciclo di vita di una prenotazione (**in attesa → confermata → arrivato / no-show**) e le azioni rapide; come crearne una **a mano** (telefono/walk-in).
- [ ] **Sala** (piano completo): mappa, fasce di servizio, **assegnazione tavoli**.
- [ ] **Notifiche push**: attivale **sul dispositivo che userà in servizio** (telefono/tablet di sala), non solo sul PC dell'ufficio — così gli avvisi arrivano dove serve.
- [ ] Mostra la **Guida in-app** (voce "Guida" nella sidebar): il cliente è **autonomo** per i dubbi, non serve ripetere tutto a voce.

---

## FASE 6 — Chiusura & follow-up  ·  *Consegna e continuità*  ·  [RESELLER]

- [ ] **Consegna** al cliente: **credenziali** di accesso, **link del widget**, **QR code**.
- [ ] Verifica che il cliente sappia **dove trovare la Guida** e **come contattare il supporto**.
- [ ] Fissa un **follow-up** (es. dopo **7 giorni**) per dubbi, ottimizzazioni e primi risultati.

---

## Errori comuni da evitare  *(ampliata)*

- **Orari non allineati allo step.** Step 30 min ma orari a :15/:45 → il widget mostra pochi o nessuno slot. Step e orari devono combaciare.
- **Categorie pasto disattivate.** Se la fascia (es. Cena) è disattivata, gli slot di quell'orario non compaiono nel widget anche coi coperti impostati. Attiva le fasce che si usano.
- **Fasce con orari sbagliati.** Se il range della fascia non copre gli slot (es. Cena impostata 20:00–22:00 ma ci sono slot alle 19:30/22:30), quegli slot restano "scoperti". Allarga il range della fascia.
- **Coperti a zero.** Uno slot con 0 coperti non è prenotabile. Usa "Compila tutti" per partire veloce.
- **Giorno di chiusura non impostato dove va.** Il riposo settimanale si imposta **lasciando il giorno vuoto in Orari e Coperti** (non in "Chiusure", che serve solo per ferie e date straordinarie). Le ferie/chiusure straordinarie vanno invece in **Chiusure**.
- **Email del ristorante sbagliata o non controllata.** È l'indirizzo che riceve le notifiche di prenotazione: se è errato o inattivo, il ristoratore non vede arrivare le prenotazioni via mail. Verificala e fai il test.
- **Notifiche attivate sul dispositivo sbagliato.** Le push vanno attivate sul telefono/tablet che sta **in sala**, non sul PC dell'ufficio, altrimenti gli avvisi non arrivano dove serve.
- **Durata tavolo non realistica.** Troppo lunga → libera pochi slot (sembra sempre pieno); troppo corta → rischio sovrapposizioni. Impostala sul tempo medio reale del servizio.
- **Tavolo "jolly" scambiato per bloccato.** Un tavolo **solo manuale (jolly)** è utilizzabile, ma il widget non lo auto-assegna; un tavolo **bloccato** è fuori uso. Non confonderli quando la sala sembra "piena".
- **Promettere servizi non nel piano.** Le voci col 🔒 lucchetto non sono incluse: è un upsell, non una cosa da configurare. Non prometterle come "già attive".
- **Saltare il test del widget.** Fai sempre una prenotazione di prova reale prima di consegnare: è la prova concreta che "tutto funziona".
- **Widget attivo ma non pubblicato.** Configurare il widget e non mettere il link su sito/Google/social è come avere un negozio senza insegna: nessuno lo trova. Pubblicalo ovunque.

---

## Nota a piè di pagina (invariata)
Il ristoratore ha sempre la **Guida in-app** nella sidebar (voce "Guida"): 19 sezioni con primi passi, configurazione e operatività — usala come riferimento, non serve ripetere tutto a voce.
© {anno} Evulery · by alagias. - Soluzioni per il web
