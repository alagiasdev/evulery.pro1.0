# Tracker promo apertura (primi 15) — istruzioni d'uso

> Uso interno. I 15 ristoratori che aderiscono alla promo apertura entro il 30 giugno 2026.
> Verso il cliente NON si parla mai di "programma" o "early adopter": e' solo "offerta promozionale".

File: [`early-adopter-tracker.csv`](early-adopter-tracker.csv)

## Come aprirlo

1. Doppio click sul file → si apre in Excel/LibreOffice
2. Se Excel mostra una colonna sola con virgole: `Dati → Testo in colonne → Delimitato → Virgola → Fine`
3. Salva come `.xlsx` per mantenere formattazioni (colori, larghezza colonne)

## Colonne — cosa va dove

| Colonna | Cosa contiene |
|---|---|
| **#** | Numero progressivo 1-15 (posto del programma) |
| **Ristorante** | Nome attività |
| **Città** | Per geo-tracking della pipeline |
| **Referente** | Persona di contatto (di solito il titolare) |
| **Email** / **Telefono** | Contatti diretti |
| **Reseller** | "—" se diretto, altrimenti nome reseller |
| **Data iscrizione** | Quando ha firmato il regolamento + pagato setup |
| **Setup €249 incassato** | SI/NO + data incasso |
| **Modalità setup** | Bonifico / Carta / Cash |
| **Data scadenza trial** | = Data iscrizione + 91 giorni (calcolata in Excel con `=A1+91`) |
| **Giorno trial corrente** | Calcolato in Excel con `=OGGI()-Data_iscrizione` |
| **Day-1 / 60 / 75 / 85 / 89** | ✅ se follow-up fatto, ⏳ se da fare, ❌ se saltato |
| **Decisione finale** | Continua Enterprise / Downgrade Professional / Downgrade Starter / Esce |
| **Piano post-trial** | Pricing definitivo (€129/79/49 o annuale) |
| **Modalità pagamento post-trial** | Mensile / Semestrale -6% (€729) / Annuale -17% (€1.290) |
| **Lock pricing €129 fino** | Data iscrizione + 3 anni (`=A1+1095` giorni) |
| **Note operative** | Tutto quello che ti serve ricordare |

## Formula utili (copia-incolla in Excel)

Per la cella "Data scadenza trial" (assumendo data iscrizione in colonna H, riga 2):
```
=H2+91
```

Per "Giorno trial corrente":
```
=SE(H2="";"";OGGI()-H2)
```

Per "Lock pricing €129 fino":
```
=H2+1095
```

## Formattazione condizionale consigliata

- **Giorno trial > 60**: cella ambra (avvicinarsi decisione)
- **Giorno trial > 85**: cella rossa (urgente, decidere a giorni)
- **Decisione finale = Esce**: riga grigia opaca
- **Decisione finale = Continua Enterprise**: riga verde

## Cron mentale dei follow-up

Quando un cliente raggiunge il **giorno trial**:

| Giorno | Azione | Email da inviare |
|---|---|---|
| 1 | Benvenuto + link al regolamento | `email-day-01-benvenuto.html` |
| 60 | Check-in: "ti piace?" + raccolta feedback | `email-day-60-checkin.html` |
| 75 | Prep decisione: "mancano 15gg, ti spiego le opzioni" | `email-day-75-prep-decisione.html` |
| 85 | Opzioni concrete: "questi sono i piani e i prezzi" | `email-day-85-opzioni.html` |
| 89 | Ultima call: "domani scade, decidi ora" | `email-day-89-ultima-call.html` |

Vedi cartella `sales/email-templates-early-adopter/` per i template pronti.

## Migrazione a sistema admin

Se arrivi a **15 clienti pieni e vuoi continuare** (programma esteso, secondi 15 posti, ecc.), conviene fare il modulo admin dedicato in dashboard (~3-4h dev). Per i primi 15 lo spreadsheet basta.
