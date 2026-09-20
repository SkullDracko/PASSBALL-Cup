<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../security/authorization.php';

class EquiposController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    public function listar(array $params): void
    {
        // GET /api/equipos?estado=activo

      /*   requireAuthAPI(); */

        $filtros = array_merge($_GET, $params);
        $where = [];
        $values = [];

        if (!empty($filtros['estado'])) {
            requerirEnum($filtros['estado'], ['activo', 'inactivo'], 'estado');
            $where[] = 'estado = ?';
            $values[] = $filtros['estado'];
        }

        $sql = '
            SELECT id, nombre, logo, capitan_id, estado, fecha_creacion
            FROM equipos
        ';

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        jsonResponse(true, [
            'equipos' => $stmt->fetchAll()
        ]);
    }

    public function crear(array $params): void
    {
        // POST /api/equipos

        $capitanId = requireJugador();
        $body = jsonBody();

        $nombre = trim((string) ($body['nombre'] ?? ''));
        $logo = array_key_exists('logo', $body) ? $body['logo'] : null;

        if ($nombre === '' || mb_strlen($nombre) > 100) {
            jsonResponse(false, [], [
                'error' => 'El nombre es obligatorio y no puede superar 100 caracteres'
            ], 400);
        }

        if ($logo !== null && (!is_string($logo) || mb_strlen($logo) > 255)) {
            jsonResponse(false, [], [
                'error' => 'El logo debe ser una cadena de hasta 255 caracteres'
            ], 400);
        }

        $stmt = $this->pdo->prepare(
            'SELECT id FROM equipos WHERE nombre = ? LIMIT 1'
        );
        $stmt->execute([$nombre]);

        if ($stmt->fetch()) {
            jsonResponse(false, [], [
                'error' => 'Ya existe un equipo con ese nombre'
            ], 409);
        }

        $stmt = $this->pdo->prepare(
            'SELECT id FROM equipo_miembros WHERE jugador_id = ? AND estado = "activo" LIMIT 1'
        );
        $stmt->execute([$capitanId]);

        if ($stmt->fetch()) {
            jsonResponse(false, [], [
                'error' => 'Ya perteneces a un equipo activo'
            ], 409);
        }

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare('
                INSERT INTO equipos (nombre, logo, capitan_id)
                VALUES (?, ?, ?)
            ');
            $stmt->execute([$nombre, $logo, $capitanId]);
            $equipoId = (int) $this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare('
                INSERT INTO equipo_miembros (equipo_id, jugador_id, estado)
                VALUES (?, ?, "activo")
            ');
            $stmt->execute([$equipoId, $capitanId]);

            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();

            jsonResponse(false, [], [
                'error' => 'No se pudo crear el equipo'
            ], 409);
        }

        jsonResponse(true, [
            'mensaje' => 'Equipo creado correctamente',
            'id' => $equipoId
        ], [], 201);
    }

    public function detalle(array $params): void
    {
        // GET /api/equipos/{id}

      /*   requireAuthAPI(); */
        $id = $this->obtenerId($params);

        $stmt = $this->pdo->prepare('
            SELECT id, nombre, logo, capitan_id, estado, fecha_creacion
            FROM equipos
            WHERE id = ?
            LIMIT 1
        ');
        $stmt->execute([$id]);
        $equipo = $stmt->fetch();

        if (!$equipo) {
            $this->noEncontrado();
        }

        jsonResponse(true, [
            'equipo' => $equipo
        ]);
    }

    public function actualizar(array $params): void
    {
        // PATCH /api/equipos/{id}

        $id = $this->obtenerId($params);
        $this->requireCapitanOAdmin($id);

        $body = jsonBody();

        if (!$body) {
            jsonResponse(false, [], [
                'error' => 'Debes enviar al menos un campo para actualizar'
            ], 400);
        }

        $campos = [];
        $values = [];

        if (array_key_exists('nombre', $body)) {
            $nombre = trim((string) $body['nombre']);

            if ($nombre === '' || mb_strlen($nombre) > 100) {
                jsonResponse(false, [], [
                    'error' => 'El nombre es obligatorio y no puede superar 100 caracteres'
                ], 400);
            }

            $stmt = $this->pdo->prepare(
                'SELECT id FROM equipos WHERE nombre = ? AND id <> ? LIMIT 1'
            );
            $stmt->execute([$nombre, $id]);

            if ($stmt->fetch()) {
                jsonResponse(false, [], [
                    'error' => 'Ya existe un equipo con ese nombre'
                ], 409);
            }

            $campos[] = 'nombre = ?';
            $values[] = $nombre;
        }

        if (array_key_exists('logo', $body)) {
            $logo = $body['logo'];

            if ($logo !== null && (!is_string($logo) || mb_strlen($logo) > 255)) {
                jsonResponse(false, [], [
                    'error' => 'El logo debe ser una cadena de hasta 255 caracteres'
                ], 400);
            }

            $campos[] = 'logo = ?';
            $values[] = $logo;
        }

        if (!$campos) {
            jsonResponse(false, [], [
                'error' => 'No hay campos válidos para actualizar'
            ], 400);
        }

        $values[] = $id;
        $stmt = $this->pdo->prepare(
            'UPDATE equipos SET ' . implode(', ', $campos) . ' WHERE id = ?'
        );
        $stmt->execute($values);

        $this->verificarExistencia($id);

        jsonResponse(true, [
            'mensaje' => 'Equipo actualizado correctamente'
        ]);
    }

    public function cambiarEstado(array $params): void
    {
        // PATCH /api/equipos/{id}/estado

        requireAdminAPI();
        $id = $this->obtenerId($params);
        $body = jsonBody();

        $estado = trim((string) ($body['estado'] ?? ''));
        requerirEnum($estado, ['activo', 'inactivo'], 'estado');

        $stmt = $this->pdo->prepare(
            'UPDATE equipos SET estado = ? WHERE id = ?'
        );
        $stmt->execute([$estado, $id]);

        $this->verificarExistencia($id);

        jsonResponse(true, [
            'mensaje' => 'Estado del equipo actualizado correctamente',
            'estado' => $estado
        ]);
    }

    public function eliminar(array $params): void
    {
        // DELETE /api/equipos/{id}

        requireAdminAPI();
        $id = $this->obtenerId($params);

        try {
            $stmt = $this->pdo->prepare('DELETE FROM equipos WHERE id = ?');
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            jsonResponse(false, [], [
                'error' => 'No se puede eliminar el equipo porque tiene registros asociados'
            ], 409);
        }

        if ($stmt->rowCount() === 0) {
            $this->noEncontrado();
        }

        jsonResponse(true, [
            'mensaje' => 'Equipo eliminado correctamente'
        ]);
    }

    // Permite que el capitán del equipo o un administrador realicen la acción
    private function requireCapitanOAdmin(int $equipoId): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!empty($_SESSION['admin_id'])) {
            requireAdminAPI();
            return;
        }

        requireCapitan($equipoId);
    }

    private function obtenerId(array $params): int
    {
        $id = $params['id'] ?? null;

        if (!filter_var($id, FILTER_VALIDATE_INT) || (int) $id <= 0) {
            jsonResponse(false, [], [
                'error' => 'ID de equipo inválido'
            ], 400);
        }

        return (int) $id;
    }

    private function verificarExistencia(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM equipos WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);

        if (!$stmt->fetch()) {
            $this->noEncontrado();
        }
    }

    private function noEncontrado(): void
    {
        jsonResponse(false, [], [
            'error' => 'Equipo no encontrado'
        ], 404);
    }
}
