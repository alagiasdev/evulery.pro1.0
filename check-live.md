# Checklist Pre-Deploy / Post-Deploy

Controlli da eseguire quando si pubblica su server di produzione.

---

## 1. Mobile Calendar Dropdown

**Problema riscontrato in dev:** il calendario "Altra data" nella dashboard si apre tagliato su schermi molto stretti (simulatore Chrome mobile). Su desktop e telefoni reali (≥375px) funziona correttamente.

**Cosa verificare:**
- Aprire la dashboard da telefono reale (iPhone, Android)
- Cliccare "Altra data" → il dropdown calendario deve apparire completo (7 colonne visibili)
- Navigare mesi avanti/indietro
- Selezionare una data → la pagina deve ricaricarsi con la data scelta

**Se non funziona:** il dropdown ha `width: 280px` con `right: 0` su mobile. Se serve supportare schermi < 360px, convertire in full-width sotto la date-strip (non posizionato relativo al bottone).

**File coinvolti:**
- `public/assets/css/dashboard.css` → `.home-cal-dropdown` + media query 768px
- `views/dashboard/home.php` → struttura `.date-chip-cal`

---

## 2. CSP (Content Security Policy)

**Attuale:** `.htaccess` include `'unsafe-inline'` per `script-src`.

**Cosa verificare:**
- Se il server di produzione ha una CSP diversa (es. nginx/Apache config a livello server), potrebbe sovrascrivere quella in `.htaccess`
- Tutti gli `<script>` inline nelle views devono funzionare
- Nessun errore CSP nella console del browser

---

## 3. HTTPS / Mixed Content

**Cosa verificare:**
- Le risorse CDN (Bootstrap CSS/JS, Bootstrap Icons) usano `https://`
- Nessun warning "mixed content" nella console
- I cookie di sessione devono avere flag `Secure` in produzione

---

## 4. Percorsi e URL

**Cosa verificare:**
- La funzione `url()` genera URL corretti con il dominio di produzione
- La funzione `asset()` punta ai file CSS/JS corretti
- L'API availability (`/api/v1/tenants/{slug}/availability`) risponde correttamente

---

## 5. Form e CSRF

**Cosa verificare:**
- Tutte le form POST funzionano (CSRF token valido)
- "Segna Arrivato" dalla lista prenotazioni funziona (non naviga alla pagina dettaglio)
- "Annulla arrivo" dalla pagina dettaglio funziona
- Cambio status prenotazione (conferma, no-show, annulla)

---

## 6. Calendario Sidebar (Dashboard)

**Cosa verificare:**
- Mini-calendario nel pannello destro mostra il mese corrente
- I giorni con prenotazioni hanno il pallino verde
- Click su un giorno → ricarica dashboard con quella data

---

## 7. Settings Pages

**Cosa verificare:**
- Tutte e 5 le tab settings funzionano (Generali, Orari e Coperti, Categorie Pasto, Caparra, Dominio)
- Salvataggio di ogni sezione
- Toggle on/off nelle Categorie Pasto e Caparra
- Copia link/CNAME nella pagina Dominio

---

## 8. Calendario Mobile - Pagina Elenco Prenotazioni

**Problema riscontrato in dev:** il calendario dropdown nella pagina elenco prenotazioni (filtro data) viene tagliato su mobile — mancano le colonne "sab" e "dom".

**Cosa verificare:**
- Aprire la lista prenotazioni da telefono reale
- Cliccare l'icona calendario nel filtro data → il dropdown deve mostrare tutte e 7 le colonne (lun-dom)
- Selezionare una data → il filtro deve aggiornarsi

**Da sistemare:** il dropdown calendario deve essere completamente visibile su schermi ≥ 375px. Valutare `right: 0` o centratura rispetto allo schermo, come fatto per il calendario della dashboard home.

---

## ~~9. Slot passati visibili nella creazione prenotazione~~ → RISOLTO

**Soluzione implementata:**
- **API**: flag `is_past` su ogni slot quando la data è oggi e l'orario è passato (`AvailabilityService`)
- **Widget pubblico**: slot passati nascosti completamente (il cliente non li vede)
- **Dashboard**: slot passati visibili in grigio con bordo tratteggiato e label "Passato", ma selezionabili (per walk-in)

---

## 10. Stripe - Configurazione in Produzione

**Stato attuale:** la logica Stripe è predisposta ma non collegata. Le variabili `.env` (`STRIPE_SECRET_KEY`) sono globali — da convertire in chiavi per-tenant.

**Da implementare al deploy:**
- Ogni ristoratore deve inserire le proprie chiavi Stripe (public + secret) dal pannello Settings > Caparra
- Salvare le chiavi come campi tenant (`stripe_public_key`, `stripe_secret_key`)
- Implementare `StripeService.php` con Checkout Session per-tenant
- Webhook endpoint `/stripe/webhook` (già previsto nelle routes) per `checkout.session.completed` e `checkout.session.expired`
- Pagine success/cancel pagamento (routes già presenti: `/{slug}/booking/success`, `/{slug}/booking/cancel`)
- Richiede URL pubblico con HTTPS per i webhook Stripe
- Testare con carte test Stripe prima di andare live

**File coinvolti:**
- `app/Controllers/Dashboard/SettingsController.php` → form chiavi Stripe per-tenant
- `app/Models/Tenant.php` → campi `stripe_public_key`, `stripe_secret_key`
- `app/Services/StripeService.php` → da creare
- `app/Controllers/Api/WebhookController.php` → handler webhook
- `app/Controllers/Api/ReservationApiController.php` → creazione Checkout Session dopo prenotazione
- `views/dashboard/settings/deposit.php` → form inserimento chiavi