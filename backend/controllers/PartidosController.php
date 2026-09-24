<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../security/authorization.php';
require_once __DIR__ . '/../services/FinalizarPartido.php';

class PartidosController
{
    private PDO $pdo;
    public function __construct()
    {
        $this->pdo = conectarDB();
    }

    public function listar(array $params): void
    {
        $filtros = array_merge($_GET, $params);
        $where = [];
        $values = [];

        foreach (['ronda_id', 'equipo_id'] as $campo) {
            if (isset($filtros[$campo]) && $filtros[$campo] !== '') {
                $where[] = $campo === 'ronda_id'
                    ? 'p.ronda_id = ?'
                    : '(p.equipo_local_id = ? OR p.equipo_visitante_id = ?)';
                $id = $this->obtenerId(['id' => $filtros[$campo]], $campo);
                $values = $campo === 'ronda_id' ? [...$values, $id] : [...$values, $id, $id];
            }
        }

        if (!empty($filtros['estado'])) {
            requerirEnum($filtros['estado'], ['programado', 'en_curso', 'finalizado', 'cancelado'], 'estado');
            $where[] = 'p.estado = ?';
            $values[] = $filtros['estado'];
        }

        $sql = '
            SELECT p.*, r.torneo_id, r.nombre AS ronda_nombre, r.orden AS ronda_orden,
                   el.nombre AS equipo_local_nombre, ev.nombre AS equipo_visitante_nombre
            FROM partidos p
            INNER JOIN torneo_rondas r ON r.id = p.ronda_id
            LEFT JOIN equipos el ON el.id = p.equipo_local_id
            LEFT JOIN equipos ev ON ev.id = p.equipo_visitante_id
        ';
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY r.orden ASC, p.posicion ASC, p.id ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
        jsonResponse(true, ['partidos' => $stmt->fetchAll()]);
    }

    public function crear(array $params): void
    {
        requireAdminAPI();
        $body = jsonBody();
        $rondaId = $this->obtenerId($body, 'ronda_id');
        $posicion = $this->obtenerPositivo($body['posicion'] ?? null, 'posicion');
        $this->verificarRonda($rondaId);

        $equipoLocal = $this->idOpcional($body['equipo_local_id'] ?? null, 'equipo_local_id');
        $equipoVisitante = $this->idOpcional($body['equipo_visitante_id'] ?? null, 'equipo_visitante_id');
        $origenLocal = $this->idOpcional($body['partido_origen_local_id'] ?? null, 'partido_origen_local_id');
        $origenVisitante = $this->idOpcional($body['partido_origen_visitante_id'] ?? null, 'partido_origen_visitante_id');

        $tieneEquipos = $equipoLocal !== null || $equipoVisitante !== null;
        $tieneOrigenes = $origenLocal !== null || $origenVisitante !== null;
        if ($tieneEquipos === $tieneOrigenes) {
            jsonResponse(false, [], ['error' => 'Debes enviar equipos concretos o partidos origen, pero no ambos'], 422);
        }
        if ($equipoLocal !== null && $equipoVisitante !== null && $equipoLocal === $equipoVisitante) {
            jsonResponse(false, [], ['error' => 'Los equipos local y visitante deben ser distintos'], 422);
        }
        if ($origenLocal !== null) $this->verificarPartidoOrigen($origenLocal, $rondaId);
        if ($origenVisitante !== null) $this->verificarPartidoOrigen($origenVisitante, $rondaId);

        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO partidos
                    (ronda_id, equipo_local_id, equipo_visitante_id, partido_origen_local_id, partido_origen_visitante_id, posicion)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$rondaId, $equipoLocal, $equipoVisitante, $origenLocal, $origenVisitante, $posicion]);
        } catch (PDOException $e) {
            jsonResponse(false, [], ['error' => 'No se pudo crear el partido'], 409);
        }
        jsonResponse(true, ['mensaje' => 'Partido creado correctamente', 'id' => (int) $this->pdo->lastInsertId()], [], 201);
    }

    public function detalle(array $params): void
    {
        $id = $this->obtenerId($params, 'id');
        $stmt = $this->pdo->prepare('
            SELECT p.*, r.torneo_id, r.nombre AS ronda_nombre, r.orden AS ronda_orden,
                   el.nombre AS equipo_local_nombre, ev.nombre AS equipo_visitante_nombre
            FROM partidos p
            INNER JOIN torneo_rondas r ON r.id = p.ronda_id
            LEFT JOIN equipos el ON el.id = p.equipo_local_id
            LEFT JOIN equipos ev ON ev.id = p.equipo_visitante_id
            WHERE p.id = ? LIMIT 1
        ');
        $stmt->execute([$id]);
        $partido = $stmt->fetch();
        if (!$partido) $this->noEncontrado();
        jsonResponse(true, ['partido' => $partido]);
    }

    public function actualizar(array $params): void
    {
        requireAdminAPI();
        $id = $this->obtenerId($params, 'id');
        $this->verificarPartido($id);
        $body = jsonBody();
        $campos = [];
        $values = [];

        if (array_key_exists('fecha_hora', $body)) {
            if ($body['fecha_hora'] !== null && !is_string($body['fecha_hora'])) {
                jsonResponse(false, [], ['error' => 'fecha_hora debe ser una fecha válida o null'], 422);
            }
            $campos[] = 'fecha_hora = ?';
            $values[] = $body['fecha_hora'];
        }
        if (array_key_exists('cancha', $body)) {
            if ($body['cancha'] !== null && (!is_string($body['cancha']) || mb_strlen($body['cancha']) > 100)) {
                jsonResponse(false, [], ['error' => 'cancha debe tener máximo 100 caracteres'], 422);
            }
            $campos[] = 'cancha = ?';
            $values[] = $body['cancha'];
        }
        if (array_key_exists('estado', $body)) {
            requerirEnum((string) $body['estado'], ['programado', 'en_curso', 'finalizado', 'cancelado'], 'estado');
            $campos[] = 'estado = ?';
            $values[] = $body['estado'];
        }
        if (!$campos) jsonResponse(false, [], ['error' => 'No hay campos válidos para actualizar'], 400);
        $values[] = $id;
        $stmt = $this->pdo->prepare('UPDATE partidos SET ' . implode(', ', $campos) . ' WHERE id = ?');
        $stmt->execute($values);
        jsonResponse(true, ['mensaje' => 'Partido actualizado correctamente']);
    }

    public function resultado(array $params): void
    {
        requireAdminAPI();
        $id = $this->obtenerId($params, 'id');
        $this->verificarPartido($id);
        $body = jsonBody();
        $golesLocal = $this->obtenerNoNegativo($body['goles_local'] ?? null, 'goles_local');
        $golesVisitante = $this->obtenerNoNegativo($body['goles_visitante'] ?? null, 'goles_visitante');
        $penalesLocal = $this->obtenerNoNegativo($body['penales_local'] ?? 0, 'penales_local');
        $penalesVisitante = $this->obtenerNoNegativo($body['penales_visitante'] ?? 0, 'penales_visitante');
        $ganadorId = $this->idOpcional($body['ganador_id'] ?? null, 'ganador_id');

        $stmt = $this->pdo->prepare('SELECT equipo_local_id, equipo_visitante_id FROM partidos WHERE id = ?');
        $stmt->execute([$id]);
        $partido = $stmt->fetch();
        if ($ganadorId !== null && !in_array($ganadorId, [(int) $partido['equipo_local_id'], (int) $partido['equipo_visitante_id']], true)) {
            jsonResponse(false, [], ['error' => 'El ganador debe ser uno de los equipos del partido'], 422);
        }

        $stmt = $this->pdo->prepare('
            UPDATE partidos SET goles_local = ?, goles_visitante = ?, penales_local = ?, penales_visitante = ?, ganador_id = ?
            WHERE id = ?
        ');
        $stmt->execute([$golesLocal, $golesVisitante, $penalesLocal, $penalesVisitante, $ganadorId, $id]);
        jsonResponse(true, ['mensaje' => 'Resultado registrado correctamente']);
    }

    public function eliminar(array $params): void
    {
        requireAdminAPI();
        $id = $this->obtenerId($params, 'id');
        $eliminado = false;
        try {
            $stmt = $this->pdo->prepare('DELETE FROM partidos WHERE id = ?');
            $stmt->execute([$id]);
            $eliminado = $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            jsonResponse(false, [], ['error' => 'No se puede eliminar el partido porque tiene registros asociados'], 409);
        }
        if (!$eliminado) $this->noEncontrado();
        jsonResponse(true, ['mensaje' => 'Partido eliminado correctamente']);
    }

    public function finalizar(array $params): void
    {
        // PATCH /api/partidos/{id}/finalizar
        requireAdminAPI();
        $id = $this->obtenerId($params, 'id');
        try {
            FinalizarPartido::ejecutar($this->pdo, $id);
        } catch (Throwable $e) {
            jsonResponse(false, [], ['error' => 'No se pudo finalizar el partido'], 409);
        }
        jsonResponse(true, ['mensaje' => 'Partido finalizado correctamente']);
    }

    private function obtenerId(array $datos, string $campo): int
    {
        $id = $datos[$campo] ?? null;
        if (!filter_var($id, FILTER_VALIDATE_INT) || (int) $id <= 0) {
            jsonResponse(false, [], ['error' => "ID inválido para {$campo}"], 400);
        }
        return (int) $id;
    }
    private function idOpcional($valor, string $campo): ?int
    {
        if ($valor === null) return null;
        return $this->obtenerId([$campo => $valor], $campo);
    }
    private function obtenerPositivo($valor, string $campo): int
    {
        return $this->obtenerNoNegativo($valor, $campo, true);
    }
    private function obtenerNoNegativo($valor, string $campo, bool $estricto = false): int
    {
        if (!filter_var($valor, FILTER_VALIDATE_INT) || (int) $valor < ($estricto ? 1 : 0)) {
            jsonResponse(false, [], ['error' => "{$campo} debe ser un entero válido"], 422);
        }
        return (int) $valor;
    }
    private function verificarRonda(int $rondaId): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM torneo_rondas WHERE id = ? LIMIT 1');
        $stmt->execute([$rondaId]);
        if (!$stmt->fetch()) jsonResponse(false, [], ['error' => 'Ronda no encontrada'], 404);
    }
    private function verificarPartido(int $id): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM partidos WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) $this->noEncontrado();
    }
    private function verificarPartidoOrigen(int $origenId, int $rondaId): void
    {
        $stmt = $this->pdo->prepare('
            SELECT r.orden AS origen_orden, destino.orden AS destino_orden,
                   r.torneo_id AS origen_torneo_id, destino.torneo_id AS destino_torneo_id
            FROM partidos p INNER JOIN torneo_rondas r ON r.id = p.ronda_id
            INNER JOIN torneo_rondas destino ON destino.id = ?
            WHERE p.id = ?
        ');
        $stmt->execute([$rondaId, $origenId]);
        $fila = $stmt->fetch();
        if (
            !$fila
            || (int) $fila['origen_torneo_id'] !== (int) $fila['destino_torneo_id']
            || (int) $fila['origen_orden'] >= (int) $fila['destino_orden']
        ) {
            jsonResponse(false, [], ['error' => 'El partido origen debe pertenecer a una ronda anterior'], 422);
        }
    }
    private function noEncontrado(): void
    {
        jsonResponse(false, [], ['error' => 'Partido no encontrado'], 404);
    }
}
