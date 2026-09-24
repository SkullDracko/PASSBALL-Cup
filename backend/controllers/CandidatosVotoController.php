<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../core/validator.php';
require_once __DIR__ . '/../middleware/adminAuth.php';

class CandidatosVotoController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    /**
     * GET /api/torneos/{torneoId}/categorias-voto/{categoriaId}/candidatos (admin)
     * Devuelve los ajustes CRUDOS (incluir/excluir) sin resolver contra el pool
     * automático — es la pantalla de edición del admin, no la boleta pública.
     */
    public function listar(array $params): void
    {
        requireAdminAPI();
        $torneoId    = $this->obtenerId($params, 'torneoId', 'torneo');
        $categoriaId = $this->obtenerId($params, 'categoriaId', 'categoría');
        $this->verificarTorneo($torneoId);
        $this->verificarCategoria($torneoId, $categoriaId);

        $stmt = $this->pdo->prepare('
            SELECT tcc.id, tcc.categoria_id, tcc.ajuste,
                   tcc.jugador_id, tcc.equipo_id,
                   u.nombre AS jugador_nombre, u.matricula,
                   e.nombre AS equipo_nombre
            FROM torneo_categoria_candidatos tcc
            LEFT JOIN usuarios u ON u.id = tcc.jugador_id
            LEFT JOIN equipos e  ON e.id = tcc.equipo_id
            WHERE tcc.categoria_id = ?
            ORDER BY tcc.id ASC
        ');
        $stmt->execute([$categoriaId]);

        jsonResponse(true, [
            'categoria_id' => $categoriaId,
            'ajustes'      => $stmt->fetchAll()
        ]);
    }

    /**
     * POST /api/torneos/{torneoId}/categorias-voto/{categoriaId}/candidatos (admin)
     *
     * Regla de negocio (sql/endpoints_implementacion.md §18): el CHECK de la tabla
     * solo garantiza que se envíe UNO de jugador_id/equipo_id; aquí además se
     * valida que el candidato corresponda al `tipo` de la categoría, porque
     * "jugador dentro de una categoría de equipos" dejaría votos incoherentes.
     *
     * Upsert: si el jugador/equipo ya tenía un ajuste en esta categoría
     * (uq_cand_categoria_jugador / uq_cand_categoria_equipo), se actualiza el
     * `ajuste` en lugar de fallar — decision: re-saveable desde la UI del admin.
     */
    public function agregar(array $params): void
    {
        requireAdminAPI();
        $torneoId    = $this->obtenerId($params, 'torneoId', 'torneo');
        $categoriaId = $this->obtenerId($params, 'categoriaId', 'categoría');
        $this->verificarTorneo($torneoId);
        $categoria = $this->verificarCategoria($torneoId, $categoriaId);

        $body = jsonBody();
        requerirCampos($body, ['ajuste']);
        requerirEnum((string) $body['ajuste'], ['incluir', 'excluir'], 'ajuste');

        $jugadorId = $this->idOrNull($body, 'jugador_id');
        $equipoId  = $this->idOrNull($body, 'equipo_id');

        // XOR: exactamente uno de los dos según el tipo de la categoría.
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

        $stmt = $this->pdo->prepare('
            INSERT INTO torneo_categoria_candidatos (categoria_id, jugador_id, equipo_id, ajuste)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE ajuste = VALUES(ajuste)
        ');
        $stmt->execute([$categoriaId, $jugadorId, $equipoId, (string) $body['ajuste']]);

        jsonResponse(true, [
            'mensaje'    => 'Ajuste de candidato guardado correctamente',
            'categoria_id' => $categoriaId,
            'jugador_id' => $jugadorId,
            'equipo_id'  => $equipoId,
            'ajuste'     => (string) $body['ajuste']
        ], [], 201);
    }

    /**
     * DELETE .../candidatos/{id} (admin)
     * Quita el ajuste. En modo automático el candidato "vuelve" al pool calculado;
     * en modo manual deja de aparecer en la boleta.
     */
    public function eliminar(array $params): void
    {
        requireAdminAPI();
        $torneoId    = $this->obtenerId($params, 'torneoId', 'torneo');
        $categoriaId = $this->obtenerId($params, 'categoriaId', 'categoría');
        $id          = $this->obtenerId($params, 'id', 'candidato');
        $this->verificarTorneo($torneoId);
        $this->verificarCategoria($torneoId, $categoriaId);

        $stmt = $this->pdo->prepare(
            'DELETE FROM torneo_categoria_candidatos WHERE id = ? AND categoria_id = ?'
        );
        $stmt->execute([$id, $categoriaId]);

        if ($stmt->rowCount() === 0) {
            jsonResponse(false, [], ['error' => 'Candidato no encontrado en esta categoría'], 404);
        }

        jsonResponse(true, ['mensaje' => 'Candidato eliminado correctamente']);
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

    /**
     * ID opcional del body: devuelve null si no viene o viene vacío,
     * 400 si viene pero no es un entero positivo.
     */
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
     * Verifica que la categoría exista Y pertenezca al torneo de la ruta.
     * Devuelve la fila con `tipo` (necesario para validar el candidato en agregar()).
     */
    private function verificarCategoria(int $torneoId, int $categoriaId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, torneo_id, tipo FROM torneo_categorias_voto WHERE id = ? AND torneo_id = ? LIMIT 1'
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