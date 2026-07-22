<?php

/**
 * Gestione autenticazione e sessione.
 * Password sempre con password_hash/password_verify (mai testo in chiaro o hash deboli).
 */
class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $stmt = Database::query(
            'SELECT id, password_hash, stato FROM users WHERE email = ? LIMIT 1',
            [$email]
        );
        $user = $stmt->fetch();

        if (!$user || $user['stato'] !== 'attivo') {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true); // mitiga session fixation
        $_SESSION['user_id'] = $user['id'];

        (new CartService())->fondiCarrelloOspiteConUtente((int) $user['id']);

        Database::query(
            'UPDATE users SET ultimo_accesso = NOW() WHERE id = ?',
            [$user['id']]
        );

        return true;
    }

    public static function register(array $data): int
    {
        $hash = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmt = Database::query(
            'INSERT INTO users (username, email, password_hash, nome, cognome, telefono)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $data['username'],
                $data['email'],
                $hash,
                $data['nome'],
                $data['cognome'],
                $data['telefono'] ?? null,
            ]
        );

        $userId = (int) Database::getConnection()->lastInsertId();

        // Ogni nuovo utente entra di default nel gruppo "clienti" (id 1, vedi seed schema.sql)
        Database::query(
            'INSERT INTO users_has_groups (users_id, groups_id) VALUES (?, 1)',
            [$userId]
        );

        return $userId;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        static $cached = null;
        if ($cached === null) {
            $stmt = Database::query(
                'SELECT id, username, email, nome, cognome, stato FROM users WHERE id = ?',
                [self::id()]
            );
            $cached = $stmt->fetch() ?: null;
        }

        return $cached;
    }

    /**
     * Forza la presenza di una sessione autenticata, altrimenti redirect al login.
     */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: ' . BASE_URL . '/account/login');
            exit;
        }
    }
}
