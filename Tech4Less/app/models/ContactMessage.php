<?php

class ContactMessage
{
    public function tutti(): array
    {
        $stmt = Database::query(
            'SELECT id, nome, email, oggetto, stato, data_invio FROM contact_messages ORDER BY data_invio DESC'
        );
        return $stmt->fetchAll();
    }

    public function trova(int $id): ?array
    {
        $stmt = Database::query('SELECT * FROM contact_messages WHERE id = ? LIMIT 1', [$id]);
        return $stmt->fetch() ?: null;
    }

    public function aggiornaStato(int $id, string $stato): void
    {
        Database::query('UPDATE contact_messages SET stato = ? WHERE id = ?', [$stato, $id]);
    }

    public function contaNuovi(): int
    {
        $stmt = Database::query("SELECT COUNT(*) AS n FROM contact_messages WHERE stato = 'nuovo'");
        return (int) $stmt->fetch()['n'];
    }
}
