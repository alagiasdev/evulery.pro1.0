PROGETTO: SISTEMA PRENOTAZIONI RISTORANTE MODELLO: SaaS EMBEDDABILE /
WHITE LABEL IBRIDO

============================================================ 1.
STRATEGIA GENERALE
============================================================

Architettura ibrida:

-   Backend centralizzato su: app.nomepiattaforma.com

-   Frontend prenotazione disponibile in 3 modalità:

    A)  Subdomain cliente (white label) prenota.ristorante.it

    B)  Embed sul sito cliente ristorante.it/prenota

    C)  Pagina hosted dalla piattaforma
        nomepiattaforma.com/nome-ristorante

Tutte le modalità puntano allo stesso tenant (ristorante).

============================================================ 2.
STRUTTURA MULTI-TENANT
============================================================

Ogni ristorante ha:

-   tenant_id
-   slug (es. trattoria-da-mario)
-   custom_domain (opzionale)

Routing: - Se dominio personalizzato → carico tenant tramite
custom_domain - Se dominio piattaforma → carico tenant tramite slug

============================================================ 3.
WIREFRAME FRONTEND PRENOTAZIONE
============================================================

  -----------------------------------------------------
  [ LOGO RISTORANTE ]

  Prenota un tavolo
  -----------------------------------------------------

Data [ 📅 22 Marzo 2026 ] Orario [ ⏰ 19:30 ▼ ] Persone [ 👥 2 ▼ ]

------------------------------------------------------------------------

Nome [_______________________] Cognome [_______________________]
Telefono [_______________________] Email [_______________________]

------------------------------------------------------------------------

[ Prenota Tavolo ]

-   Politica cancellazione

-   Eventuale caparra se richiesta

Se slot pieno:

❌ Posti non disponibili alle 19:30 👉 Disponibile alle 20:00 o 21:15
[20:00] [21:15]

============================================================ 4.
WIREFRAME BACKEND RISTORATORE
============================================================

  -----------------------------------------------------
  [ OGGI - 22 Marzo 2026 ]

  Coperti previsti: 48 / 60 Prenotazioni: 18
  -----------------------------------------------------

19:00 • Rossi Mario | 2 pax | Confermata • Bianchi Luca | 4 pax | In
attesa • Verdi Anna | 6 pax | Caparra versata

------------------------------------------------------------------------

Colori stato: - Confermata - In attesa - Arrivato - No-show - Annullata

============================================================ 5.
DETTAGLIO PRENOTAZIONE
============================================================

Rossi Mario Telefono: 3331234567 Email: rossi@email.it

Data: 22 Marzo 2026 Orario: 19:30 Persone: 2

Stato: Confermata Caparra: Non richiesta

[ Segna come ARRIVATO ] [ Segna come NO-SHOW ] [ Modifica ] [ Annulla ]

Note interne: [____________________________]

Storico cliente: - 14 Feb 2026 – Presenti - 22 Dic 2025 – No-show

============================================================ 6. LOGICA
SLOT E CAPIENZA
============================================================

Impostazioni ristorante:

Durata tavolo: 90 minuti Step orari: 30 minuti

Coperti max per fascia: 19:00 → 20 19:30 → 25 20:00 → 30 21:30 → 18

Il sistema calcola disponibilità automaticamente.

============================================================ 7. GESTIONE
DOMINIO PERSONALIZZATO
============================================================

Flusso:

1)  Cliente inserisce dominio (es. prenota.ristorante.it)
2)  Sistema genera record CNAME
3)  Stato:
    -   In attesa DNS
    -   Dominio collegato
4)  Generazione SSL automatica (Let’s Encrypt o Cloudflare)

============================================================ 8.
ONBOARDING CLIENTE
============================================================

1)  Creazione tenant
2)  Generazione link: nomepiattaforma.com/nome-ristorante
3)  Configurazione capienza
4)  Attivazione sistema
5)  Opzionale: collegamento dominio personalizzato

============================================================ 9.
MONETIZZAZIONE
============================================================

Possibile struttura pricing:

-   49€/mese Base (prenotazioni)
-   79€/mese con caparra online
-   Setup iniziale opzionale

============================================================ 10.
VANTAGGI STRATEGICI
============================================================

-   Un solo codice
-   Un solo deploy
-   Aggiornamenti centralizzati
-   White label opzionale
-   Scalabile
-   Integrabile con CRM e marketing futuro

FINE DOCUMENTO
