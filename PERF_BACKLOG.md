# PERF_BACKLOG - Evulery.Pro 1.0

> Tracciamento **prestazioni** (latenza/throughput), separato dall'`AUDIT_REPORT.md`
> (che copre bug/sicurezza/codice-morto/qualità e **non** tocca le performance).
> Questo file è un backlog vivo: si aggiorna man mano che gli interventi vengono
> fatti/misurati. **Nessuna modifica al codice è implicita in questo documento.**
>
> **Data apertura:** 2026-07-20 · **Origine:** analisi dei log `storage/logs/perf-*.log`
> di **produzione** (2026-06-26 → 2026-07-18) + lettura del codice reale.

---

## Contesto e metodo

- Il logger `App\Core\PerfLog` registra **solo le richieste oltre una soglia**
  (`PERF_LOG_THRESHOLD_MS`, default **500 ms** — [PerfLog.php:27](app/Core/PerfLog.php#L27)).
  Quindi i log mostrano **solo la coda lenta**, non la mediana. La durata misurata è
  il **wall-clock totale** dall'arrivo della richiesta (`microtime - REQUEST_TIME_FLOAT`),
  quindi **include il tempo passato in attesa di lock/rete**, non solo il lavoro CPU/DB.
- Memoria per-richiesta osservata: **2–4 MB** (peak) → nessun problema di memoria.
- Traffico attuale **leggero** (poche decine di richieste lente in 3 settimane) → questo
  è **tuning proattivo**, non un incendio. Priorità: valore/rischio, non urgenza.

## Fatti accertati (verificati nel codice, non ipotesi)

1. **Lock di sessione tenuto per tutta la richiesta.** `Session::start()` usa l'handler
   **file** di default (solo `save_path` custom, nessun handler — [Session.php:26-52](app/Core/Session.php#L26)).
   L'handler file acquisisce un **lock esclusivo** sul file di sessione fino a fine script.
   **`session_write_close()` non è chiamato da nessuna parte** (grep vuoto) → due richieste
   con lo **stesso cookie di sessione** si **serializzano** sul lock del filesystem.
2. **Polling ogni 30 s** su `notifications/unread` da ogni dashboard aperta
   ([dashboard-notifications.js:28](public/assets/js/dashboard-notifications.js#L28)).
3. **Push sincrona e bloccante nel ciclo di richiesta.** `NotificationService::sendPush()`
   fa `$webPush->flush()` ([NotificationService.php:201](app/Services/NotificationService.php#L201))
   che apre connessioni HTTP verso Google/Apple **dentro** la richiesta. Chiamata durante
   la creazione prenotazione e i cambi stato.

## Evidenze dai log (coda > 500 ms)

- `GET /dashboard/notifications/unread` ricorrente **577–1574 ms**, pur essendo banale
  (un COUNT + 1 riga, [NotificationController.php:44](app/Controllers/Dashboard/NotificationController.php#L44)).
- `GET /dashboard` fino a **4029 ms** (30/06, dentro un burst di richieste sovrapposte con
  durate crescenti 559→1216→2166 ms — firma tipica della serializzazione su lock).
- `POST /api/.../reservations` (booking pubblico) **sempre 525–1366 ms**.
- `POST /manage/.../cancel` **2934 ms** (isolato). `POST /dashboard/customers/import` 6258 ms (import CSV, accettabile).
- Rumore: `GET /robots.txt` e `/file52.php` → **404 attraversando tutto il framework** (527–1300 ms).

---

## Interventi (ordinati per valore/rischio)

Legenda stato: `TODO` · `IN CORSO` · `FATTO` · `MISURATO`

### [P1] Rilascio anticipato del lock di sessione — `session_write_close()` sugli endpoint read-only
- **Stato:** ✅ FATTO (commit `a3b07b4`) — helper `Session::closeWrite()` su apiUnread/apiRecent/heartbeat reservations+floor. Beneficio misurabile in prod (perf log).
- **Completato l'11/09/2026**: mancavano `OrderController::apiKanban` e `::apiStats`, pollati ogni **15 s**
  (il doppio della campanella) dalla pagina Ordini. Stesso schema: gate → `Auth::tenantId()` → `closeWrite()`
  → query → JSON. Verificato che i tre metodi del modello (`getKanbanData`, `getCompletedToday`, `getStats`)
  non tocchino sessione né auth, e che `closeWrite()` stia **dopo** `gate()`: il gate usa `flash()` + redirect,
  e metterlo prima avrebbe fatto perdere quel messaggio in silenzio. Ora il P1 copre tutti gli endpoint pollati.
- **Cosa:** helper `Session::closeWrite()` chiamato dopo la lettura di `tenant_id` in 4 endpoint
  sola-lettura ad alta frequenza: `NotificationController::apiUnread` (:44), `::apiRecent` (:70),
  `HeartbeatController::reservations` (:22), `::floor` (:40). Verificato: leggono solo
  `Auth::tenantId()`, poi query, poi JSON — **non scrivono in sessione dopo**.
- **Complessità:** BASSA (1 helper + 4 call-site).
- **Rischio rottura:** BASSO. Fallirebbe solo se uno di questi scrivesse in sessione dopo la
  chiusura (non lo fa) o usasse flash/CSRF (sono GET JSON). `_last_activity` è salvato in
  `start()` prima della chiusura → sessione viva.
- **Beneficio (onesto):** concreto con **richieste concorrenti sullo stesso browser**
  (multi-tab, poll che si sovrappone a pagina lenta); minore per singola scheda ferma.
- **Tempo:** ~1,5–2 h con test (4 endpoint + login + misura con dashboard aperta + poll).

### [P2] Push fuori dal ciclo di richiesta (booking POST)
- **Stato:** ✅ P2a FATTO (commit `8396379`) — `register_shutdown_function` + `fastcgi_finish_request` in `sendPush`. Beneficio SOLO prod (LiteSpeed); validare in prod. **P2b (coda async completa) resta TODO** (soluzione definitiva a roadmap).
- **Cosa:** togliere il `flush()` sincrono dal percorso della richiesta.
  - **2a (mitigazione, consigliata come primo passo):** `fastcgi_finish_request()` — risposta
    inviata al cliente **prima** della push, che parte dopo nello stesso processo.
  - **2b (definitiva):** push su coda async `notification_outbox` + worker cron (come le email,
    "FASE 2 push async" già a roadmap).
- **Complessità:** 2a BASSA · 2b MEDIA/ALTA.
- **Rischio rottura:** 2a BASSO-MEDIO (dipende dal SAPI; se assente → no-op innocuo, serve
  fallback). 2b MEDIO (tocca la consegna notifiche; testare il worker per non perdere push).
- **Tempo:** 2a ~2–4 h · 2b ~1–2 giorni.
- **Consiglio:** partire da **2a**, rimandare 2b.

### [P3] Picco `GET /dashboard` 2–4 s — INDAGINE (non ancora fix)
- **Stato:** TODO (misura)
- **Cosa:** `HomeController::index` ([:17](app/Controllers/Dashboard/HomeController.php#L17)) fa
  ~10 query + `getMealCapacity` con loop annidati (slot × prenotazioni, limitati). **Nessuna N+1
  evidente** trovata (nessuna query dentro un `foreach` su righe). Il picco a 4 s è **singolo**,
  probabilmente lock di sessione + cache fredda, **non** necessariamente una query lenta.
- **Complessità:** indagine BASSA · fix eventuale MEDIA.
- **Rischio:** indagine ZERO (lettura + timing temporaneo).
- **Tempo:** ~2–3 h. **Da fare DOPO [P1]** e ri-misurare (il lock potrebbe assorbire il picco).

### [P4] Bug `Cache::remember`: valore `null`/`0` trattato come miss
- **Stato:** TODO · **doppio con audit [36]**
- **Cosa:** la cache ri-esegue il resolver quando il valore cachato è `null`/falsy
  ([Cache.php:22-66](app/Core/Cache.php#L22)) → aggregati per-tenant ricalcolati ad ogni
  richiesta. Fix con sentinella per distinguere miss da null.
- **Complessità:** BASSA.
- **Rischio rottura:** BASSO-MEDIO — cambia la semantica di `Cache::get`/`remember`: prima del
  fix, **grep di tutti i chiamanti** per escludere chi si affida a "null = miss".
- **Tempo:** ~1–2 h incluso l'audit dei chiamanti.

### [P5] Rumore 404 bot + `robots.txt` (minore)
- **Stato:** TODO · priorità bassa
- **Cosa:** `robots.txt` statico in `public/` + eventuale corto-circuito per path spazzatura noti
  così i bot non attraversano tutto il framework.
- **Complessità:** BASSA. **Rischio:** BASSO. **Tempo:** ~30 min.

---

## Intreccio con l'AUDIT_REPORT.md

- ~~**Audit [21] — diagnostica CSRF temporanea in `CSRFMiddleware.php`**~~ — **CHIUSO il 10/09/2026.**
  Aveva risposto alla sua domanda: l'ultimo `CSRF FAIL` in produzione è del **19/08 alle 20:04**,
  con `has_cookie=no` e il corpo della richiesta arrivato intero — cioè il cookie di sessione perso
  su iOS Safari, non il body perso in transito. La correzione ("auto-guarigione", commit 983f9cc) è
  del **20/08, il giorno dopo**, e da allora il log tace. Rimosse 40 righe, resta una riga sola senza
  dati personali. Via anche la lettura di `php://input`, che su ogni fallimento leggeva un corpo
  contenente la password.
- **Audit [36] — `Cache::remember` null-miss:** coincide con **[P4]** qui sopra. Fixarlo copre
  entrambi (perf + correttezza cache).

## Ordine consigliato
**[P1]** (misurabile, basso rischio) → misura → **[P2a]** → poi valutare se **[P3]/[P4]/[P5]**
valgono lo sforzo. Il **quick win [P1]+[P2a]** dà il grosso del beneficio in ~mezza giornata.