<?php

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/core/autoload.php';
require_once __DIR__ . '/../../app/core/Database.php';

// Ogni richiesta sotto /admin richiede almeno una sessione autenticata.
// Il controllo del servizio specifico (gestione_catalogo, gestione_ordini, ...)
// resta comunque delegato a PermissionService::require() dentro ogni singolo
// controller admin, perché ogni sezione ha un permesso diverso.
Auth::requireLogin();

$router = new Router();
require APP_ROOT . '/app/config/routes.php';

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
