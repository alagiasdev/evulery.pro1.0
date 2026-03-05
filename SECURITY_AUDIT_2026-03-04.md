# SECURITY AUDIT REPORT - Evulery.Pro 1.0
**Data:** 2026-03-04
**Ultimo aggiornamento:** 2026-03-04
**Scope:** Full codebase audit (sicurezza, bug, qualita)

---

## STATO FIX - FASE 1

| # | Fix | Stato | Data |
|---|-----|-------|------|
| C3 + M7 | .htaccess: bloccare .env e directory sensibili | COMPLETATO | 2026-03-04 |
| C5 | Session cookie: `secure` dinamico | COMPLETATO | 2026-03-04 |
| A2 | `APP_DEBUG=false` in produzione | COMPLETATO | 2026-03-04 |
| C6a | Rimuovere log token reset password | COMPLETATO | 2026-03-04 |
| A1 | Security Headers HTTP (.htaccess) | COMPLETATO | 2026-03-04 |
| A5 | SRI hashes su CDN Bootstrap (4 layout) | COMPLETATO | 2026-03-04 |
| M1 | `!==` strict comparison per tenant isolation | COMPLETATO | 2026-03-04 |
| A7 | Check ruolo su force_booking | COMPLETATO | 2026-03-04 |

**Fase 1: 8/8 completati** - Tutti i quick fix sono stati applicati.

## STATO FIX - FASE 2

| # | Fix | Stato | Data |
|---|-----|-------|------|
| C2 | Filtro tenant_id su API show/cancel (IDOR) | COMPLETATO | 2026-03-04 |
| C1 | Transaction + FOR UPDATE su booking | COMPLETATO | 2026-03-04 |
| C4 | Brute force protection su login | COMPLETATO | 2026-03-04 |
| C6b | Transaction su password reset | COMPLETATO | 2026-03-04 |

**Fase 2: 4/4 completati** - Tutte le vulnerabilita critiche risolte.

---

## RIEPILOGO ESECUTIVO

| Severita | Conteggio | Risolti |
|----------|-----------|---------|
| CRITICA  | 6         | 6 (C1, C2, C3, C4, C5, C6) |
| ALTA     | 7         | 7 (A1, A2, A3, A4, A5, A6, A7) |
| MEDIA    | 8         | 8 (M1, M2, M3, M4, M5, M6, M7, M8) |
| BASSA    | 4         | 4 (B1, B2, B3, B4) |
| **Totale** | **25** | **25**  |

**Punti di forza:** SQL injection ZERO (PDO prepared statements ovunque), CSRF ben implementato, escaping XSS con `e()` e `escapeHtml()` nel JS, nessun segreto esposto nel frontend, validazione server-side presente.

**Punti deboli critici:** ~~.env esposto via web~~, ~~session cookie non sicuro~~, ~~IDOR API~~. **Tutti risolti.**

---

## CRITICA (6) - Azione immediata richiesta

### ~~C1. Race Condition: Double-Booking senza Transaction Lock~~ RISOLTO
**File:** `app/Services/AvailabilityService.php:131-157`, `app/Controllers/Dashboard/ReservationsController.php:122-137`, `app/Controllers/Api/ReservationApiController.php:76-92`
**Problema:** Il check disponibilita e la creazione prenotazione erano due operazioni separate non atomiche. Due richieste concorrenti potevano entrambe passare il check e creare prenotazione, superando la capacita massima.
**Impatto:** Overbooking del ristorante, caos operativo, perdita di reputazione.
**Fix:** Nuovo metodo `atomicBook()` in AvailabilityService: `beginTransaction()` + `SELECT ... FOR UPDATE` su time_slots del giorno (serializza tentativi concorrenti), re-check `canBook()` dentro la transazione, `create()` atomico, `commit()`. Entrambi i controller (API + Dashboard) ristrutturati per usare `atomicBook()`. Force booking bypassa il lock.
**Stato:** COMPLETATO (2026-03-04)

### ~~C2. API Tenant Isolation Bypass (IDOR)~~ RISOLTO
**File:** `app/Controllers/Api/ReservationApiController.php:116-172`
**Problema:** Gli endpoint `show` e `cancel` delle prenotazioni via API non filtravano per `tenant_id`. Bastava conoscere ID prenotazione + email del cliente per accedere/cancellare prenotazioni di QUALSIASI ristorante.
**Impatto:** Violazione dati multi-tenant, cancellazione prenotazioni altrui, sabotaggio concorrenza.
**Fix:** Aggiunto resolve tenant da slug + validazione `(int)$reservation['tenant_id'] !== (int)$tenant['id']` in entrambi i metodi `show()` e `cancel()`. Ora tutti e 3 i metodi API (`store`, `show`, `cancel`) seguono lo stesso pattern di tenant isolation.
**Stato:** COMPLETATO (2026-03-04)

### ~~C3. File .env Accessibile via Web~~ RISOLTO
**File:** `.env`, `.htaccess`
**Problema:** Il root `.htaccess` non blocca l'accesso diretto al file `.env`. Un attaccante puo leggere credenziali DB, chiavi Stripe, configurazione mail con una semplice richiesta GET.
**Impatto:** Compromissione completa del database, furto chiavi Stripe, accesso email.
**Fix:** Aggiunto `Require all denied` per `.env`, `.env.example`, `.gitignore` + `RewriteRule` per bloccare `app|config|storage|vendor|database|bootstrap`.
**Stato:** COMPLETATO (2026-03-04)

### ~~C4. Nessuna Protezione Brute Force sul Login~~ RISOLTO
**File:** `app/Controllers/Auth/LoginController.php:34-49`, `app/Services/LoginThrottle.php`
**Problema:** Nessun rate limiting, nessun lockout dopo N tentativi falliti, nessun delay. Un attaccante poteva provare migliaia di password al minuto.
**Impatto:** Account takeover tramite brute force.
**Fix:** Creata tabella `login_attempts` e classe `LoginThrottle`. Max 5 tentativi per email / 20 per IP in 15 minuti. Login bloccato con messaggio "Troppi tentativi, riprova tra X minuti". Tentativi cancellati su login riuscito. Auto-cleanup record > 1 ora.
**Stato:** COMPLETATO (2026-03-04)

### ~~C5. Session Cookie: `secure=false` hardcoded~~ RISOLTO
**File:** `app/Core/Session.php:13`
**Problema:** Il cookie di sessione e inviato anche su HTTP (non solo HTTPS), esposto a intercettazione MITM.
**Impatto:** Session hijacking su reti non sicure.
**Fix:** Impostato `'secure' => $isHttps` con detection dinamica `$_SERVER['HTTPS']`.
**Stato:** COMPLETATO (2026-03-04)

### ~~C6. Password Reset Token: Race Condition + Log in chiaro~~ RISOLTO
**File:** `app/Controllers/Auth/PasswordController.php:44, 86-115`
**Problema:** (a) Token di reset loggato in chiaro nei file di log. (b) Il flusso check-token / update-password / mark-used non e atomico - due richieste simultanee possono usare lo stesso token.
**Impatto:** Account takeover tramite log file o race condition.
**Fix (a):** COMPLETATO (2026-03-04) - Rimosso log token, sostituito con messaggio sicuro.
**Fix (b):** COMPLETATO (2026-03-04) - `doReset()` wrappato in `beginTransaction()` con `SELECT ... FOR UPDATE` sul token. Token marcato `used=1` prima dell'update password. `rollBack()` su errore o token invalido.
**Stato:** COMPLETATO (2026-03-04)

---

## ALTA (7) - Da risolvere entro 1 settimana

### ~~A1. Security Headers HTTP mancanti~~ RISOLTO
**File:** `public/.htaccess`
**Problema:** Mancano: `X-Content-Type-Options`, `X-Frame-Options`, `Content-Security-Policy`, `Strict-Transport-Security`, `Referrer-Policy`, `Permissions-Policy`.
**Fix:** Aggiunti tutti i security headers in `public/.htaccess` con CSP che consente solo CDN Bootstrap.
**Stato:** COMPLETATO (2026-03-04)

### ~~A2. APP_DEBUG=true in .env~~ RISOLTO
**File:** `.env:3`
**Problema:** In produzione mostra stack trace, path dei file, dettagli errore agli utenti.
**Fix:** Impostato `APP_DEBUG=false` in `.env`.
**Stato:** COMPLETATO (2026-03-04)

### ~~A3. Nessun Timeout/Idle Logout delle Sessioni~~ RISOLTO
**File:** `app/Core/Session.php:8-9, 18-35`
**Problema:** `lifetime=0` significava che la sessione durava finche il browser restava aperto. Nessun idle timeout. Su computer pubblici la sessione restava attiva indefinitamente.
**Impatto:** Accesso non autorizzato su computer condivisi/dimenticati.
**Fix:** Aggiunto idle timeout 30 min (`_last_activity`) e absolute timeout 8 ore (`_created_at`). Sessione distrutta e ricreata automaticamente al superamento dei limiti.
**Stato:** COMPLETATO (2026-03-04)

### ~~A4. Email di Reset Password non inviata~~ RISOLTO
**File:** `app/Controllers/Auth/PasswordController.php:44`, `app/Services/MailService.php`
**Problema:** Il token viene generato e loggato ma l'email NON viene mai inviata. L'utente vede "Controlla la tua email" ma non riceve nulla.
**Impatto:** Feature non funzionante, token esposto solo nei log.
**Fix:** Installato PHPMailer v7. Creato `MailService` con supporto SMTP (configurabile via .env: MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_ENCRYPTION). Metodo statico `sendPasswordReset()` con template HTML. Integrato in `PasswordController::sendReset()`. Config aggiornata in `config/mail.php` e `.env/.env.example`.
**Stato:** COMPLETATO (2026-03-05)

### ~~A5. CDN Bootstrap senza SRI (Subresource Integrity)~~ RISOLTO
**File:** `views/layouts/booking.php`, `views/layouts/dashboard.php`, `views/layouts/auth.php`, `views/layouts/admin.php`
**Problema:** Bootstrap CSS/JS caricati da CDN senza attributi `integrity` e `crossorigin`. Se il CDN viene compromesso, codice malevolo viene eseguito.
**Fix:** Aggiunti hash SRI `integrity` + `crossorigin="anonymous"` a tutti e 4 i layout (12 risorse totali).
**Stato:** COMPLETATO (2026-03-04)

### ~~A6. Nessun Audit Logging~~ RISOLTO
**File:** `app/Services/AuditLog.php`, `database/migrations/005_create_audit_logs.sql`
**Problema:** Nessun log strutturato di: login riusciti/falliti, logout, cambio password, azioni super_admin, accesso cross-tenant.
**Impatto:** Impossibile rilevare attacchi, nessuna traccia forense, problemi di compliance GDPR.
**Fix:** Creata tabella `audit_logs` (user_id, tenant_id, event, description, ip_address, user_agent). Classe `AuditLog` con metodo statico `log()` e costanti per eventi. Integrato in: LoginController (login success/failed, logout), PasswordController (reset request, reset done), TenantsController (tenant created, toggled), SettingsController (settings updated). Fail-safe: errori di logging non bloccano l'app.
**Stato:** COMPLETATO (2026-03-05)

### ~~A7. Force Booking senza Controllo Autorizzazione~~ RISOLTO
**File:** `app/Controllers/Dashboard/ReservationsController.php:92, 254`
**Problema:** Il parametro `force_booking` viene letto dal form POST senza verificare il ruolo dell'utente. Qualsiasi utente autenticato puo bypassare i controlli di disponibilita.
**Fix:** Aggiunto check `Auth::isOwner() || Auth::isSuperAdmin()` prima di accettare `force_booking` in `store()` e `update()`.
**Stato:** COMPLETATO (2026-03-04)

---

## MEDIA (8) - Da risolvere entro 2-4 settimane

### ~~M1. Tenant Isolation: Confronto Loose (`!=` invece di `!==`)~~ RISOLTO
**File:** `app/Controllers/Dashboard/ReservationsController.php:39,158,185,200,230`, `app/Controllers/Dashboard/CustomersController.php:66`
**Problema:** Il confronto `tenant_id` usa `!=` (loose comparison). PHP type juggling potrebbe causare bypass in edge case.
**Fix:** Sostituito con `(int)$a !== (int)$b` in tutte e 6 le occorrenze.
**Stato:** COMPLETATO (2026-03-04)

### ~~M2. Nessun Rate Limiting sulle API Pubbliche~~ RISOLTO
**File:** `app/Middleware/RateLimitMiddleware.php`, `app/Services/RateLimit.php`, `config/routes.php:76`
**Problema:** Endpoint availability, booking, show, cancel senza limiti. Permette DoS e enumerazione.
**Fix:** Creata tabella `rate_limits` e servizio `RateLimit` con limiti per metodo HTTP: GET 60 req/min (availability), POST 10 req/min (booking/cancel). Middleware `RateLimitMiddleware` registrato in `App::runMiddleware()` e applicato al gruppo `/api/v1`. Risposta 429 con tempo di attesa. Auto-cleanup record > 5 minuti.
**Stato:** COMPLETATO (2026-03-05)

### ~~M3. Nessuna Validazione Advance Window per Prenotazioni~~ RISOLTO
**File:** `app/Controllers/Dashboard/ReservationsController.php:88-97`, `app/Controllers/Api/ReservationApiController.php:45-56`
**Problema:** `booking_advance_min` e `booking_advance_max` esistevano nel DB ma non venivano mai validati server-side. Si potevano creare prenotazioni nel passato o oltre la finestra consentita.
**Fix:** Aggiunto controllo `daysAhead` vs `booking_advance_min/max` del tenant in entrambi i controller (API + Dashboard). Dashboard rispetta `force_booking` per bypassare il controllo.
**Stato:** COMPLETATO (2026-03-04)

### ~~M4. party_size senza Limiti Min/Max~~ RISOLTO
**File:** `app/Core/Validator.php:104-115`, `app/Controllers/Api/ReservationApiController.php:34`, `app/Controllers/Dashboard/ReservationsController.php:84,265`
**Problema:** `party_size` validato solo come integer. Valori 0, 999999 vengono accettati.
**Fix:** Aggiunto metodo `between()` al Validator. Applicato range 1-50 in tutti e 3 i punti (API store, Dashboard store, Dashboard update).
**Stato:** COMPLETATO (2026-03-05)

### ~~M5. SettingsController: Nessuna Validazione su Update Generale~~ RISOLTO
**File:** `app/Controllers/Dashboard/SettingsController.php:22-43`
**Problema:** Campi `name`, `email`, `cancellation_policy`, `table_duration`, `time_step` accettati senza validazione. `time_step=0` causa divisione per zero. `cancellation_policy` senza limiti di lunghezza.
**Fix:** Aggiunto Validator con required (name, email), email format, `between()` per table_duration (15-300) e time_step (5-120), max 2000 chars per cancellation_policy.
**Stato:** COMPLETATO (2026-03-05)

### ~~M6. MealCategories: Nessuna Validazione Time Format~~ RISOLTO
**File:** `app/Controllers/Dashboard/MealCategoriesController.php:40-48`
**Problema:** `start_time` e `end_time` accettati senza validare formato HH:MM. Valori invalidi corrompevano la logica di categorizzazione.
**Fix:** Aggiunta validazione regex `^\d{2}:\d{2}$` e controllo `start_time < end_time` con messaggio di errore specifico per categoria.
**Stato:** COMPLETATO (2026-03-04)

### ~~M7. Directory Storage/Config non Protette~~ RISOLTO
**File:** `storage/logs/`, `config/`
**Problema:** Log (con token di reset!) e file di configurazione potenzialmente accessibili via HTTP se il path viene indovinato.
**Fix:** Bloccato accesso via `RewriteRule ^(app|config|storage|vendor|database|bootstrap)(/|$) - [F,L]` nel root `.htaccess` (insieme a C3).
**Stato:** COMPLETATO (2026-03-04)

### ~~M8. onclick Inline nelle Tabelle (XSS Pattern a Rischio)~~ RISOLTO
**File:** `views/dashboard/customers/index.php:42`, `views/dashboard/reservations/index.php:58`, `views/dashboard/home.php:83`, `views/layouts/dashboard.php`
**Problema:** Pattern `onclick="window.location='url'"` nelle righe tabella. Sicuro ora (ID sono int) ma fragile - se in futuro gli URL contengono dati utente, diventa XSS.
**Fix:** Sostituito `onclick` con `data-url` attribute in tutte e 3 le view. Aggiunto event delegation JS nel layout dashboard con esclusione `a, button` per link/azioni interne.
**Stato:** COMPLETATO (2026-03-05)

---

## BASSA (4) - Best Practice, pianificare

### ~~B1. Password Policy Debole~~ RISOLTO
**File:** `app/Controllers/Auth/PasswordController.php:74-82`, `app/Controllers/Admin/TenantsController.php:44`, `app/Core/Validator.php`
**Problema:** Solo check `strlen >= 8`. Nessun requisito complessita (maiuscole, numeri, speciali), nessun check dizionario.
**Fix:** Aggiunto metodo `passwordStrength()` al Validator (richiede almeno 1 maiuscola + 1 numero). Applicato a: reset password (`doReset`) e creazione owner (`TenantsController::store`).
**Stato:** COMPLETATO (2026-03-05)

### ~~B2. Slug Tenant Prevedibile~~ RISOLTO
**File:** `app/Controllers/Admin/TenantsController.php:59`
**Problema:** Collisione slug risolta con `rand(100,999)` - solo 900 possibilita. Facile enumerare tutti i ristoranti.
**Fix:** Sostituito `rand(100,999)` con `bin2hex(random_bytes(4))` - 4 miliardi di possibilita.
**Stato:** COMPLETATO (2026-03-05)

### ~~B3. `query()` senza Prepared Statement (3 query in Tenant.php)~~ RISOLTO
**File:** `app/Models/Tenant.php:40,100,105`
**Problema:** 3 query usano `->query()` invece di `->prepare()->execute()`. Non vulnerabili (nessun input utente) ma inconsistenti con il resto del codebase.
**Fix:** Convertite tutte e 3 le query (`all()`, `count()`, `countActive()`) a `prepare()->execute()->fetch*()`.
**Stato:** COMPLETATO (2026-03-05)

### ~~B4. Open Redirect via HTTP_REFERER~~ RISOLTO
**File:** `app/Core/Response.php:47-54`
**Problema:** `Response::back()` usa `$_SERVER['HTTP_REFERER']` senza validazione. Un attaccante potrebbe manipolare il referer per redirect a sito malevolo.
**Fix:** Aggiunto `parse_url()` per estrarre l'host dal referer e confrontarlo con `$_SERVER['HTTP_HOST']`. Se diverso, redirect a `url('/')`.
**Stato:** COMPLETATO (2026-03-05)

---

## ASPETTI POSITIVI (Nessun Fix Richiesto)

| Area | Stato |
|------|-------|
| SQL Injection | **SICURO** - PDO prepared statements ovunque, `EMULATE_PREPARES=false` |
| CSRF Protection | **SICURO** - Token crittografico, `hash_equals()`, middleware su tutte le route auth |
| XSS Output Escaping | **SICURO** - `e()` in PHP, `escapeHtml()` in JS usati consistentemente |
| File Upload | **N/A** - Non implementato (nessuna superficie di attacco) |
| Segreti nel Frontend | **SICURO** - Nessuna chiave API/token esposta nel JS |
| Client-Side Validation | **SICURO** - Duplicata server-side con Validator |
| Password Hashing | **SICURO** - `password_hash(PASSWORD_BCRYPT)` usato correttamente |
| Dipendenze | **AGGIORNATE** - Bootstrap 5.3.3, Stripe SDK 14.x, PHP 8.1+ |

---

## ROADMAP DI REMEDIATION

### FASE 1 - Immediato (entro 24-48 ore) - COMPLETATA
| # | Fix | Stato |
|---|-----|-------|
| C3 + M7 | .htaccess: bloccare .env e directory sensibili | COMPLETATO |
| C5 | Session cookie: `secure` dinamico | COMPLETATO |
| A2 | `APP_DEBUG=false` in produzione | COMPLETATO |
| C6a | Rimuovere log token reset password | COMPLETATO |
| A1 | Aggiungere Security Headers | COMPLETATO |
| A5 | SRI hashes su CDN Bootstrap (4 layout) | COMPLETATO |
| M1 | `!==` strict comparison per tenant isolation | COMPLETATO |
| A7 | Check ruolo su force_booking | COMPLETATO |

### FASE 2 - Entro 1 settimana - COMPLETATA
| # | Fix | Effort | Criticita |
|---|-----|--------|-----------|
| C1 | ~~Transaction + FOR UPDATE su booking~~ | COMPLETATO | CRITICA |
| C2 | ~~Filtro tenant_id su API show/cancel~~ | COMPLETATO | CRITICA |
| C4 | ~~Brute force protection su login~~ | COMPLETATO | CRITICA |
| C6b | ~~Transaction su password reset~~ | COMPLETATO | CRITICA |

### FASE 3 - Entro 2-4 settimane (COMPLETATA)
| # | Fix | Stato | Criticita |
|---|-----|-------|-----------|
| A3 | ~~Session idle timeout~~ | COMPLETATO | ALTA |
| A4 | ~~Email reset password~~ | COMPLETATO | ALTA |
| A6 | ~~Audit logging system~~ | COMPLETATO | ALTA |
| M2 | ~~Rate limiting API~~ | COMPLETATO | MEDIA |
| M3 | ~~Validazione advance window~~ | COMPLETATO | MEDIA |
| M4 | ~~Validazione party_size limits~~ | COMPLETATO | MEDIA |
| M5 | ~~Validazione SettingsController~~ | COMPLETATO | MEDIA |
| M6 | ~~Validazione time format MealCategories~~ | COMPLETATO | MEDIA |
| M8 | ~~onclick -> data-url pattern~~ | COMPLETATO | MEDIA |

### FASE 4 - Backlog (completata)
| # | Fix | Stato |
|---|-----|-------|
| B1 | ~~Password policy~~ | COMPLETATO |
| B2 | ~~Slug crittografico~~ | COMPLETATO |
| B3 | ~~Prepared statements coerenti~~ | COMPLETATO |
| B4 | ~~Open redirect validation~~ | COMPLETATO |

---

## NOTE TECNICHE

### SQL Injection: Analisi Completa
Verificati TUTTI i model e controller. 100% delle query usa prepared statements con parametri named (`:param`). `PDO::ATTR_EMULATE_PREPARES = false` garantisce prepared statements reali lato MySQL. Nessuna concatenazione di input utente in query SQL.

### CSRF: Analisi Completa
Token generato con `bin2hex(random_bytes(32))`, validato con `hash_equals()`, incluso in tutti i form via `csrf_field()`. Route auth/dashboard/admin protette da `CSRFMiddleware`. API pubbliche giustamente escluse (stateless).

### XSS: Analisi Completa
Funzione `e()` (htmlspecialchars ENT_QUOTES UTF-8) usata consistentemente nelle view. JS usa `escapeHtml()` (DOM textContent method) per tutto il rendering dinamico. Unico rischio: pattern `onclick` inline (attualmente sicuro ma fragile).
