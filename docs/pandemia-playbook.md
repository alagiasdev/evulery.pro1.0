# Pandemia Playbook · Evulery

> **Scopo**: piano deferito di feature da implementare rapidamente nel caso in cui scoppi una nuova pandemia o emergenza sanitaria che imponga restrizioni ai ristoranti.
> Le feature sono pensate per essere costruite **in 1-2 settimane totali** a partire dal "Giorno 0".
> Questo documento è un "playbook estensibile" — aggiungere idee man mano che vengono.

**Ultimo update**: 2026-05-13

---

## Filosofia

Durante COVID 2020-2022 i ristoratori si sono trovati nudi davanti a esigenze impreviste:
- capienza ridotta dall'oggi al domani
- obbligo di tracciamento contatti
- chiusure improvvise (zone rosse)
- pivot forzato su asporto/delivery
- cash flow d'emergenza

Chi aveva software flessibile è sopravvissuto. Chi aveva strumenti rigidi (registri cartacei, Excel, TheFork) ha sofferto.

**Strategia Evulery**: non costruire ora queste feature (over-engineering), ma avere il **piano pronto da eseguire in 2 settimane** se serve. Diventa un argomento commerciale forte: "Evulery è pandemia-ready".

---

## Cosa Evulery copre già oggi (assets di partenza)

| Funzione | Già esistente | Note |
|---|---|---|
| Online ordering (takeaway + delivery) | ✅ | Pivot rapido senza dover comprare/configurare |
| Caparra Stripe automatica | ✅ | Anti-noshow + cash flow |
| Email broadcast con crediti | ✅ | Comunicazione clienti massiva |
| Push notifications | ✅ | Urgenze in tempo reale |
| Vetrina Digitale + QR | ✅ | Aggiornamenti senza ristampe fisiche |
| Anagrafica clienti GDPR | ✅ | Base per contact tracing |
| Sistema slot/capienza configurabile | ✅ | Base per modalità ridotta |
| Cancellazione con email automatica | ✅ | Già implementata per singola prenotazione |

---

## Feature da costruire (priorità per impatto)

### 🥇 1. Modalità "Emergenza" globale
Switch unico in `Settings → Emergenza` che riconfigura il ristorante in modalità ridotta senza dover toccare 10 impostazioni separate.

**Cosa attiva quando ON**:
- Capienza limitata a X% (slider configurabile, default 50%)
- Durata massima per tavolo (es. 90 min obbligatori)
- Buffer tra turni (es. 30 min per sanificazione)
- Slot fissi (es. 2 turni serali: 19:00 e 21:00)
- Banner automatico in widget/Vetrina: "Capienza limitata, prenotazione obbligatoria"
- Caparra obbligatoria su tutte le prenotazioni (override impostazione tenant)

**Tempo**: 1-2 giorni. Riusa logica capienza/slot esistente.

**File coinvolti** (stima):
- Migration: nuovo campo `tenants.emergency_mode` (bool) + opzioni JSON
- `app/Models/Tenant.php`: getter/setter
- `app/Controllers/Dashboard/SettingsController.php`: tab "Emergenza"
- `app/Services/AvailabilityService.php`: rispetta cap durante emergency
- Banner partial mostrato in tutti i layout pubblici

---

### 🥈 2. Tracciamento walk-in lampo
Bottone "Registra cliente entrato ora" in dashboard. Form 2-tap: solo nome + telefono. Salva in `customers` con timestamp `entered_at` e tag `walkin_traced=1`.

In pandemia diventa obbligatorio per legge tracciare TUTTI i clienti presenti, anche quelli senza prenotazione.

**Tempo**: mezza giornata.

**File coinvolti**:
- Nuovo endpoint `POST /dashboard/customers/quick-track`
- Pulsante nel page header `/dashboard` (sticky)
- Migration: aggiungere `customers.last_walkin_at` + tag system esistente

---

### 🥉 3. Export contact tracing per ASL
Endpoint "Esporta presenze dal X al Y" → CSV/PDF con: nome cliente, telefono, data/ora prenotazione, durata stimata, modalità (prenotazione o walk-in).

Pronto da inviare alla ASL in caso di caso positivo.

**Tempo**: mezza giornata.

**File coinvolti**:
- `app/Controllers/Dashboard/CustomersController.php`: nuovo metodo `exportContactTracing`
- Route `GET /dashboard/customers/contact-tracing-export?from=X&to=Y`

---

### 4. Cancellazione massiva con email automatica
Dashboard → "Annulla tutte le prenotazioni dal X al Y" → ogni cliente riceve email con motivo personalizzabile (default: "Causa restrizioni sanitarie") + voucher futuro come scusa.

**Tempo**: 1 giorno.

**File coinvolti**:
- `app/Controllers/Dashboard/ReservationsController.php`: metodo `bulkCancel`
- View "Cancellazione massiva" con preview lista clienti coinvolti prima del confirm
- `MailService::sendBulkCancellation` (estensione di `sendCancellationNotification`)

---

### 5. Gift card / voucher digitali
Vendita voucher dal widget pubblico. Stripe checkout → email con codice univoco → utilizzabile dopo riapertura.

**Caso d'uso**: cliente sostiene economicamente il ristorante chiuso, lo riscatta dopo riapertura. Diventa fonte di cash flow durante zone rosse.

**Tempo**: 3-4 giorni (è una mini-feature a sé).

**File coinvolti**:
- Migration: tabella `gift_vouchers` (codice, importo, status, expires_at)
- Stripe checkout dedicato (riusare integration esistente)
- Widget pubblico: nuovo step "Acquista voucher" parallelo a "Prenota"
- Dashboard: visualizzazione voucher venduti/riscattati

---

### 6. Banner d'emergenza globale
Banner verde/rosso configurabile da dashboard, mostrato in TUTTE le pagine pubbliche:
- Vetrina Digitale (`/hub`)
- Widget prenotazioni
- Pagina menu pubblica
- Pagina ordering pubblica
- Pagina recensioni

Esempio: *"Siamo aperti solo per asporto fino al 15 marzo"*. 1 modifica, propagazione ovunque.

**Tempo**: 1 giorno.

**File coinvolti**:
- Migration: `tenants.public_banner_text`, `tenants.public_banner_type` (info/warning/danger), `tenants.public_banner_until` (data scadenza)
- Partial `public-banner.php` incluso in tutti i layout pubblici
- Tab "Banner pubblico" in settings

---

## Tempi totali

| Sequenza | Tempo |
|---|---|
| Giorno 1 | Modalità Emergenza + Banner globale → ristorante pronto a comunicare |
| Giorno 2-3 | Walk-in tracking + Export contact tracing → compliance legale |
| Settimana 2 | Cancellazione massiva + Gift card → gestione crisi + cash flow |
| **TOTALE** | **~2 settimane** per essere "pandemia-ready" |

---

## Cosa NON fare (lessons learned dal 2020)

- ❌ **Green Pass / health screening nativi**: trauma legale, continuamente cambiato, ostile come UX. Lasciare al mercato di app dedicate
- ❌ **Distanziamento tavoli "visivo"**: over-engineering. Basta capienza % ridotta
- ❌ **Auto-dichiarazioni temperatura/sintomi**: GDPR + politico, mai una buona idea
- ❌ **Integrazione con sistema sanitario regionale**: troppo frammentato, ogni Regione ha la sua API/burocrazia
- ❌ **Costringere il cliente a firmare disclaimer**: friction enorme, abbandono prenotazioni

---

## Argomento commerciale post-implementazione

Se questo playbook viene eseguito (durante o subito dopo una nuova ondata), il pitch commerciale verso ristoratori diventa **molto forte**:

> *"Evulery ha gestito ristoranti durante COVID 2026 quando altri sistemi stavano fermi. Siamo pronti per la prossima emergenza con feature integrate da prima."*

Già la presenza di questo playbook documentato (visibile a investitori/partner) è un asset di credibilità.

---

## Backlog idee aggiuntive

Spazio per annotare nuove idee man mano che vengono in mente (anche prima che la pandemia arrivi):

- [ ] *(da popolare)*

---

## Trigger per attivare il playbook

Eseguire i task del playbook se accade UNO di questi scenari:
1. Decreto/DPCM/ordinanza che impone capienza ridotta o lockdown per ristorazione
2. Crisi sanitaria documentata (es. nuova variante con misure restrittive)
3. Richiesta di feature specifiche da 5+ clienti contemporaneamente (segnale che il mercato sta cambiando)

In caso di trigger:
1. **Giorno 0**: communicazione clienti Evulery via email broadcast "Stiamo lavorando alle feature di emergenza, disponibili entro X giorni"
2. **Giorno 1-2**: switch Emergenza + Banner globale → ai clienti immediato vantaggio operativo
3. **Settimana 1**: walk-in tracking + contact tracing
4. **Settimana 2**: gift card + cancellazione massiva

---

## Owner

Stefano Alagia. Tutte le decisioni strategiche sull'attivazione del playbook sono sue.
