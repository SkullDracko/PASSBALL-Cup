<?php

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../services/jugadores_service.php';

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

        // Validación básica de formato (ajusta el patrón a tu formato real)
        if ($matricula === '' || !preg_match('/^\d{7}$/', $matricula)) {
            jsonResponse(false, [], ['error' => 'Matrícula inválida'], 400);
        }

        $usuario = $this->buscarUsuarioPorMatricula($matricula);

        // Si no existe, se crea (login-o-registro automático)
        if (!$usuario) {
            $usuario = $this->registrarUsuario($matricula);
        }

        if ($usuario['estado'] !== 'activo') {
            jsonResponse(false, [], ['error' => 'El usuario está inactivo'], 403);
        }

        session_start();
        $_SESSION['user_id'] = (int) $usuario['id'];

        jsonResponse(true, ['usuario' => $usuario]);
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

        $usuario = $this->buscarUsuarioPorId($userId);

        if (!$usuario) {
            jsonResponse(false, [], ['error' => 'Usuario no encontrado'], 404);
        }

        jsonResponse(true, ['usuario' => $usuario]);
    }

    // ---------------------------------------------------------
    // Privados
    // ---------------------------------------------------------

    private function buscarUsuarioPorMatricula(string $matricula): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, matricula, rol, estado, jugador_activo
            FROM usuarios
            WHERE matricula = ?
            LIMIT 1
        ");
        $stmt->execute([$matricula]);

        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    private function buscarUsuarioPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, matricula, rol, estado, jugador_activo
            FROM usuarios
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);

        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    /**
     * Crea el usuario si no existe. Si dos requests llegan en paralelo con
     * la misma matrícula nueva, el índice UNIQUE evita duplicados: el que
     * pierde la carrera cae al catch y simplemente relee el registro ganador.
     */
    private function registrarUsuario(string $matricula): array
    {
        $datosJugador = datosJugadorSiEstaInscrito($matricula);
        $esJugador = $datosJugador !== null;
        $rol = $esJugador ? 'jugador' : 'usuario';

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO usuarios (matricula, rol, estado, jugador_activo, nombre, apellidop, apellidom, semestre)
                VALUES (?, ?, 'activo', ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $matricula,
                $rol,
                $esJugador ? 1 : 0,
                $datosJugador['nombre'] ?? '',
                $datosJugador['apellidop'] ?? '',
                $datosJugador['apellidom'] ?? '',
                $datosJugador['semestre'] ?? null,
            ]);
        } catch (PDOException $e) {
            // 23000 = violación de UNIQUE -> otro request ya lo creó, no es un error real
            if ($e->getCode() !== '23000') {
                throw $e;
            }
        }

        $usuario = $this->buscarUsuarioPorMatricula($matricula);

        if (!$usuario) {
            // Esto no debería pasar nunca; si pasa, es un error real de BD
            jsonResponse(false, [], ['error' => 'No se pudo crear el usuario'], 500);
        }

        return $usuario;
    }
}