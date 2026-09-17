<?php
// filepath: c:\xampp\htdocs\PASSBALL-Cup\backend\controllers\UsuariosController.php

require_once __DIR__ . '/../core/db.php';

class UsuariosController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = conectarDB();
    }

    public function listar(array $params): void
    {
        // GET /api/usuarios?estado=activo&jugador_activo=1&rol=admin

        requireAuthAPI();

        $filtros = array_merge($_GET, $params);

        $where = [];
        $values = [];

        if (!empty($filtros['estado'])) {
            $where[] = 'estado = ?';
            $values[] = $filtros['estado'];
        }

        if (isset($filtros['jugador_activo'])) {
            $valor = filter_var(
                $filtros['jugador_activo'],
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );

            if ($valor === null) {
                jsonResponse(false, [], [
                    'error' => 'jugador_activo debe ser booleano'
                ], 400);
            }

            $where[] = 'jugador_activo = ?';
            $values[] = $valor ? 1 : 0;
        }

        if (!empty($filtros['rol'])) {
            $where[] = 'rol = ?';
            $values[] = $filtros['rol'];
        }

        $sql = "
            SELECT
                id,
                matricula,
                rol,
                estado,
                jugador_activo
            FROM usuarios
        ";

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        jsonResponse(true, [
            'usuarios' => $stmt->fetchAll()
        ]);
    }

    public function detalle(array $params): void
    {
        // GET /api/usuarios/{id}

        requireAuthAPI();

        $id = $this->obtenerId($params);

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

        $stmt->execute([$id]);
        $usuario = $stmt->fetch();

        if (!$usuario) {
            jsonResponse(false, [], [
                'error' => 'Usuario no encontrado'
            ], 404);
        }

        jsonResponse(true, [
            'usuario' => $usuario
        ]);
    }

    public function actualizarAvatar(array $params): void
    {
        // PATCH /api/usuarios/{id}

        $id = $this->obtenerId($params);
        requirePropietarioOAdmin($id);

        $body = jsonBody();

        if (!array_key_exists('avatar', $body)) {
            jsonResponse(false, [], [
                'error' => 'El campo avatar es obligatorio'
            ], 400);
        }

        $avatar = $body['avatar'];

        if ($avatar !== null && !is_string($avatar)) {
            jsonResponse(false, [], [
                'error' => 'El avatar debe ser una cadena o null'
            ], 400);
        }

        if (is_string($avatar) && mb_strlen($avatar) > 255) {
            jsonResponse(false, [], [
                'error' => 'El avatar no puede superar 255 caracteres'
            ], 400);
        }

        $stmt = $this->pdo->prepare("
            UPDATE usuarios
            SET avatar = ?
            WHERE id = ?
        ");

        $stmt->execute([$avatar, $id]);

        if ($stmt->rowCount() === 0) {
            $this->verificarExistencia($id);
        }

        jsonResponse(true, [
            'mensaje' => 'Usuario actualizado correctamente'
        ]);
    }

    public function cambiarEstado(array $params): void
    {
        // PATCH /api/usuarios/{id}/estado

        requireAdminAPI();
        $id = $this->obtenerId($params);
        $body = jsonBody();

        $estado = trim((string) ($body['estado'] ?? ''));

        if (!in_array($estado, ['activo', 'inactivo'], true)) {
            jsonResponse(false, [], [
                'error' => 'El estado debe ser activo o inactivo'
            ], 400);
        }

        $stmt = $this->pdo->prepare("
            UPDATE usuarios
            SET estado = ?
            WHERE id = ?
        ");

        $stmt->execute([$estado, $id]);

        if ($stmt->rowCount() === 0) {
            $this->verificarExistencia($id);
        }

        jsonResponse(true, [
            'mensaje' => 'Estado actualizado correctamente',
            'estado' => $estado
        ]);
    }

    public function cambiarJugadorActivo(array $params): void
    {
        // PATCH /api/usuarios/{id}/jugador-activo

        requireAdminAPI();
        $id = $this->obtenerId($params);
        $body = jsonBody();

        if (!array_key_exists('jugador_activo', $body)) {
            jsonResponse(false, [], [
                'error' => 'El campo jugador_activo es obligatorio'
            ], 400);
        }

        $valor = filter_var(
            $body['jugador_activo'],
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        if ($valor === null) {
            jsonResponse(false, [], [
                'error' => 'jugador_activo debe ser booleano'
            ], 400);
        }

        $stmt = $this->pdo->prepare("
            UPDATE usuarios
            SET jugador_activo = ?
            WHERE id = ?
        ");

        $stmt->execute([$valor ? 1 : 0, $id]);

        if ($stmt->rowCount() === 0) {
            $this->verificarExistencia($id);
        }

        jsonResponse(true, [
            'mensaje' => 'Estado de jugador actualizado correctamente',
            'jugador_activo' => $valor
        ]);
    }

    private function obtenerId(array $params): int
    {
        $id = $params['id'] ?? null;

        if (!filter_var($id, FILTER_VALIDATE_INT) || (int) $id <= 0) {
            jsonResponse(false, [], [
                'error' => 'ID de usuario inválido'
            ], 400);
        }

        return (int) $id;
    }

    private function verificarExistencia(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM usuarios WHERE id = ? LIMIT 1'
        );

        $stmt->execute([$id]);

        if (!$stmt->fetch()) {
            jsonResponse(false, [], [
                'error' => 'Usuario no encontrado'
            ], 404);
        }
    }
}