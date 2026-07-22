<?php

/**
 * Router minimale: mappa un pattern di URL a [Controller, metodo].
 * Nessuna dipendenza esterna, coerente col vincolo "niente framework".
 *
 * Sintassi pattern supportata:
 *   '/'                          match esatto
 *   '/catalogo'                  match esatto
 *   '/prodotto/{slug}'           parametro nominato, passato al metodo del controller
 */
class Router
{
    private array $routes = [];

    public function get(string $pattern, string $controllerAction): void
    {
        $this->routes['GET'][$pattern] = $controllerAction;
    }

    public function post(string $pattern, string $controllerAction): void
    {
        $this->routes['POST'][$pattern] = $controllerAction;
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = parse_url($uri, PHP_URL_PATH);

        // Se l'app gira in una sottocartella (BASE_URL non vuoto), le rotte sono definite
        // senza quel prefisso: lo togliamo qui una volta sola invece di doverlo ripetere
        // in ogni pattern di routes.php.
        if (defined('BASE_URL') && BASE_URL !== '' && str_starts_with($uri, BASE_URL)) {
            $uri = substr($uri, strlen(BASE_URL));
        }

        $uri = rtrim($uri, '/');
        if ($uri === '') {
            $uri = '/';
        }

        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $pattern => $controllerAction) {
            $params = $this->match($pattern, $uri);
            if ($params !== null) {
                $this->call($controllerAction, $params);
                return;
            }
        }

        http_response_code(404);

        if (defined('APP_ENV') && APP_ENV === 'dev') {
            echo "<pre>Nessuna rotta trovata per: {$method} {$uri}</pre>";
        }

        require VIEWS_PATH . 'frontend/404.html';
    }

    /**
     * Ritorna un array di parametri se il pattern combacia, altrimenti null.
     */
    private function match(string $pattern, string $uri): ?array
    {
        $patternParts = explode('/', trim($pattern, '/'));
        $uriParts     = explode('/', trim($uri, '/'));

        if (count($patternParts) !== count($uriParts)) {
            return null;
        }

        $params = [];

        foreach ($patternParts as $i => $part) {
            if (preg_match('/^\{(\w+)\}$/', $part, $matches)) {
                $params[$matches[1]] = $uriParts[$i];
            } elseif ($part !== $uriParts[$i]) {
                return null;
            }
        }

        return $params;
    }

    private function call(string $controllerAction, array $params): void
    {
        [$controllerName, $action] = explode('@', $controllerAction);

        $class = $controllerName;
        if (!class_exists($class)) {
            http_response_code(500);
            die("Controller {$class} non trovato.");
        }

        $controller = new $class();

        if (!method_exists($controller, $action)) {
            http_response_code(500);
            die("Azione {$action} non trovata su {$class}.");
        }

        call_user_func_array([$controller, $action], $params);
    }
}
