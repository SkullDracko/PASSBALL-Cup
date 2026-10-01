<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../security/authorization.php';

class EquipoMiembrosController
{
    private PDO $pdo;
    public function __construct()
    {
        $this->pdo = conectarDB();
    }

    // Endpoint nuevo: en el controlador legacy los miembros solo se incluían
    // dentro de la respuesta de detalle del equipo.
    public function listar(array $params): void
    {
        // GET /api/equipos/{equipoId}/miembros?estado=activo

        requireAuthAPI();
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

    // Alta por jugador_id; el controlador legacy invitaba mediante matrícula.
    public function agregar(array $params): void
    {
        // POST /api/equipos/{equipoId}/miembros

        $equipoId = $this->obtenerEquipoId($params);
        $usuarioActual = requireAuthAPI();
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

        if ($jugadorId === $usuarioActual) {
            requireJugador();
        } else {
            $this->requireCapitanOAdmin($equipoId);
        }

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

        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM equipo_miembros WHERE equipo_id = ? AND estado = 'activo'"
        );
        $stmt->execute([$equipoId]);

        if ((int) $stmt->fetchColumn() >= 12) {
            jsonResponse(false, [], [
                'error' => 'El equipo ya está lleno (máximo 12 miembros)'
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

    // Equivale a la baja lógica de 'salir'/'eliminar_miembro' del controlador legacy.
    public function marcarSalida(array $params): void
    {
        // PATCH /api/equipos/{equipoId}/miembros/{jugadorId}/salida

        $equipoId = $this->obtenerEquipoId($params);
        $jugadorId = $this->obtenerJugadorId($params);
        $usuarioActual = requireAuthAPI();

        if ($jugadorId === $usuarioActual) {
            requireJugador();
        } else {
            $this->requireCapitanOAdmin($equipoId);
        }

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

    // Diferencia con 'eliminar_miembro' legacy: elimina el registro físicamente.
    public function eliminar(array $params): void
    {
        // DELETE /api/equipos/{equipoId}/miembros/{jugadorId}

        $equipoId = $this->obtenerEquipoId($params);
        $jugadorId = $this->obtenerJugadorId($params);
        requireAuthAPI();
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

    // Equivale a 'mi_equipo' legacy; esta ruta recibe el jugadorId como parámetro.
    public function equipoActual(array $params): void
    {
        // GET /api/jugadores/{jugadorId}/equipo-actual

         requireAuthAPI(); 
        $jugadorId = $this->obtenerJugadorId($params);

        $stmt = $this->pdo->prepare("
    SELECT 
        e.id,
        e.nombre,
        e.logo,
        e.capitan_id,
        e.estado,
        e.motivo_solicitud,
        e.motivo_rechazo,
        (
            SELECT te.estado
            FROM torneo_equipos te
            WHERE te.equipo_id = e.id
            ORDER BY te.fecha_solicitud DESC, te.id DESC
            LIMIT 1
        ) AS estado_postulacion,
        (
            SELECT te.motivo_rechazo
            FROM torneo_equipos te
            WHERE te.equipo_id = e.id
            ORDER BY te.fecha_solicitud DESC, te.id DESC
            LIMIT 1
        ) AS motivo_rechazo_postulacion,
        em.fecha_union,
        COUNT(em2.id) AS total_miembros
    FROM equipo_miembros em
    JOIN equipos e 
        ON e.id = em.equipo_id
    LEFT JOIN equipo_miembros em2
        ON em2.equipo_id = e.id
        AND em2.estado = 'activo'
    WHERE em.jugador_id = ?
      AND em.estado = 'activo'
    GROUP BY 
        e.id,
        e.nombre,
        e.logo,
        e.capitan_id,
        e.estado,
        e.motivo_solicitud,
        e.motivo_rechazo,
        em.fecha_union
    LIMIT 1
");
        $stmt->execute([$jugadorId]);
        $equipo = $stmt->fetch();

        jsonResponse(true, [
            'equipo' => $equipo ?: null
        ]);
    }

    // Endpoint nuevo: el controlador legacy no exponía el historial de equipos.
    public function historialEquipos(array $params): void
    {
        // GET /api/jugadores/{jugadorId}/historial-equipos

        requireAuthAPI();
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
