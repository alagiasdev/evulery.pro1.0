# Checklist Pre-Deploy / Post-Deploy

Controlli da eseguire quando si pubblica su server di produzione. Ultimo aggiornamento: 2026-06-06.

---

## 1. Migration DB pending

**Prima azione dopo `git pull`** su VPS: applicare le migration in attesa.

- Apri `https://dash.evulery.it/admin/migrations` (super admin)
- Verifica il count "Pending" — applica con il pulsante se > 0
- Lo script `scripts/migrate.php` resta disponibile da CLI come fallback

**Migration recenti significative** (verifica siano applicate):
- `059_notification_sounds.sql` — campi audio su tenants
- `060_riders.sql` — tabella riders + `orders.rider_id`
- `061_tenant_website_url.sql` — sito web esterno del ristoratore

---

## 2. Asset statici nuovi (file MP3 audio)

Quando vengono deployate nuove notifiche sonore, verificare che i file siano arrivati sul VPS:

```
public/assets/sounds/notification-master.mp3
public/assets/sounds/notification-cancellation.mp3
public/assets/sounds/notification-deposit.mp3
public/assets/sounds/notification-order.mp3
public/assets/sounds/notification-review.mp3
```

Sostituibili drop-in: il cache busting filemtime (`asset()` helper) propaga la nuova versione automaticamente.

---

## 3. HTTPS / Mixed Content

- Risorse CDN (Bootstrap CSS/JS, Bootstrap Icons) usano `https://`
- Nessun warning "mixed content" nella console
- Cookie di sessione con flag `Secure` in produzione (settato automaticamente da `Session::start()` se `HTTPS=on`)

---

## 4. CSP — Content Security Policy ✅

Implementato con nonce dinamico per request (`App::boot()`). Nessun `unsafe-inline` su `script-src`. Tutti gli inline `<script>` hanno `nonce="<?= csp_nonce() ?>"`.

**Cosa verificare:**
- Nessun errore CSP nella console del browser
- Inline event handlers (`onclick`) NON ammessi → usare event listener con nonce
- Se il server ha CSP a livello nginx/Apache, potrebbe sovrascrivere quella PHP — verificare assenza header duplicati

---

## 5. URL e percorsi

- `url()` genera URL corretti con dominio produzione
- `asset()` con cache busting (`?v=filemtime`)
- API availability (`/api/v1/tenants/{slug}/availability`) risponde correttamente
- Print endpoint ordini (`/dashboard/orders/{id}/print/kitchen|receipt`) aperti in nuova tab

---

## 6. CSRF e form

- Form POST funzionano (CSRF token valido)
- Azioni rapide su lista prenotazioni: Conferma / Arrivato / No-show / Annulla
- Cambio status ordini (Accetta / In preparazione / Pronto / Consegnato)
- Assegnazione rider via popup
- Salvataggio settings (Generale, Notifiche, Ordini, Tavoli, Caparra, Recensioni)

---

## 7. Caparra Stripe — config per-tenant ✅

**Stato attuale** (post commit `6792d08`): implementato con 4 tipi (`info`, `link`, `stripe`, `guarantee`) + crittografia AES-256-GCM per chiavi tenant.

**Cosa verificare:**
- Settings → Caparra: ristoratore può inserire le proprie chiavi Stripe (cifrate a riposo)
- Webhook endpoint `/api/v1/webhook/stripe` riceve eventi (`checkout.session.completed`, `setup_intent.succeeded`)
- Test con carte Stripe di test (4242 4242 4242 4242)
- HTTPS pubblico richiesto per i webhook

---

## 8. Fase C — Auto-refresh dashboard

Polling `/dashboard/heartbeat/reservations` + `/dashboard/heartbeat/floor` ogni 60s.

**Cosa verificare:**
- Apri `/dashboard/reservations` → resta su 60s senza toccare → cambia prenotazione da altra tab → banner ambra "X modifiche" appare entro 60s
- Idem su `/dashboard` home e `/dashboard/sala` (operativa)
- Pausa polling su tab nascosta (`document.hidden`)
- ETag/304 nella response (DevTools → Network)

---

## 9. Push notifications

**Stato attuale** (post commit `4062a19` + `a6f1bd3` + `55d5a38`): bug fix mobile + banner opt-in + race condition `getStatus`.

**Cosa verificare:**
- VAPID keys in `.env` di produzione (`VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT`)
- Banner verde "Attiva notifiche" su `/dashboard` se non subscribed
- Click "Attiva" → dialog browser permesso → subscribe → banner sparisce
- Su iPhone Safari NON installato → banner azzurro "Aggiungi a schermata Home"
- `/dashboard/settings/notifications` → box "Notifiche attive su questo dispositivo" verde

---

## 10. Notifiche audio brandizzate

Modulo `public/assets/js/notification-sounds.js` caricato dal layout dashboard se `tenant_can('push_notifications')`.

**Cosa verificare:**
- File 5 MP3 in `public/assets/sounds/` (vedi punto 2)
- Settings → Notifiche audio: master + volume + 5 toggle per evento
- Pulsante "Anteprima suono" → si sente il master (dopo prima interazione utente per autoplay policy)
- Cancellazione dal cliente (`/manage/{token}/cancel`) → suono al ristoratore via polling
- Cancellazione dal ristoratore (dashboard) → NESSUN suono (filtro `cancelled_by='staff'`)

---

## 11. Stampa ordini MVP

Endpoint `GET /dashboard/orders/{id}/print/kitchen` e `/print/receipt`.

**Cosa verificare:**
- Pulsante "🔥 Cucina" su card kanban status `accepted` / `preparing` → apre nuova tab ticket sintetico
- Pulsante "🖨 Stampa" su card kanban status `ready` → apre nuova tab ricevuta completa
- Auto-dialog stampa parte ~300ms dopo il caricamento
- Toolbar con pulsante `(i)` sobrio → mostra hint configurazione termica 80mm in Chrome
- Sezione staccabile "Conferma di consegna" presente solo su delivery, NON su asporto
- Footer ricevuta: `tenant.website_url` se valorizzato, fallback widget `dash.evulery.it/{slug}`

---

## 12. Settings pages

Tutte le tab settings funzionano e salvano correttamente. Verificare almeno:
- **Generale** — nome, email, phone, address, website_url, logo
- **Orari e Coperti** — slot, capacità per fascia
- **Categorie pasto** — toggle attivazione
- **Tavoli** (Enterprise) — CRUD + priorità + auto-assegnazione
- **Notifiche** — email toggles + template push + sezione audio + sezione browser
- **Ordini** (con `online_ordering`) — modalità, tempo prep, intervallo slot, max per slot, orari, payment
- **Caparra** — tipo (info/link/stripe/guarantee), giorni/fasce condizionali
- **Recensioni** — quiet hour, delay, soglia filtro
- **Rider** (se `delivery_mode != none`) — anagrafica + statistiche

---

## 13. Calendario mobile (legacy)

Calendario "Altra data" in dashboard e lista prenotazioni: testare su iPhone/Android reale (≥ 375px) che mostri tutte e 7 le colonne giorno (lun-dom). Su simulatori molto stretti può tagliare — non bloccante per device reali.

> Fix completo tracciato nel backlog di cleanup deferito (`project_deferred_cleanup`).

---

## 14. Performance check rapidi

- Heartbeat polling: query `MAX(updated_at)` + `COUNT(*)` con indice `idx_tenant_date` → <2ms
- Kanban order polling 15s: con LEFT JOIN riders, su tenant medio resta < 50ms
- Cache hit ratio (No-show rate + Source breakdown): cached 15 min via `Cache::remember`
- PWA service worker registrato e attivo (DevTools → Application → Service Workers)
