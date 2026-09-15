<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../services/FinalizarPartido.php';

class PartidosController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    public function listar(array $params): void {}     // GET /api/partidos — filtros: ronda_id, equipo_id, estado
    public function crear(array $params): void {}       // POST /api/partidos
    public function detalle(array $params): void {}     // GET /api/partidos/{id}
    public function actualizar(array $params): void {}  // PATCH /api/partidos/{id}
    public function resultado(array $params): void {}   // PATCH /api/partidos/{id}/resultado
    public function eliminar(array $params): void {}    // DELETE /api/partidos/{id}

    public function finalizar(array $params): void {
        // PATCH /api/partidos/{id}/finalizar
        FinalizarPartido::ejecutar($this->pdo, (int) $params['id']);
        jsonResponse(true);
    }
}
