<?php
// Sesión de jugador (tabla usuarios). Detiene la ejecución si no hay sesión válida.

function requireAuthAPI(): int {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();

    $userId = $_SESSION['user_id'] ?? $_SESSION['usuario']['id'] ?? null;

    if (!filter_var($userId, FILTER_VALIDATE_INT) || (int) $userId <= 0) {
        jsonResponse(false, [], ['error' => 'No autenticado'], 401);
    }

    $_SESSION['user_id'] = (int) $userId;

    return (int) $userId;
}
