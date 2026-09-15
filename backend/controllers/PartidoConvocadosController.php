<?php
require_once __DIR__ . '/../core/db.php';

class PartidoConvocadosController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    public function listar(array $params): void {}     // GET /api/partidos/{partidoId}/convocados
    public function convocar(array $params): void {}   // POST /api/partidos/{partidoId}/convocados
    public function actualizar(array $params): void {} // PATCH .../convocados/{jugadorId}
    public function eliminar(array $params): void {}   // DELETE .../convocados/{jugadorId}
}
