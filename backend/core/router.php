<?php
// Router mínimo: mapea método HTTP + patrón de ruta ("/torneos/{id}/equipos")
// a [Controller, método]. Sin dependencias externas, sin magia.
//
// Reemplaza tanto el "un archivo físico por acción" (se multiplica el número
// de archivos sin necesidad) como el "switch($action) por query string"
// (las rutas quedan documentadas como datos, no como código disperso).

class Router {
    private array $rutas = [];

    public function add(string $metodo, string $patron, array $handler): void {
        $this->rutas[] = [strtoupper($metodo), $patron, $handler];
    }

    public function get(string $patron, array $handler): void    { $this->add('GET', $patron, $handler); }
    public function post(string $patron, array $handler): void   { $this->add('POST', $patron, $handler); }
    public function patch(string $patron, array $handler): void  { $this->add('PATCH', $patron, $handler); }
    public function delete(string $patron, array $handler): void { $this->add('DELETE', $patron, $handler); }

    public function dispatch(string $metodo, string $uri): void {
        $path = parse_url($uri, PHP_URL_PATH);

        foreach ($this->rutas as [$rutaMetodo, $patron, $handler]) {
            if ($rutaMetodo !== strtoupper($metodo)) continue;

            $regex = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $patron);
            if (!preg_match('#^' . $regex . '$#', $path, $matches)) continue;

            $params = array_filter(
                $matches,
                fn($k) => is_string($k),
                ARRAY_FILTER_USE_KEY
            );

            [$controllerClass, $accion] = $handler;
            $controller = new $controllerClass();
            $controller->$accion($params);
            return;
        }

        jsonResponse(false, [], ['error' => 'Ruta no encontrada'], 404);
    }
}
