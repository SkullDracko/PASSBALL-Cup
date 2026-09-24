<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../core/validator.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../middleware/adminAuth.php';
require_once __DIR__ . '/../services/ResolverPoolCandidatos.php';

class CategoriasVotoController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    /**
     * GET /api/torneos/{torneoId}/categorias-voto
     * Lista las categorías del torneo ordenadas por `orden`.
     * Necesario para pintar la boleta del usuario, por eso exige sesión.
     */
    public function listar(array $params): void
    {
        requireAuthAPI();
        $torneoId = $this->obtenerId($params, 'torneoId', 'torneo');
        $this->verificarTorneo($torneoId);

        $stmt = $this->pdo->prepare('
            SELECT id, torneo_id, clave, nombre, tipo, modo_candidatos, estado,
                   fecha_ultimo_cambio, orden
            FROM torneo_categorias_voto
            WHERE torneo_id = ?
            ORDER BY orden ASC, id ASC
        ');
        $stmt->execute([$torneoId]);

        jsonResponse(true, [
            'torneo_id'  => $torneoId,
            'categorias' => $stmt->fetchAll()
        ]);
    }

    /**
     * POST /api/torneos/{torneoId}/categorias-voto (admin)
     * La categoría nace SIEMPRE en estado='cerrada' (decisión de producto):
     * el admin la abre con PATCH .../estado cuando quiera aceptar votos.
     */
    public function crear(array $params): void
    {
        requireAdminAPI();
        $torneoId = $this->obtenerId($params, 'torneoId', 'torneo');
        $this->verificarTorneo($torneoId);

        $body = jsonBody();
        requerirCampos($body, ['clave', 'nombre', 'tipo']);

        $clave         = trim((string) $body['clave']);
        $nombre        = trim((string) $body['nombre']);
        $tipo          = (string) $body['tipo'];
        $modoCandidatos = (string) ($body['modo_candidatos'] ?? 'automatico');
        $orden         = $body['orden'] ?? 0;

        $this->validarClave($clave);
        $this->validarNombre($nombre);
        requerirEnum($tipo, ['jugador', 'equipo'], 'tipo');
        requerirEnum($modoCandidatos, ['automatico', 'manual'], 'modo_candidatos');
        $orden = $this->validarOrden($orden);

        // clave es única por torneo (uq_torneo_categoria_clave). Pre-validar a mano
        // para responder 409 con un mensaje claro en vez de un error de UNIQUE.
        $stmt = $this->pdo->prepare(
            'SELECT id FROM torneo_categorias_voto WHERE torneo_id = ? AND clave = ? LIMIT 1'
        );
        $stmt->execute([$torneoId, $clave]);
        if ($stmt->fetch()) {
            jsonResponse(false, [], [
                'error' => "Ya existe una categoría con la clave '{$clave}' en este torneo"
            ], 409);
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO torneo_categorias_voto
                (torneo_id, clave, nombre, tipo, modo_candidatos, estado, orden)
            VALUES (?, ?, ?, ?, ?, "cerrada", ?)
        ');
        $stmt->execute([$torneoId, $clave, $nombre, $tipo, $modoCandidatos, $orden]);

        jsonResponse(true, [
            'mensaje' => 'Categoría de votación creada correctamente (en estado cerrada)',
            'id'      => (int) $this->pdo->lastInsertId()
        ], [], 201);
    }

    /**
     * PATCH /api/torneos/{torneoId}/categorias-voto/{id} (admin)
     * Campos editables: nombre, tipo, modo_candidatos, orden.
     *
     * Regla de negocio: si el `tipo` fuera a cambiar y la categoría ya tiene votos
     * emitidos o ajustes de candidatos del otro tipo, se rechaza con 409 — cambiar
     * el tipo bajo datos existentes dejaría registros incoherentes (un voto a un
     * jugador dentro de una categoría ahora de equipos).
     */
    public function actualizar(array $params): void
    {
        requireAdminAPI();
        $torneoId  = $this->obtenerId($params, 'torneoId', 'torneo');
        $id        = $this->obtenerId($params, 'id', 'categoría');
        $this->verificarTorneo($torneoId);
        $actual = $this->verificarCategoria($torneoId, $id);

        $body = jsonBody();
        $campos = [];
        $values = [];

        if (array_key_exists('nombre', $body)) {
            $this->validarNombre(trim((string) $body['nombre']));
            $campos[] = 'nombre = ?';
            $values[] = trim((string) $body['nombre']);
        }

        if (array_key_exists('tipo', $body)) {
            requerirEnum((string) $body['tipo'], ['jugador', 'equipo'], 'tipo');
            if ((string) $body['tipo'] !== $actual['tipo']) {
                $this->verificarCambioTipo($id);
            }
            $campos[] = 'tipo = ?';
            $values[] = (string) $body['tipo'];
        }

        if (array_key_exists('modo_candidatos', $body)) {
            requerirEnum((string) $body['modo_candidatos'], ['automatico', 'manual'], 'modo_candidatos');
            $campos[] = 'modo_candidatos = ?';
            $values[] = (string) $body['modo_candidatos'];
        }

        if (array_key_exists('orden', $body)) {
            $campos[] = 'orden = ?';
            $values[] = $this->validarOrden($body['orden']);
        }

        if (!$campos) {
            jsonResponse(false, [], ['error' => 'No hay campos válidos para actualizar'], 400);
        }

        $values[] = $id;
        $stmt = $this->pdo->prepare(
            'UPDATE torneo_categorias_voto SET ' . implode(', ', $campos) . ' WHERE id = ?'
        );
        $stmt->execute($values);

        jsonResponse(true, ['mensaje' => 'Categoría actualizada correctamente']);
    }

    /**
     * PATCH /api/torneos/{torneoId}/categorias-voto/{id}/estado (admin)
     * Abre/cierra la votación. `fecha_ultimo_cambio` se actualiza sola por el
     * ON UPDATE CURRENT_TIMESTAMP de la columna.
     */
    public function cambiarEstado(array $params): void
    {
        requireAdminAPI();
        $torneoId = $this->obtenerId($params, 'torneoId', 'torneo');
        $id       = $this->obtenerId($params, 'id', 'categoría');
        $this->verificarTorneo($torneoId);
        $this->verificarCategoria($torneoId, $id);

        $body = jsonBody();
        requerirCampos($body, ['estado']);
        requerirEnum((string) $body['estado'], ['abierta', 'cerrada'], 'estado');

        $stmt = $this->pdo->prepare('UPDATE torneo_categorias_voto SET estado = ? WHERE id = ?');
        $stmt->execute([(string) $body['estado'], $id]);

        jsonResponse(true, [
            'mensaje' => "Categoría {$id} en estado '{$body['estado']}'"
        ]);
    }

    /**
     * DELETE /api/torneos/{torneoId}/categorias-voto/{id} (admin)
     * Solo se elimina si no tiene ajustes de candidatos ni votos emitidos;
     * borrarla con datos asociados dejaría huérfanos o perdería históricos.
     */
    public function eliminar(array $params): void
    {
        requireAdminAPI();
        $torneoId = $this->obtenerId($params, 'torneoId', 'torneo');
        $id       = $this->obtenerId($params, 'id', 'categoría');
        $this->verificarTorneo($torneoId);
        $this->verificarCategoria($torneoId, $id);

        foreach (['torneo_categoria_candidatos', 'torneo_votos'] as $tabla) {
            $stmt = $this->pdo->prepare("SELECT id FROM {$tabla} WHERE categoria_id = ? LIMIT 1");
            $stmt->execute([$id]);
            if ($stmt->fetch()) {
                jsonResponse(false, [], [
                    'error' => 'No se puede eliminar: la categoría tiene candidatos o votos asociados'
                ], 409);
            }
        }

        $stmt = $this->pdo->prepare('DELETE FROM torneo_categorias_voto WHERE id = ?');
        $stmt->execute([$id]);

        jsonResponse(true, ['mensaje' => 'Categoría eliminada correctamente']);
    }

    /**
     * GET /api/torneos/{torneoId}/categorias-voto/{id}/pool
     * Pool resuelto para pintar la boleta. Usa ResolverPoolCandidatos como única
     * fuente de verdad (mismo cálculo que valida POST /votos).
     */
    public function pool(array $params): void
    {
        requireAuthAPI();
        $torneoId = $this->obtenerId($params, 'torneoId', 'torneo');
        $id       = $this->obtenerId($params, 'id', 'categoría');
        $this->verificarTorneo($torneoId);
        $this->verificarCategoria($torneoId, $id);

        $pool = ResolverPoolCandidatos::paraCategoria($this->pdo, $id);
        jsonResponse(true, $pool);
    }

    // ------------------------------------------------------------------
    // Helpers privados (copias del patrón usado en los demás controllers)
    // ------------------------------------------------------------------

    private function obtenerId(array $datos, string $campo, string $entidad): int
    {
        $id = $datos[$campo] ?? null;

        if (!filter_var($id, FILTER_VALIDATE_INT) || (int) $id <= 0) {
            jsonResponse(false, [], ["error" => "ID de {$entidad} inválido"], 400);
        }

        return (int) $id;
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
     * Devuelve la fila actual (necesaria para comparar el `tipo` en actualizar()).
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

    private function validarClave(string $clave): void
    {
        if ($clave === '' || mb_strlen($clave) > 30) {
            jsonResponse(false, [], ['error' => 'La clave debe tener entre 1 y 30 caracteres'], 422);
        }
    }

    private function validarNombre(string $nombre): void
    {
        if ($nombre === '' || mb_strlen($nombre) > 100) {
            jsonResponse(false, [], ['error' => 'El nombre debe tener entre 1 y 100 caracteres'], 422);
        }
    }

    private function validarOrden($orden): int
    {
        // Ojo con filter_var: devuelve 0 (falsy) para el 0 válido, por eso se
        // comprueba $orden === 0 de forma explícita antes de negarlo.
        if (
            $orden === null
            || $orden === ''
            || filter_var($orden, FILTER_VALIDATE_INT) === false
            || (int) $orden < 0
        ) {
            jsonResponse(false, [], ['error' => 'El orden debe ser un entero >= 0'], 422);
        }

        return (int) $orden;
    }

    /**
     * Bloquea el cambio de `tipo` cuando la categoría ya tiene datos del tipo actual.
     */
    private function verificarCambioTipo(int $categoriaId): void
    {
        foreach (['torneo_categoria_candidatos', 'torneo_votos'] as $tabla) {
            $stmt = $this->pdo->prepare("SELECT id FROM {$tabla} WHERE categoria_id = ? LIMIT 1");
            $stmt->execute([$categoriaId]);
            if ($stmt->fetch()) {
                jsonResponse(false, [], [
                    'error' => 'No se puede cambiar el tipo: la categoría ya tiene candidatos o votos'
                ], 409);
            }
        }
    }
}