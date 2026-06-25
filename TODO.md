# Evulery.Pro 1.0 - Prossimi Passi

## 📂 Dove si trovano i file di log (riferimento — agg. 2026-06-26)

Base prod: `/home/vpsevlrqrit/evulery/storage/logs/` · Base locale: `storage/logs/`

| File | Cosa contiene | Generato da | Frequenza |
|---|---|---|---|
| `storage/logs/AAAA-MM-GG.log` | log applicativo (info/warning/error + warning PHP catturati) | `app_log()` | per giorno, ad evento |
| `storage/logs/perf-AAAA-MM-GG.log` | richieste lente (> 500ms, soglia `PERF_LOG_THRESHOLD_MS`) | `App\Core\PerfLog` | per giorno, ad evento |
| `storage/logs/outbox.log` | worker coda email (solo quando invia/fallisce) | cron `process-outbox.php` | ogni minuto (scrive solo se attività) |
| `storage/logs/cron_expire.log` | scadenza caparre manuali | cron `expire-manual-deposits.php` | ogni ora (:15) |

**Log dei cron nella home (un livello sopra `evulery/`)**: `/home/vpsevlrqrit/cron_broadcast.log`, `cron_reminder.log`, `cron_review_requests.log` (rispettivamente broadcast 5 min, reminder 15 min, review 15 min).

**Pulizia automatica**: cron mensile (1° del mese, 04:00) `find /home/vpsevlrqrit/evulery/storage/logs -name '*.log' -mtime +60 -delete` → cancella i log in `storage/logs/` più vecchi di 60 giorni (soprattutto i `perf-*` e i giornalieri datati). NON tocca i `cron_*.log` nella home (vanno puliti a mano se serve). I log sono solo diagnostica: cancellarli non rompe nulla, l'app li ricrea in append.


## 📈 Sviluppo futuro — performance (CHECKPOINT a ~10 clienti attivi)

**Trigger**: quando raggiungiamo ~10 ristoranti attivi, rifare un **check performance** sui `perf-*.log` reali (atteso: creazione prenotazione ~300ms dopo la Fase 1 email async). In base ai dati, valutare:
- [ ] **Fase 2 — push asincrono** (complessità bassa-media, ~mezza giornata / 4-6h, NIENTE migration: la tabella `notification_outbox` ha già `channel`). Sposta anche il push nella coda: split `NotificationService::sendPush` → enqueue(web)/transmitPush(invio) + flag `PUSH_ASYNC` (default off, rollout dormiente) + branch `'push'` nel worker. Beneficio latenza MODESTO (push < 2 email SMTP) e tocca il percorso push appena stabilizzato → fare solo se misurato come collo di bottiglia. Dettagli in [[project-perf-email-async]].
- [x] ~~**`auth/login` ~1s**~~ **DIAGNOSTICATO 2026-06-26 → NESSUNA AZIONE**: in locale la GET /auth/login risponde in ~15ms (warm); `showForm` non fa DB né lavoro pesante. Il ~1s in prod è **cold-start** (la login è la prima richiesta di sessione → opcache/FPM freddi). Confermato dai log: compare sopra i 500ms solo sporadicamente (non a ogni login) = primo-hit-dopo-inattività, non sistematico. Fisiologico, tollerabile. Unica leva eventuale = warm-keeping lato server (opcache/FPM tuning o ping), NON ne vale la pena.

(Annotato 2026-06-26: il critico per il lancio è chiuso; questi si rivalutano col volume reale.)

## 🔧 Sviluppo futuro — ops/cron
- [ ] **`domain/verify` — timeout DNS fino a 10s** (PARCHEGGIATO 2026-06-26): la verifica del dominio personalizzato può restare appesa ~10s se il DNS non risolve. Domini custom al momento **oscurati/disattivati** → riprendere solo se si riattiva quella funzione. Fix: abbassare il timeout DNS (3-4s) o verifica non bloccante.
- [ ] **Sfasare i cron `*/15`** (nice-to-have, non urgente): oggi ai minuti tondi (:00/:15/:30/:45) partono insieme broadcasts + reminders + reviews + monitor-outbox (+ expire alle :15). Sono processi brevi e su VPS multi-core è trascurabile, ma per pulizia si possono distribuire su minuti diversi (es. reminders `0,15,30,45`, reviews `5,20,35,50`, monitor `10,25,40,55`) così non partono mai tutti nello stesso minuto. (Annotato 2026-06-26.)
- [ ] *(leva nota, se mai servisse)* `process-outbox.php` gira ogni minuto con loop interno ~55s → di fatto sempre attivo ma ~dormiente (CPU≈0). Se si volesse togliere il processo "sempre on", basta rimuovere il loop interno: un solo batch al minuto (email entro ~60s invece di ~5s). Non serve ora.
- [ ] **reCAPTCHA v3 invisibile sul submit del widget di prenotazione** (difesa-in-profondità, FUTURO — annotato 2026-06-26): si usa già reCAPTCHA v3 su demo-request/landing. Aggiungerlo al POST prenotazione alzerebbe il muro contro bot che creano prenotazioni-spam, **senza attrito per i clienti veri** (v3 è invisibile). Da fare **se** compaiono prenotazioni-spam: oggi il fronte è coperto da rate limit POST 10/min/IP + (quando attivo) Cloudflare bot mitigation. Non urgente.

## 🪑 Backlog breve periodo — IN PANCHINA (revisione 2026-05-13)

Task pronti, da fare quando emerge il trigger o c'è una finestra di lavoro.
Decisione: NON costruirli in cieco, aspettare il segnale reale.

### Pianificato a data
- [ ] **Cloudflare davanti a `dash.evulery.it` + `evulery.it` + `app.evulery.it`** — pianificato per il pomeriggio del 2026-06-08, **sospeso 2026-06-09 in fase di setup** perche' migrazione DNS piu' complessa del previsto: serve sessione dedicata ~1-2h.
  Proxy gratuito che dà: DDoS protection automatico, bot mitigation, cache statica, rate limiting, WAF base.
  **Driver di urgenza**: il 03/06/2026 il VPS ha subito un attacco scraping da 2 subnet cinesi (89.106.110.0/24 e 149.62.193.0/24) con load avg fino a 77 su 8 core. Davide di Serverplan è dovuto intervenire manualmente. Cloudflare assorbirebbe automaticamente eventi simili senza intervento umano.
  **Pre-requisito** per portare in produzione il primo cliente pagante.

  **Stato attuale (sospeso 2026-06-09)**:
  - Account Cloudflare creato, dominio `evulery.it` aggiunto, piano Free
  - Import automatico Cloudflare ha preso solo ~25 record su ~70 reali della zona cPanel
  - I 9 record di servizio (cpanel, whm, ftp, webdisk, autoconfig, autodiscover, cpcalendars, cpcontacts, webmail) sono stati portati a DNS only (grigio)
  - Proxiati correttamente: `evulery.it` root, `www`, `dash`, `app`
  - **Nameserver NON ancora cambiati al registrar** — la zona Cloudflare e' incompleta

  **App-readiness (lato codice) FATTA 2026-06-26**: `Request::ip()` ora legge il vero IP del visitatore da `CF-Connecting-IP` (validato sui range pubblici Cloudflare, IPv4+IPv6), gated da env `TRUST_CLOUDFLARE` (default **off**). Senza questo, dietro Cloudflare rate limit / login throttle / audit log vedrebbero l'IP edge di CF (rotti). **Attivazione (in quest'ordine)**: 1) Cloudflare davvero davanti (nameserver attivi); 2) **firewall origin ristretto agli IP Cloudflare** lato cPanel/Serverplan (ESSENZIALE: senza, l'header `CF-Connecting-IP` sarebbe falsificabile colpendo l'origin diretto → bypass rate limit); 3) `TRUST_CLOUDFLARE=1` in `.env` prod. La validazione sui range CF nel codice è una rete di sicurezza in più, ma il firewall-lock resta necessario.

  **Cosa manca da importare manualmente in Cloudflare** (prima di "Continue to activation"):
  - **3 record DKIM** TXT (lunghi, da copiare interi dal cPanel zone editor):
    - `default._domainkey.evulery.it`
    - `default._domainkey.app.evulery.it`
    - `default._domainkey.dash.evulery.it`
  - **TurboSMTP DKIM** TXT: `turbo-smtp._domainkey.evulery.it` (transazionali finiscono in spam se manca)
  - **SRV autodiscover**: `_autodiscover._tcp.evulery.it` (+ varianti `.app` e `.dash`) destinazione `cpanelemaildiscovery.cpanel.net`, porta 443 — autoconfig client Outlook/Thunderbird
  - **Tutti i sottodomini `*.app.evulery.it`** (autoconfig.app, autodiscover.app, cpanel.app, cpcalendars.app, cpcontacts.app, ftp.app, webdisk.app, webmail.app, whm.app, www.app, _caldav/_carddav SRV+TXT) — servono ai 4-5 clienti dell'app legacy
  - **Tutti i sottodomini `*.dash.evulery.it`** equivalenti
  - **TXT SPF `dash.evulery.it`**: `v=spf1 +a +mx -all`
  - **4 record NS delega PowerMail** (per la posta):
    - `mail.evulery.it` NS `ns1.powermailhost.com`
    - `mail.evulery.it` NS `ns2.powermailhost.com`
    - `default._domainkey.evulery.it` NS `ns1.powermailhost.com`
    - `default._domainkey.evulery.it` NS `ns2.powermailhost.com`
    - (Nota: questi sostituiscono il record TXT DKIM se PowerMail richiede sub-zone delegation;
     verificare con doc Serverplan https://supporto.serverplan.com → Powermail → "Configurare MX con DNS esterno")

  **Strategia di completamento** (~1-2h, opzioni):
  - **A. Manuale**: copiare i record uno-a-uno dal cPanel zone editor a Cloudflare. Sicuro, lungo.
  - **B. AXFR**: aprire ticket Serverplan chiedendo export zona DNS in formato BIND, importarlo in Cloudflare ("Import DNS records" sotto DNS → Records). Veloce ma serve attendere il ticket (24-48h).

  **Dopo il completamento**:
  - Verifica zona Cloudflare = zona cPanel (record per record, no campioni)
  - SSL/TLS → Full (Strict), Always Use HTTPS ON
  - NON attivare Auto Minify, Rocket Loader, Email Obfuscation (rompono JS/mailto)
  - Cambio nameserver al registrar
  - Test: `curl -I https://dash.evulery.it` deve avere header `cf-ray:`
  - Test email outbound (invia test e verifica header SPF/DKIM pass)

### Monitoraggio (warning ricorrenti nei log)

- [ ] **CSRF FAIL su `/auth/logout`** — annotato 2026-06-09 — warning nei log produzione del tipo:
  ```
  [2026-06-09 12:53:10] [warning] CSRF FAIL uri=/auth/logout method=POST
  session_id=... has_cookie=yes submitted_token=eafc3a1f...
  session_token=f40ccb80... idle_sec=0 referer=/dashboard/settings/tables
  ```
  Token submitted ≠ token in sessione, ma `idle_sec=0` (sessione attiva). Probabili
  cause: (1) utente con 2 tab aperte, logout in una rigenera token e l'altra ha
  ancora il vecchio → comportamento atteso, non bug; (2) auto-refresh heartbeat o
  qualche evento rigenera la sessione invalidando i form statici.

  **Da monitorare**: contare quante volte appare al giorno nei log di prod
  (`storage/logs/YYYY-MM-DD.log` → `grep "CSRF FAIL uri=/auth/logout"`):
  - **<5/giorno**: rumore atteso (utenti multi-tab), nessuna azione
  - **>10/giorno**: investigare meccanismo rotazione token in `Session::start()` o
    rendere logout "soft CSRF" (accetta token scaduto per la sola rotta /auth/logout,
    razionale: il logout e' azione sicura — peggior caso = logout-CSRF, annoyance
    minore). Stima fix soft logout: ~30 min.

  **Decisione**: aspettare 7 giorni di osservazione log prima di agire.

### Polish UX (coda follow-up)

- [ ] **Nome ristorante nascosto se logo presente — pagine secondarie** (annotato 2026-06-09).
  Il fix e' stato applicato a 3 view principali (booking widget, menu pubblico, hub vetrina)
  dove il logo e' grande e centrale. Restano 3 view pubbliche secondarie con stesso pattern
  `<img logo> + <h1>nome` che andrebbero uniformate per coerenza:
  - `views/menu/unavailable.php` (menu offline)
  - `views/hub/unavailable.php` (vetrina offline)
  - `views/booking/suspended.php` (tenant sospeso)
  - `views/reviews/landing.php` (pagina pubblica recensioni)

  Pattern da applicare (gia' nelle 3 view fatte):
  - Logo presente → ingrandire (+20-30px) e nascondere h1 con classe `*-sr-only` (utility
    visually-hidden definita per CSS module)
  - Logo assente → h1 visibile (default attuale)

  **NON applicabile** a `views/ordering/store.php` (layout TheFork-style con header
  compatto orizzontale + status "Aperto/Chiuso oggi" sotto il nome → il nome serve come
  ancoraggio del status, nasconderlo crea un sottotitolo orfano).

  Stima: 15-20 min. Priorita' bassa, sono pagine che il cliente vede raramente.

### Edge case — in attesa di segnalazione cliente reale
- [ ] **Asporto → "Ritiro in sede"** — il termine confonde (1 segnalazione 28/04).
  Toccare solo con 3+ segnalazioni. Globale 5-10 min, oppure `pickup_label` per tenant 3-4h.
- [ ] **Hint "clienti esclusi dal marketing"** — il broadcast filtra `marketing_consent=1`
  ed esclude in silenzio i clienti vecchi. Mostrare "Clienti CRM: X · Esclusi: Y" sotto
  il preview count. ~30 min. Alla prima segnalazione "vedo 0 destinatari".
- [ ] **Birthday overwrite** — `Customer::findOrCreate` rifiuta la modifica di un
  compleanno già salvato. Cliente che ha sbagliato data non può correggerla dal widget.
  ~15 min permettere update, oppure azione "Correggi compleanno" in dashboard.
- [ ] **CTA slug warning** — email broadcast: se `include_booking_cta=1` ma `tenant.slug`
  vuoto, il bottone "Prenota ora" viene saltato senza avviso. Warning in `store()`. ~20 min.

### Quick win riempitivi (1-3h l'uno)
- [ ] **Tavolo in form nuova prenotazione manuale** — oggi nella form di creazione
  (`/dashboard/reservations/create`) non si sceglie il tavolo: l'auto-assegnazione lo
  fa al salvataggio, e l'override avviene poi da scheda/mappa. Idea: step opzionale
  "TAVOLO" tra Sorgente e Note con dropdown AJAX (riusa partial `select-tavolo-enhance.php`)
  che lista tavoli disponibili per data+ora+coperti correnti. Stima ~3h (endpoint
  `available-tables?date&time&party` + JS dinamico). **Trigger**: 1+ ristoratore chiede
  "come blocco il tavolo subito al telefono?" — finora l'auto-assegnazione copre il caso
  normale, valore aggiunto marginale. Annotato 2026-05-24.
- [ ] **Banner guida iOS push** — pagina Notifiche: istruzioni "Aggiungi a schermata Home"
  (le push su iPhone funzionano solo da PWA installata).
- [ ] **File MP3 notifiche audio — refining** (annotato 2026-06-05). I 5 file in
  `public/assets/sounds/notification-*.mp3` sono fuori specs:
  - Durata attuale **3.7-4.3 s**, target **0.8-1.5 s** → troppo invadenti se il
    ristoratore riceve 15-20 push/ora (somma = ~80s di suoni continui).
  - Bitrate **64 kbps**, target **128 kbps** → qualità "telefonata", non premium.
  - Sample rate 48 kHz, stereo: ok.

  Come sistemarli (drop-in replacement, NESSUN deploy o riavvio richiesto —
  cache busting via filemtime automatico):
  1. Rigenerare via ElevenLabs Sound Effects aggiungendo al prompt
     `"Duration MUST be exactly 1.2 seconds, no longer. Quick decay, no long
     reverb tail."` E export a 128 kbps.
  2. Oppure trim dei file esistenti con Audacity (apri → seleziona 0-1.2s →
     `Edit → Trim Audio` → `Effect → Fade Out` 100ms → Export MP3 128 kbps).
  3. Sostituire i file mantenendo gli stessi nomi nella cartella → commit + push
     → il prossimo client riceve la nuova versione al refresh.

  Stima: 30-60 min. **Trigger**: feedback ristoratori "il suono è troppo lungo"
  OR quando vuoi finalizzare il sound logo Evulery prima del lancio commerciale.
  Riferimento prompt EN dettagliati per ogni evento: chat sessione 2026-06-05.
- [ ] **Email re-iscrizione cliente** — quando il ristoratore re-iscrive un cliente
  disiscritto, inviargli email automatica con link disiscriviti (GDPR-friendly).
- [ ] **Refactor datepicker Evulery: estrarre in modulo riusabile** (annotato 2026-06-05).
  Il calendario custom `.dr-cal-*` esiste gia' brandizzato ed e' duplicato inline in:
  - `views/dashboard/home.php` (sorgente originale, completo con shortcut Oggi/Domani/Dopodomani)
  - `views/dashboard/reservations/index.php`
  - `views/dashboard/reservations/create.php` + `edit.php`
  - `views/dashboard/settings/tables-map.php`
  - `views/dashboard/riders/stats.php` (aggiunto 2026-06-05 con bottone "Oggi" interno al calendario)

  Estrarre in `public/assets/js/evulery-datepicker.js` come modulo riusabile (pattern auto-bind
  via `data-evulery-datepicker` simile a heartbeat-polling.js). Eliminare le duplicazioni inline
  in ogni view + applicarlo anche agli `<input type="date">` nativi residui in:
  - `views/dashboard/customers/show.php` (birthday)
  - `views/dashboard/customers/stats.php` (filtri data)
  - `views/dashboard/orders/history.php` (filtri data)
  - `views/dashboard/reservations/index.php` (export CSV + filtro range)

  Stima ~4-5h. Bonus: il bottone "Oggi" diventa standard ovunque. **Trigger**: quando ho una
  sessione dedicata a "ripulire base codice" o quando 2+ ristoratori segnalano UX incoerente
  fra le pagine. Per ora il workaround locale nelle stats riders e' sufficiente.
- [ ] **Slug riservati validation** — impedire tenant con slug `admin`, `api`, `menu`,
  `hub`, `promo`, `order`, `review`, `booking`, ecc. (igiene sicurezza URL).
- [ ] **Routing fix `/{slug}/booking/success`** — in `config/routes.php` le 2 rotte
  booking stanno dopo la catch-all `/{slug}`: ordine da correggere.
- [ ] **Colori tavoli mappa sala** (Gestione Tavoli — UX) — distinguere visivamente
  3 stati: **libero** (verde), **prenotato** (giallo/ambra, prenotazione futura del giorno),
  **occupato** (rosso/viola, status `arrived` = cliente seduto ORA). Oggi i tavoli sono
  colorati ma la differenza prenotato-vs-occupato non si legge. Toccare `views/dashboard/settings/tables-map.php`
  + CSS `.tm-*` + logica `floorState` in `TableAssigner`. Stima ~2-3h. Annotato 2026-05-30.
- [ ] **Cloudflare davanti a `dash.evulery.it`** (sicurezza + performance) — proxy gratuito
  che dà: DDoS protection automatico, bot mitigation, cache statica, rate limiting,
  WAF base. Setup ~30 min (cambio nameserver dal registrar). Costo €0/mese (piano free).
  **Trigger**: il 03/06/2026 il VPS ha subito un attacco scraping da 2 subnet cinesi
  (89.106.110.0/24 e 149.62.193.0/24) con load avg fino a 77 su 8 core. Davide di
  Serverplan è dovuto intervenire manualmente per bloccarli. Cloudflare assorbirebbe
  automaticamente eventi simili senza intervento umano. Da fare prima del primo picco
  reale (acquisizione clienti, lancio commerciale, picco mediatico).

### Progetti grossi — deferiti coi loro trigger
- [ ] **Analytics module** — dopo 20+ clienti (servono dati aggregati reali)
- [ ] **Loyalty Program** — validazione con clienti Professional paganti (4 wireframe pronti)
- [ ] **Dashboard redesign UX** — 5-10 ristoranti attivi da 1+ mese + feedback strutturato
- [ ] **Sales Kit super admin** — quando arriva il 1° reseller B2B vero
- [ ] **Combinazioni di N tavoli** (Gestione Tavoli — "Fase D") — oggi solo coppie
  (`table_combinations.table_a_id/b_id`). TheFork supporta combinazioni di 3+ tavoli con
  capienze proprie (es. 4 tavoli per una tavolata da 11). Schema: tabella `combinations`
  + junction `combination_tables`. Stima 12-15h, complessità alta. **Trigger**: 1+
  richiesta esplicita cliente per tavolate 10+ (cerimonie, comunioni, eventi). Per ora le
  coppie coprono il 90% (party 5-8). Piano completo in memory `project_table_combinations_n.md`.
- [ ] **Dominio personalizzato — RIATTIVARE** — feature NASCOSTA dalla UI il 19/05/2026
  (commit `b72803f`) perché non pronta. Codice/rotte/controller intatti. Per riattivarla:
  togliere `'hidden' => true` in (1) `settings_nav()` → `app/Helpers/functions.php` e
  (2) sezione `'dominio'` in `views/dashboard/help/_sections.php`. **Ma prima** sistemare
  il bug `cname_target` hardcoded in `DomainController.php` + test end-to-end con
  `dominio.evulery.it` (piano completo: `docs/` o memory `project_custom_domain.md`).
- [ ] **Notifiche audio diversificate** (UX feedback ristoratore) — oggi le notifiche
  sono visuali (campanella dashboard + push browser). Aggiungere **suono audio breve** per
  ogni evento, diversificato per tipologia così l'orecchio del ristoratore riconosce
  l'evento senza guardare lo schermo:
  - Nuova prenotazione widget
  - Prenotazione cancellata
  - Caparra ricevuta / rimborsata
  - Ordine online ricevuto / cancellato
  - Feedback recensione ricevuto
  - Notifica reseller (lead nuovo / commissione)

  Implementazione: cartella `public/assets/sounds/`, suonarli via HTML5 Audio API in
  `notifications.js`. Settings ristoratore per abilitare/disabilitare + scelta suono per
  tipo (con preview). Volume globale. Stima ~5-6h (file audio + JS + UI settings).
  **Trigger**: nessuno specifico, è un quick-win di UX importante per uso quotidiano.
  Annotato 2026-05-30.
- [ ] **Integrazione stampanti termiche** (Online Ordering — completamento operativo) —
  oggi gli ordini online arrivano nella dashboard ma il ristoratore deve gestirli a video.
  Vogliamo che si **stampino automaticamente** una comanda su stampante termica della cucina
  (ESC/POS standard de facto). Tre approcci:
  1. **PrintNode** (consigliato MVP): servizio bridge cloud-to-printer. Agent installato
     sul PC cliente, API REST cloud-side. ~€4/mese per stampante a carico ristoratore.
     Dev stimato: 10-15h
  2. **CloudPRNT** (Star / Epson Connect): le stampanti recenti fanno polling al nostro
     server, noi rispondiamo con ESC/POS. Niente agent ma serve hardware compatibile.
     Dev stimato: 15-20h
  3. **Agent locale custom**: scrivere un daemon Python/Node sul PC cucina che fa polling.
     Massima flessibilità ma più manutenzione. Dev stimato: 30+h

  **Trigger**: 1+ ristoratore Enterprise con online ordering attivo lo chiede esplicitamente.
  Modello commerciale: feature solo Enterprise, eventualmente add-on hardware-as-a-service
  con PrintNode incluso. Annotato 2026-05-30.
- [ ] **Modale ibrido cancellazione/rifiuto prenotazioni** (UX) — generalizzare il
  pattern del wireframe `wireframes/fase-f-richieste-gruppi.html` a TUTTE le cancellazioni
  di prenotazione, inclusi i tavoli 1-2 persone. Oggi (2026-06-09) e' stato implementato
  solo il label "Annullata da: ristoratore/cliente/sistema" (campo `cancelled_by`). Il
  pattern futuro prevede una modale leggera quando il ristoratore clicca "Annulla":
  - 3-4 chip preselezionate (es. "Forza maggiore", "Errore prenotazione", "Su richiesta
    cliente", "Tavolo non disponibile piu'") con descrizione lunga sotto
  - Textarea opzionale "Aggiungi qualcosa" libera
  - Toggle "Proponi un'alternativa nell'email" (suggerisce al cliente di richiamare)
  - Bottone "Invia annullamento al cliente" (parte email con motivazione)

  Il pattern e' bello perche': pochi click, motivazione chiara per il cliente (la riceve
  in email), niente friction se il ristoratore ha fretta (textarea opzionale).
  **Trigger**: 5+ ristoratori in prod oppure 1+ segnalazione "vorrei dire al cliente
  perche' ho cancellato". Stima ~4-5h (modale + email template + 4 chip per scenario).
  Riusa l'idea della tabella `reservation_logs.note` esistente.
  Annotato 2026-06-09.
- [ ] **Servizio gestione Google My Business** (upsell post-launch) — vendere la
  gestione GMB come servizio mensile abbinato a Evulery. Sinergia naturale con
  reputation: recensioni interne alimentano GMB. 3 piani proposti:
  - **GMB Essential** €99/mese + €299 setup: 1 post/sett, risposta entro 48h, 2 foto/mese
  - **GMB Plus** €149/mese + €299 setup: 2 post/sett, risposta 24h, 4 foto/mese, Q&A monitoring
  - **GMB Pro** €249/mese + €399 setup: 3 post/sett, risposta 12h, 8 foto/mese, foto pro 1x/anno, Local SEO

  Sconto annuale -10%. Capacity: 10-15 clienti gestiti direttamente (~25h/sett),
  oltre serve freelance social media manager. Bundle Evulery Enterprise + GMB Plus -10%
  per sticky retention. **Trigger**: dopo stabilizzazione 15 Early Adopter Evulery
  (~settembre 2026), quando ho clienti come case study. Annotato 2026-06-09.
  Piano completo: memory `project_gmb_service.md`.
- [ ] **Card NFC per recensioni in-locale** (add-on hardware) — card NFC fisiche
  brandizzate posizionate sui tavoli, tap & go → landing review Evulery. Sistema
  review backend (Fase 23) supporta gia' canale NFC, manca solo prodotto fisico
  + modello commerciale. Conversione attesa +30-50% vs QR sticker (benchmark Linq/Popl).

  Modello commerciale:
  - **Incluse** nei piani Pro (3 card) ed Enterprise (10 card + sostituzione 1 anno)
  - **Kit Recensioni NFC** standalone €149 una tantum (5 card brandizzate + 3 sticker QR)
  - **Card aggiuntive** €15 cad o €60 pack 5

  Economics: card NTAG215 bianca €0,80 + stampa logo €2 + spedizione €5 → costo nostro
  ~€8 per pack 3, margine 70-90%. Limite capacity 20-30 nuovi clienti/mese gestiti
  direttamente, oltre serve stamperia partner drop-shipping. **Trigger**: dopo 5+
  Early Adopter attivi con flusso review QR validato (~agosto 2026). Annotato 2026-06-09.
  Piano completo: memory `project_nfc_review_cards.md`.

### Priorità reale a breve termine (NON in panchina)
- [x] **Migrazione VPS** — ~~nuovo server 8core/32GB, attesa conferma Serverplan (PHP 8.2,
  parallelismo). Rischio #1: testare il DB dump su MariaDB 11.4 prima dello switch.~~
  **COMPLETATA con successo (comunicato da Stefano il 2026-06-10).**
- [x] **Pagine legali** — ~~completare `privacy.html`/`terms.html`/`cookies.html` con
  P.IVA e ragione sociale prima del go-live commerciale.~~ **Verificato 2026-06-10**:
  P.IVA 01855570766 e ragione sociale gia' presenti nei file, nessun placeholder residuo.

---

## Stato Attuale
Fasi completate: Foundation, Auth, Admin Panel, Multi-Tenant, Slot/Capacita, Booking Widget, Dashboard Ristoratore, Security Audit (25/25), Dashboard UX improvements, Prenotazione Rapida Touch (FASE 16), Design System v3.1, Booking Widget Polish (FASE 17 parziale), Promozioni e Badge Sconto (FASE 14), Menu Digitale Consultivo (FASE 20A v2.1), Chiusure e Conferma Manuale (FASE 12), Email (FASE 13 parziale), Service Gating, Admin Backend (Activity Log, Gestione Utenti, Impersonation), **Email Broadcast con Crediti (FASE 15)**.
Il sistema funziona end-to-end: login, gestione ristoranti, prenotazioni da widget e dashboard, menu digitale pubblico con QR code, super admin con gestione piani/abbonamenti/utenti/impersonation/activity log, email marketing broadcast con crediti e TurboSMTP.

**Nota architetturale (Marzo 2026 - aggiornata):** Il modello commerciale e basato su **coperti illimitati** e **piani differenziati per servizi**. I piani sono gestiti dinamicamente dall'admin panel (tabelle `plans`, `services`, `plan_services`). Il service gating e implementato via `tenant_can()` / `gate_service()` / `Tenant::canUseService()` con lock card UI uniforme (`views/partials/service-locked.php`). La FASE 9 originale (feature flags con PlanService) e stata superata da un approccio piu snello.

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
- [x] Super admin backend: gestione piani (CRUD), servizi (catalogo), abbonamenti tenant, scadenze (22/03/2026)
- [x] Service gating completo: 7 servizi gatati (digital_menu, promotions, custom_domain, export_csv, email_reminder, deposit, statistics) con lock card UI, sidebar lucchetti, controller gate, pagine pubbliche friendly (22/03/2026)
- [x] Partial riutilizzabile `views/partials/service-locked.php` per lock card uniforme su tutte le pagine gatate
- [x] Servizio `deposit` (Caparra) aggiunto al catalogo servizi, gatato su dashboard + widget + API (migration 017)
- [x] Servizio `statistics` rinominato "Statistiche Clienti", gatato con lock card (migration 018)
- [x] Servizio `analytics` (Analytics) aggiunto al catalogo come placeholder futuro (migration 018)
- [x] Subscription expiry: TenantMiddleware redirect, pagine pubbliche sospese, API 403, widget embed bloccato
- [x] Pagina pubblica "Menu non disponibile" (`views/menu/unavailable.php`) se piano non include digital_menu
- [x] Activity Log: pagina `/admin/activity-log` con 34 tipi evento, filtri, KPI, paginazione, purge vecchi log (23/03/2026)
- [x] Gestione Utenti: pagina `/admin/users` con filtri (ricerca, ruolo, tenant, stato), paginazione 25/pag (23/03/2026)
- [x] Impersonation: super admin entra nella dashboard di qualsiasi ristorante con full access, banner giallo, audit log, bypass subscription expiry, logout safety (23/03/2026)
- [x] FASE 15 Email Broadcast con Crediti: area Comunicazioni dashboard, BroadcastService, cron batch TurboSMTP, crediti email, GDPR unsubscribe, URL auto-linking, sicurezza (XSS, rate limit, SMTP log sanitizzati) (23/03/2026)
- [x] Fix bug last_booking_at: aggiunta colonna + migration 021, popolamento da dati esistenti, aggiornamento su incrementBookings() (23/03/2026)

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
- [x] Link di gestione/cancellazione nell'email (link magico implementato in FASE 11)

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
- [x] Setup cron sul server di produzione (ogni 15 min)

### ~~7-11. Sviluppo futuro~~ → RIMANDATI
Le seguenti migliorie non sono prioritarie per il lancio e vengono rimandate a fasi successive:
- **Vista timeline/calendario** (Alta) — griglia oraria con blocchi colorati, FullCalendar
- **Gestione tavoli** (Molto alta) — tabelle, mappa sala, assegnazione automatica
- **Dark mode** (Media) — CSS variables, toggle, localStorage
- **~~PWA~~** — Scartata: icone duplicate per ogni ristorante
- **Multi-lingua** (Molto alta) — i18n, helper `__()`, IT + EN

### Ottimizzazioni performance (da fare in seguito)
- [ ] Minificazione CSS/JS (dashboard.css 105KB → ~25KB minificato, tool: csso/terser o build step) — rimandato a quando ci saranno 10+ utenti attivi
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

## FASE 9: Service Gating [COMPLETATA - approccio semplificato]
~~Architettura originale con PlanService e feature_definitions abbandonata.~~
**Implementazione (Marzo 2026):** Approccio semplificato con tabelle `plans`, `services`, `plan_services`.
- [x] Tabella `plans` (id, name, slug, price_monthly, description, features JSON, sort_order, is_active)
- [x] Tabella `services` (id, key, name, description, sort_order, is_active) — catalogo 14 servizi
- [x] Tabella `plan_services` (plan_id, service_id) — associazione piano-servizi
- [x] `Tenant::canUseService()` con cache per-request
- [x] Helper `tenant_can()` per view, `gate_service()` per controller
- [x] Lock card UI uniforme via partial `views/partials/service-locked.php`
- [x] Sidebar lucchetti per servizi non inclusi nel piano
- [x] Pagine pubbliche friendly (menu/unavailable.php) per servizi non disponibili
- [x] Admin panel: gestione piani CRUD, catalogo servizi, associazione piano-servizi
- [x] Piani gestiti dinamicamente dall'admin (Starter €29, Professional €59, Enterprise €99)
- [x] Coperti illimitati per tutti i piani — differenziazione solo per servizi inclusi

Servizi gatati: `digital_menu`, `promotions`, `custom_domain`, `export_csv`, `email_reminder`, `deposit`, `statistics`, `email_broadcast`
Servizi futuri: `analytics` (placeholder in catalogo)

## FASE 10: Gestione Domini Personalizzati (richiede piano con `custom_domain`)
- [ ] Testare flusso CNAME + verifica DNS (logica gia scritta)
- [ ] Configurare virtual host XAMPP per test locale con dominio custom
- [ ] Gestione SSL automatico (Let's Encrypt) per produzione
- [x] Gate: `tenant_can('custom_domain')` implementato — lock card su pagina dominio, sidebar lucchetto

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

### Blacklist Clienti → COMPLETATO
- [x] Flag `is_blocked` + `blocked_at` sulla tabella `customers`
- [x] Quando cliente bloccato tenta di prenotare (match email) → messaggio "Contatta il ristorante telefonicamente"
- [x] Bottone blocca/sblocca nella scheda cliente (toggle con flash message)
- [x] Indicatore visivo nella lista clienti (badge rosso, riga semitrasparente, avatar rosso su mobile)

### Note Clienti → COMPLETATO
- [x] Campo `notes` (TEXT) sulla tabella `customers`
- [x] Textarea nella scheda cliente per aggiungere/modificare note
- [x] Note visibili nella vista prenotazione (customer_notes_persistent in show.php)

## FASE 12: Giorni di Chiusura e Conferma Manuale [COMPLETATA]

### Giorni di Chiusura / Orari Speciali
Gestione chiusure straordinarie, ferie e orari speciali.
- [x] Model `SlotOverride` con metodi CRUD per chiusure (singole e range)
- [x] Controller `ClosuresController` (index, store, delete) con validazione max 90 giorni
- [x] Pagina dashboard "Chiusure" in Settings: calendario interattivo con selezione range
  - Chiusura singola giornata (es. festivo)
  - Ferie (range date, es. 10-25 agosto)
  - Preset rapidi (Oggi, Domani, Natale, Agosto, ecc.)
  - Motivo chiusura opzionale
- [x] Integrazione con AvailabilityService: giorni chiusi → zero slot nel widget
- [x] API pubblica: `GET /api/v1/tenants/{slug}/closures` per calendario widget
- [x] Lista chiusure prossime raggruppate per mese + storico chiusure passate

### Conferma Manuale Prenotazioni
- [x] Colonna `confirmation_mode` ENUM('auto','manual') su tenants (migration 007)
- [x] Toggle auto/manuale in Impostazioni Generali (radio buttons)
- [x] Se manuale: prenotazione arriva come `status = 'pending'`
- [x] Dashboard: bottone conferma rapida nella lista prenotazioni
- [x] Email conferma inviata solo quando stato passa a `confirmed`
- [ ] Timeout opzionale: se non confermata entro X ore, notifica al ristoratore (futuro)

## FASE 13: Notifiche Email [PARZIALMENTE COMPLETATA]
- [x] `MailService.php` con PHPMailer — invio SMTP configurabile da `.env`
- [x] SMTP configurato: `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_PORT`, `MAIL_ENCRYPTION`
- [x] Template email: conferma prenotazione (con link magico di gestione, sconto se applicato, link menu)
- [x] Template email: aggiornamento prenotazione (variante blu)
- [x] Template email: reset password (funzionante)
- [x] Template email: cancellazione prenotazione (notifica al ristoratore)
- [ ] Template email: ricevuta caparra (richiede integrazione Stripe, FASE 8)

### Reminder Email → COMPLETATO
- [x] Script cron `scripts/send-reminders.php` (24h + 2h prima)
- [x] Template HTML reminder (blu 24h, arancione 2h) con riepilogo + link gestione
- [x] Doppio reminder: 24h + 2h prima della prenotazione
- [x] Colonne `reminder_24h_sent_at` e `reminder_2h_sent_at` per evitare invii doppi
- [x] Service gating: skip tenant senza servizio `email_reminder` nel piano
- [x] Setup cron sul server di produzione (ogni 15 min)

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

## FASE 15: Email Broadcast con Crediti [COMPLETATA]
Email marketing per comunicare con i clienti del ristorante. Invio batch via TurboSMTP, crediti prepagati, GDPR compliant.

- [x] Pagina dashboard "Comunicazioni" con KPI (crediti, campagne inviate, destinatari raggiunti)
- [x] Form creazione campagna: oggetto, corpo testo con URL auto-linking, selezione segmento (6 filtri)
- [x] Preview conteggio destinatari live (fetch JS)
- [x] Dettaglio campagna con statistiche invio
- [x] Selezione destinatari: Tutti / Nuovi / Occasionali / Abituali / VIP / Inattivi da X giorni
- [x] Template email HTML: header verde con nome ristorante, corpo, info ristorante, footer GDPR
- [x] Link unsubscribe obbligatorio (GDPR): token 64 char, pagina pubblica standalone, flag irreversibile
- [x] Storico invii: lista campagne con stato (badge colorato), segmento, conteggi, paginazione
- [x] Rate limiting: max 1 broadcast/giorno per tenant
- [x] Sistema crediti: saldo su `tenants.email_credits_balance`, assegnazione da admin, decremento atomico
- [x] Transazioni crediti: storico completo in `email_credit_transactions`
- [x] Cron batch: `scripts/send-broadcasts.php` ogni 5 min, batch 50 recipient, 100ms delay, TurboSMTP
- [x] SMTP broadcast separato: `BROADCAST_SMTP_*` in .env (TurboSMTP), fallback su `MAIL_*`
- [x] Service gating: `email_broadcast` (Professional/Enterprise)
- [x] Sidebar dashboard: voce "Comunicazioni" con lucchetto se non nel piano
- [x] AuditLog: 4 eventi (broadcast created/sent/deleted, credits assigned)
- [x] Sicurezza: XSS prevention su URL, rate limit endpoint unsubscribe, SMTP password sanitizzate nei log
- [x] Migration 020: tabelle email_campaigns, email_campaign_recipients, email_credit_transactions, email_unsubscribes
- [x] Migration 021: colonna `last_booking_at` su customers per segmento "Inattivi"

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
- [x] Export prenotazioni in CSV (vedi Miglioria #2)
- [x] Validazione frontend inline booking widget (per-campo on-blur, auto-clear, no alert() nativi)
- [x] Slot passati nascosti per "Oggi" (widget: hidden, dashboard: grigi cliccabili)
- [x] Statistiche clienti (pagina dedicata con KPI, top frequenza, nuovi vs ritorno, segmentazione)
- [x] Rate limiting sulle API pubbliche (completato in Security Audit)
- [x] Security headers (CSP, X-Frame-Options, etc.)
- [x] Audit logging
- [x] Brute force protection (LoginThrottle)
- [x] Pagine auth redesign v3.1 (login, forgot-password, reset-password)

## FASE 18: Piani e Abbonamenti [PARZIALMENTE COMPLETATA]
Modello basato su **coperti illimitati** e **piani differenziati per servizi**.
- [x] Piani dinamici gestiti da admin (tabella `plans`: nome, prezzo, servizi associati)
- [x] Catalogo servizi (tabella `services`: 14 servizi con key univoca)
- [x] Associazione piano-servizi (tabella `plan_services`)
- [x] Admin panel: CRUD piani, gestione servizi, assegnazione piano a tenant
- [x] Admin panel: gestione abbonamenti (data inizio/fine, stato attivo/scaduto)
- [x] Gestione scadenze: TenantMiddleware redirect, pagine pubbliche sospese, API 403
- [x] Service gating runtime completo (7 servizi gatati, lock card UI)
- [ ] Stripe Subscriptions collegato ai piani
- [ ] Webhook per `invoice.payment_succeeded`, `invoice.payment_failed`
- [ ] Pagina pricing pubblica per i ristoratori
- [ ] Self-service upgrade/downgrade piano dal dashboard
- [ ] Trial migliorato: form creation con opzione trial, banner countdown, auto-email prima della scadenza

## FASE 19: Funzionalita Avanzate (Future)
- [ ] Notifiche SMS (Twilio o simile) — crediti pay-per-use
- [ ] Calendario visuale prenotazioni (vista settimanale/mensile)
- [ ] Analytics avanzati (servizio `analytics`): trend prenotazioni, heatmap orari, performance promo, revenue caparre — richiede piano Professional+
- [ ] Multi-lingua (i18n)
- [ ] Integrazione Google Calendar
- [ ] Waitlist (lista d'attesa quando pieno)
- [ ] Multi-sede (servizio `multi_location` gia nel catalogo)
- [ ] Accesso API (servizio `api_access` gia nel catalogo)
- [x] Email marketing con crediti (area comunicazioni + pacchetti email) → FASE 15 completata

## 🦠 Pandemia Playbook [DEFERITO]
Piano completo nel file dedicato: [`docs/pandemia-playbook.md`](docs/pandemia-playbook.md).
6 feature da costruire in ~2 settimane SE scoppia una nuova pandemia / emergenza sanitaria con restrizioni ai ristoranti:
1. Modalità "Emergenza" globale (switch settings)
2. Tracciamento walk-in lampo
3. Export contact tracing per ASL
4. Cancellazione massiva con email automatica
5. Gift card / voucher digitali (cash flow d'emergenza)
6. Banner d'emergenza globale (vetrina + widget + menu + ordering)

Trigger di attivazione: decreto/DPCM, crisi sanitaria, o 5+ clienti chiedono feature simili contemporaneamente.
**Estendibile**: aggiungere nuove idee al file man mano che vengono in mente.

## FASE 21: Lead Management & Onboarding [COMPLETATA]
Gestione lead da landing page evulery.it + onboarding ristoratori.

### Lead Management (CRM ristoratori)
- [x] Tabella `demo_requests` (migration 051)
- [x] API pubblica `POST /api/v1/demo-request` (rate limit, reCAPTCHA)
- [x] Form landing invia a API backend (JS fetch)
- [x] Pagina admin `/admin/leads` con lista, filtri (stato, data), KPI
- [x] 7 stati lead: new, contacted, demo_scheduled, demo_done, negotiating, customer, lost
- [x] Note interne con timestamp + storico attività (`demo_request_activities`)
- [x] Reminder follow-up (next_followup_at, alert dashboard admin)

### Onboarding rapido
- [x] Da lead → bottone "Convert" che pre-compila form tenant
- [x] Snapshot `tenants.acquired_by_reseller_id` al momento conversione

### Statistiche landing (futuro)
- [ ] KPI: richieste/mese, tasso conversione lead → cliente (deferito)
- [ ] UTM tracking (fonte lead: Google, social, referral) (deferito)

## FASE 22B: Programma Reseller B2B [COMPLETATA 2026-05-11]
Area `/reseller/*` per procacciatori B2B che vendono Evulery a ristoratori.

### Backend (commits a06d6f9, 6e59291, 99e073b)
- [x] Migration 052: ruolo `reseller`, tabella `reseller_profiles` (commissioni custom per reseller), `tenants.acquired_by_reseller_id`
- [x] Migration 053: tabella `credit_recharge_requests` (ordini ricarica crediti email)
- [x] Middleware `ResellerMiddleware` + login redirect basato su ruolo
- [x] Controller area: Dashboard, Leads, Clients, Commissions, Credits, Materials, Profile
- [x] `App\Services\CommissionCalculator` come single source of truth dei calcoli
- [x] Email auto al reseller all'assegnazione lead (`MailService::sendLeadAssignedToReseller`)
- [x] Email approvazione/rifiuto ricarica crediti

### Frontend
- [x] Layout `views/layouts/reseller.php` con sidebar verde brand + badge contatori
- [x] Dashboard: KPI (lead aperti, clienti attivi, maturato mese, totale maturato con breakdown)
- [x] Pagina commissioni dettagliata: storico mensile + breakdown per cliente + pagamenti attesi 12mo
- [x] Pagina ricariche crediti: form richiesta + storico stati + badge
- [x] Pagina materiali commerciali: catalogo whitelist (cliente / demo / outbound) con preview + download
- [x] Admin: `/admin/credit-requests` con approve/reject + email + Tenant::addCredits in transazione

### Hardening security (post-review)
- [x] `User::update($id, $data, $allowPrivileged=false)` per evitare mass-assignment
- [x] Approve credit con `SELECT ... FOR UPDATE` (no double-credit su double-click)
- [x] Rimosso leak note admin private dalla view reseller leads/show
- [x] Allineata formula calcolo commissioni tra Dashboard e Commissions (TIMESTAMPDIFF)
- [x] Skip tenant senza piano/sub nei calcoli (no crash 500 su edge case)
- [x] `realpath()` check difensivo in MaterialsController (whitelist + path traversal safety)
- [x] Validazione `next_followup_at` via `DateTime::createFromFormat`

### Pre-deploy in produzione
- [x] Applicare migration 052 (reseller_profiles, acquired_by_reseller_id, role VARCHAR)
- [x] Applicare migration 053 (credit_recharge_requests)
- [ ] Cambiare password super admin (durante test era stata resettata ad `admin1234`)

## FASE 22B-bis: Reseller refinements [COMPLETATA 2026-05-12]
Rifiniture su area reseller + materiali commerciali.

### Reseller side
- [x] Pagina `/reseller/leads/create`: il reseller aggiunge lead direttamente dal pannello
  (auto-assignment, status `new`, utm_source `reseller_added`, anti-duplicato non bloccante 30gg)
- [x] Bottone "Nuovo lead" verde nel page header `/reseller/leads`

### Admin side
- [x] `destroyReseller` con safety check: blocca se ha tenant attivi, altrimenti NULL su
  `acquired_by_reseller_id` / `assigned_reseller_id` + hard delete con CASCADE
- [x] Bottone "Elimina reseller" in danger zone del form edit
- [x] Email duplicata in `storeReseller`: alert dettagliato con nome/ruolo/stato +
  link diretto alla scheda esistente (no più generico "esiste già")
- [x] Normalizzazione aggressiva email (lowercase + rimozione NBSP/zero-width)

### Materiali reseller
- [x] `sales/demo-script.html` — versione HTML interattiva (timer sticky, checklist
  persistenti localStorage, scroll-spy TOC). Il `.md` resta come sorgente per copyediting
- [x] `sales/roi-calculator.html` — strumento live per la demo:
  - Input vuoti, niente numeri inventati come default
  - Output con formule visibili sotto ogni numero
  - Card piano-aware (Starter 10-25% recupero noshow per "solo reminder",
    Pro/Enterprise 30-90% per "caparra Stripe attiva")
  - Card "Primo anno (€249 setup)" + "A regime in 12 mesi"
  - Bottone "Copia testo per email post-demo"
  - Disclaimer IVA + "fatturato non utile" + stima conservativa
- [x] `sales/faq-ristoratore.html` — 15 FAQ accordion in 5 categorie
  (contratti/migrazione/uso/dati/supporto) da inviare ai prospect tiepidi
- [x] Battlecard rinominata da `battlecard-thefork.html` a `battlecard-piattaforme.html`
- [x] **MaterialsController** inietta nonce CSP nei `<script>` inline degli HTML serviti
  (Lesson #19: senza nonce gli script venivano bloccati silenziosamente dalla CSP)
- [x] View materiali gestisce card 'client' senza PDF (solo "Apri")

## FASE 22C: UX dashboard navigation [COMPLETATA 2026-05-12]
Navigazione giorno-per-giorno più rapida in `/dashboard` e `/dashboard/reservations`.

- [x] Frecce ‹ › sempre visibili accanto alla date-strip (vai a `?date=±1` server-side)
- [x] Funzionano anche senza JS, nessun limite sui salti
- [x] Filtri stato/source preservati nei link
- [x] In modalità "Prossime" o range le frecce non vengono renderizzate
- [x] Shortcut tastiera ←/→ (ignora se focus in input/textarea/contenteditable)
- [x] Page header con titolo + pill `.dh-date-badge` brand-light per visibilità
  "quale giorno stai vedendo" (utile coordinato con le frecce)
- [x] Layout flex: desktop riga (`Prenotazioni · Mar 12 Maggio 2026`), mobile colonna
- [x] Stile frecce coerente con `.date-chip` / `.date-chip-sm` (border 2px / 1.5px)

### Possibili refinements futuri (filter bar prenotazioni)
- [ ] **Datepicker DA/A "stale"** — DEFERITO in attesa di segnalazione reale da un cliente.
  Il PHP è corretto (`value="<?= $date ?>"`), il disallineamento osservato è browser
  form-state restore (bfcache/autofill su `<input type="date">`). Fix pronto se serve:
  script `pageshow` che riassegna il value server-side ai 2 datepicker. Da implementare
  SOLO quando un cliente segnala lo scenario esatto (browser + azione) per fix verificabile.
- [ ] Filtri secondari (DA/A, STATO, FONTE) collassabili sotto "Filtri" toggle
- [ ] DA/A singolo quando from=to (UI più pulita)
- [ ] STATO/FONTE come chip toggle invece di `<select>`

### Gestione lead reseller
- [x] **Modifica anagrafica lead dal backend** [COMPLETATA 2026-05-13] — admin su
  qualsiasi lead + reseller sui propri. Form "Correggi anagrafica" in /admin/leads/{id}
  e /reseller/leads/{id}. Validazione + blocco email duplicata + activity log del diff.
  Commit 10699de.

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
- [x] Calendario mobile dashboard home: verificato su device reale (≥375px)
- [x] Calendario mobile pagina prenotazioni: fix sab/dom su mobile
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
