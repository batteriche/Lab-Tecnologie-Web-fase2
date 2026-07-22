-- =====================================================================
-- TECH4LESS — Fase 2 (LLM-Assisted Redevelopment)
-- Schema MySQL — 22 tabelle
-- Modello users - groups - services (requisito obbligatorio) + dominio e-commerce
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Rieseguibile in sicurezza: droppa tutto prima di ricreare (ordine inverso alle dipendenze)
DROP TABLE IF EXISTS activity_log;
DROP TABLE IF EXISTS faqs;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS wishlist_items;
DROP TABLE IF EXISTS wishlists;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS order_status_history;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS coupons;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS carts;
DROP TABLE IF EXISTS product_specs;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS brands;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS addresses;
DROP TABLE IF EXISTS services_has_groups;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS users_has_groups;
DROP TABLE IF EXISTS `groups`;
DROP TABLE IF EXISTS users;

-- ---------------------------------------------------------------------
-- 1) NUCLEO USERS - GROUPS - SERVICES
-- ---------------------------------------------------------------------

CREATE TABLE users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(50)  NOT NULL UNIQUE,
    email           VARCHAR(150) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    nome            VARCHAR(80)  NOT NULL,
    cognome         VARCHAR(80)  NOT NULL,
    telefono        VARCHAR(30)  NULL,
    stato           ENUM('attivo','sospeso','eliminato') NOT NULL DEFAULT 'attivo',
    data_registrazione DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_accesso  DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE `groups` (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(50) NOT NULL UNIQUE,   -- es. 'clienti', 'admin', 'magazzinieri'
    descrizione     VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE users_has_groups (
    users_id        INT NOT NULL,
    groups_id       INT NOT NULL,
    PRIMARY KEY (users_id, groups_id),
    FOREIGN KEY (users_id)  REFERENCES users(id)  ON DELETE CASCADE,
    FOREIGN KEY (groups_id) REFERENCES `groups`(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- "services" = funzionalità/permessi applicativi (es. 'gestione_catalogo', 'gestione_ordini', 'gestione_utenti')
CREATE TABLE services (
    username        VARCHAR(50) PRIMARY KEY,  -- nome tecnico del servizio/permesso
    descrizione     VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE services_has_groups (
    services_username VARCHAR(50) NOT NULL,
    groups_id          INT NOT NULL,
    PRIMARY KEY (services_username, groups_id),
    FOREIGN KEY (services_username) REFERENCES services(username) ON DELETE CASCADE,
    FOREIGN KEY (groups_id)         REFERENCES `groups`(id)          ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2) INDIRIZZI (spedizione/fatturazione)
-- ---------------------------------------------------------------------

CREATE TABLE addresses (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    users_id        INT NOT NULL,
    etichetta       VARCHAR(50) NOT NULL DEFAULT 'Casa',
    via             VARCHAR(150) NOT NULL,
    citta           VARCHAR(100) NOT NULL,
    provincia       VARCHAR(2)  NOT NULL,
    cap             VARCHAR(10) NOT NULL,
    nazione         VARCHAR(60) NOT NULL DEFAULT 'Italia',
    predefinito     TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (users_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3) CATALOGO
-- ---------------------------------------------------------------------

CREATE TABLE categories (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(80) NOT NULL,
    slug            VARCHAR(80) NOT NULL UNIQUE,
    parent_id       INT NULL,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE brands (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE products (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    categories_id   INT NOT NULL,
    brands_id       INT NULL,
    nome            VARCHAR(150) NOT NULL,
    slug            VARCHAR(150) NOT NULL UNIQUE,
    descrizione     TEXT NULL,
    prezzo          DECIMAL(10,2) NOT NULL,
    prezzo_scontato DECIMAL(10,2) NULL,
    condizione      ENUM('nuovo','ricondizionato') NOT NULL DEFAULT 'nuovo',
    giacenza        INT NOT NULL DEFAULT 0,
    garanzia_mesi   INT NOT NULL DEFAULT 24,
    attivo          TINYINT(1) NOT NULL DEFAULT 1,
    data_creazione  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categories_id) REFERENCES categories(id),
    FOREIGN KEY (brands_id)     REFERENCES brands(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE product_images (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    products_id     INT NOT NULL,
    percorso        VARCHAR(255) NOT NULL,
    ordine          INT NOT NULL DEFAULT 0,
    FOREIGN KEY (products_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- specifiche tecniche chiave/valore (scheda tecnica reale, requisito Fase 1 confermato)
CREATE TABLE product_specs (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    products_id     INT NOT NULL,
    chiave          VARCHAR(80) NOT NULL,
    valore          VARCHAR(150) NOT NULL,
    FOREIGN KEY (products_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4) CARRELLO
-- ---------------------------------------------------------------------

CREATE TABLE carts (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    users_id        INT NULL,              -- NULL = carrello ospite legato a session_token
    session_token   VARCHAR(64) NULL,
    data_creazione  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (users_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cart_items (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    carts_id        INT NOT NULL,
    products_id     INT NOT NULL,
    quantita        INT NOT NULL DEFAULT 1,
    prezzo_unitario DECIMAL(10,2) NOT NULL,  -- snapshot prezzo al momento dell'aggiunta
    FOREIGN KEY (carts_id)    REFERENCES carts(id)    ON DELETE CASCADE,
    FOREIGN KEY (products_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5) COUPON / SCONTI (feature nuova rispetto a Fase 1)
-- ---------------------------------------------------------------------

CREATE TABLE coupons (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    codice          VARCHAR(30) NOT NULL UNIQUE,
    tipo            ENUM('percentuale','fisso') NOT NULL,
    valore          DECIMAL(10,2) NOT NULL,
    data_inizio     DATE NOT NULL,
    data_fine       DATE NOT NULL,
    utilizzo_massimo INT NULL,
    utilizzi_correnti INT NOT NULL DEFAULT 0,
    attivo          TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6) ORDINI
-- ---------------------------------------------------------------------

CREATE TABLE orders (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    users_id        INT NOT NULL,
    addresses_id    INT NOT NULL,
    coupons_id      INT NULL,
    numero_ordine   VARCHAR(20) NOT NULL UNIQUE,
    subtotale       DECIMAL(10,2) NOT NULL,
    sconto          DECIMAL(10,2) NOT NULL DEFAULT 0,
    totale          DECIMAL(10,2) NOT NULL,
    stato           ENUM('in_attesa','pagato','spedito','consegnato','annullato') NOT NULL DEFAULT 'in_attesa',
    data_ordine     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (users_id)     REFERENCES users(id),
    FOREIGN KEY (addresses_id) REFERENCES addresses(id),
    FOREIGN KEY (coupons_id)   REFERENCES coupons(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE order_items (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    orders_id       INT NOT NULL,
    products_id     INT NULL,
    nome_prodotto   VARCHAR(150) NOT NULL,   -- snapshot, sopravvive a modifiche/eliminazione prodotto
    quantita        INT NOT NULL,
    prezzo_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (orders_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (products_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- storico stati (tracciamento spedizione, feature admin avanzata)
CREATE TABLE order_status_history (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    orders_id       INT NOT NULL,
    stato           ENUM('in_attesa','pagato','spedito','consegnato','annullato') NOT NULL,
    nota            VARCHAR(255) NULL,
    users_id        INT NULL,  -- admin che ha effettuato il cambio
    data_cambio     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (orders_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (users_id)  REFERENCES users(id)  ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7) RECENSIONI E WISHLIST (feature nuove rispetto a Fase 1)
-- ---------------------------------------------------------------------

CREATE TABLE reviews (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    products_id     INT NOT NULL,
    users_id        INT NOT NULL,
    voto            TINYINT NOT NULL CHECK (voto BETWEEN 1 AND 5),
    testo           TEXT NULL,
    approvata       TINYINT(1) NOT NULL DEFAULT 0,   -- moderazione admin
    data_creazione  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (products_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (users_id)    REFERENCES users(id)    ON DELETE CASCADE,
    UNIQUE KEY uniq_review (products_id, users_id)
) ENGINE=InnoDB;

CREATE TABLE wishlists (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    users_id        INT NOT NULL UNIQUE,
    FOREIGN KEY (users_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE wishlist_items (
    wishlists_id    INT NOT NULL,
    products_id     INT NOT NULL,
    data_aggiunta   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (wishlists_id, products_id),
    FOREIGN KEY (wishlists_id) REFERENCES wishlists(id) ON DELETE CASCADE,
    FOREIGN KEY (products_id)  REFERENCES products(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 8) MESSAGGI DI CONTATTO (già presenti in Fase 1, confermati)
-- ---------------------------------------------------------------------

CREATE TABLE contact_messages (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NOT NULL,
    oggetto         VARCHAR(150) NOT NULL,
    testo           TEXT NOT NULL,
    stato           ENUM('nuovo','letto','risposto','archiviato') NOT NULL DEFAULT 'nuovo',
    data_invio      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 9) FAQ (gestibili da admin, non hardcoded — miglioramento vs Fase 1)
-- ---------------------------------------------------------------------

CREATE TABLE faqs (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    domanda         VARCHAR(255) NOT NULL,
    risposta        TEXT NOT NULL,
    ordine          INT NOT NULL DEFAULT 0,
    pubblicata      TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 10) ACTIVITY LOG (auditing admin — feature nuova, sicurezza/robustezza)
-- ---------------------------------------------------------------------

CREATE TABLE activity_log (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    users_id        INT NULL,
    azione          VARCHAR(100) NOT NULL,   -- es. 'prodotto_creato', 'ordine_stato_modificato'
    dettagli        VARCHAR(255) NULL,
    ip_address      VARCHAR(45) NULL,
    data_evento     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (users_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- Conteggio tabelle: 22 (>= 14 richieste)
-- users, groups, users_has_groups, services, services_has_groups,
-- addresses, categories, brands, products, product_images, product_specs,
-- carts, cart_items, coupons, orders, order_items, order_status_history,
-- reviews, wishlists, wishlist_items, contact_messages, faqs, activity_log
-- =====================================================================

-- Seed minimo per bootstrap (gruppi e servizi base)
INSERT INTO `groups` (nome, descrizione) VALUES
('clienti', 'Utenti registrati che acquistano sul sito'),
('admin', 'Amministratori con accesso completo al backend'),
('magazzinieri', 'Gestione catalogo e ordini, no gestione utenti');

INSERT INTO services (username, descrizione) VALUES
('gestione_catalogo', 'CRUD su prodotti, categorie, brand'),
('gestione_ordini', 'Visualizzazione e aggiornamento stato ordini'),
('gestione_utenti', 'CRUD su utenti e gruppi'),
('gestione_contenuti', 'Gestione FAQ e messaggi di contatto'),
('acquisto', 'Carrello, checkout, wishlist, recensioni');

INSERT INTO services_has_groups (services_username, groups_id) VALUES
('gestione_catalogo', 2), ('gestione_catalogo', 3),
('gestione_ordini', 2), ('gestione_ordini', 3),
('gestione_utenti', 2),
('gestione_contenuti', 2),
('acquisto', 1);
