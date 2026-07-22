<?php

class Wishlist
{
    public function trovaOCreaPerUtente(int $userId): int
    {
        $stmt = Database::query('SELECT id FROM wishlists WHERE users_id = ? LIMIT 1', [$userId]);
        $row = $stmt->fetch();

        if ($row) {
            return (int) $row['id'];
        }

        Database::query('INSERT INTO wishlists (users_id) VALUES (?)', [$userId]);
        return (int) Database::getConnection()->lastInsertId();
    }

    public function aggiungi(int $wishlistId, int $productId): void
    {
        // INSERT IGNORE: la PK composita (wishlists_id, products_id) evita duplicati senza dover leggere prima
        Database::query(
            'INSERT IGNORE INTO wishlist_items (wishlists_id, products_id) VALUES (?, ?)',
            [$wishlistId, $productId]
        );
    }

    public function rimuovi(int $wishlistId, int $productId): void
    {
        Database::query(
            'DELETE FROM wishlist_items WHERE wishlists_id = ? AND products_id = ?',
            [$wishlistId, $productId]
        );
    }

    public function prodotti(int $wishlistId): array
    {
        $stmt = Database::query(
            'SELECT p.id, p.nome, p.slug, p.prezzo, p.prezzo_scontato,
                    (SELECT pi.percorso FROM product_images pi
                     WHERE pi.products_id = p.id
                     ORDER BY pi.ordine ASC LIMIT 1) AS immagine
             FROM wishlist_items wi
             JOIN products p ON p.id = wi.products_id
             WHERE wi.wishlists_id = ?
             ORDER BY wi.data_aggiunta DESC',
            [$wishlistId]
        );
        return $stmt->fetchAll();
    }
}
