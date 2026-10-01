<?php
/**
 * PASSBALL Cup - Logout del administrador
 *
 * Destruye la sesion completa. Antes solo hacia unset($_SESSION['admin']),
 * pero requireAdminAPI() lee $_SESSION['admin_id'] y esa clave la seguan
 * dejando puesta, de modo que la sesion de la API sobrevivia al logout y
 * los endpoints protegidos seguian aceptando al mismo administrador.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $p['path'],
        $p['domain'],
        $p['secure'],
        $p['httponly']
    );
}

session_destroy();

header('Location: ../login.php');
exit;
