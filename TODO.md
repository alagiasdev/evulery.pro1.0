# Evulery.Pro 1.0 - Prossimi Passi

## Stato Attuale
Fasi completate: Foundation, Auth, Admin Panel, Multi-Tenant, Slot/Capacita, Booking Widget, Dashboard Ristoratore.
Il sistema funziona end-to-end: login, gestione ristoranti, prenotazioni da widget e dashboard.

**Completato recentemente:**
- [x] Redesign widget prenotazione stile TheFork (calendario visuale, 4 step, slot raggruppati per categoria pasto)
- [x] Tabella `meal_categories` + Model + API `?grouped=1`
- [x] Dashboard gestione categorie pasto
- [x] Social proof ("Gia X prenotazioni per oggi")

---

## FASE 8: Integrazione Stripe Caparra
- [ ] Configurare chiavi Stripe test nel `.env`
- [ ] Implementare `StripeService.php` (creazione Checkout Session)
- [ ] Collegare il flusso: form prenotazione → redirect Stripe → conferma
- [ ] Gestire webhook `checkout.session.completed` e `checkout.session.expired`
- [ ] Pagina successo/annullamento pagamento
- [ ] Impostazioni caparra nel dashboard (gia creata la UI, manca il collegamento Stripe)
- [ ] Testare con carte test Stripe

## FASE 9: Sistema Piani e Feature Flags
Architettura flessibile per gestire i piani commerciali con feature gating dinamico.

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
- [ ] Gate: `PlanService::can('crm')` - richiede piano Starter+
- [ ] Tabella `customers` (tenant_id, first_name, last_name, email UNIQUE per tenant, phone, total_bookings, total_noshow, last_booking_at)
- [ ] Auto-creazione/aggiornamento customer ad ogni prenotazione (match per email + tenant)
- [ ] Dashboard ristoratore: lista clienti con storico visite, no-show rate, party size medio
- [ ] Scheda cliente: tutte le prenotazioni passate e future
- [ ] Segmentazione automatica: Nuovo (1 visita), Occasionale (2-3), Abituale (4-9), VIP (10+)
- [ ] Top clienti per frequenza nel periodo selezionato
- [ ] Statistiche: nuovi vs ritorno (percentuale sul totale prenotazioni)

### Link Magico - Gestione Prenotazione (self-service cliente)
Il cliente gestisce la prenotazione tramite un link unico ricevuto via email. Nessuna registrazione.
- [ ] Gate: `PlanService::can('manage_link')` - richiede piano Starter+
- [ ] Colonna `manage_token` (VARCHAR 64, UNIQUE) sulla tabella `reservations`, generato alla creazione
- [ ] Route: `/{slug}/booking/manage/{token}` → pagina di gestione
- [ ] Vista dettagli: data, ora, persone, stato prenotazione
- [ ] Modifica prenotazione: cambio data, ora, numero persone (con verifica disponibilita in tempo reale)
- [ ] Cancellazione prenotazione: conferma + motivo opzionale
- [ ] Regole ristoratore: "modifiche consentite fino a X ore prima" (configurabile da dashboard)
- [ ] Scadenza link: funziona fino a X ore dopo il termine della prenotazione
- [ ] Notifica al ristoratore nel dashboard ad ogni modifica/cancellazione del cliente

### Impostazioni Dashboard Ristoratore
- [ ] Sezione "Gestione prenotazione cliente" nelle impostazioni
  - Tempo minimo per modifica (es. 2h prima)
  - Tempo minimo per cancellazione (es. 1h prima)
  - Permettere modifica data (si/no)
  - Permettere modifica persone (si/no)

### Blacklist Clienti
- [ ] Gate: `PlanService::can('blacklist')` - richiede piano Starter+
- [ ] Flag `is_blocked` sulla tabella `customers`
- [ ] Quando cliente bloccato tenta di prenotare (match email) → messaggio "Contatta il ristorante telefonicamente"
- [ ] Bottone blocca/sblocca nella scheda cliente
- [ ] Indicatore visivo nella lista clienti (badge rosso)

### Note Clienti
- [ ] Gate: `PlanService::can('customer_notes')` - richiede piano Starter+
- [ ] Campo `notes` (TEXT) sulla tabella `customers`
- [ ] Textarea nella scheda cliente per aggiungere/modificare note
- [ ] Note visibili nella vista prenotazione in arrivo (es. "Allergia noci", "Preferisce veranda")

## FASE 12: Giorni di Chiusura e Conferma Manuale

### Giorni di Chiusura / Orari Speciali
Gestione chiusure straordinarie, ferie e orari speciali.
- [ ] Gate: `PlanService::can('closure_days')` - richiede piano Starter+
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
- [ ] Gate: `PlanService::can('manual_confirmation')` - richiede piano Pro+
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
- [ ] Gate: `PlanService::can('reminder')` - richiede piano Starter+
- [ ] Cron job / task scheduler che controlla prenotazioni in arrivo
- [ ] Template email reminder con riepilogo + link gestione
- [ ] Configurazione ristoratore: quando inviare il reminder
  - 24 ore prima (default)
  - 2 ore prima
  - Entrambi (doppio reminder)
  - Disattivato
- [ ] Colonna `reminder_sent_at` su `reservations` per evitare invii doppi

## FASE 14: Promozioni e Sconti
Badge sconto percentuale nel widget (stile TheFork). Il ristoratore gestisce la domanda incentivando le fasce orarie/giorni vuoti.

### Dashboard Ristoratore - Gestione Promozioni
- [ ] Gate: `PlanService::can('promotions')` - richiede piano Pro+
- [ ] Tabella `promotions` (tenant_id, name, discount_percent, type: 'recurring'/'specific_date')
- [ ] Per type 'recurring': giorni della settimana (bitmask o colonne lun-dom), fascia oraria opzionale
- [ ] Per type 'specific_date': date_from, date_to
- [ ] Campi: is_active, valid_from, valid_to (periodo validita della regola)
- [ ] Pagina dashboard "Promozioni": lista promozioni + form creazione
  - Giorno ricorrente (es. ogni martedi -20%)
  - Fascia oraria (es. 18:00-19:00 -15% ogni giorno)
  - Data specifica (es. 15 marzo -30%)
  - Combinato (es. ogni lunedi 12:00-14:00 -25%)
- [ ] Attiva/disattiva promozione con toggle

### Widget - Badge Sconto
- [ ] API availability: includere info promozione per ogni slot (discount_percent se applicabile)
- [ ] Badge percentuale sotto l'orario nel widget (es. "-20%")
- [ ] Stile: badge colorato (verde/arancione) come TheFork
- [ ] CSS: classe `.bw-slot-discount` per il badge

### Conferma e Tracking
- [ ] Conferma prenotazione mostra "Promozione applicata: -X% sul conto"
- [ ] Email conferma include info sconto
- [ ] Link magico mostra lo sconto applicato
- [ ] Lo sconto NON modifica la caparra Stripe (la caparra e un deposito, lo sconto si applica al conto al tavolo)
- [ ] Statistiche: prenotazioni con promozione vs prezzo pieno, efficacia per promozione

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
- [ ] Gate: `PlanService::can('email_broadcast')` - richiede piano Pro+

## FASE 16: Dashboard Prenotazione Rapida (Touch-Friendly)
Riscrittura completa della pagina "Nuova Prenotazione" per uso su schermi touch e gestione prenotazioni telefoniche.
Obiettivo: 5 tocchi in 5 secondi per registrare una prenotazione telefonica.

### Selezione Data
- [ ] Pulsanti rapidi: **Oggi** / **Domani** / **Dopodomani** (evidenziati, grandi, touch-friendly)
- [ ] Pulsante "Altra data" che apre un calendario visuale (stesso stile del widget)
- [ ] La data selezionata aggiorna automaticamente gli slot disponibili

### Selezione Orario
- [ ] Slot raggruppati per servizio (Pranzo, Cena, ecc.) con etichette categoria pasto
- [ ] Pulsanti orario con indicatore disponibilita in tempo reale (coperti liberi)
- [ ] Colore verde = disponibile, giallo = quasi pieno, rosso = pieno
- [ ] Caricamento dinamico via API al cambio data

### Numero Coperti
- [ ] Griglia touch: pulsanti da 1 a 10 (celle grandi, facili da toccare)
- [ ] Campo input numerico per gruppi superiori a 10
- [ ] Aggiornamento disponibilita in tempo reale al cambio coperti

### Ricerca Cliente (da CRM)
- [ ] Campo ricerca unico: digitare telefono o nome per cercare
- [ ] Autocompletamento con risultati dal CRM (clienti esistenti del ristorante)
- [ ] Se cliente trovato: auto-compilazione nome, cognome, email, telefono
- [ ] Se cliente nuovo: compilazione manuale dei campi
- [ ] Badge segmento visibile (Nuovo, Abituale, VIP) accanto al nome trovato
- [ ] Avviso se cliente in blacklist: "Cliente bloccato - prenotazione non consentita"

### Sorgente Prenotazione
- [ ] Selettore sorgente: **Telefono** / **Walk-in** / **Altro** (pulsanti toggle)
- [ ] Sorgente salvata nella prenotazione per statistiche (da dove arrivano le prenotazioni)

### Note e Conferma
- [ ] Campo note rapide: allergie, intolleranze, seggiolone, richieste particolari
- [ ] Riepilogo visivo prima della conferma: data, ora, coperti, cliente, sorgente
- [ ] Pulsante "Salva Prenotazione" grande e ben visibile

### Layout e UX
- [ ] Pulsanti grandi (minimo 48x48px) con spaziatura generosa per schermi touch
- [ ] Flusso verticale a singola colonna su mobile
- [ ] Nessun dropdown nativo: tutti pulsanti e griglie
- [ ] Feedback visivo immediato ad ogni selezione (evidenziazione verde)

## FASE 17: Polish e UX
- [ ] Paginazione nelle liste (prenotazioni, clienti, tenants)
- [ ] Filtri avanzati prenotazioni (range date, stato multiplo)
- [ ] Upload logo ristorante (form + salvataggio in `public/uploads/tenants/`)
- [ ] Responsive mobile per sidebar dashboard (toggle hamburger)
- [ ] Export prenotazioni in CSV (richiede piano Starter+)
- [ ] Validazione frontend piu robusta (feedback inline nei form)
- [ ] Rate limiting sulle API pubbliche

## FASE 18: Billing SaaS (Stripe Subscriptions)
- [ ] Stripe Subscriptions collegato a `plan_definitions`
- [ ] Webhook per `invoice.payment_succeeded`, `invoice.payment_failed`
- [ ] Disattivazione automatica tenant se pagamento fallisce
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

## FASE 20: Deploy Produzione
- [ ] Configurare server (VPS con Apache/Nginx + PHP + MySQL)
- [ ] Migrare database
- [ ] Configurare HTTPS (Let's Encrypt)
- [ ] Wildcard DNS per domini custom tenants
- [ ] Configurare Stripe live keys
- [ ] Backup automatico database
- [ ] Monitoring errori (Sentry o simile)

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
