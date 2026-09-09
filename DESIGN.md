# Linee guida di design — Evulery

Questa guida **descrive ciò che il codice fa già**, non un ideale a cui tendere: ogni
valore qui sotto è stato estratto da `public/assets/css/*.css` e verificato in pagina.
Serve a un solo scopo: quando si aggiunge una funzione, non reinventare misure e colori
ma riusare quelli esistenti, così l'interfaccia resta una sola cosa e non un collage.

La vetrina viva dei componenti è **`docs/design-preview.html`**: si apre nel browser e
carica il CSS vero, quindi mostra sempre lo stato attuale, non una copia che invecchia.

---

## 1. Principi che il prodotto segue già

1. **Mai un controllo invisibile.** Niente input con `opacity:0` o sovrapposti per
   intercettare i clic: si usano controlli standard visibili. `showPicker()` e simili
   solo come miglioria, mai come unico modo per fare una cosa.
2. **Il verde è il marchio, il blu no.** Le azioni principali sono verdi
   (`btn-success`, `btn-outline-success`). Il blu è riservato allo stato "arrivato".
3. **Un numero mostrato deve coincidere con quello che succede.** Se una schermata
   dichiara "3 destinatari", la campagna deve inviarne 3: stessa query, stessa
   finestra temporale. Vale per badge, card e conteggi.
4. **Un badge dice se c'è qualcosa da fare**, non quanto è grande l'archivio. Se serve
   un totale, va in pagina, non nella barra laterale.
5. **Su mobile non si scorre di lato per scoprire una funzione.** Le griglie vanno a
   capo. Fa eccezione solo ciò che è intrinsecamente una sequenza (la linea del tempo
   della Sala).
6. **Il testo dice cosa succede.** Etichette all'imperativo sui pulsanti ("Invia
   auguri"), e il canale esplicito quando ne esistono due ("Invia auguri via email").

---

## 2. Colori

### Marchio
| Ruolo | Valore | Variabile |
|---|---|---|
| Brand | `#00844A` | `--brand`, `--rs-brand`, `--bw-primary` |
| Brand scuro (hover) | `#006837` | `--brand-dark` |
| Brand chiaro (sfondi) | `#E8F5E9` | `--brand-light` |

### Grigi (i più usati, in ordine di frequenza reale)
| Uso | Valore |
|---|---|
| Testo secondario | `#6c757d` (199 occorrenze) — `--gray-600` |
| Testo tenue / icone spente | `#adb5bd` — `--gray-500` |
| Testo forte | `#495057` |
| Titoli / inchiostro | `#1a1d23` |
| Bordi | `#e9ecef`, `#dee2e6` |
| Sfondi tenui | `#f8f9fa`, `#fafbfc` |
| Sfondo pagina | `#f5f6fa` (dashboard), `#f5f6f8` (area reseller) |

### Stati (convenzione da rispettare)
| Stato | Punto/testo | Sfondo pillola |
|---|---|---|
| Confermato | `#198754` / `#2E7D32` | `#E8F5E9` |
| In attesa | `#ffc107` / `#E65100` | `#FFF8E1` |
| Arrivato / seduto | `#1565C0` | `#E3F2FD` |
| No-show, annullato, distruttivo | `#dc3545` | `#fde8e8` |
| Compleanni | `#D81B60` | `#fce4ec` |
| WhatsApp | `#25D366` (hover `#1DA851`) | — |

> **Debito noto.** Lo stato "arrivato" oggi compare con almeno quattro tonalità di blu
> diverse (`#1565C0`, `#0EA5E9`, `#00838f`, `#0d6efd`) e "confermato" con quattro verdi
> (`#198754`, `#2E7D32`, `#00844A`, `#0a5c36`), a seconda del componente. Non è una
> scelta: è stratificazione. Chi tocca uno di quei componenti usi i valori della tabella
> qui sopra, così il debito si riassorbe invece di crescere.

---

## 3. Tipografia

Il font è quello di sistema, ereditato da Bootstrap 5.3 (caricato da CDN nel layout);
non c'è un webfont nella dashboard. Il monospace (`ui-monospace, SFMono-Regular, Menlo`)
si usa solo per codici e URL da copiare.

| Elemento | Dimensione | Peso |
|---|---|---|
| Numero grande in card (KPI) | `1.45rem` | 700 |
| Etichetta sotto il numero | `.7rem`, maiuscoletto, `letter-spacing .3px` | 500 |
| Testo corrente | `.85rem` – `.88rem` | 400/500 |
| Testo secondario | `.78rem` – `.82rem`, colore `#6c757d` | 400 |
| Micro-etichette (intestazioni tabella) | `.68rem`, maiuscoletto | 700 |

---

## 4. Misure

| Cosa | Valore |
|---|---|
| Raggio card | `12px` |
| Raggio riquadro icona | `10px` |
| Raggio pulsanti e campi | `8px` (pillole: `999px`) |
| Ombra card | `0 1px 3px rgba(0,0,0,.06)` |
| Padding card statistica | `14px 16px` |
| Riquadro icona | `42×42px`, icona `1.15rem` |
| Distanza fra card in griglia | `12px` (mobile `8px`) |

### Punti di rottura
Se ne usano tanti nel progetto, ma **quelli veri sono due**: `768px` (13 usi) e `576px`
(11 usi); `992px` serve per il passaggio da desktop largo a tablet. Per una funzione
nuova, attenersi a questi tre ed evitare valori inventati.

| Larghezza | Comportamento |
|---|---|
| ≤ 992px | griglie a 3 colonne, sidebar comprimibile |
| ≤ 768px | griglie a 2 colonne, sidebar a scomparsa |
| ≤ 576px | 2 colonne strette, gap ridotto, pulsanti a piena larghezza |

---

## 5. Componenti ricorrenti

- **Card statistica** (`.dh-stat-card`) — riquadro icona colorato + numero + etichetta.
  È il modello per qualunque riquadro numerico, comprese le card filtro dei Clienti.
- **Filtro a card** (`.seg-tab`) — come sopra, ma cliccabile: bordo `2px` **trasparente**
  a riposo che si colora quando il filtro è attivo, così la card non "salta" di 2px.
- **Badge di stato** (`.status-badge`) — pillola con sfondo tenue e testo saturo, colori
  dalla tabella degli stati.
- **Pillola d'azione** (`.wa-btn`) — icona + verbo, mai la sola icona: su telefono il
  tooltip non esiste e un'icona da sola non si spiega.
- **Barra d'avviso** — sfondo tenue, bordo sinistro `4px` nel colore del tema
  (ambra `#F57F17` per le cose da fare, rosa `#D81B60` per i compleanni), e compare
  **solo quando c'è qualcosa da segnalare**.
- **Tabella + card mobile** — sopra i 768px la tabella, sotto le card (`.mobile-card`).
  Non si comprime una tabella su telefono: si cambia componente.
- **Stato vuoto** — icona tenue, una riga che spiega cosa comparirà lì, ed eventualmente
  il pulsante per creare il primo elemento.

---

## 6. Regole per il mobile (imparate sbagliando)

1. **Mai `style="grid-template-columns:…"` inline.** Lo stile inline batte ogni media
   query: la pagina resta a N colonne anche sul telefono. È successo due volte — area
   reseller e Menù — e nel Menù trascinava l'intera pagina fuori schermo.
2. **Le varianti di griglia vanno nominate nei breakpoint.** `.griglia.variante` ha
   specificità maggiore di `.griglia`: dentro la media query va nominata anche la
   variante, altrimenti non viene sovrascritta.
3. **Il contenuto largo scorre dentro il suo contenitore**, non nella pagina. Una
   tabella dentro una card con `overflow:hidden` rende le ultime colonne irraggiungibili.
4. **Gli `input` in una griglia vogliono `min-width: 0`**, altrimenti impongono la loro
   larghezza naturale (~180px) e sfondano il contenitore.
5. **Verificare a 390px di larghezza reale.** Attenzione: Chrome headless con
   `--window-size=390` rende a 504px e ritaglia; per misurare davvero serve un iframe
   largo 390 dentro una finestra più grande.

---

## 7. Prima di dire che una schermata è finita

- [ ] Provata a **390px** e a desktop.
- [ ] Nessuno scorrimento orizzontale della pagina (`document.scrollWidth` ≤ viewport).
- [ ] I numeri mostrati coincidono con quello che l'azione farà davvero.
- [ ] Le azioni dicono **cosa** fanno e **su quale canale**.
- [ ] Gli stati vuoti spiegano cosa comparirà.
- [ ] Colori presi dalla tabella degli stati, non scelti a occhio.
- [ ] Se il dato può mancare (telefono, email, data di nascita), l'interfaccia lo dice
      invece di mostrare un pulsante che non funziona.

---

*Aggiornare questo file quando si introduce un componente nuovo o si cambia una
convenzione — non quando si cambia un colore a una singola pagina: quello è un caso da
ricondurre alle tabelle qui sopra.*
