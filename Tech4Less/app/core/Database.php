<?php

/**
 * Wrapper singleton su PDO. Tutte le query dell'applicazione passano da qui
 * per garantire connessione unica e uso sistematico di query preparate
 * (requisito "sicurezza/robustezza" della Fase 2).
 */
class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
        // Non istanziabile direttamente
    }

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                // Non esporre mai i dettagli di connessione all'utente finale
                error_log('Errore connessione DB: ' . $e->getMessage());
                http_response_code(500);
                die('Errore interno. Riprova più tardi.');
            }
        }

        return self::$instance;
    }

    /**
     * Scorciatoia per query preparate: query($sql, $params) -> PDOStatement
     */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
