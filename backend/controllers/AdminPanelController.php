<?php
require_once __DIR__ . '/../core/db.php';

/**
 * Lecturas que el panel admin necesita y que la API no le deja usar.
 *
 * Las equivalents de jugador (CategoriasVotoController::listar,
 * EquipoMiembrosController::listar) exigen sesion de JUGADOR, asi que un
 * administrador recibia 401. No se modifican esos guards: aqui se ofrecen
 * las mismas consultas bajo requireAdminAPI.
 *
 * Las escrituras (aprobar, rechazar, crear ronda, registrar resultado...)
 * si estan expuestas en la API y el panel las usa directamente.
 */
class AdminPanelController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    /**
     * Categorias de votacion de un torneo, con su conteo de votos.
     * El legacy hacia la categoria y el COUNT como subconsulta correlacionada.
     */
    public function categoriasVoto(array $params): void {
        // GET /api/admin/torneos/{torneoId}/categorias-voto

        requireAdminAPI();
        $torneoId = (int) ($params['torneoId'] ?? 0);

        if ($torneoId <= 0) {
            jsonResponse(false, [], ['error' => 'Torneo no valido'], 400);
        }

        $stmt = $this->pdo->prepare("
            SELECT
                c.id,
                c.torneo_id,
                c.clave,
                c.nombre,
                c.tipo,
                c.modo_candidatos,
                c.estado,
                c.orden,
                (
                    SELECT COUNT(*)
                    FROM torneo_votos v
                    WHERE v.categoria_id = c.id
                ) AS total_votos
            FROM torneo_categorias_voto c
            WHERE c.torneo_id = ?
            ORDER BY c.orden ASC, c.id ASC
        ");
        $stmt->execute([$torneoId]);

        $categorias = $stmt->fetchAll();

        jsonResponse(true, [
            'categorias' => $categorias,
            'total'      => count($categorias)
        ]);
    }

    /**
     * Ajustes de candidatos de TODAS las categorias de un torneo.
     *
     * El legacy los traia en una sola consulta y los agrupaba por categoria
     * en PHP; se mantiene asi para no multiplicar las peticiones.
     */
    public function candidatosVoto(array $params): void {
        // GET /api/admin/torneos/{torneoId}/candidatos-voto

        requireAdminAPI();
        $torneoId = (int) ($params['torneoId'] ?? 0);

        if ($torneoId <= 0) {
            jsonResponse(false, [], ['error' => 'Torneo no valido'], 400);
        }

        $stmt = $this->pdo->prepare("
            SELECT
                cc.id AS ajuste_id,
                cc.categoria_id,
                cc.ajuste,
                cc.jugador_id,
                cc.equipo_id,
                u.nombre AS jugador_nombre,
                e.nombre AS equipo_nombre
            FROM torneo_categoria_candidatos cc
            JOIN torneo_categorias_voto c ON c.id = cc.categoria_id
            LEFT JOIN usuarios u ON u.id = cc.jugador_id
            LEFT JOIN equipos e ON e.id = cc.equipo_id
            WHERE c.torneo_id = ?
            ORDER BY cc.categoria_id ASC, cc.id ASC
        ");
        $stmt->execute([$torneoId]);

        $porCategoria = [];

        foreach ($stmt->fetchAll() as $ajuste) {
            $porCategoria[(int) $ajuste['categoria_id']][] = $ajuste;
        }

        jsonResponse(true, ['ajustes' => $porCategoria]);
    }

    /**
     * Miembros activos de un equipo.
     * EquipoMiembrosController::listar exige sesion de jugador.
     */
    public function miembrosEquipo(array $params): void {
        // GET /api/admin/equipos/{equipoId}/miembros

        requireAdminAPI();
        $equipoId = (int) ($params['equipoId'] ?? 0);

        if ($equipoId <= 0) {
            jsonResponse(false, [], ['error' => 'Equipo no valido'], 400);
        }

        $stmt = $this->pdo->prepare("
            SELECT
                u.id,
                u.matricula,
                u.nombre,
                em.equipo_id
            FROM equipo_miembros em
            JOIN usuarios u ON u.id = em.jugador_id
            WHERE em.equipo_id = ?
              AND em.estado = 'activo'
            ORDER BY u.nombre ASC
        ");
        $stmt->execute([$equipoId]);

        $miembros = $stmt->fetchAll();

        jsonResponse(true, [
            'miembros' => $miembros,
            'total'    => count($miembros)
        ]);
    }

    /**
     * Jugadores de los equipos APROBADOS de un torneo, con su equipo.
     * Alimenta los selectores de candidatos de la vista Votaciones.
     */
    public function jugadoresTorneo(array $params): void {
        // GET /api/admin/torneos/{torneoId}/jugadores

        requireAdminAPI();
        $torneoId = (int) ($params['torneoId'] ?? 0);

        if ($torneoId <= 0) {
            jsonResponse(false, [], ['error' => 'Torneo no valido'], 400);
        }

        $stmt = $this->pdo->prepare("
            SELECT DISTINCT
                u.id,
                u.nombre,
                e.id AS equipo_id,
                e.nombre AS equipo
            FROM equipo_miembros em
            JOIN usuarios u ON u.id = em.jugador_id
            JOIN equipos e ON e.id = em.equipo_id
            JOIN torneo_equipos te ON te.equipo_id = e.id
            WHERE te.torneo_id = ?
              AND te.estado = 'aprobado'
              AND em.estado = 'activo'
            ORDER BY u.nombre ASC
        ");
        $stmt->execute([$torneoId]);

        $jugadores = $stmt->fetchAll();

        jsonResponse(true, [
            'jugadores' => $jugadores,
            'total'     => count($jugadores)
        ]);
    }

    /**
     * Conteo de votos emitidos. La tarjeta Votos de Inicio lo sacaba de un
     * COUNT directo sobre torneo_votos y no habia endpoint.
     */
    public function votosResumen(array $params): void {
        // GET /api/admin/votos-resumen[?torneo_id=]

        requireAdminAPI();

        $torneoId = (int) ($_GET['torneo_id'] ?? 0);

        if ($torneoId > 0) {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM torneo_votos WHERE torneo_id = ?
            ");
            $stmt->execute([$torneoId]);
        } else {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM torneo_votos");
        }

        jsonResponse(true, [
            'votos' => (int) $stmt->fetchColumn()
        ]);
    }
}
