<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../security/authorization.php';

class PartidoPorterosController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    public function listar(array $params): void
    {
        $partidoId = $this->id($params, 'partidoId'); $this->verificarPartido($partidoId);
        $stmt = $this->pdo->prepare('
            SELECT pe.id, pe.partido_id, pe.jugador_id, u.matricula, pe.atajadas, pe.goles_recibidos
            FROM partido_estadisticas_portero pe INNER JOIN usuarios u ON u.id = pe.jugador_id
            WHERE pe.partido_id = ? ORDER BY pe.id
        '); $stmt->execute([$partidoId]);
        jsonResponse(true, ['porteros' => $stmt->fetchAll()]);
    }

    public function registrar(array $params): void
    {
        requireAdminAPI(); $partidoId = $this->id($params, 'partidoId'); $this->verificarPartido($partidoId); $body = jsonBody();
        $jugadorId = $this->id($body, 'jugador_id'); $atajadas = $this->numero($body['atajadas'] ?? null, 'atajadas'); $goles = $this->numero($body['goles_recibidos'] ?? null, 'goles_recibidos');
        $this->verificarPorteroConvocado($partidoId, $jugadorId);
        try { $stmt = $this->pdo->prepare('INSERT INTO partido_estadisticas_portero (partido_id, jugador_id, atajadas, goles_recibidos) VALUES (?, ?, ?, ?)'); $stmt->execute([$partidoId, $jugadorId, $atajadas, $goles]); }
        catch (PDOException $e) { jsonResponse(false, [], ['error' => 'El portero ya tiene estadísticas registradas para este partido'], 409); }
        jsonResponse(true, ['mensaje' => 'Estadística de portero registrada correctamente', 'id' => (int) $this->pdo->lastInsertId()], [], 201);
    }

    public function actualizar(array $params): void
    {
        requireAdminAPI(); $partidoId = $this->id($params, 'partidoId'); $jugadorId = $this->id($params, 'jugadorId'); $this->verificarRegistro($partidoId, $jugadorId); $body = jsonBody(); $campos = []; $values = [];
        foreach (['atajadas', 'goles_recibidos'] as $campo) if (array_key_exists($campo, $body)) { $campos[] = "{$campo} = ?"; $values[] = $this->numero($body[$campo], $campo); }
        if (!$campos) jsonResponse(false, [], ['error' => 'No hay campos válidos para actualizar'], 400);
        $values[] = $partidoId; $values[] = $jugadorId; $stmt = $this->pdo->prepare('UPDATE partido_estadisticas_portero SET ' . implode(', ', $campos) . ' WHERE partido_id = ? AND jugador_id = ?'); $stmt->execute($values);
        jsonResponse(true, ['mensaje' => 'Estadística de portero actualizada correctamente']);
    }

    public function eliminar(array $params): void
    {
        requireAdminAPI(); $partidoId = $this->id($params, 'partidoId'); $jugadorId = $this->id($params, 'jugadorId'); $stmt = $this->pdo->prepare('DELETE FROM partido_estadisticas_portero WHERE partido_id = ? AND jugador_id = ?'); $stmt->execute([$partidoId, $jugadorId]); if ($stmt->rowCount() === 0) $this->noEncontrado(); jsonResponse(true, ['mensaje' => 'Estadística de portero eliminada correctamente']);
    }

    private function id(array $datos, string $campo): int { $id = $datos[$campo] ?? null; if (!filter_var($id, FILTER_VALIDATE_INT) || (int) $id <= 0) jsonResponse(false, [], ['error' => "ID inválido para {$campo}"], 400); return (int) $id; }
    private function numero($valor, string $campo): int { if (!filter_var($valor, FILTER_VALIDATE_INT) || (int) $valor < 0 || (int) $valor > 32767) jsonResponse(false, [], ['error' => "{$campo} debe ser un entero entre 0 y 32767"], 422); return (int) $valor; }
    private function verificarPartido(int $id): void { $stmt = $this->pdo->prepare('SELECT id FROM partidos WHERE id = ? LIMIT 1'); $stmt->execute([$id]); if (!$stmt->fetch()) jsonResponse(false, [], ['error' => 'Partido no encontrado'], 404); }
    private function verificarPorteroConvocado(int $partidoId, int $jugadorId): void { $stmt = $this->pdo->prepare('SELECT id FROM partido_convocados WHERE partido_id = ? AND jugador_id = ? AND posicion = "portero"'); $stmt->execute([$partidoId, $jugadorId]); if (!$stmt->fetch()) jsonResponse(false, [], ['error' => 'El jugador debe estar convocado como portero'], 422); }
    private function verificarRegistro(int $partidoId, int $jugadorId): void { $stmt = $this->pdo->prepare('SELECT id FROM partido_estadisticas_portero WHERE partido_id = ? AND jugador_id = ?'); $stmt->execute([$partidoId, $jugadorId]); if (!$stmt->fetch()) $this->noEncontrado(); }
    private function noEncontrado(): void { jsonResponse(false, [], ['error' => 'Estadística de portero no encontrada'], 404); }
}
