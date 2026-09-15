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
$_ENV['APP_DEBUG'] = $_ENV['APP_DEBUG'] ?? '1';  // '1' muestra errores detallados

error_reporting($_ENV['APP_DEBUG'] === '1' ? E_ALL : 0);
ini_set('display_errors', $_ENV['APP_DEBUG'] === '1' ? '1' : '0');
