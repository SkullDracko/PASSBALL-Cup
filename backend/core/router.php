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
        $permitidos = [];

        // El path se casa primero y el metodo despues. Al revés, un desajuste de
        // metodo se descartaba en silencio y caia en el 404 generico, con el
        // mismo cuerpo que una ruta inexistente: un GET a un endpoint POST era
        // indistinguible de una ruta que no existe (F3).
        foreach ($this->rutas as [$rutaMetodo, $patron, $handler]) {
            $regex = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $patron);
            if (!preg_match('#^' . $regex . '$#', $path, $matches)) continue;

            $permitidos[$rutaMetodo] = true;

            if ($rutaMetodo !== strtoupper($metodo)) continue;

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

        // El path existe pero no con este metodo: 405, no 404. 23 de las 83
        // rutas aceptan varios metodos, asi que el Allow aporta informacion real.
        if ($permitidos) {
            header('Allow: ' . implode(', ', array_keys($permitidos)));
            jsonResponse(false, [], [
                'error' => "Método no permitido para $path",
                'permitidos' => array_keys($permitidos)
            ], 405);
        }

        jsonResponse(false, [], ['error' => "Ruta no encontrada: $metodo $path"], 404);
    }
}
