-- =====================================================================
-- TECH4LESS — Seed dati (VERSIONE IDEMPOTENTE)
-- Rieseguibile senza resettare lo schema: non duplica i dati demo
-- già presenti e non tocca i dati custom (es. nuovi utenti creati
-- durante l'uso dell'app).
--
-- NB: groups, services, services_has_groups sono già popolati dal
-- bootstrap in fondo a schema.sql: NON vengono reinseriti qui.
-- Mapping gruppi bootstrap: 1=clienti, 2=admin, 3=magazzinieri
-- =====================================================================

USE tech4less;

-- ---------------------------------------------------------------------
-- USERS
-- username/email sono UNIQUE -> INSERT IGNORE basta
-- ---------------------------------------------------------------------
INSERT IGNORE INTO users (username, email, password_hash, nome, cognome, telefono, stato) VALUES
('r.daviero', 'admin@email.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Riccardo', 'D''Aviero', NULL, 'attivo'),
('a.dedominicis', 'admin2@email.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alessia', 'De Dominicis', NULL, 'attivo'),
('a.pierantonio', 'cliente@email.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alfonso', 'Pierantonio', NULL, 'attivo');

-- users_has_groups ha PRIMARY KEY (users_id, groups_id) -> INSERT IGNORE basta
-- Nota: qui uso gli id 1,2,3 assumendo che i 3 utenti demo siano i primi
-- inseriti. Se questa assunzione non regge più nel tuo ambiente, va
-- riscritta con una SELECT su username invece dell'id fisso.
INSERT IGNORE INTO users_has_groups (users_id, groups_id) VALUES
(1, 2),
(2, 2),
(3, 1);

-- ---------------------------------------------------------------------
-- INDIRIZZI
-- Nessun vincolo UNIQUE -> guard manuale con NOT EXISTS
-- ---------------------------------------------------------------------
INSERT INTO addresses (users_id, etichetta, via, citta, provincia, cap, nazione, predefinito)
SELECT 3, 'Casa', 'Via Roma 1', 'L''Aquila', 'AQ', '67100', 'Italia', 1
WHERE NOT EXISTS (
    SELECT 1 FROM addresses WHERE users_id = 3 AND via = 'Via Roma 1'
);

-- ---------------------------------------------------------------------
-- CATALOGO
-- slug/nome sono UNIQUE -> INSERT IGNORE basta
-- ---------------------------------------------------------------------
INSERT IGNORE INTO categories (nome, slug) VALUES
('Schede video', 'schede-video'),
('Processori', 'processori'),
('Memorie RAM', 'memorie-ram'),
('Storage (SSD/HDD)', 'storage'),
('Periferiche', 'periferiche');

INSERT IGNORE INTO brands (nome) VALUES
('ASUS'),
('AMD'),
('Corsair'),
('Kingston'),
('Logitech');

-- products.slug è UNIQUE -> INSERT IGNORE basta
INSERT IGNORE INTO products (categories_id, brands_id, nome, slug, descrizione, prezzo, prezzo_scontato, condizione, giacenza, garanzia_mesi, attivo) VALUES
(1, 1, 'RTX 5070 Super 12GB', 'rtx-5070-super-12gb', 'Scheda video perfetta per il 1440p ad alti refresh rate.', 799.99, 749.99, 'nuovo', 12, 24, 1),
(3, 3, 'Corsair Vengeance 32GB DDR5', 'corsair-vengeance-32gb-ddr5', 'Kit 2x16GB 6000MHz C36.', 399.99, NULL, 'nuovo', 45, 24, 1),
(2, 2, 'AMD Ryzen 7 7800X3D', 'amd-ryzen-7-7800x3d', 'Processore per gaming.', 420.00, 389.90, 'nuovo', 3, 24, 1),
(4, 4, 'Kingston KC3000 1TB', 'kingston-kc3000-1tb', 'SSD NVMe PCIe 4.0.', 149.99, NULL, 'ricondizionato', 0, 12, 1),
(2, 2, 'AMD Ryzen 5 9600X', 'amd-ryzen-5-9600x', 'Processore per gaming.', 199.99, NULL, 'nuovo', 3, 24, 1),
(5, 5, 'Logitech G PRO X Wireless LIGHTSPEED', 'logitech-g-pro-x-wireless-lightspeed', 'Cuffie per il gaming.', 239.99, 82.64, 'nuovo', 0, 48, 1);

-- product_images: nessun vincolo UNIQUE -> guard con NOT EXISTS
INSERT INTO product_images (products_id, percorso, ordine)
SELECT * FROM (SELECT 1 AS products_id, '5070.jpg' AS percorso, 0 AS ordine) t
WHERE NOT EXISTS (SELECT 1 FROM product_images WHERE products_id = 1 AND percorso = '5070.jpg');

INSERT INTO product_images (products_id, percorso, ordine)
SELECT * FROM (SELECT 2 AS products_id, 'ram.jpg' AS percorso, 0 AS ordine) t
WHERE NOT EXISTS (SELECT 1 FROM product_images WHERE products_id = 2 AND percorso = 'ram.jpg');

INSERT INTO product_images (products_id, percorso, ordine)
SELECT * FROM (SELECT 3 AS products_id, 'cpu.jpg' AS percorso, 0 AS ordine) t
WHERE NOT EXISTS (SELECT 1 FROM product_images WHERE products_id = 3 AND percorso = 'cpu.jpg');

INSERT INTO product_images (products_id, percorso, ordine)
SELECT * FROM (SELECT 4 AS products_id, 'ssd.jpg' AS percorso, 0 AS ordine) t
WHERE NOT EXISTS (SELECT 1 FROM product_images WHERE products_id = 4 AND percorso = 'ssd.jpg');

INSERT INTO product_images (products_id, percorso, ordine)
SELECT * FROM (SELECT 5 AS products_id, 'cpu2.jpg' AS percorso, 0 AS ordine) t
WHERE NOT EXISTS (SELECT 1 FROM product_images WHERE products_id = 5 AND percorso = 'cpu2.jpg');

INSERT INTO product_images (products_id, percorso, ordine)
SELECT * FROM (SELECT 6 AS products_id, 'cuffie.jpg' AS percorso, 0 AS ordine) t
WHERE NOT EXISTS (SELECT 1 FROM product_images WHERE products_id = 6 AND percorso = 'cuffie.jpg');

-- product_specs: nessun vincolo UNIQUE -> guard con NOT EXISTS
INSERT INTO product_specs (products_id, chiave, valore)
SELECT * FROM (SELECT 1 AS products_id, 'VRAM' AS chiave, '12GB GDDR6X' AS valore) t
WHERE NOT EXISTS (SELECT 1 FROM product_specs WHERE products_id = 1 AND chiave = 'VRAM');

INSERT INTO product_specs (products_id, chiave, valore)
SELECT * FROM (SELECT 2 AS products_id, 'Frequenza' AS chiave, '6000 MHz' AS valore) t
WHERE NOT EXISTS (SELECT 1 FROM product_specs WHERE products_id = 2 AND chiave = 'Frequenza');

INSERT INTO product_specs (products_id, chiave, valore)
SELECT * FROM (SELECT 3 AS products_id, 'Core/Thread' AS chiave, '8 Core / 16 Thread' AS valore) t
WHERE NOT EXISTS (SELECT 1 FROM product_specs WHERE products_id = 3 AND chiave = 'Core/Thread');

-- ---------------------------------------------------------------------
-- COUPON
-- codice è UNIQUE -> INSERT IGNORE basta
-- ---------------------------------------------------------------------
INSERT IGNORE INTO coupons (codice, tipo, valore, data_inizio, data_fine, attivo) VALUES
('BENVENUTO10', 'percentuale', 10, '2026-01-01', '2026-12-31', 1),
('GAMER20', 'percentuale', 20, '2026-01-01', '2026-08-01', 1);

-- ---------------------------------------------------------------------
-- ORDINI
-- numero_ordine è UNIQUE -> INSERT IGNORE basta per orders
-- ---------------------------------------------------------------------
INSERT IGNORE INTO orders (users_id, addresses_id, coupons_id, numero_ordine, subtotale, sconto, totale, stato, data_ordine) VALUES
(3, 1, NULL, 'ORD-0001', 649.00, 0.00, 649.00, 'consegnato', '2026-06-15 10:30:00'),
(3, 1, 1, 'ORD-0002', 125.50, 12.55, 112.95, 'spedito', '2026-07-02 15:45:00');

-- order_items: nessun vincolo UNIQUE -> guard con NOT EXISTS
INSERT INTO order_items (orders_id, products_id, nome_prodotto, quantita, prezzo_unitario)
SELECT * FROM (SELECT 1 AS orders_id, 1 AS products_id, 'RTX 5070 Super 12GB' AS nome_prodotto, 1 AS quantita, 649.00 AS prezzo_unitario) t
WHERE NOT EXISTS (SELECT 1 FROM order_items WHERE orders_id = 1 AND products_id = 1);

INSERT INTO order_items (orders_id, products_id, nome_prodotto, quantita, prezzo_unitario)
SELECT * FROM (SELECT 2 AS orders_id, 2 AS products_id, 'Corsair Vengeance 32GB DDR5' AS nome_prodotto, 1 AS quantita, 125.50 AS prezzo_unitario) t
WHERE NOT EXISTS (SELECT 1 FROM order_items WHERE orders_id = 2 AND products_id = 2);

-- order_status_history: nessun vincolo UNIQUE -> guard con NOT EXISTS
INSERT INTO order_status_history (orders_id, stato, nota, users_id)
SELECT * FROM (SELECT 1 AS orders_id, 'consegnato' AS stato, 'Ordine consegnato al cliente' AS nota, 1 AS users_id) t
WHERE NOT EXISTS (SELECT 1 FROM order_status_history WHERE orders_id = 1 AND stato = 'consegnato');

INSERT INTO order_status_history (orders_id, stato, nota, users_id)
SELECT * FROM (SELECT 2 AS orders_id, 'spedito' AS stato, 'Ordine spedito tramite corriere' AS nota, 1 AS users_id) t
WHERE NOT EXISTS (SELECT 1 FROM order_status_history WHERE orders_id = 2 AND stato = 'spedito');

-- ---------------------------------------------------------------------
-- FAQ
-- nessun vincolo UNIQUE -> guard con NOT EXISTS (sulla domanda)
-- ---------------------------------------------------------------------
INSERT INTO faqs (domanda, risposta, ordine, pubblicata)
SELECT * FROM (SELECT 'Quali sono i tempi di spedizione?' AS domanda, 'Spediamo entro 24h lavorative dall''ordine. La consegna avviene solitamente in 24/48h tramite corriere espresso.' AS risposta, 1 AS ordine, 1 AS pubblicata) t
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE domanda = 'Quali sono i tempi di spedizione?');

INSERT INTO faqs (domanda, risposta, ordine, pubblicata)
SELECT * FROM (SELECT 'Come funziona la garanzia sui ricondizionati?' AS domanda, 'Tutti i prodotti ricondizionati sono coperti da 12 mesi di garanzia hardware gestita direttamente dal nostro laboratorio.' AS risposta, 2 AS ordine, 1 AS pubblicata) t
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE domanda = 'Come funziona la garanzia sui ricondizionati?');

INSERT INTO faqs (domanda, risposta, ordine, pubblicata)
SELECT * FROM (SELECT 'Come so se questa componente è compatibile con il mio PC?' AS domanda, 'Contattaci! Il nostro team di esperti sarà felice di aiutarti. Clicca su "Scrivici" nella parte in basso di questa pagina e noi ti ricontatteremo via email.' AS risposta, 3 AS ordine, 1 AS pubblicata) t
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE domanda = 'Come so se questa componente è compatibile con il mio PC?');

-- ---------------------------------------------------------------------
-- MESSAGGI DI CONTATTO
-- nessun vincolo UNIQUE -> guard con NOT EXISTS (email + oggetto)
-- ---------------------------------------------------------------------
INSERT INTO contact_messages (nome, email, oggetto, testo, stato)
SELECT * FROM (SELECT 'Luca Forlizzi' AS nome, 'luca@email.it' AS email, 'Informazioni su un componente' AS oggetto, 'Salve, volevo sapere se la RTX 5070 richiede un cavo 12VHPWR o usa i classici 8-pin.' AS testo, 'nuovo' AS stato) t
WHERE NOT EXISTS (SELECT 1 FROM contact_messages WHERE email = 'luca@email.it' AND oggetto = 'Informazioni su un componente');

INSERT INTO contact_messages (nome, email, oggetto, testo, stato)
SELECT * FROM (SELECT 'Anna Pepe' AS nome, 'anna@email.it' AS email, 'Ordine e spedizione' AS oggetto, 'Ho sbagliato l''indirizzo di consegna per l''ordine #2, possiamo modificarlo?' AS testo, 'letto' AS stato) t
WHERE NOT EXISTS (SELECT 1 FROM contact_messages WHERE email = 'anna@email.it' AND oggetto = 'Ordine e spedizione');

-- ---------------------------------------------------------------------
-- NOTA: la tabella "newsletter_subscribers" non esiste nel nuovo schema.
-- ---------------------------------------------------------------------
