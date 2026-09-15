<?php
// Sesión de administrador (tabla administradores, independiente de usuarios).

function requireAdminAPI(): int {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();

    if (empty($_SESSION['admin_id'])) {
        jsonResponse(false, [], ['error' => 'No autenticado como administrador'], 401);
    }

    return (int) $_SESSION['admin_id'];
}
