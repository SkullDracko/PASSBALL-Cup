<?php

require_once __DIR__ . '/../core/db.php';

class AuthController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = conectarDB();
    }

    public function login(array $params): void
    {
        // POST /api/auth/login

        $body = jsonBody();

        $matricula = trim($body['matricula'] ?? '');

        // Validación básica
        if ($matricula === '') {
            jsonResponse(
                false,
                [],
                ['error' => 'La matrícula es obligatoria'],
                400
            );
        }

        // Buscar usuario
        $stmt = $this->pdo->prepare("
            SELECT
                id,
                matricula,
                rol,
                estado,
                jugador_activo
            FROM usuarios
            WHERE matricula = ?
            LIMIT 1
        ");

        $stmt->execute([$matricula]);

        $usuario = $stmt->fetch();

        // Usuario no encontrado
        if (!$usuario) {
            jsonResponse(
                false,
                [],
                ['error' => 'Usuario no encontrado'],
                404
            );
        }

        // Usuario inactivo
        if ($usuario['estado'] !== 'activo') {
            jsonResponse(
                false,
                [],
                ['error' => 'El usuario está inactivo'],
                403
            );
        }

        // Crear sesión
        session_start();

        $_SESSION['user_id'] = (int) $usuario['id'];

        jsonResponse(true, [
            'usuario' => $usuario
        ]);
    }

    public function logout(array $params): void
    {
        // POST /api/auth/logout

        requireAuthAPI();

        session_destroy();

        jsonResponse(true);
    }

    public function me(array $params): void
    {
        // GET /api/auth/me

        $userId = requireAuthAPI();

        $stmt = $this->pdo->prepare("
            SELECT
                id,
                matricula,
                rol,
                estado,
                jugador_activo
            FROM usuarios
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$userId]);

        $usuario = $stmt->fetch();

        if (!$usuario) {
            jsonResponse(
                false,
                [],
                ['error' => 'Usuario no encontrado'],
                404
            );
        }

        jsonResponse(true, [
            'usuario' => $usuario
        ]);
    }
}