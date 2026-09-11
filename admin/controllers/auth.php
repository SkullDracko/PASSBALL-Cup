<?php
/**
 * PASSBALL Cup - Middleware de autenticación (admin)
 * Incluir en cada página del panel admin que requiera login
 * Esquema: tabla administradores
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$admin = $_SESSION['admin'];