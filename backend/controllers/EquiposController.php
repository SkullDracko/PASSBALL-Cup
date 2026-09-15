<?php
require_once __DIR__ . '/../core/db.php';

class EquiposController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    public function listar(array $params): void {}       // GET /api/equipos — filtro: estado
    public function crear(array $params): void {}         // POST /api/equipos
    public function detalle(array $params): void {}       // GET /api/equipos/{id}
    public function actualizar(array $params): void {}    // PATCH /api/equipos/{id}
    public function cambiarEstado(array $params): void {} // PATCH /api/equipos/{id}/estado
    public function eliminar(array $params): void {}      // DELETE /api/equipos/{id}
}
