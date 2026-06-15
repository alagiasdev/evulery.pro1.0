# Assistente AI telefonico/prenotazioni vocali — decisione: FUORI ROADMAP

> **Stato: valutato e scartato (2026-06-15).** Non è un backlog da fare,
> è una decisione di prodotto motivata. Documentato per avere una risposta
> pronta quando altri ristoratori lo richiedono (è già emerso da più di uno).

---

## La richiesta

Alcuni ristoratori (giugno 2026) hanno chiesto un assistente AI che:
risponde al telefono, parla col cliente, capisce quante persone / che ora /
che giorno, crea la prenotazione e manda la conferma. Versione "vocale" di
un receptionist automatico.

## La decisione

**Probabilmente non lo faremo.** Va contro la filosofia di prodotto di
Evulery. Se mai si valutasse, NON nella forma vocale (vedi sotto).

## Perché NO — il ragionamento

La forza di Evulery è l'opposto del voice AI: tutto integrato, zero
commissioni, **zero dipendenze da terzi**, canone fisso, semplice da usare
e da mantenere. Il voice AI romperebbe ogni pilastro:

1. **Dipendenze esterne pesanti** — telefonia (Twilio/Telnyx), trascrizione
   vocale, sintesi vocale, orchestratori (Vapi/Retell). Ogni integrazione =
   una chiave da custodire, un fornitore che cambia prezzi/API, un punto di
   rottura, una superficie di sicurezza in più. Evulery punta a poche
   librerie, `vendor/` committato, audit semplice.
2. **Costi variabili ricorrenti** — i voice agent costano per-minuto
   (~0,15–0,45 € a chiamata). Snaturano il modello a canone fisso, che è
   l'argomento di vendita principale contro le piattaforme a commissione.
3. **GDPR / responsabilità** — registrazioni vocali = dati personali
   sensibili da gestire/conservare; responsabilità sugli errori dell'AI
   (prenotazione sbagliata = danno reale al ristoratore).
4. **Qualità non garantita** — riconoscimento italiano con accenti regionali
   + rumore del ristorante in sottofondo. Un'AI che capisce male l'orario o
   i coperti è peggio di nessuna AI.
5. **Mismatch di target** — feature da catena/ristorante premium con reparto
   IT. Il target Evulery (trattorie, piccolo-medi) la trova "comoda" in
   astratto ma non la usa davvero; vuole semplicità.

**Principio guida**: una piattaforma che fa poche cose bene e si rompe
raramente vale più di una che fa tutto e va manutenuta come un aereo.
La semplicità (sicurezza + poche integrazioni) è un vantaggio competitivo.

## Cosa rispondere ai ristoratori che lo chiedono

> "È una bella idea e ci abbiamo pensato. Per ora abbiamo scelto di non
> farla: vogliamo tenere Evulery semplice, sicuro e senza costi a sorpresa.
> Un centralino AI aggiunge complessità e costi per chiamata che andrebbero
> contro la nostra promessa di canone fisso e zero pensieri. Preferiamo
> investire su quello che usi ogni giorno."

## Se un domani il bisogno diventasse fortissimo (riserva)

NON partire dal voce. La via meno invasiva sarebbe il **testo via WhatsApp**:
il cliente scrive "tavolo per 2 dopodomani alle 20", un assistente AI capisce,
verifica disponibilità via API Evulery esistente, conferma. Elimina i due
problemi peggiori del voce (riconoscimento audio + costo per-minuto alto) e
copre lo stesso bisogno (in Italia si prenota molto via WhatsApp). Ma anche
questa aggiunge dipendenze esterne (WhatsApp Business API + LLM) e costi
variabili: resta coerente con questa decisione tenerla fuori salvo evidenza
di domanda massiccia + modello add-on a pagamento sostenibile.

Nota: l'unico pezzo "gia' pronto" sarebbe il cervello-prenotazione (API
availability + creazione + conferma email esistenti). Lo strato
conversazionale e telefonico è tutto il lavoro, ed è anche il rischio.

---

*Decisione del 2026-06-15. Rivedere solo se: (a) domanda massiccia e
ricorrente, (b) un modello add-on a consumo chiaramente sostenibile,
(c) qualità riconoscimento italiano validata su pilota reale.*
