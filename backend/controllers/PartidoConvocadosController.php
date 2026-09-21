<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../security/authorization.php';

class PartidoConvocadosController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    public function listar(array $params): void
    {
        $partidoId = $this->partidoId($params);
        $this->verificarPartido($partidoId);
        $stmt = $this->pdo->prepare('
            SELECT pc.id, pc.partido_id, pc.jugador_id, u.matricula, pc.equipo_id,
                   e.nombre AS equipo_nombre, pc.titular, pc.posicion
            FROM partido_convocados pc
            INNER JOIN usuarios u ON u.id = pc.jugador_id
            INNER JOIN equipos e ON e.id = pc.equipo_id
            WHERE pc.partido_id = ?
            ORDER BY pc.equipo_id, pc.posicion, pc.id
        ');
        $stmt->execute([$partidoId]);
        jsonResponse(true, ['convocados' => $stmt->fetchAll()]);
    }

    public function convocar(array $params): void
    {
        requireAdminAPI();
        $partidoId = $this->partidoId($params);
        $this->verificarPartido($partidoId);
        $body = jsonBody();
        $jugadorId = $this->id($body, 'jugador_id');
        $equipoId = $this->id($body, 'equipo_id');
        $titular = $this->booleano($body['titular'] ?? true, 'titular');
        $posicion = (string) ($body['posicion'] ?? '');
        requerirEnum($posicion, ['portero', 'defensa', 'mediocampo', 'delantero'], 'posicion');
        $this->verificarJugadorEquipoPartido($jugadorId, $equipoId, $partidoId);

        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO partido_convocados (partido_id, jugador_id, equipo_id, titular, posicion)
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([$partidoId, $jugadorId, $equipoId, $titular, $posicion]);
        } catch (PDOException $e) {
            jsonResponse(false, [], ['error' => 'El jugador ya está convocado para este partido'], 409);
        }
        jsonResponse(true, ['mensaje' => 'Jugador convocado correctamente', 'id' => (int) $this->pdo->lastInsertId()], [], 201);
    }

    public function actualizar(array $params): void
    {
        requireAdminAPI();
        $partidoId = $this->partidoId($params);
        $jugadorId = $this->id($params, 'jugadorId');
        $this->verificarConvocado($partidoId, $jugadorId);
        $body = jsonBody(); $campos = []; $values = [];
        if (array_key_exists('titular', $body)) { $campos[] = 'titular = ?'; $values[] = $this->booleano($body['titular'], 'titular'); }
        if (array_key_exists('posicion', $body)) { requerirEnum((string) $body['posicion'], ['portero', 'defensa', 'mediocampo', 'delantero'], 'posicion'); $campos[] = 'posicion = ?'; $values[] = $body['posicion']; }
        if (!$campos) jsonResponse(false, [], ['error' => 'No hay campos válidos para actualizar'], 400);
        $values[] = $partidoId; $values[] = $jugadorId;
        $stmt = $this->pdo->prepare('UPDATE partido_convocados SET ' . implode(', ', $campos) . ' WHERE partido_id = ? AND jugador_id = ?');
        $stmt->execute($values);
        jsonResponse(true, ['mensaje' => 'Convocatoria actualizada correctamente']);
    }

    public function eliminar(array $params): void
    {
        requireAdminAPI();
        $partidoId = $this->partidoId($params); $jugadorId = $this->id($params, 'jugadorId');
        $stmt = $this->pdo->prepare('DELETE FROM partido_convocados WHERE partido_id = ? AND jugador_id = ?');
        $stmt->execute([$partidoId, $jugadorId]);
        if ($stmt->rowCount() === 0) $this->noEncontrado();
        jsonResponse(true, ['mensaje' => 'Convocado eliminado correctamente']);
    }

    private function partidoId(array $params): int { return $this->id($params, 'partidoId'); }
    private function id(array $datos, string $campo): int
    {
        $id = $datos[$campo] ?? null;
        if (!filter_var($id, FILTER_VALIDATE_INT) || (int) $id <= 0) jsonResponse(false, [], ['error' => "ID inválido para {$campo}"], 400);
        return (int) $id;
    }
    private function booleano($valor, string $campo): int
    {
        if (!is_bool($valor) && !in_array($valor, [0, 1, '0', '1'], true)) jsonResponse(false, [], ['error' => "{$campo} debe ser booleano"], 422);
        return filter_var($valor, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }
    private function verificarPartido(int $id): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM partidos WHERE id = ? LIMIT 1'); $stmt->execute([$id]);
        if (!$stmt->fetch()) jsonResponse(false, [], ['error' => 'Partido no encontrado'], 404);
    }
    private function verificarJugadorEquipoPartido(int $jugadorId, int $equipoId, int $partidoId): void
    {
        $stmt = $this->pdo->prepare('
            SELECT em.id FROM partidos p
            INNER JOIN equipo_miembros em ON em.equipo_id IN (p.equipo_local_id, p.equipo_visitante_id)
            WHERE p.id = ? AND em.equipo_id = ? AND em.jugador_id = ? AND em.estado = "activo"
        ');
        $stmt->execute([$partidoId, $equipoId, $jugadorId]);
        if (!$stmt->fetch()) jsonResponse(false, [], ['error' => 'El jugador no pertenece activamente a un equipo del partido'], 422);
    }
    private function verificarConvocado(int $partidoId, int $jugadorId): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM partido_convocados WHERE partido_id = ? AND jugador_id = ? LIMIT 1'); $stmt->execute([$partidoId, $jugadorId]);
        if (!$stmt->fetch()) $this->noEncontrado();
    }
    private function noEncontrado(): void { jsonResponse(false, [], ['error' => 'Convocado no encontrado'], 404); }
}
