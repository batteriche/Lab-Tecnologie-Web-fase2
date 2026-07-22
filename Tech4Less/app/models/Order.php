<?php

class Order
{
    public function crea(array $dati): int
    {
        Database::query(
            'INSERT INTO orders (users_id, addresses_id, coupons_id, numero_ordine, subtotale, sconto, totale, stato)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $dati['users_id'],
                $dati['addresses_id'],
                $dati['coupons_id'],
                $dati['numero_ordine'],
                $dati['subtotale'],
                $dati['sconto'],
                $dati['totale'],
                'in_attesa',
            ]
        );

        return (int) Database::getConnection()->lastInsertId();
    }

    public function aggiungiRiga(int $orderId, int $productId, string $nomeProdotto, int $quantita, float $prezzoUnitario): void
    {
        Database::query(
            'INSERT INTO order_items (orders_id, products_id, nome_prodotto, quantita, prezzo_unitario)
             VALUES (?, ?, ?, ?, ?)',
            [$orderId, $productId, $nomeProdotto, $quantita, $prezzoUnitario]
        );
    }

    public function registraStato(int $orderId, string $stato, ?string $nota = null, ?int $adminId = null): void
    {
        Database::query(
            'INSERT INTO order_status_history (orders_id, stato, nota, users_id) VALUES (?, ?, ?, ?)',
            [$orderId, $stato, $nota, $adminId]
        );
    }

    public function trovaPerNumero(string $numeroOrdine, int $userId): ?array
    {
        $stmt = Database::query(
            'SELECT * FROM orders WHERE numero_ordine = ? AND users_id = ? LIMIT 1',
            [$numeroOrdine, $userId]
        );
        return $stmt->fetch() ?: null;
    }

    public function righe(int $orderId): array
    {
        $stmt = Database::query(
            'SELECT nome_prodotto, quantita, prezzo_unitario FROM order_items WHERE orders_id = ?',
            [$orderId]
        );
        return $stmt->fetchAll();
    }

    public function perUtente(int $userId): array
    {
        $stmt = Database::query(
            'SELECT id, numero_ordine, totale, stato, data_ordine FROM orders WHERE users_id = ? ORDER BY data_ordine DESC',
            [$userId]
        );
        return $stmt->fetchAll();
    }

    public function generaNumeroOrdine(): string
    {
        return 'T4L-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }

    /**
     * Elenco admin con nome cliente in JOIN, senza filtro utente.
     */
    public function tuttiAdmin(): array
    {
        $stmt = Database::query(
            'SELECT o.id, o.numero_ordine, o.totale, o.stato, o.data_ordine,
                    u.nome AS cliente_nome, u.cognome AS cliente_cognome
             FROM orders o
             JOIN users u ON u.id = o.users_id
             ORDER BY o.data_ordine DESC'
        );
        return $stmt->fetchAll();
    }

    public function trovaPerId(int $id): ?array
    {
        $stmt = Database::query(
            'SELECT o.*, u.nome AS cliente_nome, u.cognome AS cliente_cognome, u.email AS cliente_email,
                    a.via, a.citta, a.provincia, a.cap
             FROM orders o
             JOIN users u ON u.id = o.users_id
             JOIN addresses a ON a.id = o.addresses_id
             WHERE o.id = ? LIMIT 1',
            [$id]
        );
        return $stmt->fetch() ?: null;
    }

    public function storicoStati(int $orderId): array
    {
        $stmt = Database::query(
            'SELECT stato, nota, data_cambio FROM order_status_history WHERE orders_id = ? ORDER BY data_cambio ASC',
            [$orderId]
        );
        return $stmt->fetchAll();
    }

    public function aggiornaStato(int $orderId, string $stato): void
    {
        Database::query('UPDATE orders SET stato = ? WHERE id = ?', [$stato, $orderId]);
    }
}
