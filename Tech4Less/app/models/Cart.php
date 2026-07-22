<?php

class Cart
{
    public function trovaOCreaPerUtente(int $userId): array
    {
        $stmt = Database::query('SELECT * FROM carts WHERE users_id = ? LIMIT 1', [$userId]);
        $cart = $stmt->fetch();

        if ($cart) {
            return $cart;
        }

        Database::query('INSERT INTO carts (users_id) VALUES (?)', [$userId]);
        $id = (int) Database::getConnection()->lastInsertId();

        return ['id' => $id, 'users_id' => $userId, 'session_token' => null];
    }

    public function trovaOCreaPerOspite(string $token): array
    {
        $stmt = Database::query('SELECT * FROM carts WHERE session_token = ? LIMIT 1', [$token]);
        $cart = $stmt->fetch();

        if ($cart) {
            return $cart;
        }

        Database::query('INSERT INTO carts (session_token) VALUES (?)', [$token]);
        $id = (int) Database::getConnection()->lastInsertId();

        return ['id' => $id, 'users_id' => null, 'session_token' => $token];
    }

    /**
     * Righe del carrello con dati prodotto già in JOIN, per evitare N+1 nella view.
     */
    public function righe(int $cartId): array
    {
        $stmt = Database::query(
            'SELECT ci.id AS item_id, ci.quantita, ci.prezzo_unitario,
                    p.id AS products_id, p.nome, p.slug, p.giacenza
             FROM cart_items ci
             JOIN products p ON p.id = ci.products_id
             WHERE ci.carts_id = ?
             ORDER BY ci.id ASC',
            [$cartId]
        );
        return $stmt->fetchAll();
    }

    public function trovaRigaProdotto(int $cartId, int $productId): ?array
    {
        $stmt = Database::query(
            'SELECT id, quantita FROM cart_items WHERE carts_id = ? AND products_id = ? LIMIT 1',
            [$cartId, $productId]
        );
        return $stmt->fetch() ?: null;
    }

    public function aggiungiRiga(int $cartId, int $productId, int $quantita, float $prezzoUnitario): void
    {
        Database::query(
            'INSERT INTO cart_items (carts_id, products_id, quantita, prezzo_unitario) VALUES (?, ?, ?, ?)',
            [$cartId, $productId, $quantita, $prezzoUnitario]
        );
    }

    public function aggiornaQuantita(int $itemId, int $quantita): void
    {
        Database::query('UPDATE cart_items SET quantita = ? WHERE id = ?', [$quantita, $itemId]);
    }

    public function rimuoviRiga(int $itemId): void
    {
        Database::query('DELETE FROM cart_items WHERE id = ?', [$itemId]);
    }

    public function svuota(int $cartId): void
    {
        Database::query('DELETE FROM cart_items WHERE carts_id = ?', [$cartId]);
    }

    /**
     * Sposta tutte le righe di un carrello ospite dentro il carrello dell'utente
     * appena autenticato, sommando le quantità sui prodotti già presenti.
     */
    public function unisci(int $cartOspiteId, int $cartUtenteId): void
    {
        if ($cartOspiteId === $cartUtenteId) {
            return;
        }

        $righe = $this->righe($cartOspiteId);

        foreach ($righe as $riga) {
            $esistente = $this->trovaRigaProdotto($cartUtenteId, (int) $riga['products_id']);

            if ($esistente) {
                $this->aggiornaQuantita((int) $esistente['id'], (int) $esistente['quantita'] + (int) $riga['quantita']);
            } else {
                $this->aggiungiRiga($cartUtenteId, (int) $riga['products_id'], (int) $riga['quantita'], (float) $riga['prezzo_unitario']);
            }
        }

        $this->svuota($cartOspiteId);
        Database::query('DELETE FROM carts WHERE id = ?', [$cartOspiteId]);
    }
}
