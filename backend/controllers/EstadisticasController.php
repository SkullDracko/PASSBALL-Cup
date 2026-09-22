<?php
require_once __DIR__ . '/../core/db.php';

// Solo lectura: agregados calculados con JOIN/GROUP BY, sin tabla propia.
class EstadisticasController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    public function goleadores(array $params): void
    {
        $torneoId = $this->torneoId();
        $stmt = $this->pdo->prepare('
            SELECT pe.jugador_id, u.matricula,
                   COUNT(*) AS goles
            FROM partido_eventos pe
            INNER JOIN partidos p ON p.id = pe.partido_id
            INNER JOIN torneo_rondas r ON r.id = p.ronda_id
            INNER JOIN usuarios u ON u.id = pe.jugador_id
            WHERE r.torneo_id = ?
              AND p.estado = "finalizado"
              AND pe.tipo IN ("gol", "penal_anotado")
            GROUP BY pe.jugador_id, u.matricula
            ORDER BY goles DESC, pe.jugador_id ASC
        ');
        $stmt->execute([$torneoId]);

        jsonResponse(true, [
            'torneo_id' => $torneoId,
            'goleadores' => $stmt->fetchAll()
        ]);
    }

    public function asistencias(array $params): void
    {
        $torneoId = $this->torneoId();
        $stmt = $this->pdo->prepare('
            SELECT pe.asistencia_jugador_id AS jugador_id,
                   u.matricula,
                   COUNT(*) AS asistencias
            FROM partido_eventos pe
            INNER JOIN partidos p ON p.id = pe.partido_id
            INNER JOIN torneo_rondas r ON r.id = p.ronda_id
            INNER JOIN usuarios u ON u.id = pe.asistencia_jugador_id
            WHERE r.torneo_id = ?
              AND p.estado = "finalizado"
              AND pe.asistencia_jugador_id IS NOT NULL
            GROUP BY pe.asistencia_jugador_id, u.matricula
            ORDER BY asistencias DESC, pe.asistencia_jugador_id ASC
        ');
        $stmt->execute([$torneoId]);

        jsonResponse(true, [
            'torneo_id' => $torneoId,
            'asistencias' => $stmt->fetchAll()
        ]);
    }

    public function tarjetas(array $params): void
    {
        $torneoId = $this->torneoId();
        $stmt = $this->pdo->prepare('
            SELECT pe.jugador_id, u.matricula, pe.tipo,
                   COUNT(*) AS cantidad
            FROM partido_eventos pe
            INNER JOIN partidos p ON p.id = pe.partido_id
            INNER JOIN torneo_rondas r ON r.id = p.ronda_id
            INNER JOIN usuarios u ON u.id = pe.jugador_id
            WHERE r.torneo_id = ?
              AND p.estado = "finalizado"
              AND pe.tipo IN ("tarjeta_amarilla", "tarjeta_roja")
            GROUP BY pe.jugador_id, u.matricula, pe.tipo
            ORDER BY cantidad DESC, pe.jugador_id ASC, pe.tipo ASC
        ');
        $stmt->execute([$torneoId]);

        jsonResponse(true, [
            'torneo_id' => $torneoId,
            'tarjetas' => $stmt->fetchAll()
        ]);
    }

    public function porteros(array $params): void
    {
        $torneoId = $this->torneoId();
        $stmt = $this->pdo->prepare('
            SELECT pep.jugador_id, u.matricula,
                   SUM(pep.atajadas) AS atajadas,
                   SUM(pep.goles_recibidos) AS goles_recibidos
            FROM partido_estadisticas_portero pep
            INNER JOIN partidos p ON p.id = pep.partido_id
            INNER JOIN torneo_rondas r ON r.id = p.ronda_id
            INNER JOIN usuarios u ON u.id = pep.jugador_id
            WHERE r.torneo_id = ?
              AND p.estado = "finalizado"
            GROUP BY pep.jugador_id, u.matricula
            ORDER BY atajadas DESC, pep.jugador_id ASC
        ');
        $stmt->execute([$torneoId]);

        jsonResponse(true, [
            'torneo_id' => $torneoId,
            'porteros' => $stmt->fetchAll()
        ]);
    }

    public function tablaPosiciones(array $params): void
    {
        $torneoId = $this->torneoId();
        $stmt = $this->pdo->prepare('
            SELECT t.equipo_id, e.nombre,
                   COUNT(p.id) AS partidos_jugados,
                   SUM(CASE WHEN p.ganador_id = t.equipo_id THEN 1 ELSE 0 END) AS victorias,
                   SUM(CASE WHEN p.ganador_id IS NOT NULL AND p.ganador_id <> t.equipo_id THEN 1 ELSE 0 END) AS derrotas,
                   SUM(CASE WHEN p.ganador_id IS NULL AND p.goles_local = p.goles_visitante THEN 1 ELSE 0 END) AS empates,
                   SUM(CASE WHEN p.equipo_local_id = t.equipo_id THEN COALESCE(p.goles_local, 0) ELSE COALESCE(p.goles_visitante, 0) END) AS goles_favor,
                   SUM(CASE WHEN p.equipo_local_id = t.equipo_id THEN COALESCE(p.goles_visitante, 0) ELSE COALESCE(p.goles_local, 0) END) AS goles_contra
            FROM (
                SELECT DISTINCT equipo_id
                FROM torneo_equipos
                WHERE torneo_id = ? AND estado = "aprobado"
            ) t
            INNER JOIN equipos e ON e.id = t.equipo_id
            LEFT JOIN (
                SELECT p.id, p.equipo_local_id, p.equipo_visitante_id,
                       p.goles_local, p.goles_visitante, p.ganador_id
                FROM partidos p
                INNER JOIN torneo_rondas r ON r.id = p.ronda_id
                WHERE r.torneo_id = ? AND p.estado = "finalizado"
            ) p ON p.equipo_local_id = t.equipo_id OR p.equipo_visitante_id = t.equipo_id
            GROUP BY t.equipo_id, e.nombre
            ORDER BY victorias DESC, (goles_favor - goles_contra) DESC, goles_favor DESC, e.nombre ASC
        ');
        $stmt->execute([$torneoId, $torneoId]);
        $tabla = $stmt->fetchAll();

        foreach ($tabla as &$fila) {
            $fila['diferencia'] = (int) $fila['goles_favor'] - (int) $fila['goles_contra'];
        }
        unset($fila);

        jsonResponse(true, [
            'torneo_id' => $torneoId,
            'tabla_posiciones' => $tabla
        ]);
    }

    public function jugador(array $params): void
    {
        $jugadorId = $this->obtenerId($params, 'jugadorId');
        $this->verificarJugador($jugadorId);

        $estadisticas = [
            'jugador_id' => $jugadorId,
            'convocatorias' => 0,
            'titularidades' => 0,
            'goles' => 0,
            'asistencias' => 0,
            'tarjetas_amarillas' => 0,
            'tarjetas_rojas' => 0
        ];

        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) AS convocatorias,
                   COALESCE(SUM(CASE WHEN titular = 1 THEN 1 ELSE 0 END), 0) AS titularidades
            FROM partido_convocados
            WHERE jugador_id = ?
        ');
        $stmt->execute([$jugadorId]);
        $estadisticas = array_merge($estadisticas, $stmt->fetch() ?: []);

        $stmt = $this->pdo->prepare('
            SELECT
                COALESCE(SUM(CASE WHEN tipo IN ("gol", "penal_anotado") THEN 1 ELSE 0 END), 0) AS goles,
                COALESCE(SUM(CASE WHEN tipo = "tarjeta_amarilla" THEN 1 ELSE 0 END), 0) AS tarjetas_amarillas,
                COALESCE(SUM(CASE WHEN tipo = "tarjeta_roja" THEN 1 ELSE 0 END), 0) AS tarjetas_rojas
            FROM partido_eventos
            WHERE jugador_id = ?
        ');
        $stmt->execute([$jugadorId]);
        $estadisticas = array_merge($estadisticas, $stmt->fetch() ?: []);

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS asistencias FROM partido_eventos WHERE asistencia_jugador_id = ?'
        );
        $stmt->execute([$jugadorId]);
        $estadisticas = array_merge($estadisticas, $stmt->fetch() ?: []);

        foreach (['convocatorias', 'titularidades', 'goles', 'asistencias', 'tarjetas_amarillas', 'tarjetas_rojas'] as $campo) {
            $estadisticas[$campo] = (int) $estadisticas[$campo];
        }

        $stmt = $this->pdo->prepare('SELECT id, matricula, rol, estado, jugador_activo FROM usuarios WHERE id = ? LIMIT 1');
        $stmt->execute([$jugadorId]);

        jsonResponse(true, [
            'jugador' => $stmt->fetch(),
            'estadisticas' => $estadisticas
        ]);
    }

    private function torneoId(): int
    {
        $filtros = $_GET;
        $torneoId = $this->obtenerId($filtros, 'torneo_id');
        $stmt = $this->pdo->prepare('SELECT id FROM torneos WHERE id = ? LIMIT 1');
        $stmt->execute([$torneoId]);

        if (!$stmt->fetch()) {
            jsonResponse(false, [], ['error' => 'Torneo no encontrado'], 404);
        }

        return $torneoId;
    }

    private function obtenerId(array $datos, string $campo): int
    {
        $id = $datos[$campo] ?? null;
        if (!filter_var($id, FILTER_VALIDATE_INT) || (int) $id <= 0) {
            jsonResponse(false, [], ['error' => "ID inválido para {$campo}"], 400);
        }
        return (int) $id;
    }

    private function verificarJugador(int $jugadorId): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM usuarios WHERE id = ? LIMIT 1');
        $stmt->execute([$jugadorId]);
        if (!$stmt->fetch()) {
            jsonResponse(false, [], ['error' => 'Jugador no encontrado'], 404);
        }
    }
}
