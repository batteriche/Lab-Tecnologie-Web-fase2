<?php

class User
{
    public function tutti(): array
    {
        $stmt = Database::query(
            "SELECT u.id, u.username, u.email, u.nome, u.cognome, u.stato,
                    GROUP_CONCAT(g.nome SEPARATOR ', ') AS gruppi
             FROM users u
             LEFT JOIN users_has_groups uhg ON uhg.users_id = u.id
             LEFT JOIN `groups` g ON g.id = uhg.groups_id
             GROUP BY u.id
             ORDER BY u.data_registrazione DESC"
        );
        return $stmt->fetchAll();
    }

    public function trova(int $id): ?array
    {
        $stmt = Database::query('SELECT * FROM users WHERE id = ? LIMIT 1', [$id]);
        return $stmt->fetch() ?: null;
    }

    public function gruppiDisponibili(): array
    {
        $stmt = Database::query('SELECT id, nome FROM `groups` ORDER BY nome ASC');
        return $stmt->fetchAll();
    }

    public function gruppiDiUtente(int $userId): array
    {
        $stmt = Database::query('SELECT groups_id FROM users_has_groups WHERE users_id = ?', [$userId]);
        return array_column($stmt->fetchAll(), 'groups_id');
    }

    /**
     * Sostituisce integralmente i gruppi di un utente con l'elenco passato
     * (usato dal form admin "gruppi assegnati" con checkbox multiple).
     */
    public function impostaGruppi(int $userId, array $groupIds): void
    {
        Database::query('DELETE FROM users_has_groups WHERE users_id = ?', [$userId]);

        foreach ($groupIds as $groupId) {
            Database::query(
                'INSERT INTO users_has_groups (users_id, groups_id) VALUES (?, ?)',
                [$userId, (int) $groupId]
            );
        }
    }
}
