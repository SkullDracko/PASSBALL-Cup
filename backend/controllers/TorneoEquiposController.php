<?php
require_once __DIR__ . '/../core/db.php';

class TorneoEquiposController {
    private PDO $pdo;
    public function __construct() { $this->pdo = conectarDB(); }

    public function listar(array $params): void {}    // GET /api/torneos/{torneoId}/equipos — filtro: estado
    public function solicitar(array $params): void {} // POST /api/torneos/{torneoId}/equipos
    public function aprobar(array $params): void {}   // PATCH .../equipos/{equipoId}/aprobar
    public function rechazar(array $params): void {}  // PATCH .../equipos/{equipoId}/rechazar
    public function retirar(array $params): void {}   // PATCH .../equipos/{equipoId}/retirar
}
