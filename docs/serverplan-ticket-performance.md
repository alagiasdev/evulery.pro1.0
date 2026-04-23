# Ticket Serverplan — Tuning MariaDB + Verifica OPcache

Testo pronto da incollare nell'area ticket di Serverplan (o via email al supporto). Modificare solo i punti marcati `<<<>>>` con i dati effettivi del tuo account.

---

## Oggetto

Richiesta tuning MariaDB (innodb_buffer_pool_size) + verifica OPcache su VPS Professional

---

## Corpo del ticket

Buongiorno,

vi scrivo per richiedere due interventi di tuning sul mio VPS Professional, necessari per migliorare le performance di un'applicazione PHP multi-tenant che sta crescendo di utenza.

**Dati account**
- Username cPanel: `<<<INSERIRE USERNAME>>>` (se non siete in grado di recuperarlo dal ticket)
- Dominio principale: `dash.evulery.it`
- Pacchetto: VPS Professional (6 core, 12GB RAM, 500GB SSD, CentOS 7 + WHM/cPanel)

---

### 1. Tuning MariaDB — `innodb_buffer_pool_size`

Attualmente il buffer pool di MariaDB è impostato al valore di default (1GB). Considerando che il VPS ha 12GB di RAM disponibili e il carico database è crescente, chiederei di portarlo a 6GB.

Modifiche da applicare a `/etc/my.cnf` (sezione `[mysqld]`):

```
innodb_buffer_pool_size = 6G
innodb_log_file_size = 512M
```

Successivamente è necessario il riavvio del servizio MariaDB:
```
systemctl restart mariadb
```

**Nota**: durante il riavvio ci saranno pochi secondi di downtime per il database. Se preferite una finestra di manutenzione coordinata, va benissimo, ditemi voi orario e data.

**Motivazione**: l'applicazione è un SaaS di prenotazioni ristorante, le query principali (listing prenotazioni, aggregazioni statistiche 30gg, ricerca clienti) beneficiano direttamente di un buffer pool ampio. Con 1GB MariaDB evette frequentemente pagine a disco penalizzando i tempi di risposta.

---

### 2. Verifica stato OPcache su PHP handler

Chiedo cortesemente di confermare che **OPcache sia abilitato** sul gestore PHP utilizzato dal mio account (PHP 8.1 o superiore).

In particolare vorrei verificare:
- `opcache.enable = 1`
- `opcache.memory_consumption` almeno 128 MB
- `opcache.max_accelerated_files` almeno 10000
- `opcache.validate_timestamps = 1`
- `opcache.revalidate_freq` tra 2 e 60 secondi

Se OPcache non fosse attivo o i valori fossero sotto questi minimi, vi chiederei di abilitarlo/ritoccarlo. Su un'applicazione PHP come la mia, OPcache è il singolo maggior boost di performance disponibile senza modificare il codice.

---

Resto a disposizione per finestre di manutenzione concordate se necessario.

Grazie del supporto,
`<<<INSERIRE NOME>>>`
