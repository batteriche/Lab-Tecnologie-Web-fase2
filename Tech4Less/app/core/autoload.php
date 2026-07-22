<?php

/**
 * Autoloader minimale: mappa il namespace/nome classe alla struttura cartelle.
 * Evita require manuali sparsi, senza introdurre Composer (non necessario qui).
 */
spl_autoload_register(function (string $class) {
    $class = str_replace('\\', '/', $class);

    $paths = [
        APP_ROOT . '/app/core/'    . $class . '.php',
        APP_ROOT . '/app/controllers/' . $class . '.php',
        APP_ROOT . '/app/models/'  . $class . '.php',
        APP_ROOT . '/app/services/'. $class . '.php',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require $path;
            return;
        }
    }
});
