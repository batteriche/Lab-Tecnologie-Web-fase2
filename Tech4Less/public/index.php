<?php

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/autoload.php';
require_once __DIR__ . '/../app/core/Database.php';

$router = new Router();
require APP_ROOT . '/app/config/routes.php';

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
