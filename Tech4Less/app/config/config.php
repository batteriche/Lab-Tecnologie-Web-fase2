<?php
/**
 * Configurazione globale dell'applicazione.
 * In produzione questi valori vanno spostati in variabili d'ambiente,
 * qui restano centralizzati per semplicità del corso.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'tech4less');
define('DB_USER', 'root');
define('DB_PASS', 'Password');
define('DB_CHARSET', 'utf8mb4');

// Percorso assoluto della root applicativa (cartella che contiene app/, public/, database/)
define('APP_ROOT', dirname(__DIR__, 2));

define('VIEWS_PATH', APP_ROOT . '/app/views/');

// Base URL usata per generare link e redirect (senza slash finale)
define('BASE_URL', '/tech4less');

// Durata cookie "ricordami" in secondi (30 giorni)
define('REMEMBER_ME_TTL', 60 * 60 * 24 * 30);

// Ambiente: 'dev' abilita la visualizzazione errori
define('APP_ENV', 'dev');

if (APP_ENV === 'dev') {
    ini_set('display_errors', '1');
    // Il motore template2.inc.php (beContent) è stato scritto per PHP più vecchio:
    // genera Warning/Deprecated innocui (proprietà dinamiche, preg_replace su null)
    // che PHP 8.2+ segnala più severamente. Li silenziamo qui senza toccare il motore,
    // mantenendo visibili gli errori veri del nostro codice applicativo.
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE);
} else {
    ini_set('display_errors', '0');
}

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);
