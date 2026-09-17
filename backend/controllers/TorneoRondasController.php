<?php
require_once __DIR__ . '/../core/db.php';

class TorneoRondasController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    public function listar(array $params): void {}    // GET /api/torneos/{torneoId}/rondas
    public function crear(array $params): void {}      // POST /api/torneos/{torneoId}/rondas
    public function actualizar(array $params): void {} // PATCH /api/torneos/{torneoId}/rondas/{id}
    public function eliminar(array $params): void {}   // DELETE /api/torneos/{torneoId}/rondas/{id}
}
