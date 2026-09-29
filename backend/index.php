<?php
// Punto de entrada único. Todas las peticiones a /api/* llegan aquí
// (ver .htaccess) y se despachan según routes/api.php.

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/core/response.php';
require_once __DIR__ . '/core/validator.php';
require_once __DIR__ . '/core/router.php';
require_once __DIR__ . '/middleware/auth.php';
require_once __DIR__ . '/middleware/adminAuth.php';
require_once __DIR__ . '/security/authorization.php';

spl_autoload_register(function (string $class): void {
    foreach (['controllers', 'services'] as $carpeta) {
        $archivo = __DIR__ . "/{$carpeta}/{$class}.php";
        if (file_exists($archivo)) {
            require $archivo;
            return;
        }
    }
});

$router = new Router();
require __DIR__ . '/routes/api.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// El servidor antepone su propia raiz a /api/* y las rutas de routes/api.php no
// la incluyen, asi que se recorta. Antes la carpeta del proyecto estaba
// hardcodeada ('/PASSBALL-Cup/backend'), lo que hacia que desplegar en otra
// carpeta -o en la raiz del docroot- dejara las 83 rutas en 404 (F7).
// Se deriva de SCRIPT_NAME para que acompanhe al proyecto donde se instale.
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');

if ($basePath !== '' && $basePath !== '/' && str_starts_with($uri, $basePath)) {
    $uri = substr($uri, strlen($basePath));
}

$router->dispatch(
    $_SERVER['REQUEST_METHOD'],
    $uri
);
