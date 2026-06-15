# Account collaboratori (staff) — discussione cliente + scoping

> Documento di lavoro per i colloqui con i ristoratori (giugno 2026).
> Bisogno emerso da piu' richieste: dare un accesso ai dipendenti che
> permetta di gestire le prenotazioni MA non di toccare le impostazioni
> del ristorante (prezzi, orari, caparra, piani, ecc.).
>
> Decisione gia' presa: **funzionalita' gatata Professional + Enterprise**
> (lo Starter e' pensato per piccoli ristoratori che gestiscono tutto in
> prima persona, non hanno bisogno di account separati).

---

## 1. Il bisogno

Il titolare oggi ha un solo accesso "tuttofare". Quando vuole far gestire
le prenotazioni a un cameriere, alla persona alla cassa o al figlio che
dà una mano la sera, è costretto a dargli le SUE credenziali — che però
aprono anche orari, prezzi, caparra, dati di fatturazione e configurazioni
delicate. Un errore (o una modifica non voluta) può creare danni.

La richiesta: **un accesso "ridotto" per i collaboratori**, che permetta
di lavorare sulle prenotazioni senza poter cambiare le impostazioni.

---

## 2. La proposta: due livelli di accesso

### Titolare (come oggi)
Accesso completo: prenotazioni, sala, ordini, clienti, comunicazioni,
recensioni, **e tutte le impostazioni** (orari, coperti, tavoli, caparra,
promozioni, piano, ecc.). È il proprietario dell'account.

### Collaboratore (nuovo)
Accesso al **lavoro di tutti i giorni**, senza le impostazioni:
- ✅ **Prenotazioni** — vede, crea, modifica, segna arrivati/no-show
- ✅ **Sala / tavoli** — vede la mappa, assegna i tavoli
- ✅ **Ordini online** — gestisce i takeaway/delivery in arrivo
- ✅ **Notifiche** — riceve gli avvisi in tempo reale
- ❌ **Impostazioni** — orari, coperti, tavoli (configurazione), caparra,
  promozioni, dominio, piano: tutto bloccato
- ❌ **Comunicazioni / email marketing** — bloccato (manda email a tutti i
  clienti, troppo delicato)
- ❌ **Cambio piano / fatturazione** — bloccato

> Il collaboratore vede una dashboard "più snella": spariscono le voci che
> non può usare. Se prova a entrare in una pagina vietata digitando
> l'indirizzo a mano, il sistema lo blocca comunque (sicurezza vera, non
> solo voci nascoste).

---

## 3. Le decisioni da prendere — DA CHIEDERE AI RISTORATORI

Queste quattro domande cambiano come costruiamo la funzione. Raccogliere
le risposte dai colloqui prima di sviluppare.

### Domanda A — Il collaboratore deve vedere l'archivio CLIENTI completo?
Per gestire una prenotazione gli basta vedere nome e telefono di *quella*
prenotazione. Ma l'archivio clienti completo (tutti i contatti, telefoni,
email, storico visite, compleanni) è una cosa diversa — è il patrimonio
del ristorante.
- **Opzione 1**: il collaboratore NON vede l'archivio clienti completo
  (vede solo i dati della singola prenotazione). Più sicuro per la privacy.
- **Opzione 2**: il collaboratore vede tutto l'archivio clienti.
- *Da chiedere*: "Ti darebbe fastidio se il cameriere potesse scaricare/
  consultare tutta la rubrica clienti del locale?"

### Domanda B — Account individuali o un unico account condiviso?
- **Individuali** (un account per Mario, uno per Giulia): si sa sempre CHI
  ha fatto cosa — utile se una prenotazione viene cancellata per errore o
  c'è una contestazione. Più ordine.
- **Condiviso** (una sola password "staff" per tutti): più semplice ma non
  si sa chi ha fatto cosa.
- *Da chiedere*: "Quante persone gestirebbero le prenotazioni? Ti
  interessa sapere chi ha fatto una modifica, o va bene un accesso unico?"

### Domanda C — Il collaboratore può ANNULLARE le prenotazioni?
- **Sì**: gestione completa (crea, modifica, annulla).
- **Solo creazione/modifica**: può prendere e spostare prenotazioni ma non
  cancellarle (per evitare cancellazioni accidentali o non autorizzate).
- *Da chiedere*: "Vuoi che il collaboratore possa anche annullare una
  prenotazione, o preferisci che lo faccia solo il titolare?"

### Domanda D — Recensioni: operativo o riservato al titolare?
La gestione recensioni (rispondere ai feedback, vedere la reputazione) è
una cosa che fai gestire ai collaboratori o te la tieni?
- *Da chiedere*: "Le recensioni le gestisci tu o le lasceresti anche allo
  staff?"

---

## 4. Sicurezza — come la spieghiamo al ristoratore

- Il collaboratore **non può cambiare il proprio livello** né crearsi
  permessi: solo il titolare crea e gestisce i collaboratori.
- Il collaboratore **non può cambiare il piano** né vedere la fatturazione.
- Le pagine vietate sono bloccate **davvero**, non solo nascoste: anche
  conoscendo l'indirizzo esatto, il sistema nega l'accesso.
- Il titolare può **disattivare** un collaboratore in qualunque momento
  (es. dipendente che se ne va) con un click, senza cancellare lo storico.

---

## 5. Come funziona per il titolare (bozza UX)

In **Impostazioni → Collaboratori** (visibile solo al titolare):
1. "Aggiungi collaboratore": nome, email, password iniziale.
2. Lista dei collaboratori con stato Attivo/Disattivato.
3. Pulsanti: Modifica, Disattiva/Riattiva, Elimina.

Il collaboratore riceve le sue credenziali e accede dallo stesso indirizzo
del titolare (`dash.evulery.it`), ma vede una dashboard ridotta.

---

## 6. Piani e prezzo

**Funzione inclusa in Professional ed Enterprise.** Non disponibile su
Starter (pensato per chi gestisce tutto da solo).

Da decidere (interno, dopo i colloqui):
- Numero di collaboratori: **illimitati** o un tetto (es. Professional fino
  a 3, Enterprise illimitati)?
- *Indicazione*: probabile partire con illimitati per semplicità, mettere
  un tetto solo se emerge un abuso. Da validare col volume reale.

---

## 7. Domande riassuntive da fare nei colloqui

1. Quante persone, oltre a te, gestirebbero le prenotazioni?
2. Ti basta che vedano le prenotazioni, o anche ordini online e sala?
3. Vuoi che vedano tutta la rubrica clienti o solo i dati della singola
   prenotazione? (privacy)
4. Account separati per persona (sai chi fa cosa) o uno unico condiviso?
5. Il collaboratore può annullare prenotazioni o solo crearle/modificarle?
6. Le recensioni le gestisci solo tu o anche lo staff?
7. Quanti collaboratori pensi servirebbero al massimo?

Le risposte mi dicono se sviluppare **adesso** o dopo, e con quale livello
di dettaglio.

---

## 8. Note tecniche (uso interno)

Stato attuale del codice (verificato 2026-06-15):
- Ruolo `staff` GIA' presente nell'enum `users.role`
  (`super_admin`/`owner`/`staff`).
- `App\Core\Auth` salva gia' il ruolo in sessione; ci sono `Auth::role()`,
  `isSuperAdmin()`, `isOwner()`, `belongsToTenant()`. Manca `isStaff()`.
- `App\Models\User` gestisce gia' il ruolo nel create/update con flag
  `allowPrivileged` anti-escalation (lo staff non puo' alzarsi a owner).
- **Manca**: middleware di ruolo che protegga le rotte `/dashboard/settings/*`
  + `/communications` (il pezzo di sicurezza vero), UI titolare per creare
  collaboratori (`StaffController` dashboard), filtraggio sidebar su
  `Auth::role()`, helper `Auth::isStaff()`.

Modello consigliato: **2 ruoli fissi** (owner/staff), NON permessi granulari
(over-engineering per il bisogno reale; il modello a ruoli si estende dopo
se serve).

Sicurezza chiave: protezione **lato server** delle rotte di configurazione,
non solo voci nascoste in sidebar (un URL digitato a mano deve essere
bloccato dal middleware).

Gating: nuovo servizio (es. `staff_accounts`) assegnato a Professional +
Enterprise, gating SOLO-UI coerente con la prassi (vedi
[[project-durata-fascia]] per il pattern grandfathering al downgrade).

Stima: ~5-6h (il grosso e' il middleware di ruolo + pagina gestione
collaboratori). Retrocompatibile: gli owner attuali restano owner.

*Ultimo aggiornamento: 2026-06-15 · documento interno per i colloqui
Early Adopter.*
