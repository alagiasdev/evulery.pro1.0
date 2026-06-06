# Evulery.Pro 1.0

**SaaS multi-tenant per la gestione di prenotazioni, ordini, menu e reputazione di ristoranti.**

Dominio produzione: [dash.evulery.it](https://dash.evulery.it) — Landing: [evulery.it](https://evulery.it)

---

## Cos'è

Evulery.Pro è la piattaforma SaaS che il ristoratore usa per:

- **Prenotazioni** — widget pubblico zero-frizione, CRM clienti automatico, link magico per gestione senza account, reminder anti no-show
- **Tavoli & Sala** — sala virtuale con mappa drag&drop, auto-assegnazione con fit-primario, tavoli elastici (min/max), stati (jolly, bloccato, archiviato)
- **Ordini online** — store pubblico takeaway + delivery con zone CAP, kanban operativo, statistiche per rider, stampa termica 80mm
- **Caparra** — 4 modalità (info, link, Stripe, carta a garanzia) con chiavi per-tenant cifrate AES-256-GCM
- **Reputazione** — review request automatici post-visita, filtro sentimento, conforme Legge 34/2026
- **Comunicazioni** — email broadcast segmentato con crediti e GDPR consent
- **Notifiche** — multi-canale (email, campanella, push browser, audio brandizzato), banner iOS PWA per iPhone
- **Reseller B2B** — area dedicata con lead, commissioni configurabili per-reseller, materiali commerciali interattivi

Documento di prodotto autoritativo: [`docs/product-strategy.md`](docs/product-strategy.md).
Storico release: [`CHANGELOG.md`](CHANGELOG.md). Backlog: [`TODO.md`](TODO.md).

---

## Stack

- **PHP 8.3** (vanilla, no framework) — PSR-4 autoload via Composer (`classmap-authoritative`)
- **MySQL 5.7+ / MariaDB 11.4** (produzione) — InnoDB, utf8mb4
- **Bootstrap 5.3** + Bootstrap Icons 1.11 (CDN con SRI hash)
- **PHPMailer 7** (SMTP transactional + broadcast TurboSMTP)
- **Stripe SDK 14** (caparra + SetupIntent guarantee + webhook signed)
- **Minishlink WebPush 10** (VAPID)
- Frontend custom JS (no framework), moduli IIFE riusabili (`heartbeat-polling.js`, `notification-sounds.js`, ecc.)
- CSS namespacing per area: `.bw-*` (booking widget), `.dr-*` (dashboard reservation), `.dm-*` (menu), `.do-*` (orders), `.tm-*` (tables map), `.rd-*` (riders), `.rv-*` (reviews), `.hg-*` (help guide), `.lg-*` (legal), `.dh-*` (dashboard home), ecc.

Ambiente locale: **XAMPP su Windows**. `php.exe` in `C:\xampp\php\`, MySQL in `C:\xampp\mysql\bin\`.

---

## Setup locale

### 1. Prerequisiti
- XAMPP con PHP 8.3 + MySQL/MariaDB attivi
- Composer (incluso il binary `composer.phar` nella root del progetto)
- Git

### 2. Clone + dipendenze
```bash
git clone https://github.com/alagiasdev/evulery.pro1.0.git
cd evulery.pro1.0
php composer.phar install
```

### 3. Database
- Crea il database locale (es. `evulery_pro`) via phpMyAdmin o CLI
- Importa lo schema iniziale (TODO: scrivere `database/init.sql` di seed)
- Applica le migration in ordine:
  ```bash
  php scripts/migrate.php
  ```

### 4. Configurazione `.env`
Copia `.env.example` in `.env` e compila almeno:
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
- `APP_URL` (locale: `http://localhost/evulery.pro1.0`)
- `APP_DEBUG=true` in dev (mai in prod)
- `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT` (per push notifications — generabili con `vendor/bin/generate-vapid-keys`)
- `STRIPE_*` opzionali in dev
- `MAIL_*` per SMTP transactional

### 5. Routing locale
Document root → `public/`. In XAMPP, alias in `httpd-vhosts.conf` o accesso diretto via `http://localhost/evulery.pro1.0/public/`.

### 6. Test di vita
```
http://localhost/evulery.pro1.0/public/
```
Dovresti vedere la landing. Login dashboard: `/auth/login` con un tenant seed.

---

## Architettura

Codebase organizzato in:

```
app/
├── Controllers/    # PSR-4: App\Controllers\…
│   ├── Admin/
│   ├── Api/
│   ├── Auth/
│   ├── Dashboard/
│   ├── Delivery/
│   ├── Hub/
│   ├── Menu/
│   ├── Ordering/
│   └── Reseller/
├── Core/           # App, Auth, Database, Request, Response, Router, Session, TenantResolver, ...
├── Helpers/        # functions.php (e, url, asset, tenant_can, gate_service, ...) + view.php
├── Middleware/     # Auth, Admin, CSRF, RateLimit, Tenant, DashboardRateLimit
├── Models/         # PSR-4 con costruttore Database::getInstance()
└── Services/       # Logica di dominio (HeartbeatService, BroadcastService, TableAssigner, ...)
config/
├── app.php, database.php, mail.php, stripe.php
└── routes.php      # Tutte le rotte (~300+), gruppi per auth+tenant+csrf+ratelimit
database/
└── migrations/     # NNN_descrizione.sql — applicabili via /admin/migrations o CLI
public/
├── index.php       # Front controller
├── assets/         # CSS, JS, immagini, suoni (cache busting via filemtime)
├── sw-push.js      # Service worker per Web Push
└── manifest.json   # PWA manifest
views/
├── auth/, admin/, booking/, dashboard/, delivery/, hub/, layouts/, manage/, menu/, ordering/, partials/, reseller/, review/, ...
└── dashboard/help/_sections.php  # Guida in-app
wireframes/         # HTML standalone per design review (es. order-print-mvp.html, riders-management.html)
docs/               # Strategia, playbook, ticket
sales/              # Materiali commerciali (demo, ROI calc, FAQ, brand kit)
```

### Pattern chiave
- **Multi-tenant**: ogni richiesta dashboard ha un `tenant_id` corrente via `TenantResolver`. Tutti i Model filtrano `WHERE tenant_id = :tid` per isolation
- **Service gating**: `tenant_can('service_key')` + `gate_service('key')` per ogni feature avanzata
- **CSP nonce dinamico**: `csp_nonce()` su tutti gli `<script>` inline, no `unsafe-inline` su `script-src`
- **Auto-refresh dashboard**: heartbeat polling con ETag/304 + pausa su tab nascosta
- **Notifiche audio**: modulo JS riusabile con preload + autoplay policy + filter `cancelled_by`

Per dettagli architetturali vedi memory CLAUDE.md.

---

## Deploy

### Produzione (`dash.evulery.it`)

Server: **Serverplan VPS** (Genoa) — 8 core Xeon Gold + 32 GB RAM + CloudLinux 10, cPanel.

**Flusso**:
1. Sviluppo locale + commit
2. Push su `main` (GitHub: `alagiasdev/evulery.pro1.0` — repo pubblico, vincolo per cPanel Git)
3. cPanel Git Version Control → **Update from Remote**
   - ⚠️ "Deploy HEAD Commit" è disabilitato su questo VPS, `.cpanel.yml` NON viene mai eseguito
   - Per questo `vendor/` è committato nel repo
4. Apri `/admin/migrations` → applica le pending
5. Verifica con i punti di `check-live.md`

### Landing (`evulery.it`)

Cartella `website/` (HTML/CSS/JS statico, no framework). Deploy via **FTP manuale** — non collegata al repo cPanel di `dash.evulery.it`.

> **Mai confondere i due flussi**: app via cPanel Git "Update from Remote", landing via FTP.

### Cron jobs produzione
Configurati in cPanel:
- Reminder prenotazioni — ogni 15 min
- Broadcast email queue — ogni 5 min
- Review requests — ogni 15 min
- Orders auto-cancel pending — ogni 30 min

---

## Documentazione

| File | Cosa contiene |
|---|---|
| [`README.md`](README.md) | Questo file — overview + setup |
| [`CHANGELOG.md`](CHANGELOG.md) | Storico release con date e categorie (Added/Changed/Fixed/Security) |
| [`TODO.md`](TODO.md) | Backlog (in panchina, quick win, trigger-based, deferred) |
| [`SPECS.md`](SPECS.md) | Concept document originale Marzo 2026 (storico) |
| [`check-live.md`](check-live.md) | Checklist pre/post-deploy aggiornata |
| [`SECURITY_AUDIT_2026-03-04.md`](SECURITY_AUDIT_2026-03-04.md) | Audit sicurezza Marzo 2026 (snapshot storico) |
| [`docs/product-strategy.md`](docs/product-strategy.md) | Ragionamenti di prodotto e strategia (26 sezioni) |
| [`docs/pandemia-playbook.md`](docs/pandemia-playbook.md) | Piano emergenza sanitaria (deferito, eseguibile in 2 settimane se necessario) |
| `wireframes/*.html` | Wireframe HTML per design review (apri nel browser) |
| `sales/*` | Materiali commerciali (demo, ROI calc, FAQ, brand kit) |

Guida in-app per i ristoratori: `/dashboard/guide` con 17+ sezioni e ricerca.

---

## Contributing

Sviluppo individuale al momento. Per modifiche significative, aggiornare:
1. `CHANGELOG.md` con la voce della release
2. `TODO.md` se la modifica chiude un task in backlog
3. `docs/product-strategy.md` se introduce/modifica feature di prodotto
4. Guida in-app (`views/dashboard/help/_sections.php`) se cambia il comportamento user-facing

Commit messages in italiano lowercase con prefisso area:
```
broadcast preview: hint trasparenza GDPR fuori dal box + conta disiscritti
riders stats: usa il datepicker custom Evulery (.dr-cal-*) + bottone Oggi
fix: bug rider markup mancante in renderOrderCard dopo cambio status
```

---

## Licenza

Proprietario — © Evulery · by alagias. — Soluzioni per il web. Tutti i diritti riservati.
