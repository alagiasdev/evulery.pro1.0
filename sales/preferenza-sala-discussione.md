# Gestione preferenza sala — discussione cliente + benchmark

> Documento di lavoro per i primi colloqui con i ristoratori (giugno 2026).
> Idea emersa dai primi 3-4 incontri: molti ristoratori hanno più sale
> (interno / giardino / terrazza / sala camino) e i clienti chiedono
> spesso una preferenza in fase di prenotazione. Come la gestiamo senza
> imbrattare il widget esistente?

---

## 1. Il problema operativo

In primavera/estate i clienti vogliono il giardino; in inverno la sala
camino. Aggiungere uno step nel widget di prenotazione tipo *"dove
preferisci sederti?"* rischia di scoraggiare chi prenota di fretta dal
telefono (perdita di conversione).

Servono soluzioni che:

1. Non aggiungano attrito al widget esistente (già testato e raffinato)
2. Permettano al cliente di esprimere la preferenza in modo naturale
3. Gestiscano l'aspettativa cliente senza promesse non mantenibili
4. Diano al ristoratore visibilità delle preferenze in dashboard

---

## 2. I 4 approcci possibili

### A — Preferenza morbida (nota interna)
Il cliente scrive *"preferirei giardino"* nella nota libera. Il sistema
**non considera** la preferenza nella disponibilità — accetta come oggi.
Il ristoratore legge la nota e cerca di onorarla in fase di assegnazione.

- **Pro**: zero rischio "sembrare pieno", zero dev
- **Contro**: aspettativa cliente non gestita, conflitti al servizio
- **Quando usare**: ristoranti dove la sala esterna è "extra" non garantito

### B — Filtro rigido per area
Il cliente sceglie l'area come parte della prenotazione. Disponibilità
calcolata **solo** sui tavoli di quell'area. Se piena, slot non
disponibile anche se altre sale hanno tavoli liberi.

- **Pro**: zero conflitti, cliente ottiene esattamente quello che ha chiesto
- **Contro**: cliente vede "tutto occupato alle 20:30" quando in realtà ci sono 8 tavoli liberi in sala interna. Si perdono prenotazioni.
- **Quando usare**: ristoranti dove le sale sono esperienze nettamente diverse (es. ristorante con terrazza panoramica a parte)

### C — Ibrida con fallback guidato
Cliente sceglie area preferita → sistema mostra disponibilità di quell'area.
Se piena, banner: *"Nessun tavolo in giardino alle 20:30. Vuoi vedere altri
orari in giardino, oppure provare in sala interna?"* → click → ricalcola.

- **Pro**: cliente guidato, ristorante non perde prenotazioni
- **Contro**: UX a 2 step, più dev
- **Quando usare**: caso medio (2-3 sale, sala esterna preferita ma non esclusiva)

### D — Capacità per area (avanzato)
Ristoratore configura capacità per sala + fascia oraria. Es: *"Giardino
max 20 coperti contemporanei alle 20-22"*. Sistema gestisce capacità
per area separatamente dal totale.

- **Pro**: granulare, gestisce vincoli cucina/staff
- **Contro**: alta complessità configurazione
- **Quando usare**: ristoranti grandi (>120 coperti) con problemi di flusso

---

## 3. L'idea che ci piace di più: "Keyword Matching" (ibrida invisibile)

**Da brainstorming con Stefano, giugno 2026.**

Il ristoratore configura — per ogni area — delle **parole chiave**.
Es. per "Sala Esterna": *giardino, esterno, fuori, all'aperto, tavolo
fuori, terrazza*.

Quando un cliente prenota e nella nota scrive *"vorrei un tavolino in
giardino se possibile"*, il sistema riconosce la parola "giardino" e prova
ad assegnare automaticamente un tavolo della sala esterna. Il cliente
non vede niente di nuovo nel widget; l'esperienza resta quella attuale.

### Per il ristoratore
- Configura le keyword una sola volta (~5 min per setup iniziale)
- In dashboard vede un'etichetta accanto alla prenotazione:
  *"Cliente preferisce: Giardino"*
- Se il giardino è pieno, sistema mette il cliente in altra sala e
  segnala chiaramente: il ristoratore può decidere se chiamare o tenere

### Per il cliente
- Nessun nuovo step nel widget
- Nell'email di conferma trova: *"Hai indicato preferenza per il giardino —
  faremo il possibile per sistemarti all'esterno, ma il posto non è
  garantito al 100%."*

### Vantaggi sintetici

1. **Zero attrito**: widget invariato, conversione protetta
2. **Esperienza naturale**: cliente si esprime come parla
3. **Sistema invisibile**: non sembra "tecnologico", sembra gestione personale
4. **Scalabile**: funziona uguale per sala camino, fumatori, riservato, ecc.
5. **Trasparenza**: la frase "non garantito" riduce conflitti al servizio
6. **Originale**: nessun competitor lo fa così (vedi §5)

### Limiti onesti

1. **Possibile overbooking sala richiesta**: 12 "preferenza giardino" su 8
   tavoli esterni → 4 dentro. Gestito da avviso email.
2. **Pioggia / chiusura sala**: riassegnazione manuale dal pannello.
   In v2 si potrebbe aggiungere *"Sala chiusa oggi"* automatico.
3. **Falsi positivi rari**: cliente scrive *"siamo del fuori sede"* → keyword
   "fuori" matcha. Mitigato con word boundary, accent normalization,
   negazione ("no [keyword]", "non [keyword]").
4. **Sala specifica garantita non possibile**: per casi VIP (anniversario,
   compleanno) il ristoratore deve gestire manualmente.

### Estensioni utili (4 add-on che valgono il piccolo extra di dev)

1. **Trasparenza email cliente**: aggiunge frase "non garantito" se è
   scattata una keyword. Zero attrito, gestisce aspettativa.
2. **Badge ristoratore in dashboard**: visibilità immediata della
   preferenza cliente, anche se sistema ha assegnato altra sala.
3. **Match robusto**: case + accenti + word boundaries + negazione.
4. **Modello dati estensibile**: nuova tabella `tenant_areas` con
   stagionalità (`season_start`, `season_end`) e flag attivazione/disattivazione.
   30 minuti di dev in più → 6 mesi di feature future "gratis".

---

## 4. Decisioni ancora da prendere

Prima di sviluppare servono risposte a queste 5 domande:

1. **Modello dati**: tabella `tenant_areas` (estensibile, +30 min dev) o
   campo JSON sui tenants (rapido, ma rigido)?
2. **Negazione "no/non [keyword]"**: implementiamo subito o annota per v2?
   (5 minuti in più)
3. **Dashboard manuale**: il match si applica anche alle prenotazioni
   create dal ristoratore in dashboard (es. quando trascrive una telefonata)?
4. **Frase "non garantito" nell'email**: solo se keyword scatta, oppure
   sempre in stagione per area "giardino"?
5. **Stagionalità**: campo `is_active` manuale per ora, oppure
   `season_start`/`season_end` automatici dall'inizio?

---

## 5. Benchmark competitor (per il colloquio)

I leader di mercato si dividono in due pattern dominanti:

### Pattern A — Solo note libere
**Chi**: TheFork, Quandoo, la maggior parte degli europei.

Il widget non ha campo "preferenza sala". Cliente scrive in nota libera.
Ristoratore assegna manualmente.

- **Pro per piattaforma**: widget pulito, conversione alta
- **Contro per ristoratore**: legge ogni nota a mano, errori frequenti
  nelle serate piene
- **Garanzia al cliente**: nessuna, gestione interamente manuale

### Pattern B — Dropdown seating preference
**Chi**: OpenTable, Resy, SevenRooms (US in genere).

Widget con dropdown opzionale *"Seating preference: Indoor / Outdoor /
Bar / Patio / Window"*. Cliente sceglie, sistema mostra disponibilita'
nella sezione.

- **Pro**: esperienza guidata
- **Contro**: step extra nel flusso. In US la cultura accetta "preference
  not guarantee", in Italia il cliente medio legge "scelto giardino" come
  "garantito giardino" → conflitti
- **Garanzia**: variabile. SevenRooms (premium) ha capacità per sezione.

### Confronto con la nostra idea

| Aspetto                         | TheFork-style | OpenTable-style | **Evulery (keyword)** |
| ------------------------------- | ------------- | --------------- | --------------------- |
| Cambio nel widget cliente       | Nessuno       | +1 step         | Nessuno               |
| Sistema capisce intento cliente | No (manuale)  | Si (esplicito)  | Si (auto da nota)     |
| Lavoro ristoratore              | Alto          | Basso           | Basso                 |
| Gestione aspettativa cliente    | Nessuna       | Implicita       | Esplicita ("non garantito") |
| Configurazione iniziale         | Zero          | Lista sezioni   | ~5 min keyword        |
| Rischio overbooking sezione     | Alto          | Basso           | Alto (accettato)      |

La nostra idea prende **il meglio del pattern A** (zero attrito widget,
esperienza naturale) e aggiunge **intelligenza del pattern B** (sistema
capisce dove vuole stare il cliente), risolvendo il punto debole di
entrambi (TheFork = lavoro manuale; OpenTable = step extra).

**Per quanto ne so, nessun competitor usa esattamente questo pattern.**
È una mossa originale.

> Nota onestà: data del benchmark riferita a fine 2024 / inizio 2025.
> Non escludo che qualcuno abbia introdotto qualcosa di simile nei
> 12-18 mesi successivi che non conosco. Verifica al momento del lancio.

---

## 6. Frasi pronte per i colloqui

### Davanti a un ristoratore che usa TheFork
> "Quante note al giorno leggi prima di assegnare un tavolo? E quante
> volte ti dimentichi la preferenza scritta dal cliente in nota? Con
> noi il sistema lo fa al posto tuo, automaticamente, senza che tu debba
> ricordartelo o leggere ogni nota."

### Davanti a un ristoratore che ha valutato OpenTable / sistemi premium
> "OpenTable fa scegliere al cliente nello step di prenotazione, ma il
> cliente medio italiano si confonde con troppi step. Da noi resta tutto
> semplice nella nota libera, ma il sistema capisce la preferenza."

### Davanti a un ristoratore scettico sul tech
> "Niente di nuovo per i tuoi clienti — scrivono come hanno sempre fatto.
> Sei tu che vedi un'etichetta in più che ti aiuta a non dimenticarti
> chi voleva fuori."

---

## 7. Domande da fare durante i colloqui

Per capire se vale la pena sviluppare ADESSO o DOPO il lancio:

1. Quante volte alla settimana ti capita una richiesta esplicita
   *"voglio fuori"* o *"voglio sala camino"*?
2. Oggi come la gestisci? (Note a parte, calendario, memoria, telefono)
3. Se il sistema mette il cliente in sala interna pur avendo chiesto
   fuori, è un problema serio per te, o gestibile con una telefonata
   in anticipo?
4. Quanto tempo dedicheresti la prima volta per configurare 5-6 parole
   chiave per ogni sala?
5. C'è qualcuno che non accetta mai un tavolo "non garantito" (es. ti
   chiama prima per assicurarsi)? Quanto frequente?

---

## 8. Annotazioni tecniche (uso interno)

Quando si svilupperà, ecco lo scope MVP:

- **Migration**: nuova tabella `tenant_areas` (id, tenant_id, name, keywords TEXT, is_active, sort_order)
- **Modello**: classe `Area` + service `AreaKeywordMatcher`
- **TableAssigner**: parametro opzionale `?string $preferredArea`, filtro pre-fit
- **Integrazione**: `ReservationApiController::store` + `ReservationsController::store` chiamano matcher prima di autoAssign
- **UI dashboard**: Settings → Tavoli → nuova sezione "Aree e parole chiave"; scheda prenotazione mostra badge preferenza rilevata
- **Email**: estensione `MailService::sendReservationConfirmation` con frase condizionale

Stima: ~4-5 ore dev, no migration data destructive, retrocompatibile.

**Trigger di sviluppo**: 3+ ristoratori intervistati confermano interesse
+ disponibilità a configurare le keyword. Sotto questa soglia, rimanere
nel pattern A (nota libera) basta.

---

*Ultimo aggiornamento: 2026-06-10 · documento interno per i primi
colloqui commerciali Early Adopter.*
