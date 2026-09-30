<?php
// Carga variables de entorno (.env simple, sin depender de librerías externas).
// En producción, definir estas variables reales a nivel de servidor/hosting.

function cargarEnv(string $path): void {
    if (!file_exists($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $_ENV[trim($key)] = trim($value);
    }
}

cargarEnv(__DIR__ . '/../../.env');

// Defaults de desarrollo si no hay .env (no usar en producción)
$_ENV['DB_HOST'] = $_ENV['DB_HOST'] ?? '127.0.0.1';
$_ENV['DB_NAME'] = $_ENV['DB_NAME'] ?? 'passballcup';
$_ENV['DB_USER'] = $_ENV['DB_USER'] ?? 'root';
$_ENV['DB_PASS'] = $_ENV['DB_PASS'] ?? '';

$_ENV['APP_ENV'] = $_ENV['APP_ENV'] ?? 'local'; // local | production
// APP_DEBUG=1 imprime la traza PDO completa (con DSN y nombre de la base) a
// cualquier llamador anónimo en un 500. Por defecto apagado, también en local:
// quien lo necesite lo enciende en .env (S3).
$_ENV['APP_DEBUG'] = $_ENV['APP_DEBUG'] ?? '0';

// Integración con AFIHub (verificación de inscripción de jugadores).
// AFI_API_KEY debe coincidir con el secreto configurado en el endpoint de
// AFIHub; sin él, cualquiera puede llamar ese endpoint falsificando el
// header Origin y obtener datos de estudiantes (Origin no es un mecanismo
// de autenticación real para llamadas servidor-a-servidor).
$_ENV['AFI_API_KEY'] = $_ENV['AFI_API_KEY'] ?? 'CAMBIA-ESTA-LLAVE-EN-.env';
$_ENV['AFI_BASE_URL'] = $_ENV['AFI_BASE_URL'] ?? (
    $_ENV['APP_ENV'] === 'production'
        ? 'https://passballcup.encuestapassword2026.com/api/publico_verificar_inscripcion.php'
        : 'http://localhost/AFIhub/controllers/publico_verificar_inscripcion.php'
);
$_ENV['AFI_ORIGIN'] = $_ENV['AFI_ORIGIN'] ?? (
    $_ENV['APP_ENV'] === 'production'
        ? 'https://passballcup.encuestapassword2026.com'
        : 'http://localhost'
);

error_reporting($_ENV['APP_DEBUG'] === '1' ? E_ALL : 0);
ini_set('display_errors', $_ENV['APP_DEBUG'] === '1' ? '1' : '0');

// Subida de archivos. Se replican aquí los valores de config/app.php del sitio
// legacy para que backend/ siga siendo autónomo; los guards evitan un
// "Constant already defined" si alguna vez se cargaran ambos.
if (!defined('UPLOADS_PATH')) {
    define('UPLOADS_PATH', __DIR__ . '/../../uploads/');
    define('UPLOADS_URL', 'uploads/');
    define('MAX_FILE_SIZE', 5 * 1024 * 1024);
    define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
}
