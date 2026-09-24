<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../security/authorization.php';

class PartidoEventosController
{
    private PDO $pdo;
    public function __construct()
    {
        $this->pdo = conectarDB();
    }

    public function listar(array $params): void
    {
        $partidoId = $this->id($params, 'partidoId');
        $this->verificarPartido($partidoId);
        $filtros = array_merge($_GET, $params);
        $where = ['pe.partido_id = ?'];
        $values = [$partidoId];
        if (!empty($filtros['tipo'])) {
            requerirEnum($filtros['tipo'], ['gol', 'autogol', 'penal_anotado', 'tarjeta_amarilla', 'tarjeta_roja'], 'tipo');
            $where[] = 'pe.tipo = ?';
            $values[] = $filtros['tipo'];
        }
        foreach (['jugador_id', 'equipo_id'] as $campo) if (isset($filtros[$campo]) && $filtros[$campo] !== '') {
            $where[] = "pe.{$campo} = ?";
            $values[] = $this->id($filtros, $campo);
        }
        $stmt = $this->pdo->prepare('
            SELECT pe.*, u.matricula, e.nombre AS equipo_nombre, a.matricula AS asistencia_matricula
            FROM partido_eventos pe INNER JOIN usuarios u ON u.id = pe.jugador_id
            INNER JOIN equipos e ON e.id = pe.equipo_id LEFT JOIN usuarios a ON a.id = pe.asistencia_jugador_id
            WHERE ' . implode(' AND ', $where) . ' ORDER BY pe.minuto, pe.id
        ');
        $stmt->execute($values);
        jsonResponse(true, ['eventos' => $stmt->fetchAll()]);
    }

    public function registrar(array $params): void
    {
        requireAdminAPI();
        $partidoId = $this->id($params, 'partidoId');
        $this->verificarPartido($partidoId);
        $body = jsonBody();
        $jugadorId = $this->id($body, 'jugador_id');
        $equipoId = $this->id($body, 'equipo_id');
        $tipo = (string) ($body['tipo'] ?? '');
        requerirEnum($tipo, ['gol', 'autogol', 'penal_anotado', 'tarjeta_amarilla', 'tarjeta_roja'], 'tipo');
        $minuto = $body['minuto'] ?? null;
        $asistenciaId = $body['asistencia_jugador_id'] ?? null;
        $this->validarMinuto($minuto);
        $asistenciaId = $asistenciaId === null ? null : $this->id(['id' => $asistenciaId], 'id');
        $this->verificarJugadorEquipo($partidoId, $jugadorId, $equipoId);
        if ($asistenciaId !== null) $this->verificarJugadorConvocado($partidoId, $asistenciaId);
        if ($asistenciaId !== null && !in_array($tipo, ['gol', 'penal_anotado'], true)) jsonResponse(false, [], ['error' => 'La asistencia solo aplica a goles o penales anotados'], 422);
        $stmt = $this->pdo->prepare('INSERT INTO partido_eventos (partido_id, jugador_id, equipo_id, tipo, minuto, asistencia_jugador_id) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$partidoId, $jugadorId, $equipoId, $tipo, $minuto, $asistenciaId]);
        jsonResponse(true, ['mensaje' => 'Evento registrado correctamente', 'id' => (int) $this->pdo->lastInsertId()], [], 201);
    }

    public function actualizar(array $params): void
    {
        requireAdminAPI();
        $partidoId = $this->id($params, 'partidoId');
        $eventoId = $this->id($params, 'eventoId');
        $this->verificarEvento($partidoId, $eventoId);
        $body = jsonBody();
        $campos = [];
        $values = [];
        foreach (['jugador_id', 'equipo_id', 'asistencia_jugador_id'] as $campo) if (array_key_exists($campo, $body)) {
            $valor = $body[$campo] === null ? null : $this->id(['id' => $body[$campo]], 'id');
            $campos[] = "{$campo} = ?";
            $values[] = $valor;
        }
        if (array_key_exists('tipo', $body)) {
            requerirEnum((string) $body['tipo'], ['gol', 'autogol', 'penal_anotado', 'tarjeta_amarilla', 'tarjeta_roja'], 'tipo');
            $campos[] = 'tipo = ?';
            $values[] = $body['tipo'];
        }
        if (array_key_exists('minuto', $body)) {
            $this->validarMinuto($body['minuto']);
            $campos[] = 'minuto = ?';
            $values[] = $body['minuto'];
        }
        if (!$campos) jsonResponse(false, [], ['error' => 'No hay campos válidos para actualizar'], 400);
        $values[] = $partidoId;
        $values[] = $eventoId;
        $stmt = $this->pdo->prepare('UPDATE partido_eventos SET ' . implode(', ', $campos) . ' WHERE partido_id = ? AND id = ?');
        $stmt->execute($values);
        jsonResponse(true, ['mensaje' => 'Evento actualizado correctamente']);
    }

    public function eliminar(array $params): void
    {
        requireAdminAPI();
        $partidoId = $this->id($params, 'partidoId');
        $eventoId = $this->id($params, 'eventoId');
        $stmt = $this->pdo->prepare('DELETE FROM partido_eventos WHERE partido_id = ? AND id = ?');
        $stmt->execute([$partidoId, $eventoId]);
        if ($stmt->rowCount() === 0) $this->noEncontrado();
        jsonResponse(true, ['mensaje' => 'Evento eliminado correctamente']);
    }

    private function id(array $datos, string $campo): int
    {
        $id = $datos[$campo] ?? null;
        if (!filter_var($id, FILTER_VALIDATE_INT) || (int) $id <= 0) jsonResponse(false, [], ['error' => "ID inválido para {$campo}"], 400);
        return (int) $id;
    }
    private function verificarPartido(int $id): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM partidos WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) jsonResponse(false, [], ['error' => 'Partido no encontrado'], 404);
    }
    private function verificarJugadorEquipo(int $partidoId, int $jugadorId, int $equipoId): void
    {
        $stmt = $this->pdo->prepare('SELECT pc.id FROM partido_convocados pc INNER JOIN partidos p ON p.id = pc.partido_id WHERE pc.partido_id = ? AND pc.jugador_id = ? AND pc.equipo_id = ?');
        $stmt->execute([$partidoId, $jugadorId, $equipoId]);
        if (!$stmt->fetch()) jsonResponse(false, [], ['error' => 'El jugador no está convocado con ese equipo'], 422);
    }
    private function verificarJugadorConvocado(int $partidoId, int $jugadorId): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM partido_convocados WHERE partido_id = ? AND jugador_id = ?');
        $stmt->execute([$partidoId, $jugadorId]);
        if (!$stmt->fetch()) jsonResponse(false, [], ['error' => 'El asistente no está convocado'], 422);
    }
    private function verificarEvento(int $partidoId, int $eventoId): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM partido_eventos WHERE partido_id = ? AND id = ?');
        $stmt->execute([$partidoId, $eventoId]);
        if (!$stmt->fetch()) $this->noEncontrado();
    }
    private function validarMinuto($minuto): void
    {
        if ($minuto !== null && (!filter_var($minuto, FILTER_VALIDATE_INT) || (int) $minuto < 0 || (int) $minuto > 255)) jsonResponse(false, [], ['error' => 'El minuto debe ser un entero entre 0 y 255'], 422);
    }
    private function noEncontrado(): void
    {
        jsonResponse(false, [], ['error' => 'Evento no encontrado'], 404);
    }
}
