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
  pill: 999px

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
azioni contestuali (`100px`/`999px`): la forma dice "questo è un'etichetta o un'azione
breve", mai un contenitore.

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
5. **Due aree con palette parallele**: reseller (`--rs-*`) e admin (`--admin-accent`)
   ripetono valori invece di ereditarli.

---

*La vetrina dei componenti è in `docs/design-preview.html`: carica lo stesso
`dashboard.css` della dashboard, quindi mostra sempre lo stato attuale. Aggiornare
questo documento quando nasce un componente o cambia una convenzione — non per il
colore di una singola pagina, che va invece ricondotto alle tabelle qui sopra.*
