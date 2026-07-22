# Tech4Less

Repository del progetto di **Laboratorio di Tecnologie del Web** — AA 2025/2026
Autori: Alessia De Dominicis ([dedoale](https://github.com/dedoale)) e Riccardo D'Aviero ([Batteriche](https://github.com/Batteriche))

Tech4Less è un e-commerce di elettronica ricondizionata e nuova, sviluppato in **PHP puro (no framework)** con architettura **MVC custom**, pensato come progetto didattico per il corso di Tecnologie del Web.

---

## Indice

- [Stack tecnologico](#stack-tecnologico)
- [Architettura](#architettura)
- [Struttura delle cartelle](#struttura-delle-cartelle)
- [Modello dati](#modello-dati)
- [Sistema di permessi (users → groups → services)](#sistema-di-permessi-users--groups--services)
- [Routing](#routing)
- [Funzionalità principali](#funzionalità-principali)
- [Sicurezza](#sicurezza)
- [Installazione e avvio locale](#installazione-e-avvio-locale)
- [Configurazione](#configurazione)

---

## Stack tecnologico

| Livello | Tecnologia |
|---|---|
| Linguaggio backend | PHP 8.2+ |
| Database | MySQL / MariaDB (PDO, query preparate) |
| Frontend | HTML, CSS, JavaScript vanilla |
| Template engine | Motore custom `template2.inc.php` (sintassi `<[var]>` / `<[foreach]>...<[/foreach]>`) |
| Routing | Router custom (nessun framework, nessuna dipendenza esterna) |
| Autenticazione | Sessioni PHP native + `password_hash`/`password_verify` |

Non sono utilizzati framework (Laravel, Symfony, ecc.) né Composer: è una scelta didattica del corso, per mostrare l'implementazione "a mano" dei pattern MVC, routing, ORM minimale e sicurezza.

---

## Architettura

Il progetto segue un pattern **MVC** con un ulteriore livello di **Service** per la logica di business più complessa (carrello, ordini, permessi), in modo da mantenere i controller sottili:

```
Richiesta HTTP
      │
      ▼
public/index.php  (front controller pubblico)
public/admin/index.php  (front controller area admin, richiede login)
      │
      ▼
   Router  →  Controller  →  Service (opzionale)  →  Model  →  Database (PDO)
                   │
                   ▼
              View (.html + template2.inc.php)
```

- **Front controller**: due punti di ingresso separati (`public/index.php` per il sito pubblico, `public/admin/index.php` per il backend), entrambi instradano tramite lo stesso `Router` e lo stesso file `routes.php`.
- **Router**: mappa pattern URL (anche con parametri tipo `{slug}`) a `Controller@metodo`.
- **Controller**: gestiscono input, validazione, CSRF, chiamano model/service e fanno il render della vista. La classe base `Controller` centralizza rendering, redirect, lettura input e token CSRF; `AdminController` (che estende `Controller`) aggiunge sidebar, topbar e controllo permessi per l'area amministrativa.
- **Model**: una classe per tabella/aggregato (es. `Product`, `Order`, `User`), incapsula le query SQL preparate.
- **Service**: logica applicativa che coinvolge più model o transazioni (es. `CartService`, `OrderService`, `PermissionService`).
- **View**: file `.html` puri (nessun PHP embedded), popolati tramite il motore di template `template2.inc.php` con placeholder `<[chiave]>` e blocchi ripetuti `<[foreach]>...<[/foreach]>`.
- **Autoload**: autoloader custom (`app/core/autoload.php`) che mappa il nome classe alla cartella corretta (`core/`, `controllers/`, `models/`, `services/`) senza bisogno di Composer.

---

## Struttura delle cartelle

```
Tech4Less/
├── app/
│   ├── config/
│   │   ├── config.php        # costanti globali (DB, ambiente, sessione)
│   │   └── routes.php        # definizione di tutte le rotte
│   ├── controllers/
│   │   ├── HomeController.php
│   │   ├── CatalogoController.php
│   │   ├── CarrelloController.php
│   │   ├── CheckoutController.php
│   │   ├── AccountController.php
│   │   └── admin/
│   │       ├── DashboardController.php
│   │       ├── ProdottiController.php
│   │       ├── OrdiniController.php
│   │       ├── UtentiController.php
│   │       ├── MessaggiController.php
│   │       └── LogController.php
│   ├── core/
│   │   ├── autoload.php         # autoloader custom
│   │   ├── Router.php           # routing
│   │   ├── Database.php         # singleton PDO
│   │   ├── Controller.php       # controller base (render, redirect, CSRF...)
│   │   ├── AdminController.php  # controller base area admin
│   │   ├── Auth.php             # login/logout/sessione
│   │   ├── Validator.php        # validazione input fluente
│   │   ├── PermissionService.php# verifica permessi users→groups→services
│   │   └── ImageUploader.php    # upload sicuro immagini prodotto
│   ├── models/
│   │   ├── User.php, Address.php, Category.php, Product.php
│   │   ├── Cart.php, Coupon.php, Order.php
│   │   ├── ContactMessage.php, Wishlist.php, ActivityLog.php
│   ├── services/
│   │   ├── CartService.php      # logica carrello (ospite + utente)
│   │   ├── OrderService.php     # creazione ordine transazionale
│   │   └── PermissionService.php
│   └── views/
│       ├── frontend/   # home, catalogo, prodotto, carrello, checkout, account...
│       ├── admin/      # dashboard, prodotti, ordini, utenti, messaggi, log
│       ├── layout/     # header/footer condivisi frontend
│       └── template2.inc.php  # motore di template
├── database/
│   ├── schema.sql       # struttura completa (22 tabelle)
│   └── seed_demo.sql    # dati dimostrativi
└── public/
    ├── index.php         # front controller pubblico
    ├── admin/index.php   # front controller area admin
    ├── .htaccess          # rewrite verso i front controller
    └── assets/            # css, js, immagini
```

---

## Modello dati

Lo schema (`database/schema.sql`) definisce **22 tabelle**, organizzate in gruppi funzionali:

1. **Nucleo permessi**: `users`, `groups`, `users_has_groups`, `services`, `services_has_groups`
2. **Anagrafiche**: `addresses`
3. **Catalogo**: `categories`, `brands`, `products`, `product_images`, `product_specs`
4. **Carrello**: `carts`, `cart_items`
5. **Sconti**: `coupons`
6. **Ordini**: `orders`, `order_items`, `order_status_history`
7. **Interazione utente**: `reviews`, `wishlists`, `wishlist_items`
8. **Contenuti/contatti**: `contact_messages`, `faqs`
9. **Auditing**: `activity_log`

Punti di rilievo del design:

- Tutte le foreign key sono esplicite, con `ON DELETE CASCADE`/`SET NULL` scelti in base alla semantica (es. cancellare un utente cancella i suoi indirizzi, ma non gli ordini che restano storicizzati).
- `order_items` salva uno **snapshot** di nome prodotto e prezzo al momento dell'acquisto, così l'ordine resta consistente anche se il prodotto viene modificato o eliminato in seguito.
- `carts` supporta sia utenti loggati (`users_id`) sia ospiti (`session_token`), con logica di fusione al login (vedi `CartService::fondiCarrelloOspiteConUtente`).
- `coupons` gestisce sconti percentuali o fissi, con validità temporale e limite di utilizzo.
- Lo script è **rieseguibile in sicurezza**: droppa tutte le tabelle nell'ordine inverso delle dipendenze prima di ricrearle.

---

## Sistema di permessi (users → groups → services)

Il progetto implementa un modello di autorizzazione a tre livelli, requisito centrale del corso:

- **users**: account con stato (`attivo`, `sospeso`, `eliminato`).
- **groups**: ruoli applicativi (es. `clienti`, `admin`, `magazzinieri`).
- **services**: funzionalità/permessi (es. `gestione_catalogo`, `gestione_ordini`, `gestione_utenti`, `gestione_contenuti`, `acquisto`).

Le tabelle ponte `users_has_groups` e `services_has_groups` collegano utenti a gruppi e gruppi a servizi. `PermissionService::can($userId, $servizio)` verifica con una singola query JOIN se l'utente appartiene a un gruppo abilitato al servizio richiesto; `PermissionService::require($servizio)` blocca la richiesta con una risposta 403 in caso contrario. Ogni `AdminController` invoca `richiediPermesso()` a inizio azione, così ogni sezione del backend (catalogo, ordini, utenti, contenuti) può avere permessi indipendenti senza controlli sparsi nei singoli controller.

---

## Routing

Tutte le rotte sono definite in `app/config/routes.php` e caricate da entrambi i front controller. Il `Router` supporta metodi GET/POST e parametri nominati nel pattern (es. `/prodotto/{slug}`).

**Frontend pubblico**: home, catalogo (con filtro per categoria), dettaglio prodotto, carrello (visualizza/aggiungi/rimuovi/aggiorna), checkout (con conferma e riepilogo ordine), account (login, registrazione, logout, profilo, storico ordini, wishlist), recensioni, pagine statiche (chi siamo, FAQ, contatti).

**Backend amministrativo** (montato sotto `/admin`, con richiesta di login applicata a livello di front controller): dashboard, gestione prodotti (CRUD completo), gestione ordini (elenco, dettaglio, cambio stato), gestione utenti (elenco, assegnazione gruppi), gestione messaggi di contatto, log attività.

---

## Funzionalità principali

- **Catalogo prodotti** con categorie, brand, specifiche tecniche chiave/valore, immagini multiple e prodotti nuovi/ricondizionati.
- **Carrello** persistente sia per utenti loggati sia per ospiti (via token di sessione), con fusione automatica al login.
- **Checkout** transazionale: crea l'ordine, applica eventuali coupon, decrementa la giacenza prodotto per riga, storicizza lo stato iniziale e svuota il carrello — tutto all'interno di una singola transazione DB (rollback automatico in caso di errore, es. giacenza insufficiente).
- **Coupon/sconti** a percentuale o importo fisso, con periodo di validità e limite di utilizzi.
- **Account utente**: registrazione, login, profilo, storico ordini, wishlist, recensioni sui prodotti (soggette a moderazione admin).
- **Area amministrativa** con dashboard, CRUD prodotti (incluso upload immagini), gestione ordini e cambio stato, gestione utenti/gruppi, gestione messaggi di contatto, log attività per auditing.
- **Motore di template proprietario** (`template2.inc.php`): le view sono file `.html` senza logica applicativa, con placeholder sostituiti dai controller.

---

## Sicurezza

- **Query preparate ovunque**: tutte le interazioni con il database passano dal wrapper `Database::query()` su PDO, con `PDO::ATTR_EMULATE_PREPARES` disattivato.
- **Password**: mai in chiaro, sempre `password_hash()`/`password_verify()`.
- **Sessioni**: cookie con `httponly` e `samesite=Lax`; `session_regenerate_id(true)` al login per mitigare session fixation.
- **CSRF**: token generato per sessione e verificato su ogni form POST tramite `Controller::verifyCsrf()`.
- **Upload immagini**: `ImageUploader` valida estensione, MIME reale del contenuto (non solo l'header dichiarato) e dimensione massima (5 MB); il nome del file salvato è generato casualmente per evitare path traversal o collisioni.
- **Permessi**: ogni azione dell'area admin richiede sia login sia appartenenza a un gruppo abilitato al servizio specifico (vedi sezione permessi).
- **Errori**: in produzione (`APP_ENV != 'dev'`) i dettagli di errore non sono mai esposti all'utente finale; gli errori di connessione al database sono loggati lato server e mostrati all'utente in forma generica.

---

## Installazione e avvio locale

### Requisiti

- PHP 8.2 o superiore (estensioni `pdo_mysql`, `mbstring`)
- MySQL o MariaDB
- Apache con `mod_rewrite` abilitato (per via del file `.htaccess` in `public/`)

### Passi

1. Clonare il repository:
   ```bash
   git clone https://github.com/<org>/Lab-Tecnologie-Web-fase2.git
   ```

2. Creare il database ed eseguire lo schema:
   ```bash
   mysql -u root -p -e "CREATE DATABASE tech4less"
   mysql -u root -p tech4less < Tech4Less/database/schema.sql
   mysql -u root -p tech4less < Tech4Less/database/seed_demo.sql
   ```

3. Configurare le credenziali del database in `Tech4Less/app/config/config.php` (vedi sezione [Configurazione](#configurazione)).

4. Puntare il document root del webserver alla cartella `Tech4Less/public/`, oppure — per test rapidi con il server integrato di PHP:
   ```bash
   cd Tech4Less/public
   php -S localhost:8000
   ```
   > Nota: il server integrato di PHP non applica `.htaccess`; per un test fedele alla configurazione di produzione è consigliato Apache con `mod_rewrite`.

5. Se l'app non gira nella root del dominio ma in una sottocartella (es. `http://localhost/tech4less`), aggiornare la costante `BASE_URL` in `config.php` di conseguenza.

---

## Configurazione

Tutte le costanti di configurazione si trovano in `Tech4Less/app/config/config.php`:

| Costante | Descrizione |
|---|---|
| `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_CHARSET` | Credenziali di connessione al database |
| `BASE_URL` | Prefisso URL dell'applicazione (vuoto se in root, es. `/tech4less` se in sottocartella) |
| `REMEMBER_ME_TTL` | Durata del cookie "ricordami" in secondi |
| `APP_ENV` | `dev` abilita la visualizzazione degli errori PHP; qualsiasi altro valore la disabilita per produzione |

> In questo progetto didattico le credenziali restano centralizzate nel file di configurazione per semplicità del corso; in un contesto di produzione andrebbero spostate in variabili d'ambiente e il file escluso dal controllo di versione.


## ER Diagram

```mermaid
erDiagram
    USERS ||--o{ USERS_HAS_GROUPS : "appartiene a"
    GROUPS ||--o{ USERS_HAS_GROUPS : "raggruppa"

erDiagram
    USERS ||--o{ USERS_HAS_GROUPS : "appartiene a"
    GROUPS ||--o{ USERS_HAS_GROUPS : "raggruppa"
    GROUPS ||--o{ SERVICES_HAS_GROUPS : "abilitato a"
    SERVICES ||--o{ SERVICES_HAS_GROUPS : "concesso a"

    USERS ||--o{ ADDRESSES : "possiede"
    USERS ||--o{ CARTS : "ha (opzionale)"
    USERS ||--o{ ORDERS : "effettua"
    USERS ||--o{ ORDER_STATUS_HISTORY : "aggiorna (admin, opzionale)"
    USERS ||--o{ REVIEWS : "scrive"
    USERS ||--o| WISHLISTS : "ha"
    USERS ||--o{ ACTIVITY_LOG : "genera (opzionale)"

    CATEGORIES ||--o{ CATEGORIES : "sotto-categoria di"
    CATEGORIES ||--o{ PRODUCTS : "classifica"
    BRANDS ||--o{ PRODUCTS : "produce (opzionale)"

    PRODUCTS ||--o{ PRODUCT_IMAGES : "ha"
    PRODUCTS ||--o{ PRODUCT_SPECS : "ha"
    PRODUCTS ||--o{ CART_ITEMS : "aggiunto in"
    PRODUCTS ||--o{ ORDER_ITEMS : "acquistato in (opzionale)"
    PRODUCTS ||--o{ REVIEWS : "recensito in"
    PRODUCTS ||--o{ WISHLIST_ITEMS : "salvato in"

    CARTS ||--o{ CART_ITEMS : "contiene"

    COUPONS ||--o{ ORDERS : "applicato a (opzionale)"

    ADDRESSES ||--o{ ORDERS : "spedisce a"

    ORDERS ||--o{ ORDER_ITEMS : "contiene"
    ORDERS ||--o{ ORDER_STATUS_HISTORY : "traccia"

    WISHLISTS ||--o{ WISHLIST_ITEMS : "contiene"

    USERS {
        int id PK
        varchar username UK
        varchar email UK
        varchar password_hash
        varchar nome
        varchar cognome
        varchar telefono
        enum stato
        datetime data_registrazione
        datetime ultimo_accesso
    }

    GROUPS {
        int id PK
        varchar nome UK
        varchar descrizione
    }

    USERS_HAS_GROUPS {
        int users_id PK, FK
        int groups_id PK, FK
    }

    SERVICES {
        varchar username PK
        varchar descrizione
    }

    SERVICES_HAS_GROUPS {
        varchar services_username PK, FK
        int groups_id PK, FK
    }

    ADDRESSES {
        int id PK
        int users_id FK
        varchar etichetta
        varchar via
        varchar citta
        varchar provincia
        varchar cap
        varchar nazione
        tinyint predefinito
    }

    CATEGORIES {
        int id PK
        varchar nome
        varchar slug UK
        int parent_id FK
    }

    BRANDS {
        int id PK
        varchar nome UK
    }

    PRODUCTS {
        int id PK
        int categories_id FK
        int brands_id FK
        varchar nome
        varchar slug UK
        text descrizione
        decimal prezzo
        decimal prezzo_scontato
        enum condizione
        int giacenza
        int garanzia_mesi
        tinyint attivo
        datetime data_creazione
    }

    PRODUCT_IMAGES {
        int id PK
        int products_id FK
        varchar percorso
        int ordine
    }

    PRODUCT_SPECS {
        int id PK
        int products_id FK
        varchar chiave
        varchar valore
    }

    CARTS {
        int id PK
        int users_id FK
        varchar session_token
        datetime data_creazione
    }

    CART_ITEMS {
        int id PK
        int carts_id FK
        int products_id FK
        int quantita
        decimal prezzo_unitario
    }

    COUPONS {
        int id PK
        varchar codice UK
        enum tipo
        decimal valore
        date data_inizio
        date data_fine
        int utilizzo_massimo
        int utilizzi_correnti
        tinyint attivo
    }

    ORDERS {
        int id PK
        int users_id FK
        int addresses_id FK
        int coupons_id FK
        varchar numero_ordine UK
        decimal subtotale
        decimal sconto
        decimal totale
        enum stato
        datetime data_ordine
    }

    ORDER_ITEMS {
        int id PK
        int orders_id FK
        int products_id FK
        varchar nome_prodotto
        int quantita
        decimal prezzo_unitario
    }

    ORDER_STATUS_HISTORY {
        int id PK
        int orders_id FK
        enum stato
        varchar nota
        int users_id FK
        datetime data_cambio
    }

    REVIEWS {
        int id PK
        int products_id FK
        int users_id FK
        tinyint voto
        text testo
        tinyint approvata
        datetime data_creazione
    }

    WISHLISTS {
        int id PK
        int users_id FK, UK
    }

    WISHLIST_ITEMS {
        int wishlists_id PK, FK
        int products_id PK, FK
        datetime data_aggiunta
    }

    CONTACT_MESSAGES {
        int id PK
        varchar nome
        varchar email
        varchar oggetto
        text testo
        enum stato
        datetime data_invio
    }

    FAQS {
        int id PK
        varchar domanda
        text risposta
        int ordine
        tinyint pubblicata
    }

    ACTIVITY_LOG {
        int id PK
        int users_id FK
        varchar azione
        varchar dettagli
        varchar ip_address
        datetime data_evento
    }
```
