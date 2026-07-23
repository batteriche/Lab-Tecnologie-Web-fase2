<?php

/**
 * Verifica i permessi secondo il modello users -> groups -> services.
 * Unico punto di controllo per l'intera applicazione (niente controlli sparsi
 * nei singoli controller).
 */
class PermissionService
{
    /**
     * True se l'utente appartiene a un gruppo abilitato al servizio dato.
     */
    public static function can(int $userId, string $serviceUsername): bool
    {
        $stmt = Database::query(
            'SELECT COUNT(*) AS n
             FROM users_has_groups uhg
             JOIN services_has_groups shg ON shg.groups_id = uhg.groups_id
             WHERE uhg.users_id = ? AND shg.services_username = ?',
            [$userId, $serviceUsername]
        );

        return (int) $stmt->fetch()['n'] > 0;
    }

    /**
     * Blocca l'accesso (403) se l'utente loggato non ha il permesso richiesto.
     */
    public static function require(string $serviceUsername): void
    {
        Auth::requireLogin();

        if (!self::can(Auth::id(), $serviceUsername)) {
            http_response_code(403);
            require VIEWS_PATH . 'frontend/403.html';
            exit;
        }
    }

    /**
     * Elenco nomi gruppo dell'utente (utile per la UI admin).
     */
    public static function groupsOf(int $userId): array
    {
        $stmt = Database::query(
            'SELECT g.nome
             FROM `groups` g
             JOIN users_has_groups uhg ON uhg.groups_id = g.id
             WHERE uhg.users_id = ?',
            [$userId]
        );

        return array_column($stmt->fetchAll(), 'nome');
    }
}
