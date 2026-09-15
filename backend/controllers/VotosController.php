<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../services/ResolverPoolCandidatos.php';

class VotosController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    public function emitir(array $params): void {
        // POST /api/torneos/{torneoId}/votos — upsert sobre uq_voto_usuario_categoria
        $usuarioId = requireAuthAPI();
        $body = jsonBody();
        requerirCampos($body, ['categoria_id']);

        // TODO: 1) cargar categoría, rechazar si estado != 'abierta'
        // TODO: 2) $pool = ResolverPoolCandidatos::paraCategoria($this->pdo, $body['categoria_id']);
        //          rechazar si el candidato no está en $pool
        // TODO: 3) INSERT ... ON DUPLICATE KEY UPDATE (o SELECT+UPDATE/INSERT)
    }

    public function mios(array $params): void {
        // GET /api/torneos/{torneoId}/votos/mios
        $usuarioId = requireAuthAPI();
    }

    public function resultados(array $params): void {
        // GET /api/torneos/{torneoId}/votos/resultados?categoria_id=
        // COUNT(*) agrupado por jugador_id/equipo_id
    }

    public function retirar(array $params): void {
        // DELETE /api/torneos/{torneoId}/votos/{categoriaId}
        $usuarioId = requireAuthAPI();
    }
}
