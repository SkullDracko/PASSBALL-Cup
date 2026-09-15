<?php
require_once __DIR__ . '/../core/db.php';

class TorneosController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    public function listar(array $params): void {}    // GET /api/torneos
    public function crear(array $params): void {}      // POST /api/torneos
    public function detalle(array $params): void {}    // GET /api/torneos/{id}
    public function actualizar(array $params): void {} // PATCH /api/torneos/{id}
    public function eliminar(array $params): void {}   // DELETE /api/torneos/{id}
}
