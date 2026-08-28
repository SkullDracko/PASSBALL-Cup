<?php
/**
 * PASSBALL Cup - Middleware de autenticación
 * Incluir en cada página que requiera login
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

$usuario = $_SESSION['usuario'];

// Helper: verificar si es admin
function es_admin(): bool {
    global $usuario;
    return $usuario['rol'] === 'admin';
}

// Helper: verificar si es líder
function es_lider(): bool {
    global $usuario;
    return $usuario['rol'] === 'lider';
}

// Helper: verificar si tiene un rol específico
function tiene_rol(string $rol): bool {
    global $usuario;
    return $usuario['rol'] === $rol;
}
