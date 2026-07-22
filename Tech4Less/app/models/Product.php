<?php

class Product
{
    /**
     * Prodotti attivi, con nome categoria già in JOIN (evita N+1 nelle view).
     */
    public function inEvidenza(int $limite = 4): array
    {
        // LIMIT con parametro richiede bindValue esplicito col tipo intero (PDO lo tratta come stringa altrimenti)
        return $this->fetchWithIntLimit(
            'SELECT p.id, p.nome, p.slug, p.prezzo, p.prezzo_scontato, p.condizione,
                    c.nome AS categoria_nome,
                    (SELECT pi.percorso FROM product_images pi
                     WHERE pi.products_id = p.id
                     ORDER BY pi.ordine ASC LIMIT 1) AS immagine
             FROM products p
             JOIN categories c ON c.id = p.categories_id
             WHERE p.attivo = 1
             ORDER BY p.data_creazione DESC
             LIMIT :lim',
            $limite
        );
    }

    public function tutti(int $offset = 0, int $limite = 24): array
    {
        $sql = 'SELECT p.id, p.nome, p.slug, p.prezzo, p.prezzo_scontato, p.condizione,
                       c.nome AS categoria_nome,
                       (SELECT pi.percorso FROM product_images pi
                        WHERE pi.products_id = p.id
                        ORDER BY pi.ordine ASC LIMIT 1) AS immagine
                FROM products p
                JOIN categories c ON c.id = p.categories_id
                WHERE p.attivo = 1
                ORDER BY p.nome ASC
                LIMIT :off, :lim';

        $stmt = Database::getConnection()->prepare($sql);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function perCategoria(int $categoriaId, int $limite = 24): array
    {
        return $this->fetchWithIntLimit(
            'SELECT p.id, p.nome, p.slug, p.prezzo, p.prezzo_scontato, p.condizione,
                    c.nome AS categoria_nome,
                    (SELECT pi.percorso FROM product_images pi
                     WHERE pi.products_id = p.id
                     ORDER BY pi.ordine ASC LIMIT 1) AS immagine
             FROM products p
             JOIN categories c ON c.id = p.categories_id
             WHERE p.attivo = 1 AND p.categories_id = ' . (int) $categoriaId . '
             ORDER BY p.nome ASC
             LIMIT :lim',
            $limite
        );
    }

    public function trovaPerSlug(string $slug): ?array
    {
        $stmt = Database::query(
            'SELECT p.*, c.nome AS categoria_nome
             FROM products p
             JOIN categories c ON c.id = p.categories_id
             WHERE p.slug = ? AND p.attivo = 1
             LIMIT 1',
            [$slug]
        );
        $prodotto = $stmt->fetch() ?: null;

        if ($prodotto !== null) {
            $prodotto['immagine'] = $this->immaginePrincipale((int) $prodotto['id']);
        }

        return $prodotto;
    }

    /**
     * Path (colonna product_images.percorso) della prima immagine del prodotto,
     * o stringa vuota se il prodotto non ne ha nessuna.
     */
    public function immaginePrincipale(int $productId): string
    {
        $stmt = Database::query(
            'SELECT percorso FROM product_images WHERE products_id = ? ORDER BY ordine ASC LIMIT 1',
            [$productId]
        );
        $riga = $stmt->fetch();
        return $riga['percorso'] ?? '';
    }

    public function trovaPerId(int $id): ?array
    {
        $stmt = Database::query('SELECT * FROM products WHERE id = ? LIMIT 1', [$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Elenco admin: include anche i prodotti disattivati (a differenza delle query frontend).
     */
    public function tuttiAdmin(): array
    {
        $stmt = Database::query(
            'SELECT p.id, p.nome, p.prezzo, p.giacenza, p.attivo, c.nome AS categoria_nome
             FROM products p
             JOIN categories c ON c.id = p.categories_id
             ORDER BY p.data_creazione DESC'
        );
        return $stmt->fetchAll();
    }

    public function crea(array $dati): int
    {
        Database::query(
            'INSERT INTO products (categories_id, brands_id, nome, slug, descrizione, prezzo, prezzo_scontato, condizione, giacenza, garanzia_mesi, attivo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $dati['categories_id'], $dati['brands_id'], $dati['nome'], $dati['slug'],
                $dati['descrizione'], $dati['prezzo'], $dati['prezzo_scontato'], $dati['condizione'],
                $dati['giacenza'], $dati['garanzia_mesi'], $dati['attivo'],
            ]
        );
        return (int) Database::getConnection()->lastInsertId();
    }

    public function aggiorna(int $id, array $dati): void
    {
        Database::query(
            'UPDATE products SET categories_id=?, brands_id=?, nome=?, slug=?, descrizione=?,
                                  prezzo=?, prezzo_scontato=?, condizione=?, giacenza=?, garanzia_mesi=?, attivo=?
             WHERE id = ?',
            [
                $dati['categories_id'], $dati['brands_id'], $dati['nome'], $dati['slug'],
                $dati['descrizione'], $dati['prezzo'], $dati['prezzo_scontato'], $dati['condizione'],
                $dati['giacenza'], $dati['garanzia_mesi'], $dati['attivo'], $id,
            ]
        );
    }

    public function elimina(int $id): void
    {
        Database::query('DELETE FROM products WHERE id = ?', [$id]);
    }

    public function specifiche(int $productId): array
    {
        $stmt = Database::query(
            'SELECT chiave, valore FROM product_specs WHERE products_id = ?',
            [$productId]
        );
        return $stmt->fetchAll();
    }

    public function aggiungiImmagine(int $productId, string $percorso, int $ordine = 0): void
    {
        Database::query(
            'INSERT INTO product_images (products_id, percorso, ordine) VALUES (?, ?, ?)',
            [$productId, $percorso, $ordine]
        );
    }

    /**
     * Rimuove le immagini già associate (usato in modifica, prima di inserirne una
     * nuova, per non accumulare immagini vecchie orfane).
     */
    public function rimuoviImmagini(int $productId): void
    {
        Database::query('DELETE FROM product_images WHERE products_id = ?', [$productId]);
    }

    /**
     * Helper interno per query con LIMIT dinamico (PDO vuole PARAM_INT esplicito).
     */
    private function fetchWithIntLimit(string $sql, int $limit): array
    {
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Formatta un prezzo per la view (la sintassi del template non fa calcoli/formattazioni).
     */
    public static function formatPrezzo(?float $valore): string
    {
        if ($valore === null) {
            return '';
        }
        return '€ ' . number_format($valore, 2, ',', '.');
    }

    /**
     * Decrementa la giacenza di un prodotto in modo sicuro.
     */
/**
     * Decrementa la giacenza di un prodotto in modo sicuro.
     */
    public function decrementaGiacenza(int $productId, int $quantita): bool
    {
        // Usiamo due nomi separati per la quantità (:q1 e :q2) per evitare l'errore HY093 di PDO
        $sql = 'UPDATE products SET giacenza = giacenza - :q1 WHERE id = :id AND giacenza >= :q2';
        $stmt = Database::getConnection()->prepare($sql);
        
        $stmt->bindValue(':q1', $quantita, PDO::PARAM_INT);
        $stmt->bindValue(':q2', $quantita, PDO::PARAM_INT);
        $stmt->bindValue(':id', $productId, PDO::PARAM_INT);
        
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
}
