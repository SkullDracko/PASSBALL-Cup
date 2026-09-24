<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../core/validator.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../services/ResolverPoolCandidatos.php';

class VotosController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    /**
     * POST /api/torneos/{torneoId}/votos
     *
     * Emite (o actualiza) mi voto. Es un UPSERT sobre uq_voto_usuario_categoria:
     * un usuario solo tiene un voto vigente por categoría y puede cambiarlo.
     *
     * Reglas que NO viven en la BD y por eso se validan aquí:
     *  1. la categoría debe estar en estado 'abierta';
     *  2. el candidato debe pertenecer al pool resuelto de la categoría
     *     (ResolverPoolCandidatos, misma fuente que GET /pool);
     *  3. el candidato debe corresponder al `tipo` de la categoría.
     */
    public function emitir(array $params): void
    {
        $usuarioId = requireAuthAPI();
        $torneoId  = $this->obtenerId($params, 'torneoId', 'torneo');
        $this->verificarTorneo($torneoId);

        $body = jsonBody();
        requerirCampos($body, ['categoria_id']);

        $categoriaId = $this->obtenerId($body, 'categoria_id', 'categoría');
        $categoria   = $this->verificarCategoria($torneoId, $categoriaId);

        if ($categoria['estado'] !== 'abierta') {
            jsonResponse(false, [], ['error' => 'Esta categoría no está abierta para votación'], 422);
        }

        // El candidato a votar depende del tipo: nunca ambos.
        $jugadorId = $this->idOrNull($body, 'jugador_id');
        $equipoId  = $this->idOrNull($body, 'equipo_id');

        if ($categoria['tipo'] === 'jugador') {
            if ($jugadorId === null || $equipoId !== null) {
                jsonResponse(false, [], [
                    'error' => 'Esta categoría es de tipo "jugador": envía solo jugador_id'
                ], 422);
            }
            $this->verificarJugador($jugadorId);
        } else {
            if ($equipoId === null || $jugadorId !== null) {
                jsonResponse(false, [], [
                    'error' => 'Esta categoría es de tipo "equipo": envía solo equipo_id'
                ], 422);
            }
            $this->verificarEquipo($equipoId);
        }

        // Validación contra el pool resuelto: evita votos "escritos a mano"
        // por alguien que no fue candidato (documentado). Comparisons en int.
        $pool = ResolverPoolCandidatos::paraCategoria($this->pdo, $categoriaId);
        $enPool = false;
        foreach ($pool['candidatos'] as $candidato) {
            if ($categoria['tipo'] === 'jugador' && (int) ($candidato['jugador_id'] ?? 0) === $jugadorId) {
                $enPool = true;
                break;
            }
            if ($categoria['tipo'] === 'equipo' && (int) ($candidato['equipo_id'] ?? 0) === $equipoId) {
                $enPool = true;
                break;
            }
        }

        if (!$enPool) {
            jsonResponse(false, [], [
                'error' => 'El candidato no pertenece al pool de candidatos de esta categoría'
            ], 422);
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO torneo_votos (torneo_id, usuario_id, categoria_id, jugador_id, equipo_id)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                jugador_id = VALUES(jugador_id),
                equipo_id  = VALUES(equipo_id),
                fecha_voto = CURRENT_TIMESTAMP
        ');
        $stmt->execute([$torneoId, $usuarioId, $categoriaId, $jugadorId, $equipoId]);

        jsonResponse(true, [
            'mensaje' => 'Voto registrado correctamente (se actualiza si ya habías votado en esta categoría)'
        ]);
    }

    /**
     * GET /api/torneos/{torneoId}/votos/mios
     * Mis votos vigentes en el torneo, uno por categoría, con el nombre del
     * candidato elegido para pintar la boleta como "ya voté por...".
     */
    public function mios(array $params): void
    {
        $usuarioId = requireAuthAPI();
        $torneoId  = $this->obtenerId($params, 'torneoId', 'torneo');
        $this->verificarTorneo($torneoId);

        $stmt = $this->pdo->prepare('
            SELECT v.categoria_id, v.jugador_id, v.equipo_id, v.fecha_voto,
                   c.clave, c.nombre AS categoria_nombre, c.tipo AS categoria_tipo,
                   u.nombre AS jugador_nombre, u.matricula,
                   e.nombre AS equipo_nombre
            FROM torneo_votos v
            INNER JOIN torneo_categorias_voto c ON c.id = v.categoria_id
            LEFT JOIN usuarios u ON u.id = v.jugador_id
            LEFT JOIN equipos e  ON e.id = v.equipo_id
            WHERE v.torneo_id = ? AND v.usuario_id = ?
            ORDER BY v.fecha_voto DESC
        ');
        $stmt->execute([$torneoId, $usuarioId]);

        jsonResponse(true, [
            'torneo_id' => $torneoId,
            'mis_votos' => $stmt->fetchAll()
        ]);
    }

    /**
     * GET /api/torneos/{torneoId}/votos/resultados?categoria_id=
     * Conteo agregado por candidato para pintar el ranking de una categoría.
     * Requiere `categoria_id` en el query string.
     */
    public function resultados(array $params): void
    {
        requireAuthAPI();
        $torneoId = $this->obtenerId($params, 'torneoId', 'torneo');
        $this->verificarTorneo($torneoId);

        $categoriaId = $this->obtenerId($_GET, 'categoria_id', 'categoría');
        $categoria   = $this->verificarCategoria($torneoId, $categoriaId);

        // El GROUP BY y los campos dependen del tipo: agrupar por NULL (equipos
        // en una categoría de jugador) mezclaría resultados, así que se separa.
        if ($categoria['tipo'] === 'equipo') {
            $stmt = $this->pdo->prepare('
                SELECT v.equipo_id, e.nombre AS equipo_nombre,
                       COUNT(*) AS votos
                FROM torneo_votos v
                LEFT JOIN equipos e ON e.id = v.equipo_id
                WHERE v.torneo_id = ? AND v.categoria_id = ? AND v.equipo_id IS NOT NULL
                GROUP BY v.equipo_id, e.nombre
                ORDER BY votos DESC, v.equipo_id ASC
            ');
            $stmt->execute([$torneoId, $categoriaId]);
        } else {
            // Para jugadores se adjunta también su equipo actual (membresía activa)
            // para que la UI pueda agrupar visualmente por equipo.
            $stmt = $this->pdo->prepare('
                SELECT v.jugador_id, u.nombre AS jugador_nombre, u.matricula,
                       em.equipo_id, e.nombre AS equipo_nombre,
                       COUNT(*) AS votos
                FROM torneo_votos v
                LEFT JOIN usuarios u ON u.id = v.jugador_id
                LEFT JOIN equipo_miembros em ON em.jugador_id = v.jugador_id AND em.estado = "activo"
                LEFT JOIN equipos e ON e.id = em.equipo_id
                WHERE v.torneo_id = ? AND v.categoria_id = ? AND v.jugador_id IS NOT NULL
                GROUP BY v.jugador_id, u.nombre, u.matricula, em.equipo_id, e.nombre
                ORDER BY votos DESC, v.jugador_id ASC
            ');
            $stmt->execute([$torneoId, $categoriaId]);
        }

        jsonResponse(true, [
            'categoria_id'    => $categoriaId,
            'categoria_nombre' => $categoria['nombre'],
            'tipo'             => $categoria['tipo'],
            'resultados'       => $stmt->fetchAll()
        ]);
    }

    /**
     * DELETE /api/torneos/{torneoId}/votos/{categoriaId}
     * Retira mi voto de una categoría del torneo.
     */
    public function retirar(array $params): void
    {
        $usuarioId = requireAuthAPI();
        $torneoId  = $this->obtenerId($params, 'torneoId', 'torneo');
        $this->verificarTorneo($torneoId);

        $categoriaId = $this->obtenerId($params, 'categoriaId', 'categoría');
        $this->verificarCategoria($torneoId, $categoriaId);

        $stmt = $this->pdo->prepare('
            DELETE FROM torneo_votos
            WHERE torneo_id = ? AND usuario_id = ? AND categoria_id = ?
        ');
        $stmt->execute([$torneoId, $usuarioId, $categoriaId]);

        if ($stmt->rowCount() === 0) {
            jsonResponse(false, [], ['error' => 'No tienes un voto en esta categoría'], 404);
        }

        jsonResponse(true, ['mensaje' => 'Voto retirado correctamente']);
    }

    // ------------------------------------------------------------------
    // Helpers privados (mismo patrón que los demás controllers)
    // ------------------------------------------------------------------

    private function obtenerId(array $datos, string $campo, string $entidad): int
    {
        $id = $datos[$campo] ?? null;

        if (!filter_var($id, FILTER_VALIDATE_INT) || (int) $id <= 0) {
            jsonResponse(false, [], ["error" => "ID de {$entidad} inválido"], 400);
        }

        return (int) $id;
    }

    private function idOrNull(array $datos, string $campo): ?int
    {
        if (!array_key_exists($campo, $datos) || $datos[$campo] === null || $datos[$campo] === '') {
            return null;
        }

        if (!filter_var($datos[$campo], FILTER_VALIDATE_INT) || (int) $datos[$campo] <= 0) {
            jsonResponse(false, [], ["error" => "ID inválido para {$campo}"], 400);
        }

        return (int) $datos[$campo];
    }

    private function verificarTorneo(int $torneoId): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM torneos WHERE id = ? LIMIT 1');
        $stmt->execute([$torneoId]);

        if (!$stmt->fetch()) {
            jsonResponse(false, [], ['error' => 'Torneo no encontrado'], 404);
        }
    }

    /**
     * Verifica que la categoría exista Y pertenezca al torneo de la ruta,
     * devolviendo la fila completa (estado/tipo necesarios en emitir/resultados).
     */
    private function verificarCategoria(int $torneoId, int $categoriaId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, torneo_id, clave, nombre, tipo, estado
             FROM torneo_categorias_voto WHERE id = ? AND torneo_id = ? LIMIT 1'
        );
        $stmt->execute([$categoriaId, $torneoId]);
        $categoria = $stmt->fetch();

        if (!$categoria) {
            jsonResponse(false, [], [
                'error' => 'Categoría de votación no encontrada en este torneo'
            ], 404);
        }

        return $categoria;
    }

    private function verificarJugador(int $jugadorId): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM usuarios WHERE id = ? LIMIT 1');
        $stmt->execute([$jugadorId]);

        if (!$stmt->fetch()) {
            jsonResponse(false, [], ['error' => 'Jugador no encontrado'], 404);
        }
    }

    private function verificarEquipo(int $equipoId): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM equipos WHERE id = ? LIMIT 1');
        $stmt->execute([$equipoId]);

        if (!$stmt->fetch()) {
            jsonResponse(false, [], ['error' => 'Equipo no encontrado'], 404);
        }
    }
}