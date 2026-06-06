# Changelog Evulery.Pro 1.0

Tutte le modifiche significative del progetto sono documentate in questo file.

Formato basato su [Keep a Changelog](https://keepachangelog.com/), date in ISO 8601.

---

## [Unreleased]

Aggiornamenti documentazione in corso, nessuna feature nuova in pipeline. Backlog su `TODO.md`.

---

## 2026-06-06 — Security audit + Stampa ordini MVP + documentazione

### Security
- **Audit completo** a 3 mesi dal precedente (file privato `SECURITY_AUDIT_2026-06-06.md`, gitignored). Verdetto: buono stato di sicurezza, pattern dell'audit di Marzo applicati consistentemente alle nuove feature (Riders, Heartbeat, Print MVP, Notifiche multi-canale, Reseller B2B). 3 findings totali: nessuno HIGH/CRITICAL, 1 MEDIUM (security headers HTTP — fix 5 min raccomandato prima del lancio), 2 LOW (SVG upload, token expiry).


### Added
- **Stampa ordini MVP**: due viste standalone HTML+CSS `@page print` con dialog browser automatico
  - `GET /dashboard/orders/{id}/print/kitchen` — ticket sintetico per la brigata (no prezzi, no telefono, no email, no indirizzo; note in reverse video b/n; # ordine GIGANTE)
  - `GET /dashboard/orders/{id}/print/receipt` — ricevuta completa cliente con sezione staccabile ✂ "Conferma di consegna" solo per delivery
  - Pulsanti contestuali sui card kanban (Cucina su accepted/preparing, Stampa su ready)
  - Footer dinamico `tenants.website_url` con fallback widget Evulery
  - Hint toolbar 80mm dismissabile + ricomparsa via icona info sobria
- Migration `061_tenant_website_url` per il campo sito web del ristoratore
- Guida interna estesa: sezione Tavoli con jolly/bloccato/archiviato/min-max/palette colori; sezione Ordini con spiegazione slot e max per slot

### Fixed
- **Bug killer rider markup**: `renderOrderCard()` JS ricostruiva le card kanban dopo cambio status ma saltava il blocco rider — il pulsante "Assegna rider" spariva fino a F5 manuale
- Tabella "Completati/Chiusi oggi" non si aggiornava senza F5 — implementato `updateCompletedTable()` con container sempre presente
- Tear-off "Conferma di consegna" appariva anche su asporto (privo di senso) — ora solo delivery

### Changed
- Hint stampante termica 80mm: chiuso di default invece di aperto (icona info sempre visibile per riapertura)
- Icona hint da emoji 💡 a SVG info-circle sobrio; allineamento esatto con pulsanti Stampa/Chiudi

---

## 2026-06-05 — Riders MVP + Notifiche audio + Push fix

### Added
- **Riders MVP completo**: anagrafica + assegnazione ordini + statistiche
  - Migration `060_riders` con `riders` table e `orders.rider_id` + `orders.rider_assigned_at`
  - CRUD `/dashboard/riders` con palette colori fissa 8 colori, archivia/riattiva
  - Stats `/dashboard/riders/stats` con KPI globali + tabella per rider + range chip (oggi default, mese, 7gg, 30gg, custom)
  - Datepicker custom Evulery (.dr-cal-*) + bottone "Oggi" + flip viewport
  - Badge rider colorato sui card kanban + popup assegnazione
  - Board pubblica `/delivery/{token}` mostra nome rider + pulsante "Consegnato" disabilitato senza rider (forza dati puliti)
  - Voce sidebar gated su `ordering_enabled + delivery_mode != 'none'`
- **Notifiche audio brandizzate**: 5 sound logo (master + cancellation + deposit + order + review)
  - Migration `059_notification_sounds` con master toggle + volume + 5 toggle per evento
  - Modulo `notification-sounds.js` con preload + autoplay policy + filter `cancelled_by`
  - Settings UI con anteprima e hot-reload volume
- **Banner iOS PWA install** azzurro contestuale per iPhone Safari non-standalone
- Strategia plugin marketplace (memory `project_plugin_marketplace.md`) — primo candidato Print Pro per Distinto Sushi

### Fixed
- **Push notifications mobile bug killer**: il click sulla campanella mobile chiamava `subscribeToPush()` async e poi navigava via subito → la promise di iscrizione veniva interrotta sempre. Nessun mobile riusciva a iscriversi.
- Race condition `EvuleryPush.getStatus()`: leggeva `swRegistration` variabile locale null al primo render → box settings mostrava "non attive" anche per utenti già subscribed. Fix con `navigator.serviceWorker.ready`.
- Audit duplicazioni post-sessione: unificate helper localStorage banner dismiss (`isBannerDismissedRecently` + `rememberBannerDismissed`)

### Changed
- Session `ABSOLUTE_TIMEOUT` 8h → **12h** (copre turno tipico ristoratore 11-23 senza re-login a metà)
- Push opt-in: niente più subscribe-on-bell-click (fragile), banner verde esplicito sulla home + sezione settings dedicata
- `NotificationService::notifyCancellation` espone `cancelled_by` come campo strutturato JSON in `data` (preparazione audio filter)

---

## 2026-06-04 — Fase C Auto-refresh dashboard

### Added
- **Heartbeat polling** ETag/304 con pausa su `document.hidden`, backoff esponenziale
  - `App\Services\HeartbeatService` con `forReservations()` e `forFloor()`
  - `GET /dashboard/heartbeat/reservations` e `/heartbeat/floor` (gatato `table_management`)
  - Modulo JS riusabile `heartbeat-polling.js` con auto-bind via `data-*` attributes
  - Banner ambra sticky-top contestuale "X modifiche su [contesto]"
- Applicato a 3 pagine: home dashboard, prenotazioni, sala operativa

### Changed
- Grammatica banner heartbeat: preposizione articolata dentro il label (es. "sulle prenotazioni di questa pagina" invece di "su le prenotazioni")

---

## 2026-06-03 — Quick win Tier 2

### Fixed
- **Birthday correggibile dal widget**: `Customer::findOrCreate` ora permette update del campo se diverso da quello in DB (era impossibile correggere date errate)
- **CTA "Prenota ora" broadcast**: warning UI se `include_booking_cta=1` ma `tenant.slug` vuoto + neutralizzazione del flag
- **Hint marketing esclusi broadcast**: refactor totale del preview con header totale "X clienti non riceveranno l'email" + breakdown (no consenso vs disiscritti). Sostituita riga ambra "Esclusi (no consenso)" che sembrava un alert

---

## 2026-05-30 → 2026-06-03 — VPS Migration consolidation + Migrator + Quick wins

### Added
- **Auto-migration**: `App\Services\Migrator` + pagina admin `/admin/migrations` per applicare migration via browser invece di CLI cron-job
- Script `scripts/migrate.php` come thin wrapper CLI sopra Migrator
- Quick win Tier 1 Gestione Tavoli Fase B + E:
  - Migration `058_table_availability_flags` con `is_bookable_online`, `is_blocked`, `block_reason`
  - Toggle "tavolo jolly" (Disponibile online OFF) + lucchetto sulla mappa per tavoli bloccati
  - Auto-motivo "Bloccato dal DD/MM/YYYY", warning su dropdown su tavolo bloccato
  - Sezione "Archiviati" in lista (rename Disattivato → Archiviato)
- Sticky bar "Salva ordine" position:fixed con slide-in

### Changed
- VPS migrato su nuovo server Serverplan 8 core Xeon Gold + 32 GB RAM + CloudLinux 10

---

## 2026-05-24 — Combinazione multi-tavolo ad-hoc + Dropdown tavolo custom

### Added
- Fase A combinazione multi-tavolo ad-hoc: il ristoratore può combinare 3+ tavoli al volo per un gruppo specifico senza pre-configurare la coppia
- Dropdown tavolo custom (Opzione A): UI con icone, sezioni, tag "occupato", flip dinamico nel viewport, position:fixed (no clipping), popup mappa 400px

---

## 2026-05-20 — Tavoli elastici (min/max capacity)

### Added
- Migration `057_table_min_capacity` con backfill `min=1` (comportamento pre-migration identico)
- Algoritmo fit-primary (capacity-party ASC) → priorità manuale (tiebreaker) → id (deterministico)
- Modale tavolo con due campi "Posti minimi → Posti massimi" + anteprima live
- Pill range ambra nella lista, badge combinabili con range posti (es. "3-10 posti")
- Helper `format_capacity()` e `format_seats_range()` come single source of truth

### Changed
- Combo attivata solo se nessun singolo valido libero (regola: party > max(maxA, maxB))

---

## 2026-05-18 — Gestione Tavoli Fase 1 + Fase 2

### Added
- Servizio gatato `table_management` (Enterprise)
- `App\Models\Table` (CRUD, priorità, combinazioni coppie, posizioni)
- `App\Services\TableAssigner` con turni (tavolo occupato per durata+buffer), auto-assegnazione non bloccante
- Fase 1: pagina Impostazioni > Tavoli con drag riordino priorità, modale, master toggle auto-assegnazione
- Fase 2: Mappa sala (`/dashboard/settings/tables/map`) con modalità Setup (drag&drop posizioni) e Operativa (scorri-orari + popup riassegnazione)
- Override manuale tavolo nella scheda prenotazione, badge tavolo nella lista

---

## 2026-05-15 — Caparra condizionale + carta a garanzia

### Added
- Caparra condizionale per giorni della settimana + fasce orarie (meal categories)
- 4° tipo caparra `guarantee` (carta a garanzia, modello impronta-carta TheFork):
  - Stripe Checkout `mode=setup`, reservation pending→confirmed via webhook `handleGuaranteeSetup`
  - `guarantee_status`: none/pending/secured/charged/waived
  - PaymentIntent off-session per addebito penale no-show (`chargeGuarantee`) o `waiveGuarantee`

---

## 2026-05-11 → 2026-05-12 — Programma Reseller B2B v3

### Added
- Area `/reseller/*` con dashboard KPI, lead filtrati, clienti acquisiti, commissioni, ricariche crediti email, materiali commerciali
- Admin `/admin/credit-requests` per approvazione ricariche
- `App\Services\CommissionCalculator` (single source of truth)
- Tabella `reseller_profiles` con 4 campi commissione configurabili per-reseller
- Snapshot reseller al momento conversione lead→tenant (`tenants.acquired_by_reseller_id`)
- 3 nuovi materiali: demo script HTML interattivo, ROI calculator, FAQ ristoratore
- Add-lead manuale, delete safe, normalizzazione aggressiva email anti-dup
- Frecce navigazione giorno ±1 sempre visibili in dashboard + prenotazioni, shortcut tastiera ←/→

### Security
- `User::update($id, $data, $allowPrivileged=false)` opt-in esplicito per campi privilegiati
- `SELECT ... FOR UPDATE` dentro transazione su approve credit (anti double-processing)

---

## 2026-04-30 — GDPR consent + Birthday widget V2.3

### Added
- Migration `048` con `marketing_consent` NULL/0/1 + audit
- Widget V2.3 con 3 stati intelligenti A/B/C via lookup AJAX `/api/v1/tenants/{slug}/customers/lookup`
- Endpoint con rate limit dedicato (10 req/min/IP)
- `BroadcastService::getRecipients` filtra `WHERE marketing_consent = 1`
- Segmento broadcast "Compleanno questo mese" + flag CTA "Prenota ora" per singola campagna

---

## 2026-04-25 → 2026-04-29 — Vetrina Digitale (Hub) + caparra polish

### Added
- Vetrina Digitale: pagina pubblica aggregatrice scansionabile da QR, 6 palette + 8 azioni preset + custom Enterprise + QR client-side
- Migration `046`: `markDepositRefunded` setta NOW(), banner mostra data rimborso
- Migration `047`: flag `include_booking_cta` per singola campagna broadcast

---

## 2026-04-13 → 2026-04-15 — Help Guide in-app + Pagine legali + Branding

### Added
- Sezione "Guida" in-app con 17 sezioni, ricerca, feedback 👍/👎 via email
- Pagine legali GDPR-compliant: privacy, terms, cookies
- `SUPPORT_EMAIL` aggiornata a `info@evulery.it`

---

## 2026-04-07 — Storico ordini redesign + Stripe UX fix

### Added
- Storico ordini con 3 tab (panoramica/ordini/classifiche), KPI con trend %, breakdown tipo/pagamento, top piatti/clienti, export CSV
- Dettaglio ordine uniformato (badge pill come prenotazioni, timeline cronologia stati)

### Fixed
- Widget mostra "Pagamento Caparra" (pending) invece di "Confermata" prima del pagamento Stripe
- Double-slash JS: `url('')` trailing slash causava `//` in fetch URL → errore di rete su POST

---

## 2026-04-01 — Reputation Management (FASE 23)

### Added
- Review request automatici post-visita, landing pubblica con filtro sentimento (stelle)
- Feedback privato per voti bassi (< 4), dashboard 3 tab (panoramica/feedback/storico)
- Settings, cron, 2 canali (email tracciato + QR/NFC/embed anonimo)
- Migration `037`, servizio `review_management`, CSS `.rv-*`
- Conforme Legge 34/2026 sulle recensioni verificate

---

## 2026-03-30 — Online ordering (FASE 22)

### Added
- Takeaway + delivery, migration `032`, kanban dashboard, store pubblico
- Delivery zones (simple/CAP) con costi e minimi differenziati

---

## 2026-03-26 — Notifiche 3 livelli

### Added
- Migration `028`, servizio `push_notifications`, PWA manifest per mobile
- Email ristoratore (tutti i piani) + campanella dashboard + push browser (Professional/Enterprise)

---

## 2026-03-24 — Caparra 3 Livelli (pre-guarantee)

### Added
- 3 tipi caparra: info/link/stripe
- Crittografia AES-256-GCM per chiavi tenant
- Bottone "Segna caparra ricevuta", widget con stato "In Attesa"

### Removed
- Stripe Connect (overengineered per il use case)

---

## 2026-03-04 — Security audit (snapshot)

### Security
- Fix completati Fase 1 + Fase 2 (vedi `SECURITY_AUDIT_2026-03-04.md`)
- CSP con nonce dinamico per request, no `unsafe-inline` su `script-src`
- Tenant isolation hardening (`!==` strict comparison)
- Brute force protection su login

---

## 2026-02 e precedenti — Pre-1.0

Sviluppo iniziale del prodotto MVP: prenotazioni, widget, CRM automatico, link magico, reminder, chiusure, blacklist, promozioni, email broadcast, dashboard rapida.
Per dettagli storici vedi `SPECS.md` (concept document originale) e `docs/product-strategy.md`.

---

*Per il backlog corrente vedi `TODO.md`. Per architettura e scelte di design vedi `docs/product-strategy.md`.*
