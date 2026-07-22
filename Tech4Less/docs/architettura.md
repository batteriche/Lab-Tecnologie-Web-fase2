# TECH4LESS — Fase 2: Architettura e Struttura Progetto

## Vincoli rispettati
Stack invariato: PHP puro, MySQL, HTML/CSS, JS vanilla, `template2.inc.php`. Nessun framework.
Il "miglioramento architetturale" è ottenuto con un **front controller custom** e organizzazione a livelli, senza introdurre dipendenze esterne.

## Struttura cartelle

```
tech4less/
├── public/                      ← unico document root esposto al web server
│   ├── index.php                ← front controller: instrada tutte le richieste
│   ├── admin/
│   │   └── index.php            ← front controller separato per il backend
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── img/
│   └── .htaccess                ← rewrite verso index.php
│
├── app/
│   ├── config/
│   │   ├── config.php           ← credenziali DB, costanti ambiente
│   │   └── routes.php           ← mappa rotta → controller/azione
│   ├── core/
│   │   ├── Router.php           ← risolve URL → controller
│   │   ├── Database.php         ← wrapper PDO singleton, query preparate
│   │   ├── Auth.php             ← login, sessione, hashing password, permessi
│   │   ├── Controller.php       ← classe base (render template, redirect, input)
│   │   └── Validator.php        ← validazione input centralizzata
│   ├── controllers/
│   │   ├── HomeController.php
│   │   ├── CatalogoController.php
│   │   ├── CarrelloController.php
│   │   ├── CheckoutController.php
│   │   ├── AccountController.php   ← registrazione/login/profilo/indirizzi/wishlist
│   │   ├── RecensioniController.php
│   │   └── admin/
│   │       ├── DashboardController.php
│   │       ├── ProdottiController.php
│   │       ├── OrdiniController.php
│   │       ├── UtentiController.php
│   │       ├── MessaggiController.php
│   │       └── LogController.php
│   ├── models/
│   │   ├── User.php  Product.php  Category.php  Order.php  Cart.php
│   │   ├── Coupon.php  Review.php  Wishlist.php  ContactMessage.php  Faq.php
│   │   └── (ogni model = query verso 1 tabella/aggregato, no ORM esterno)
│   ├── services/
│   │   ├── CartService.php      ← logica carrello (merge ospite→utente, totali)
│   │   ├── OrderService.php     ← creazione ordine, applicazione coupon, storico stati
│   │   └── PermissionService.php← verifica users→groups→services
│   └── views/
│       ├── template2.inc.php    ← motore di template fornito dal corso (invariato)
│       ├── layout/
│       │   ├── header.tpl.php
│       │   └── footer.tpl.php
│       ├── frontend/
│       │   ├── home.tpl.php  catalogo.tpl.php  prodotto.tpl.php
│       │   ├── carrello.tpl.php  checkout.tpl.php
│       │   ├── account.tpl.php  wishlist.tpl.php
│       │   ├── faq.tpl.php  chi-siamo.tpl.php
│       └── admin/
│           ├── dashboard.tpl.php  prodotti.tpl.php  ordini.tpl.php
│           ├── utenti.tpl.php  messaggi.tpl.php  log.tpl.php
│
├── database/
│   ├── schema.sql                ← script già generato
│   └── seed_demo.sql             ← dati demo per test/esame
│
└── docs/                         ← per i deliverable richiesti in Sezione 8.2
    ├── prompt_log.md
    ├── development_diary.md
    └── er_diagram.png
```

## Come si concilia con `template2.inc.php`
- I **controller** (`app/controllers/`) contengono solo logica applicativa: leggono l'input, chiamano i **model/service**, e passano un array di dati alla vista.
- Le **view** (`.tpl.php`) usano esclusivamente la sintassi a placeholder di `template2.inc.php` (`<[variabile]>`, `<[foreach]>...<[/foreach]>`), esattamente come nel layout Fase 1 — nessuna logica PHP nelle view.
- Il **Router** è l'unica novità strutturale: sostituisce il collegamento diretto file-per-pagina con una mappa `routes.php` (es. `catalogo/{categoria}` → `CatalogoController@index`), rendendo gli URL più puliti e centralizzando i controlli di permesso.

## Permessi (users – groups – services)
`PermissionService::can($userId, 'gestione_ordini')` interroga `users_has_groups` → `services_has_groups` per decidere se mostrare una sezione admin o rispondere 403. Sostituisce controlli sparsi con un unico punto di verifica.

## Feature aggiuntive rispetto alla Fase 1 (rispettando il vincolo "solo in direzione conservativa")
1. **Wishlist** utente (tabelle `wishlists`, `wishlist_items`)
2. **Recensioni prodotto** con moderazione admin (`reviews`)
3. **Coupon/sconti** applicabili in checkout (`coupons`)
4. **Storico stato ordine** tracciabile lato admin e cliente (`order_status_history`)
5. **FAQ gestibili da admin** invece che hardcoded in HTML
6. **Activity log** per audit azioni amministrative
7. **Ruolo intermedio "magazzinieri"** oltre a clienti/admin, per mostrare l'uso reale del modello a gruppi
8. **Carrello persistente** anche da ospite (session_token) con merge automatico al login

## Prossimi passi consigliati
1. Validare questo schema e questa struttura.
2. Generare `config.php`, `Database.php`, `Router.php`, `Auth.php` (nucleo).
3. Procedere per slice funzionale (home → catalogo → carrello → checkout → account → admin), documentando ogni prompt per il deliverable "prompt log" richiesto in Sezione 8.2.
