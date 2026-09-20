<?php

require_once __DIR__ . '/../core/db.php';

function requireRol(string $rol): int
{
    $usuarioId = requireAuthAPI();
    $pdo = conectarDB();

    $stmt = $pdo->prepare(
        'SELECT rol, estado FROM usuarios WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$usuarioId]);
    $usuario = $stmt->fetch();

    if (!$usuario || $usuario['estado'] !== 'activo') {
        jsonResponse(false, [], ['error' => 'Usuario no autorizado'], 403);
    }

    if ($usuario['rol'] !== $rol) {
        jsonResponse(false, [], ['error' => 'No tienes permisos para esta acción'], 403);
    }

    return $usuarioId;
}

function requireJugador(): int
{
    $usuarioId = requireAuthAPI();
    $pdo = conectarDB();

    $stmt = $pdo->prepare(
        'SELECT rol, estado, jugador_activo FROM usuarios WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$usuarioId]);
    $usuario = $stmt->fetch();

    if (
        !$usuario
        || $usuario['rol'] !== 'jugador'
        || $usuario['estado'] !== 'activo'
        || !(bool) $usuario['jugador_activo']
    ) {
        jsonResponse(false, [], ['error' => 'Se requiere un jugador activo'], 403);
    }

    return $usuarioId;
}

function requireCapitan(int $equipoId): int
{
    $usuarioId = requireJugador();
    $pdo = conectarDB();

    $stmt = $pdo->prepare(
        'SELECT id FROM equipos WHERE id = ? AND capitan_id = ? LIMIT 1'
    );
    $stmt->execute([$equipoId, $usuarioId]);

    if (!$stmt->fetch()) {
        jsonResponse(false, [], ['error' => 'No eres el capitán de este equipo'], 403);
    }

    return $usuarioId;
}

function requirePropietarioOAdmin(int $usuarioId): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!empty($_SESSION['admin_id'])) {
        requireAdminAPI();
        return;
    }

    if (empty($_SESSION['user_id'])) {
        jsonResponse(false, [], ['error' => 'No autenticado'], 401);
    }

    if ((int) $_SESSION['user_id'] !== $usuarioId) {
        jsonResponse(false, [], [
            'error' => 'Solo puedes modificar tu propio usuario'
        ], 403);
    }
}