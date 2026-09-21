<?php
require_once __DIR__ . '/../core/db.php';

class BracketController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    public function obtener(array $params): void {
        // $params: torneoId de la URL. Es una consulta de solo lectura.
        $torneoId = $this->obtenerId($params);
        $this->verificarTorneo($torneoId);

        $stmt = $this->pdo->prepare('
            SELECT
                r.id AS ronda_id,
                r.nombre AS ronda_nombre,
                r.orden AS ronda_orden,
                p.id AS partido_id,
                p.posicion,
                p.fecha_hora,
                p.cancha,
                p.estado,
                p.goles_local,
                p.goles_visitante,
                p.penales_local,
                p.penales_visitante,
                p.ganador_id,
                p.equipo_local_id,
                p.equipo_visitante_id,
                p.partido_origen_local_id,
                p.partido_origen_visitante_id,
                local.nombre AS equipo_local_nombre,
                visitante.nombre AS equipo_visitante_nombre,
                origen_local.ganador_id AS origen_local_ganador_id,
                origen_visitante.ganador_id AS origen_visitante_ganador_id
            FROM torneo_rondas r
            LEFT JOIN partidos p ON p.ronda_id = r.id
            LEFT JOIN equipos local ON local.id = p.equipo_local_id
            LEFT JOIN equipos visitante ON visitante.id = p.equipo_visitante_id
            LEFT JOIN partidos origen_local ON origen_local.id = p.partido_origen_local_id
            LEFT JOIN partidos origen_visitante ON origen_visitante.id = p.partido_origen_visitante_id
            WHERE r.torneo_id = ?
            ORDER BY r.orden ASC, r.id ASC, p.posicion ASC, p.id ASC
        ');
        $stmt->execute([$torneoId]);

        $rondas = [];
        foreach ($stmt->fetchAll() as $fila) {
            $rondaId = (int) $fila['ronda_id'];

            if (!isset($rondas[$rondaId])) {
                $rondas[$rondaId] = [
                    'id' => $rondaId,
                    'nombre' => $fila['ronda_nombre'],
                    'orden' => (int) $fila['ronda_orden'],
                    'partidos' => []
                ];
            }

            if ($fila['partido_id'] === null) {
                continue;
            }

            $rondas[$rondaId]['partidos'][] = [
                'id' => (int) $fila['partido_id'],
                'posicion' => (int) $fila['posicion'],
                'fecha_hora' => $fila['fecha_hora'],
                'cancha' => $fila['cancha'],
                'estado' => $fila['estado'],
                'goles_local' => $fila['goles_local'] === null ? null : (int) $fila['goles_local'],
                'goles_visitante' => $fila['goles_visitante'] === null ? null : (int) $fila['goles_visitante'],
                'penales_local' => $fila['penales_local'] === null ? null : (int) $fila['penales_local'],
                'penales_visitante' => $fila['penales_visitante'] === null ? null : (int) $fila['penales_visitante'],
                'ganador_id' => $fila['ganador_id'] === null ? null : (int) $fila['ganador_id'],
                'equipo_local' => $this->equipo($fila['equipo_local_id'], $fila['equipo_local_nombre']),
                'equipo_visitante' => $this->equipo($fila['equipo_visitante_id'], $fila['equipo_visitante_nombre']),
                'origen_local' => $this->origen($fila['partido_origen_local_id'], $fila['origen_local_ganador_id']),
                'origen_visitante' => $this->origen($fila['partido_origen_visitante_id'], $fila['origen_visitante_ganador_id'])
            ];
        }

        jsonResponse(true, [
            'torneo_id' => $torneoId,
            'rondas' => array_values($rondas)
        ]);
    }

    private function obtenerId(array $params): int
    {
        $id = $params['torneoId'] ?? null;

        if (!filter_var($id, FILTER_VALIDATE_INT) || (int) $id <= 0) {
            jsonResponse(false, [], [
                'error' => 'ID de torneo inválido'
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

    private function equipo($equipoId, $nombre): ?array
    {
        if ($equipoId === null) {
            return null;
        }

        return [
            'id' => (int) $equipoId,
            'nombre' => $nombre
        ];
    }

    private function origen($partidoId, $ganadorId): ?array
    {
        if ($partidoId === null) {
            return null;
        }

        return [
            'partido_id' => (int) $partidoId,
            'ganador_id' => $ganadorId === null ? null : (int) $ganadorId
        ];
    }
}
