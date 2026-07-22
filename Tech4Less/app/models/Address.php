<?php

class Address
{
    public function crea(int $userId, array $data): int
    {
        Database::query(
            'INSERT INTO addresses (users_id, etichetta, via, citta, provincia, cap, nazione, predefinito)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $userId,
                $data['etichetta'] ?: 'Casa',
                $data['via'],
                $data['citta'],
                $data['provincia'],
                $data['cap'],
                $data['nazione'] ?: 'Italia',
                1,
            ]
        );

        return (int) Database::getConnection()->lastInsertId();
    }

    public function perUtente(int $userId): array
    {
        $stmt = Database::query(
            'SELECT id, etichetta, via, citta, provincia, cap, nazione FROM addresses WHERE users_id = ? ORDER BY predefinito DESC, id DESC',
            [$userId]
        );
        return $stmt->fetchAll();
    }

    public function trova(int $id, int $userId): ?array
    {
        $stmt = Database::query(
            'SELECT id, etichetta, via, citta, provincia, cap, nazione FROM addresses WHERE id = ? AND users_id = ? LIMIT 1',
            [$id, $userId]
        );
        return $stmt->fetch() ?: null;
    }
}
