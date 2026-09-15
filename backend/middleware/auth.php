<?php
// Sesión de jugador (tabla usuarios). Detiene la ejecución si no hay sesión válida.

function requireAuthAPI(): int {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();

    if (empty($_SESSION['user_id'])) {
        jsonResponse(false, [], ['error' => 'No autenticado'], 401);
    }

    return (int) $_SESSION['user_id'];
}
