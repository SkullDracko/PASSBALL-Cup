<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../services/ResolverPoolCandidatos.php';

class CategoriasVotoController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    public function listar(array $params): void {}      // GET /api/torneos/{torneoId}/categorias-voto
    public function crear(array $params): void {}        // POST /api/torneos/{torneoId}/categorias-voto — nace en estado='cerrada'
    public function actualizar(array $params): void {}   // PATCH .../categorias-voto/{id} — nombre, tipo, modo_candidatos, orden
    public function cambiarEstado(array $params): void {} // PATCH .../categorias-voto/{id}/estado — abierta <-> cerrada
    public function eliminar(array $params): void {}     // DELETE .../categorias-voto/{id}

    public function pool(array $params): void {
        // GET /api/torneos/{torneoId}/categorias-voto/{id}/pool
        $pool = ResolverPoolCandidatos::paraCategoria($this->pdo, (int) $params['id']);
        jsonResponse(true, $pool);
    }
}
