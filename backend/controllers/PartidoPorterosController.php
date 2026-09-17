<?php
require_once __DIR__ . '/../core/db.php';

class PartidoPorterosController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    public function listar(array $params): void {}     // GET /api/partidos/{partidoId}/porteros
    public function registrar(array $params): void {}  // POST /api/partidos/{partidoId}/porteros
    public function actualizar(array $params): void {} // PATCH .../porteros/{jugadorId}
    public function eliminar(array $params): void {}   // DELETE .../porteros/{jugadorId}
}
