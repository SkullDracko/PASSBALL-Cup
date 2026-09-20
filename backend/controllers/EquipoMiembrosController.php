<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../security/authorization.php';

class EquipoMiembrosController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    public function listar(array $params): void
    {
        // GET /api/equipos/{equipoId}/miembros?estado=activo

      /*   requireAuthAPI(); */
        $equipoId = $this->obtenerEquipoId($params);
        $this->verificarEquipoExiste($equipoId);

        $filtros = array_merge($_GET, $params);
        $where = ['em.equipo_id = ?'];
        $values = [$equipoId];

        if (!empty($filtros['estado'])) {
            requerirEnum($filtros['estado'], ['activo', 'inactivo'], 'estado');
            $where[] = 'em.estado = ?';
            $values[] = $filtros['estado'];
        }

        $sql = '
            SELECT em.id, em.equipo_id, em.jugador_id, u.matricula,
                   em.fecha_union, em.fecha_salida, em.estado
            FROM equipo_miembros em
            JOIN usuarios u ON u.id = em.jugador_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY em.fecha_union DESC
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        jsonResponse(true, [
            'miembros' => $stmt->fetchAll()
        ]);
    }

    public function agregar(array $params): void
    {
        // POST /api/equipos/{equipoId}/miembros

        $equipoId = $this->obtenerEquipoId($params);
        $this->requireCapitanOAdmin($equipoId);
        $this->verificarEquipoExiste($equipoId);

        $body = jsonBody();
        requerirCampos($body, ['jugador_id']);

        $jugadorId = $body['jugador_id'];

        if (!filter_var($jugadorId, FILTER_VALIDATE_INT) || (int) $jugadorId <= 0) {
            jsonResponse(false, [], [
                'error' => 'jugador_id inválido'
            ], 400);
        }

        $jugadorId = (int) $jugadorId;

        $stmt = $this->pdo->prepare(
            "SELECT id FROM usuarios WHERE id = ? AND estado = 'activo' AND jugador_activo = 1 AND rol = 'jugador' LIMIT 1"
        );
        $stmt->execute([$jugadorId]);

        if (!$stmt->fetch()) {
            jsonResponse(false, [], [
                'error' => 'El jugador no existe o no está activo'
            ], 404);
        }

        $stmt = $this->pdo->prepare(
            "SELECT id FROM equipo_miembros WHERE jugador_id = ? AND estado = 'activo' LIMIT 1"
        );
        $stmt->execute([$jugadorId]);

        if ($stmt->fetch()) {
            jsonResponse(false, [], [
                'error' => 'El jugador ya pertenece a un equipo activo'
            ], 409);
        }

        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO equipo_miembros (equipo_id, jugador_id, estado)
                VALUES (?, ?, "activo")
            ');
            $stmt->execute([$equipoId, $jugadorId]);
        } catch (PDOException $e) {
            jsonResponse(false, [], [
                'error' => 'No se pudo agregar al jugador al equipo'
            ], 409);
        }

        jsonResponse(true, [
            'mensaje' => 'Jugador agregado al equipo correctamente',
            'id' => (int) $this->pdo->lastInsertId()
        ], [], 201);
    }

    public function marcarSalida(array $params): void
    {
        // PATCH /api/equipos/{equipoId}/miembros/{jugadorId}/salida

        $equipoId = $this->obtenerEquipoId($params);
        $jugadorId = $this->obtenerJugadorId($params);
        $this->requireCapitanOAdmin($equipoId);

        $stmt = $this->pdo->prepare("
            UPDATE equipo_miembros
            SET estado = 'inactivo', fecha_salida = NOW()
            WHERE equipo_id = ? AND jugador_id = ? AND estado = 'activo'
        ");
        $stmt->execute([$equipoId, $jugadorId]);

        if ($stmt->rowCount() === 0) {
            jsonResponse(false, [], [
                'error' => 'El jugador no es miembro activo de este equipo'
            ], 404);
        }

        jsonResponse(true, [
            'mensaje' => 'Salida del jugador registrada correctamente'
        ]);
    }

    public function eliminar(array $params): void
    {
        // DELETE /api/equipos/{equipoId}/miembros/{jugadorId}

        $equipoId = $this->obtenerEquipoId($params);
        $jugadorId = $this->obtenerJugadorId($params);
        $this->requireCapitanOAdmin($equipoId);

        $stmt = $this->pdo->prepare(
            'DELETE FROM equipo_miembros WHERE equipo_id = ? AND jugador_id = ?'
        );
        $stmt->execute([$equipoId, $jugadorId]);

        if ($stmt->rowCount() === 0) {
            jsonResponse(false, [], [
                'error' => 'El jugador no es miembro de este equipo'
            ], 404);
        }

        jsonResponse(true, [
            'mensaje' => 'Miembro eliminado correctamente'
        ]);
    }

    public function equipoActual(array $params): void
    {
        // GET /api/jugadores/{jugadorId}/equipo-actual

      /*   requireAuthAPI(); */
        $jugadorId = $this->obtenerJugadorId($params);

        $stmt = $this->pdo->prepare("
            SELECT e.id, e.nombre, e.logo, e.capitan_id, e.estado, em.fecha_union
            FROM equipo_miembros em
            JOIN equipos e ON e.id = em.equipo_id
            WHERE em.jugador_id = ? AND em.estado = 'activo'
            LIMIT 1
        ");
        $stmt->execute([$jugadorId]);
        $equipo = $stmt->fetch();

        jsonResponse(true, [
            'equipo' => $equipo ?: null
        ]);
    }

    public function historialEquipos(array $params): void
    {
        // GET /api/jugadores/{jugadorId}/historial-equipos

      /*   requireAuthAPI(); */
        $jugadorId = $this->obtenerJugadorId($params);

        $stmt = $this->pdo->prepare("
            SELECT e.id, e.nombre, e.logo, em.estado, em.fecha_union, em.fecha_salida
            FROM equipo_miembros em
            JOIN equipos e ON e.id = em.equipo_id
            WHERE em.jugador_id = ?
            ORDER BY em.fecha_union DESC
        ");
        $stmt->execute([$jugadorId]);

        jsonResponse(true, [
            'historial' => $stmt->fetchAll()
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

    private function obtenerEquipoId(array $params): int
    {
        $id = $params['equipoId'] ?? null;

        if (!filter_var($id, FILTER_VALIDATE_INT) || (int) $id <= 0) {
            jsonResponse(false, [], [
                'error' => 'ID de equipo inválido'
            ], 400);
        }

        return (int) $id;
    }

    private function obtenerJugadorId(array $params): int
    {
        $id = $params['jugadorId'] ?? null;

        if (!filter_var($id, FILTER_VALIDATE_INT) || (int) $id <= 0) {
            jsonResponse(false, [], [
                'error' => 'ID de jugador inválido'
            ], 400);
        }

        return (int) $id;
    }

    private function verificarEquipoExiste(int $equipoId): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM equipos WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$equipoId]);

        if (!$stmt->fetch()) {
            jsonResponse(false, [], [
                'error' => 'Equipo no encontrado'
            ], 404);
        }
    }
}
