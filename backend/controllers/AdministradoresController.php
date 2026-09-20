<?php
require_once __DIR__ . '/../core/db.php';

class AdministradoresController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = conectarDB();
    }

    public function listar(array $params): void
    {
        // GET /api/administradores

        requireAdminAPI();

        $filtros = array_merge($_GET, $params);
        $where = [];
        $values = [];

        if (isset($filtros['activo'])) {
            $activo = filter_var(
                $filtros['activo'],
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );

            if ($activo === null) {
                jsonResponse(false, [], [
                    'error' => 'activo debe ser booleano'
                ], 400);
            }

            $where[] = 'activo = ?';
            $values[] = $activo ? 1 : 0;
        }

        $sql = '
            SELECT id, nombre, usuario, activo, fecha_alta
            FROM administradores
        ';

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        jsonResponse(true, [
            'administradores' => $stmt->fetchAll()
        ]);
    }

    public function crear(array $params): void
    {
        // POST /api/administradores

        // TODO: Activar cuando exista un flujo autorizado para crear administradores.
        // /* requireAdminAPI(); */

        $body = jsonBody();
        $nombre = trim((string) ($body['nombre'] ?? ''));
        $usuario = trim((string) ($body['usuario'] ?? ''));
        $contrasena = (string) ($body['contrasena'] ?? '');

        if ($nombre === '' || mb_strlen($nombre) > 100) {
            jsonResponse(false, [], [
                'error' => 'El nombre es obligatorio y no puede superar 100 caracteres'
            ], 400);
        }

        if ($usuario === '' || mb_strlen($usuario) > 50) {
            jsonResponse(false, [], [
                'error' => 'El usuario es obligatorio y no puede superar 50 caracteres'
            ], 400);
        }

        if (mb_strlen($contrasena) < 8) {
            jsonResponse(false, [], [
                'error' => 'La contraseña debe tener al menos 8 caracteres'
            ], 400);
        }

        $stmt = $this->pdo->prepare(
            'SELECT id FROM administradores WHERE usuario = ? LIMIT 1'
        );
        $stmt->execute([$usuario]);

        if ($stmt->fetch()) {
            jsonResponse(false, [], [
                'error' => 'El usuario administrador ya existe'
            ], 409);
        }

        $contrasenaHash = password_hash($contrasena, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare('
            INSERT INTO administradores (nombre, usuario, contrasena)
            VALUES (?, ?, ?)
        ');
        $stmt->execute([$nombre, $usuario, $contrasenaHash]);

        jsonResponse(true, [
            'mensaje' => 'Administrador creado correctamente',
            'id' => (int) $this->pdo->lastInsertId()
        ], [], 201);
    }

    public function detalle(array $params): void
    {
        // GET /api/administradores/{id}

        /* requireAdminAPI(); */
        $id = $this->obtenerId($params);

        $stmt = $this->pdo->prepare('
            SELECT id, nombre, usuario, activo, fecha_alta
            FROM administradores
            WHERE id = ?
            LIMIT 1
        ');
        $stmt->execute([$id]);
        $administrador = $stmt->fetch();

        if (!$administrador) {
            $this->noEncontrado();
        }

        jsonResponse(true, [
            'administrador' => $administrador
        ]);
    }

    public function actualizar(array $params): void
    {
        // PATCH /api/administradores/{id}

        /* requireAdminAPI(); */
        $id = $this->obtenerId($params);
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

            $campos[] = 'nombre = ?';
            $values[] = $nombre;
        }

        if (array_key_exists('usuario', $body)) {
            $usuario = trim((string) $body['usuario']);

            if ($usuario === '' || mb_strlen($usuario) > 50) {
                jsonResponse(false, [], [
                    'error' => 'El usuario es obligatorio y no puede superar 50 caracteres'
                ], 400);
            }

            $stmt = $this->pdo->prepare(
                'SELECT id FROM administradores WHERE usuario = ? AND id <> ? LIMIT 1'
            );
            $stmt->execute([$usuario, $id]);

            if ($stmt->fetch()) {
                jsonResponse(false, [], [
                    'error' => 'El usuario administrador ya existe'
                ], 409);
            }

            $campos[] = 'usuario = ?';
            $values[] = $usuario;
        }

        if (array_key_exists('contrasena', $body)) {
            $contrasena = (string) $body['contrasena'];

            if (mb_strlen($contrasena) < 8) {
                jsonResponse(false, [], [
                    'error' => 'La contraseña debe tener al menos 8 caracteres'
                ], 400);
            }

            $campos[] = 'contrasena = ?';
            $values[] = password_hash($contrasena, PASSWORD_DEFAULT);
        }

        if (!$campos) {
            jsonResponse(false, [], [
                'error' => 'No hay campos válidos para actualizar'
            ], 400);
        }

        $values[] = $id;
        $stmt = $this->pdo->prepare(
            'UPDATE administradores SET ' . implode(', ', $campos) . ' WHERE id = ?'
        );
        $stmt->execute($values);

        $this->verificarExistencia($id);

        jsonResponse(true, [
            'mensaje' => 'Administrador actualizado correctamente'
        ]);
    }

    public function cambiarActivo(array $params): void
    {
        // PATCH /api/administradores/{id}/activo

        /* requireAdminAPI(); */
        $id = $this->obtenerId($params);
        $body = jsonBody();

        if (!array_key_exists('activo', $body)) {
            jsonResponse(false, [], [
                'error' => 'El campo activo es obligatorio'
            ], 400);
        }

        $activo = filter_var(
            $body['activo'],
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        if ($activo === null) {
            jsonResponse(false, [], [
                'error' => 'activo debe ser booleano'
            ], 400);
        }

        $stmt = $this->pdo->prepare(
            'UPDATE administradores SET activo = ? WHERE id = ?'
        );
        $stmt->execute([$activo ? 1 : 0, $id]);

        $this->verificarExistencia($id);

        jsonResponse(true, [
            'mensaje' => 'Estado del administrador actualizado correctamente',
            'activo' => $activo
        ]);
    }

    public function eliminar(array $params): void
    {
        // DELETE /api/administradores/{id}

        /* requireAdminAPI(); */
        $id = $this->obtenerId($params);

        $stmt = $this->pdo->prepare(
            'DELETE FROM administradores WHERE id = ?'
        );
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            $this->noEncontrado();
        }

        jsonResponse(true, [
            'mensaje' => 'Administrador eliminado correctamente'
        ]);
    }

    private function obtenerId(array $params): int
    {
        $id = $params['id'] ?? null;

        if (!filter_var($id, FILTER_VALIDATE_INT) || (int) $id <= 0) {
            jsonResponse(false, [], [
                'error' => 'ID de administrador inválido'
            ], 400);
        }

        return (int) $id;
    }

    private function verificarExistencia(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM administradores WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);

        if (!$stmt->fetch()) {
            $this->noEncontrado();
        }
    }

    private function noEncontrado(): void
    {
        jsonResponse(false, [], [
            'error' => 'Administrador no encontrado'
        ], 404);
    }
}
