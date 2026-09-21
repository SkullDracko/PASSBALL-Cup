<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../security/authorization.php';

class TorneoEquiposController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    public function listar(array $params): void
    {
        // GET /api/torneos/{torneoId}/equipos?estado=aprobado

        $torneoId = $this->obtenerId($params, 'torneoId', 'torneo');
        $this->verificarTorneo($torneoId);
        $filtros = array_merge($_GET, $params);
        $where = ['te.torneo_id = ?'];
        $values = [$torneoId];

        if (!empty($filtros['estado'])) {
            requerirEnum($filtros['estado'], ['pendiente', 'aprobado', 'rechazado', 'retirado'], 'estado');
            $where[] = 'te.estado = ?';
            $values[] = $filtros['estado'];
        }

        $stmt = $this->pdo->prepare('
            SELECT
                te.id,
                te.torneo_id,
                te.equipo_id,
                e.nombre AS equipo_nombre,
                e.logo AS equipo_logo,
                e.capitan_id,
                te.estado,
                te.fecha_solicitud,
                te.fecha_aprobacion,
                te.aprobado_por
            FROM torneo_equipos te
            INNER JOIN equipos e ON e.id = te.equipo_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY te.fecha_solicitud DESC, te.id DESC
        ');
        $stmt->execute($values);

        jsonResponse(true, [
            'inscripciones' => $stmt->fetchAll()
        ]);
    }

    public function solicitar(array $params): void
    {
        // POST /api/torneos/{torneoId}/equipos

        $torneoId = $this->obtenerId($params, 'torneoId', 'torneo');
        $this->verificarTorneo($torneoId);
        $body = jsonBody();
        $equipoId = $this->obtenerId($body, 'equipo_id', 'equipo');

        requireCapitan($equipoId);
        $this->verificarEquipoActivo($equipoId);

        $stmt = $this->pdo->prepare(
            'SELECT estado FROM torneo_equipos WHERE torneo_id = ? AND equipo_id = ? LIMIT 1'
        );
        $stmt->execute([$torneoId, $equipoId]);

        if ($stmt->fetch()) {
            jsonResponse(false, [], [
                'error' => 'El equipo ya tiene una inscripción en este torneo'
            ], 409);
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO torneo_equipos (torneo_id, equipo_id, estado)
            VALUES (?, ?, "pendiente")
        ');
        $stmt->execute([$torneoId, $equipoId]);

        jsonResponse(true, [
            'mensaje' => 'Solicitud de inscripción enviada correctamente',
            'id' => (int) $this->pdo->lastInsertId()
        ], [], 201);
    }

    public function aprobar(array $params): void
    {
        // PATCH /api/torneos/{torneoId}/equipos/{equipoId}/aprobar

        $adminId = requireAdminAPI();
        $torneoId = $this->obtenerId($params, 'torneoId', 'torneo');
        $equipoId = $this->obtenerId($params, 'equipoId', 'equipo');
        $this->verificarTorneo($torneoId);
        $this->verificarInscripcion($torneoId, $equipoId, 'pendiente');

        $stmt = $this->pdo->prepare('
            UPDATE torneo_equipos
            SET estado = "aprobado", fecha_aprobacion = CURRENT_TIMESTAMP, aprobado_por = ?
            WHERE torneo_id = ? AND equipo_id = ? AND estado = "pendiente"
        ');
        $stmt->execute([$adminId, $torneoId, $equipoId]);

        if ($stmt->rowCount() === 0) {
            jsonResponse(false, [], [
                'error' => 'La solicitud ya no está pendiente'
            ], 409);
        }

        jsonResponse(true, [
            'mensaje' => 'Equipo aprobado correctamente'
        ]);
    }

    public function rechazar(array $params): void
    {
        // PATCH /api/torneos/{torneoId}/equipos/{equipoId}/rechazar

        requireAdminAPI();
        $torneoId = $this->obtenerId($params, 'torneoId', 'torneo');
        $equipoId = $this->obtenerId($params, 'equipoId', 'equipo');
        $this->verificarTorneo($torneoId);
        $this->verificarInscripcion($torneoId, $equipoId, 'pendiente');

        $stmt = $this->pdo->prepare('
            UPDATE torneo_equipos
            SET estado = "rechazado", fecha_aprobacion = NULL, aprobado_por = NULL
            WHERE torneo_id = ? AND equipo_id = ? AND estado = "pendiente"
        ');
        $stmt->execute([$torneoId, $equipoId]);

        if ($stmt->rowCount() === 0) {
            jsonResponse(false, [], [
                'error' => 'La solicitud ya no está pendiente'
            ], 409);
        }

        jsonResponse(true, [
            'mensaje' => 'Solicitud rechazada correctamente'
        ]);
    }

    public function retirar(array $params): void
    {
        // PATCH /api/torneos/{torneoId}/equipos/{equipoId}/retirar

        $torneoId = $this->obtenerId($params, 'torneoId', 'torneo');
        $equipoId = $this->obtenerId($params, 'equipoId', 'equipo');
        $this->verificarTorneo($torneoId);

        if ($this->haySesionAdmin()) {
            requireAdminAPI();
        } else {
            requireCapitan($equipoId);
        }

        $this->verificarInscripcion($torneoId, $equipoId, 'aprobado');

        $stmt = $this->pdo->prepare('
            UPDATE torneo_equipos
            SET estado = "retirado"
            WHERE torneo_id = ? AND equipo_id = ? AND estado = "aprobado"
        ');
        $stmt->execute([$torneoId, $equipoId]);

        if ($stmt->rowCount() === 0) {
            jsonResponse(false, [], [
                'error' => 'La inscripción ya no está aprobada'
            ], 409);
        }

        jsonResponse(true, [
            'mensaje' => 'Equipo retirado correctamente'
        ]);
    }

    private function obtenerId(array $datos, string $campo, string $entidad): int
    {
        $id = $datos[$campo] ?? null;

        if (!filter_var($id, FILTER_VALIDATE_INT) || (int) $id <= 0) {
            jsonResponse(false, [], [
                'error' => "ID de {$entidad} inválido"
            ], 400);
        }

        return (int) $id;
    }

    private function verificarTorneo(int $torneoId): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM torneos WHERE id = ? LIMIT 1');
        $stmt->execute([$torneoId]);

        if (!$stmt->fetch()) {
            jsonResponse(false, [], [
                'error' => 'Torneo no encontrado'
            ], 404);
        }
    }

    private function verificarEquipoActivo(int $equipoId): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM equipos WHERE id = ? AND estado = "activo" LIMIT 1'
        );
        $stmt->execute([$equipoId]);

        if (!$stmt->fetch()) {
            jsonResponse(false, [], [
                'error' => 'Equipo no encontrado o inactivo'
            ], 404);
        }
    }

    private function verificarInscripcion(int $torneoId, int $equipoId, string $estado): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM torneo_equipos WHERE torneo_id = ? AND equipo_id = ? AND estado = ? LIMIT 1'
        );
        $stmt->execute([$torneoId, $equipoId, $estado]);

        if (!$stmt->fetch()) {
            jsonResponse(false, [], [
                'error' => "No existe una inscripción {$estado} para ese equipo en el torneo"
            ], 409);
        }
    }

    private function haySesionAdmin(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        return !empty($_SESSION['admin_id']);
    }
}


