# Evulery.Pro 1.0 - Prossimi Passi

## Stato Attuale
Fasi completate: Foundation, Auth, Admin Panel, Multi-Tenant, Slot/Capacita, Booking Widget, Dashboard Ristoratore, Security Audit (25/25), Dashboard UX improvements, Prenotazione Rapida Touch (FASE 16), Design System v3.1, Booking Widget Polish (FASE 17 parziale), Promozioni e Badge Sconto (FASE 14), Menu Digitale Consultivo (FASE 20A v2.1).
Il sistema funziona end-to-end: login, gestione ristoranti, prenotazioni da widget e dashboard, menu digitale pubblico con QR code.

**Nota architetturale (Marzo 2026):** Il modello commerciale sara basato sui **coperti** (numero di prenotazioni/coperti gestiti), NON sulle feature flags. Tutte le funzionalita sono disponibili per tutti i tenant. La FASE 9 (Feature Flags) e stata abbandonata. I piani commerciali (FASE 18) limiteranno solo il volume di coperti mensili, non l'accesso alle singole feature.

**Completato recentemente (sessione corrente - Marzo 2026):**
- [x] Promozioni: badge sconto su slot dashboard, sconto in email reminder, colonna Sconto in CSV export (20/03/2026)
- [x] Toggle "Promozioni solo da widget" in Impostazioni: quando attivo, sconti visibili solo sul widget online, non da dashboard (20/03/2026)
- [x] FASE 20A Menu Digitale v2.1: deploy produzione completato (20/03/2026). Dashboard 3 tab (Piatti/Categorie/Aspetto), pagina pubblica standalone con hero, sticky nav, search, allergeni EU, QR code. Categorie con accordion UI, sottocategorie, icon picker. Fix CSP nonce su script pubblico.
- [x] Slot passati nascosti per "Oggi": widget li nasconde completamente, dashboard li mostra grigi (dashed border, opacity .6) ma cliccabili per walk-in
- [x] CSS stati slot passati: `.dr-slot-past` con 3 stati (normal, hover con opacity 1, active con sfondo grigio)
- [x] Flag `is_past` negli slot availability (AvailabilityService): confronto data/ora corrente
- [x] Validazione frontend inline booking widget: errori per-campo on-blur, auto-clear su input, classi `.bw-has-error`/`.bw-has-success`, nessun alert() nativo
- [x] Statistiche clienti: pagina `/dashboard/customers/stats` con KPI (clienti totali, pren. medie, tasso ritorno, tasso no-show), top clienti per frequenza nel periodo, donut nuovi vs ritorno, segmentazione barre
- [x] Filtro periodo statistiche: preset 30/90/365 giorni + date custom
- [x] Tab "Statistiche" integrato nei segment tabs della lista clienti (margin-left auto, bordo dashed verde)
- [x] Fix filter-bar clienti: wrappato in `.filter-row` per layout flex corretto
- [x] Footer branding: "© {anno} Evulery · by alagias. - Soluzioni per il web" su tutte le pagine
- [x] Wireframe creati: `customers-stats.html`, `booking-widget-validation.html`, `plans-feature-flags.html`

**Completato in sessioni precedenti:**
- [x] Paginazione server-side liste: clienti (25/pag con filtro segmento + ricerca), admin tenants (25/pag con ricerca)
- [x] Upload logo ristorante: upload in settings, validazione MIME + 2MB, preview, rimozione. Logo visibile solo sul widget di prenotazione (sfondo chiaro)
- [x] Classe `Paginator` riutilizzabile (app/Core/Paginator.php): offset/limit, URL con query params, link con gaps
- [x] Dashboard home redesign: stat cards con trend, countdown arrivi, capienza pranzo/cena, confronto settimana, no-show rate, fonte prenotazioni, sidebar con calendario e azioni rapide
- [x] Ricerca globale prenotazioni: barra di ricerca cross-date per nome/telefono/email nella pagina prenotazioni
- [x] Email reminder: script cron con reminder 24h (blu) + 2h (arancione), template HTML, migrazione colonne reminder_sent_at
- [x] Email conferma al cliente: template HTML conferma + aggiornamento, hook widget + dashboard, sender dinamico (nome ristorante), Reply-To email tenant
- [x] Esportazione CSV prenotazioni: export con range date, filtro stato, scorciatoie rapide (mese/settimana/anno), separatore ; per Excel IT, BOM UTF-8
- [x] Design System v3.1: brand #00844A, card radius 12px, shadow leggera, input radius 8px
- [x] Redesign pagine auth (login, forgot-password, reset-password) in stile v3.1
- [x] Redesign create/edit prenotazione in stile v3.1 (section-card, summary bar, grid layout)
- [x] Fix calendario dropdown dashboard home (overflow-x: auto → visible)
- [x] Fix "Segna Arrivato" lista prenotazioni (global click handler esclude form)
- [x] Pulsante "Annulla arrivo" nella pagina dettaglio prenotazione
- [x] Fix toggle categorie pasto (isset → !empty per hidden input)
- [x] Fix mobile: settings tabs flex-wrap, calendario dropdown positioning
- [x] Branding "Evulery · by alagias. - Soluzioni per il web" nelle pagine auth
- [x] check-live.md: checklist pre/post deploy
- [x] Note cliente persistenti (colonna notes su customers, textarea in scheda cliente, visibili in show prenotazione)
- [x] Redesign widget prenotazione stile TheFork (calendario visuale, 4 step, slot raggruppati per categoria pasto)
- [x] Tabella `meal_categories` + Model + API `?grouped=1`
- [x] Dashboard gestione categorie pasto
- [x] Social proof ("Gia X prenotazioni per oggi")
- [x] Security Audit completo 25/25 (CSRF, XSS, SQL injection, brute force, rate limiting, audit log, CSP, password policy, open redirect, input validation)
- [x] PHPMailer integrato + MailService (reset password funzionante)
- [x] Eliminazione prenotazione (entro 30 min dalla creazione, con countdown)
- [x] Pulsante "Segna Arrivato" nella lista prenotazioni (con redirect back alla lista)
- [x] Fix phantom slots nella tabella Orari e Coperti (disabilitati + rimossi al salvataggio)
- [x] Conferma manuale prenotazioni: toggle auto/manual in settings, status pending, bottone conferma rapida in lista, email al cliente alla conferma
- [x] Link magico gestione prenotazione: token 64-char, pagina pubblica /manage/{token}, visualizzazione + cancellazione, link nell'email di conferma
- [x] Warning prenotazione duplicata: avviso soft nel widget se cliente ha già prenotazione attiva per la stessa data (può procedere comunque)

---

## MIGLIORIE IMMEDIATE (ordine consigliato)

### ~~1. Email conferma al cliente~~ → COMPLETATO
~~**Complessita: Bassa** | File: 3 | Priorita: ALTA~~
- [x] Template HTML conferma prenotazione (data, ora, persone, nome ristorante)
- [x] Template HTML aggiornamento prenotazione (variante blu)
- [x] Hook in `ReservationApiController::store()` (prenotazione da widget)
- [x] Hook in `ReservationsController::store()` (prenotazione da dashboard)
- [x] Hook in `ReservationsController::update()` (modifica da dashboard)
- [x] Sender dinamico: FromName = nome ristorante, Reply-To = email tenant
- [ ] Link di cancellazione nell'email (richiede link magico, FASE 11)

### ~~2. Esportazione CSV prenotazioni~~ → COMPLETATO
~~**Complessita: Bassa** | File: 3 | Priorita: MEDIA~~
- [x] Metodo `export()` in `ReservationsController` (genera CSV con header)
- [x] Metodo `findForExport()` in `Reservation` model (filtri date_from/date_to/status)
- [x] Rotta GET `/dashboard/reservations/export`
- [x] Pannello export inline con range date, filtro stato, scorciatoie rapide
- [x] Campi: data, orario, nome, cognome, email, telefono, persone, stato, fonte, note cliente, note interne, creata il
- [x] BOM UTF-8 + separatore `;` per compatibilita Excel italiano

### ~~3. Note cliente persistenti~~ → COMPLETATO
~~**Complessita: Bassa** | File: 4 | Migrazione: 1 colonna~~
- [x] Colonna `notes` (TEXT) su tabella `customers`
- [x] Form textarea in `views/dashboard/customers/show.php`
- [x] Metodo `updateNotes()` in `Customer` model
- [x] Note visibili in `reservations/show.php` (customer_notes_persistent)

### ~~4. Dashboard home migliorata~~ → COMPLETATO
~~**Complessita: Media** | File: 2 | Priorita: MEDIA~~
- [x] Coperti totali del giorno (somma party_size prenotazioni confermate/arrivate)
- [x] Tasso no-show del ristorante (ultimi 30 giorni)
- [x] Prossime prenotazioni in arrivo (ordinate per orario, con countdown live)
- [x] Confronto con settimana precedente (barre comparative)
- [x] Card riassuntive con icone Bootstrap Icons + trend indicators
- [x] Riepilogo servizi pranzo/cena con capienza e overbooking
- [x] Fonte prenotazioni (widget vs dashboard vs telefono)
- [x] Sidebar: mini calendario, prossimi 7 giorni, azioni rapide

### ~~5. Ricerca globale prenotazioni~~ → COMPLETATO
~~**Complessita: Media-bassa** | File: 3~~
- [x] Metodo `searchGlobal()` in `Reservation` model (nome, telefono, email cross-date)
- [x] Barra di ricerca nella pagina prenotazioni (campo testo + risultati inline)
- [x] Ricerca via parametro `?q=` nella rotta esistente `/dashboard/reservations`

### ~~6. Email promemoria (reminder)~~ → COMPLETATO
~~**Complessita: Media** | File: 4 | Migrazione: 2 colonne~~
- [x] Script cron PHP `scripts/send-reminders.php` (24h + 2h prima)
- [x] Colonne `reminder_24h_sent_at` e `reminder_2h_sent_at` su tabella `reservations`
- [x] Query: prenotazioni confermate con margine temporale (23-25h e 1.5-2.5h)
- [x] Template HTML reminder (blu 24h, arancione 2h) con riepilogo prenotazione
- [ ] Setup cron sul server di produzione (ogni 15 min) — da configurare al deploy

### ~~7-11. Sviluppo futuro~~ → RIMANDATI
Le seguenti migliorie non sono prioritarie per il lancio e vengono rimandate a fasi successive:
- **Vista timeline/calendario** (Alta) — griglia oraria con blocchi colorati, FullCalendar
- **Gestione tavoli** (Molto alta) — tabelle, mappa sala, assegnazione automatica
- **Dark mode** (Media) — CSS variables, toggle, localStorage
- **~~PWA~~** — Scartata: icone duplicate per ogni ristorante
- **Multi-lingua** (Molto alta) — i18n, helper `__()`, IT + EN

### Ottimizzazioni performance (da fare in seguito)
- [ ] Minificazione CSS/JS (dashboard.css 105KB → ~25KB minificato, tool: csso/terser o build step)
- [ ] CSS splitting: separare dashboard.css in moduli caricati per pagina (menu, reservations, settings)
- [ ] OPcache tuning su VPS: verificare `opcache.enable=1`, `opcache.revalidate_freq=60`
- [ ] CDN per asset statici (Bootstrap, Bootstrap Icons) — da CDN pubblico o Cloudflare
- [ ] Lazy loading immagini menu (già `loading="lazy"`, verificare su VPS)
- [ ] Database: query profiling su pagine lente (EXPLAIN su query AvailabilityService)

### Menu digitale — migliorie estetiche (da fare in seguito)
- [ ] Dashboard menu: sottocategorie allineamento layout (freccia + padding sinistro)

---

## FASE 8: Integrazione Stripe Caparra
- [ ] Configurare chiavi Stripe test nel `.env`
- [ ] Implementare `StripeService.php` (creazione Checkout Session)
- [ ] Collegare il flusso: form prenotazione → redirect Stripe → conferma
- [ ] Gestire webhook `checkout.session.completed` e `checkout.session.expired`
- [ ] Pagina successo/annullamento pagamento
- [ ] Impostazioni caparra nel dashboard (gia creata la UI, manca il collegamento Stripe)
- [ ] Testare con carte test Stripe

## FASE 9: Sistema Piani e Feature Flags [ABBANDONATA]
~~Architettura flessibile per gestire i piani commerciali con feature gating dinamico.~~
**Decisione (Marzo 2026):** Modello commerciale basato sui coperti, non sulle feature. Tutte le funzionalita disponibili per tutti. I piani differenzieranno solo per volume coperti mensili (vedi FASE 18).

### Database: 3 nuove tabelle
- [ ] `plan_definitions` - Piani con prezzo e booking_limit
  - Campi: id, name (key), display_name, description, price, booking_limit (NULL=illimitate), sort_order, is_active
  - Piani default: Free (0€, 50 pren/mese), Starter (29€, 500), Pro (59€, illimitate), Business (99€, illimitate)
- [ ] `feature_definitions` - Registro funzionalita
  - Campi: id, key, display_name, description, type (boolean/limit), category, sort_order
  - Features iniziali: deposit, custom_domain, white_label, custom_meal_categories, export_csv, email_notifications, sms_notifications, analytics, api_access, multi_user, priority_support, promotions, email_broadcast, blacklist, customer_notes, manual_confirmation, closure_days
- [ ] `plan_features` - Matrice piano/feature (tabella ponte)
  - Campi: id, plan_id, feature_id, is_enabled, limit_value (per type=limit)
- [ ] Migrare colonna `tenants.plan` da ENUM('base','deposit','custom') a VARCHAR che referenzia `plan_definitions.name`
- [ ] (Futuro) `tenant_feature_overrides` - Override per eccezioni su singolo tenant

### Backend
- [ ] `PlanService.php` - Servizio centralizzato feature gating
  - `can('deposit')` → true/false
  - `can('custom_domain')` → true/false
  - `limit('multi_user')` → numero o null (illimitato)
  - `bookingsRemaining()` → conteggio rimanente o null
  - `isAtBookingLimit()` → true/false
  - `getPlanDetails()` → info piano corrente
- [ ] Integrare PlanService nei controller esistenti (deposit, domain, meal-categories, ecc.)
- [ ] Upgrade prompts: quando il ristoratore accede a feature bloccata, mostrare messaggio con link upgrade

### Admin Panel
- [ ] Pagina gestione piani: matrice interattiva feature x piano
  - Checkbox per boolean, input numerico per limit, prezzo e booking_limit editabili
- [ ] Pagina gestione features: aggiungere/modificare feature definitions
- [ ] Nella scheda tenant: assegnazione piano + visualizzazione features attive

### Matrice Default Piani/Features
```
                        Free    Starter   Pro      Business
────────────────────────────────────────────────────────────
Prezzo                  0€      29€       59€      99€
Prenotazioni/mese       50      500       ∞        ∞
────────────────────────────────────────────────────────────
Caparra (Stripe)        [ ]     [ ]       [✓]      [✓]
Dominio personalizzato  [ ]     [ ]       [ ]      [✓]
White-label             [ ]     [ ]       [ ]      [✓]
Categorie pasto custom  [ ]     [ ]       [✓]      [✓]
Export CSV              [ ]     [✓]       [✓]      [✓]
Email avanzate          [ ]     [✓]       [✓]      [✓]
SMS                     [ ]     [ ]       [ ]      [✓]
Analytics/Report        [ ]     [ ]       [✓]      [✓]
Accesso API             [ ]     [ ]       [✓]      [✓]
Multi-user (staff)      1       2         5        ∞
CRM Clienti             [ ]     [✓]       [✓]      [✓]
Link gestione prenot.   [ ]     [✓]       [✓]      [✓]
Reminder email          [ ]     [✓]       [✓]      [✓]
Promozioni/Sconti       [ ]     [ ]       [✓]      [✓]
Email broadcast         [ ]     [ ]       [✓]      [✓]
Blacklist clienti       [ ]     [✓]       [✓]      [✓]
Note clienti            [ ]     [✓]       [✓]      [✓]
Conferma manuale        [ ]     [ ]       [✓]      [✓]
Giorni chiusura         [ ]     [✓]       [✓]      [✓]
Prenot. rapida touch    [✓]     [✓]       [✓]      [✓]
Supporto prioritario    [ ]     [ ]       [✓]      [✓]
```

**Nota:** Tutte le feature sono flag booleani controllabili dall'admin nella matrice.
Il piano Free include solo il widget di prenotazione base (calendario, orari, form).
L'admin puo modificare qualsiasi flag per qualsiasi piano in qualsiasi momento.

## FASE 10: Gestione Domini Personalizzati (richiede piano Business)
- [ ] Testare flusso CNAME + verifica DNS (logica gia scritta)
- [ ] Configurare virtual host XAMPP per test locale con dominio custom
- [ ] Gestione SSL automatico (Let's Encrypt) per produzione
- [ ] Gate: verificare `PlanService::can('custom_domain')` prima di permettere configurazione

## FASE 11: CRM Clienti e Gestione Prenotazione
Sistema automatico di gestione clienti per il ristoratore + self-service per il cliente finale.

### CRM Automatico (senza account cliente)
Il sistema raggruppa le prenotazioni per email, tenant-scoped. Nessuna azione richiesta dal cliente.
- [x] Tabella `customers` (tenant_id, first_name, last_name, email UNIQUE per tenant, phone, total_bookings, total_noshow, last_booking_at)
- [x] Auto-creazione/aggiornamento customer ad ogni prenotazione (match per email + tenant)
- [x] Dashboard ristoratore: lista clienti con storico visite, no-show rate, party size medio
- [x] Scheda cliente: tutte le prenotazioni passate e future
- [x] Segmentazione automatica: Nuovo (1 visita), Occasionale (2-3), Abituale (4-9), VIP (10+)
- [x] Top clienti per frequenza nel periodo selezionato (pagina /dashboard/customers/stats)
- [x] Statistiche: nuovi vs ritorno (percentuale sul totale prenotazioni, donut chart CSS)
- [x] KPI cards: clienti totali, pren. medie/cliente, tasso ritorno, tasso no-show
- [x] Segmentazione clienti con barre proporzionali
- [x] Filtro periodo (30/90/365 giorni + personalizzato)

### Link Magico - Gestione Prenotazione (self-service cliente)
Il cliente gestisce la prenotazione tramite un link unico ricevuto via email. Nessuna registrazione.
- [x] Colonna `manage_token` (VARCHAR 64, UNIQUE) sulla tabella `reservations`, generato alla creazione
- [x] Route: `/manage/{token}` → pagina di gestione (layout minimal standalone)
- [x] Vista dettagli: data, ora, persone, stato prenotazione (con date in italiano)
- [x] Cancellazione prenotazione: conferma JS + aggiornamento stato + log
- [x] Link "Gestisci prenotazione" nell'email di conferma
- [ ] **Sviluppo futuro:** Modifica prenotazione (cambio data, ora, persone con verifica disponibilita)
- [ ] Regole ristoratore: "modifiche consentite fino a X ore prima" (configurabile da dashboard)
- [ ] Scadenza link: funziona fino a X ore dopo il termine della prenotazione
- [ ] Notifica al ristoratore nel dashboard ad ogni modifica/cancellazione del cliente

### Impostazioni Dashboard Ristoratore
- [ ] Sezione "Gestione prenotazione cliente" nelle impostazioni
  - Tempo minimo per modifica (es. 2h prima)
  - Tempo minimo per cancellazione (es. 1h prima)
  - Permettere modifica data (si/no)
  - Permettere modifica persone (si/no)

### Blacklist Clienti → COMPLETATO (senza gate, da integrare con PlanService)
- [x] Flag `is_blocked` + `blocked_at` sulla tabella `customers`
- [x] Quando cliente bloccato tenta di prenotare (match email) → messaggio "Contatta il ristorante telefonicamente"
- [x] Bottone blocca/sblocca nella scheda cliente (toggle con flash message)
- [x] Indicatore visivo nella lista clienti (badge rosso, riga semitrasparente, avatar rosso su mobile)

### Note Clienti → COMPLETATO
- [x] Campo `notes` (TEXT) sulla tabella `customers`
- [x] Textarea nella scheda cliente per aggiungere/modificare note
- [x] Note visibili nella vista prenotazione (customer_notes_persistent in show.php)

## FASE 12: Giorni di Chiusura e Conferma Manuale

### Giorni di Chiusura / Orari Speciali
Gestione chiusure straordinarie, ferie e orari speciali.
- [ ] Tabella `closures` (tenant_id, date_from, date_to, reason, type: 'closed'/'special_hours')
- [ ] Per type 'special_hours': colonne `special_open_time`, `special_close_time`
- [ ] Pagina dashboard "Chiusure e Ferie": calendario per selezionare date/range
  - Chiusura singola giornata (es. festivo)
  - Ferie (range date, es. 10-25 agosto)
  - Orari speciali per giorno specifico (es. vigilia Natale solo pranzo)
- [ ] Integrazione con AvailabilityService: giorni chiusi → zero slot nel widget
- [ ] Integrazione con calendario widget: giorni chiusi mostrati come non disponibili (grigi)

### Conferma Manuale Prenotazioni
Alcuni ristoranti vogliono approvare prima di confermare.
- [ ] Impostazione on/off nel dashboard: "Richiedi conferma manuale"
- [ ] Se attivo: prenotazione arriva come "In attesa di conferma" (nuovo stato)
- [ ] Dashboard: bottoni Conferma / Rifiuta per ogni prenotazione in attesa
- [ ] Il cliente vede "In attesa di conferma" nella pagina link magico
- [ ] Email automatica al cliente quando il ristoratore conferma o rifiuta
- [ ] Timeout opzionale: se non confermata entro X ore, notifica al ristoratore

## FASE 13: Notifiche Email
- [ ] Implementare `NotificationService.php`
- [ ] Configurare SMTP nel `.env` (o usare servizio come Mailgun/SendGrid)
- [ ] Template email: conferma prenotazione (con link magico di gestione)
- [ ] Template email: modifica prenotazione (conferma nuovi dettagli + link gestione)
- [ ] Template email: cancellazione prenotazione
- [ ] Template email: ricevuta caparra
- [ ] Template email: reset password (attualmente logga il token)

### Reminder Email
Notifica automatica prima della prenotazione per ridurre i no-show. Include link magico per modificare/cancellare.
- [ ] Cron job / task scheduler che controlla prenotazioni in arrivo
- [ ] Template email reminder con riepilogo + link gestione
- [ ] Configurazione ristoratore: quando inviare il reminder
  - 24 ore prima (default)
  - 2 ore prima
  - Entrambi (doppio reminder)
  - Disattivato
- [ ] Colonna `reminder_sent_at` su `reservations` per evitare invii doppi

## FASE 14: Promozioni e Sconti [COMPLETATA]
Badge sconto percentuale nel widget (stile TheFork). Il ristoratore gestisce la domanda incentivando le fasce orarie/giorni vuoti.

### Dashboard Ristoratore - Gestione Promozioni
- [x] Tabella `promotions` (tenant_id, name, discount_percent, type: recurring/time_slot/specific_date)
- [x] Per type 'recurring': giorni della settimana (CSV), fascia oraria opzionale
- [x] Per type 'time_slot': fascia oraria obbligatoria, giorni opzionali
- [x] Per type 'specific_date': date_from/date_to, fascia oraria opzionale
- [x] Pagina dashboard "Promozioni" in Settings con KPI (attive, prenotazioni scontate 30gg, % crescita)
- [x] CRUD completo: creazione, modifica, toggle attiva/disattiva, eliminazione
- [x] Matching server-side: findApplicable() con priorita specific_date > time_slot > recurring, sconto piu alto vince

### Widget - Badge Sconto
- [x] API availability: discount_percent per ogni slot (AvailabilityService)
- [x] Badge inline sotto l'orario nel widget (Variante B, stile compatto)
- [x] Stile: badge arancione (#E65100) con testo bianco

### Conferma e Tracking
- [x] Conferma prenotazione mostra sconto applicato
- [x] Email conferma include riga promozione
- [x] Link magico mostra lo sconto applicato
- [x] Lo sconto NON modifica la caparra Stripe (informativo, applicato al conto al tavolo)
- [x] Colonna discount_percent su reservations, lookup server-side (mai fidarsi del client)
- [x] Display in dashboard: badge nella lista prenotazioni + dettaglio nella scheda

## FASE 15: Email Broadcast
Newsletter semplice per comunicare con i clienti (eventi, nuovo menu, promozioni). Richiede piano Pro+.

- [ ] Pagina dashboard "Comunicazioni": form invio email
  - Oggetto
  - Testo libero (textarea, niente editor drag&drop)
  - Possibilita di aggiungere link nel testo
- [ ] Selezione destinatari:
  - Tutti i clienti
  - Per segmento (VIP, Abituali, Occasionali, Nuovi)
  - Chi non viene da X giorni (es. 30+ giorni)
- [ ] Template email fisso: logo ristorante in header, testo al centro, footer con indirizzo + link disiscrizione
- [ ] Link unsubscribe obbligatorio (GDPR): colonna `unsubscribed` su `customers`
- [ ] Storico invii: lista email inviate con data, oggetto, numero destinatari
- [ ] Rate limiting: max 1 broadcast al giorno per evitare spam

## FASE 16: Dashboard Prenotazione Rapida (Touch-Friendly) [COMPLETATA]
Riscrittura completa della pagina "Nuova Prenotazione" per uso su schermi touch e gestione prenotazioni telefoniche.
Obiettivo: 5 tocchi in 5 secondi per registrare una prenotazione telefonica.

### Selezione Data
- [x] Pulsanti rapidi: **Oggi** / **Domani** / **Dopodomani** (evidenziati, grandi, touch-friendly)
- [x] Pulsante "Altra data" che apre un calendario visuale (stesso stile del widget)
- [x] La data selezionata aggiorna automaticamente gli slot disponibili

### Selezione Orario
- [x] Slot raggruppati per servizio (Pranzo, Cena, ecc.) con etichette categoria pasto
- [x] Pulsanti orario con indicatore disponibilita in tempo reale (coperti liberi)
- [x] Colore verde = disponibile, giallo = quasi pieno, rosso = pieno
- [x] Caricamento dinamico via API al cambio data

### Numero Coperti
- [x] Griglia touch: pulsanti da 1 a 10 (celle grandi, facili da toccare)
- [x] Campo input numerico per gruppi superiori a 10
- [x] Aggiornamento disponibilita in tempo reale al cambio coperti

### Ricerca Cliente (da CRM)
- [x] Campo ricerca unico: digitare telefono o nome per cercare
- [x] Autocompletamento con risultati dal CRM (clienti esistenti del ristorante)
- [x] Se cliente trovato: auto-compilazione nome, cognome, email, telefono
- [x] Se cliente nuovo: compilazione manuale dei campi
- [x] Badge segmento visibile (Nuovo, Abituale, VIP) accanto al nome trovato
- [x] Avviso se cliente in blacklist: "Cliente bloccato - prenotazione non consentita"

### Sorgente Prenotazione
- [x] Selettore sorgente: **Telefono** / **Walk-in** / **Altro** (pulsanti toggle)
- [x] Sorgente salvata nella prenotazione per statistiche (da dove arrivano le prenotazioni)

### Note e Conferma
- [x] Campo note rapide: allergie, intolleranze, seggiolone, richieste particolari
- [x] Riepilogo visivo prima della conferma: data, ora, coperti, cliente, sorgente
- [x] Pulsante "Salva Prenotazione" grande e ben visibile

### Layout e UX
- [x] Pulsanti grandi (minimo 48x48px) con spaziatura generosa per schermi touch
- [x] Flusso verticale a singola colonna su mobile
- [x] Nessun dropdown nativo: tutti pulsanti e griglie
- [x] Feedback visivo immediato ad ogni selezione (evidenziazione verde)

### Design System v3.1 (applicato)
- [x] Redesign create.php con section-card, section-num, dr-form-grid, dr-summary-bar
- [x] Redesign edit.php con stessa struttura, sezione cliente read-only
- [x] CSS dashboard.css: tutte le classi dr-* aggiornate al design system

## FASE 17: Polish e UX
- [x] Paginazione server-side: clienti (25/pag, filtro segmento + ricerca), admin tenants (25/pag, ricerca)
- [ ] Filtri avanzati prenotazioni (range date, stato multiplo)
- [x] Upload logo ristorante (form settings, MIME validation, preview, rimozione, visibile solo su widget)
- [x] Responsive mobile per sidebar dashboard (toggle hamburger in app.js)
- [ ] Export prenotazioni in CSV → vedi Miglioria #2
- [x] Validazione frontend inline booking widget (per-campo on-blur, auto-clear, no alert() nativi)
- [x] Slot passati nascosti per "Oggi" (widget: hidden, dashboard: grigi cliccabili)
- [x] Statistiche clienti (pagina dedicata con KPI, top frequenza, nuovi vs ritorno, segmentazione)
- [x] Rate limiting sulle API pubbliche (completato in Security Audit)
- [x] Security headers (CSP, X-Frame-Options, etc.)
- [x] Audit logging
- [x] Brute force protection (LoginThrottle)
- [x] Pagine auth redesign v3.1 (login, forgot-password, reset-password)

## FASE 18: Billing SaaS (Stripe Subscriptions)
Modello basato sui **coperti mensili**: tutte le feature incluse, i piani differenziano solo per volume.
- [ ] Definizione piani per fasce coperti (es. Free fino a X, Starter fino a Y, Pro illimitati)
- [ ] Contatore coperti mensili per tenant (somma party_size prenotazioni del mese)
- [ ] Stripe Subscriptions collegato ai piani
- [ ] Webhook per `invoice.payment_succeeded`, `invoice.payment_failed`
- [ ] Soft limit: avviso quando il tenant si avvicina al limite coperti
- [ ] Hard limit: blocco nuove prenotazioni widget al superamento (dashboard sempre operativo)
- [ ] Pannello admin: gestione abbonamenti, storico pagamenti
- [ ] Pagina pricing pubblica per i ristoratori
- [ ] Self-service upgrade/downgrade piano dal dashboard

## FASE 19: Funzionalita Avanzate (Future)
- [ ] Notifiche SMS (Twilio o simile) - richiede piano Business
- [ ] Calendario visuale prenotazioni (vista settimanale/mensile)
- [ ] Report e statistiche (grafici coperti, no-show rate, trend) - richiede piano Pro+
- [ ] Multi-lingua (i18n)
- [ ] Integrazione Google Calendar
- [ ] Waitlist (lista d'attesa quando pieno)
- [ ] QR code per link prenotazione
- [ ] App PWA per ristoratore

## FASE 20A: Menu Digitale Consultivo [COMPLETATA]
Menu digitale pubblico per i clienti del ristorante, gestibile dalla dashboard. Design v2.1 con hero, categorie, allergeni EU, QR code.

### Database
- [x] Migration `012_create_menu_tables.sql`: tabelle `menu_categories` + `menu_items`, ALTER tenants `menu_enabled`
- [x] Migration `013_menu_enhancements.sql`: campo `icon` su menu_categories, campi `menu_hero_image`/`menu_tagline`/`opening_hours` su tenants
- [x] 14 allergeni EU obbligatori (JSON su menu_items)

### Backend
- [x] Model `MenuCategory` (CRUD, ICONS constant ~20 icone Bootstrap, sort_order, getItemCounts)
- [x] Model `MenuItem` (CRUD, ALLERGENS/ALLERGEN_ICONS/ALLERGEN_COLORS constants, findAvailableGrouped, toggles)
- [x] Controller `Dashboard/MenuController` (3 tab: index/categoriesIndex/appearanceIndex + CRUD categorie/piatti + toggle/settings)
- [x] Controller `Menu/MenuPageController` (pagina pubblica standalone, check menu_enabled)
- [x] Controller `Api/MenuApiController` (GET `/api/v1/tenants/{slug}/menu` JSON)
- [x] Rotte dashboard: `/menu`, `/menu/categories`, `/menu/appearance` + CRUD items/categories
- [x] Rotta pubblica: `/{slug}/menu`
- [x] Upload immagini piatti (2MB, jpg/png/webp) + hero image (5MB)

### Dashboard (3 tab, pattern Settings)
- [x] Tab **Piatti**: KPI cards, lista piatti raggruppati per categoria, CTA sidebar con preview + QR + link pubblico
- [x] Tab **Categorie**: lista con icone + conteggio piatti, form nuova categoria con icon picker, modal modifica
- [x] Tab **Aspetto**: toggle menu pubblico, config tagline/orari/hero image, preview header, QR code
- [x] Sidebar menu: voce "Menu" con icona `bi-book`

### Pagina Pubblica v2.1 (standalone, no layout)
- [x] Hero con foto/sfondo scuro, logo, nome, tagline, orari
- [x] Griglia categorie landing (card con icona + conteggio piatti)
- [x] Sticky nav con scroll spy + search
- [x] Sezione "Piatti del Giorno" (card ambrate)
- [x] Sezioni categoria con icone, piatti con immagine lazy, prezzo, descrizione
- [x] Allergeni: pill tag colorati per esteso (`.dm-at-{key}`, 14 colori)
- [x] Legenda allergeni collassabile
- [x] CTA footer "Prenota un tavolo"
- [x] Footer branding: "© {anno} Evulery · by alagias. - Soluzioni per il web"

### CSS
- [x] `public/assets/css/menu.css` (NUOVO): stili pagina pubblica, namespace `.dm-*`, CSS custom properties `--dm-*`
- [x] `public/assets/css/dashboard.css` (MODIFICA): stili admin `.dm-admin-*`, CTA sidebar, category group headers

### Integrazioni
- [x] Email conferma prenotazione: link "Consulta il menu" se menu_enabled
- [x] Pagina conferma booking: link menu se menu_enabled
- [x] QR code via `api.qrserver.com` (no dipendenze esterne)

### Wireframe
- [x] `wireframes/menu-dashboard-tabs.html`: layout 3 tab con switch interattivo

---

## FASE 20: Deploy Produzione
- [ ] Configurare server (VPS con Apache/Nginx + PHP + MySQL)
- [ ] Migrare database
- [ ] Configurare HTTPS (Let's Encrypt)
- [ ] Wildcard DNS per domini custom tenants
- [ ] Configurare Stripe live keys
- [ ] Backup automatico database
- [ ] Monitoring errori (Sentry o simile)

---

## Bug Noti / Da Verificare in Produzione
Vedi `check-live.md` per la checklist completa. Punti principali:
- [x] Slot orfani: fallback gruppo "Altro" in `getGroupedSlots()` per slot fuori da categorie pasto
- [x] startHour dinamico: tabella Orari e Coperti parte dall'ora minima tra categorie attive e slot esistenti (floor 9)
- [x] Slot passati per "Oggi": widget li nasconde, dashboard li mostra grigi ma cliccabili (walk-in)
- [ ] Calendario mobile dashboard home: verificare su device reale (≥375px)
- [ ] Calendario mobile pagina prenotazioni: tagliato su mobile (mancano sab/dom)
- [ ] CSP: verificare che non ci siano policy server-level che sovrascrivano .htaccess
- [ ] HTTPS: verificare mixed content e cookie Secure flag

---

## Credenziali Test
| Ruolo | Email | Password |
|-------|-------|----------|
| Super Admin | admin@evulery.pro | admin123 |
| Owner Demo | mario@trattoriadamario.it | admin123 |

## URL Locali
- Login: http://localhost/evulery.pro1.0/auth/login
- Admin: http://localhost/evulery.pro1.0/admin
- Dashboard: http://localhost/evulery.pro1.0/dashboard
- Booking: http://localhost/evulery.pro1.0/trattoria-da-mario
- API: http://localhost/evulery.pro1.0/api/v1/tenants/trattoria-da-mario/availability?date=2026-02-28&party_size=2
- API (grouped): http://localhost/evulery.pro1.0/api/v1/tenants/trattoria-da-mario/availability?date=2026-03-02&party_size=2&grouped=1
- Menu pubblico: http://localhost/evulery.pro1.0/trattoria-da-mario/menu
- Menu API: http://localhost/evulery.pro1.0/api/v1/tenants/trattoria-da-mario/menu
