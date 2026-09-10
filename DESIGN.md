---
version: 1.0
name: Evulery-design-system
description: >
  Gestionale per ristoranti, usato ogni giorno da chi sta in sala e spesso con una
  mano sola sul telefono. La superficie è un pannello chiaro su fondo grigio-azzurro,
  con un unico verde di marca a segnare le azioni e una tavolozza semantica rigida
  per gli stati delle prenotazioni: verde confermato, ambra in attesa, blu arrivato,
  rosso no-show. Nessuna decorazione: ogni colore vuol dire qualcosa, e la densità
  informativa vince sull'aria.

colors:
  brand: "#00844A"
  brand-dark: "#006837"
  brand-light: "#E8F5E9"
  ink: "#1a1d23"
  body: "#495057"
  mute: "#6c757d"
  faint: "#adb5bd"
  hairline: "#e9ecef"
  hairline-strong: "#dee2e6"
  hairline-neutral: "#f0f0f0"   # SOLO superfici calde/pubbliche, vedi "Superfici"
  canvas: "#f5f6fa"
  canvas-reseller: "#f5f6f8"
  surface: "#ffffff"
  surface-soft: "#fafbfc"
  surface-softer: "#f8f9fa"
  sidebar: "#1a1d23"
  admin-accent: "#1565C0"

semantic:
  confirmed: { dot: "#198754", text: "#2E7D32", bg: "#E8F5E9" }
  pending: { dot: "#ffc107", text: "#E65100", bg: "#FFF8E1" }
  arrived: { dot: "#1565C0", text: "#1565C0", bg: "#E3F2FD" }
  noshow: { dot: "#dc3545", text: "#dc3545", bg: "#fde8e8" }
  cancelled: { dot: "#dc3545", text: "#dc3545", bg: "#fde8e8" }
  birthday: { dot: "#D81B60", text: "#880E4F", bg: "#fce4ec" }
  whatsapp: { dot: "#25D366", text: "#ffffff", bg: "#25D366", hover: "#1DA851" }
  warning-bar: { border: "#F57F17", bg: "#FFF8E1", text: "#5D4037" }

typography:
  family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif
  family-mono: ui-monospace, SFMono-Regular, Menlo, monospace
  kpi-xl: { fontSize: 1.45rem, fontWeight: 700, lineHeight: 1 }
  kpi-lg: { fontSize: 1.1rem, fontWeight: 800, lineHeight: 1 }
  title-page: { fontSize: 1.5rem, fontWeight: 800, letterSpacing: -.5px }
  title-card: { fontSize: .95rem, fontWeight: 700 }
  body-lg: { fontSize: .9rem, fontWeight: 400 }
  body-md: { fontSize: .85rem, fontWeight: 400 }
  body-sm: { fontSize: .82rem, fontWeight: 400, color: "{colors.mute}" }
  meta: { fontSize: .78rem, fontWeight: 500, color: "{colors.mute}" }
  label: { fontSize: .72rem, fontWeight: 600, textTransform: uppercase, letterSpacing: .3px }
  label-sm: { fontSize: .68rem, fontWeight: 700, textTransform: uppercase, letterSpacing: .5px }
  micro: { fontSize: .65rem, fontWeight: 600 }

spacing:
  xxs: 4px
  xs: 6px
  sm: 8px
  md: 12px
  lg: 16px
  xl: 20px
  2xl: 24px

rounded:
  xs: 4px
  sm: 6px
  md: 8px
  lg: 10px
  xl: 12px
  2xl: 16px
  pill: 100px          # 999px, 99px e 50px sono alias equivalenti, vedi "Forme"

shadows:
  level-1: "0 1px 3px rgba(0,0,0,.06)"
  level-2: "0 1px 4px rgba(0,0,0,.06)"
  level-3: "0 8px 24px rgba(0,0,0,.12)"

layout:
  sidebar-width: 180px
  sidebar-width-reseller: 220px
  page-padding: "20px 24px"
  page-padding-mobile: "16px"
  grid-gap: 12px
  grid-gap-mobile: 8px

breakpoints:
  lg: 992px
  md: 768px
  sm: 576px

surfaces:
  dashboard:
    audience: ristoratore
    canvas: "#f5f6fa"
    rounded: 12px
    shadow: "0 1px 3px rgba(0,0,0,.06)"
    prefix: "dh- dr- cs- tm- oh- av- notif-"
  booking-widget:
    audience: cliente del ristorante
    canvas: "#FFFFFF"
    surface-soft: "#F8F9FA"
    rounded: 16px
    rounded-sm: 10px
    shadow: "0 4px 24px rgba(0,0,0,0.08)"
    accent-urgency: "#FF6B00"
    text: "#1A1A1A"
    text-muted: "#6B7280"
    prefix: "bw-"
  menu-pubblico:
    audience: cliente del ristorante
    canvas: "#FAFAF8"
    rounded: 14px
    highlight: "#F59E0B"
    text-body: "#374151"
    prefix: "dm-"
  hub:
    audience: cliente del ristorante
    canvas: "#ffffff"
    accent: "#E8F5E9"
    prefix: "hub-"
  ordering:
    audience: cliente del ristorante
    canvas: "#FAF8F5"
    rounded: 14px
    accent: "#E8A317"
    prefix: "os-"
  reviews:
    audience: cliente del ristorante
    star: "#FFC107"
    star-empty: "#e9ecef"
    prefix: "rv-"
  delivery-board:
    audience: rider
    canvas: "#f5f3f0"
    rounded: 14px
    states: { nuovo: "#F9A825", in-corso: "#1565C0", consegnato: "#00844A", ritardo: "#E65100" }
    prefix: "db-"
  reseller:
    audience: rivenditore
    canvas: "#f5f6f8"
    sidebar-width: 220px
    prefix: "rs-"
  admin:
    audience: super admin
    accent: "#1565C0"
    accent-dark: "#0D47A1"
    sidebar-width: 220px
    prefix: "adm- admin-"
  email:
    audience: cliente del ristorante
    container: 600px
    canvas: "#f5f6f8"
    rounded: "10px / 12px"
    style: inline (MailService)

components:
  stat-card:
    description: "Riquadro numerico: icona in box colorato, numero, etichetta. Modello per ogni KPI."
    backgroundColor: "{colors.surface}"
    rounded: "{rounded.xl}"
    padding: "14px 16px"
    shadow: "{shadows.level-1}"
    iconBox: { size: 42px, rounded: "{rounded.lg}", fontSize: 1.15rem }
    value: "{typography.kpi-xl}"
    label: "{typography.label}"
  filter-card:
    description: "Come stat-card ma cliccabile. Bordo sempre presente e trasparente, si colora sull'attivo."
    border: "2px solid transparent"
    borderActive: "2px solid {stato}"
    rounded: "{rounded.xl}"
    padding: "14px 16px"
  status-badge:
    description: "Pillola di stato: sfondo tenue, testo saturo, dalla tavolozza semantica."
    rounded: "20px"
    padding: ".3rem .75rem"
    typography: { fontSize: .78rem, fontWeight: 700 }
  action-pill:
    description: "Azione contestuale in riga: icona + verbo, mai la sola icona."
    rounded: "{rounded.pill}"
    height: 24px
    padding: "0 9px"
    typography: { fontSize: .72rem, fontWeight: 600 }
  alert-bar:
    description: "Avviso in cima alla pagina. Compare solo quando c'è qualcosa da fare."
    borderLeft: "4px solid {semantic.*.dot}"
    rounded: "{rounded.lg}"
    padding: "12px 16px"
  mobile-card:
    description: "Sostituisce la riga di tabella sotto i 768px: avatar, nome, meta, azione."
    rounded: "{rounded.lg}"
    padding: ".6rem .85rem"
    shadow: "{shadows.level-1}"
  empty-state:
    description: "Icona tenue, una riga che spiega cosa comparirà, eventuale azione."
    padding: "60px 20px"
    iconColor: "{colors.faint}"
---


## Panoramica

Evulery è un gestionale che si usa **in servizio**: il telefono in una mano, i coperti
da segnare, il cliente al banco che aspetta. Questo detta tutto il resto. L'interfaccia
non intrattiene, non ha stati d'animo, non usa il colore per decorare: ogni tinta
significa qualcosa e chi la vede deve poterla leggere di sfuggita, con la sala piena.

La superficie è un fondo grigio-azzurro `{colors.canvas}` su cui poggiano pannelli
bianchi con raggio `{rounded.xl}` e un'ombra sottilissima `{shadows.level-1}`: mai
bordi marcati, mai riquadri dentro riquadri. La barra laterale è l'unica zona scura
(`{colors.sidebar}`), stretta 180 px, e su telefono scompare del tutto.

Il verde `{colors.brand}` è l'unico colore di marca e si spende con parsimonia: azioni
principali, valori positivi, elemento attivo nella navigazione. Non esiste un secondo
colore d'accento decorativo — quelli che sembrano accenti (blu, ambra, rosso, rosa)
sono **stati**, e usarli fuori dal loro significato rompe la lettura a colpo d'occhio
di chi in sala guarda solo i pallini.

Le pagine sono dense per scelta: un ristoratore preferisce vedere trenta prenotazioni
senza scorrere che dieci ben arieggiate. La conseguenza è che la scala tipografica vive
quasi tutta **sotto il rem**: `.72rem` e `.82rem` sono le due misure più usate
dell'intero foglio di stile.

---

## Colori

### Marchio
Un solo verde, tre gradazioni. `{colors.brand}` per le azioni e i valori positivi,
`{colors.brand-dark}` per lo stato premuto, `{colors.brand-light}` come fondo tenue di
badge e riquadri-icona.

> **Regola non negoziabile**: il colore delle azioni è verde, mai blu. Il blu appartiene
> allo stato "arrivato" e all'area amministrativa (`{colors.admin-accent}`), che è un
> ambiente diverso e vuole distinguersi a colpo d'occhio dalla dashboard del cliente.

### Superfici
`{colors.canvas}` fondo pagina · `{colors.surface}` pannelli · `{colors.surface-soft}`
e `{colors.surface-softer}` per intestazioni di tabella e righe in evidenza al passaggio
del mouse. I separatori sono `{colors.hairline}`; `{colors.hairline-strong}` solo dove
serve un contorno percepibile, come i campi di un form.

Esiste un **terzo grigio di linea**, `{colors.hairline-neutral}`, e la differenza non è
un capriccio: `#e9ecef` e `#dee2e6` sono grigi **freddi** (il blu è più alto del rosso),
`#f0f0f0` è **neutro puro** (R = G = B). Il neutro appartiene alle superfici calde, che
stanno su fondi avorio e dove un grigio bluastro stonerebbe: widget di prenotazione e
menù digitale, dove ha già un nome (`--bw-border-light`, `--dm-border-light`), e la
board consegne. Nelle superfici fredde — dashboard, admin, vetrina, reseller — la linea
è `{colors.hairline}` e basta.

Fino al 10/09/2026 questa regola non era scritta e il neutro era finito in 103 punti
delle superfici fredde, dove conviveva con `{colors.hairline}` senza alcun criterio: nella
dashboard `.cal-nav button:hover` usava l'uno e `.page-back a:hover` l'altro, per fare
la stessa identica cosa. Sono stati riportati tutti a `{colors.hairline}`. **Su una
superficie fredda, `#f0f0f0` è un errore.**

### Testo
Quattro livelli, non di più: `{colors.ink}` per titoli e numeri, `{colors.body}` per il
testo forte, `{colors.mute}` per tutto il testo secondario — è il colore più usato del
progetto, 199 occorrenze — e `{colors.faint}` per icone spente e testo disabilitato.

### Stati
La tabella `semantic` nel front-matter è la fonte: ogni stato ha un colore per il
puntino, uno per il testo e uno di sfondo per la pillola. Verde confermato, ambra in
attesa, blu arrivato, rosso no-show e annullato, rosa compleanni, verde WhatsApp per
il canale di messaggistica.

> **Debito noto, da riassorbire toccando i componenti.** Lo stato "arrivato" oggi
> compare con quattro blu diversi (`#1565C0`, `#0EA5E9`, `#00838f`, `#0d6efd`) e
> "confermato" con quattro verdi (`#198754`, `#2E7D32`, `#00844A`, `#0a5c36`), a seconda
> che si guardi un puntino, una pillola, un KPI o un'etichetta. Non è una scelta: è
> stratificazione. Chi mette mano a uno di quei componenti adotti i valori della tabella.

---

## Tipografia

### Famiglia
Nessun webfont: si eredita lo stack di sistema di Bootstrap 5.3. È una scelta di
sostanza, non di pigrizia — la dashboard si apre da rete di ristorante, spesso lenta, e
un font che non deve scaricarsi è testo leggibile un secondo prima. Il monospace serve
solo a codici, slug e URL da copiare.

### Gerarchia
| Token | Misura | Uso |
|---|---|---|
| `kpi-xl` | 1.45rem / 700 | Numero grande nelle card statistica |
| `kpi-lg` | 1.1rem / 800 | Numero nei riquadri compatti |
| `title-page` | 1.5rem / 800, tracking −.5px | Titolo di pagina |
| `title-card` | .95rem / 700 | Titolo di un pannello |
| `body-md` | .85rem | Testo corrente |
| `body-sm` | .82rem, colore mute | Descrizioni, sottotitoli |
| `meta` | .78rem | Righe secondarie sotto un nome |
| `label` | .72rem / 600, maiuscoletto | Etichette sotto i numeri |
| `label-sm` | .68rem / 700, maiuscoletto | Intestazioni di tabella |
| `micro` | .65rem / 600 | Badge e pillole |

### Principi
Le etichette sotto i numeri sono **maiuscoletto con tracking .3px**: distinguono il dato
dalla sua didascalia senza aggiungere un colore. I titoli sono in stile frase, mai tutto
maiuscolo. Il grassetto porta il significato dentro una frase (`<strong>` sui numeri che
contano) invece di affidarlo al colore, perché in sala si legge di corsa.

### Lacuna nota
Il foglio di stile contiene **una ventina di misure diverse** fra `.65rem` e `1.45rem`,
molte nate una tantum. I token qui sopra sono le misure realmente ricorrenti: per una
schermata nuova ci si attiene a queste, senza inventare valori intermedi.

---

## Layout

### Spaziature
Base 4 px, come quasi ovunque, ma il progetto le esprime **in rem**: `.25rem` (4) ·
`.35rem` (~6) · `.5rem` (8) · `.75rem` (12) · `1rem` (16) · `1.25rem` (20) · `1.5rem`
(24). Il `gap` più usato in assoluto è `.5rem` (81 occorrenze), seguito da `.35rem`.

- **Padding di pagina**: `{layout.page-padding}` su desktop, `16px` su telefono.
- **Padding dei pannelli**: `1rem 1.25rem` per le card di contenuto, `14px 16px` per le
  card statistica, `.6rem 1rem` per le righe di elenco.
- **Distanza fra card in griglia**: `{layout.grid-gap}`, che scende a
  `{layout.grid-gap-mobile}` sotto i 576 px.

### Contenitori
La dashboard non ha un contenitore a larghezza massima: il contenuto riempie lo spazio
oltre la barra laterale da 180 px. È voluto — su un tablet in cassa si vuole tutta la
tabella, non una colonna centrata con due fasce vuote ai lati.

### Griglie
Le griglie dei KPI partono da 6, 4 o 3 colonne e degradano ai punti di rottura. La
regola vale per tutte:

| Larghezza | Colonne |
|---|---|
| oltre 992 px | 6 / 4 / 3 secondo la pagina |
| ≤ 992 px | 3 |
| ≤ 768 px | 2 |
| ≤ 576 px | 2, con gap ridotto |

### Strategia responsive
Sotto i 768 px la barra laterale esce dal flusso e si apre a scomparsa; le tabelle
**non si comprimono**, vengono sostituite da card (`mobile-card`); le griglie vanno a
capo su due colonne. L'unico scorrimento orizzontale ammesso è quello di ciò che è
intrinsecamente una sequenza — la linea del tempo dell'occupazione in Sala — e comunque
dentro il proprio contenitore, mai a livello di pagina.

---

## Elevazione

Tre livelli, e si usano quasi solo i primi due.

- `{shadows.level-1}` — i pannelli appoggiati sul fondo. È l'ombra del prodotto: 26
  occorrenze, tutto il resto è eccezione.
- `{shadows.level-2}` — barre fisse e intestazioni che scorrono sopra il contenuto.
- `{shadows.level-3}` — solo per ciò che galleggia davvero: dialoghi, menu a comparsa,
  pulsante flottante.

Nessun bordo *e* ombra sullo stesso elemento: o l'uno o l'altra. I pannelli usano
l'ombra, i campi di form il bordo.

---

## Forme

La scala dei raggi va da `{rounded.xs}` a `{rounded.pill}`, ma tre valori coprono quasi
tutto: **8 px** per pulsanti e campi (100 occorrenze), **10 px** per i riquadri-icona e
i contenitori piccoli, **12 px** per le card. Le forme a pillola sono riservate a badge di stato (raggio `20px`, 16 occorrenze) e
azioni contestuali: la forma dice "questo è un'etichetta o un'azione
breve", mai un contenitore.

La pillola nel codice è scritta in quattro modi — `100px` (16 volte), `99px` (5),
`999px` (3), `50px` (1) — e **rendono tutti identici**: basta che il raggio superi la
metà dell'altezza dell'elemento perché i lati diventino semicerchi, e su una pillola
alta 20–44 px qualunque di questi quattro valori lo fa. Non c'è quindi niente da
correggere nel CSS: il token dice `100px` perché è la forma più usata, ma trovare uno
degli altri tre non è un errore. **Per una forma nuova usare `100px`.**

I riquadri-icona sono sempre **quadrati con raggio 10 px**, 42 px di lato nelle card
statistica, con sfondo tenue e icona nel colore pieno dello stesso tema.

---

## Componenti

### Pulsanti
Si usa Bootstrap con il verde di marca sovrascritto. `btn-success` per l'azione
principale della schermata (una sola per pagina), `btn-outline-success` per le
secondarie, `btn-outline-secondary` per le neutre, `btn-outline-danger` per le
distruttive. Sempre con icona a sinistra e **verbo all'infinito o imperativo**: "Invia
auguri", "Importa CSV", "Nuova voce".

Quando in una schermata convivono due canali per la stessa azione, il pulsante dichiara
quale: "Invia auguri **via email**" accanto alla pillola WhatsApp.

### Card e pannelli
`stat-card` per i numeri, `filter-card` quando quel numero è anche un filtro. La
differenza sta solo nel bordo: presente ma trasparente a riposo, colorato quando il
filtro è attivo — così la card non si sposta di due pixel al clic.

### Form
Campi con bordo `{colors.hairline-strong}`, raggio `{rounded.md}`, fondo bianco; al
fuoco prendono il bordo verde e un alone `0 0 0 .25rem rgba(0,132,74,.15)`. Etichette sopra
il campo, in `{typography.label}`. Le schede di dettaglio partono in **sola lettura** e
si sbloccano con "Modifica": evita di cambiare un'email per sbaglio e perdere le
notifiche di un cliente.

> Un campo dentro una griglia vuole `min-width: 0`, altrimenti impone la propria
> larghezza naturale (~180 px) e sfonda il contenitore.

### Navigazione
Barra laterale scura con sezioni in `{typography.label-sm}`, voce attiva in verde pieno.
I badge accanto alle voci indicano **cose che richiedono attenzione**, non totali di
archivio: un numero che non scende mai smette di essere letto. Il badge deve inoltre
aprire esattamente l'elenco che ha contato.

### Elenchi
Gli elenchi della dashboard NON sono `<table>`: sono griglie CSS di righe
(`.cust-row`, colonne fisse + `1fr` sul nome), con intestazione su
`{colors.surface-soft}` in `{typography.label-sm}`, righe da `.6rem 1rem` separate da
una hairline e riga cliccabile con `data-url`. Le tabelle vere restano nelle aree
admin e reseller, con celle da `14px`. Sotto i 768 px la tabella lascia
il posto alle `mobile-card`. Se una tabella resta larga, deve scorrere **dentro il suo
contenitore**, mai trascinare la pagina.

### Componenti caratteristici
- **Barra d'avviso** (`alert-bar`) — bordo sinistro spesso, fondo tenue, comparsa
  condizionata: "3 richieste dal sito da assegnare", "Compleanni di settembre".
- **Pillola d'azione** (`action-pill`) — l'esempio è "Invia auguri" su WhatsApp: icona
  più verbo, e sparisce se il dato non c'è (numero mancante o non valido) invece di
  offrire un pulsante che non funziona.
- **Stato vuoto** — icona tenue, una riga che spiega cosa comparirà lì, ed eventualmente
  l'azione per creare il primo elemento.

---

## Le dieci superfici

Tutto quanto sopra descrive **la dashboard del ristoratore**, che è la superficie più
grande (267 KB di CSS su 429 complessivi). Ma il prodotto ne ha **dieci**, e **sei** le
vede il **cliente del ristorante**, non il nostro cliente: sono quelle su cui si gioca
la reputazione di chi ci paga.

Dieci superfici ma **nove fogli di stile**: la mail transazionale non ne ha uno, il suo
stile è scritto a mano dentro `MailService` perché i client di posta ignorano il CSS
esterno. Da qui la confusione fra i due numeri — dove si legge "nove" riferito ai
*fogli* è corretto, riferito alle *superfici* no.

| Superficie | Chi la vede | Fondo | Raggio | Prefisso |
|---|---|---|---|---|
| Dashboard | ristoratore | `#f5f6fa` freddo | 12px | `dh- dr- cs- tm- oh-` |
| Widget prenotazione | cliente | bianco | **16px** | `bw-` |
| Menù digitale | cliente | `#FAFAF8` caldo | 14px | `dm-` |
| Vetrina (hub) | cliente | bianco | — | `hub-` |
| Ordini online | cliente | `#FAF8F5` caldo | 14px | `os-` |
| Recensioni | cliente | — | — | `rv-` |
| Board consegne | rider | `#f5f3f0` | 14px | `db-` |
| Area reseller | rivenditore | `#f5f6f8` | 12px | `rs-` |
| Area admin | noi | — | 12px | `adm- admin-` |
| Email | cliente | `#f5f6f8`, 600px | 10–12px | inline in `MailService` |

### La regola implicita che le tiene insieme
Il **verde di marca è identico ovunque** — `#00844A`, `#006837`, `#E8F5E9` compaiono con
gli stessi valori in tutti e nove i fogli di stile. È l'unico filo comune, ed è quello
che fa sembrare lo stesso prodotto un widget su un sito esterno e una dashboard.

Tutto il resto cambia secondo il pubblico, con una logica che nessuno aveva scritto ma
che il codice segue con coerenza:

- **Le superfici pubbliche sono più calde e più morbide.** Menù e ordini stanno su fondi
  avorio (`#FAFAF8`, `#FAF8F5`), i raggi salgono a 14–16 px, l'ombra del widget è
  quattro volte più marcata di quella della dashboard (`0 4px 24px` contro `0 1px 3px`).
  Chi prenota da telefono, magari mentre cammina, ha bisogno di superfici generose.
- **Il gestionale è freddo e compatto.** Fondo grigio-azzurro, raggio 12 px, ombra
  minima, testo sotto il rem: densità, perché si legge in servizio.
- **Ogni superficie ha un solo accento oltre al verde**, e serve a una cosa sola:
  arancio `#FF6B00` per l'urgenza nel widget ("ultimi posti"), ambra `#F59E0B` per le
  voci in evidenza nel menù, `#E8A317` per gli ordini, giallo `#FFC107` per le stelle
  delle recensioni, blu `#1565C0` per distinguere l'area admin.

### Regole per superficie

**Widget di prenotazione** (`bw-`) — è il volto pubblico: vive dentro il sito di un
altro, quindi non può ereditare nulla dall'ospite. Font Apple-first
(`-apple-system, BlinkMacSystemFont`), tutto autoconsistente, nessuna dipendenza da
Bootstrap. Toccarlo significa toccare la conversione: ogni modifica va provata a 360 px.
L'asset è **cachato da Cloudflare**, quindi dopo un deploy va purgato.

**Menù digitale** (`dm-`) — si legge al tavolo, spesso con poca luce e una mano sola.
Testo `#374151` su avorio, mai grigio tenue su bianco. Le voci in evidenza usano l'ambra,
non il verde: il verde qui significherebbe "disponibile".

**Ordini online** (`os-`) — stesso impianto del menù, con l'arancio per i richiami
all'azione secondari e il rosso `#dc3545` riservato a errori e rimozioni dal carrello.

**Board consegne** (`db-`) — pensata per stare aperta su un tablet in cucina: colori
degli stati più saturi del resto (giallo, blu, verde, arancio) perché si guarda da un
metro di distanza.

**Aree reseller e admin** — riprendono l'impianto della dashboard con la barra laterale
più larga (220 px). L'admin aggiunge il blu `#1565C0` come accento di ambiente: serve a
capire a colpo d'occhio che non si sta lavorando nella dashboard di un cliente.

**Email** (`MailService`) — stile inline, contenitore 600 px, raggi 10–12 px, palette
identica alla dashboard. Nessun webfont, nessun CSS esterno: i client di posta li
ignorano o li bloccano.

---

## Voce e testi

Tutta l'interfaccia è in **italiano**, con il vocabolario della ristorazione: coperti,
turni, servizio, sala, no-show. Mai gergo tecnico rivolto all'utente ("segmento",
"query", "record" non compaiono nelle schermate del ristoratore).

- I messaggi dicono **cosa è successo e cosa fare**: "Il tuo account non risulta
  associato a nessun ristorante. Contatta il supporto" — non "Errore 403".
- I numeri vanno **spiegati quando sono parziali**: "La giornata di oggi è ancora in
  corso" sotto un confronto settimanale evita che un calo di metà settimana allarmi.
- Quando un'azione ha un costo — crediti email — la schermata dice in anticipo quanti
  destinatari ci sono davvero, non quanti sarebbero in teoria.
- Il footer porta sempre: `© {anno} Evulery · by alagias. - Soluzioni per il web`.

---

## Fare / Non fare

### Fare
- Riusare i token di questo documento invece di scegliere valori a occhio.
- Colorare secondo il **significato**: se non è uno stato, è verde o grigio.
- Mostrare i numeri con la stessa fonte dell'azione che scateneranno.
- Far comparire avvisi e badge **solo quando c'è qualcosa da fare**.
- Dare a ogni azione un verbo, e il canale quando ne esiste più d'uno.
- Provare ogni schermata a 390 px prima di considerarla finita.

### Non fare
- Niente `style="grid-template-columns:…"` inline: batte ogni media query e il
  responsive smette di esistere (è già successo due volte, in aree diverse).
- Niente controlli invisibili — `opacity: 0`, overlay trasparenti — per intercettare i
  clic.
- Niente blu per le azioni: quello è lo stato "arrivato".
- Niente icona senza parola in un'azione: su telefono non c'è tooltip.
- Niente scorrimento laterale per raggiungere un filtro.
- Niente totali nei badge della barra laterale.
- Niente `<a>` dentro un altro `<a>`: la riga cliccabile si fa con `data-url`, che il
  gestore globale gestisce ignorando i clic sui link interni.

---

## Dove sta cosa

Il foglio della dashboard è cresciuto a 267 KB e le classi sono raggruppate per
**prefisso di pagina**, non per componente. Sapere a quale pagina appartiene un prefisso
è metà del lavoro quando si va a modificare qualcosa.

| Prefisso | Regole | Dove vive |
|---|---|---|
| `tm-` | 266 | Mappa sala e gestione tavoli — `settings/tables-map.php` |
| `cs-` | 123 | Scheda cliente — `customers/show.php` |
| `dr-` | 122 | Prenotazioni e calendario in home |
| `hg-` | 114 | Guida in-app — `help/detail.php` |
| `dh-` | 109 | Dashboard home (card statistica, griglie, azioni rapide) |
| `dm-` | 81 | Menù, lato gestione — `menu/categories.php` |
| `rd-`, `do-` | 78 + 54 | Ordini, board e dettaglio |
| `oh-` | 73 | Storico ordini |
| `promo-` | 61 | Promozioni e aspetto del menù |
| `av-`, `cl-` | 48 + 35 | Disponibilità online e chiusure |
| `notif-`, `push-` | 46 + 36 | Notifiche e push del browser |
| `ob-` | 43 | Card di onboarding — `partials/onboarding.php` |
| `res-` | 38 | Chiusura straordinaria |
| `bd-` | 29 | Compleanni in home |
| `seg-` | 26 | Filtri a card dei clienti |
| `susp-` | 26 | Pagina "abbonamento sospeso" |
| `nv-` | 23 | Card Novità — `partials/novita-card.php` |
| `hero-` | 19 | Intestazione della scheda cliente |

> Questa organizzazione per pagina è comoda finché una pagina è una cosa sola, ma è
> anche il motivo per cui la card statistica esiste come `dh-stat-card` e il filtro come
> `seg-tab`, pur essendo lo stesso oggetto con un bordo in più. Quando si crea un
> componente destinato a comparire in **più pagine**, conviene dargli un nome proprio
> invece del prefisso della pagina in cui nasce.

---

## Lacune note

Onestà su ciò che il sistema **non ha ancora**, così chi legge non crede di trovarlo:

1. **Nessun tema scuro.** Le pagine sono pensate solo in chiaro.
2. **Nessuno stato di caricamento uniforme**: alcune schermate hanno spinner propri,
   altre nulla. Da definire quando si toccherà il primo flusso lento.
3. **Accessibilità non verificata**: i contrasti non sono stati misurati contro WCAG, e
   il testo secondario `{colors.mute}` su bianco è al limite. Da controllare prima di
   dichiarare qualunque conformità.
4. **Spaziature non tokenizzate nel CSS**: i valori sono scritti a mano in rem. I token
   qui sopra descrivono l'uso reale, non esistono come variabili.
5. **Nove palette parallele.** Ogni superficie ridichiara i propri colori con un prefisso
   diverso (`--bw-*`, `--dm-*`, `--os-*`, `--rs-*`…). Il verde di marca è per fortuna
   identico ovunque, ma è ripetuto nove volte: cambiarlo significa toccare nove file.
6. **Nessun componente condiviso fra superfici.** Un pulsante del widget e uno della
   dashboard non hanno una riga di CSS in comune. È il prezzo dell'isolamento del widget
   (che vive dentro siti altrui e non può ereditare nulla), ma vale anche dove non
   servirebbe — menù, ordini e vetrina potrebbero condividere una base.
7. **Il gestionale non ha un contenitore a larghezza massima**: su schermi molto larghi
   le righe degli elenchi si allungano parecchio. Voluto per i tablet in cassa, da
   rivedere se qualcuno lavorasse su un monitor da 27 pollici.

---

*La vetrina dei componenti è in `docs/design-preview.html`: carica lo stesso
`dashboard.css` della dashboard, quindi mostra sempre lo stato attuale. Aggiornare
questo documento quando nasce un componente o cambia una convenzione — non per il
colore di una singola pagina, che va invece ricondotto alle tabelle qui sopra.*
